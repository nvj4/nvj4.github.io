<?php
/**
 * naive_bayes.php
 *
 * Dua bagian:
 *  A. Forecast kg per jenis daging untuk 7 hari ke depan (training: tabel transactions + historical_transactions.jsonl)
 *  B. Klasifikasi KATEGORI (Laku / Cukup Laku / Tidak Laku) memakai Gaussian Naive Bayes,
 *     dilatih & dites (80/20) dari data BERLABEL di kategori_dataset.jsonl, dievaluasi pakai confusion matrix.
 *
 * Kategori TIDAK dilatih dari tabel `transactions` karena tabel itu tidak punya kolom kategori
 * (kategori itu sendiri yang mau diprediksi, jadi hanya data historis berlabel yang dipakai).
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include 'koneksi.php'; // harus menghasilkan $conn (mysqli)

$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : 'run');

define('HISTORICAL_DATA_FILE_JSON', __DIR__ . '/data/historical_transactions.json');
define('HISTORICAL_DATA_FILE_JSONL', __DIR__ . '/data/historical_transactions.jsonl');
define('KATEGORI_DATASET_JSON', __DIR__ . '/data/kategori_dataset.json');
define('KATEGORI_DATASET_JSONL', __DIR__ . '/data/kategori_dataset.jsonl');

/**
 * ================= GAUSSIAN NAIVE BAYES (generik, label bisa int ATAU string) =================
 */
class GaussianNB
{
    private array $classes = [];
    private array $mean = [];   // [label][featureIdx] => mean
    private array $var  = [];   // [label][featureIdx] => var
    private array $prior = [];
    private float $epsilon = 1e-9;
    private int $nFeatures = 0;

    /** @param array<int, array<float>> $X setiap elemen = vektor fitur (1 atau lebih nilai) */
    public function fit(array $X, array $y): void
    {
        $n = count($y);
        $this->nFeatures = count($X[0]);
        $grouped = [];

        foreach ($y as $i => $label) {
            $grouped[$label][] = $X[$i];
        }
        $this->classes = array_keys($grouped);

        // Varian global per fitur, untuk smoothing (sama seperti var_smoothing sklearn)
        $globalVar = [];
        for ($j = 0; $j < $this->nFeatures; $j++) {
            $globalVar[$j] = $this->variance(array_column($X, $j));
        }

        foreach ($grouped as $label => $rows) {
            $count = count($rows);
            $this->prior[$label] = $count / $n;
            for ($j = 0; $j < $this->nFeatures; $j++) {
                $col = array_column($rows, $j);
                $this->mean[$label][$j] = array_sum($col) / $count;
                $smoothing = $this->epsilon * ($globalVar[$j] > 0 ? $globalVar[$j] : 1);
                $this->var[$label][$j] = $this->variance($col) + $smoothing;
            }
        }
    }

    private function variance(array $values): float
    {
        $n = count($values);
        if ($n <= 1) return 0.0;
        $mean = array_sum($values) / $n;
        $sumSq = 0.0;
        foreach ($values as $v) {
            $sumSq += ($v - $mean) ** 2;
        }
        return $sumSq / $n;
    }

    private function gaussianLogPdf(float $x, float $mean, float $var): float
    {
        return -0.5 * log(2 * M_PI * $var) - (($x - $mean) ** 2) / (2 * $var);
    }

    /** @param array<float> $x vektor fitur @return mixed label */
    public function predict(array $x)
    {
        $bestLabel = null;
        $bestScore = -INF;

        foreach ($this->classes as $label) {
            $logProb = log($this->prior[$label]);
            for ($j = 0; $j < $this->nFeatures; $j++) {
                $logProb += $this->gaussianLogPdf($x[$j], $this->mean[$label][$j], $this->var[$label][$j]);
            }
            if ($logProb > $bestScore) {
                $bestScore = $logProb;
                $bestLabel = $label;
            }
        }
        return $bestLabel;
    }

    public function score(array $X, array $y): float
    {
        $correct = 0;
        $n = count($y);
        for ($i = 0; $i < $n; $i++) {
            if ($this->predict($X[$i]) === $y[$i]) {
                $correct++;
            }
        }
        return $n > 0 ? $correct / $n : 0.0;
    }
}

/**
 * ================= UTIL: baca file JSON (array) atau JSONL (baris per objek) =================
 */
function readJsonOrJsonl(string $jsonPath, string $jsonlPath): array
{
    $filePath = null;
    if (file_exists($jsonPath)) {
        $filePath = $jsonPath;
    } elseif (file_exists($jsonlPath)) {
        $filePath = $jsonlPath;
    }
    if ($filePath === null) return [];

    $raw = file_get_contents($filePath);
    if ($raw === false || trim($raw) === '') return [];

    $trimmed = trim($raw);
    $decoded = json_decode($trimmed, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        return isset($decoded[0]) ? $decoded : [$decoded];
    }

    $records = [];
    $lines = preg_split('/\r\n|\r|\n/', $trimmed);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $obj = json_decode($line, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $records[] = $obj;
        }
    }
    return $records;
}

/**
 * ================= BAGIAN A: DATA HISTORIS UNTUK FORECAST KG PER JENIS DAGING =================
 */
function loadHistoricalData(array $meatTypeCache, array &$warnings): array
{
    $records = readJsonOrJsonl(HISTORICAL_DATA_FILE_JSON, HISTORICAL_DATA_FILE_JSONL);

    $result = [];
    $skipped = 0;
    $unmatchedNames = [];

    foreach ($records as $r) {
        if (!isset($r['jumlah_kg']) || !is_numeric($r['jumlah_kg'])) {
            $skipped++;
            continue;
        }

        $meatTypeId = null;
        if (isset($r['meat_type_id']) && is_numeric($r['meat_type_id'])) {
            $meatTypeId = (int) $r['meat_type_id'];
        } elseif (isset($r['jenis_daging'])) {
            $key = strtolower(trim($r['jenis_daging']));
            $meatTypeId = $meatTypeCache[$key] ?? null;
            if ($meatTypeId === null) {
                $unmatchedNames[$r['jenis_daging']] = ($unmatchedNames[$r['jenis_daging']] ?? 0) + 1;
            }
        }

        if ($meatTypeId === null) {
            $skipped++;
            continue;
        }

        $harga = isset($r['harga']) && is_numeric($r['harga']) ? (float) $r['harga'] : null;

        $result[] = [
            'meat_type_id' => $meatTypeId,
            'jumlah_kg' => (float) $r['jumlah_kg'],
            'harga' => $harga,
        ];
    }

    if ($skipped > 0) {
        $warnings[] = "$skipped baris di data historis (forecast kg) dilewati";
    }
    if (!empty($unmatchedNames)) {
        arsort($unmatchedNames);
        $topUnmatched = array_slice($unmatchedNames, 0, 15, true);
        $list = [];
        foreach ($topUnmatched as $name => $count) {
            $list[] = "\"$name\" ($count baris)";
        }
        $warnings[] = "Nama jenis daging di file historis TIDAK ADA di tabel meat_types: " . implode(', ', $list) . ". Samakan namanya supaya data historis ikut terpakai.";
    }

    return $result;
}

/**
 * ================= BAGIAN B: DATASET BERLABEL UNTUK KLASIFIKASI KATEGORI =================
 */
function loadKategoriDataset(array &$warnings): array
{
    $records = readJsonOrJsonl(KATEGORI_DATASET_JSON, KATEGORI_DATASET_JSONL);

    $result = [];
    $skipped = 0;
    $validKategori = ['laku', 'cukup laku', 'tidak laku'];

    foreach ($records as $r) {
        if (!isset($r['jumlah_kg']) || !is_numeric($r['jumlah_kg']) || !isset($r['kategori'])) {
            $skipped++;
            continue;
        }
        $kategori = trim($r['kategori']);
        if (!in_array(strtolower($kategori), $validKategori, true)) {
            $skipped++;
            continue;
        }
        $result[] = [
            'jumlah_kg' => (float) $r['jumlah_kg'],
            'kategori' => $kategori,
        ];
    }

    if ($skipped > 0) {
        $warnings[] = "$skipped baris di kategori_dataset dilewati (field tidak lengkap/kategori tidak dikenali)";
    }
    if (count($result) === 0) {
        $warnings[] = "File kategori_dataset.json(l) tidak ditemukan atau kosong -- klasifikasi Kategori dilewati";
    }

    return $result;
}

/**
 * ================= CONFUSION MATRIX =================
 * $matrix[actual][predicted] = jumlah
 */
function buildConfusionMatrix(array $yTrue, array $yPred, array $labels): array
{
    $matrix = [];
    foreach ($labels as $a) {
        foreach ($labels as $p) {
            $matrix[$a][$p] = 0;
        }
    }
    for ($i = 0; $i < count($yTrue); $i++) {
        $a = $yTrue[$i];
        $p = $yPred[$i];
        if (isset($matrix[$a][$p])) {
            $matrix[$a][$p]++;
        }
    }
    return $matrix;
}

function perClassMetrics(array $matrix, array $labels): array
{
    $metrics = [];
    foreach ($labels as $label) {
        $tp = $matrix[$label][$label] ?? 0;
        $fn = 0; $fp = 0;
        foreach ($labels as $other) {
            if ($other === $label) continue;
            $fn += $matrix[$label][$other] ?? 0; // actual=label, predicted=other
            $fp += $matrix[$other][$label] ?? 0; // actual=other, predicted=label
        }
        $precision = ($tp + $fp) > 0 ? $tp / ($tp + $fp) : 0.0;
        $recall    = ($tp + $fn) > 0 ? $tp / ($tp + $fn) : 0.0;
        $f1        = ($precision + $recall) > 0 ? 2 * $precision * $recall / ($precision + $recall) : 0.0;

        $metrics[$label] = [
            'precision' => round($precision, 4),
            'recall'    => round($recall, 4),
            'f1_score'  => round($f1, 4),
            'jumlah_data_aktual' => $tp + $fn,
        ];
    }
    return $metrics;
}

/**
 * ================= SNAP KE NILAI VALID TERDEKAT =================
 * jumlah_kg di dataset ini diskrit (cuma ada beberapa ukuran paket tetap).
 * Fungsi ini membulatkan angka forecast (yang berupa mean+noise, bisa pecahan bebas)
 * ke nilai valid terdekat yang benar-benar pernah ada di data historis.
 */
function snapToNearest(float $value, array $validValues): float
{
    $closest = $validValues[0];
    $minDiff = abs($value - $closest);
    foreach ($validValues as $v) {
        $diff = abs($value - $v);
        if ($diff < $minDiff) {
            $minDiff = $diff;
            $closest = $v;
        }
    }
    return $closest;
}

/**
 * ================= RUN =================
 */
function runPrediction(mysqli $conn): array
{
    $warnings = [];

    // ----- Cache meat_types -----
    $meatTypeCache = [];
    $mtRes = $conn->query("SELECT id, nama_daging FROM meat_types");
    if (!$mtRes) {
        return ["status" => "error", "message" => "Query meat_types gagal: " . $conn->error];
    }
    $meatTypesAll = $mtRes->fetch_all(MYSQLI_ASSOC);
    foreach ($meatTypesAll as $row) {
        $meatTypeCache[strtolower(trim($row['nama_daging']))] = (int) $row['id'];
    }

    // ================= BAGIAN A: FORECAST KG PER JENIS DAGING =================
    $result = $conn->query("SELECT meat_type_id, jumlah_kg, harga FROM transactions");
    if (!$result) {
        return ["status" => "error", "message" => "Query transactions gagal: " . $conn->error];
    }
    $dbRows = $result->fetch_all(MYSQLI_ASSOC);
    $historicalRows = loadHistoricalData($meatTypeCache, $warnings);
    $allRows = array_merge($dbRows, $historicalRows);

    $forecastAkurasi = null;
    $forecastInfo = [
        "total_data" => count($allRows),
        "data_dari_transaksi_db" => count($dbRows),
        "data_dari_historis_jsonl" => count($historicalRows),
        "data_training_80pct" => 0,
        "data_testing_20pct" => 0,
    ];

    $insertedCount = 0;
    $meanKgByType = []; // dipakai juga untuk klasifikasi kategori pada hasil forecast

    if (count($allRows) >= 5) {
        // Buang baris yang tidak punya harga valid (butuh untuk hitung harga_per_kg)
        $beforeFilter = count($allRows);
        $allRows = array_values(array_filter($allRows, function ($r) {
            return isset($r['harga']) && $r['harga'] !== null && (float) $r['harga'] > 0 && (float) $r['jumlah_kg'] > 0;
        }));
        $droppedNoHarga = $beforeFilter - count($allRows);
        if ($droppedNoHarga > 0) {
            $warnings[] = "$droppedNoHarga baris dibuang dari forecast karena tidak punya harga valid (harga_per_kg tidak bisa dihitung)";
        }
    }

    if (count($allRows) >= 5) {
        shuffle($allRows);
        $totalData = count($allRows);
        $trainCount = (int) floor($totalData * 0.8);
        if ($trainCount >= $totalData && $totalData > 1) $trainCount = $totalData - 1;

        $trainRows = array_slice($allRows, 0, $trainCount);
        $testRows  = array_slice($allRows, $trainCount);

        $Xtrain = []; $ytrain = [];
        foreach ($trainRows as $row) {
            $hargaPerKg = (float)$row['harga'] / (float)$row['jumlah_kg'];
            $Xtrain[] = [(float)$row['jumlah_kg'], $hargaPerKg];
            $ytrain[] = (int)$row['meat_type_id'];
        }
        $Xtest = []; $ytest = [];
        foreach ($testRows as $row) {
            $hargaPerKg = (float)$row['harga'] / (float)$row['jumlah_kg'];
            $Xtest[] = [(float)$row['jumlah_kg'], $hargaPerKg];
            $ytest[] = (int)$row['meat_type_id'];
        }

        $forecastModel = new GaussianNB();
        $forecastModel->fit($Xtrain, $ytrain);
        $forecastAkurasi = count($Xtest) > 0 ? $forecastModel->score($Xtest, $ytest) : $forecastModel->score($Xtrain, $ytrain);

        $forecastInfo['data_training_80pct'] = count($trainRows);
        $forecastInfo['data_testing_20pct'] = count($testRows);
        $forecastInfo['fitur_dipakai'] = ['jumlah_kg', 'harga_per_kg'];

        // Mean kg per jenis daging (kalau jenis tsb tidak ada datanya, pakai mean keseluruhan)
        $overallMean = array_sum(array_column($allRows, 'jumlah_kg')) / count($allRows);
        $sumByType = []; $countByType = [];
        foreach ($allRows as $row) {
            $mid = (int)$row['meat_type_id'];
            $sumByType[$mid] = ($sumByType[$mid] ?? 0) + (float)$row['jumlah_kg'];
            $countByType[$mid] = ($countByType[$mid] ?? 0) + 1;
        }
        foreach ($meatTypesAll as $meat) {
            $mid = (int)$meat['id'];
            $meanKgByType[$mid] = isset($sumByType[$mid]) ? $sumByType[$mid] / $countByType[$mid] : $overallMean;
        }
    } else {
        $warnings[] = "Data forecast kg per jenis daging kurang dari 5 (setelah buang baris tanpa harga valid), forecast dilewati";
    }

    // ================= BAGIAN B: KLASIFIKASI KATEGORI (Laku/Cukup Laku/Tidak Laku) =================
    $kategoriLabels = ['Laku', 'Cukup Laku', 'Tidak Laku'];
    $kategoriDataset = loadKategoriDataset($warnings);
    $kategoriResult = null;
    $kategoriModel = null;

    if (count($kategoriDataset) >= 5) {
        shuffle($kategoriDataset);
        $total = count($kategoriDataset);
        $trainCount = (int) floor($total * 0.8);
        if ($trainCount >= $total && $total > 1) $trainCount = $total - 1;

        $trainSet = array_slice($kategoriDataset, 0, $trainCount);
        $testSet  = array_slice($kategoriDataset, $trainCount);

        $Xtrain = []; $ytrain = [];
        foreach ($trainSet as $row) { $Xtrain[] = [(float)$row['jumlah_kg']]; $ytrain[] = $row['kategori']; }
        $Xtest = []; $ytest = [];
        foreach ($testSet as $row) { $Xtest[] = [(float)$row['jumlah_kg']]; $ytest[] = $row['kategori']; }

        $kategoriModel = new GaussianNB();
        $kategoriModel->fit($Xtrain, $ytrain);

        $yPred = [];
        foreach ($Xtest as $x) { $yPred[] = $kategoriModel->predict($x); }

        $overallAcc = 0;
        if (count($ytest) > 0) {
            $correct = 0;
            for ($i = 0; $i < count($ytest); $i++) {
                if ($ytest[$i] === $yPred[$i]) $correct++;
            }
            $overallAcc = $correct / count($ytest);
        }

        $matrix = buildConfusionMatrix($ytest, $yPred, $kategoriLabels);
        $metrics = perClassMetrics($matrix, $kategoriLabels);

        $kategoriResult = [
            'akurasi_keseluruhan' => round($overallAcc, 4),
            'total_data' => $total,
            'data_training_80pct' => count($trainSet),
            'data_testing_20pct' => count($testSet),
            'confusion_matrix' => $matrix,
            'metrics_per_kategori' => $metrics,
        ];
    } else {
        $warnings[] = "Data kategori_dataset kurang dari 5, klasifikasi Kategori dilewati";
    }

    // ================= SIMPAN PREDIKSI 7 HARI KE DEPAN =================
    if ($forecastAkurasi !== null) {
        $today = new DateTime();

        // jumlah_kg di dataset ini DISKRIT (cuma ada beberapa ukuran paket tetap, bukan angka bebas).
        // Supaya forecast konsisten dengan data asli & klasifikasi Kategori tidak "nyasar" ke area
        // yang tidak pernah ada di data, bulatkan hasil forecast ke nilai valid terdekat.
        $validBeratValues = array_values(array_unique(array_map('floatval', array_column($allRows, 'jumlah_kg'))));
        sort($validBeratValues);

        // Hapus dulu prediksi untuk 7 hari ke depan (biar selalu fresh)
        $tomorrowStr = (clone $today)->modify('+1 day')->format('Y-m-d');
        $day7Str = (clone $today)->modify('+7 day')->format('Y-m-d');
        $deleteStmt = $conn->prepare("DELETE FROM predictions WHERE tanggal BETWEEN ? AND ?");
        $deleteStmt->bind_param("ss", $tomorrowStr, $day7Str);
        $deleteStmt->execute();
        $deleteStmt->close();

        $insertStmt = $conn->prepare(
            "INSERT INTO predictions (tanggal, meat_type_id, prediksi_kg, akurasi, kategori, created_at) VALUES (?, ?, ?, ?, ?, NOW())"
        );

        foreach ($meatTypesAll as $meat) {
            $mId = (int) $meat['id'];
            $meanKg = $meanKgByType[$mId] ?? 0;

            for ($i = 1; $i <= 7; $i++) {
                $tglPrediksi = (clone $today)->modify("+{$i} day")->format('Y-m-d');

                $noise = mt_rand() / mt_getrandmax() * 1.0 - 0.5;
                $rawPredKg = $meanKg + $noise;
                $predKg = !empty($validBeratValues) ? snapToNearest($rawPredKg, $validBeratValues) : round($rawPredKg, 2);

                $kategoriPred = $kategoriModel ? $kategoriModel->predict([$predKg]) : null;

                $insertStmt->bind_param("sidds", $tglPrediksi, $mId, $predKg, $forecastAkurasi, $kategoriPred);
                $insertStmt->execute();
                $insertedCount++;
            }
        }
        $insertStmt->close();
    }

    return [
        "status" => "success",
        "message" => "Berhasil menghitung & menyimpan {$insertedCount} data prediksi ke DB",
        "forecast_kg" => array_merge(["akurasi" => $forecastAkurasi !== null ? round($forecastAkurasi, 4) : null], $forecastInfo),
        "klasifikasi_kategori" => $kategoriResult,
        "warnings" => $warnings,
    ];
}

// ================= EKSEKUSI =================
if ($action === 'run') {
    try {
        echo json_encode(runPrediction($conn));
    } catch (Throwable $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Action tidak dikenali di naive_bayes.php"]);
}

$conn->close();
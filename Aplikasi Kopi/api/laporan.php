<?php
require_once "koneksi.php";

header("Content-Type: application/json");

 $jenis = $_GET['jenis'] ?? '';

/* ======================================================
   Rumus regresi linear berganda — HARUS SAMA dengan
   yang dipakai di kalkulator/prediksi front-end:
   Y = 8.5405 + 3.9936(X1) + 3.7708(X2) + 14.8803(X3) + 16.9990(X4)
====================================================== */
function hitungPrediksiY($x1, $x2, $x3, $x4) {
    return 8.5405 + (3.9936 * $x1) + (3.7708 * $x2) + (14.8803 * $x3) + (16.9990 * $x4);
}

/* ======================================================
   Konversi teks → numerik untuk input regresi.
   Database menyimpan "Cerah"/"Hujan", "Ya"/"Tidak", dll.
   Fungsi ini menangani KEDUA format (teks & angka).
====================================================== */
function toNumCuaca($v) {
    if (is_numeric($v)) return (float) $v;
    return (strtolower(trim($v)) === 'cerah') ? 1 : 0;
}

function toNumGajian($v) {
    if (is_numeric($v)) return (float) $v;
    $v = strtolower(trim($v));
    return ($v === 'ya' || $v === 'gajian' || $v === '1') ? 1 : 0;
}

function toNumPromosi($v) {
    if (is_numeric($v)) return (float) $v;
    $v = strtolower(trim($v));
    return ($v === 'ya' || $v === 'ada' || $v === '1') ? 1 : 0;
}

/* Normalisasi daring: kadang 0-1, kadang 0-100 */
function normalisasiDaring($v) {
    $v = (float) $v;
    return $v > 1 ? $v / 100 : $v;
}

/* Cek apakah kolom tertentu ada di tabel */
function kolomAda($pdo, $tabel, $kolom) {
    try {
        $s = $pdo->query("SHOW COLUMNS FROM `$tabel` LIKE '$kolom'");
        return ($s && $s->rowCount() > 0);
    } catch (Exception $e) {
        return false;
    }
}

/* ======================================================
   Bangun bagian SELECT dinamis untuk kolom opsional
   (hari_gajian & daring mungkin belum ada di tabel)
====================================================== */
function buildOptionalSelect($pdo, $tabel) {
    $gajian = kolomAda($pdo, $tabel, 'hari_gajian');
    $daring = kolomAda($pdo, $tabel, 'daring');

    $selGajian = $gajian ? ", hari_gajian" : ", 0 AS hari_gajian";
    $selDaring = $daring ? ", daring"        : ", 0.45 AS daring";

    return [$selGajian, $selDaring, $gajian, $daring];
}

/* Teks label untuk tampilan laporan */
function labelCuaca($v) {
    if (is_numeric($v)) return $v == 1 ? 'Cerah' : 'Hujan';
    return ucfirst(trim($v));
}
function labelPromo($v) {
    if (is_numeric($v)) return $v == 1 ? 'Ada' : 'Tidak';
    $v = strtolower(trim($v));
    return ($v === 'ya' || $v === 'ada') ? 'Ada' : 'Tidak';
}
function labelGajian($v) {
    if (is_numeric($v)) return $v == 1 ? 'Ya' : 'Tidak';
    $v = strtolower(trim($v));
    return ($v === 'ya' || $v === 'gajian') ? 'Ya' : 'Tidak';
}


switch ($jenis) {

    /* ==================================================
       LAPORAN DATA PENJUALAN
       Tabel  : penjualan
       Kolom  : id_penjualan, tanggal, cuaca, promosi,
                jumlah_penjualan, [hari_gajian], [daring]
    ================================================== */
    case "penjualan":

        list($selG, $selD, $hasG, $hasD) = buildOptionalSelect($pdo, 'penjualan');

        $stmt = $pdo->query("
            SELECT
                id_penjualan AS id,
                tanggal,
                cuaca,
                promosi,
                jumlah_penjualan
                $selG
                $selD
            FROM penjualan
            ORDER BY tanggal DESC
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total = 0;
        $tertinggi = null;
        $terendah  = null;
        $tabel = [];

        foreach ($rows as $r) {
            $v = (float) $r['jumlah_penjualan'];
            $total += $v;
            if ($tertinggi === null || $v > $tertinggi) $tertinggi = $v;
            if ($terendah  === null || $v < $terendah)  $terendah  = $v;

            $daringVal = normalisasiDaring($r['daring'] ?? 0.45);

            $tabel[] = [
                'tanggal'       => $r['tanggal'],
                'penjualan'     => $v,
                'cuaca'         => labelCuaca($r['cuaca']),
                'cuaca_num'     => toNumCuaca($r['cuaca']),
                'hari_gajian'   => labelGajian($r['hari_gajian'] ?? 0),
                'gajian_num'    => toNumGajian($r['hari_gajian'] ?? 0),
                'promosi'       => labelPromo($r['promosi']),
                'promo_num'     => toNumPromosi($r['promosi']),
                'daring'        => $daringVal,
                'persen_daring' => $daringVal
            ];
        }

        $n = count($rows);

        echo json_encode([
            "success" => true,
            "data" => [
                "total"     => $total,
                "rata_rata" => $n ? round($total / $n, 2) : 0,
                "tertinggi" => $tertinggi ?? 0,
                "terendah"  => $terendah  ?? 0,
                "jumlah_data" => $n,
                "tabel"     => $tabel
            ]
        ]);

    break;


    /* ==================================================
       LAPORAN HASIL PREDIKSI
       Tabel  : prediksi
       Kolom  : id, tanggal_prediksi, cuaca, promosi,
                hasil_prediksi, [hari_gajian], [daring]
    ================================================== */
    case "prediksi":

        list($selG, $selD, $hasG, $hasD) = buildOptionalSelect($pdo, 'prediksi');

        /* Cek nama kolom tanggal — bisa tanggal_prediksi atau tanggal */
        $colTanggal = kolomAda($pdo, 'prediksi', 'tanggal_prediksi')
            ? 'tanggal_prediksi'
            : 'tanggal';

        /* Cek nama kolom hasil — bisa hasil_prediksi atau prediksi */
        $colHasil = kolomAda($pdo, 'prediksi', 'hasil_prediksi')
            ? 'hasil_prediksi'
            : 'prediksi';

        $stmt = $pdo->query("
            SELECT
                id,
                $colTanggal AS tanggal,
                cuaca,
                promosi,
                $colHasil AS hasil_prediksi
                $selG
                $selD
            FROM prediksi
            ORDER BY tanggal DESC
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalPred = 0;
        $tabel = [];

        foreach ($rows as $r) {
            $predVal = (float) $r['hasil_prediksi'];
            $totalPred += $predVal;
            $daringVal = normalisasiDaring($r['daring'] ?? 0.45);

            $tabel[] = [
                'tanggal'     => $r['tanggal'],
                'prediksi'    => $predVal,
                'cuaca'       => labelCuaca($r['cuaca']),
                'hari_gajian' => labelGajian($r['hari_gajian'] ?? 0),
                'promosi'     => labelPromo($r['promosi']),
                'daring'      => round($daringVal * 100) . '%'
            ];
        }

        $n = count($rows);

        echo json_encode([
            "success" => true,
            "data" => [
                "total_prediksi" => round($totalPred, 1),
                "rata_rata"     => $n ? round($totalPred / $n, 1) : 0,
                "jumlah_data"   => $n,
                "tabel"         => $tabel
            ]
        ]);

    break;


    /* ==================================================
       LAPORAN EVALUASI MODEL (R², MAE, MAPE)
       Dihitung LANGSUNG dari data penjualan riil vs
       hasil rumus regresi — bukan dari tabel validasi_model.
    ================================================== */
    case "validasi":

        list($selG, $selD, $hasG, $hasD) = buildOptionalSelect($pdo, 'penjualan');

        $stmt = $pdo->query("
            SELECT
                tanggal, cuaca, promosi, jumlah_penjualan
                $selG
                $selD
            FROM penjualan
            ORDER BY tanggal ASC
        ");
        $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $n = count($sales);
        $r2 = 0;
        $mae = 0;
        $mape = 0;

        if ($n > 0) {
            $actuals = [];
            $preds   = [];

            foreach ($sales as $s) {
                $x1 = toNumCuaca($s['cuaca']);
                $x2 = toNumGajian($s['hari_gajian'] ?? 0);
                $x3 = toNumPromosi($s['promosi']);
                $x4 = normalisasiDaring($s['daring'] ?? 0.45);

                $actuals[] = (float) $s['jumlah_penjualan'];
                $preds[]   = max(0, hitungPrediksiY($x1, $x2, $x3, $x4));
            }

            $meanActual = array_sum($actuals) / $n;
            $ssRes = 0;
            $ssTot = 0;
            $absErrSum = 0;
            $pctErrSum = 0;
            $pctCount = 0;

            for ($i = 0; $i < $n; $i++) {
                $err = $actuals[$i] - $preds[$i];
                $ssRes     += $err * $err;
                $ssTot     += ($actuals[$i] - $meanActual) ** 2;
                $absErrSum += abs($err);
                if ($actuals[$i] != 0) {
                    $pctErrSum += abs($err / $actuals[$i]);
                    $pctCount++;
                }
            }

            $r2   = $ssTot > 0 ? (1 - ($ssRes / $ssTot)) : 0;
            $mae  = $absErrSum / $n;
            $mape = $pctCount > 0 ? ($pctErrSum / $pctCount) * 100 : 0;

            /* R² negatif → model lebih buruk dari rata-rata, dibatasi ke 0 */
            if ($r2 < 0) $r2 = 0;
        }

        /* Riwayat validasi manual dari simulator (jika tabel ada) */
        $riwayat = [];
        if (kolomAda($pdo, 'validasi_model', 'id_validasi')) {
            try {
                $stmtH = $pdo->query("
                    SELECT id_validasi, r2, mae, mape, created_at
                    FROM validasi_model
                    ORDER BY created_at DESC
                    LIMIT 20
                ");
                $riwayat = $stmtH->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $riwayat = [];
            }
        }

        echo json_encode([
            "success" => true,
            "data" => [
                "r_squared"   => round($r2, 4),
                "mae"         => round($mae, 2),
                "mape"        => round($mape, 2),
                "jumlah_data" => $n,
                "keterangan"  => "Dihitung otomatis dari " . $n . " data penjualan riil",
                "riwayat"     => $riwayat
            ]
        ]);

    break;


    /* ==================================================
       LAPORAN PENDAPATAN KEUANGAN
       Total & rata-rata pendapatan berdasarkan
       jumlah_penjualan × harga per cup (Rp 18.000)
    ================================================== */
    case "keuangan":

        $HARGA_PER_CUP = 18000;

        $stmt = $pdo->query("
            SELECT tanggal, jumlah_penjualan
            FROM penjualan
            ORDER BY tanggal ASC
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalCup = 0;
        $n = count($rows);
        $tabel = [];
        $tertinggi = null;
        $terendah  = null;

        foreach ($rows as $r) {
            $cup = (float) $r['jumlah_penjualan'];
            $pendapatan = $cup * $HARGA_PER_CUP;
            $totalCup += $cup;

            if ($tertinggi === null || $pendapatan > $tertinggi) $tertinggi = $pendapatan;
            if ($terendah  === null || $pendapatan < $terendah)  $terendah  = $pendapatan;

            $tabel[] = [
                'tanggal'    => $r['tanggal'],
                'cup'        => $cup,
                'pendapatan' => $pendapatan
            ];
        }

        $totalPendapatan = $totalCup * $HARGA_PER_CUP;

        echo json_encode([
            "success" => true,
            "data" => [
                "total_pendapatan" => $totalPendapatan,
                "rata_harian"      => $n > 0 ? round($totalPendapatan / $n) : 0,
                "total_cup"       => $totalCup,
                "pendapatan_tertinggi" => $tertinggi ?? 0,
                "pendapatan_terendah"  => $terendah  ?? 0,
                "harga_per_cup"   => $HARGA_PER_CUP,
                "jumlah_hari"     => $n,
                "tabel"           => $tabel
            ]
        ]);

    break;


    /* ==================================================
       LAPORAN EFEKTIVITAS PROMOSI
       Membandingkan rata-rata penjualan hari promosi vs
       hari tanpa promosi langsung dari data riil.
    ================================================== */
    case "promosi":

        list($selG, $selD, $hasG, $hasD) = buildOptionalSelect($pdo, 'penjualan');

        $stmt = $pdo->query("
            SELECT
                tanggal, cuaca, promosi, jumlah_penjualan
                $selG
                $selD
            FROM penjualan
            ORDER BY tanggal ASC
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $dgPromo    = [];
        $tanpaPromo = [];

        foreach ($rows as $r) {
            $v = (float) $r['jumlah_penjualan'];
            if (toNumPromosi($r['promosi']) === 1) {
                $dgPromo[] = $v;
            } else {
                $tanpaPromo[] = $v;
            }
        }

        $jmlDg    = count($dgPromo);
        $jmlTanpa = count($tanpaPromo);

        $rataDg    = $jmlDg    ? array_sum($dgPromo)    / $jmlDg    : 0;
        $rataTanpa = $jmlTanpa ? array_sum($tanpaPromo) / $jmlTanpa : 0;

        $selisih = $rataDg - $rataTanpa;
        $persen  = $rataTanpa > 0 ? ($selisih / $rataTanpa) * 100 : 0;

        if ($jmlDg === 0 || $jmlTanpa === 0) {
            $kesimpulan = "Data belum cukup lengkap untuk membandingkan hari dengan dan tanpa promosi.";
        } elseif ($selisih > 0) {
            $kesimpulan = "Promosi terbukti efektif! Rata-rata penjualan naik "
                . round($persen, 1) . "% (" . round($selisih, 1)
                . " cup) pada hari promosi dibanding hari tanpa promosi.";
        } elseif ($selisih < 0) {
            $kesimpulan = "Promosi belum menunjukkan dampak positif. Rata-rata penjualan justru turun "
                . round(abs($persen), 1) . "% pada hari promosi.";
        } else {
            $kesimpulan = "Tidak ada perbedaan rata-rata penjualan antara hari promosi dan tanpa promosi.";
        }

        echo json_encode([
            "success" => true,
            "data" => [
                "jumlah_hari_promosi"     => $jmlDg,
                "jumlah_hari_non_promosi" => $jmlTanpa,
                "rata_rata_promosi"       => round($rataDg, 2),
                "rata_rata_non_promosi"   => round($rataTanpa, 2),
                "selisih"                 => round($selisih, 2),
                "persen_kenaikan"         => round($persen, 2),
                "kesimpulan"              => $kesimpulan
            ]
        ]);

    break;


    default:

        echo json_encode([
            "success" => false,
            "message" => "Jenis laporan tidak ditemukan. Pilih: penjualan, prediksi, validasi, keuangan, atau promosi."
        ]);

    break;

}
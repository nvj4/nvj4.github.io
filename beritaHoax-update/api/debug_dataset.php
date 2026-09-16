<?php
require_once 'text_similarity.php';

echo "<h2>DEBUG PREPROCESSING & SIMILARITY</h2>";

// 1. Test preprocessing input
 $inputText = "vaksin covid berbahaya menyebabkan kematian";
 $inputWords = Preprocessor::process($inputText);

echo "<h3>1. Preprocessing Input Text</h3>";
echo "Original: <code>$inputText</code><br>";
echo "Hasil setelah preprocessing:<br>";
echo "<pre>";
print_r($inputWords);
echo "</pre>";
echo "Jumlah kata: " . count($inputWords) . "<br>";

// 2. Baca dataset dan bandingkan
echo "<hr><h3>2. Bandingkan dengan 10 Baris Pertama Dataset</h3>";

 $datasetPath = __DIR__ . '/data/dataset_hoax.jsonl';
 $lines = file($datasetPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

 $found = false;
 $bestScore = 0;
 $bestIndex = -1;

for ($i = 0; $i < min(10, count($lines)); $i++) {
    $data = json_decode($lines[$i], true);
    if (!$data || empty($data['content'])) {
        echo "<b>Baris " . ($i + 1) . ":</b> SKIPPED (content kosong)<br><br>";
        continue;
    }
    
    $content = $data['content'];
    $datasetWords = Preprocessor::process($content);
    
    // Hitung similarity manual
    if (!empty($inputWords) && !empty($datasetWords)) {
        $intersection = array_intersect($inputWords, $datasetWords);
        $union = array_unique(array_merge($inputWords, $datasetWords));
        $score = (count($intersection) / count($union)) * 100;
    } else {
        $score = 0;
        $intersection = [];
    }
    
    echo "<div style='border:1px solid #ddd;padding:10px;margin:10px 0;border-radius:5px;'>";
    echo "<b>Baris " . ($i + 1) . "</b> | Score: <span style='color:" . ($score > 0 ? "green" : "red") . ";font-weight:bold;'>" . round($score, 1) . "%</span><br>";
    echo "Title: " . (isset($data['title']) ? $data['title'] : '-') . "<br>";
    echo "Content (50 char): <code>" . substr($content, 0, 80) . "...</code><br>";
    echo "Kata setelah preprocess (" . count($datasetWords) . " kata): <code>" . implode(', ', array_slice($datasetWords, 0, 15)) . "...</code><br>";
    echo "Kata yang cocok: <span style='color:blue;font-weight:bold;'>" . (count($intersection) > 0 ? implode(', ', $intersection) : 'TIDAK ADA') . "</span><br>";
    echo "</div>";
    
    if ($score > $bestScore) {
        $bestScore = $score;
        $bestIndex = $i;
    }
}

echo "<hr><h3>3. Cari di SEMUA Dataset (3648 baris)</h3>";
echo "Mencari kata kunci <code>vaksin</code> di content...<br><br>";

 $vaksinFound = 0;
 $covidFound = 0;
 $berbahayaFound = 0;
 $menyebabkanFound = 0;
 $kematianFound = 0;

 $bestOverall = 0;
 $bestOverallData = null;

foreach ($lines as $i => $line) {
    $data = json_decode($line, true);
    if (!$data || empty($data['content'])) continue;
    
    $content = strtolower($data['content']);
    
    // Hitung kemunculan kata kunci mentah (sebelum preprocess)
    if (strpos($content, 'vaksin') !== false) $vaksinFound++;
    if (strpos($content, 'covid') !== false) $covidFound++;
    if (strpos($content, 'berbahaya') !== false) $berbahayaFound++;
    if (strpos($content, 'menyebabkan') !== false) $menyebabkanFound++;
    if (strpos($content, 'kematian') !== false) $kematianFound++;
    
    // Hitung similarity
    $datasetWords = Preprocessor::process($content);
    if (!empty($inputWords) && !empty($datasetWords)) {
        $intersection = array_intersect($inputWords, $datasetWords);
        $union = array_unique(array_merge($inputWords, $datasetWords));
        $score = (count($intersection) / count($union)) * 100;
        
        if ($score > $bestOverall) {
            $bestOverall = $score;
            $bestOverallData = $data;
        }
    }
}

echo "<table border='1' cellpadding='8' style='border-collapse:collapse;'>";
echo "<tr><th>Kata Kunci</th><th>Jumlah di Dataset</th></tr>";
echo "<tr><td>vaksin</td><td>$vaksinFound</td></tr>";
echo "<tr><td>covid</td><td>$covidFound</td></tr>";
echo "<tr><td>berbahaya</td><td>$berbahayaFound</td></tr>";
echo "<tr><td>menyebabkan</td><td>$menyebabkanFound</td></tr>";
echo "<tr><td>kematian</td><td>$kematianFound</td></tr>";
echo "</table>";

echo "<br><h3>4. Score Tertinggi dari Semua Dataset</h3>";
echo "Best score: <b>" . round($bestOverall, 2) . "%</b><br>";
if ($bestOverallData) {
    echo "Title: " . $bestOverallData['title'] . "<br>";
    echo "Content: <code>" . substr($bestOverallData['content'], 0, 200) . "...</code><br>";
}

echo "<hr><h3>5. Analisis Masalah</h3>";
if ($vaksinFound == 0 && $covidFound == 0) {
    echo "<p style='color:red;font-size:18px;'>❌ MASALAH: Dataset TIDAK MEMILIKI kata 'vaksin' atau 'covid'!</p>";
    echo "<p>Dataset anda mungkin berisi topik yang berbeda, atau format field salah.</p>";
    echo "<p>Silakan cek beberapa baris dataset secara manual.</p>";
} elseif ($bestOverall == 0) {
    echo "<p style='color:red;font-size:18px;'>❌ MASALAH: Preprocessing MENGHAPUS semua kata penting!</p>";
    echo "<p>Kata 'vaksin', 'covid', 'berbahaya' dll dihapus oleh stopwords atau terlalu pendek.</p>";
    echo "<p>Solusi: Perbaiki daftar stopwords di Preprocessor.</p>";
} elseif ($bestOverall < 60) {
    echo "<p style='color:orange;font-size:18px;'>⚠️ MASALAH: Score terlalu rendah (" . round($bestOverall, 2) . "%)</p>";
    echo "<p>Threshold saat ini 60%. Score tertinggi hanya " . round($bestOverall, 2) . "%.</p>";
    echo "<p>Solusi: Turunkan threshold atau perbaiki algoritma.</p>";
} else {
    echo "<p style='color:green;font-size:18px;'>✅ Seharusnya bisa mendeteksi (score " . round($bestOverall, 2) . "%)</p>";
}

// 6. Tampilkan 3 baris dataset asli
echo "<hr><h3>6. Contoh 3 Baris Dataset ASLI (belum dipreprocess)</h3>";
for ($i = 0; $i < min(3, count($lines)); $i++) {
    $data = json_decode($lines[$i], true);
    if (!$data) continue;
    
    echo "<div style='background:#f9f9f9;padding:15px;margin:10px 0;border-radius:5px;border-left:4px solid #333;'>";
    echo "<b>Baris " . ($i + 1) . "</b><br>";
    echo "<b>Title:</b> " . (isset($data['title']) ? $data['title'] : '-') . "<br>";
    echo "<b>Content:</b> " . (isset($data['content']) ? $data['content'] : '-') . "<br>";
    echo "<b>Source:</b> " . (isset($data['source']) ? $data['source'] : '-') . "<br>";
    echo "<b>Published:</b> " . (isset($data['published_at']) ? $data['published_at'] : '-') . "<br>";
    echo "</div>";
}
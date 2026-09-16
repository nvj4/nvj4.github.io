<?php
require 'text_similarity.php';

 $matcher = new HoaxMatcher(__DIR__ . '/data/dataset_hoax.jsonl');

// Test dengan teks yang jelas-jelas ada di dataset
 $testText = "Vaksin covid berbahaya dan menyebabkan kematian massal";
 $result = $matcher->findMatch($testText);

echo "<h2>Hasil Test Deteksi</h2>";
echo "<pre>";
print_r($result);
echo "</pre>";

echo "<hr>";
echo "<h2>Info</h2>";
echo "Status: " . $result['status'] . "<br>";
echo "Score: " . $result['score'] . "%<br>";
echo "Tanggal: " . $result['published_at'] . "<br>";

if ($result['status'] === 'HOAX_DETECTED') {
    echo "<h3 style='color:red'>✅ BERHASIL MENDETEKSI HOAX!</h3>";
} else {
    echo "<h3 style='color:orange'>⚠️ TIDAK MENDETEKSI HOAX</h3>";
    echo "<p>Cek apakah teks test cocok dengan isi dataset</p>";
}
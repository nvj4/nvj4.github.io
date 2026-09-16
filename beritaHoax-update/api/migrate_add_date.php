<?php
// migrate_add_date.php - Jalankan sekali saja

 $inputFile = __DIR__ . '/data/dataset_hoax.jsonl';
 $outputFile = __DIR__ . '/data/dataset_hoax_new.jsonl';

 $handle = fopen($inputFile, 'r');
 $output = fopen($outputFile, 'w');

while (($line = fgets($handle)) !== false) {
    $line = trim($line);
    if (empty($line)) continue;
    
    $data = json_decode($line, true);
    if (!$data) continue;
    
    // Jika belum ada published_at, coba ekstrak dari URL
    if (empty($data['published_at']) && !empty($data['source'])) {
        // Format URL TurnBackHoax: https://turnbackhoax.id/2024/01/15/salah-hoaks-xxx/
        if (preg_match('#turnbackhoax\.id/(\d{4})/(\d{2})/(\d{2})/#', $data['source'], $matches)) {
            $data['published_at'] = $matches[1] . '-' . $matches[2] . '-' . $matches[3];
        }
    }
    
    // Jika masih kosong, beri nilai default
    if (empty($data['published_at'])) {
        $data['published_at'] = '';
    }
    
    fwrite($output, json_encode($data, JSON_UNESCAPED_UNICODE) . "\n");
}

fclose($handle);
fclose($output);

echo "Selesai! File baru: $outputFile\n";
echo "Silakan ganti file lama dengan file baru.\n";
<?php
/**
 * FaktaKu - api/news_feed.php
 * Mengambil "Berita Terkini" (umum/trending Indonesia) dari GNews API,
 * lalu di-cache sebentar supaya kuota API tidak cepat habis.
 *
 * Response:
 *  { "success": true, "data": [ {title, description, content, url, image, publishedAt, source}, ... ], "fetched_at": "..." }
 *  { "success": false, "message": "..." }
 */

header('Content-Type: application/json; charset=utf-8');
error_reporting(0); // supaya warning PHP tidak merusak output JSON

// ════════════════════════════════════════
// 1) AMBIL API KEY GNEWS
//    Kode di bawah otomatis coba pakai config yang SAMA dengan yang
//    dipakai detect_search.php. Kalau tidak ketemu otomatis, isi manual
//    di baris "GANTI DI SINI" paling bawah blok ini.
// ════════════════════════════════════════
$apiKey = null;

$possibleConfigFiles = [
    __DIR__ . '/config.php',
    __DIR__ . '/../config.php',
    __DIR__ . '/../includes/config.php',
    __DIR__ . '/../config/config.php',
];
foreach ($possibleConfigFiles as $cfgFile) {
    if (file_exists($cfgFile)) {
        include_once $cfgFile;
        break;
    }
}

// Coba beberapa nama constant/variable yang umum dipakai
if (defined('GNEWS_API_KEY'))       $apiKey = GNEWS_API_KEY;
elseif (defined('GNEWS_KEY'))       $apiKey = GNEWS_KEY;
elseif (defined('NEWS_API_KEY'))    $apiKey = NEWS_API_KEY;
elseif (isset($GNEWS_API_KEY))      $apiKey = $GNEWS_API_KEY;

// ⚠️ GANTI DI SINI kalau tidak ketemu otomatis dari config di atas.
// (Hanya baris ini yang perlu diisi — jangan ubah baris pengecekan di bawahnya)
if (!$apiKey) {
    $apiKey = 'aac289912470519ad3a315f30419453c';
}

$apiKey = trim((string) $apiKey);

// Validasi berdasarkan FORMAT (bukan perbandingan teks placeholder), supaya
// tidak salah tolak walau teks instruksi di atas ikut ke-replace.
// API key GNews normalnya string alfanumerik ~32 karakter.
$looksLikeValidKey = (bool) preg_match('/^[a-zA-Z0-9]{16,}$/', $apiKey);

if (!$looksLikeValidKey) {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'message' => 'GNews API key belum dikonfigurasi. Buka file api/news_feed.php dan isi variabel $apiKey (atau samakan dengan config yang dipakai detect_search.php).'
    ]);
    exit;
}

// ════════════════════════════════════════
// 2) CACHE — supaya tidak boros kuota GNews (free tier terbatas)
// ════════════════════════════════════════
$cacheFile   = __DIR__ . '/cache_news_feed.json';
$cacheTTL    = 900; // 15 menit
$forceRefresh = isset($_GET['refresh']) && $_GET['refresh'] === '1';

if (!$forceRefresh && file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTTL) {
    echo file_get_contents($cacheFile);
    exit;
}

// ════════════════════════════════════════
// 3) FETCH DARI GNEWS (top-headlines, umum/trending, Indonesia)
// ════════════════════════════════════════
$endpoint = 'https://gnews.io/api/v4/top-headlines'
    . '?category=general'
    . '&lang=id'
    . '&country=id'
    . '&max=9'
    . '&apikey=' . urlencode($apiKey);

$articles = [];
$fetchError = null;

if (function_exists('curl_init')) {
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 12);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        $fetchError = 'Koneksi ke GNews gagal: ' . $curlErr;
    } elseif ($httpCode !== 200) {
        $fetchError = 'GNews merespons HTTP ' . $httpCode;
    } else {
        $json = json_decode($response, true);
        if (!isset($json['articles']) || !is_array($json['articles'])) {
            $fetchError = 'Format respons GNews tidak sesuai.';
        } else {
            foreach ($json['articles'] as $a) {
                $articles[] = [
                    'title'       => $a['title'] ?? '-',
                    'description' => $a['description'] ?? '',
                    'content'     => $a['content'] ?? ($a['description'] ?? ''),
                    'url'         => $a['url'] ?? '#',
                    'image'       => $a['image'] ?? '',
                    'publishedAt' => $a['publishedAt'] ?? '',
                    'source'      => $a['source']['name'] ?? 'Sumber tidak diketahui'
                ];
            }
        }
    }
} else {
    $fetchError = 'Ekstensi cURL tidak aktif di server ini.';
}

// ════════════════════════════════════════
// 4) RESPONSE — kalau gagal fetch tapi masih ada cache lama, pakai cache lama
//    daripada tampilan kosong.
// ════════════════════════════════════════
if ($fetchError || empty($articles)) {
    if (file_exists($cacheFile)) {
        echo file_get_contents($cacheFile);
        exit;
    }
    echo json_encode([
        'success' => false,
        'message' => $fetchError ?: 'Tidak ada artikel yang ditemukan.'
    ]);
    exit;
}

$result = [
    'success'    => true,
    'data'       => $articles,
    'fetched_at' => date('c')
];

file_put_contents($cacheFile, json_encode($result));
echo json_encode($result);
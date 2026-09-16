<?php
// api/detect_search.php
session_start();
require_once __DIR__ . '/koneksi.php';
require 'text_similarity.php';

 $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'http://localhost';
header("Access-Control-Allow-Origin: " . $origin);
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

 $req = json_decode(file_get_contents("php://input"), true);
 $text = isset($req['content']) ? $req['content'] : '';
 $source = isset($req['source']) ? $req['source'] : '';
 $finalText = $text ?: $source;

if (empty(trim($finalText))) {
    echo json_encode(["success" => false, "message" => "Konten berita kosong"]);
    exit;
}

// ============================================================
// DAFTAR SUMBER TERPERCAYA
// ============================================================
 $TRUSTED_DOMAINS = [
    'detik.com', 'kompas.com', 'liputan6.com', 'cnnindonesia.com',
    'inews.id', 'tempo.co', 'tribunnews.com', 'republika.co.id',
    'antaranews.com', 'sindonews.com', 'okezone.com', 'viva.co.id',
    'suara.com', 'medcom.id', 'pikiran-rakyat.com', 'jawapos.com',
];

// ============================================================
// GNEWS API
// ============================================================
function fetchGNews($query, $max = 5) {
    $apiKey = 'aac289912470519ad3a315f30419453c';

    $params = [
        'q'       => $query,
        'lang'    => 'id',
        'country' => 'id',
        'max'     => $max,
        'apikey'  => $apiKey
    ];

    $url = "https://gnews.io/api/v4/search?" . http_build_query($params);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'FaktualNews-Bot/1.0');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError || $httpCode !== 200) {
        return [];
    }

    $data = json_decode($response, true);

    if (!isset($data['articles']) || !is_array($data['articles'])) {
        return [];
    }

    $results = [];
    foreach ($data['articles'] as $article) {
        if (empty($article['url'])) continue;

        $link = $article['url'];
        $title = !empty($article['title']) ? $article['title'] : 'Tanpa Judul';
        $domain = parse_url($link, PHP_URL_HOST);
        if (empty($domain)) {
            $domain = 'Sumber Tidak Diketahui';
        }
        $domain = str_replace('www.', '', $domain);
        $tanggal = !empty($article['publishedAt']) ? $article['publishedAt'] : '';

        $results[] = [
            "title"       => $title,
            "source"      => $domain,
            "link"        => $link,
            "domain"      => $domain,
            "publishedAt" => $tanggal
        ];
    }

    return $results;
}

function cariDiGNews($inputText, $preprocessedWords) {
    global $TRUSTED_DOMAINS;

    $existingLinks = [];
    $allResults = [];

    // ROUND 1: 3 kata dari Preprocessor
    $q1 = implode(' ', array_slice($preprocessedWords, 0, 3));
    if (strlen($q1) >= 5) {
        $r1 = fetchGNews($q1, 8);
        foreach ($r1 as $item) {
            if (!in_array($item['link'], $existingLinks)) {
                $allResults[] = $item;
                $existingLinks[] = $item['link'];
            }
        }
    }

    // ROUND 2: 2 kata dari Preprocessor
    if (count($allResults) < 3) {
        $q2 = implode(' ', array_slice($preprocessedWords, 0, 2));
        if (strlen($q2) >= 5 && $q2 !== $q1) {
            $r2 = fetchGNews($q2, 8);
            foreach ($r2 as $item) {
                if (!in_array($item['link'], $existingLinks)) {
                    $allResults[] = $item;
                    $existingLinks[] = $item['link'];
                }
            }
        }
    }

    // ROUND 3: Kalimat pertama teks asli
    if (count($allResults) < 3) {
        $sentences = preg_split('/[.!?]+/', $inputText);
        $firstSent = trim($sentences[0]);
        $words = explode(' ', $firstSent);
        $shortText = implode(' ', array_slice($words, 0, 6));
        if (strlen($shortText) >= 10 && $shortText !== $q1 && $shortText !== $q2) {
            $r3 = fetchGNews($shortText, 8);
            foreach ($r3 as $item) {
                if (!in_array($item['link'], $existingLinks)) {
                    $allResults[] = $item;
                    $existingLinks[] = $item['link'];
                }
            }
        }
    }

    // ROUND 4: Hanya 2 kata paling penting
    if (count($allResults) < 1 && count($preprocessedWords) >= 2) {
        $q4 = $preprocessedWords[0] . ' ' . $preprocessedWords[1];
        $r4 = fetchGNews($q4, 5);
        foreach ($r4 as $item) {
            if (!in_array($item['link'], $existingLinks)) {
                $allResults[] = $item;
                $existingLinks[] = $item['link'];
            }
        }
    }

    // FILTER: Pisah trusted vs non-trusted
    $trusted = [];
    $others = [];
    foreach ($allResults as $r) {
        $isTrusted = false;
        foreach ($TRUSTED_DOMAINS as $td) {
            if (strpos($r['domain'], $td) !== false) {
                $isTrusted = true;
                break;
            }
        }
        if ($isTrusted) {
            $trusted[] = $r;
        } else {
            $others[] = $r;
        }
    }

    // URUTKAN: Trusted dulu, max 5 total
    $final = [];
    foreach ($trusted as $r) {
        $final[] = [
            "title"       => $r['title'],
            "source"      => $r['source'],
            "link"        => $r['link'],
            "publishedAt" => isset($r['publishedAt']) ? $r['publishedAt'] : ''
        ];
    }
    $remaining = 5 - count($final);
    if ($remaining > 0) {
        foreach (array_slice($others, 0, $remaining) as $r) {
            $final[] = [
                "title"       => $r['title'],
                "source"      => $r['source'],
                "link"        => $r['link'],
                "publishedAt" => isset($r['publishedAt']) ? $r['publishedAt'] : ''
            ];
        }
    }

    return $final;
}

// ============================================================
// FUNGSI FORMAT TANGGAL INDONESIA
// ============================================================
function formatTanggalIndo($tanggal) {
    if (empty($tanggal)) {
        return 'Tanggal tidak diketahui';
    }
    
    // Coba parse berbagai format
    $date = false;
    
    // Format ISO: 2024-01-15 atau 2024-01-15T10:30:00Z
    if (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $tanggal, $m)) {
        $date = mktime(0, 0, 0, (int)$m[2], (int)$m[3], (int)$m[1]);
    }
    
    if (!$date) {
        return $tanggal; // Return asli jika tidak bisa parse
    }
    
    $bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    
    $d = (int)date('d', $date);
    $m = (int)date('m', $date);
    $y = date('Y', $date);
    
    return $d . ' ' . $bulan[$m] . ' ' . $y;
}

// ============================================================
// LOGIKA DETEKSI
// ============================================================

try {
    // 1. CEK LOKAL
    $matcher = new HoaxMatcher(__DIR__ . '/dataa/hoax.jsonl');
    $localResult = $matcher->findMatch($finalText);

        if ($localResult['status'] === 'HOAX_DETECTED') {
        $finalLabel = 'hoax';
        
        // Format tanggal
        $tanggalFormatted = formatTanggalIndo($localResult['published_at']);
        $message = "Berita terindikasi HOAX. Teks ini memiliki kemiripan " . $localResult['score'] . "% dengan database hoaks.";
        
        // LOGIKA LINK SUMBER
        $sourceLink = $localResult['source'];
        $sourceLabel = '';
        if (!empty($sourceLink)) {
            $sourceLabel = parse_url($sourceLink, PHP_URL_HOST);
        } else {
            $sourceLabel = 'Cari Sumber di Google';
            $cleanRef = preg_replace('/^\[(HOAKS|HOAX|SALAH|FALSE|FITNAH|DISINFORMASI)\]\s*/i', '', $localResult['reference']);
            $searchQuery = urlencode('"' . trim($cleanRef) . '" klarifikasi hoaks fakta');
            $sourceLink = "https://www.google.com/search?q=" . $searchQuery;
        }

        // 1. Reference Utama (Berita Hoaks-nya)
        $references = [[
            "title"       => $localResult['reference'],
            "source"      => $sourceLabel,
            "link"        => $sourceLink,
            "publishedAt" => $tanggalFormatted,
            "category"    => isset($localResult['category']) ? $localResult['category'] : '',
            "excerpt"     => !empty($localResult['body_text']) ? $localResult['body_text'] : $message
        ]];
        
        // 2. Cari Berita Terkait dari GNews (Untuk ditampilkan di bawah)
        $preprocessedWords = Preprocessor::process($finalText);
        $gnewsResults = cariDiGNews($finalText, $preprocessedWords);
        if (count($gnewsResults) > 0) {
            foreach ($gnewsResults as $gn) {
                $references[] = $gn; // Menambahkan berita terkait ke array
            }
        };
        
        $gnewsResults = [];
        
    } else {
        $finalLabel = 'fakta';
        $message = "Berita AMAN (FAKTA). Tidak ditemukan kecocokan signifikan dengan database hoaks.";
        $references = [];

        $preprocessedWords = Preprocessor::process($finalText);
        $gnewsResults = cariDiGNews($finalText, $preprocessedWords);

        if (count($gnewsResults) > 0) {
            $references = $gnewsResults;
            $message .= " Ditemukan berita serupa dari sumber terpercaya.";
        }
    }

    // 2. HITUNG TINGKAT KEPERCAYAAN
    $confidence = 0;
    if ($finalLabel === 'hoax') {
        $confidence = $localResult['score'];
    } else {
        $confidence = 100 - $localResult['score'];
        if (isset($gnewsResults) && count($gnewsResults) > 0) {
            $confidence = min(99, $confidence + 10);
        }
    }

    // 3. SIMPAN KE DATABASE
    $saveEmail = null;
    $saveUserId = null;
    $saveDebug = '';

    if (isset($_SESSION['user'])) {
        $saveEmail = $_SESSION['user']['email'];
        $saveUserId = $_SESSION['user']['id'];
    } else {
        $saveEmail = isset($req['user_email']) ? $req['user_email'] : null;
        $saveUserId = isset($req['user_id']) ? (int)$req['user_id'] : null;
    }

    if ($saveEmail || $saveUserId) {
        try {
            $stmtSave = $pdo->prepare("INSERT INTO detection_history (content, detection_result, user_email, user_id) VALUES (?, ?, ?, ?)");
            $stmtSave->execute([$finalText, $finalLabel, $saveEmail, $saveUserId]);
            $lastId = $pdo->lastInsertId();

            $stmtCheck = $pdo->prepare("SELECT id, detection_result, user_email FROM detection_history WHERE id = ?");
            $stmtCheck->execute([$lastId]);
            $checkRow = $stmtCheck->fetch();

            if ($checkRow) {
                $saveDebug = 'Tersimpan ID#' . $lastId . ' result=' . $checkRow['detection_result'] . ' email=' . $checkRow['user_email'];
            } else {
                $saveDebug = 'ANEH: Tidak error tapi data tidak ditemukan (lastID=' . $lastId . ')';
            }
        } catch (Exception $e) {
            $saveDebug = 'DB Error: ' . $e->getMessage();
        }
    } else {
        $saveDebug = 'Skip: tidak ada user';
    }

    echo json_encode([
        "success" => true,
        "data" => [
            "result"     => $finalLabel,
            "message"    => $message,
            "confidence" => round($confidence, 1),
            "references" => $references,
            "debug_save" => $saveDebug,
            "debug_match" => [
                "status"       => $localResult['status'],
                "score"        => $localResult['score'],
                "published_at" => $localResult['published_at']
            ]
        ]
    ]);

} catch (Throwable $e) {
    echo json_encode([
        "success" => false, 
        "message" => $e->getMessage(),
        "file"    => $e->getFile(),
        "line"    => $e->getLine()
    ]);
}
<?php
// text_similarity.php

class Preprocessor {
    public static function process($text) {
        $text = strtolower($text);
        $text = preg_replace('/https?:\/\/[^\s]+/i', '', $text);
        $text = preg_replace('/[^a-z0-9\s]/i', ' ', $text);
        $text = preg_replace('/\d+/', '', $text);
        
        $stopwords = [
            'yang', 'dan', 'di', 'ke', 'dari', 'dengan', 'untuk', 'pada', 'adalah',
            'ini', 'itu', 'atau', 'dalam', 'akan', 'tidak', 'juga', 'sudah', 'belum',
            'bisa', 'ada', 'karena', 'oleh', 'seorang', 'sebuah', 'tersebut', 'mereka',
            'kami', 'kita', 'aku', 'kamu', 'dia', 'ia', 'beliau', 'saya', 'diri',
            'nya', 'si', 'jika', 'saat', 'setelah', 'sebelum', 'ketika',
            'sedang', 'sedangkan', 'tetapi', 'namun', 'meski', 'walaupun', 'meskipun',
            'agar', 'supaya', 'hingga', 'sampai', 'sejak', 'selama', 'bagi', 'tentang',
            'seperti', 'bagai', 'sebagai', 'maupun', 'baik', 'lebih', 'paling', 'sangat',
            'amat', 'cukup', 'kurang', 'hampir', 'baru', 'lagi', 'pun', 'selain',
            'terhadap', 'melalui', 'mengenai', 'berdasarkan', 'menurut', 'daripada',
            'yaitu', 'ialah', 'empat', 'lima', 'enam', 'tujuh', 'delapan',
            'sembilan', 'sepuluh', 'pertama', 'kedua', 'ketiga', 'berita', 'informasi',
            'hoaks', 'hoax', 'salah', 'kabar', 'viral'
        ];
        
        $words = explode(' ', $text);
        $words = array_filter($words, function($word) use ($stopwords) {
            $word = trim($word);
            return strlen($word) > 2 && !in_array($word, $stopwords);
        });
        
        return array_values($words);
    }
}

class HoaxMatcher {
    private $dataset = [];
    
    public function __construct($filePath) {
        if (!file_exists($filePath)) {
            return;
        }
        
        $handle = fopen($filePath, 'r');
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $line = trim($line);
                if (empty($line)) continue;
                
                $data = json_decode($line, true);
                if ($data) {
                    $rawContent = isset($data['content']) ? trim($data['content']) : '';
                    $rawTitle = isset($data['title']) ? trim($data['title']) : '';
                    
                    // Jika content kosong atau cuma "-", gunakan title
                    $finalContent = $rawContent;
                    if (empty($rawContent) || $rawContent === '-') {
                        $finalContent = $rawTitle;
                    }
                    
                    // Hilangkan tag [HOAKS], [HOAX], [SALAH] dari awal
                    $finalContent = preg_replace('/^\[(HOAKS|HOAX|SALAH|FALSE|FITNAH|DISINFORMASI)\]\s*/i', '', $finalContent);
                    $finalContent = trim($finalContent);
                    
                    if (empty($finalContent)) continue;
                    
                    // Perbaiki published_at format
                    $pubAt = isset($data['published_at']) ? $data['published_at'] : '';
                    if (preg_match('/(\d{4}-\d{2}-\d{2})/', $pubAt, $m)) {
                        $pubAt = $m[1];
                    }
                    
                    // ✅ FITUR KEAMANAN: HANYA TERIMA LINK SUMBER FAKTA CHECKING YANG VALID
                                       // ✅ AMBIL LINK SUMBER DARI FIELD 'url' ATAU 'source'
                    $rawSource = isset($data['source']) ? trim($data['source']) : '';
                    $rawUrl = isset($data['url']) ? trim($data['url']) : ''; // Membaca field url dari dataset baru
                    
                    $finalSource = '';
                    
                    // 1. Prioritas utama: Gunakan 'source' jika isinya link yang valid
                    if (!empty($rawSource) && $rawSource !== '-' && strpos($rawSource, 'http') === 0) {
                        $finalSource = $rawSource;
                    } 
                    // 2. Jika source kosong/invalid, gunakan 'url' (seperti komdigi.go.id)
                    elseif (!empty($rawUrl) && strpos($rawUrl, 'http') === 0) {
                        $finalSource = $rawUrl;
                    }
                    
                    $this->dataset[] = [
                        'title'        => $rawTitle,
                        'content'      => $finalContent,
                        'source'       => $finalSource, // Sekarang akan berisi link komdigi/go.id
                        'published_at' => $pubAt,
                        'category'     => isset($data['category']) ? $data['category'] : '',
                        
                    ];
                }
            }
            fclose($handle);
        }
    }
    
    public function findMatch($inputText) {
        if (empty($this->dataset)) {
            return [
                'status'       => 'NOT_FOUND',
                'score'        => 0,
                'reference'    => '',
                'source'       => '',
                'published_at' => '',
                'category'     => '',
               
            ];
        }
        
        $inputWords = Preprocessor::process($inputText);
        $bestMatch = null;
        $bestScore = 0;
        $bestMatchWords = [];
        
        foreach ($this->dataset as $item) {
            $datasetWords = Preprocessor::process($item['content']);
            
            // ✅ GUNAKAN ALGORITMA YANG KEMARIN SUKSES 100%
            $score = $this->calculateDatasetCoverage($inputWords, $datasetWords);
            
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $item;
                $bestMatchWords = $datasetWords;
            }
        }
        
        // Hitung jumlah kata yang benar-benar cocok
        $matchedCount = count(array_intersect($inputWords, $bestMatchWords));
        
        // ✅ SYARAT DETEKSI
        $threshold = 70;
        
        if ($bestScore >= $threshold && $bestMatch && $matchedCount >= 2) {
            return [
                'status'       => 'HOAX_DETECTED',
                'score'        => round($bestScore, 1),
                'reference'    => $bestMatch['title'],
                'source'       => $bestMatch['source'],
                'published_at' => $bestMatch['published_at'],
                'category'     => $bestMatch['category']
            ];
        }
        
        return [
            'status'       => 'NOT_FOUND',
            'score'        => round($bestScore, 1),
            'reference'    => '',
            'source'       => '',
            'published_at' => '',
            'category'     => ''
        ];
    }
    
    // ✅ FUNGSI YANG KEMARIN SUKSES MEMBUAT SKOR 100%
    private function calculateDatasetCoverage($inputWords, $datasetWords) {
        if (empty($inputWords) || empty($datasetWords)) {
            return 0;
        }
        
        $matchCount = 0;
        // Loop kata di JUDUL DATASET
        foreach ($datasetWords as $word) {
            // Apakah kata judul ADA di dalam teks user?
            if (in_array($word, $inputWords)) {
                $matchCount++;
            }
        }
        
        // Skor = (Kata judul yang cocok / Total kata judul) * 100
        return ($matchCount / count($datasetWords)) * 100;
    }
}
<?php
require_once __DIR__ . '/koneksi.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

 $action = optionalParam('action', '');

switch ($action) {

    case 'comparison':
        $period = optionalParam('period', 'all');
        $whereDate = '';
        $params = [];

        if ($period === 'this_month') {
            $whereDate = " AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')";
        } elseif ($period === 'last_month') {
            $whereDate = " AND created_at BETWEEN DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-01') AND LAST_DAY(DATE_SUB(NOW(), INTERVAL 1 MONTH)) 23:59:59";
        }

        try {
            $db = getDB();
            $sql = "SELECT detection_result, COUNT(*) AS jumlah
                    FROM detection_history
                    WHERE detection_result IN ('hoax', 'fakta', 'safe')
                    {$whereDate}
                    GROUP BY detection_result";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();

            $fakta = 0; $hoax = 0;
            foreach ($rows as $row) {
                if ($row['detection_result'] === 'fakta' || $row['detection_result'] === 'safe') $fakta += (int)$row['jumlah'];
                elseif ($row['detection_result'] === 'hoax') $hoax += (int)$row['jumlah'];
            }
            $total = $fakta + $hoax;

            jsonResponse(['success' => true, 'data' => ['total' => $total, 'fakta' => $fakta, 'hoax' => $hoax, 'daily' => []]]);
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'message' => 'Query gagal: ' . $e->getMessage()], 500);
        }
        break;

    case 'monthly_stats':
        try {
            $db = getDB();
            $stmt = $db->query("SELECT COUNT(*) AS total FROM detection_history");
            $total = (int)$stmt->fetch()['total'];

            $stmt = $db->query("SELECT COUNT(*) AS total FROM detection_history WHERE detection_result = 'hoax'");
            $hoax = (int)$stmt->fetch()['total'];

            $stmt = $db->query("SELECT COUNT(*) AS total FROM detection_history WHERE detection_result IN ('fakta','safe')");
            $fakta = (int)$stmt->fetch()['total'];

            $belum = $total - $hoax - $fakta;
            if ($belum < 0) $belum = 0;

            $akurasi = 0;
            try {
                $stmt = $db->query("SELECT accuracy_rate FROM system_statistic LIMIT 1");
                $row = $stmt->fetch();
                if ($row) $akurasi = (float)$row['accuracy_rate'];
            } catch (Exception $e) {}

            $kategori = ['Kesehatan' => 0, 'Politik' => 0, 'Keuangan' => 0, 'Bencana Alam' => 0, 'Lainnya' => 0];
            $stmt = $db->query("SELECT content FROM detection_history WHERE detection_result = 'hoax'");
            $hoaxContents = $stmt->fetchAll();

            $kKes = ['vaksin','obat','penyakit','kanker','diabetes','herbal','kesehatan','virus','sembuh'];
            $kPol = ['presiden','pemerintah','menteri','partai','pemilu','politik','kebijakan'];
            $kFin = ['investasi','bunga','pinjaman','bank','uang','bantuan','subsidi','harga','bbm'];
            $kBen = ['gempa','banjir','gunung meletus','tsunami','longsor','bencana','bmkg'];

            foreach ($hoaxContents as $row) {
                $text = strtolower($row['content'] ?? '');
                $words = explode(' ', $text);
                $matched = false;
                if (count(array_intersect($words, $kKes)) >= 1) { $kategori['Kesehatan']++; $matched = true; }
                if (count(array_intersect($words, $kPol)) >= 1) { $kategori['Politik']++; $matched = true; }
                if (count(array_intersect($words, $kFin)) >= 1) { $kategori['Keuangan']++; $matched = true; }
                if (count(array_intersect($words, $kBen)) >= 1) { $kategori['Bencana Alam']++; $matched = true; }
                if (!$matched) $kategori['Lainnya']++;
            }

            jsonResponse([
                'success' => true,
                'data' => [
                    'total' => $total, 'hoax' => $hoax, 'fakta' => $fakta,
                    'belum_verifikasi' => $belum, 'akurasi' => $akurasi, 'kategori_hoax' => $kategori
                ]
            ]);
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'message' => 'Query gagal: ' . $e->getMessage()], 500);
        }
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Action tidak dikenali. Gunakan: comparison, monthly_stats'], 400);
        break;
}
?>
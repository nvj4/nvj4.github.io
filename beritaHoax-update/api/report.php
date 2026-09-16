<?php
require_once __DIR__ . '/koneksi.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

 $action = optionalParam('action', '');

// ============================================================
// GET
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    switch ($action) {

        case 'activity':
            $period = optionalParam('period', 'all');
            $statusFilter = optionalParam('status', 'all');

            $where = ['1=1'];
            $params = [];

            switch ($period) {
                case 'this_month':
                    $where[] = "r.created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')";
                    break;
                case 'this_year':
                    $where[] = "r.created_at >= DATE_FORMAT(NOW(), '%Y-01-01')";
                    break;
            }

            if ($statusFilter !== 'all') {
                $where[] = "r.status = :status";
                $params[':status'] = $statusFilter;
            }

            $whereClause = implode(' AND ', $where);

            try {
                $db = getDB();

                $stmt = $db->prepare("
                    SELECT 
                        r.id,
                        r.reporter_name,
                        r.reporter_email,
                        r.title,
                        r.description,
                        r.source_type,
                        r.category,
                        r.status,
                        r.created_at
                    FROM reports r
                    WHERE {$whereClause}
                    ORDER BY r.created_at DESC
                ");
                $stmt->execute($params);
                $reports = $stmt->fetchAll();

                jsonResponse([
                    'success' => true,
                    'data' => $reports,
                    'count' => count($reports)
                ]);

            } catch (PDOException $e) {
                jsonResponse(['success' => false, 'message' => 'Query gagal: ' . $e->getMessage()], 500);
            }
            break;

        default:
            jsonResponse(['success' => false, 'message' => 'Action GET tidak dikenali'], 400);
            break;
    }

// ============================================================
// POST
// ============================================================
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {

    switch ($action) {

        case 'create':
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;

    $userId      = isset($input['user_id']) && $input['user_id'] !== '' ? (int)$input['user_id'] : null;
    $name        = cleanInput($input['reporter_name'] ?? '');
    $email       = cleanInput($input['reporter_email'] ?? '');
    $title       = cleanInput($input['title'] ?? '');
    $desc        = cleanInput($input['description'] ?? '');
    $sourceType  = cleanInput($input['source_type'] ?? 'lainnya');
    $category    = cleanInput($input['category'] ?? 'lainnya');

    if (empty($desc)) {
        jsonResponse(['success' => false, 'message' => 'Deskripsi laporan wajib diisi'], 400);
    }

    if (empty($name)) $name = 'Anonim';
    if (empty($title)) $title = mb_substr($desc, 0, 80); // otomatis dari deskripsi

    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO reports (user_id, reporter_name, reporter_email, title, description, source_type, category, status)
            VALUES (:user_id, :name, :email, :title, :desc, :src, :cat, 'pending')
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':name'    => $name,
            ':email'   => $email,
            ':title'   => $title,
            ':desc'    => $desc,
            ':src'     => $sourceType,
            ':cat'     => $category
        ]);

        jsonResponse([
            'success' => true,
            'message' => 'Laporan berhasil dikirim',
            'data' => [
                'id' => (int)$db->lastInsertId(),
                'reporter_name' => $name,
                'status' => 'pending'
            ]
        ], 201);

    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'message' => 'Gagal simpan: ' . $e->getMessage()], 500);
    }
    break;

        default:
            jsonResponse(['success' => false, 'message' => 'Action POST tidak dikenali'], 400);
            break;
    }

} else {
    jsonResponse(['success' => false, 'message' => 'Method tidak diizinkan'], 405);
}
?>
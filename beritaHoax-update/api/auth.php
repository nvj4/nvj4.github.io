<?php
require_once __DIR__ . '/koneksi.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

 $action = optionalParam('action', '');

switch ($action) {

    case 'users':
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT id, name, email, created_at FROM users ORDER BY name ASC");
            $stmt->execute();
            jsonResponse(['success' => true, 'data' => $stmt->fetchAll(), 'count' => $stmt->rowCount()]);
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'message' => 'Query gagal: ' . $e->getMessage()], 500);
        }
        break;

    case 'user_stats':
        try {
            $db = getDB();
            
            $stmt = $db->query("SELECT COUNT(*) AS total FROM users");
            $totalUsers = (int)$stmt->fetch()['total'];

            $stmt = $db->query("SELECT name, email FROM users ORDER BY name ASC");
            $listUsers = $stmt->fetchAll();

            $stmt = $db->query("
                SELECT 
                    CASE 
                        WHEN email LIKE '%@gmail.com' THEN 'Gmail'
                        WHEN email LIKE '%@yahoo.com' OR email LIKE '%@yahoo.co.id' THEN 'Yahoo'
                        WHEN email LIKE '%@student.unindra.ac.id' THEN 'Email Kampus (UNINDRA)'
                        WHEN email LIKE '%@outlook.com' THEN 'Outlook'
                        ELSE 'Domain Lainnya'
                    END AS domain_name,
                    COUNT(*) AS jumlah
                FROM users 
                GROUP BY domain_name
                ORDER BY jumlah DESC
            ");
            $emailDomains = $stmt->fetchAll();

            jsonResponse([
                'success' => true,
                'data' => [
                    'total_users' => $totalUsers,
                    'list_users' => $listUsers,
                    'email_domains' => $emailDomains
                ]
            ]);
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'message' => 'Query gagal: ' . $e->getMessage()], 500);
        }
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Action tidak dikenali. Gunakan: users, user_stats'], 400);
        break;
}
?>
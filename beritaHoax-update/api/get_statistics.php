<?php
header('Content-Type: application/json');
require_once __DIR__ . '/koneksi.php';

try {
    $stmt = $pdo->prepare("SELECT total_news_analyzed, total_users, accuracy_rate, avg_detection_time FROM system_statistic LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch();

    if ($row) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Data statistik kosong']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Query error: ' . $e->getMessage()]);
}
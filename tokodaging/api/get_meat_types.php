<?php
include 'koneksi.php';

$result = $conn->query("SELECT * FROM meat_types");
$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);

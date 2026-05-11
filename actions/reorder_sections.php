<?php
session_start();
if (!isset($_SESSION['user_id'])) { http_response_code(403); exit(); }
include "../config/db.php";

$data = json_decode(file_get_contents('php://input'), true);
if (empty($data['ids']) || !is_array($data['ids'])) { http_response_code(400); exit(); }

foreach ($data['ids'] as $pos => $id) {
    $id  = (int)$id;
    $pos = (int)$pos;
    $conn->query("UPDATE sections SET position = $pos WHERE id = $id");
}

echo json_encode(['ok' => true]);

<?php
session_start();
if (!isset($_SESSION['user_id'])) { http_response_code(403); exit(); }
include "../config/db.php";

$data = json_decode(file_get_contents('php://input'), true);
$id   = (int)($data['id']   ?? 0);
$h    = (int)($data['height'] ?? 0);
$type = $data['type'] ?? '';

if ($id > 0) {
    $row   = $conn->query("SELECT style FROM sections WHERE id = $id")->fetch_assoc();
    $style = json_decode($row['style'] ?? '{}', true) ?: [];

    if ($type === 'image') {
        // Store image-specific dimensions and wrap
        if (!empty($data['width']))  $style['img_width']  = (int)$data['width'];
        if ($h > 0)                  $style['img_height'] = $h;
        if (isset($data['wrap']))    $style['img_wrap']   = $data['wrap'];
        if (isset($data['x']))       $style['img_x']      = (int)$data['x'];
        if (isset($data['y']))       $style['img_y']      = (int)$data['y'];
    } else {
        // Non-image: store as block height
        if ($h > 0) $style['height'] = $h;
    }

    $styleJson = $conn->real_escape_string(json_encode($style));
    $heightVal = ($type === 'image') ? (int)($data['height'] ?? 0) : $h;
    $conn->query("UPDATE sections SET style = '$styleJson', height = $heightVal WHERE id = $id");
}

echo json_encode(['ok' => true]);

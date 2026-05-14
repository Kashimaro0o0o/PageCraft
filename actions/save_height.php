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
        // Canva-style canvas fields
        if (isset($data['canvas_w']))  $style['canvas_w']  = (int)$data['canvas_w'];
        if (isset($data['canvas_h']))  $style['canvas_h']  = (int)$data['canvas_h'];
        if (isset($data['img_scale'])) $style['img_scale'] = (float)$data['img_scale'];
        if (isset($data['img_off_x'])) $style['img_off_x'] = (int)$data['img_off_x'];
        if (isset($data['img_off_y'])) $style['img_off_y'] = (int)$data['img_off_y'];
        if (isset($data['fit_mode']))  $style['fit_mode']  = $data['fit_mode'];

        // Legacy compat fields
        if (!empty($data['width']))    $style['img_width']  = (int)$data['width'];
        if ($h > 0)                    $style['img_height'] = $h;
        if (isset($data['x']))         $style['img_x']      = (int)$data['x'];
        if (isset($data['y']))         $style['img_y']      = (int)$data['y'];
    } else {
        if ($h > 0) $style['height'] = $h;
    }

    $styleJson = $conn->real_escape_string(json_encode($style));
    $heightVal = $h > 0 ? $h : 0;
    $conn->query("UPDATE sections SET style = '$styleJson', height = $heightVal WHERE id = $id");
}

echo json_encode(['ok' => true]);
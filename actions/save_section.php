<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); exit();
}
include "../config/db.php";

$page_id = (int)$_POST['page_id'];
$type    = $_POST['type'];
$site_id = isset($_GET['site_id']) ? (int)$_GET['site_id'] : (int)($_POST['site_id'] ?? 0);

$content = $_POST['content'] ?? '';

if ($type === 'button') {
    $url    = $_POST['btn_url']         ?? '#';
    $bg     = $_POST['btn_bg']          ?? '#6c3afc';
    $color  = $_POST['btn_color']       ?? '#ffffff';
    $align  = $_POST['btn_align']       ?? 'center';
    $size   = !empty($_POST['btn_font_size']) ? $_POST['btn_font_size'] : '16';
    $weight = $_POST['btn_font_weight'] ?? 'bold';
    $radius = $_POST['btn_radius']      ?? '12px';
    $style  = json_encode([
        'url'         => $url,
        'bg'          => $bg,
        'color'       => $color,
        'text_align'  => $align,
        'font_size'   => $size . 'px',
        'font_weight' => $weight,
        'radius'      => $radius,
    ]);

} elseif ($type === 'image') {
    if (!empty($_FILES['image_file']['name'])) {
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $ext     = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if (!in_array($ext, $allowed)) die('Invalid file type.');
        $filename = 'img_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        move_uploaded_file($_FILES['image_file']['tmp_name'], $uploadDir . $filename);
        $content = '../uploads/' . $filename;
    }
    $style = json_encode([]);

} elseif ($type === 'header') {
    $text_align  = $_POST['text_align']  ?? 'left';
    $font_size   = !empty($_POST['font_size']) ? $_POST['font_size'] : '24';
    $weight      = $_POST['font_weight'] ?? 'bold';
    $font_family = $_POST['font_family'] ?? 'Arial, sans-serif';
    $bg          = $_POST['header_bg']   ?? '#1a1a2e';
    $hcolor      = $_POST['header_color']?? '#ffffff';
    $style = json_encode([
        'bg'          => $bg,
        'color'       => $hcolor,
        'text_align'  => $text_align,
        'font_size'   => $font_size . 'px',
        'font_weight' => $weight,
        'font_family' => $font_family,
    ]);

} else {
    $text_align  = $_POST['text_align']  ?? 'left';
    $font_size   = !empty($_POST['font_size']) ? $_POST['font_size'] : '16';
    $color       = $_POST['color']       ?? '#000000';
    $weight      = $_POST['font_weight'] ?? 'normal';
    $font_family = $_POST['font_family'] ?? 'Arial, sans-serif';
    $style = json_encode([
        'text_align'  => $text_align,
        'font_size'   => $font_size . 'px',
        'color'       => $color,
        'font_weight' => $weight,
        'font_family' => $font_family,
    ]);
}

$result   = $conn->query("SELECT MAX(position) as max_pos FROM sections WHERE page_id = $page_id");
$row      = $result->fetch_assoc();
$position = ($row['max_pos'] ?? 0) + 1;

$stmt = $conn->prepare("INSERT INTO sections (page_id, type, content, style, position) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("isssi", $page_id, $type, $content, $style, $position);
$stmt->execute();
$stmt->close();

header("Location: ../pages/editor.php?site_id=" . $site_id);
exit();
?>

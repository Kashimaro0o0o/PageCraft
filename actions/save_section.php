<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); exit();
}
include "../config/db.php";

$page_id = (int)$_POST['page_id'];
$type    = $_POST['type'];
$content = $_POST['content'] ?? '';
$site_id = isset($_GET['site_id']) ? (int)$_GET['site_id'] : (int)($_POST['site_id'] ?? 0);

$text_align = $_POST['text_align'] ?? 'left';
$font_size  = $_POST['font_size'] ?? '16';
$color      = $_POST['color'] ?? '#000000';
$weight     = $_POST['font_weight'] ?? 'normal';
$font_family = $_POST['font_family'] ?? 'Arial, sans-serif';

if ($type === 'header') {
    $style = json_encode([
        "bg"    => "#1a1a2e",
        "color" => "#ffffff"
    ]);
} else {
    $style = json_encode([
        "text_align"   => $text_align,
        "font_size"    => $font_size . "px",
        "color"        => $color,
        "font_weight"  => $weight,
        "font_family"  => $font_family
    ]);
}

$result  = $conn->query("SELECT MAX(position) as max_pos FROM sections WHERE page_id = $page_id");
$row     = $result->fetch_assoc();
$position = ($row['max_pos'] ?? 0) + 1;

$stmt = $conn->prepare("INSERT INTO sections (page_id, type, content, style, position) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("isssi", $page_id, $type, $content, $style, $position);
$stmt->execute();
$stmt->close();

header("Location: ../admin/editor.php?site_id=".$site_id);
exit();
?>
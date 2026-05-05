<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); exit();
}
include "../config/db.php";

$id      = (int)$_POST['id'];
$content = trim($_POST['content'] ?? '');
$site_id = (int)$_POST['site_id'];
$type    = $_POST['section_type'] ?? '';

if ($id > 0) {
    if ($type === 'header') {
        $bg    = $_POST['bg_color']   ?? '#1a1a2e';
        $color = $_POST['text_color'] ?? '#ffffff';
        $align = $_POST['text_align'] ?? 'left';
        $size  = !empty($_POST['font_size']) ? $_POST['font_size'] : '24';
        $weight= $_POST['font_weight']?? 'bold';
        $family= $_POST['font_family']?? 'Arial, sans-serif';
        $style = json_encode([
            'bg'          => $bg,
            'color'       => $color,
            'text_align'  => $align,
            'font_size'   => $size . 'px',
            'font_weight' => $weight,
            'font_family' => $family,
        ]);
        $stmt = $conn->prepare("UPDATE sections SET content = ?, style = ? WHERE id = ?");
        $stmt->bind_param("ssi", $content, $style, $id);
    } else {
        $align  = $_POST['text_align']  ?? 'left';
        $size   = !empty($_POST['font_size']) ? $_POST['font_size'] : '16';
        $color  = $_POST['color']       ?? '#000000';
        $weight = $_POST['font_weight'] ?? 'normal';
        $family = $_POST['font_family'] ?? 'Arial, sans-serif';
        $style  = json_encode([
            'text_align'  => $align,
            'font_size'   => $size . 'px',
            'color'       => $color,
            'font_weight' => $weight,
            'font_family' => $family,
        ]);
        $stmt = $conn->prepare("UPDATE sections SET content = ?, style = ? WHERE id = ?");
        $stmt->bind_param("ssi", $content, $style, $id);
    }
    $stmt->execute();
    $stmt->close();
}

header("Location: ../pages/editor.php?site_id=$site_id");
exit();
?>

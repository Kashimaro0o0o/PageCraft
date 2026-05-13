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
        $stmt = $conn->prepare("UPDATE sections SET content = ?, style = ? WHERE id = ?");
        $stmt->bind_param("ssi", $content, $style, $id);
    } elseif ($type === 'image') {
        // Handle image upload or URL
        if (!empty($_FILES['image_file']['name'])) {
            $uploadDir = '../uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext     = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp'];
            if (!in_array($ext, $allowed)) {
                header("Location: ../pages/editor.php?site_id=$site_id&error=invalid_file");
                exit();
            }
            $filename = 'img_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            move_uploaded_file($_FILES['image_file']['tmp_name'], $uploadDir . $filename);
            $content = '../uploads/' . $filename;
        }
        if (empty($content) && empty($_FILES['image_file']['name'])) {
            $existing = $conn->query("SELECT content FROM sections WHERE id = $id")->fetch_assoc();
            $content = $existing['content'] ?? '';
        }
        $stmt = $conn->prepare("UPDATE sections SET content = ? WHERE id = ?");
        $stmt->bind_param("si", $content, $id);
    } elseif ($type === 'header') {
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


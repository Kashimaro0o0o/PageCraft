<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); exit();
}
include "../config/db.php";

$id      = (int)$_POST['id'];
$content = trim($_POST['content'] ?? '');
$site_id = (int)$_POST['site_id'];

if ($id > 0) {
    $stmt = $conn->prepare("UPDATE sections SET content = ? WHERE id = ?");
    $stmt->bind_param("si", $content, $id);
    $stmt->execute();
    $stmt->close();
}

header("Location: ../pages/editor.php?site_id=$site_id");
exit();
?>
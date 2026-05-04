<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); exit();
}
include "../config/db.php";

$site_id   = (int)$_POST['site_id'];
$site_name = trim($_POST['site_name'] ?? '');
$user_id   = $_SESSION['user_id'];

if ($site_id > 0 && $site_name !== '') {
    $stmt = $conn->prepare("UPDATE sites SET site_name = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sii", $site_name, $site_id, $user_id);
    $stmt->execute();
    $stmt->close();
}

header("Location: ../pages/editor.php?site_id=$site_id");
exit();
?>
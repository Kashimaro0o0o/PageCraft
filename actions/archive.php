<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); exit();
}
include "../config/db.php";

$id      = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$site_id = isset($_GET['site_id']) ? (int)$_GET['site_id'] : 0;

if ($id > 0) {
    $conn->query("UPDATE sections SET is_archived = 1 WHERE id = $id");
}

header("Location: ../pages/editor.php?site_id=$site_id");
exit();
?>
<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); exit();
}

$site_id = (int)$_GET['site_id'];
$conn->query("UPDATE sites SET is_published = 1 WHERE id = $site_id");

// AJAX call — just return OK
if (isset($_GET['ajax'])) {
    echo json_encode(['ok' => true]);
    exit();
}

// Normal redirect for backwards compatibility
header("Location: ../pages/editor.php?site_id=$site_id&published=1");
exit();

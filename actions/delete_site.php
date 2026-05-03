<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); exit();
}
include "../config/db.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
    // Delete sections first
    $conn->query("DELETE FROM sections WHERE page_id IN (SELECT id FROM pages WHERE site_id = $id)");
    // Delete pages
    $conn->query("DELETE FROM pages WHERE site_id = $id");
    // Delete site
    $conn->query("DELETE FROM sites WHERE id = $id AND user_id = " . $_SESSION['user_id']);
}

header("Location: ../pages/dashboard.php");
exit();
?>
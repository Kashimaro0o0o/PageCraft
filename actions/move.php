<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); exit();
}
include "../config/db.php";

$id      = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$dir     = $_GET['dir'] ?? 'up';
$site_id = isset($_GET['site_id']) ? (int)$_GET['site_id'] : 0;

// Get current section
$section = $conn->query("SELECT * FROM sections WHERE id = $id")->fetch_assoc();
if (!$section) { header("Location: ../pages/editor.php?site_id=$site_id"); exit(); }

$page_id  = $section['page_id'];
$position = $section['position'];

if ($dir === 'up') {
    // Find section above
    $swap = $conn->query("SELECT * FROM sections WHERE page_id = $page_id AND position < $position AND is_archived = 0 ORDER BY position DESC LIMIT 1")->fetch_assoc();
} else {
    // Find section below
    $swap = $conn->query("SELECT * FROM sections WHERE page_id = $page_id AND position > $position AND is_archived = 0 ORDER BY position ASC LIMIT 1")->fetch_assoc();
}

if ($swap) {
    // Swap positions
    $conn->query("UPDATE sections SET position = {$swap['position']} WHERE id = $id");
    $conn->query("UPDATE sections SET position = $position WHERE id = {$swap['id']}");
}

header("Location: ../pages/editor.php?site_id=$site_id");
exit();
?>
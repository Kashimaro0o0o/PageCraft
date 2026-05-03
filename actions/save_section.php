<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); exit();
}
include "../config/db.php";

$page_id = (int)$_POST['page_id'];
$type    = $_POST['type'];
$content = $_POST['content'] ?? '';
$site_id = isset($_GET['site_id']) ? (int)$_GET['site_id'] : 0;

$result  = $conn->query("SELECT MAX(position) as max_pos FROM sections WHERE page_id = $page_id");
$row     = $result->fetch_assoc();
$position = ($row['max_pos'] ?? 0) + 1;

$stmt = $conn->prepare("INSERT INTO sections (page_id, type, content, position) VALUES (?, ?, ?, ?)");
$stmt->bind_param("issi", $page_id, $type, $content, $position);
$stmt->execute();
$stmt->close();

header("Location: ../pages/editor.php?site_id=" . $site_id);
exit();
?>
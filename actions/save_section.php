<?php
include "../config/db.php";

$page_id = $_POST['page_id'];
$type = $_POST['type'];
$content = $_POST['content'];

$result = $conn->query("SELECT MAX(position) as max_pos FROM sections WHERE page_id=$page_id");
$row = $result->fetch_assoc();
$position = $row['max_pos'] + 1;

$conn->query("INSERT INTO sections (page_id, type, content, position)
VALUES ($page_id, '$type', '$content', $position)");

header("Location: ../pages/editor.php?site_id=".$_GET['site_id']);
?>
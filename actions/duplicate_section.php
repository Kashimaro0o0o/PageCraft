<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }
include "../config/db.php";

$id      = (int)($_GET['id']      ?? 0);
$site_id = (int)($_GET['site_id'] ?? 0);

if ($id > 0) {
    // Get the original section
    $row = $conn->query("SELECT * FROM sections WHERE id = $id")->fetch_assoc();
    if ($row) {
        // Insert position: right after the original
        $conn->query("UPDATE sections SET position = position + 1 WHERE page_id = {$row['page_id']} AND position > {$row['position']}");

        $newPos  = (int)$row['position'] + 1;
        $page_id = (int)$row['page_id'];
        $type    = $conn->real_escape_string($row['type']);
        $content = $conn->real_escape_string($row['content']);
        $style   = $conn->real_escape_string($row['style']);
        $height  = (int)$row['height'];

        $conn->query("INSERT INTO sections (page_id, type, content, style, position, height)
                      VALUES ($page_id, '$type', '$content', '$style', $newPos, $height)");
    }
}

header("Location: ../pages/editor.php?site_id=$site_id");
exit();
?>
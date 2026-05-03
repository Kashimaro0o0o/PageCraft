<?php
include "../config/db.php";

$id = $_GET['id'];
$dir = $_GET['dir'];
$site_id = $_GET['site_id'];

$current = $conn->query("SELECT * FROM sections WHERE id=$id")->fetch_assoc();

if ($dir == "up") {
    $swap = $conn->query("SELECT * FROM sections WHERE page_id=".$current['page_id']." AND position < ".$current['position']." ORDER BY position DESC LIMIT 1")->fetch_assoc();
} else {
    $swap = $conn->query("SELECT * FROM sections WHERE page_id=".$current['page_id']." AND position > ".$current['position']." ORDER BY position ASC LIMIT 1")->fetch_assoc();
}

if ($swap) {
    $conn->query("UPDATE sections SET position=".$swap['position']." WHERE id=".$current['id']);
    $conn->query("UPDATE sections SET position=".$current['position']." WHERE id=".$swap['id']);
}

header("Location: ../pages/editor.php?site_id=$site_id");
?>
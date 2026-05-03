<?php
include "../config/db.php";

$id = $_GET['id'];
$site_id = $_GET['site_id'];

$conn->query("UPDATE sections SET is_archived=1 WHERE id=$id");

header("Location: ../pages/editor.php?site_id=$site_id");
?>
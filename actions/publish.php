<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$site_id = (int)$_GET['site_id'];

$conn->query("UPDATE sites SET is_published = 1 WHERE id = $site_id");

header("Location: ../pages/view.php?site_id=$site_id");
exit();
?>
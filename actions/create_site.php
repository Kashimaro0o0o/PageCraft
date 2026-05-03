<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); exit();
}
include "../config/db.php";

$site_name = trim($_POST['site_name'] ?? '');
$user_id   = $_SESSION['user_id'];

if ($site_name !== '') {
    $stmt = $conn->prepare("INSERT INTO sites (user_id, site_name) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $site_name);
    $stmt->execute();
    $stmt->close();
}

header("Location: ../pages/dashboard.php");
exit();
?>
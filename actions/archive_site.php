<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); exit();
}
include "../config/db.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$restore = isset($_GET['restore']) && $_GET['restore'] === '1';

if ($id > 0) {
    // Ensure is_archived column exists in sites table
    $colCheck = $conn->query("SHOW COLUMNS FROM sites LIKE 'is_archived'");
    if ($colCheck->num_rows === 0) {
        $conn->query("ALTER TABLE sites ADD COLUMN is_archived TINYINT(1) DEFAULT 0");
    }

    $val = $restore ? 0 : 1;
    $conn->query("UPDATE sites SET is_archived = $val WHERE id = $id AND user_id = " . $_SESSION['user_id']);
}

$redirect = $restore ? "../pages/dashboard.php" : "../pages/dashboard.php";
header("Location: $redirect");
exit();
?>

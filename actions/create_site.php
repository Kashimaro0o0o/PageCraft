<?php
include "../config/db.php";

$site_name = $_POST['site_name'];

$conn->query("INSERT INTO sites (user_id, site_name) VALUES (1, '$site_name')");

header("Location: ../pages/dashboard.php");
?>
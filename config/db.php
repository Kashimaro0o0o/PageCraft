<?php
$conn = new mysqli("localhost", "root", "", "pagecraft");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
<?php
include "../config/db.php";

$id = (int)$_POST['id'];
$bg = $_POST['bg'] ?? '#1a1a2e';
$color = $_POST['color'] ?? '#ffffff';

$style = json_encode([
    "bg" => $bg,
    "color" => $color
]);

$stmt = $conn->prepare("UPDATE sections SET style = ? WHERE id = ?");
$stmt->bind_param("si", $style, $id);
$stmt->execute();
$stmt->close();
?>
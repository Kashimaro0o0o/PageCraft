<?php
include "../config/db.php";

$site_id = $_GET['site_id'];

$page = $conn->query("SELECT * FROM pages WHERE site_id=$site_id")->fetch_assoc();
$page_id = $page['id'];

echo "<h1>".$page['title']."</h1>";

$sections = $conn->query("SELECT * FROM sections 
    WHERE page_id=$page_id AND is_archived=0 
    ORDER BY position ASC");

while ($row = $sections->fetch_assoc()) {

    if ($row['type'] == 'text') {
        echo "<p>".$row['content']."</p>";
    }

    if ($row['type'] == 'image') {
        echo "<img src='".$row['content']."' style='max-width:500px'><br>";
    }
}
?>
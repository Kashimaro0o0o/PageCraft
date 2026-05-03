<?php
include "../config/db.php";

$site_id = $_GET['site_id'];

$page = $conn->query("SELECT * FROM pages WHERE site_id=$site_id")->fetch_assoc();

if (!$page) {
    $conn->query("INSERT INTO pages (site_id, title, slug) VALUES ($site_id, 'Home', 'home')");
    $page = $conn->query("SELECT * FROM pages WHERE site_id=$site_id")->fetch_assoc();
}

$page_id = $page['id'];
?>

<h2>Editor</h2>

<form action="../actions/save_section.php?site_id=<?= $site_id ?>" method="POST">
    <input type="hidden" name="page_id" value="<?= $page_id ?>">

    <select name="type">
        <option value="text">Text</option>
        <option value="image">Image</option>
    </select>

    <textarea name="content" placeholder="Enter content"></textarea>

    <button type="submit">Add Section</button>
</form>

<hr>

<?php
$sections = $conn->query("SELECT * FROM sections 
    WHERE page_id=$page_id AND is_archived=0 
    ORDER BY position ASC");

while ($row = $sections->fetch_assoc()) {
    echo "<div style='border:1px solid #ccc; margin:10px; padding:10px;'>";

    if ($row['type'] == 'text') {
        echo "<p>".$row['content']."</p>";
    } else {
        echo "<img src='".$row['content']."' width='200'>";
    }

    echo "<br>";
    echo "<a href='../actions/move.php?id=".$row['id']."&dir=up&site_id=$site_id'>⬆️</a> ";
    echo "<a href='../actions/move.php?id=".$row['id']."&dir=down&site_id=$site_id'>⬇️</a> ";
    echo "<a href='../actions/archive.php?id=".$row['id']."&site_id=$site_id'>📦 Archive</a>";

    echo "</div>";
}
?>
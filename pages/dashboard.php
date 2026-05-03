<?php
include "../config/db.php";
?>

<h2>Create New Website</h2>

<form action="../actions/create_site.php" method="POST">
    <input type="text" name="site_name" placeholder="Website Name" required>
    <button type="submit">Create</button>
</form>

<h3>Your Sites</h3>

<?php
$result = $conn->query("SELECT * FROM sites");

while ($row = $result->fetch_assoc()) {
    echo "<p>";
    echo $row['site_name'];
    echo " | <a href='editor.php?site_id=".$row['id']."'>Edit</a>";
    echo " | <a href='view.php?site_id=".$row['id']."'>View</a>";
    echo "</p>";
}
?>
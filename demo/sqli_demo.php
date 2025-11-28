<?php
// ...existing code...
$db = new PDO('sqlite::memory:');
$db->exec("CREATE TABLE users(id INTEGER PRIMARY KEY, username TEXT)");
$db->exec("INSERT INTO users(username) VALUES ('admin'), ('user')");

// ❌ vulnerable
if (isset($_GET['name'])) {
    $name = $_GET['name'];
    $sql = "SELECT * FROM users WHERE username = '$name'";
    $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>Vulnerable SQL: $sql\n"; var_dump($rows); echo "</pre>";
}
// ✅ seguro
if (isset($_GET['safe'])) {
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$_GET['safe']]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>Seguro username=" . htmlspecialchars($_GET['safe'], ENT_QUOTES) . "\n"; var_dump($rows); echo "</pre>";
}
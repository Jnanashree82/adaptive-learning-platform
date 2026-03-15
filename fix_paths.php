<?php
require_once 'config/database.php';

echo "<h1>🔧 Fixing File Paths</h1>";

// Get all notes with bad paths (full Windows paths)
$stmt = $pdo->query("SELECT * FROM notes WHERE file_path LIKE 'D:%' OR file_path = ''");
$notes = $stmt->fetchAll();

if (empty($notes)) {
    echo "<p style='color:green;'>✅ All paths are correct!</p>";
} else {
    echo "<p>Found " . count($notes) . " notes with incorrect paths.</p>";
    
    foreach ($notes as $note) {
        // Generate new filename
        $clean_title = preg_replace('/[^a-zA-Z0-9]/', '_', $note['title']);
        $new_filename = $note['id'] . '_' . $clean_title . '.' . ($note['file_type'] ?? 'pdf');
        $new_path = 'uploads/notes/' . $new_filename;
        
        echo "<p>Note ID {$note['id']}: '{$note['title']}'<br>";
        echo "Old path: {$note['file_path']}<br>";
        echo "New path: {$new_path}<br>";
        
        // Update database
        $update = $pdo->prepare("UPDATE notes SET file_path = ? WHERE id = ?");
        $update->execute([$new_path, $note['id']]);
        
        echo "✅ Updated<br><br>";
    }
    
    echo "<p style='color:green;'>✅ All paths fixed!</p>";
}

// Show current paths
echo "<h2>Current File Paths:</h2>";
$all = $pdo->query("SELECT id, title, file_path FROM notes ORDER BY id")->fetchAll();
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Title</th><th>File Path</th></tr>";
foreach ($all as $n) {
    echo "<tr>";
    echo "<td>{$n['id']}</td>";
    echo "<td>{$n['title']}</td>";
    echo "<td>{$n['file_path']}</td>";
    echo "</tr>";
}
echo "</table>";
?>
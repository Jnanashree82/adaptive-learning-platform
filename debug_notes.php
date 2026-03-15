<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

// Get all notes
$notes = $pdo->query("SELECT id, title, file_path FROM notes ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Notes</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f0f2f5; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #667eea; color: white; }
        tr:hover { background: #f5f5f5; }
        .success { color: green; }
        .error { color: red; }
        .btn { 
            padding: 5px 10px; 
            background: #667eea; 
            color: white; 
            text-decoration: none; 
            border-radius: 3px; 
        }
    </style>
</head>
<body>
    <h1>🔍 Notes Debug Page</h1>
    <table>
        <tr>
            <th>ID</th>
            <th>Title</th>
            <th>File Path (from DB)</th>
            <th>Full Server Path</th>
            <th>File Exists?</th>
            <th>Action</th>
        </tr>
        <?php foreach ($notes as $note): 
            $full_path = __DIR__ . DIRECTORY_SEPARATOR . $note['file_path'];
            $exists = file_exists($full_path);
        ?>
        <tr>
            <td><?php echo $note['id']; ?></td>
            <td><?php echo htmlspecialchars($note['title']); ?></td>
            <td><?php echo $note['file_path']; ?></td>
            <td><small><?php echo $full_path; ?></small></td>
            <td class="<?php echo $exists ? 'success' : 'error'; ?>">
                <?php echo $exists ? '✅ YES' : '❌ NO'; ?>
            </td>
            <td>
                <a href="view_note.php?id=<?php echo $note['id']; ?>" class="btn" target="_blank">View</a>
                <a href="<?php echo $note['file_path']; ?>" class="btn" target="_blank">Direct Link</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
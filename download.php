<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$note_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$note_id) {
    die('Invalid note ID');
}

// Get note info
$sql = "SELECT * FROM notes WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$note_id]);
$note = $stmt->fetch();

if (!$note) {
    die('Note not found');
}

// Update download count
$sql = "UPDATE notes SET download_count = download_count + 1 WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$note_id]);

// If there's a file path, download that file
if ($note['file_path'] && file_exists($note['file_path'])) {
    $file = $note['file_path'];
    $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $note['title']) . '.' . pathinfo($file, PATHINFO_EXTENSION);
    
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($file));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    readfile($file);
    exit();
} 
// If no file, create text file from content
else if ($note['content']) {
    $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $note['title']) . '.txt';
    
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($note['content']));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    echo $note['content'];
    exit();
} else {
    die('No content available for download');
}
?>
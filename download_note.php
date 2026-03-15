<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get note details
$stmt = $pdo->prepare("SELECT * FROM notes WHERE id = ?");
$stmt->execute([$id]);
$note = $stmt->fetch();

if (!$note) {
    die("Note not found");
}

// Update download count
$pdo->prepare("UPDATE notes SET download_count = download_count + 1 WHERE id = ?")->execute([$id]);

// Get file path
$file_path = $note['file_path'];
$full_path = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file_path);

if (!file_exists($full_path)) {
    die("File not found at: " . $full_path);
}

// Get file info
$file_ext = pathinfo($file_path, PATHINFO_EXTENSION);
$file_name = $note['title'] . '.' . $file_ext;
$file_size = filesize($full_path);

// Set headers for download
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $file_name . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . $file_size);

// Clear output buffer
ob_clean();
flush();

// Read file
readfile($full_path);
exit();
?>
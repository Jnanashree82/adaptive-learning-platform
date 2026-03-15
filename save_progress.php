<?php
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$note_id = $data['note_id'] ?? 0;
$progress = $data['progress'] ?? 0;
$position = $data['position'] ?? 0;

if (!$note_id) {
    echo json_encode(['success' => false]);
    exit();
}

$sql = "INSERT INTO reading_progress (user_id, note_id, last_position, last_read, progress_percent) 
        VALUES (?, ?, ?, NOW(), ?)
        ON DUPLICATE KEY UPDATE 
        last_position = VALUES(last_position),
        last_read = NOW(),
        progress_percent = VALUES(progress_percent)";
        
$stmt = $pdo->prepare($sql);
$success = $stmt->execute([$_SESSION['user_id'], $note_id, $position, $progress]);

echo json_encode(['success' => $success]);
?>
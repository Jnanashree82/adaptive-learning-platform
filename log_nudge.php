<?php
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$note_id = $data['note_id'] ?? 0;

if (!$note_id) {
    echo json_encode(['success' => false]);
    exit();
}

$sql = "UPDATE focus_sessions 
        SET nudges_sent = nudges_sent + 1 
        WHERE user_id = ? AND note_id = ? 
        ORDER BY id DESC LIMIT 1";
        
$stmt = $pdo->prepare($sql);
$success = $stmt->execute([$_SESSION['user_id'], $note_id]);

echo json_encode(['success' => $success]);
?>
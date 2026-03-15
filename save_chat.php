<?php
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false]);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user_id'];
$session_id = session_id();
$message = $data['message'] ?? '';
$response = $data['response'] ?? '';
$type = $data['type'] ?? 'text';

$sql = "INSERT INTO chat_history (user_id, session_id, message, response, message_type) VALUES (?, ?, ?, ?, ?)";
$stmt = $pdo->prepare($sql);
$success = $stmt->execute([$user_id, $session_id, $message, $response, $type]);

echo json_encode(['success' => $success]);
?>
<?php
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['profile'])) {
    $sql = "UPDATE users SET accessibility_profile = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$data['profile'], $_SESSION['user_id']]);
    $_SESSION['accessibility']['profile'] = $data['profile'];
    
} elseif (isset($data['text_size'])) {
    $sql = "UPDATE users SET text_size = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$data['text_size'], $_SESSION['user_id']]);
    $_SESSION['accessibility']['text_size'] = $data['text_size'];
    
} elseif (isset($data['high_contrast'])) {
    $sql = "UPDATE users SET high_contrast = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$data['high_contrast'], $_SESSION['user_id']]);
    $_SESSION['accessibility']['high_contrast'] = $data['high_contrast'];
    
} elseif (isset($data['focus_mode'])) {
    $sql = "UPDATE users SET focus_mode = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$data['focus_mode'], $_SESSION['user_id']]);
    $_SESSION['accessibility']['focus_mode'] = $data['focus_mode'];
}

echo json_encode(['success' => true]);
?>
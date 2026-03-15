<?php
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$theme = $data['theme'] ?? 'light';

$sql = "UPDATE users SET theme_mode = ? WHERE id = ?";
$stmt = $pdo->prepare($sql);
$success = $stmt->execute([$theme, $_SESSION['user_id']]);

if ($success) {
    $_SESSION['accessibility']['theme_mode'] = $theme;
}

echo json_encode(['success' => $success]);
?>
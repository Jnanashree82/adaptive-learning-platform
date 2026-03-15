<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    die('Please login first');
}

echo "<h1>🔍 Accessibility Settings Diagnostic</h1>";

// Get user settings from database
$sql = "SELECT accessibility_profile, text_size, high_contrast, focus_mode FROM users WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['user_id']]);
$db_settings = $stmt->fetch();

echo "<h2>Database Settings:</h2>";
echo "<pre>";
print_r($db_settings);
echo "</pre>";

echo "<h2>Session Settings:</h2>";
echo "<pre>";
print_r($_SESSION['accessibility'] ?? 'Not set in session');
echo "</pre>";

echo "<h2>Session Data:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>LocalStorage Check Instructions:</h2>";
echo "<p>Open browser console (F12) and type:</p>";
echo "<code>console.log(localStorage);</code>";
echo "<p>You should see:</p>";
echo "<ul>";
echo "<li>accessibility_profile</li>";
echo "<li>accessibility_text_size</li>";
echo "<li>accessibility_high_contrast</li>";
echo "<li>accessibility_focus_mode</li>";
echo "</ul>";

echo "<h2>Test Links:</h2>";
echo "<ul>";
echo "<li><a href='test-accessibility.php' target='_blank'>Test Accessibility Page</a></li>";
echo "<li><a href='dashboard.php' target='_blank'>Dashboard</a></li>";
echo "<li><a href='branches.php' target='_blank'>Branches</a></li>";
echo "<li><a href='settings.php' target='_blank'>Settings</a></li>";
echo "</ul>";
?>
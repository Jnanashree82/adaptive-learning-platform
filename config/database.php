<?php
$host = 'localhost';
$port = '3307';  // Make sure this is included
$dbname = 'adaptive_learning';
$username = 'root';
$password = '';

try {
    // Include port in connection
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8");
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

session_start();
?>
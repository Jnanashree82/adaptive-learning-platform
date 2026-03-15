<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit();
}

$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? 0;

if ($type && $id) {
    switch ($type) {
        case 'branch':
            // First delete related semesters and subjects
            $pdo->prepare("DELETE FROM semesters WHERE branch_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM branches WHERE id = ?")->execute([$id]);
            break;
            
        case 'semester':
            // Delete related subjects first
            $pdo->prepare("DELETE FROM subjects WHERE semester_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM semesters WHERE id = ?")->execute([$id]);
            break;
            
        case 'subject':
            // Delete notes for this subject first
            $pdo->prepare("DELETE FROM notes WHERE subject_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM subjects WHERE id = ?")->execute([$id]);
            break;
    }
}

header('Location: admin_dashboard.php');
exit();
?>
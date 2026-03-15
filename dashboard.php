<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user info
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get user preferences for ADHD mode
$adhd_mode = ($user['accessibility_profile'] == 'adhd' || $user['focus_mode'] == 1);

// Get all branches with counts
$sql = "SELECT b.*, 
               COUNT(DISTINCT s.id) as subject_count,
               COUNT(DISTINCT n.id) as note_count
        FROM branches b
        LEFT JOIN semesters sem ON b.id = sem.branch_id
        LEFT JOIN subjects s ON sem.id = s.semester_id
        LEFT JOIN notes n ON s.id = n.subject_id
        GROUP BY b.id
        ORDER BY b.name";
$branches = $pdo->query($sql)->fetchAll();

// Get statistics
$totalBranches = count($branches);
$totalSubjects = $pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
$totalNotes = $pdo->query("SELECT COUNT(*) FROM notes")->fetchColumn();

// Define colors for cards
$branchColors = [
    'CSE' => ['color' => '#4361ee', 'icon' => '💻', 'light' => '#e0e7ff'],
    'ECE' => ['color' => '#f72585', 'icon' => '📡', 'light' => '#ffe0f0'],
    'ME' => ['color' => '#4cc9f0', 'icon' => '🔧', 'light' => '#e0f7ff'],
    'CE' => ['color' => '#f8961e', 'icon' => '🏗️', 'light' => '#ffe0e0'],
    'ISE' => ['color' => '#9c89b8', 'icon' => '🌐', 'light' => '#f0e0ff'],
    'AIML' => ['color' => '#7209b7', 'icon' => '🤖', 'light' => '#f3e8ff'],
    'AIDS' => ['color' => '#3a0ca3', 'icon' => '📊', 'light' => '#e0e0ff'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - NeuroLearn</title>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/adaptive.css">
    <link rel="stylesheet" href="css/accessibility.css">
    <link rel="stylesheet" href="css/modes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- In the head section of your PHP files -->
<link rel="stylesheet" href="css/voice-agent.css">
<link rel="stylesheet" href="css/reading-timer.css">
    <!-- JavaScript -->
    <script src="js/global-accessibility.js" defer></script>
    <script src="js/theme-loader.js" defer></script>
    
    <style>
        /* ===== GLOBAL VARIABLES ===== */
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --dark: #1e293b;
            --light: #f8f9fa;
            --gray: #64748b;
            --border: #e2e8f0;
            --shadow: 0 2px 4px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
            --radius: 12px;
            --radius-sm: 8px;
            --sidebar-width: 280px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html {
            font-size: 16px;
            scroll-behavior: smooth;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f1f5f9;
            color: var(--dark);
            line-height: 1.5;
            min-width: 320px;
            overflow-x: hidden;
            transition: all 0.3s ease;
        }
        
        /* ===== TEXT SIZE CLASSES ===== */
        body.size-85 { font-size: 0.85rem; }
        body.size-90 { font-size: 0.9rem; }
        body.size-95 { font-size: 0.95rem; }
        body.size-100 { font-size: 1rem; }
        body.size-105 { font-size: 1.05rem; }
        body.size-110 { font-size: 1.1rem; }
        body.size-115 { font-size: 1.15rem; }
        body.size-120 { font-size: 1.2rem; }
        body.size-125 { font-size: 1.25rem; }
        body.size-130 { font-size: 1.3rem; }
        
        /* ===== LEFT SIDEBAR NAVIGATION ===== */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background: #000000;
            color: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
            overflow-y: auto;
            transition: all 0.3s ease;
            padding: 2rem 0;
        }
        
        /* Hide sidebar in ADHD mode */
        body.adhd-mode .sidebar {
            transform: translateX(-100%);
            opacity: 0;
            pointer-events: none;
        }
        
        body.adhd-mode .main-content {
            margin-left: 0;
        }
        
        .sidebar-header {
            padding: 0 1.5rem 2rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .sidebar-header h2 span {
            background: linear-gradient(135deg, #fff, #ccc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .nav-menu {
            list-style: none;
            padding: 1.5rem 0;
        }
        
        .nav-menu li {
            margin: 0.25rem 1rem;
        }
        
        .nav-menu li a {
            display: flex;
            align-items: center;
            padding: 0.875rem 1.5rem;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            border-radius: var(--radius-sm);
            transition: all 0.2s;
            gap: 1rem;
            font-weight: 500;
        }
        
        .nav-menu li a i {
            width: 1.5rem;
            font-size: 1.2rem;
            color: rgba(255,255,255,0.5);
        }
        
        .nav-menu li:hover a {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .nav-menu li:hover a i {
            color: white;
        }
        
        .nav-menu li.active a {
            background: rgba(255,255,255,0.15);
            color: white;
            font-weight: 600;
            border-left: 3px solid white;
        }
        
        .nav-menu li.active a i {
            color: white;
        }
        
        .sidebar-footer {
            padding: 2rem 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: auto;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: rgba(255,255,255,0.05);
            border-radius: var(--radius-sm);
            margin-bottom: 1.5rem;
        }
        
        .user-avatar {
            width: 2.5rem;
            height: 2.5rem;
            background: linear-gradient(135deg, #666, #333);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1rem;
        }
        
        .user-name {
            font-weight: 600;
            color: white;
            margin-bottom: 0.125rem;
            font-size: 0.9rem;
        }
        
        .user-badge {
            font-size: 0.7rem;
            color: rgba(255,255,255,0.7);
            background: rgba(255,255,255,0.1);
            padding: 0.125rem 0.5rem;
            border-radius: 30px;
            display: inline-block;
        }
        
        /* ===== TEXT SIZE CONTROLS (in sidebar) ===== */
        .text-size-controls {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            margin: 1rem 0;
            padding: 0.5rem;
            background: rgba(255,255,255,0.05);
            border-radius: 40px;
        }
        
        .size-btn {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 50%;
            border: none;
            background: rgba(255,255,255,0.1);
            cursor: pointer;
            font-size: 1.2rem;
            font-weight: 600;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .size-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .size-display {
            font-size: 1rem;
            font-weight: 600;
            color: white;
            background: rgba(255,255,255,0.1);
            padding: 0.375rem 0.75rem;
            border-radius: 30px;
            min-width: 3.75rem;
            text-align: center;
        }
        
        /* ===== COLOR BLINDNESS CONTROLS (in sidebar) ===== */
        .section-title {
            font-size: 0.75rem;
            font-weight: 600;
            color: rgba(255,255,255,0.5);
            margin: 1.5rem 0 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .colorblind-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
            margin: 0.75rem 0;
        }
        
        .colorblind-btn {
            padding: 0.6rem 0.5rem;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: var(--radius-sm);
            background: rgba(255,255,255,0.05);
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
            position: relative;
            color: white;
        }
        
        .colorblind-btn:hover {
            border-color: rgba(255,255,255,0.5);
            background: rgba(255,255,255,0.1);
        }
        
        .colorblind-btn.active {
            border-color: white;
            background: rgba(255,255,255,0.2);
        }
        
        .colorblind-btn.active::after {
            content: '✓';
            position: absolute;
            top: 0.25rem;
            right: 0.25rem;
            width: 1rem;
            height: 1rem;
            background: white;
            color: black;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.6rem;
        }
        
        .colorblind-preview {
            width: 100%;
            height: 1.5rem;
            border-radius: 0.25rem;
        }
        
        .colorblind-name {
            font-size: 0.7rem;
            font-weight: 500;
            color: rgba(255,255,255,0.9);
        }
        
        /* ===== MAIN CONTENT ===== */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            min-height: 100vh;
            background: #f1f5f9;
            transition: all 0.3s ease;
        }
        
        .content-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* ===== WELCOME SECTION ===== */
        .welcome-section {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1.5rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-lg);
        }
        
        .welcome-section h1 {
            font-size: 1.8em;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .welcome-section p {
            font-size: 1em;
            opacity: 0.95;
            margin-bottom: 1.25rem;
        }
        
        /* ===== STATS GRID ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: var(--radius-sm);
            padding: 1rem;
            text-align: center;
        }
        
        .stat-icon {
            font-size: 1.8em;
            margin-bottom: 0.5rem;
        }
        
        .stat-number {
            font-size: 1.5em;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            font-size: 0.85em;
            opacity: 0.9;
        }
        
        /* Make stats highlightable */
        .stat-card .stat-number,
        .stat-card .stat-label {
            display: inline-block;
            transition: all 0.2s ease;
            padding: 2px 4px;
            border-radius: 4px;
        }
        
        .stat-card .stat-number:hover,
        .stat-card .stat-label:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.05);
        }
        
        /* ===== SECTION HEADER ===== */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 1.5rem 0 1rem;
        }
        
        .section-header h2 {
            font-size: 1.3em;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .section-header h2 i {
            color: var(--primary);
        }
        
        .view-all-link {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.9em;
            font-weight: 500;
            padding: 0.375rem 0.75rem;
            background: white;
            border-radius: 30px;
            box-shadow: var(--shadow);
            transition: all 0.2s;
        }
        
        .view-all-link:hover {
            background: var(--primary);
            color: white;
        }
        
        /* ===== BRANCHES GRID ===== */
        .branches-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .branch-card {
            background: white;
            border-radius: var(--radius);
            padding: 1rem;
            box-shadow: var(--shadow);
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid transparent;
        }
        
        .branch-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }
        
        .branch-icon {
            font-size: 2.2em;
            margin-bottom: 0.6rem;
            text-align: center;
        }
        
        .branch-name {
            font-size: 1.1em;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.25rem;
            text-align: center;
        }
        
        .branch-code {
            font-size: 0.75em;
            color: var(--primary);
            background: #e0e7ff;
            padding: 0.125rem 0.5rem;
            border-radius: 30px;
            display: inline-block;
            margin: 0 auto 0.6rem;
            text-align: center;
            width: fit-content;
        }
        
        .branch-description {
            font-size: 0.85em;
            color: var(--gray);
            line-height: 1.4;
            margin-bottom: 0.75rem;
            text-align: center;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .branch-stats {
            display: flex;
            gap: 0.5rem;
            border-top: 1px solid var(--border);
            padding-top: 0.75rem;
        }
        
        .branch-stat {
            flex: 1;
            text-align: center;
        }
        
        .branch-stat-value {
            font-size: 1em;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.125rem;
        }
        
        .branch-stat-label {
            font-size: 0.7em;
            color: var(--gray);
        }
        
        /* Make all branch elements highlightable */
        .branch-name, .branch-code, .branch-description, 
        .branch-stat-value, .branch-stat-label {
            display: inline-block;
            transition: all 0.2s ease;
            padding: 2px 4px;
            border-radius: 4px;
        }
        
        .branch-name:hover, .branch-code:hover, .branch-description:hover,
        .branch-stat-value:hover, .branch-stat-label:hover {
            background: rgba(139, 92, 246, 0.2);
            transform: scale(1.02);
            cursor: pointer;
        }
        
        /* ===== AI TUTOR CARD ===== */
        .ai-tutor-card {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: var(--radius);
            padding: 1.5rem;
            margin: 1.5rem 0;
            color: white;
        }
        
        .ai-tutor-content {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .ai-tutor-icon {
            font-size: 3.5em;
        }
        
        .ai-tutor-text {
            flex: 1;
        }
        
        .ai-tutor-text h3 {
            font-size: 1.3em;
            font-weight: 600;
            margin-bottom: 0.375rem;
        }
        
        .ai-tutor-text p {
            font-size: 0.9em;
            opacity: 0.95;
            margin-bottom: 0.75rem;
        }
        
        .ai-tutor-btn {
            background: white;
            color: var(--primary);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 30px;
            font-size: 0.9em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .ai-tutor-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        /* Make AI tutor text highlightable */
        .ai-tutor-text h3, .ai-tutor-text p {
            display: inline-block;
            transition: all 0.2s ease;
            padding: 2px 4px;
            border-radius: 4px;
        }
        
        .ai-tutor-text h3:hover, .ai-tutor-text p:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.02);
        }
        
        /* ===== FOCUS TUNNEL ===== */
        .focus-tunnel {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 9999;
            display: none;
        }
        
        body.adhd-mode .focus-tunnel.active {
            display: block;
        }
        
        .blur-overlay {
            position: absolute;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            transition: all 0.1s ease;
            z-index: 10000;
        }
        
        .overlay-top {
            top: 0;
            left: 0;
            right: 0;
            height: var(--roller-top);
        }
        
        .overlay-bottom {
            left: 0;
            right: 0;
            height: calc(100vh - var(--roller-bottom));
            top: var(--roller-bottom);
        }
        
        .overlay-left {
            top: var(--roller-top);
            left: 0;
            width: 0;
            height: var(--roller-height);
        }
        
        .overlay-right {
            top: var(--roller-top);
            right: 0;
            width: 0;
            height: var(--roller-height);
        }
        
        .focus-roller {
            position: fixed;
            left: 0;
            right: 0;
            height: 120px;
            background: transparent;
            border-top: 3px solid #8b5cf6;
            border-bottom: 3px solid #8b5cf6;
            pointer-events: none;
            z-index: 10001;
            display: none;
            box-shadow: 0 0 30px rgba(139, 92, 246, 0.5);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { border-color: #8b5cf6; }
            50% { border-color: #667eea; }
        }
        
        body.adhd-mode .focus-roller.active {
            display: block;
        }
        
        .roller-inner {
            position: absolute;
            top: 3px;
            left: 20px;
            right: 20px;
            bottom: 3px;
            background: rgba(139, 92, 246, 0.1);
            border-radius: 10px;
            pointer-events: none;
            box-shadow: inset 0 0 30px rgba(139, 92, 246, 0.3);
        }
        
        .roller-handle {
            position: absolute;
            right: 30px;
            width: 40px;
            height: 40px;
            background: #8b5cf6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            pointer-events: auto;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            transition: all 0.2s;
            z-index: 10002;
        }
        
        .roller-handle:hover {
            transform: scale(1.1);
            background: #7c3aed;
        }
        
        .roller-handle.top {
            top: -20px;
        }
        
        .roller-handle.bottom {
            bottom: -20px;
        }
        
        /* ===== WORD HIGHLIGHTING ===== */
        .highlightable {
            transition: all 0.2s ease;
            position: relative;
            z-index: 1;
            display: inline-block;
            padding: 2px 4px;
            border-radius: 4px;
        }
        
        .highlightable:hover {
            background: rgba(139, 92, 246, 0.3) !important;
            transform: scale(1.02);
            box-shadow: 0 2px 8px rgba(139, 92, 246, 0.4);
            color: #000 !important;
            font-weight: 500;
        }
        
        /* ===== ADHD CONTROL PANEL ===== */
        .adhd-panel {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            z-index: 10003;
            display: <?php echo $adhd_mode ? 'block' : 'none'; ?>;
            width: 300px;
            border: 3px solid #8b5cf6;
        }
        
        .adhd-panel h3 {
            color: #8b5cf6;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
        }
        
        .control-group {
            margin-bottom: 15px;
        }
        
        .control-label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #4b5563;
            font-size: 14px;
        }
        
        .control-btn {
            width: 100%;
            padding: 10px;
            background: #f3f4f6;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-weight: 500;
        }
        
        .control-btn:hover {
            background: #e5e7eb;
        }
        
        .control-btn.active {
            background: #8b5cf6;
            color: white;
            border-color: #8b5cf6;
        }
        
        .slider-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .slider {
            flex: 1;
            height: 6px;
            -webkit-appearance: none;
            background: linear-gradient(90deg, #8b5cf6, #667eea);
            border-radius: 3px;
        }
        
        .slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 20px;
            height: 20px;
            background: white;
            border: 2px solid #8b5cf6;
            border-radius: 50%;
            cursor: pointer;
        }
        
        .value-display {
            min-width: 50px;
            text-align: center;
            font-weight: 600;
            color: #8b5cf6;
        }
        
        /* ===== ADHD STATUS BADGE ===== */
        .adhd-status {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #8b5cf6;
            color: white;
            padding: 10px 20px;
            border-radius: 50px;
            z-index: 10004;
            display: <?php echo $adhd_mode ? 'flex' : 'none'; ?>;
            align-items: center;
            gap: 10px;
            box-shadow: 0 10px 20px rgba(139, 92, 246, 0.4);
            font-weight: 600;
        }
        
        .adhd-status i {
            font-size: 20px;
        }
        
        /* ===== FLOATING ACTION BUTTON ===== */
        .fab-container {
            position: fixed;
            bottom: 30px;
            left: 30px;
            z-index: 10005;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .fab-main {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #8b5cf6, #6366f1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            cursor: pointer;
            box-shadow: 0 10px 25px rgba(139, 92, 246, 0.5);
            transition: all 0.3s ease;
            border: 3px solid white;
            animation: fabPulse 2s infinite;
            position: relative;
        }
        
        .fab-main:hover {
            transform: scale(1.1) rotate(90deg);
            box-shadow: 0 15px 30px rgba(139, 92, 246, 0.7);
        }
        
        @keyframes fabPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .fab-tooltip {
            position: absolute;
            left: 80px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 5px 15px;
            border-radius: 30px;
            font-size: 14px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            pointer-events: none;
        }
        
        .fab-main:hover .fab-tooltip {
            opacity: 1;
            visibility: visible;
            left: 90px;
        }
        
        .fab-menu {
            position: absolute;
            bottom: 80px;
            left: 0;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            width: 280px;
            overflow: hidden;
            display: none;
            animation: slideInUp 0.3s ease;
            border: 2px solid #8b5cf6;
        }
        
        .fab-menu.show {
            display: block;
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fab-menu-header {
            background: linear-gradient(135deg, #8b5cf6, #6366f1);
            color: white;
            padding: 15px 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .fab-menu-header i {
            font-size: 20px;
        }
        
        .fab-menu-items {
            padding: 10px;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .fab-menu-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 15px;
            color: #1f2937;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.2s;
            margin: 5px 0;
        }
        
        .fab-menu-item:hover {
            background: #f3f4f6;
            transform: translateX(5px);
        }
        
        .fab-menu-item i {
            width: 24px;
            font-size: 18px;
            color: #8b5cf6;
        }
        
        .fab-menu-item.active {
            background: #ede9fe;
            color: #8b5cf6;
            font-weight: 600;
        }
        
        .fab-menu-item.active i {
            color: #8b5cf6;
        }
        
        .fab-menu-divider {
            height: 1px;
            background: #e5e7eb;
            margin: 10px 0;
        }
        
        .fab-badge {
            background: #f72585;
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 30px;
            margin-left: auto;
        }
        
        /* Adjust for ADHD mode */
        body.adhd-mode .fab-container {
            bottom: 30px;
            left: 30px;
        }
        
        /* ===== COLOR BLINDNESS FILTERS - FIXED ===== */
        body.protanopia-mode {
            filter: url('#protanopia') !important;
            -webkit-filter: url('#protanopia') !important;
        }
        
        body.deuteranopia-mode {
            filter: url('#deuteranopia') !important;
            -webkit-filter: url('#deuteranopia') !important;
        }
        
        body.tritanopia-mode {
            filter: url('#tritanopia') !important;
            -webkit-filter: url('#tritanopia') !important;
        }
        
        body.achromatopsia-mode {
            filter: grayscale(100%) !important;
            -webkit-filter: grayscale(100%) !important;
        }
        
        body.high-contrast-mode {
            filter: contrast(200%) brightness(120%) !important;
            -webkit-filter: contrast(200%) brightness(120%) !important;
        }
        
        svg {
            position: absolute;
            width: 0;
            height: 0;
        }
        
        /* ===== TOAST NOTIFICATION ===== */
        .toast-message {
            position: fixed;
            bottom: 30px;
            right: 30px;
            padding: 10px 20px;
            background: linear-gradient(135deg, #4361ee, #3f37c9);
            color: white;
            border-radius: 30px;
            box-shadow: 0 10px 25px rgba(67, 97, 238, 0.3);
            z-index: 10000;
            animation: slideInRight 0.3s ease;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOutRight {
            to { transform: translateX(100%); opacity: 0; }
        }
        
        /* ===== VOICE AGENT STYLES ===== */
        .voice-agent-button {
            position: fixed;
            bottom: 120px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #8b5cf6, #6366f1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 10px 25px rgba(139, 92, 246, 0.4);
            z-index: 10005;
            transition: all 0.3s ease;
            animation: voicePulse 2s infinite;
        }

        .voice-agent-button:hover {
            transform: scale(1.1);
            box-shadow: 0 15px 30px rgba(139, 92, 246, 0.6);
        }

        .voice-agent-button .voice-icon {
            color: white;
            font-size: 24px;
        }

        @keyframes voicePulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .voice-agent-panel {
            position: fixed;
            bottom: 200px;
            right: 30px;
            width: 350px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            z-index: 10006;
            display: none;
            overflow: hidden;
            border: 2px solid #8b5cf6;
        }

        .voice-agent-panel.active {
            display: block;
            animation: slideInUp 0.3s ease;
        }

        .voice-panel-header {
            background: linear-gradient(135deg, #8b5cf6, #6366f1);
            color: white;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .voice-panel-header h3 {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .voice-close-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .voice-close-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        .voice-panel-content {
            padding: 20px;
        }

        .voice-control-group {
            margin-bottom: 20px;
        }

        .voice-control-group label {
            display: block;
            font-weight: 600;
            color: #4b5563;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .voice-select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            color: #1f2937;
            background: white;
            cursor: pointer;
        }

        .voice-select:focus {
            outline: none;
            border-color: #8b5cf6;
        }

        .voice-slider-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .voice-slider-container input[type="range"] {
            flex: 1;
            height: 6px;
            -webkit-appearance: none;
            background: linear-gradient(90deg, #8b5cf6, #6366f1);
            border-radius: 3px;
        }

        .voice-slider-container input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 20px;
            height: 20px;
            background: white;
            border: 2px solid #8b5cf6;
            border-radius: 50%;
            cursor: pointer;
        }

        .voice-slider-container span {
            font-size: 12px;
            color: #6b7280;
            min-width: 35px;
        }

        .voice-value-display {
            text-align: center;
            font-weight: 600;
            color: #8b5cf6;
            margin-top: 5px;
            font-size: 14px;
        }

        .voice-control-buttons {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 15px;
        }

        .voice-btn {
            padding: 10px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
            font-size: 14px;
        }

        .voice-btn-primary {
            background: linear-gradient(135deg, #8b5cf6, #6366f1);
            color: white;
        }

        .voice-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139, 92, 246, 0.4);
        }

        .voice-btn-secondary {
            background: #f3f4f6;
            color: #4b5563;
            border: 1px solid #e5e7eb;
        }

        .voice-btn-secondary:hover {
            background: #e5e7eb;
        }

        .voice-btn-block {
            width: 100%;
            margin: 15px 0;
        }

        .voice-options {
            margin: 15px 0;
            padding: 10px;
            background: #f9fafb;
            border-radius: 10px;
        }

        .voice-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            cursor: pointer;
            font-size: 14px;
            color: #4b5563;
        }

        .voice-checkbox input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        .voice-status {
            padding: 10px;
            background: #f3f4f6;
            border-radius: 10px;
            font-size: 13px;
            color: #4b5563;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Adjust for ADHD mode */
        body.adhd-mode .voice-agent-button {
            bottom: 120px;
            right: 20px;
        }

        body.adhd-mode .voice-agent-panel {
            bottom: 200px;
            right: 20px;
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            .voice-agent-panel {
                width: 300px;
                right: 15px;
                bottom: 150px;
            }
            
            .voice-agent-button {
                bottom: 80px;
                right: 15px;
            }
            
            .fab-menu {
                width: 250px;
                left: 0;
            }
            
            .fab-main {
                width: 60px;
                height: 60px;
                font-size: 24px;
            }
        }

        /* High contrast mode support */
        body.high-contrast-mode .voice-agent-button,
        body.high-contrast-mode .fab-main {
            background: #000;
            border: 3px solid #ff0;
        }

        body.high-contrast-mode .voice-agent-panel,
        body.high-contrast-mode .fab-menu {
            border-color: #ff0;
        }

        body.high-contrast-mode .voice-panel-header,
        body.high-contrast-mode .fab-menu-header {
            background: #000;
            border-bottom: 2px solid #ff0;
        }
        
        /* ===== READING TIMER STYLES ===== */
        .reading-progress {
            position: fixed;
            top: 0;
            left: 0;
            width: 0%;
            height: 3px;
            background: linear-gradient(90deg, #f59e0b, #fbbf24);
            z-index: 10008;
            transition: width 0.3s ease;
        }

        .nudge-container {
            position: fixed;
            bottom: 30px;
            left: 100px;
            width: 350px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            z-index: 10007;
            display: none;
            animation: nudgeSlideIn 0.3s ease;
            border-left: 4px solid #f59e0b;
            overflow: hidden;
        }

        .nudge-container.show {
            display: block;
        }

        @keyframes nudgeSlideIn {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .nudge-content {
            padding: 20px;
            position: relative;
        }

        .nudge-icon {
            width: 40px;
            height: 40px;
            background: #fef3c7;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
        }

        .nudge-icon i {
            font-size: 20px;
            color: #f59e0b;
        }

        .nudge-message {
            font-size: 15px;
            color: #1f2937;
            margin-bottom: 15px;
            line-height: 1.5;
            font-weight: 500;
        }

        .nudge-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .nudge-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .nudge-btn-primary {
            background: #f59e0b;
            color: white;
        }

        .nudge-btn-primary:hover {
            background: #d97706;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(245, 158, 11, 0.3);
        }

        .nudge-btn-secondary {
            background: #f3f4f6;
            color: #4b5563;
        }

        .nudge-btn-secondary:hover {
            background: #e5e7eb;
        }

        .nudge-btn-link {
            background: transparent;
            color: #6b7280;
            text-decoration: underline;
            padding: 8px 0;
        }

        .nudge-btn-link:hover {
            color: #4b5563;
        }

        .nudge-timer {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #6b7280;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
        }

        .nudge-timer i {
            color: #f59e0b;
        }

        .simplified-text {
            background-color: #fef3c7;
            padding: 15px;
            border-radius: 12px;
            border-left: 4px solid #f59e0b;
            margin: 15px 0;
            animation: highlightPulse 2s ease;
        }

        @keyframes highlightPulse {
            0%, 100% { background-color: #fef3c7; }
            50% { background-color: #ffedd5; }
        }

        .simplified-badge {
            display: inline-block;
            background: #f59e0b;
            color: white;
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 30px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .original-text-toggle {
            margin-top: 10px;
            font-size: 12px;
            color: #f59e0b;
            cursor: pointer;
            text-decoration: underline;
        }

        /* ADHD mode adjustments */
        body.adhd-mode .nudge-container {
            bottom: 200px;
            left: 100px;
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            .nudge-container {
                width: calc(100% - 40px);
                left: 60px;
                bottom: 20px;
            }
            
            .nudge-actions {
                flex-direction: column;
            }
            
            .nudge-btn {
                width: 100%;
                justify-content: center;
            }
        }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .branches-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                left: -100%;
                transition: left 0.3s ease;
            }
            
            .sidebar.open {
                left: 0;
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .stats-grid,
            .branches-grid {
                grid-template-columns: 1fr;
            }
            .ai-tutor-content {
                flex-direction: column;
                text-align: center;
            }
            .adhd-panel {
                width: 90%;
                left: 5%;
                right: 5%;
            }
        }
    </style>
</head>
<body data-profile="<?php echo $_SESSION['accessibility']['profile'] ?? 'standard'; ?>"
      data-text-size="<?php echo $_SESSION['accessibility']['text_size'] ?? 100; ?>"
      data-high-contrast="<?php echo $_SESSION['accessibility']['high_contrast'] ?? 0; ?>"
      data-focus-mode="<?php echo $_SESSION['accessibility']['focus_mode'] ?? 0; ?>"
      data-theme="<?php echo $_SESSION['accessibility']['theme_mode'] ?? 'light'; ?>"
      class="<?php echo $adhd_mode ? 'adhd-mode' : ''; ?> size-100" id="mainBody">
    
    <!-- Focus Tunnel - Creates blur outside roller -->
    <div class="focus-tunnel" id="focusTunnel">
        <div class="blur-overlay overlay-top" id="overlayTop"></div>
        <div class="blur-overlay overlay-bottom" id="overlayBottom"></div>
        <div class="blur-overlay overlay-left" id="overlayLeft"></div>
        <div class="blur-overlay overlay-right" id="overlayRight"></div>
    </div>
    
    <!-- Focus Roller - The clear window -->
    <div class="focus-roller" id="focusRoller">
        <div class="roller-inner"></div>
        <div class="roller-handle top" onclick="adjustRollerHeight(-20)" title="Decrease Height">
            <i class="fas fa-compress-alt"></i>
        </div>
        <div class="roller-handle bottom" onclick="adjustRollerHeight(20)" title="Increase Height">
            <i class="fas fa-expand-alt"></i>
        </div>
    </div>
    
    <!-- ADHD Status Badge -->
    <div class="adhd-status" id="adhdStatus">
        <i class="fas fa-brain"></i>
        <span>ADHD Focus Mode</span>
        <button onclick="toggleFocusMode()" style="background: rgba(255,255,255,0.2); border: none; color: white; padding: 5px 10px; border-radius: 30px; cursor: pointer; margin-left: 10px;">
            <i class="fas fa-power-off"></i>
        </button>
    </div>
    
    <!-- Floating Action Button -->
    <div class="fab-container">
        <div class="fab-main" id="fabMain" onclick="toggleFabMenu()">
            <i class="fas fa-compass"></i>
            <span class="fab-tooltip">Quick Navigation</span>
        </div>
        
        <div class="fab-menu" id="fabMenu">
            <div class="fab-menu-header">
                <i class="fas fa-compass"></i>
                <span>Quick Navigation</span>
            </div>
            <div class="fab-menu-items">
                <a href="dashboard.php" class="fab-menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="branches.php" class="fab-menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'branches.php' ? 'active' : ''; ?>">
                    <i class="fas fa-book-open"></i>
                    <span>Branches</span>
                </a>
                <a href="pdf-analyzer.php" class="fab-menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'pdf-analyzer.php' ? 'active' : ''; ?>">
                    <i class="fas fa-file-pdf"></i>
                    <span>PDF Analyzer</span>
                    <span class="fab-badge">New</span>
                </a>
                <a href="my_notes.php" class="fab-menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'my_notes.php' ? 'active' : ''; ?>">
                    <i class="fas fa-sticky-note"></i>
                    <span>My Notes</span>
                </a>
                <a href="chat.php" class="fab-menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'chat.php' ? 'active' : ''; ?>">
                    <i class="fas fa-robot"></i>
                    <span>AI Tutor</span>
                </a>
                <div class="fab-menu-divider"></div>
                <a href="settings.php" class="fab-menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <a href="logout.php" class="fab-menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
    
    <!-- ADHD Control Panel -->
    <div class="adhd-panel" id="adhdPanel">
        <h3><i class="fas fa-brain"></i> ADHD Focus Controls</h3>
        
        <div class="control-group">
            <label class="control-label"><i class="fas fa-ruler"></i> Roller Height</label>
            <div class="slider-container">
                <input type="range" class="slider" id="rollerHeightSlider" min="60" max="300" value="120" oninput="updateRollerHeight(this.value)">
                <span class="value-display" id="heightValue">120px</span>
            </div>
        </div>
        
        <div class="control-group">
            <label class="control-label"><i class="fas fa-eye"></i> Blur Intensity</label>
            <div class="slider-container">
                <input type="range" class="slider" id="blurIntensity" min="3" max="15" value="8" oninput="updateBlurIntensity(this.value)">
                <span class="value-display" id="blurValue">8px</span>
            </div>
        </div>
        
        <div class="control-group">
            <label class="control-label"><i class="fas fa-palette"></i> Highlight Color</label>
            <div style="display: flex; gap: 10px;">
                <button class="control-btn" style="background: #8b5cf6; color: white;" onclick="setHighlightColor('#8b5cf6')">Purple</button>
                <button class="control-btn" style="background: #3b82f6; color: white;" onclick="setHighlightColor('#3b82f6')">Blue</button>
                <button class="control-btn" style="background: #10b981; color: white;" onclick="setHighlightColor('#10b981')">Green</button>
            </div>
        </div>
        
        <div class="control-group">
            <button class="control-btn" onclick="resetRoller()">
                <i class="fas fa-undo"></i> Reset Position
            </button>
        </div>
        
        <div class="control-group">
            <button class="control-btn" onclick="toggleFocusMode()">
                <i class="fas fa-power-off"></i> Turn Off Focus Mode
            </button>
        </div>
        
        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e5e7eb; text-align: center; color: #6b7280; font-size: 12px;">
            <i class="fas fa-info-circle"></i> Only content inside the roller is fully visible
        </div>
    </div>

    <!-- SVG Filters for Color Blindness -->
    <svg>
        <filter id="protanopia">
            <feColorMatrix type="matrix" values="0.567,0.433,0,0,0 0.558,0.442,0,0,0 0,0.242,0.758,0,0 0,0,0,1,0"/>
        </filter>
        <filter id="deuteranopia">
            <feColorMatrix type="matrix" values="0.625,0.375,0,0,0 0.7,0.3,0,0,0 0,0.3,0.7,0,0 0,0,0,1,0"/>
        </filter>
        <filter id="tritanopia">
            <feColorMatrix type="matrix" values="0.95,0.05,0,0,0 0,0.433,0.567,0,0 0,0.475,0.525,0,0 0,0,0,1,0"/>
        </filter>
    </svg>

    <!-- Left Sidebar Navigation -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2><span>🧠 NeuroLearn</span></h2>
        </div>
        
       <ul class="nav-menu">
    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
        <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    </li>
    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'branches.php' ? 'active' : ''; ?>">
        <a href="branches.php"><i class="fas fa-book-open"></i> Branches</a>
    </li>
    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'pdf-analyzer.php' ? 'active' : ''; ?>">
        <a href="pdf-analyzer.php"><i class="fas fa-file-pdf"></i> PDF Analyzer</a>
    </li>
    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'my_notes.php' ? 'active' : ''; ?>">
        <a href="my_notes.php"><i class="fas fa-sticky-note"></i> My Notes</a>
    </li>
    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'chat.php' ? 'active' : ''; ?>">
        <a href="chat.php"><i class="fas fa-robot"></i> AI Tutor</a>
    </li>
    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
        <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
    </li>
    <li>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </li>
</ul>
        
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar"><?php echo strtoupper(substr($user['full_name'] ?? $user['username'], 0, 2)); ?></div>
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></div>
                    <div class="user-badge">
                        <?php 
                        $profile_display = [
                            'standard' => '📚 Standard',
                            'dyslexia' => '🔤 Dyslexia',
                            'adhd' => '🎯 ADHD'
                        ];
                        echo $profile_display[$_SESSION['accessibility']['profile'] ?? 'standard'];
                        ?>
                    </div>
                </div>
            </div>
            
            <!-- Text Size Controls -->
            <div class="section-title">📏 TEXT SIZE</div>
            <div class="text-size-controls">
                <button class="size-btn" onclick="changeTextSize(-5)">−</button>
                <span class="size-display" id="sizeDisplay">100%</span>
                <button class="size-btn" onclick="changeTextSize(5)">+</button>
            </div>
            
            <!-- Color Blindness Modes -->
            <div class="section-title">🌈 COLOR VISION</div>
            <div class="colorblind-grid">
                <button class="colorblind-btn" onclick="setColorBlindMode('none')" id="mode-none">
                    <div class="colorblind-preview" style="background: linear-gradient(90deg, #4361ee, #3f37c9);"></div>
                    <span class="colorblind-name">Normal</span>
                </button>
                <button class="colorblind-btn" onclick="setColorBlindMode('protanopia')" id="mode-protanopia">
                    <div class="colorblind-preview" style="background: linear-gradient(90deg, #8B6B4D, #6B4D8B);"></div>
                    <span class="colorblind-name">Protanopia</span>
                </button>
                <button class="colorblind-btn" onclick="setColorBlindMode('deuteranopia')" id="mode-deuteranopia">
                    <div class="colorblind-preview" style="background: linear-gradient(90deg, #8B6B4D, #6B8B4D);"></div>
                    <span class="colorblind-name">Deuteranopia</span>
                </button>
                <button class="colorblind-btn" onclick="setColorBlindMode('tritanopia')" id="mode-tritanopia">
                    <div class="colorblind-preview" style="background: linear-gradient(90deg, #FF8C69, #69FF8C);"></div>
                    <span class="colorblind-name">Tritanopia</span>
                </button>
                <button class="colorblind-btn" onclick="setColorBlindMode('achromatopsia')" id="mode-achromatopsia">
                    <div class="colorblind-preview" style="background: linear-gradient(90deg, #888, #555);"></div>
                    <span class="colorblind-name">Monochrome</span>
                </button>
                <button class="colorblind-btn" onclick="setColorBlindMode('high-contrast')" id="mode-high-contrast">
                    <div class="colorblind-preview" style="background: linear-gradient(90deg, #000, #FF0);"></div>
                    <span class="colorblind-name">High Contrast</span>
                </button>
            </div>
            
            <!-- Mobile menu toggle -->
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()" style="display: none; background: rgba(255,255,255,0.1); border: none; color: white; padding: 10px; border-radius: 5px; width: 100%; margin-top: 1rem;">
                <i class="fas fa-bars"></i> Menu
            </button>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="content-container">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <h1 class="highlightable">Welcome back, <?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?>! 👋</h1>
                <p class="highlightable"><?php echo $adhd_mode ? '🎯 Focus Mode Active - Move mouse to control the focus roller. Click the <i class="fas fa-compass"></i> button for navigation.' : 'Your personalized engineering learning platform'; ?></p>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📚</div>
                        <div class="stat-number highlightable"><?php echo $totalBranches; ?></div>
                        <div class="stat-label highlightable">Branches</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">📖</div>
                        <div class="stat-number highlightable"><?php echo $totalSubjects ?: 0; ?></div>
                        <div class="stat-label highlightable">Subjects</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">📝</div>
                        <div class="stat-number highlightable"><?php echo $totalNotes ?: 0; ?></div>
                        <div class="stat-label highlightable">Notes</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🎯</div>
                        <div class="stat-number highlightable">24/7</div>
                        <div class="stat-label highlightable">AI Tutor</div>
                    </div>
                </div>
            </div>

            <!-- Engineering Branches Section -->
            <div class="section-header">
                <h2><i class="fas fa-book-open"></i> <span class="highlightable">Engineering Branches</span></h2>
                <a href="branches.php" class="view-all-link highlightable">View All →</a>
            </div>

            <div class="branches-grid">
                <?php foreach(array_slice($branches, 0, 6) as $index => $branch): 
                    $code = $branch['code'];
                    $icon = $branchColors[$code]['icon'] ?? '📚';
                ?>
                <div class="branch-card" onclick="window.location.href='branch.php?id=<?php echo $branch['id']; ?>'">
                    <div class="branch-icon"><?php echo $icon; ?></div>
                    <div class="branch-name highlightable"><?php echo htmlspecialchars($branch['name']); ?></div>
                    <div class="branch-code highlightable"><?php echo htmlspecialchars($branch['code']); ?></div>
                    <div class="branch-description highlightable"><?php echo htmlspecialchars(substr($branch['description'] ?? 'Engineering branch', 0, 60)) . '...'; ?></div>
                    <div class="branch-stats">
                        <div class="branch-stat">
                            <div class="branch-stat-value highlightable"><?php echo $branch['subject_count'] ?? 0; ?></div>
                            <div class="branch-stat-label highlightable">Subjects</div>
                        </div>
                        <div class="branch-stat">
                            <div class="branch-stat-value highlightable">8</div>
                            <div class="branch-stat-label highlightable">Semesters</div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- AI Tutor Card -->
            <div class="ai-tutor-card">
                <div class="ai-tutor-content">
                    <div class="ai-tutor-icon">🤖</div>
                    <div class="ai-tutor-text">
                        <h3 class="highlightable">AI Learning Assistant</h3>
                        <p class="highlightable">Get instant help with engineering concepts, personalized explanations, and 24/7 support.</p>
                        <button class="ai-tutor-btn highlightable" onclick="window.location.href='chat.php'">
                            <i class="fas fa-robot"></i> Start Learning
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
    // ===== TEXT SIZE MANAGEMENT =====
    let currentSize = 100;
    const MIN_SIZE = 85;
    const MAX_SIZE = 130;
    const STEP = 5;
    let currentColorBlindMode = 'none';
    
    // ===== ADHD FOCUS TUNNEL =====
    let rollerActive = <?php echo $adhd_mode ? 'true' : 'false'; ?>;
    let rollerHeight = 120;
    let rollerTop = window.innerHeight / 2 - rollerHeight / 2;
    let blurIntensity = 8;
    let highlightColor = '#8b5cf6';
    
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Dashboard loaded - Text size controls ready');
        
        // Load saved text size from localStorage
        const savedSize = localStorage.getItem('accessibility_text_size');
        if (savedSize) {
            currentSize = parseInt(savedSize);
            applyTextSize(currentSize);
        }
        
        // Load saved color blind mode
        const savedMode = localStorage.getItem('colorblind_mode');
        if (savedMode) {
            setColorBlindMode(savedMode, false);
        }
        
        updateSizeDisplay();
        
        // Initialize focus tunnel if ADHD mode is active
        if (rollerActive) {
            initFocusTunnel();
        }
        
        // Add highlightable class to all text elements
        document.querySelectorAll('.stat-number, .stat-label, .branch-name, .branch-code, .branch-description, .branch-stat-value, .branch-stat-label, .ai-tutor-text h3, .ai-tutor-text p, .view-all-link').forEach(el => {
            el.classList.add('highlightable');
        });
        
        // Close floating menu when clicking outside
        document.addEventListener('click', function(event) {
            const fabMenu = document.getElementById('fabMenu');
            const fabMain = document.getElementById('fabMain');
            
            if (!fabMain.contains(event.target) && !fabMenu.contains(event.target)) {
                fabMenu.classList.remove('show');
            }
        });
    });
    
    // Mobile menu toggle
    function toggleMobileMenu() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('open');
    }
    
    // Toggle floating action menu
    function toggleFabMenu() {
        const menu = document.getElementById('fabMenu');
        menu.classList.toggle('show');
    }
    
    // ===== TEXT SIZE FUNCTIONS =====
    function changeTextSize(delta) {
        let newSize = currentSize + delta;
        
        if (newSize < MIN_SIZE) {
            showToast('✨ Minimum text size (85%) reached');
            return;
        }
        if (newSize > MAX_SIZE) {
            showToast('✨ Maximum text size (130%) reached');
            return;
        }
        
        currentSize = newSize;
        applyTextSize(currentSize);
        saveTextSize(currentSize);
        showToast(`📏 Text size: ${currentSize}%`);
    }
    
    function applyTextSize(size) {
        const sizeDisplay = document.getElementById('sizeDisplay');
        if (sizeDisplay) sizeDisplay.textContent = size + '%';
        
        document.body.classList.remove(
            'size-85', 'size-90', 'size-95', 'size-100',
            'size-105', 'size-110', 'size-115', 'size-120',
            'size-125', 'size-130'
        );
        
        let sizeClass = 'size-100';
        if (size <= 85) sizeClass = 'size-85';
        else if (size <= 90) sizeClass = 'size-90';
        else if (size <= 95) sizeClass = 'size-95';
        else if (size <= 100) sizeClass = 'size-100';
        else if (size <= 105) sizeClass = 'size-105';
        else if (size <= 110) sizeClass = 'size-110';
        else if (size <= 115) sizeClass = 'size-115';
        else if (size <= 120) sizeClass = 'size-120';
        else if (size <= 125) sizeClass = 'size-125';
        else sizeClass = 'size-130';
        
        document.body.classList.add(sizeClass);
        document.body.dataset.textSize = size;
        localStorage.setItem('accessibility_text_size', size);
    }
    
    function setColorBlindMode(mode, showToastMsg = true) {
        document.body.classList.remove(
            'protanopia-mode', 'deuteranopia-mode', 'tritanopia-mode', 
            'achromatopsia-mode', 'high-contrast-mode'
        );
        
        document.querySelectorAll('.colorblind-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        const selectedBtn = document.getElementById('mode-' + mode);
        if (selectedBtn) selectedBtn.classList.add('active');
        
        if (mode !== 'none') {
            document.body.classList.add(mode + '-mode');
        }
        
        currentColorBlindMode = mode;
        document.body.dataset.colorblindMode = mode;
        localStorage.setItem('colorblind_mode', mode);
        
        if (showToastMsg) {
            const modeNames = {
                'none': '🌈 Normal vision',
                'protanopia': '🔴 Protanopia mode',
                'deuteranopia': '🟢 Deuteranopia mode',
                'tritanopia': '🔵 Tritanopia mode',
                'achromatopsia': '⚪ Monochrome mode',
                'high-contrast': '⚫ High contrast mode'
            };
            showToast(modeNames[mode] + ' activated');
        }
    }
    
    function saveTextSize(size) {
        fetch('save_setting.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ text_size: size })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) console.log('Text size saved to server:', size);
        })
        .catch(error => console.error('Error saving text size:', error));
    }
    
    function showToast(message) {
        const existingToast = document.querySelector('.toast-message');
        if (existingToast) existingToast.remove();
        
        const toast = document.createElement('div');
        toast.className = 'toast-message';
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 2000);
    }
    
    function updateSizeDisplay() {
        const sizeDisplay = document.getElementById('sizeDisplay');
        if (sizeDisplay) sizeDisplay.textContent = currentSize + '%';
    }
    
    // ===== ADHD FOCUS FUNCTIONS =====
    function initFocusTunnel() {
        const roller = document.getElementById('focusRoller');
        const tunnel = document.getElementById('focusTunnel');
        
        roller.style.top = rollerTop + 'px';
        roller.style.height = rollerHeight + 'px';
        
        roller.classList.add('active');
        tunnel.classList.add('active');
        
        updateOverlays();
    }
    
    function updateOverlays() {
        document.documentElement.style.setProperty('--roller-top', rollerTop + 'px');
        document.documentElement.style.setProperty('--roller-bottom', (rollerTop + rollerHeight) + 'px');
        document.documentElement.style.setProperty('--roller-height', rollerHeight + 'px');
        
        document.querySelectorAll('.blur-overlay').forEach(el => {
            el.style.backdropFilter = `blur(${blurIntensity}px)`;
            el.style.backgroundColor = `rgba(0, 0, 0, 0.85)`;
        });
    }
    
    document.addEventListener('mousemove', function(e) {
        if (rollerActive) {
            let newTop = e.clientY - rollerHeight / 2;
            newTop = Math.max(0, Math.min(window.innerHeight - rollerHeight, newTop));
            
            rollerTop = newTop;
            document.getElementById('focusRoller').style.top = rollerTop + 'px';
            updateOverlays();
        }
    });
    
    function adjustRollerHeight(delta) {
        rollerHeight = Math.max(60, Math.min(300, rollerHeight + delta));
        document.getElementById('focusRoller').style.height = rollerHeight + 'px';
        document.getElementById('rollerHeightSlider').value = rollerHeight;
        document.getElementById('heightValue').textContent = rollerHeight + 'px';
        
        rollerTop = Math.min(rollerTop, window.innerHeight - rollerHeight);
        document.getElementById('focusRoller').style.top = rollerTop + 'px';
        
        updateOverlays();
    }
    
    function updateRollerHeight(value) {
        rollerHeight = parseInt(value);
        document.getElementById('focusRoller').style.height = rollerHeight + 'px';
        document.getElementById('heightValue').textContent = rollerHeight + 'px';
        
        rollerTop = Math.min(rollerTop, window.innerHeight - rollerHeight);
        document.getElementById('focusRoller').style.top = rollerTop + 'px';
        
        updateOverlays();
    }
    
    function updateBlurIntensity(value) {
        blurIntensity = parseInt(value);
        document.getElementById('blurValue').textContent = blurIntensity + 'px';
        updateOverlays();
    }
    
    function setHighlightColor(color) {
        highlightColor = color;
        document.getElementById('focusRoller').style.borderColor = color;
        
        const style = document.createElement('style');
        style.textContent = `
            .highlightable:hover {
                background: ${color}30 !important;
                box-shadow: 0 2px 8px ${color}80;
            }
        `;
        document.head.appendChild(style);
    }
    
    function resetRoller() {
        rollerHeight = 120;
        rollerTop = window.innerHeight / 2 - rollerHeight / 2;
        
        document.getElementById('focusRoller').style.height = rollerHeight + 'px';
        document.getElementById('focusRoller').style.top = rollerTop + 'px';
        document.getElementById('rollerHeightSlider').value = rollerHeight;
        document.getElementById('heightValue').textContent = rollerHeight + 'px';
        
        updateOverlays();
    }
    
    function toggleFocusMode() {
        rollerActive = !rollerActive;
        const roller = document.getElementById('focusRoller');
        const tunnel = document.getElementById('focusTunnel');
        const panel = document.getElementById('adhdPanel');
        const status = document.getElementById('adhdStatus');
        const body = document.getElementById('mainBody');
        
        if (rollerActive) {
            roller.classList.add('active');
            tunnel.classList.add('active');
            panel.style.display = 'block';
            status.style.display = 'flex';
            body.classList.add('adhd-mode');
            initFocusTunnel();
        } else {
            roller.classList.remove('active');
            tunnel.classList.remove('active');
            panel.style.display = 'none';
            status.style.display = 'none';
            body.classList.remove('adhd-mode');
        }
    }
    
    window.addEventListener('resize', function() {
        if (rollerActive) {
            rollerTop = Math.min(rollerTop, window.innerHeight - rollerHeight);
            document.getElementById('focusRoller').style.top = rollerTop + 'px';
            updateOverlays();
        }
    });
    
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === '+') { e.preventDefault(); changeTextSize(5); }
        if (e.ctrlKey && e.key === '-') { e.preventDefault(); changeTextSize(-5); }
        if (e.ctrlKey && e.key === '0') { e.preventDefault(); currentSize = 100; applyTextSize(100); saveTextSize(100); showToast('✨ Text size reset to 100%'); }
    });
    </script>
    <script> window.chtlConfig = { chatbotId: "6586829846" } </script>
    <script async data-id="6586829846" id="chtl-script" type="text/javascript" src="https://chatling.ai/js/embed.js"></script>
    
    <!-- Include Reading Timer -->
    <?php include 'includes/reading-timer.php'; ?>
    
    <!-- Voice Agent -->
    <?php include 'includes/voice-agent.php'; ?>

</body>
</html>
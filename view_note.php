<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'] ?? 0;
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get user preferences for accessibility
$stmt = $pdo->prepare("SELECT accessibility_profile, text_size, high_contrast, focus_mode, theme_mode FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_prefs = $stmt->fetch();

// Get note details with related information
$stmt = $pdo->prepare("
    SELECT n.*, s.subject_name, s.subject_code, b.name as branch_name, 
           b.icon as branch_icon, sem.semester_number
    FROM notes n
    JOIN subjects s ON n.subject_id = s.id
    JOIN semesters sem ON s.semester_id = sem.id
    JOIN branches b ON sem.branch_id = b.id
    WHERE n.id = ?
");
$stmt->execute([$id]);
$note = $stmt->fetch();

if (!$note) {
    die("Note not found");
}

// Update view count
$pdo->prepare("UPDATE notes SET view_count = view_count + 1 WHERE id = ?")->execute([$id]);

// Check if ADHD mode is active
$adhd_mode = ($user_prefs['accessibility_profile'] == 'adhd' || $user_prefs['focus_mode'] == 1);

// Get file path
$file_path = $note['file_path'];
$full_path = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file_path);
$file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

// Extract text content for text files
$file_content = '';
if ($file_ext == 'txt' && file_exists($full_path)) {
    $file_content = file_get_contents($full_path);
} elseif ($file_ext == 'pdf') {
    // For PDF, we'll use the iframe approach but with accessibility features
    $file_content = "PDF document - Use the built-in PDF viewer below";
}

// Common keywords to highlight in engineering content
$keywords = [
    'algorithm', 'data structure', 'array', 'linked list', 'tree', 'graph',
    'sorting', 'searching', 'recursion', 'dynamic programming',
    'ohm\'s law', 'voltage', 'current', 'resistance', 'capacitor',
    'thermodynamics', 'enthalpy', 'entropy', 'heat transfer',
    'foundation', 'beam', 'column', 'slab', 'concrete',
    'python', 'java', 'c++', 'javascript', 'html', 'css',
    'machine learning', 'artificial intelligence', 'neural network',
    'database', 'sql', 'query', 'normalization',
    'operating system', 'process', 'thread', 'memory management'
];

// Function to highlight keywords in text
function highlightKeywords($text, $keywords) {
    foreach ($keywords as $keyword) {
        $pattern = '/\b(' . preg_quote($keyword, '/') . ')\b/i';
        $text = preg_replace($pattern, '<span class="highlighted-keyword">$1</span>', $text);
    }
    return $text;
}

// Apply highlighting to text content
if ($file_ext == 'txt' && $file_content) {
    $highlighted_content = highlightKeywords(htmlspecialchars($file_content), $keywords);
    $display_content = nl2br($highlighted_content);
} else {
    $display_content = $file_content;
}

// Set theme class
$theme_class = '';
if ($user_prefs['theme_mode'] == 'dark') {
    $theme_class = 'theme-dark';
} elseif ($user_prefs['theme_mode'] == 'eye-comfort') {
    $theme_class = 'theme-eye-comfort';
} else {
    $theme_class = 'theme-light';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($note['title']); ?> - NeuroLearn Viewer</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/adaptive.css">
    <link rel="stylesheet" href="css/accessibility.css">
    <link rel="stylesheet" href="css/modes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Dyslexic&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: <?php echo ($user_prefs['accessibility_profile'] == 'dyslexia') ? "'Open Dyslexic', 'Comic Sans MS', sans-serif" : "'Inter', -apple-system, BlinkMacSystemFont, sans-serif"; ?>;
            min-height: 100vh;
            transition: all 0.3s ease;
            line-height: <?php echo ($user_prefs['accessibility_profile'] == 'dyslexia') ? '1.8' : '1.6'; ?>;
            letter-spacing: <?php echo ($user_prefs['accessibility_profile'] == 'dyslexia') ? '0.12ch' : 'normal'; ?>;
            font-size: <?php echo ($user_prefs['text_size'] ?? 100); ?>%;
        }

        /* ===== THEME MODES ===== */
        /* Light Theme (Default) */
        body.theme-light {
            background: #f1f5f9;
            color: #1e293b;
        }
        
        body.theme-light .toolbar {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }
        
        body.theme-light .note-content-container {
            background: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        body.theme-light .note-header {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border-left-color: #8b5cf6;
        }

        /* Dark Theme */
        body.theme-dark {
            background: #1a1a1a;
            color: #e0e0e0;
        }
        
        body.theme-dark .toolbar {
            background: linear-gradient(135deg, #2d3748, #1a202c);
        }
        
        body.theme-dark .note-content-container {
            background: #2d2d2d;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            color: #e0e0e0;
            border: 1px solid #404040;
        }
        
        body.theme-dark .note-header {
            background: #2d2d2d;
            border-left-color: #8b5cf6;
            color: #e0e0e0;
        }
        
        body.theme-dark .note-title {
            color: #e0e0e0;
        }
        
        body.theme-dark .note-meta {
            color: #a0a0a0;
        }
        
        body.theme-dark .highlighted-keyword {
            background: #5b4a1a;
            color: #ffd700;
            border-bottom-color: #ffa500;
        }
        
        body.theme-dark .accessibility-panel,
        body.theme-dark .voice-agent-panel,
        body.theme-dark .fab-menu {
            background: #2d2d2d;
            color: #e0e0e0;
            border-color: #8b5cf6;
        }
        
        body.theme-dark .panel-header,
        body.theme-dark .voice-panel-header {
            background: linear-gradient(135deg, #2d3748, #1a202c);
        }
        
        body.theme-dark .control-btn {
            background: #3d3d3d;
            color: #e0e0e0;
            border-color: #505050;
        }
        
        body.theme-dark .control-btn:hover {
            background: #4d4d4d;
        }

        /* Eye Comfort Theme */
        body.theme-eye-comfort {
            background: #fbf7e9;
            color: #5b4636;
        }
        
        body.theme-eye-comfort .toolbar {
            background: linear-gradient(135deg, #a1887f, #8d6e63);
        }
        
        body.theme-eye-comfort .note-content-container {
            background: #fff9e6;
            box-shadow: 0 10px 30px rgba(139, 69, 19, 0.1);
            color: #5b4636;
            border: 1px solid #e6d5b8;
        }
        
        body.theme-eye-comfort .note-header {
            background: #fff3d6;
            border-left-color: #8b4513;
            color: #5b4636;
        }
        
        body.theme-eye-comfort .note-title {
            color: #5b4636;
        }
        
        body.theme-eye-comfort .note-meta {
            color: #8b6b4d;
        }
        
        body.theme-eye-comfort .highlighted-keyword {
            background: #ffd966;
            color: #5a3e1b;
            border-bottom-color: #b45309;
        }
        
        body.theme-eye-comfort .accessibility-panel,
        body.theme-eye-comfort .voice-agent-panel,
        body.theme-eye-comfort .fab-menu {
            background: #fff9e6;
            color: #5b4636;
            border-color: #8b4513;
        }
        
        body.theme-eye-comfort .panel-header,
        body.theme-eye-comfort .voice-panel-header {
            background: linear-gradient(135deg, #a1887f, #8d6e63);
        }
        
        body.theme-eye-comfort .control-btn {
            background: #fff3d6;
            color: #5b4636;
            border-color: #e6d5b8;
        }

        /* Dyslexia mode specific styles */
        body.dyslexia-mode {
            font-family: 'Open Dyslexic', 'Comic Sans MS', sans-serif !important;
            line-height: 1.8 !important;
            letter-spacing: 0.12ch !important;
            word-spacing: 0.16ch !important;
        }

        body.dyslexia-mode.theme-light {
            background: #fef9e7;
        }

        body.dyslexia-mode.theme-eye-comfort {
            background: #fdf5e6;
        }

        body.dyslexia-mode p,
        body.dyslexia-mode .note-content {
            font-size: 1.2em;
        }

        /* High contrast mode */
        body.high-contrast-mode {
            background: #000 !important;
            color: #ff0 !important;
        }

        body.high-contrast-mode .toolbar,
        body.high-contrast-mode .sidebar,
        body.high-contrast-mode .note-content-container,
        body.high-contrast-mode .note-header {
            background: #000 !important;
            border-color: #ff0 !important;
            color: #ff0 !important;
        }

        body.high-contrast-mode .toolbar a,
        body.high-contrast-mode .toolbar button {
            border: 2px solid #ff0 !important;
            color: #ff0 !important;
        }

        /* ===== TOOLBAR ===== */
        .toolbar {
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: relative;
            z-index: 100;
            color: white;
        }

        .toolbar-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .toolbar-left a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            background: rgba(255,255,255,0.2);
            border-radius: 5px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .toolbar-left a:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.05);
        }

        .note-info {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
        }

        .note-info span {
            background: rgba(255,255,255,0.15);
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 14px;
        }

        .toolbar-right {
            display: flex;
            gap: 10px;
        }

        .toolbar-btn {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 5px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            border: none;
            font-size: 14px;
        }

        .btn-download {
            background: #10b981;
        }

        .btn-download:hover {
            background: #059669;
            transform: scale(1.05);
        }

        .btn-accessibility {
            background: #8b5cf6;
        }

        .btn-accessibility:hover {
            background: #7c3aed;
            transform: scale(1.05);
        }

        /* ===== ACCESSIBILITY PANEL ===== */
        .accessibility-panel {
            position: fixed;
            top: 80px;
            right: 20px;
            width: 320px;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            z-index: 1000;
            display: none;
            overflow: hidden;
            border: 2px solid #8b5cf6;
        }

        .accessibility-panel.show {
            display: block;
            animation: slideInRight 0.3s ease;
        }

        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .panel-header {
            background: linear-gradient(135deg, #8b5cf6, #6366f1);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .panel-header h3 {
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
        }

        .panel-content {
            padding: 20px;
            background: inherit;
        }

        .control-group {
            margin-bottom: 20px;
        }

        .control-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .control-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .control-btn {
            flex: 1;
            min-width: 80px;
            padding: 8px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .control-btn:hover {
            border-color: #8b5cf6;
            background: #f3f4f6;
            transform: scale(1.02);
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
            background: linear-gradient(90deg, #8b5cf6, #6366f1);
            border-radius: 3px;
        }

        .slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 18px;
            height: 18px;
            background: white;
            border: 2px solid #8b5cf6;
            border-radius: 50%;
            cursor: pointer;
        }

        .value-display {
            min-width: 45px;
            text-align: center;
            font-weight: 600;
            color: #8b5cf6;
            font-size: 14px;
        }

        /* Theme selector */
        .theme-selector {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 10px;
        }

        .theme-option {
            text-align: center;
            padding: 10px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 12px;
        }

        .theme-option:hover {
            border-color: #8b5cf6;
            transform: scale(1.05);
        }

        .theme-option.active {
            border-color: #8b5cf6;
            background: #8b5cf6;
            color: white;
        }

        .theme-option.light { background: #f1f5f9; color: #1e293b; }
        .theme-option.dark { background: #1a1a1a; color: #e0e0e0; }
        .theme-option.eye-comfort { background: #fbf7e9; color: #5b4636; }

        /* ===== FOCUS TUNNEL (for ADHD) ===== */
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

        /* ===== CONTENT AREA ===== */
        .content-wrapper {
            padding: 30px;
            min-height: calc(100vh - 70px);
        }

        .note-header {
            max-width: 1200px;
            margin: 0 auto 30px;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #8b5cf6;
        }

        .note-title {
            font-size: 28px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .note-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            font-size: 14px;
        }

        .note-meta i {
            color: #8b5cf6;
            margin-right: 5px;
        }

        /* ===== NOTE CONTENT ===== */
        .note-content-container {
            max-width: 1200px;
            margin: 0 auto;
            border-radius: 16px;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }

        .note-content {
            font-size: 16px;
            line-height: 1.8;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        /* Keyword highlighting */
        .highlighted-keyword {
            padding: 2px 4px;
            border-radius: 4px;
            font-weight: 500;
            border-bottom: 2px solid #eab308;
            transition: all 0.2s;
        }

        .highlighted-keyword:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(234, 179, 8, 0.3);
            cursor: pointer;
        }

        /* ===== VOICE AGENT ===== */
        .voice-agent-button {
            position: fixed;
            bottom: 30px;
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

        @keyframes voicePulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .voice-agent-panel {
            position: fixed;
            bottom: 100px;
            right: 30px;
            width: 320px;
            border-radius: 16px;
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

        @keyframes slideInUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .voice-panel-header {
            background: linear-gradient(135deg, #8b5cf6, #6366f1);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .voice-panel-header h3 {
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .voice-panel-content {
            padding: 20px;
            background: inherit;
        }

        .voice-control-group {
            margin-bottom: 15px;
        }

        .voice-control-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 13px;
        }

        .voice-select {
            width: 100%;
            padding: 8px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 13px;
            background: inherit;
            color: inherit;
        }

        .voice-btn {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 10px;
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
        }

        .voice-btn-secondary:hover {
            background: #e5e7eb;
        }

        .voice-status {
            padding: 10px;
            background: #f3f4f6;
            border-radius: 8px;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ===== PDF VIEWER ===== */
        .pdf-container {
            width: 100%;
            height: calc(100vh - 150px);
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
        }

        .pdf-viewer {
            width: 100%;
            height: 100%;
            border: none;
        }

        /* ===== FLOATING NAVIGATION BUTTON ===== */
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
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #8b5cf6, #6366f1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 10px 25px rgba(139, 92, 246, 0.5);
            transition: all 0.3s ease;
            border: 3px solid white;
            animation: fabPulse 2s infinite;
        }

        .fab-main:hover {
            transform: scale(1.1) rotate(90deg);
        }

        @keyframes fabPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .fab-menu {
            position: absolute;
            bottom: 70px;
            left: 0;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 220px;
            overflow: hidden;
            display: none;
            animation: slideInLeft 0.3s ease;
            border: 2px solid #8b5cf6;
        }

        .fab-menu.show {
            display: block;
        }

        @keyframes slideInLeft {
            from { transform: translateX(-20px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .fab-menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            text-decoration: none;
            transition: all 0.2s;
        }

        .fab-menu-item:hover {
            background: #f3f4f6;
            transform: translateX(5px);
        }

        body.theme-dark .fab-menu-item:hover {
            background: #3d3d3d;
        }

        body.theme-eye-comfort .fab-menu-item:hover {
            background: #fff3d6;
        }

        .fab-menu-item i {
            width: 20px;
            color: #8b5cf6;
        }

        .fab-menu-divider {
            height: 1px;
            background: #e5e7eb;
            margin: 5px 0;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .toolbar {
                flex-direction: column;
                gap: 10px;
            }
            
            .toolbar-left {
                width: 100%;
                justify-content: space-between;
            }
            
            .note-info {
                display: none;
            }
            
            .content-wrapper {
                padding: 15px;
            }
            
            .note-content-container {
                padding: 20px;
            }
            
            .voice-agent-panel,
            .accessibility-panel {
                width: 280px;
                right: 10px;
            }
            
            .fab-main {
                width: 50px;
                height: 50px;
                font-size: 20px;
            }
        }
    </style>
</head>
<body class="<?php 
    echo ($user_prefs['accessibility_profile'] == 'dyslexia' ? 'dyslexia-mode ' : '') . 
         ($user_prefs['high_contrast'] ? 'high-contrast-mode ' : '') . 
         ($adhd_mode ? 'adhd-mode ' : '') . 
         $theme_class; 
?>" id="mainBody" data-text-size="<?php echo $user_prefs['text_size'] ?? 100; ?>">
    
    <!-- Focus Tunnel for ADHD -->
    <div class="focus-tunnel" id="focusTunnel">
        <div class="blur-overlay overlay-top" id="overlayTop"></div>
        <div class="blur-overlay overlay-bottom" id="overlayBottom"></div>
        <div class="blur-overlay overlay-left" id="overlayLeft"></div>
        <div class="blur-overlay overlay-right" id="overlayRight"></div>
    </div>
    
    <!-- Focus Roller -->
    <div class="focus-roller" id="focusRoller">
        <div class="roller-inner"></div>
        <div class="roller-handle top" onclick="adjustRollerHeight(-20)" title="Decrease Height">
            <i class="fas fa-compress-alt"></i>
        </div>
        <div class="roller-handle bottom" onclick="adjustRollerHeight(20)" title="Increase Height">
            <i class="fas fa-expand-alt"></i>
        </div>
    </div>
    
    <!-- Floating Navigation Button -->
    <div class="fab-container">
        <div class="fab-main" onclick="toggleFabMenu()">
            <i class="fas fa-compass"></i>
        </div>
        <div class="fab-menu" id="fabMenu">
            <a href="dashboard.php" class="fab-menu-item">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="branches.php" class="fab-menu-item">
                <i class="fas fa-book-open"></i> Branches
            </a>
            <a href="my_notes.php" class="fab-menu-item">
                <i class="fas fa-sticky-note"></i> My Notes
            </a>
            <a href="pdf-analyzer.php" class="fab-menu-item">
                <i class="fas fa-file-pdf"></i> PDF Analyzer
            </a>
            <a href="ai_tutor.php" class="fab-menu-item">
                <i class="fas fa-robot"></i> AI Tutor
            </a>
            <div class="fab-menu-divider"></div>
            <a href="settings.php" class="fab-menu-item">
                <i class="fas fa-cog"></i> Settings
            </a>
            <a href="logout.php" class="fab-menu-item">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
    
    <!-- Voice Agent Button -->
    <div class="voice-agent-button" onclick="toggleVoicePanel()">
        <i class="fas fa-volume-up"></i>
    </div>
    
    <!-- Voice Agent Panel -->
    <div class="voice-agent-panel" id="voicePanel">
        <div class="voice-panel-header">
            <h3><i class="fas fa-microphone-alt"></i> Voice Reader</h3>
            <button class="close-btn" onclick="toggleVoicePanel()">×</button>
        </div>
        <div class="voice-panel-content">
            <div class="voice-control-group">
                <label>Voice Speed</label>
                <div class="slider-container">
                    <input type="range" class="slider" id="voiceSpeed" min="0.5" max="2" step="0.1" value="1">
                    <span class="value-display" id="speedValue">1x</span>
                </div>
            </div>
            <div class="voice-control-group">
                <label>Voice Pitch</label>
                <div class="slider-container">
                    <input type="range" class="slider" id="voicePitch" min="0.5" max="2" step="0.1" value="1">
                    <span class="value-display" id="pitchValue">1</span>
                </div>
            </div>
            <div class="voice-control-group">
                <label>Select Voice</label>
                <select class="voice-select" id="voiceSelect">
                    <option value="">Default Voice</option>
                </select>
            </div>
            <button class="voice-btn voice-btn-primary" onclick="readNote()">
                <i class="fas fa-play"></i> Read Note
            </button>
            <button class="voice-btn voice-btn-secondary" onclick="stopReading()">
                <i class="fas fa-stop"></i> Stop
            </button>
            <div class="voice-status" id="voiceStatus">
                <i class="fas fa-info-circle"></i> Ready
            </div>
        </div>
    </div>
    
    <!-- Accessibility Panel -->
    <div class="accessibility-panel" id="accessibilityPanel">
        <div class="panel-header">
            <h3><i class="fas fa-universal-access"></i> Accessibility</h3>
            <button class="close-btn" onclick="toggleAccessibilityPanel()">×</button>
        </div>
        <div class="panel-content">
            <div class="control-group">
                <label>📏 Text Size</label>
                <div class="slider-container">
                    <input type="range" class="slider" id="textSizeSlider" min="70" max="200" value="<?php echo $user_prefs['text_size'] ?? 100; ?>">
                    <span class="value-display" id="textSizeValue"><?php echo $user_prefs['text_size'] ?? 100; ?>%</span>
                </div>
            </div>

            <div class="control-group">
                <label>🎨 Theme Mode</label>
                <div class="theme-selector">
                    <div class="theme-option light <?php echo ($user_prefs['theme_mode'] == 'light' || !$user_prefs['theme_mode']) ? 'active' : ''; ?>" onclick="setTheme('light')">
                        <i class="fas fa-sun"></i> Light
                    </div>
                    <div class="theme-option dark <?php echo ($user_prefs['theme_mode'] == 'dark') ? 'active' : ''; ?>" onclick="setTheme('dark')">
                        <i class="fas fa-moon"></i> Dark
                    </div>
                    <div class="theme-option eye-comfort <?php echo ($user_prefs['theme_mode'] == 'eye-comfort') ? 'active' : ''; ?>" onclick="setTheme('eye-comfort')">
                        <i class="fas fa-eye"></i> Comfort
                    </div>
                </div>
            </div>

            <div class="control-group">
                <label>🎯 Focus Mode (ADHD)</label>
                <div class="control-buttons">
                    <button class="control-btn <?php echo $adhd_mode ? 'active' : ''; ?>" onclick="toggleFocusMode()">
                        <i class="fas fa-bullseye"></i> <?php echo $adhd_mode ? 'On' : 'Off'; ?>
                    </button>
                </div>
                <?php if ($adhd_mode): ?>
                <div style="margin-top: 10px; padding: 10px; background: #8b5cf6; color: white; border-radius: 8px; font-size: 12px;">
                    <i class="fas fa-info-circle"></i> Move mouse to control focus roller
                </div>
                <?php endif; ?>
            </div>

            <div class="control-group">
                <label>🔤 Dyslexia Mode</label>
                <div class="control-buttons">
                    <button class="control-btn <?php echo ($user_prefs['accessibility_profile'] == 'dyslexia') ? 'active' : ''; ?>" onclick="toggleDyslexiaMode()">
                        <i class="fas fa-font"></i> <?php echo ($user_prefs['accessibility_profile'] == 'dyslexia') ? 'On' : 'Off'; ?>
                    </button>
                </div>
            </div>

            <div class="control-group">
                <label>⚫ High Contrast</label>
                <div class="control-buttons">
                    <button class="control-btn <?php echo $user_prefs['high_contrast'] ? 'active' : ''; ?>" onclick="toggleHighContrast()">
                        <i class="fas fa-adjust"></i> <?php echo $user_prefs['high_contrast'] ? 'On' : 'Off'; ?>
                    </button>
                </div>
            </div>

            <?php if ($file_ext == 'pdf'): ?>
            <div class="control-group">
                <label>📥 PDF Controls</label>
                <div class="control-buttons">
                    <a href="download_note.php?id=<?php echo $id; ?>" class="control-btn" style="text-decoration: none; color: inherit;">
                        <i class="fas fa-download"></i> Download PDF
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Toolbar -->
    <div class="toolbar">
        <div class="toolbar-left">
            <a href="my_notes.php"><i class="fas fa-arrow-left"></i> My Notes</a>
            <div class="note-info">
                <span><i class="fas fa-book"></i> <?php echo htmlspecialchars($note['subject_name']); ?></span>
                <span><i class="fas fa-code-branch"></i> <?php echo htmlspecialchars($note['branch_name']); ?></span>
                <span><i class="fas fa-layer-group"></i> Sem <?php echo $note['semester_number']; ?></span>
            </div>
        </div>
        <div class="toolbar-right">
            <button class="toolbar-btn btn-accessibility" onclick="toggleAccessibilityPanel()">
                <i class="fas fa-universal-access"></i> Accessibility
            </button>
            <a href="download_note.php?id=<?php echo $id; ?>" class="toolbar-btn btn-download">
                <i class="fas fa-download"></i> Download
            </a>
        </div>
    </div>

    <!-- Content Area -->
    <div class="content-wrapper">
        <div class="note-header">
            <h1 class="note-title">
                <?php echo htmlspecialchars($note['title']); ?>
            </h1>
            <div class="note-meta">
                <span><i class="far fa-calendar"></i> Added: <?php echo date('M d, Y', strtotime($note['created_at'])); ?></span>
                <span><i class="far fa-eye"></i> Views: <?php echo $note['view_count'] + 1; ?></span>
                <span><i class="fas fa-download"></i> Downloads: <?php echo $note['download_count']; ?></span>
                <?php if ($note['note_type']): ?>
                <span><i class="fas fa-tag"></i> Type: <?php echo ucfirst($note['note_type']); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="note-content-container">
            <?php if ($file_ext == 'pdf'): ?>
                <div class="pdf-container">
                    <iframe src="<?php echo $file_path; ?>" class="pdf-viewer"></iframe>
                </div>
                <p style="text-align: center; margin-top: 20px; color: #64748b;">
                    <i class="fas fa-info-circle"></i> 
                    Use the PDF viewer above. You can also download the file.
                </p>
            <?php elseif ($file_ext == 'txt' && $display_content): ?>
                <div class="note-content">
                    <?php echo $display_content; ?>
                </div>
            <?php else: ?>
                <p style="text-align: center;">
                    <i class="fas fa-exclamation-circle"></i> 
                    Content cannot be displayed directly. 
                    <a href="download_note.php?id=<?php echo $id; ?>" style="color: #8b5cf6;">Download file</a>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // ===== ADHD FOCUS TUNNEL =====
        let rollerActive = <?php echo $adhd_mode ? 'true' : 'false'; ?>;
        let rollerHeight = 120;
        let rollerTop = window.innerHeight / 2 - rollerHeight / 2;
        let blurIntensity = 8;
        let highlightColor = '#8b5cf6';
        
        // ===== VOICE AGENT =====
        let synth = window.speechSynthesis;
        let currentUtterance = null;
        let voices = [];
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            if (rollerActive) {
                initFocusTunnel();
            }
            
            // Load voices
            loadVoices();
            
            // Add event listeners for sliders
            document.getElementById('voiceSpeed').addEventListener('input', function(e) {
                document.getElementById('speedValue').textContent = e.target.value + 'x';
            });
            
            document.getElementById('voicePitch').addEventListener('input', function(e) {
                document.getElementById('pitchValue').textContent = e.target.value;
            });
            
            document.getElementById('textSizeSlider').addEventListener('input', function(e) {
                const size = e.target.value;
                document.getElementById('textSizeValue').textContent = size + '%';
                document.body.style.fontSize = size + '%';
            });
            
            // Close menus when clicking outside
            document.addEventListener('click', function(event) {
                const fabMenu = document.getElementById('fabMenu');
                const fabMain = document.querySelector('.fab-main');
                const voicePanel = document.getElementById('voicePanel');
                const voiceBtn = document.querySelector('.voice-agent-button');
                const accessPanel = document.getElementById('accessibilityPanel');
                const accessBtn = document.querySelector('.btn-accessibility');
                
                if (fabMain && !fabMain.contains(event.target) && !fabMenu.contains(event.target)) {
                    fabMenu.classList.remove('show');
                }
                
                if (voiceBtn && !voiceBtn.contains(event.target) && !voicePanel.contains(event.target)) {
                    voicePanel.classList.remove('active');
                }
                
                if (accessBtn && !accessBtn.contains(event.target) && !accessPanel.contains(event.target)) {
                    accessPanel.classList.remove('show');
                }
            });
        });
        
        // ===== FLOATING MENU =====
        function toggleFabMenu() {
            document.getElementById('fabMenu').classList.toggle('show');
        }
        
        // ===== ACCESSIBILITY PANEL =====
        function toggleAccessibilityPanel() {
            document.getElementById('accessibilityPanel').classList.toggle('show');
        }
        
        // ===== THEME MODES =====
        function setTheme(theme) {
            const body = document.body;
            body.classList.remove('theme-light', 'theme-dark', 'theme-eye-comfort');
            body.classList.add('theme-' + theme);
            
            // Update active state in theme selector
            document.querySelectorAll('.theme-option').forEach(opt => {
                opt.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
            
            // Save to localStorage
            localStorage.setItem('theme_mode', theme);
            
            // Save to server via AJAX
            fetch('save_setting.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ theme_mode: theme })
            });
        }
        
        // ===== VOICE PANEL =====
        function toggleVoicePanel() {
            document.getElementById('voicePanel').classList.toggle('active');
        }
        
        function loadVoices() {
            voices = synth.getVoices();
            const voiceSelect = document.getElementById('voiceSelect');
            
            voices.forEach((voice, index) => {
                const option = document.createElement('option');
                option.value = index;
                option.textContent = voice.name + ' (' + voice.lang + ')';
                voiceSelect.appendChild(option);
            });
        }
        
        function readNote() {
            if (synth.speaking) {
                synth.cancel();
            }
            
            let text = '';
            
            <?php if ($file_ext == 'txt'): ?>
                // Get text content from the note
                text = document.querySelector('.note-content')?.innerText || '';
            <?php else: ?>
                // For PDF, read the title and description
                text = "Viewing PDF: <?php echo addslashes($note['title']); ?>. ";
                text += "This is a PDF document. Use the download button to save it.";
            <?php endif; ?>
            
            if (!text) {
                text = "<?php echo addslashes($note['title']); ?>";
            }
            
            const utterance = new SpeechSynthesisUtterance(text);
            
            // Apply settings
            const speed = document.getElementById('voiceSpeed').value;
            const pitch = document.getElementById('voicePitch').value;
            const voiceIndex = document.getElementById('voiceSelect').value;
            
            utterance.rate = parseFloat(speed);
            utterance.pitch = parseFloat(pitch);
            
            if (voices.length > 0 && voiceIndex) {
                utterance.voice = voices[voiceIndex];
            }
            
            utterance.onstart = function() {
                document.getElementById('voiceStatus').innerHTML = '<i class="fas fa-volume-up"></i> Reading...';
            };
            
            utterance.onend = function() {
                document.getElementById('voiceStatus').innerHTML = '<i class="fas fa-info-circle"></i> Finished';
            };
            
            utterance.onerror = function() {
                document.getElementById('voiceStatus').innerHTML = '<i class="fas fa-exclamation-circle"></i> Error';
            };
            
            synth.speak(utterance);
            currentUtterance = utterance;
        }
        
        function stopReading() {
            if (synth.speaking) {
                synth.cancel();
                document.getElementById('voiceStatus').innerHTML = '<i class="fas fa-info-circle"></i> Stopped';
            }
        }
        
        // ===== FOCUS TUNNEL FUNCTIONS =====
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
            
            rollerTop = Math.min(rollerTop, window.innerHeight - rollerHeight);
            document.getElementById('focusRoller').style.top = rollerTop + 'px';
            
            updateOverlays();
        }
        
        function resetRoller() {
            rollerHeight = 120;
            rollerTop = window.innerHeight / 2 - rollerHeight / 2;
            
            document.getElementById('focusRoller').style.height = rollerHeight + 'px';
            document.getElementById('focusRoller').style.top = rollerTop + 'px';
            
            updateOverlays();
        }
        
        function toggleFocusMode() {
            rollerActive = !rollerActive;
            const roller = document.getElementById('focusRoller');
            const tunnel = document.getElementById('focusTunnel');
            const body = document.getElementById('mainBody');
            
            if (rollerActive) {
                roller.classList.add('active');
                tunnel.classList.add('active');
                body.classList.add('adhd-mode');
                initFocusTunnel();
            } else {
                roller.classList.remove('active');
                tunnel.classList.remove('active');
                body.classList.remove('adhd-mode');
            }
            
            // Update button text
            const btn = document.querySelector('.control-btn .fa-bullseye').parentNode;
            btn.innerHTML = rollerActive ? '<i class="fas fa-bullseye"></i> On' : '<i class="fas fa-bullseye"></i> Off';
        }
        
        function toggleDyslexiaMode() {
            document.body.classList.toggle('dyslexia-mode');
            const btn = document.querySelectorAll('.control-btn')[2];
            if (document.body.classList.contains('dyslexia-mode')) {
                btn.innerHTML = '<i class="fas fa-font"></i> On';
            } else {
                btn.innerHTML = '<i class="fas fa-font"></i> Off';
            }
        }
        
        function toggleHighContrast() {
            document.body.classList.toggle('high-contrast-mode');
            const btn = document.querySelectorAll('.control-btn')[3];
            if (document.body.classList.contains('high-contrast-mode')) {
                btn.innerHTML = '<i class="fas fa-adjust"></i> On';
            } else {
                btn.innerHTML = '<i class="fas fa-adjust"></i> Off';
            }
        }
        
        window.addEventListener('resize', function() {
            if (rollerActive) {
                rollerTop = Math.min(rollerTop, window.innerHeight - rollerHeight);
                document.getElementById('focusRoller').style.top = rollerTop + 'px';
                updateOverlays();
            }
        });
    </script>
    
    <!-- Voice Agent and Chatling -->
    <script> window.chtlConfig = { chatbotId: "6586829846" } </script>
    <script async data-id="6586829846" id="chtl-script" type="text/javascript" src="https://chatling.ai/js/embed.js"></script>
</body>
</html>
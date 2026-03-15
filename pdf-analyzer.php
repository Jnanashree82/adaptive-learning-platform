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

// Get user preferences
$accessibility_profile = $_SESSION['accessibility']['profile'] ?? 'standard';
$adhd_mode = ($accessibility_profile == 'adhd' || $user['focus_mode'] == 1);
$text_size = $_SESSION['accessibility']['text_size'] ?? 100;
$color_blind_mode = $_SESSION['accessibility']['color_blind_mode'] ?? 'none';
$focus_mode = $_SESSION['accessibility']['focus_mode'] ?? 0;
$theme_mode = $_SESSION['accessibility']['theme_mode'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Analyzer - NeuroLearn</title>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/adaptive.css">
    <link rel="stylesheet" href="css/accessibility.css">
    <link rel="stylesheet" href="css/modes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=OpenDyslexic:wght@400;700&display=swap" rel="stylesheet">
    
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
            --dyslexia-font: 'OpenDyslexic', 'Inter', sans-serif;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
    }
    
        body {
            font-family: <?php echo ($accessibility_profile == 'dyslexia') ? 'var(--dyslexia-font)' : "'Inter', sans-serif"; ?>;
            background: #f1f5f9;
            color: var(--dark);
            line-height: <?php echo ($accessibility_profile == 'dyslexia') ? '1.8' : '1.6'; ?>;
            letter-spacing: <?php echo ($accessibility_profile == 'dyslexia') ? '0.12ch' : 'normal'; ?>;
            min-width: 320px;
            overflow-x: hidden;
            transition: all 0.3s ease;
        }
        
        /* Dyslexia-friendly styles */
        body.dyslexia-mode {
            font-family: var(--dyslexia-font);
            line-height: 1.8;
            letter-spacing: 0.12ch;
            word-spacing: 0.16ch;
        }
        
        body.dyslexia-mode p,
        body.dyslexia-mode .summary-text,
        body.dyslexia-mode .point-content {
            font-size: 1.2em;
        }
        
        /* Text size classes */
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
        
        .nav-menu li.active a {
            background: rgba(255,255,255,0.15);
            color: white;
            font-weight: 600;
            border-left: 3px solid white;
        }
        
        .sidebar-footer {
            padding: 2rem 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.1);
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
        
        /* ===== TEXT SIZE CONTROLS ===== */
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
        
        /* ===== COLOR BLINDNESS CONTROLS ===== */
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
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* ===== PDF ANALYZER HEADER ===== */
        .analyzer-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 2rem;
            border-radius: var(--radius);
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
        }
        
        .analyzer-header h1 {
            font-size: 2.2em;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .analyzer-header p {
            font-size: 1.1em;
            opacity: 0.95;
            margin-bottom: 1.5rem;
        }
        
        /* ===== ACCESSIBILITY TOOLBAR ===== */
        .accessibility-toolbar {
            background: white;
            border-radius: 50px;
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }
        
        .toolbar-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .toolbar-btn {
            padding: 0.5rem 1rem;
            border: 1px solid var(--border);
            border-radius: 30px;
            background: white;
            color: var(--gray);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
            font-size: 0.9rem;
        }
        
        .toolbar-btn:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .toolbar-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .toolbar-btn.danger {
            background: var(--danger);
            color: white;
            border-color: var(--danger);
        }
        
        .toolbar-btn i {
            font-size: 1rem;
        }
        
        .timer-display {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 30px;
            font-weight: 600;
            font-size: 1.2rem;
            font-family: monospace;
        }
        
        /* ===== UPLOAD SECTION ===== */
        .upload-section {
            background: white;
            border-radius: var(--radius);
            padding: 3rem;
            text-align: center;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border: 3px dashed var(--border);
            transition: all 0.3s ease;
        }
        
        .upload-section.dragover {
            border-color: var(--primary);
            background: #f0f4ff;
        }
        
        .upload-icon {
            font-size: 4rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        
        .upload-section h3 {
            font-size: 1.5rem;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }
        
        .upload-section p {
            color: var(--gray);
            margin-bottom: 1.5rem;
        }
        
        .upload-btn {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 40px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .upload-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(67, 97, 238, 0.3);
        }
        
        .file-info {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: var(--radius-sm);
            display: none;
        }
        
        .file-info.show {
            display: block;
        }
        
        /* ===== ANALYSIS GRID ===== */
        .analysis-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        /* ===== PDF PREVIEW PANEL ===== */
        .preview-panel {
            background: white;
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            height: fit-content;
        }
        
        .preview-panel h2 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--dark);
        }
        
        .pdf-preview {
            background: #f8f9fa;
            border-radius: var(--radius-sm);
            padding: 1rem;
            min-height: 300px;
            max-height: 500px;
            overflow-y: auto;
            position: relative;
        }
        
        .pdf-preview-content {
            line-height: 1.8;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        /* Focus mode for PDF preview */
        body.adhd-mode .pdf-preview-content {
            position: relative;
        }
        
        .focus-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
            z-index: 10;
        }
        
        .focus-roller-pdf {
            position: absolute;
            left: 0;
            right: 0;
            height: 120px;
            background: transparent;
            border-top: 3px solid #8b5cf6;
            border-bottom: 3px solid #8b5cf6;
            pointer-events: none;
            box-shadow: 0 0 30px rgba(139, 92, 246, 0.5);
            transition: top 0.1s ease;
        }
        
        .blur-overlay-pdf {
            position: absolute;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            transition: all 0.1s ease;
        }
        
        /* ===== SUMMARY PANEL ===== */
        .summary-panel {
            background: white;
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
        }
        
        .summary-panel h2 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--dark);
        }
        
        .summary-content {
            background: #f8f9fa;
            border-radius: var(--radius-sm);
            padding: 1.5rem;
            min-height: 300px;
            max-height: 500px;
            overflow-y: auto;
        }
        
        .summary-section {
            margin-bottom: 2rem;
        }
        
        .summary-section h3 {
            font-size: 1.1rem;
            color: var(--primary);
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .point-item {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
            padding: 0.5rem;
            border-radius: var(--radius-sm);
            transition: background 0.2s;
        }
        
        .point-item:hover {
            background: #f0f4ff;
        }
        
        .point-bullet {
            color: var(--primary);
            font-weight: bold;
            min-width: 1.5rem;
        }
        
        .point-content {
            flex: 1;
            line-height: 1.6;
        }
        
        /* Highlighted words */
        .highlight-word {
            background: #fff3bf;
            padding: 2px 4px;
            border-radius: 3px;
            font-weight: 500;
            border-bottom: 2px solid #fcc419;
        }
        
        .highlight-word.important {
            background: #ffe066;
            border-bottom-color: #e67700;
        }
        
        .highlight-word.keyword {
            background: #d0ebff;
            border-bottom-color: #1c7ed6;
        }
        
        .highlight-word.definition {
            background: #d3f9d8;
            border-bottom-color: #2b8a3e;
        }
        
        /* ===== KEYWORDS PANEL ===== */
        .keywords-panel {
            background: white;
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
        }
        
        .keywords-panel h2 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .keywords-cloud {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: var(--radius-sm);
        }
        
        .keyword-tag {
            padding: 0.5rem 1rem;
            background: white;
            border-radius: 30px;
            font-size: 0.9rem;
            color: var(--dark);
            box-shadow: var(--shadow);
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid var(--border);
        }
        
        .keyword-tag:hover {
            transform: scale(1.05);
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .keyword-tag.important {
            background: #fff3bf;
            border-color: #fcc419;
            font-weight: 600;
        }
        
        /* ===== FOCUS MODE CONTROLS ===== */
        .focus-controls {
            background: white;
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
        }
        
        .focus-controls h3 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .control-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        
        .slider-container {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 1;
        }
        
        .slider {
            flex: 1;
            height: 6px;
            -webkit-appearance: none;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border-radius: 3px;
        }
        
        .slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 20px;
            height: 20px;
            background: white;
            border: 2px solid var(--primary);
            border-radius: 50%;
            cursor: pointer;
        }
        
        .value-badge {
            background: var(--primary);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 30px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        /* ===== STUDY TIMER ===== */
        .timer-panel {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: var(--radius);
            padding: 1.5rem;
            color: white;
            margin-bottom: 1.5rem;
        }
        
        .timer-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        
        .timer-header h3 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .timer-clock {
            font-size: 3rem;
            font-weight: 700;
            text-align: center;
            font-family: monospace;
            margin: 1rem 0;
        }
        
        .timer-progress {
            height: 8px;
            background: rgba(255,255,255,0.2);
            border-radius: 4px;
            margin: 1rem 0;
            overflow: hidden;
        }
        
        .timer-progress-bar {
            height: 100%;
            width: 100%;
            background: white;
            transition: width 1s linear;
        }
        
        .timer-controls {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }
        
        .timer-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            background: rgba(255,255,255,0.2);
            color: white;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .timer-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.1);
        }
        
        /* ===== READ ALOUD CONTROLS ===== */
        .read-aloud-panel {
            background: white;
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
        }
        
        .voice-controls {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
        }
        
        .voice-select {
            flex: 1;
            padding: 0.75rem;
            border: 2px solid var(--border);
            border-radius: 30px;
            font-size: 0.9rem;
            min-width: 200px;
        }
        
        .playback-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: none;
            background: var(--primary);
            color: white;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .playback-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 10px 20px rgba(67, 97, 238, 0.3);
        }
        
        .playback-btn.pause {
            background: var(--warning);
        }
        
        .playback-btn.stop {
            background: var(--danger);
        }
        
        /* ===== LOADING OVERLAY ===== */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.9);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 11000;
            flex-direction: column;
            gap: 1rem;
        }
        
        .loading-overlay.show {
            display: flex;
        }
        
        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 5px solid var(--border);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .loading-text {
            font-size: 1.2rem;
            color: var(--dark);
            font-weight: 600;
        }
        
        /* ===== TOAST NOTIFICATION ===== */
        .toast-message {
            position: fixed;
            bottom: 30px;
            right: 30px;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 50px;
            box-shadow: var(--shadow-lg);
            z-index: 11001;
            animation: slideInRight 0.3s ease;
            font-weight: 500;
        }
        
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        /* ===== COLOR BLINDNESS FILTERS ===== */
        body.protanopia-mode {
            filter: url('#protanopia') !important;
        }
        body.deuteranopia-mode {
            filter: url('#deuteranopia') !important;
        }
        body.tritanopia-mode {
            filter: url('#tritanopia') !important;
        }
        body.achromatopsia-mode {
            filter: grayscale(100%) !important;
        }
        body.high-contrast-mode {
            filter: contrast(200%) brightness(120%) !important;
        }
        
        svg {
            position: absolute;
            width: 0;
            height: 0;
        }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) {
            .analysis-grid {
                grid-template-columns: 1fr;
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
            
            .accessibility-toolbar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .toolbar-group {
                justify-content: center;
            }
            
            .timer-clock {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body data-profile="<?php echo $accessibility_profile; ?>"
      data-text-size="<?php echo $text_size; ?>"
      data-focus-mode="<?php echo $focus_mode; ?>"
      data-theme="<?php echo $theme_mode; ?>"
      class="<?php echo $adhd_mode ? 'adhd-mode' : ''; ?> size-<?php echo $text_size; ?> <?php echo ($accessibility_profile == 'dyslexia') ? 'dyslexia-mode' : ''; ?>" 
      id="mainBody">
    
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

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
        <div class="loading-text" id="loadingText">Analyzing PDF...</div>
    </div>

    <!-- Left Sidebar Navigation -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2><span>🧠 NeuroLearn</span></h2>
        </div>
        
        <ul class="nav-menu">
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="branches.php"><i class="fas fa-book-open"></i> Branches</a></li>
            <li class="active"><a href="pdf-analyzer.php"><i class="fas fa-file-pdf"></i> PDF Analyzer</a></li>
            <li><a href="my_notes.php"><i class="fas fa-sticky-note"></i> My Notes</a></li>
            <li><a href="chat.php"><i class="fas fa-robot"></i> AI Tutor</a></li>
            <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
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
                        echo $profile_display[$accessibility_profile];
                        ?>
                    </div>
                </div>
            </div>
            
            <!-- Text Size Controls -->
            <div class="section-title">📏 TEXT SIZE</div>
            <div class="text-size-controls">
                <button class="size-btn" onclick="changeTextSize(-5)">−</button>
                <span class="size-display" id="sizeDisplay"><?php echo $text_size; ?>%</span>
                <button class="size-btn" onclick="changeTextSize(5)">+</button>
            </div>
            
            <!-- Color Blindness Modes -->
            <div class="section-title">🌈 COLOR VISION</div>
            <div class="colorblind-grid">
                <button class="colorblind-btn <?php echo $color_blind_mode == 'none' ? 'active' : ''; ?>" onclick="setColorBlindMode('none')" id="mode-none">
                    <div class="colorblind-preview" style="background: linear-gradient(90deg, #4361ee, #3f37c9);"></div>
                    <span class="colorblind-name">Normal</span>
                </button>
                <button class="colorblind-btn <?php echo $color_blind_mode == 'protanopia' ? 'active' : ''; ?>" onclick="setColorBlindMode('protanopia')" id="mode-protanopia">
                    <div class="colorblind-preview" style="background: linear-gradient(90deg, #8B6B4D, #6B4D8B);"></div>
                    <span class="colorblind-name">Protanopia</span>
                </button>
                <button class="colorblind-btn <?php echo $color_blind_mode == 'deuteranopia' ? 'active' : ''; ?>" onclick="setColorBlindMode('deuteranopia')" id="mode-deuteranopia">
                    <div class="colorblind-preview" style="background: linear-gradient(90deg, #8B6B4D, #6B8B4D);"></div>
                    <span class="colorblind-name">Deuteranopia</span>
                </button>
                <button class="colorblind-btn <?php echo $color_blind_mode == 'tritanopia' ? 'active' : ''; ?>" onclick="setColorBlindMode('tritanopia')" id="mode-tritanopia">
                    <div class="colorblind-preview" style="background: linear-gradient(90deg, #FF8C69, #69FF8C);"></div>
                    <span class="colorblind-name">Tritanopia</span>
                </button>
                <button class="colorblind-btn <?php echo $color_blind_mode == 'achromatopsia' ? 'active' : ''; ?>" onclick="setColorBlindMode('achromatopsia')" id="mode-achromatopsia">
                    <div class="colorblind-preview" style="background: linear-gradient(90deg, #888, #555);"></div>
                    <span class="colorblind-name">Monochrome</span>
                </button>
                <button class="colorblind-btn <?php echo $color_blind_mode == 'high-contrast' ? 'active' : ''; ?>" onclick="setColorBlindMode('high-contrast')" id="mode-high-contrast">
                    <div class="colorblind-preview" style="background: linear-gradient(90deg, #000, #FF0);"></div>
                    <span class="colorblind-name">High Contrast</span>
                </button>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="content-container">
            <!-- Header -->
            <div class="analyzer-header">
                <h1>📄 PDF Analyzer</h1>
                <p>Upload your PDF for AI-powered analysis with dyslexia & ADHD-friendly features</p>
            </div>
            
            <!-- Accessibility Toolbar -->
            <div class="accessibility-toolbar">
                <div class="toolbar-group">
                    <button class="toolbar-btn <?php echo $adhd_mode ? 'active' : ''; ?>" onclick="toggleFocusMode()" id="focusModeBtn">
                        <i class="fas fa-bullseye"></i> Focus Mode
                    </button>
                    <button class="toolbar-btn" onclick="toggleDyslexiaMode()" id="dyslexiaBtn">
                        <i class="fas fa-font"></i> Dyslexia Mode
                    </button>
                    <button class="toolbar-btn" onclick="startReadAloud()" id="readAloudBtn">
                        <i class="fas fa-volume-up"></i> Read Aloud
                    </button>
                    <button class="toolbar-btn" onclick="stopReadAloud()" id="stopReadBtn">
                        <i class="fas fa-stop"></i> Stop
                    </button>
                </div>
                
                <div class="toolbar-group">
                    <div class="timer-display" id="timerDisplay">05:00</div>
                </div>
            </div>
            
            <!-- Upload Section -->
            <div class="upload-section" id="uploadSection">
                <div class="upload-icon">
                    <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <h3>Drop your PDF here or click to upload</h3>
                <p>Supports PDF files up to 50MB • Secure processing • Instant analysis</p>
                <input type="file" id="pdfFile" accept=".pdf" style="display: none;">
                <button class="upload-btn" onclick="document.getElementById('pdfFile').click()">
                    <i class="fas fa-upload"></i> Choose PDF
                </button>
                
                <div class="file-info" id="fileInfo">
                    <i class="fas fa-file-pdf"></i>
                    <span id="fileName"></span> • 
                    <span id="fileSize"></span>
                </div>
            </div>
            
            <!-- Analysis Grid -->
            <div class="analysis-grid" id="analysisGrid" style="display: none;">
                <!-- PDF Preview Panel -->
                <div class="preview-panel">
                    <h2>
                        <i class="fas fa-file-pdf" style="color: #dc2626;"></i>
                        PDF Preview
                    </h2>
                    <div class="pdf-preview" id="pdfPreview">
                        <div class="pdf-preview-content" id="pdfContent"></div>
                        
                        <!-- Focus Mode Overlay (for ADHD) -->
                        <div class="focus-overlay" id="focusOverlay" style="display: none;">
                            <div class="focus-roller-pdf" id="focusRollerPdf"></div>
                            <div class="blur-overlay-pdf" id="blurTopPdf"></div>
                            <div class="blur-overlay-pdf" id="blurBottomPdf"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Summary Panel -->
                <div class="summary-panel">
                    <h2>
                        <i class="fas fa-list-alt" style="color: var(--primary);"></i>
                        Smart Summary
                    </h2>
                    <div class="summary-content" id="summaryContent">
                        <div class="summary-section">
                            <h3><i class="fas fa-lightbulb"></i> Key Points</h3>
                            <div id="keyPoints"></div>
                        </div>
                        
                        <div class="summary-section">
                            <h3><i class="fas fa-compass"></i> Main Concepts</h3>
                            <div id="mainConcepts"></div>
                        </div>
                        
                        <div class="summary-section">
                            <h3><i class="fas fa-flag"></i> Important Takeaways</h3>
                            <div id="takeaways"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Keywords Panel -->
            <div class="keywords-panel" id="keywordsPanel" style="display: none;">
                <h2><i class="fas fa-tags"></i> Important Keywords & Definitions</h2>
                <div class="keywords-cloud" id="keywordsCloud"></div>
            </div>
            
            <!-- Focus Controls Panel (for ADHD mode) -->
            <div class="focus-controls" id="focusControls" style="display: none;">
                <h3><i class="fas fa-bullseye" style="color: #8b5cf6;"></i> Focus Mode Controls</h3>
                
                <div class="control-row">
                    <span>Roller Height</span>
                    <div class="slider-container">
                        <input type="range" class="slider" id="rollerHeight" min="60" max="300" value="120">
                        <span class="value-badge" id="heightValue">120px</span>
                    </div>
                </div>
                
                <div class="control-row">
                    <span>Blur Intensity</span>
                    <div class="slider-container">
                        <input type="range" class="slider" id="blurIntensity" min="2" max="10" value="4">
                        <span class="value-badge" id="blurValue">4px</span>
                    </div>
                </div>
                
                <div class="control-row">
                    <span>Highlight Color</span>
                    <div style="display: flex; gap: 0.5rem;">
                        <button class="toolbar-btn" style="background: #8b5cf6; color: white;" onclick="setHighlightColor('#8b5cf6')">Purple</button>
                        <button class="toolbar-btn" style="background: #3b82f6; color: white;" onclick="setHighlightColor('#3b82f6')">Blue</button>
                        <button class="toolbar-btn" style="background: #10b981; color: white;" onclick="setHighlightColor('#10b981')">Green</button>
                    </div>
                </div>
            </div>
            
            <!-- Timer Panel -->
            <div class="timer-panel" id="timerPanel">
                <div class="timer-header">
                    <h3><i class="fas fa-hourglass-half"></i> Focus Timer</h3>
                    <div class="timer-controls">
                        <button class="timer-btn" onclick="startTimer()">
                            <i class="fas fa-play"></i>
                        </button>
                        <button class="timer-btn" onclick="pauseTimer()">
                            <i class="fas fa-pause"></i>
                        </button>
                        <button class="timer-btn" onclick="resetTimer()">
                            <i class="fas fa-redo"></i>
                        </button>
                    </div>
                </div>
                
                <div class="timer-clock" id="timerClock">05:00</div>
                
                <div class="timer-progress">
                    <div class="timer-progress-bar" id="timerProgress" style="width: 100%;"></div>
                </div>
                
                <div style="text-align: center; font-size: 0.9rem; opacity: 0.9;">
                    <span id="timerStatus">Ready to focus</span>
                </div>
            </div>
            
            <!-- Read Aloud Panel -->
            <div class="read-aloud-panel" id="readAloudPanel">
                <h3><i class="fas fa-microphone-alt"></i> Read Aloud</h3>
                
                <div class="voice-controls">
                    <select class="voice-select" id="voiceSelect">
                        <option value="">Select Voice</option>
                        <option value="male">Male Voice (English)</option>
                        <option value="female">Female Voice (English)</option>
                        <option value="uk">UK English</option>
                        <option value="aus">Australian English</option>
                    </select>
                    
                    <button class="playback-btn" onclick="playSummary()">
                        <i class="fas fa-play"></i>
                    </button>
                    <button class="playback-btn pause" onclick="pauseSpeech()">
                        <i class="fas fa-pause"></i>
                    </button>
                    <button class="playback-btn stop" onclick="stopSpeech()">
                        <i class="fas fa-stop"></i>
                    </button>
                    
                    <select class="voice-select" id="speedSelect">
                        <option value="0.5">0.5x</option>
                        <option value="0.75">0.75x</option>
                        <option value="1" selected>1x</option>
                        <option value="1.25">1.25x</option>
                        <option value="1.5">1.5x</option>
                        <option value="2">2x</option>
                    </select>
                </div>
            </div>
        </div>
    </main>

    <script>
        // ===== GLOBAL VARIABLES =====
        let currentPDFText = '';
        let analysisData = null;
        let synth = window.speechSynthesis;
        let currentUtterance = null;
        let timerInterval = null;
        let timeLeft = 300; // 5 minutes in seconds
        let rollerActive = <?php echo $adhd_mode ? 'true' : 'false'; ?>;
        let rollerHeight = 120;
        let rollerTop = 0;
        let blurIntensity = 4;
        
        // ===== TEXT SIZE MANAGEMENT =====
        let currentSize = <?php echo $text_size; ?>;
        const MIN_SIZE = 85;
        const MAX_SIZE = 130;
        
        function changeTextSize(delta) {
            let newSize = currentSize + delta;
            if (newSize < MIN_SIZE || newSize > MAX_SIZE) return;
            
            currentSize = newSize;
            document.body.classList.remove(
                'size-85', 'size-90', 'size-95', 'size-100',
                'size-105', 'size-110', 'size-115', 'size-120',
                'size-125', 'size-130'
            );
            document.body.classList.add('size-' + currentSize);
            document.getElementById('sizeDisplay').textContent = currentSize + '%';
            
            // Save to localStorage
            localStorage.setItem('accessibility_text_size', currentSize);
            
            // Save to server via AJAX
            fetch('save_setting.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ text_size: currentSize })
            });
        }
        
        // ===== COLOR BLINDNESS MODES =====
        function setColorBlindMode(mode) {
            document.body.classList.remove(
                'protanopia-mode', 'deuteranopia-mode', 'tritanopia-mode', 
                'achromatopsia-mode', 'high-contrast-mode'
            );
            
            document.querySelectorAll('.colorblind-btn').forEach(btn => btn.classList.remove('active'));
            document.getElementById('mode-' + mode).classList.add('active');
            
            if (mode !== 'none') {
                document.body.classList.add(mode + '-mode');
            }
            
            localStorage.setItem('colorblind_mode', mode);
            
            fetch('save_setting.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ color_blind_mode: mode })
            });
            
            showToast('Color mode updated');
        }
        
        // ===== DYSLEXIA MODE =====
        function toggleDyslexiaMode() {
            document.body.classList.toggle('dyslexia-mode');
            const btn = document.getElementById('dyslexiaBtn');
            if (document.body.classList.contains('dyslexia-mode')) {
                btn.classList.add('active');
                showToast('Dyslexia-friendly font enabled');
            } else {
                btn.classList.remove('active');
                showToast('Standard font restored');
            }
        }
        
        // ===== PDF UPLOAD HANDLING =====
        document.getElementById('pdfFile').addEventListener('change', handleFileUpload);
        
        const uploadSection = document.getElementById('uploadSection');
        uploadSection.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadSection.classList.add('dragover');
        });
        
        uploadSection.addEventListener('dragleave', (e) => {
            e.preventDefault();
            uploadSection.classList.remove('dragover');
        });
        
        uploadSection.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadSection.classList.remove('dragover');
            const file = e.dataTransfer.files[0];
            if (file && file.type === 'application/pdf') {
                processPDF(file);
            } else {
                showToast('Please upload a PDF file', 'error');
            }
        });
        
        function handleFileUpload(e) {
            const file = e.target.files[0];
            if (file) {
                processPDF(file);
            }
        }
        
        async function processPDF(file) {
            // Show file info
            document.getElementById('fileName').textContent = file.name;
            document.getElementById('fileSize').textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';
            document.getElementById('fileInfo').classList.add('show');
            
            // Show loading
            showLoading('Analyzing PDF...');
            
            // Simulate PDF processing (replace with actual API call)
            setTimeout(() => {
                // Sample analysis data (replace with actual API response)
                analysisData = {
                    text: "Machine learning is a subset of artificial intelligence that enables systems to learn and improve from experience without being explicitly programmed. It focuses on developing computer programs that can access data and use it to learn for themselves. The learning process begins with observations or data, such as examples, direct experience, or instruction, to look for patterns in data and make better decisions in the future based on the examples we provide. The primary aim is to allow computers to learn automatically without human intervention or assistance and adjust actions accordingly.",
                    summary: {
                        keyPoints: [
                            "Machine learning is a subset of artificial intelligence",
                            "Systems learn and improve from experience without explicit programming",
                            "Uses data to find patterns and make decisions",
                            "Aims for automatic learning without human intervention"
                        ],
                        mainConcepts: [
                            "Supervised Learning: Learning from labeled data",
                            "Unsupervised Learning: Finding patterns in unlabeled data",
                            "Reinforcement Learning: Learning through trial and error"
                        ],
                        takeaways: [
                            "ML enables computers to learn from data",
                            "Three main types: supervised, unsupervised, reinforcement",
                            "Applications include prediction, classification, and clustering"
                        ]
                    },
                    keywords: [
                        { word: "Machine Learning", type: "important", definition: "AI subset for automatic learning" },
                        { word: "Artificial Intelligence", type: "keyword", definition: "Human-like intelligence in machines" },
                        { word: "Supervised Learning", type: "definition", definition: "Learning from labeled data" },
                        { word: "Unsupervised Learning", type: "definition", definition: "Finding patterns in unlabeled data" },
                        { word: "Reinforcement Learning", type: "definition", definition: "Learning through rewards/punishments" },
                        { word: "Pattern Recognition", type: "important", definition: "Identifying regularities in data" }
                    ]
                };
                
                currentPDFText = analysisData.text;
                
                // Display PDF preview with highlighted text
                displayPDFPreview(analysisData.text, analysisData.keywords);
                
                // Display summary
                displaySummary(analysisData.summary);
                
                // Display keywords cloud
                displayKeywords(analysisData.keywords);
                
                // Show all panels
                document.getElementById('analysisGrid').style.display = 'grid';
                document.getElementById('keywordsPanel').style.display = 'block';
                
                hideLoading();
                showToast('PDF analyzed successfully!');
            }, 2000);
        }
        
        function displayPDFPreview(text, keywords) {
            let highlightedText = text;
            
            // Highlight keywords
            keywords.forEach(item => {
                const regex = new RegExp(item.word, 'gi');
                highlightedText = highlightedText.replace(regex, match => 
                    `<span class="highlight-word ${item.type}" title="${item.definition}">${match}</span>`
                );
            });
            
            document.getElementById('pdfContent').innerHTML = highlightedText;
        }
        
        function displaySummary(summary) {
            // Key Points
            let keyPointsHTML = '';
            summary.keyPoints.forEach((point, index) => {
                keyPointsHTML += `
                    <div class="point-item">
                        <span class="point-bullet">•</span>
                        <span class="point-content">${point}</span>
                    </div>
                `;
            });
            document.getElementById('keyPoints').innerHTML = keyPointsHTML;
            
            // Main Concepts
            let conceptsHTML = '';
            summary.mainConcepts.forEach((concept, index) => {
                conceptsHTML += `
                    <div class="point-item">
                        <span class="point-bullet">→</span>
                        <span class="point-content">${concept}</span>
                    </div>
                `;
            });
            document.getElementById('mainConcepts').innerHTML = conceptsHTML;
            
            // Takeaways
            let takeawaysHTML = '';
            summary.takeaways.forEach((takeaway, index) => {
                takeawaysHTML += `
                    <div class="point-item">
                        <span class="point-bullet">★</span>
                        <span class="point-content">${takeaway}</span>
                    </div>
                `;
            });
            document.getElementById('takeaways').innerHTML = takeawaysHTML;
        }
        
        function displayKeywords(keywords) {
            let keywordsHTML = '';
            keywords.forEach(item => {
                keywordsHTML += `
                    <span class="keyword-tag ${item.type}" title="${item.definition}">
                        ${item.word}
                    </span>
                `;
            });
            document.getElementById('keywordsCloud').innerHTML = keywordsHTML;
        }
        
        // ===== FOCUS MODE (ADHD) =====
        function toggleFocusMode() {
            rollerActive = !rollerActive;
            const btn = document.getElementById('focusModeBtn');
            const overlay = document.getElementById('focusOverlay');
            const controls = document.getElementById('focusControls');
            
            if (rollerActive) {
                btn.classList.add('active');
                overlay.style.display = 'block';
                controls.style.display = 'block';
                document.body.classList.add('adhd-mode');
                initFocusRoller();
            } else {
                btn.classList.remove('active');
                overlay.style.display = 'none';
                controls.style.display = 'none';
                document.body.classList.remove('adhd-mode');
            }
        }
        
        function initFocusRoller() {
            const preview = document.querySelector('.pdf-preview');
            const roller = document.getElementById('focusRollerPdf');
            
            rollerTop = preview.offsetHeight / 2 - rollerHeight / 2;
            roller.style.top = rollerTop + 'px';
            roller.style.height = rollerHeight + 'px';
            
            updateFocusOverlays();
        }
        
        document.addEventListener('mousemove', function(e) {
            if (rollerActive) {
                const preview = document.querySelector('.pdf-preview');
                const rect = preview.getBoundingClientRect();
                
                if (e.clientY >= rect.top && e.clientY <= rect.bottom) {
                    let newTop = e.clientY - rect.top - rollerHeight / 2;
                    newTop = Math.max(0, Math.min(preview.offsetHeight - rollerHeight, newTop));
                    
                    rollerTop = newTop;
                    document.getElementById('focusRollerPdf').style.top = rollerTop + 'px';
                    updateFocusOverlays();
                }
            }
        });
        
        function updateFocusOverlays() {
            const preview = document.querySelector('.pdf-preview');
            const roller = document.getElementById('focusRollerPdf');
            
            document.getElementById('blurTopPdf').style.cssText = `
                top: 0;
                left: 0;
                right: 0;
                height: ${rollerTop}px;
                background: rgba(0,0,0,0.85);
                backdrop-filter: blur(${blurIntensity}px);
            `;
            
            document.getElementById('blurBottomPdf').style.cssText = `
                top: ${rollerTop + rollerHeight}px;
                left: 0;
                right: 0;
                height: ${preview.offsetHeight - (rollerTop + rollerHeight)}px;
                background: rgba(0,0,0,0.85);
                backdrop-filter: blur(${blurIntensity}px);
            `;
        }
        
        document.getElementById('rollerHeight').addEventListener('input', function(e) {
            rollerHeight = parseInt(e.target.value);
            document.getElementById('heightValue').textContent = rollerHeight + 'px';
            document.getElementById('focusRollerPdf').style.height = rollerHeight + 'px';
            updateFocusOverlays();
        });
        
        document.getElementById('blurIntensity').addEventListener('input', function(e) {
            blurIntensity = parseInt(e.target.value);
            document.getElementById('blurValue').textContent = blurIntensity + 'px';
            updateFocusOverlays();
        });
        
        function setHighlightColor(color) {
            document.getElementById('focusRollerPdf').style.borderColor = color;
        }
        
        // ===== TIMER FUNCTIONS =====
        function startTimer() {
            if (timerInterval) clearInterval(timerInterval);
            
            timerInterval = setInterval(() => {
                if (timeLeft > 0) {
                    timeLeft--;
                    updateTimerDisplay();
                    
                    if (timeLeft === 0) {
                        // Timer hit zero - notify user
                        showToast("⏰ Time's up! Take a break or continue reading.");
                        playTimerSound();
                        resetTimer();
                    }
                }
            }, 1000);
            
            document.getElementById('timerStatus').textContent = 'Focus mode active';
        }
        
        function pauseTimer() {
            if (timerInterval) {
                clearInterval(timerInterval);
                timerInterval = null;
                document.getElementById('timerStatus').textContent = 'Timer paused';
            }
        }
        
        function resetTimer() {
            pauseTimer();
            timeLeft = 300;
            updateTimerDisplay();
            document.getElementById('timerStatus').textContent = 'Ready to focus';
        }
        
        function updateTimerDisplay() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            const timeString = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            document.getElementById('timerClock').textContent = timeString;
            document.getElementById('timerDisplay').textContent = timeString;
            
            const progress = (timeLeft / 300) * 100;
            document.getElementById('timerProgress').style.width = progress + '%';
        }
        
        function playTimerSound() {
            const audio = new Audio('data:audio/wav;base64,UklGRlwAAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YVAAAAA=');
            audio.play();
        }
        
        // ===== READ ALOUD FUNCTIONS =====
        function playSummary() {
            if (!analysisData) {
                showToast('Please upload a PDF first');
                return;
            }
            
            // Stop any ongoing speech
            if (synth.speaking) {
                synth.cancel();
            }
            
            // Get summary text
            let text = "Summary of your PDF. ";
            analysisData.summary.keyPoints.forEach(point => {
                text += point + ". ";
            });
            text += "Main concepts include: ";
            analysisData.summary.mainConcepts.forEach(concept => {
                text += concept + ". ";
            });
            
            const utterance = new SpeechSynthesisUtterance(text);
            
            // Get selected voice and speed
            const voiceType = document.getElementById('voiceSelect').value;
            const speed = parseFloat(document.getElementById('speedSelect').value);
            
            // Set voice (simplified - in production you'd map to actual voices)
            const voices = synth.getVoices();
            if (voiceType === 'male') {
                utterance.voice = voices.find(v => v.name.includes('Male')) || voices[0];
            } else if (voiceType === 'female') {
                utterance.voice = voices.find(v => v.name.includes('Female')) || voices[1];
            }
            
            utterance.rate = speed;
            utterance.pitch = 1;
            
            utterance.onstart = () => {
                document.getElementById('readAloudBtn').classList.add('active');
            };
            
            utterance.onend = () => {
                document.getElementById('readAloudBtn').classList.remove('active');
            };
            
            synth.speak(utterance);
            currentUtterance = utterance;
        }
        
        function stopReadAloud() {
            if (synth.speaking) {
                synth.cancel();
                document.getElementById('readAloudBtn').classList.remove('active');
            }
        }
        
        function pauseSpeech() {
            if (synth.speaking && !synth.paused) {
                synth.pause();
            }
        }
        
        function stopSpeech() {
            if (synth.speaking) {
                synth.cancel();
            }
        }
        
        // ===== UTILITY FUNCTIONS =====
        function showLoading(message = 'Processing...') {
            document.getElementById('loadingText').textContent = message;
            document.getElementById('loadingOverlay').classList.add('show');
        }
        
        function hideLoading() {
            document.getElementById('loadingOverlay').classList.remove('show');
        }
        
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = 'toast-message';
            toast.textContent = message;
            toast.style.background = type === 'error' ? 
                'linear-gradient(135deg, #dc2626, #b91c1c)' : 
                'linear-gradient(135deg, var(--primary), var(--secondary))';
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateTimerDisplay();
            
            // Load saved settings
            const savedSize = localStorage.getItem('accessibility_text_size');
            if (savedSize) {
                currentSize = parseInt(savedSize);
                document.body.classList.add('size-' + currentSize);
                document.getElementById('sizeDisplay').textContent = currentSize + '%';
            }
            
            // Initialize focus mode if active
            if (rollerActive) {
                initFocusRoller();
            }
        });
    </script>
</body>
</html>
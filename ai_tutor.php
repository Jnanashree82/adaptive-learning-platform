<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$session_id = session_id();

// Get user preferences for ADHD mode
$sql = "SELECT accessibility_profile, focus_mode FROM users WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$user_prefs = $stmt->fetch();
$adhd_mode = ($user_prefs['accessibility_profile'] == 'adhd' || $user_prefs['focus_mode'] == 1);

// Handle file upload
$upload_success = '';
$upload_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_upload'])) {
    $target_dir = "uploads/ai_tutor/" . $user_id . "/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_name = time() . '_' . basename($_FILES['file_upload']['name']);
    $target_file = $target_dir . $file_name;
    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    if ($_FILES['file_upload']['size'] > 10000000) {
        $upload_error = "File is too large. Max size is 10MB.";
    } else {
        if (move_uploaded_file($_FILES['file_upload']['tmp_name'], $target_file)) {
            $extracted_text = "File uploaded successfully: " . $file_name;
            
            $sql = "INSERT INTO uploaded_files (user_id, session_id, file_name, file_path, file_type, extracted_text) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $session_id, $file_name, $target_file, $file_type, $extracted_text]);
            
            $upload_success = "File uploaded successfully!";
        } else {
            $upload_error = "Error uploading file.";
        }
    }
}

// Get chat history
$sql = "SELECT * FROM chat_history WHERE user_id = ? AND session_id = ? ORDER BY created_at ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id, $session_id]);
$chat_history = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Engineering Tutor - NeuroLearn</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/adaptive.css">
    <link rel="stylesheet" href="css/accessibility.css">
    <link rel="stylesheet" href="css/modes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- JavaScript -->
    <script src="js/global-accessibility.js" defer></script>
    <script src="js/theme-loader.js" defer></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        /* ===== DASHBOARD LAYOUT ===== */
        .dashboard {
            display: flex;
            min-height: 100vh;
            position: relative;
        }

        .sidebar {
            width: 280px;
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        /* ADHD Mode - Sidebar hidden */
        body.adhd-mode .sidebar {
            transform: translateX(-100%);
            opacity: 0;
            pointer-events: none;
        }

        body.adhd-mode .main-content {
            margin-left: 0;
        }

        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid #e5e7eb;
        }

        .sidebar-header h2 {
            color: #667eea;
            font-size: 24px;
        }

        .nav-menu {
            flex: 1;
            padding: 20px 0;
            list-style: none;
        }

        .nav-menu li {
            margin: 5px 15px;
        }

        .nav-menu li a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #6b7280;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
            gap: 12px;
        }

        .nav-menu li.active a {
            background: linear-gradient(135deg, #667eea20, #764ba220);
            color: #667eea;
            font-weight: 600;
        }

        .nav-menu li a:hover {
            background: #f8fafc;
            color: #667eea;
        }

        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        /* ADHD Mode - Full width */
        body.adhd-mode .main-content {
            margin-left: 0;
            padding: 20px;
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

        /* ===== ADHD FOCUS TUNNEL ===== */
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

        /* ===== TUTOR CONTAINER ===== */
        .tutor-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            overflow: hidden;
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .tutor-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .tutor-header h1 {
            font-size: 36px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .tutor-header p {
            font-size: 18px;
            opacity: 0.95;
            margin-bottom: 20px;
        }

        .branch-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin-top: 15px;
        }

        .branch-tag {
            background: rgba(255,255,255,0.2);
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 14px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.3);
            transition: all 0.3s;
            cursor: pointer;
        }

        .branch-tag:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.05);
        }

        .input-methods {
            display: flex;
            gap: 15px;
            padding: 20px;
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
            flex-wrap: wrap;
        }

        .method-btn {
            flex: 1;
            min-width: 100px;
            padding: 15px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            color: #6b7280;
        }

        .method-btn i {
            font-size: 24px;
        }

        .method-btn.active {
            border-color: #667eea;
            background: linear-gradient(135deg, #667eea10, #764ba210);
            color: #667eea;
        }

        .method-btn:hover {
            border-color: #667eea;
            transform: translateY(-3px);
        }

        .chat-container {
            height: 450px;
            overflow-y: auto;
            padding: 30px;
            background: #ffffff;
        }

        .message {
            margin-bottom: 20px;
            display: flex;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .user-message {
            justify-content: flex-end;
        }

        .ai-message {
            justify-content: flex-start;
        }

        .message-content {
            max-width: 80%;
            padding: 15px 20px;
            border-radius: 20px;
            line-height: 1.6;
            font-size: 15px;
        }

        .user-message .message-content {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-bottom-right-radius: 5px;
        }

        .ai-message .message-content {
            background: #f8fafc;
            color: #333;
            border-bottom-left-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .message-meta {
            font-size: 11px;
            margin-top: 5px;
            color: #94a3b8;
        }

        .upload-area {
            padding: 20px;
            border: 2px dashed #e5e7eb;
            border-radius: 12px;
            text-align: center;
            margin: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .upload-area:hover {
            border-color: #667eea;
            background: #f8fafc;
        }

        .upload-area i {
            font-size: 40px;
            color: #667eea;
            margin-bottom: 10px;
        }

        .voice-recorder {
            display: none;
            padding: 20px;
            text-align: center;
            background: #f8fafc;
            margin: 20px;
            border-radius: 12px;
        }

        .voice-recorder.active {
            display: block;
        }

        .record-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ff6b6b, #ff8e53);
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            margin: 10px;
            transition: all 0.3s;
        }

        .record-btn.recording {
            animation: pulse 1s infinite;
        }

        .input-area {
            padding: 20px;
            background: #f8fafc;
            border-top: 1px solid #e5e7eb;
        }

        .suggestions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }

        .suggestion-btn {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 30px;
            padding: 8px 16px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .suggestion-btn:hover {
            border-color: #667eea;
            color: #667eea;
        }

        .input-container {
            display: flex;
            gap: 10px;
        }

        textarea {
            flex: 1;
            padding: 15px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 15px;
            resize: none;
            height: 80px;
            font-family: inherit;
        }

        textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .send-btn {
            width: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .send-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 20px rgba(102,126,234,0.3);
        }

        .typing-indicator {
            display: flex;
            gap: 5px;
            padding: 15px 20px;
            background: #f8fafc;
            border-radius: 20px;
            width: fit-content;
        }

        .typing-dot {
            width: 8px;
            height: 8px;
            background: #94a3b8;
            border-radius: 50%;
            animation: typing 1.4s infinite;
        }

        .typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-dot:nth-child(3) { animation-delay: 0.4s; }

        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-10px); }
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

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) {
            .branches-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .main-content {
                margin-left: 0;
                padding: 15px;
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
      class="<?php echo $adhd_mode ? 'adhd-mode' : ''; ?>" id="mainBody">
    
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
                <a href="dashboard.php" class="fab-menu-item">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="branches.php" class="fab-menu-item">
                    <i class="fas fa-book-open"></i>
                    <span>Branches</span>
                </a>
                <a href="pdf-analyzer.php" class="fab-menu-item">
                    <i class="fas fa-file-pdf"></i>
                    <span>PDF Analyzer</span>
                    <span class="fab-badge">New</span>
                </a>
                <a href="my_notes.php" class="fab-menu-item">
                    <i class="fas fa-sticky-note"></i>
                    <span>My Notes</span>
                </a>
                <a href="ai_tutor.php" class="fab-menu-item active">
                    <i class="fas fa-robot"></i>
                    <span>AI Tutor</span>
                </a>
                <div class="fab-menu-divider"></div>
                <a href="settings.php" class="fab-menu-item">
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
            <i class="fas fa-info-circle"></i> Only content inside the roller is visible
        </div>
    </div>

    <div class="dashboard">
        <nav class="sidebar">
            <div class="sidebar-header">
                <h2>🧠 NeuroLearn</h2>
            </div>
            <ul class="nav-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="branches.php"><i class="fas fa-book-open"></i> Branches</a></li>
                <li><a href="pdf-analyzer.php"><i class="fas fa-file-pdf"></i> PDF Analyzer</a></li>
                <li><a href="my_notes.php"><i class="fas fa-sticky-note"></i> My Notes</a></li>
                <li class="active"><a href="ai_tutor.php"><i class="fas fa-robot"></i> AI Tutor</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <div class="tutor-container">
                <div class="tutor-header">
                    <h1>
                        <i class="fas fa-robot"></i>
                        AI Engineering Tutor
                        <i class="fas fa-graduation-cap"></i>
                    </h1>
                    <p><?php echo $adhd_mode ? '🎯 Focus Mode Active - Move mouse to control the focus roller. Click the <i class="fas fa-compass"></i> button for navigation.' : 'Ask any engineering question - Get accurate answers in seconds! 🚀'; ?></p>
                    <div class="branch-tags">
                        <span class="branch-tag" onclick="setBranch('CSE')">💻 CSE</span>
                        <span class="branch-tag" onclick="setBranch('ECE')">📡 ECE</span>
                        <span class="branch-tag" onclick="setBranch('ME')">🔧 ME</span>
                        <span class="branch-tag" onclick="setBranch('CE')">🏗️ CE</span>
                        <span class="branch-tag" onclick="setBranch('EEE')">⚡ EEE</span>
                        <span class="branch-tag" onclick="setBranch('AIML')">🤖 AIML</span>
                        <span class="branch-tag" onclick="setBranch('AIDS')">📊 AIDS</span>
                    </div>
                </div>

                <div class="input-methods">
                    <button class="method-btn active" onclick="showInput('text')" id="btn-text">
                        <i class="fas fa-keyboard"></i>
                        <span>Text</span>
                    </button>
                    <button class="method-btn" onclick="showInput('voice')" id="btn-voice">
                        <i class="fas fa-microphone"></i>
                        <span>Voice</span>
                    </button>
                    <button class="method-btn" onclick="showInput('file')" id="btn-file">
                        <i class="fas fa-file-pdf"></i>
                        <span>PDF/Image</span>
                    </button>
                </div>

                <div id="fileUploadArea" class="upload-area" style="display: none;">
                    <form action="ai_tutor.php" method="post" enctype="multipart/form-data" id="uploadForm">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <h3>Upload PDF or Image</h3>
                        <p>Click to browse or drag and drop</p>
                        <input type="file" name="file_upload" id="fileInput" style="display: none;" accept=".pdf,.txt,.jpg,.jpeg,.png">
                        <div id="fileInfo" style="margin-top: 10px; color: #667eea;"></div>
                        <?php if($upload_success): ?>
                            <p style="color: #43e97b;">✅ <?php echo $upload_success; ?></p>
                        <?php endif; ?>
                        <?php if($upload_error): ?>
                            <p style="color: #ff6b6b;">❌ <?php echo $upload_error; ?></p>
                        <?php endif; ?>
                    </form>
                </div>

                <div id="voiceRecorder" class="voice-recorder">
                    <h3><i class="fas fa-microphone-alt"></i> Voice Recorder</h3>
                    <button class="record-btn" id="recordBtn" onclick="toggleRecording()">
                        <i class="fas fa-microphone"></i>
                    </button>
                    <p id="recordingStatus">Click to start recording</p>
                </div>

                <div class="chat-container" id="chatContainer">
                    <div class="message ai-message">
                        <div class="message-content">
                            <strong>👋 Hello! I'm your AI Engineering Tutor</strong><br><br>
                            I can answer any engineering question instantly using AI!<br><br>
                            <strong>Try asking:</strong><br>
                            • "Explain Quick Sort algorithm with example"<br>
                            • "What is Ohm's Law? Derive its formula"<br>
                            • "Write Python code for binary search"<br>
                            • "Explain laws of thermodynamics"<br>
                            • "What are the types of foundations in civil engineering?"<br>
                        </div>
                    </div>

                    <?php foreach($chat_history as $chat): ?>
                        <div class="message user-message">
                            <div class="message-content">
                                <?php echo htmlspecialchars($chat['message']); ?>
                                <div class="message-meta">
                                    <i class="fas fa-<?php echo $chat['message_type'] == 'text' ? 'keyboard' : ($chat['message_type'] == 'voice' ? 'microphone' : 'file'); ?>"></i>
                                    <?php echo date('h:i A', strtotime($chat['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                        <div class="message ai-message">
                            <div class="message-content">
                                <?php echo nl2br(htmlspecialchars($chat['response'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="input-area">
                    <div class="suggestions">
                        <button class="suggestion-btn" onclick="setQuestion('Explain Quick Sort algorithm with example and time complexity')">📊 Quick Sort</button>
                        <button class="suggestion-btn" onclick="setQuestion('What is Ohm\'s Law? Explain with formula and examples')">⚡ Ohm's Law</button>
                        <button class="suggestion-btn" onclick="setQuestion('Write Python code for binary search and explain')">🐍 Binary Search</button>
                        <button class="suggestion-btn" onclick="setQuestion('Explain the laws of thermodynamics with applications')">🔥 Thermodynamics</button>
                        <button class="suggestion-btn" onclick="setQuestion('What are the different types of foundations in civil engineering?')">🏗️ Foundations</button>
                    </div>
                    
                    <div class="input-container" id="textInput">
                        <textarea id="userInput" placeholder="Ask any engineering question..."></textarea>
                        <button class="send-btn" onclick="sendMessage()"><i class="fas fa-paper-plane"></i></button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Voice Agent -->
    <?php include 'includes/voice-agent.php'; ?>

    <script>
    // ===== ADHD FOCUS TUNNEL =====
    let rollerActive = <?php echo $adhd_mode ? 'true' : 'false'; ?>;
    let rollerHeight = 120;
    let rollerTop = window.innerHeight / 2 - rollerHeight / 2;
    let blurIntensity = 8;
    let highlightColor = '#8b5cf6';
    
    // ===== TEXT SIZE MANAGEMENT =====
    let currentSize = <?php echo $_SESSION['accessibility']['text_size'] ?? 100; ?>;
    const MIN_SIZE = 85;
    const MAX_SIZE = 130;
    
    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        if (rollerActive) {
            initFocusTunnel();
        }
        
        // Close floating menu when clicking outside
        document.addEventListener('click', function(event) {
            const fabMenu = document.getElementById('fabMenu');
            const fabMain = document.getElementById('fabMain');
            
            if (fabMain && fabMenu && !fabMain.contains(event.target) && !fabMenu.contains(event.target)) {
                fabMenu.classList.remove('show');
            }
        });

        // Textarea auto-resize
        const textarea = document.getElementById('userInput');
        if (textarea) {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
            
            textarea.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
        }
    });
    
    // ===== FLOATING MENU FUNCTIONS =====
    function toggleFabMenu() {
        const menu = document.getElementById('fabMenu');
        menu.classList.toggle('show');
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
    
    // ===== TEXT SIZE FUNCTIONS =====
    function changeTextSize(delta) {
        let newSize = currentSize + delta;
        
        if (newSize < MIN_SIZE || newSize > MAX_SIZE) return;
        
        currentSize = newSize;
        document.body.style.fontSize = currentSize + '%';
        document.getElementById('sizeDisplay').textContent = currentSize + '%';
        localStorage.setItem('accessibility_text_size', currentSize);
    }
    
    function setColorBlindMode(mode) {
        document.body.classList.remove('protanopia-mode', 'deuteranopia-mode', 'tritanopia-mode', 'achromatopsia-mode', 'high-contrast-mode');
        if (mode !== 'none') {
            document.body.classList.add(mode + '-mode');
        }
        localStorage.setItem('colorblind_mode', mode);
        showToast('Color mode updated');
    }
    
    function showToast(message) {
        const toast = document.createElement('div');
        toast.className = 'toast-message';
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() -> toast.remove(), 300);
        }, 2000);
    }
    
    // ===== AI TUTOR FUNCTIONS =====
    let currentInputMode = 'text';
    let mediaRecorder;
    let audioChunks = [];
    let isRecording = false;
    let currentBranch = '';

    function showInput(mode) {
        currentInputMode = mode;
        document.querySelectorAll('.method-btn').forEach(btn => btn.classList.remove('active'));
        document.getElementById(`btn-${mode}`).classList.add('active');
        document.getElementById('textInput').style.display = mode === 'text' ? 'flex' : 'none';
        document.getElementById('fileUploadArea').style.display = mode === 'file' ? 'block' : 'none';
        document.getElementById('voiceRecorder').style.display = mode === 'voice' ? 'block' : 'none';
    }

    document.querySelector('.upload-area')?.addEventListener('click', function() {
        document.getElementById('fileInput').click();
    });

    document.getElementById('fileInput')?.addEventListener('change', function(e) {
        if (this.files.length > 0) {
            document.getElementById('fileInfo').innerHTML = `📎 ${this.files[0].name}`;
            document.getElementById('uploadForm').submit();
        }
    });

    async function toggleRecording() {
        if (!isRecording) {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                mediaRecorder = new MediaRecorder(stream);
                audioChunks = [];
                
                mediaRecorder.ondataavailable = event => {
                    audioChunks.push(event.data);
                };
                
                mediaRecorder.onstop = () => {
                    const audioBlob = new Blob(audioChunks, { type: 'audio/wav' });
                    recognizeSpeech(audioBlob);
                };
                
                mediaRecorder.start();
                isRecording = true;
                document.getElementById('recordBtn').classList.add('recording');
                document.getElementById('recordingStatus').textContent = '🔴 Recording... Click to stop';
            } catch (err) {
                alert('Microphone access denied');
            }
        } else {
            mediaRecorder.stop();
            mediaRecorder.stream.getTracks().forEach(track => track.stop());
            isRecording = false;
            document.getElementById('recordBtn').classList.remove('recording');
            document.getElementById('recordingStatus').textContent = 'Processing...';
        }
    }

    function recognizeSpeech(audioBlob) {
        const recognition = new (window.SpeechRecognition || window.webkitSpeechRecognition)();
        recognition.lang = 'en-US';
        recognition.interimResults = false;

        recognition.start();

        recognition.onresult = function(event) {
            const speechText = event.results[0][0].transcript;
            document.getElementById('userInput').value = speechText;
            sendMessage();
        };

        recognition.onerror = function(event) {
            alert('Speech recognition error: ' + event.error);
        };
    }

    // ===== DEEPSEEK API INTEGRATION =====
    async function sendMessage() {
        const input = document.getElementById("userInput");
        const message = input.value.trim();

        if (!message) return;

        addMessage(message, "user");
        input.value = "";

        const typingId = showTypingIndicator();

        try {
            // Your DeepSeek API key
            const API_KEY = 'AIzaSyC-7b5tjUHFIg-Her_-m6ISoPnbx2be1Jw';
            
            // DeepSeek API endpoint
            const response = await fetch("https://api.deepseek.com/v1/chat/completions", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Authorization": `Bearer ${API_KEY}`
                },
                body: JSON.stringify({
                    model: "deepseek-chat",
                    messages: [
                        {
                            role: "system",
                            content: "You are an expert engineering tutor specializing in all branches of engineering. Answer questions in detail with examples, formulas, code snippets, and practical applications. Be accurate, educational, and helpful."
                        },
                        {
                            role: "user",
                            content: message
                        }
                    ],
                    temperature: 0.7,
                    max_tokens: 2000,
                    top_p: 0.95,
                    frequency_penalty: 0,
                    presence_penalty: 0
                })
            });

            const data = await response.json();
            removeElement(typingId);

            if (data.choices && data.choices.length > 0) {
                const aiResponse = data.choices[0].message.content;
                addMessage(aiResponse, "ai");
                saveChat(message, aiResponse, "text");
            } else {
                console.error('API Response:', data);
                if (data.error) {
                    addMessage(`⚠️ API Error: ${data.error.message}. Using local knowledge base.`, "ai");
                } else {
                    addMessage("⚠️ AI could not generate an answer. Using local knowledge base.", "ai");
                }
                
                const fallbackResponse = getLocalResponse(message);
                addMessage(fallbackResponse, "ai");
                saveChat(message, fallbackResponse, "text");
            }

        } catch (error) {
            removeElement(typingId);
            console.error('DeepSeek API Error:', error);
            
            addMessage(`⚠️ Connection Error: ${error.message}. Using local knowledge base.`, "ai");
            
            const fallbackResponse = getLocalResponse(message);
            addMessage(fallbackResponse, "ai");
            saveChat(message, fallbackResponse, "text");
        }
    }

    // Keep your existing getLocalResponse function here
    function getLocalResponse(question) {
        question = question.toLowerCase();
        
        // Data Structures and Algorithms
        if (question.includes('quick sort') || question.includes('quicksort')) {
            return `📊 **Quick Sort Algorithm**

**Definition:** Quick Sort is a divide-and-conquer algorithm that picks an element as pivot and partitions the array around it.

**Algorithm Steps:**
1. Choose a pivot element (usually last element)
2. Partition: Rearrange array so smaller elements come before pivot, larger after
3. Recursively apply to sub-arrays

**Time Complexity:**
• Best/Average Case: O(n log n)
• Worst Case: O(n²) - when array already sorted

**Space Complexity:** O(log n)

**Python Implementation:**
\`\`\`python
def quick_sort(arr):
    if len(arr) <= 1:
        return arr
    pivot = arr[len(arr) // 2]
    left = [x for x in arr if x < pivot]
    middle = [x for x in arr if x == pivot]
    right = [x for x in arr if x > pivot]
    return quick_sort(left) + middle + quick_sort(right)

# Example
arr = [3, 6, 8, 10, 1, 2, 1]
sorted_arr = quick_sort(arr)
print(f"Sorted array: {sorted_arr}")
\`\`\`

**Example:** Sorting [3, 6, 8, 10, 1, 2, 1]
1. Choose pivot = 8
2. Left: [3, 6, 1, 2, 1]
3. Middle: [8]
4. Right: [10]
5. Recursively sort left and right

**Applications:**
• Database sorting
• Numerical computations
• Commercial computing

Need more details on any specific aspect? 🚀`;
        }
        
        // Binary Search
        else if (question.includes('binary search')) {
            return `🔍 **Binary Search Algorithm**

**Definition:** Binary Search is an efficient algorithm for finding an element in a sorted array by repeatedly dividing the search interval in half.

**Algorithm Steps:**
1. Start with the middle element
2. If target equals middle → return index
3. If target < middle → search left half
4. If target > middle → search right half
5. Repeat until found or interval empty

**Time Complexity:** O(log n)
**Space Complexity:** O(1) for iterative, O(log n) for recursive

**Python Implementation:**
\`\`\`python
def binary_search(arr, target):
    left, right = 0, len(arr) - 1
    
    while left <= right:
        mid = (left + right) // 2
        
        if arr[mid] == target:
            return mid
        elif arr[mid] < target:
            left = mid + 1
        else:
            right = mid - 1
    
    return -1  # Not found

# Example
arr = [1, 3, 5, 7, 9, 11, 13, 15]
target = 7
result = binary_search(arr, target)
print(f"Element {target} found at index: {result}")
\`\`\`

**Example Trace:** Searching for 7 in [1, 3, 5, 7, 9, 11, 13, 15]
1. left=0, right=7, mid=3 → arr[3]=7 ✓ Found!

**Applications:**
• Dictionary lookups
• Database indexing
• Debugging (git bisect)
• Mathematical computations

Want me to explain any other searching algorithm?`;
        }
        
        // Ohm's Law
        else if (question.includes('ohm')) {
            return `⚡ **Ohm's Law - Complete Guide**

**Definition:** Ohm's Law states that the current through a conductor between two points is directly proportional to the voltage across the two points.

**Formula:** V = I × R

Where:
• **V** = Voltage (volts) - Electrical potential difference
• **I** = Current (amperes) - Flow of electric charge
• **R** = Resistance (ohms) - Opposition to current flow

**Derived Formulas:**
• I = V / R
• R = V / I

**Power Formula:**
P = V × I = I² × R = V² / R

**Solved Examples:**

**Example 1:** Find current when voltage = 12V, resistance = 4Ω
I = V/R = 12/4 = **3A**

**Example 2:** Find voltage when current = 2A, resistance = 6Ω
V = I × R = 2 × 6 = **12V**

**Example 3:** Find resistance when voltage = 24V, current = 3A
R = V/I = 24/3 = **8Ω**

**Example 4:** Find power when V=12V, I=3A
P = V × I = 12 × 3 = **36W**

**Applications:**
• Circuit design and analysis
• Power supply sizing
• Troubleshooting electrical systems
• Battery calculations
• LED resistor selection

**Practical Circuit:**
\`\`\`
     +12V -----[R=4Ω]----- GND
              |
              I = 3A
\`\`\`

**Limitations:**
• Only applies to ohmic materials
• Temperature affects resistance
• Not applicable to non-linear devices

Need help with any specific circuit problem? 🔧`;
        }
        
        // Python
        else if (question.includes('python')) {
            return `🐍 **Python Programming - Complete Guide**

**What is Python?**
Python is a high-level, interpreted programming language known for its simplicity and readability. Created by Guido van Rossum in 1991.

**Key Features:**
• Easy to learn and read
• Extensive standard library
• Cross-platform compatibility
• Dynamic typing
• Object-oriented
• Great for beginners

**Basic Syntax:**

\`\`\`python
# Variables and Data Types
name = "John"           # String
age = 25                # Integer
height = 5.9            # Float
is_student = True       # Boolean

# Lists (arrays)
fruits = ["apple", "banana", "orange"]
fruits.append("mango")  # Add item
print(fruits[0])        # Access first item

# Loops
for fruit in fruits:
    print(fruit)

# While loop
count = 0
while count < 5:
    print(count)
    count += 1

# Functions
def add(a, b):
    """Add two numbers and return result"""
    return a + b

result = add(5, 3)
print(f"Sum: {result}")

# Classes
class Student:
    def __init__(self, name, age):
        self.name = name
        self.age = age
    
    def introduce(self):
        return f"Hi, I'm {self.name} and I'm {self.age} years old"

# Create object
student = Student("Alice", 20)
print(student.introduce())
\`\`\`

**Engineering Applications:**

1. **Data Science & Analysis**
\`\`\`python
import numpy as np
import pandas as pd

# NumPy for numerical computing
arr = np.array([1, 2, 3, 4, 5])
mean = np.mean(arr)
print(f"Mean: {mean}")

# Pandas for data manipulation
data = {'Name': ['John', 'Anna'], 'Age': [25, 30]}
df = pd.DataFrame(data)
print(df)
\`\`\`

2. **Mathematics Example**
\`\`\`python
import math

# Calculate area of circle
radius = 5
area = math.pi * radius ** 2
print(f"Area of circle with radius {radius}: {area:.2f}")

# Solve quadratic equation
a, b, c = 1, -5, 6
discriminant = b**2 - 4*a*c
if discriminant >= 0:
    root1 = (-b + math.sqrt(discriminant)) / (2*a)
    root2 = (-b - math.sqrt(discriminant)) / (2*a)
    print(f"Roots: {root1}, {root2}")
\`\`\`

3. **File Handling**
\`\`\`python
# Write to file
with open('data.txt', 'w') as file:
    file.write('Hello, Engineering!')

# Read from file
with open('data.txt', 'r') as file:
    content = file.read()
    print(content)
\`\`\`

**Common Libraries for Engineers:**
• **NumPy** - Numerical computing
• **SciPy** - Scientific computing
• **Matplotlib** - Data visualization
• **Pandas** - Data analysis
• **SymPy** - Symbolic mathematics
• **TensorFlow/PyTorch** - Machine learning

Want to learn more about any specific Python topic? 💻`;
        }
        
        // Thermodynamics
        else if (question.includes('thermo')) {
            return `🔥 **Thermodynamics - Complete Guide**

**Definition:** Thermodynamics is the branch of physics that deals with heat, work, temperature, and energy. It describes how thermal energy converts to and from other forms.

**The Four Laws of Thermodynamics:**

**1. Zeroth Law**
• If two systems are in thermal equilibrium with a third, they're in equilibrium with each other
• Forms the basis for temperature measurement

**2. First Law (Energy Conservation)**
ΔU = Q - W

Where:
• ΔU = Change in internal energy
• Q = Heat added to system
• W = Work done by system

**3. Second Law (Entropy)**
• Heat cannot spontaneously flow from cold to hot
• Entropy of isolated universe always increases
• Defines direction of processes

**4. Third Law**
• As temperature approaches absolute zero, entropy approaches minimum
• Absolute zero (-273.15°C) is unattainable

**Key Concepts:**

**Internal Energy (U)**
• Total energy stored in a system
• Sum of kinetic and potential energies

**Enthalpy (H)**
H = U + PV
• Total heat content of system
• Useful for constant pressure processes

**Entropy (S)**
• Measure of disorder or randomness
• ΔS = Q/T for reversible processes

**Important Formulas:**

**Ideal Gas Law:**
PV = nRT

**Work done by gas:**
W = P × ΔV (constant pressure)

**Heat transfer:**
Q = m × c × ΔT

**Efficiency of Heat Engine:**
η = 1 - (T₂/T₁)

**Solved Examples:**

**Example 1:** Work done by expanding gas
Given: P = 100 kPa, ΔV = 0.5 m³
W = P × ΔV = 100 × 0.5 = **50 kJ**

**Example 2:** Heat required to raise temperature
Given: m = 2 kg water, c = 4186 J/kg·K, ΔT = 10°C
Q = m × c × ΔT = 2 × 4186 × 10 = **83.72 kJ**

**Example 3:** Carnot engine efficiency
Given: T₁ = 500K, T₂ = 300K
η = 1 - (300/500) = 1 - 0.6 = **0.4 or 40%**

**Applications:**
• Heat engines (car engines, power plants)
• Refrigerators and heat pumps
• HVAC systems
• Rocket propulsion
• Material science
• Chemical reactions

**Common Processes:**
• Isothermal (constant temperature)
• Isobaric (constant pressure)
• Isochoric (constant volume)
• Adiabatic (no heat transfer)

Need help with any specific thermodynamic concept? 🔧`;
        }
        
        // Civil Engineering - Foundations
        else if (question.includes('foundation') || question.includes('civil')) {
            return `🏗️ **Types of Foundations in Civil Engineering**

**Definition:** A foundation is the lowest part of a structure that transfers loads from the building to the soil below.

**Types of Foundations:**

## 1. SHALLOW FOUNDATIONS

### A. Isolated Spread Footing
• Used for individual columns
• Most common and economical
• Suitable for good soil conditions

### B. Combined Footing
• Supports two or more columns
• Used when columns are close
• Saves construction cost

### C. Strip Footing
• Continuous under walls
• Used for load-bearing walls
• Distributes load evenly

### D. Raft/Mat Foundation
• Covers entire building area
• Used for poor soil conditions
• Reduces differential settlement

## 2. DEEP FOUNDATIONS

### A. Pile Foundation
• Long, slender columns driven into ground
• Types:
  - End-bearing piles
  - Friction piles
  - Combination piles
• Materials: Concrete, steel, timber

### B. Pier Foundation
• Larger diameter than piles
• Drilled or excavated
• Used for heavy loads

### C. Caisson Foundation
• Watertight structure
• Used for underwater construction
• Common in bridges and ports

**Factors Affecting Foundation Choice:**
• Soil type and bearing capacity
• Building load
• Water table level
• Adjacent structures
• Cost and time
• Climate conditions

**Soil Bearing Capacity Examples:**
• Hard rock: 300-400 kN/m²
• Soft rock: 150-200 kN/m²
• Sand: 100-300 kN/m²
• Clay: 50-150 kN/m²

**Settlement Calculation:**
S = (q × B × I) / E

Where:
• S = Settlement
• q = Applied pressure
• B = Foundation width
• I = Influence factor
• E = Modulus of elasticity

**Common Problems and Solutions:**

1. **Uneven Settlement**
   → Use raft foundation or piles

2. **High Water Table**
   → Dewatering or waterproofing

3. **Expansive Soil**
   → Under-reamed piles or soil replacement

**Famous Examples:**
• Burj Khalifa - Deep pile foundation
• Golden Gate Bridge - Caisson foundations
• Leaning Tower of Pisa - Soil settlement issue

Need information about any specific foundation type? 🚧`;
        }
        
        // Default response
        else {
            return `I understand you're asking about "${question.substring(0, 100)}..."

I can help you with these engineering topics:

**Computer Science:**
• Data Structures (Arrays, Linked Lists, Trees, Graphs)
• Algorithms (Sorting, Searching, Dynamic Programming)
• Programming (Python, Java, C++)

**Electronics:**
• Ohm's Law
• Kirchhoff's Laws
• Circuit analysis
• Digital logic

**Mechanical:**
• Thermodynamics laws
• Fluid mechanics
• Heat transfer
• Machine design

**Civil:**
• Foundation types
• Structural analysis
• Building materials
• Construction methods

**Please ask a specific question like:**
• "Explain Quick Sort algorithm"
• "What is Ohm's Law?"
• "Write Python code for binary search"
• "Explain laws of thermodynamics"
• "What are the types of foundations?"

I'll give you a detailed, accurate answer with examples and formulas! 🚀`;
        }
    }

    function addMessage(text, sender) {
        const container = document.getElementById('chatContainer');
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${sender}-message`;
        
        const contentDiv = document.createElement('div');
        contentDiv.className = 'message-content';
        
        // Format text
        let formattedText = text
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/```(.*?)```/gs, '<pre><code>$1</code></pre>')
            .replace(/`(.*?)`/g, '<code>$1</code>')
            .replace(/\n/g, '<br>');
        
        contentDiv.innerHTML = formattedText;
        
        const timeDiv = document.createElement('div');
        timeDiv.className = 'message-meta';
        timeDiv.innerHTML = `${new Date().toLocaleTimeString()}`;
        contentDiv.appendChild(timeDiv);
        
        messageDiv.appendChild(contentDiv);
        container.appendChild(messageDiv);
        container.scrollTop = container.scrollHeight;
    }

    function showTypingIndicator() {
        const container = document.getElementById('chatContainer');
        const id = 'typing-' + Date.now();
        const typingDiv = document.createElement('div');
        typingDiv.className = 'message ai-message';
        typingDiv.id = id;
        
        const contentDiv = document.createElement('div');
        contentDiv.className = 'typing-indicator';
        contentDiv.innerHTML = '<span class="typing-dot"></span><span class="typing-dot"></span><span class="typing-dot"></span>';
        
        typingDiv.appendChild(contentDiv);
        container.appendChild(typingDiv);
        container.scrollTop = container.scrollHeight;
        
        return id;
    }

    function removeElement(id) {
        const element = document.getElementById(id);
        if (element) element.remove();
    }

    function setQuestion(question) {
        document.getElementById('userInput').value = question;
        sendMessage();
    }

    function setBranch(branch) {
        currentBranch = branch;
        const branchNames = {
            'CSE': 'Computer Science',
            'ECE': 'Electronics',
            'ME': 'Mechanical',
            'CE': 'Civil',
            'EEE': 'Electrical',
            'AIML': 'AI/ML',
            'AIDS': 'Data Science'
        };
        addMessage(`I want to learn about ${branchNames[branch]} Engineering`, 'user');
        
        setTimeout(() => {
            addMessage(`Great! Ask me anything about ${branchNames[branch]}. I can explain concepts, formulas, examples, and applications!`, 'ai');
        }, 500);
    }

    function saveChat(message, response, type) {
        fetch('save_chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                message: message,
                response: response,
                type: type,
                branch: currentBranch
            })
        }).catch(error => console.error('Error saving chat:', error));
    }
    </script>
    <script> window.chtlConfig = { chatbotId: "6586829846" } </script>
    <script async data-id="6586829846" id="chtl-script" type="text/javascript" src="https://chatling.ai/js/embed.js"></script>
</body>
</html>
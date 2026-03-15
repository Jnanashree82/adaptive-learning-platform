<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user preferences
$sql = "SELECT accessibility_profile, text_size, high_contrast, focus_mode FROM users WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['user_id']]);
$user_prefs = $stmt->fetch();

// Get all branches with subject counts
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

// Check if ADHD mode is active
$adhd_mode = ($user_prefs['accessibility_profile'] == 'adhd' || $user_prefs['focus_mode'] == 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Engineering Branches - NeuroLearn</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/adaptive.css">
    <link rel="stylesheet" href="css/accessibility.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/opendyslexic@0.1.0/opendyslexic.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- JavaScript -->
    <script src="js/global-accessibility.js" defer></script>
    <script src="js/accessibility.js" defer></script>
    
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
            transition: all 0.3s ease;
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
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
            transition: all 0.3s ease;
        }
        
        /* ADHD Mode - Sidebar hidden */
        body.adhd-mode .sidebar {
            transform: translateX(-100%);
            opacity: 0;
            pointer-events: none;
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
            list-style: none;
            padding: 20px 0;
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
        
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
            min-height: 100vh;
            transition: all 0.3s ease;
            position: relative;
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
        
        /* ===== HEADER SECTION ===== */
        .header-section {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            padding: 40px;
            border-radius: 16px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .header-section h1 {
            font-size: 36px;
            margin-bottom: 10px;
        }
        
        .header-section p {
            font-size: 16px;
            opacity: 0.95;
        }
        
        /* ===== BRANCHES GRID ===== */
        .branches-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            padding: 20px 0;
        }
        
        .branch-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }
        
        .branch-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(99, 102, 241, 0.2);
            border-color: #6366f1;
        }
        
        .branch-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #6366f1, #8b5cf6);
        }
        
        .branch-icon {
            font-size: 48px;
            margin-bottom: 15px;
            transition: transform 0.3s ease;
        }
        
        .branch-card:hover .branch-icon {
            transform: scale(1.1);
        }
        
        .branch-name {
            font-size: 24px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 5px;
        }
        
        .branch-code {
            color: #6366f1;
            font-weight: 600;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .branch-description {
            color: #6b7280;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .branch-stats {
            display: flex;
            gap: 20px;
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
        }
        
        .stat-item {
            text-align: center;
            flex: 1;
        }
        
        .stat-value {
            font-size: 20px;
            font-weight: bold;
            color: #1f2937;
        }
        
        .stat-label {
            font-size: 12px;
            color: #6b7280;
        }
        
        /* Make all text elements highlightable */
        .branch-name, .branch-code, .branch-description, .stat-value, .stat-label {
            display: inline-block;
            transition: all 0.2s ease;
            padding: 2px 4px;
            border-radius: 4px;
        }
        
        .branch-name:hover, .branch-code:hover, .branch-description:hover, .stat-value:hover, .stat-label:hover {
            background: rgba(139, 92, 246, 0.2);
            transform: scale(1.02);
            cursor: pointer;
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
                padding: 20px;
            }
            
            .branches-grid {
                grid-template-columns: 1fr;
            }
            
            .adhd-panel {
                width: 90%;
                left: 5%;
                right: 5%;
            }
            
            .header-section h1 {
                font-size: 28px;
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
            <i class="fas fa-info-circle"></i> Only content inside the roller is visible
        </div>
    </div>

    <div class="dashboard">
        <nav class="sidebar">
            <div class="sidebar-header">
                <h2>🧠 NeuroLearn</h2>
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
        </nav>

        <main class="main-content">
            <div class="container">
                <div class="header-section">
                    <h1>📚 Engineering Branches</h1>
                    <p><?php echo $adhd_mode ? '🎯 Focus Mode Active - Move mouse to control the focus roller. Click the <i class="fas fa-compass"></i> button for navigation.' : 'Select your engineering branch to access semester-wise notes and study materials'; ?></p>
                </div>

                <div class="branches-grid">
                    <?php foreach($branches as $branch): ?>
                    <div class="branch-card" onclick="window.location.href='branch.php?id=<?php echo $branch['id']; ?>'">
                        <div class="branch-icon"><?php echo htmlspecialchars($branch['icon'] ?? '📚'); ?></div>
                        <div class="branch-name highlightable"><?php echo htmlspecialchars($branch['name']); ?></div>
                        <div class="branch-code highlightable"><?php echo htmlspecialchars($branch['code']); ?></div>
                        <div class="branch-description highlightable"><?php echo htmlspecialchars($branch['description'] ?? 'Engineering branch with comprehensive study materials'); ?></div>
                        <div class="branch-stats">
                            <div class="stat-item">
                                <div class="stat-value highlightable"><?php echo $branch['subject_count'] ?: 0; ?></div>
                                <div class="stat-label highlightable">Subjects</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value highlightable"><?php echo $branch['note_count'] ?: 0; ?></div>
                                <div class="stat-label highlightable">Notes</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value highlightable">8</div>
                                <div class="stat-label highlightable">Semesters</div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // ===== ADHD FOCUS TUNNEL =====
        let rollerActive = <?php echo $adhd_mode ? 'true' : 'false'; ?>;
        let rollerHeight = 120;
        let rollerTop = window.innerHeight / 2 - rollerHeight / 2;
        let blurIntensity = 8;
        let highlightColor = '#8b5cf6';
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            if (rollerActive) {
                initFocusTunnel();
            }
            
            // Add highlightable class to all text elements
            document.querySelectorAll('.branch-name, .branch-code, .branch-description, .stat-value, .stat-label').forEach(el => {
                el.classList.add('highlightable');
            });
            
            // Close floating menu when clicking outside
            document.addEventListener('click', function(event) {
                const fabMenu = document.getElementById('fabMenu');
                const fabMain = document.getElementById('fabMain');
                
                if (fabMain && fabMenu && !fabMain.contains(event.target) && !fabMenu.contains(event.target)) {
                    fabMenu.classList.remove('show');
                }
            });
        });
        
        // Toggle floating action menu
        function toggleFabMenu() {
            const menu = document.getElementById('fabMenu');
            menu.classList.toggle('show');
        }
        
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
            // Update CSS variables for overlays
            document.documentElement.style.setProperty('--roller-top', rollerTop + 'px');
            document.documentElement.style.setProperty('--roller-bottom', (rollerTop + rollerHeight) + 'px');
            document.documentElement.style.setProperty('--roller-height', rollerHeight + 'px');
            
            // Update blur intensity
            document.querySelectorAll('.blur-overlay').forEach(el => {
                el.style.backdropFilter = `blur(${blurIntensity}px)`;
                el.style.backgroundColor = `rgba(0, 0, 0, 0.85)`;
            });
        }
        
        // Move roller with mouse
        document.addEventListener('mousemove', function(e) {
            if (rollerActive) {
                let newTop = e.clientY - rollerHeight / 2;
                
                // Keep within bounds
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
            
            // Keep roller within bounds
            rollerTop = Math.min(rollerTop, window.innerHeight - rollerHeight);
            document.getElementById('focusRoller').style.top = rollerTop + 'px';
            
            updateOverlays();
        }
        
        function updateRollerHeight(value) {
            rollerHeight = parseInt(value);
            document.getElementById('focusRoller').style.height = rollerHeight + 'px';
            document.getElementById('heightValue').textContent = rollerHeight + 'px';
            
            // Keep roller within bounds
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
            
            // Update roller border color
            document.getElementById('focusRoller').style.borderColor = color;
            
            // Update highlight color for hover effects
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
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (rollerActive) {
                rollerTop = Math.min(rollerTop, window.innerHeight - rollerHeight);
                document.getElementById('focusRoller').style.top = rollerTop + 'px';
                updateOverlays();
            }
        });
    </script>
</body>
</html>
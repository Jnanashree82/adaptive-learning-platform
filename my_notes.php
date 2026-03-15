<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user preferences
$stmt = $pdo->prepare("SELECT accessibility_profile, text_size, high_contrast, focus_mode, theme_mode FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_prefs = $stmt->fetch();

// Get all notes with subject and branch information
$sql = "SELECT n.*, s.subject_name, s.subject_code, b.name as branch_name, b.icon as branch_icon,
               sem.semester_number
        FROM notes n
        JOIN subjects s ON n.subject_id = s.id
        JOIN semesters sem ON s.semester_id = sem.id
        JOIN branches b ON sem.branch_id = b.id
        ORDER BY n.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$notes = $stmt->fetchAll();

// Calculate statistics
$total_notes = count($notes);
$total_views = array_sum(array_column($notes, 'view_count'));
$total_downloads = array_sum(array_column($notes, 'download_count'));

// Check if ADHD mode is active
$adhd_mode = ($user_prefs['accessibility_profile'] == 'adhd' || $user_prefs['focus_mode'] == 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Notes - NeuroLearn ADHD Focus Mode</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/adaptive.css">
    <link rel="stylesheet" href="css/accessibility.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Dyslexic&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        /* ===== MAIN LAYOUT ===== */
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            transition: all 0.3s ease;
        }
        
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
        
        /* ADHD Mode - Sidebar hidden, main content full width */
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
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
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
        
        /* Blur overlays - everything outside the roller is blurred */
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
        
        /* Focus Roller - the clear window */
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
        
        /* Roller inner glow */
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
        
        /* Roller handles for manual adjustment */
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
        
        /* ===== WORD HIGHLIGHTING INSIDE ROLLER ===== */
        .highlightable {
            transition: all 0.2s ease;
            position: relative;
            z-index: 1;
        }
        
        .highlightable:hover {
            background: rgba(139, 92, 246, 0.3) !important;
            transform: scale(1.02);
            box-shadow: 0 2px 8px rgba(139, 92, 246, 0.4);
            border-radius: 4px;
            color: #000 !important;
            font-weight: 500;
        }
        
        /* When roller is active, only elements inside get hover effect */
        body.adhd-mode .focus-roller.active ~ .main-content .highlightable {
            transition: all 0.2s ease;
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
        
        /* ===== PAGE HEADER ===== */
        .page-header {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .page-header h1 {
            font-size: 28px;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .page-header p {
            color: #6b7280;
        }
        
        /* ===== STATS CARDS ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background: linear-gradient(135deg, #667eea20, #764ba220);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: #667eea;
        }
        
        .stat-info h3 {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
        }
        
        .stat-info p {
            color: #64748b;
            font-size: 14px;
        }
        
        /* ===== NOTES GRID ===== */
        .notes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 25px;
        }
        
        .note-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: all 0.3s;
            border: 2px solid transparent;
            position: relative;
        }
        
        .note-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(102,126,234,0.15);
            border-color: #667eea;
        }
        
        /* Make all text elements highlightable */
        .note-title, .note-meta span, .file-info, .note-stats span, .note-type {
            display: inline-block;
            transition: all 0.2s ease;
            padding: 2px 4px;
            border-radius: 4px;
        }
        
        .note-title:hover, .note-meta span:hover, .file-info:hover, .note-stats span:hover {
            background: rgba(139, 92, 246, 0.2);
            transform: scale(1.02);
            cursor: pointer;
        }
        
        .note-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .note-type {
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .type-theory { background: #dbeafe; color: #1e40af; }
        .type-numerical { background: #dcfce7; color: #166534; }
        .type-formula { background: #fef9c3; color: #854d0e; }
        .type-previous_paper { background: #f3e8ff; color: #6b21a8; }
        
        .note-title {
            font-size: 20px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 12px;
        }
        
        .note-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 15px;
            font-size: 13px;
            color: #64748b;
        }
        
        .file-info {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            font-size: 13px;
            border-left: 4px solid #667eea;
        }
        
        .note-stats {
            display: flex;
            gap: 20px;
            margin: 15px 0;
            padding: 10px 0;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .action-buttons {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }
        
        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-view {
            background: #667eea;
            color: white;
        }
        
        .btn-download {
            background: #10b981;
            color: white;
        }
        
        .btn-adhd {
            background: #8b5cf6;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        /* ===== ADHD STATUS ===== */
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
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .adhd-panel {
                width: 90%;
                left: 5%;
                right: 5%;
            }
        }
    </style>
</head>
<body class="<?php echo $adhd_mode ? 'adhd-mode' : ''; ?>" id="mainBody">
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
            <label class="control-label"><i class="fas fa-highlighter"></i> Highlight Color</label>
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
    <!-- ADD THIS LINE - PDF Analyzer -->
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
                <div class="page-header">
                    <h1><i class="fas fa-sticky-note"></i> My Notes</h1>
                    <p><?php echo $adhd_mode ? '🎯 Focus Mode Active - Move mouse to control the focus roller. Only text inside the roller is visible!' : 'Access all your study materials'; ?></p>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
                        <div class="stat-info">
                            <h3><?php echo $total_notes; ?></h3>
                            <p>Total Notes</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-eye"></i></div>
                        <div class="stat-info">
                            <h3><?php echo $total_views; ?></h3>
                            <p>Total Views</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-download"></i></div>
                        <div class="stat-info">
                            <h3><?php echo $total_downloads; ?></h3>
                            <p>Total Downloads</p>
                        </div>
                    </div>
                </div>

                <?php if (empty($notes)): ?>
                    <div style="text-align: center; padding: 60px; background: white; border-radius: 16px;">
                        <i class="fas fa-folder-open" style="font-size: 64px; color: #cbd5e1; margin-bottom: 20px;"></i>
                        <h3 style="color: #1e293b;">No Notes Available</h3>
                        <p style="color: #6b7280;">Browse branches to find study materials</p>
                        <a href="branches.php" style="display: inline-block; margin-top: 20px; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 8px;">Browse Branches</a>
                    </div>
                <?php else: ?>
                    <div class="notes-grid">
                        <?php foreach ($notes as $note): 
                            $file_ext = pathinfo($note['file_path'], PATHINFO_EXTENSION);
                            $file_icon = 'fa-file-pdf';
                            $icon_color = '#ef4444';
                            
                            if ($file_ext == 'txt') {
                                $file_icon = 'fa-file-lines';
                                $icon_color = '#3b82f6';
                            } elseif (in_array($file_ext, ['jpg', 'jpeg', 'png'])) {
                                $file_icon = 'fa-file-image';
                                $icon_color = '#10b981';
                            } elseif (in_array($file_ext, ['doc', 'docx'])) {
                                $file_icon = 'fa-file-word';
                                $icon_color = '#2563eb';
                            }
                        ?>
                        <div class="note-card">
                            <div class="note-header">
                                <span class="note-type type-<?php echo $note['note_type'] ?? 'theory'; ?>">
                                    <?php echo ucfirst($note['note_type'] ?? 'Theory'); ?>
                                </span>
                                <span style="font-size: 12px; color: #64748b;">
                                    <?php echo date('M d, Y', strtotime($note['created_at'])); ?>
                                </span>
                            </div>
                            
                            <h3 class="note-title highlightable"><?php echo htmlspecialchars($note['title']); ?></h3>
                            
                            <div class="note-meta">
                                <span class="highlightable"><i class="fas fa-book"></i> <?php echo htmlspecialchars($note['subject_name']); ?></span>
                                <span class="highlightable"><i class="fas fa-code-branch"></i> <?php echo htmlspecialchars($note['branch_name']); ?></span>
                                <span class="highlightable"><i class="fas fa-layer-group"></i> Sem <?php echo $note['semester_number']; ?></span>
                            </div>
                            
                            <div class="file-info highlightable">
                                <i class="fas <?php echo $file_icon; ?>" style="color: <?php echo $icon_color; ?>;"></i>
                                <strong><?php echo basename($note['file_path']); ?></strong>
                            </div>
                            
                            <div class="note-stats">
                                <span class="highlightable"><i class="far fa-eye"></i> <?php echo $note['view_count']; ?> views</span>
                                <span class="highlightable"><i class="fas fa-download"></i> <?php echo $note['download_count']; ?> downloads</span>
                            </div>
                            
                            <div class="action-buttons">
                                <a href="view_note.php?id=<?php echo $note['id']; ?>" class="btn btn-view" target="_blank">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="download_note.php?id=<?php echo $note['id']; ?>" class="btn btn-download">
                                    <i class="fas fa-download"></i> Download
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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
            document.querySelectorAll('.note-title, .note-meta span, .file-info, .note-stats span').forEach(el => {
                el.classList.add('highlightable');
            });
        });
        
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
        <!-- Main Content -->
    <main class="main-content">
        ...
    </main>

    <?php include 'includes/voice-agent.php'; ?>

    <script>
    // ===== TEXT SIZE MANAGEMENT =====
    ...
    </script>
    <script> window.chtlConfig = { chatbotId: "6586829846" } </script>
    <script async data-id="6586829846" id="chtl-script" type="text/javascript" src="https://chatling.ai/js/embed.js"></script>
</body>
</html>
</body>
</html>
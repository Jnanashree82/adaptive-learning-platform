<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get current settings
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Update settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $profile = $_POST['accessibility_profile'] ?? 'standard';
    $text_size = $_POST['text_size'] ?? 100;
    $high_contrast = isset($_POST['high_contrast']) ? 1 : 0;
    $focus_mode = isset($_POST['focus_mode']) ? 1 : 0;
    $theme_mode = $_POST['theme_mode'] ?? 'light';
    
    $sql = "UPDATE users SET 
            accessibility_profile = ?,
            text_size = ?,
            high_contrast = ?,
            focus_mode = ?,
            theme_mode = ?
            WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$profile, $text_size, $high_contrast, $focus_mode, $theme_mode, $_SESSION['user_id']]);
    
    // Update session
    $_SESSION['accessibility'] = [
        'profile' => $profile,
        'text_size' => $text_size,
        'high_contrast' => $high_contrast,
        'focus_mode' => $focus_mode,
        'theme_mode' => $theme_mode
    ];
    
    $success = "Settings updated successfully!";
    
    // Refresh user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accessibility Settings - NeuroLearn</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/accessibility.css">
    <link rel="stylesheet" href="css/modes.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/opendyslexic@0.1.0/opendyslexic.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f1f5f9;
            overflow-x: hidden;
            transition: all 0.3s ease;
        }

        /* Body classes for different profiles */
        body.dyslexia-profile {
            font-family: 'OpenDyslexic', 'Comic Sans MS', sans-serif !important;
            letter-spacing: 0.12ch;
            line-height: 1.8;
        }

        body.adhd-profile {
            background: #ffffff;
        }

        body.adhd-profile .sidebar {
            transform: translateX(-100%);
            opacity: 0;
            pointer-events: none;
        }

        body.adhd-profile .main-content {
            margin-left: 0;
        }

        body.high-contrast-mode {
            filter: contrast(1.2) brightness(1.1);
        }

        body.high-contrast-mode .settings-container {
            background: #000 !important;
            color: #ff0 !important;
            border: 2px solid #ff0;
        }

        body.high-contrast-mode .settings-section {
            border-color: #ff0;
        }

        body.theme-dark {
            background: #1a1a1a;
        }

        body.theme-dark .settings-container {
            background: #2d2d2d;
            color: #e0e0e0;
        }

        body.theme-dark .settings-section {
            border-color: #404040;
        }

        body.theme-dark .profile-card {
            background: #333;
            color: #fff;
        }

        body.theme-eye-comfort {
            background: #fbf7e9;
        }

        body.theme-eye-comfort .settings-container {
            background: #fff9e6;
        }

        /* Dashboard Layout */
        .dashboard {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: #000;
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 100;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header h2 {
            color: white;
            font-size: 1.5rem;
        }

        .nav-menu {
            list-style: none;
            padding: 1rem 0;
        }

        .nav-menu li {
            margin: 0.25rem 1rem;
        }

        .nav-menu li a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s;
            gap: 0.75rem;
        }

        .nav-menu li a i {
            width: 1.25rem;
        }

        .nav-menu li:hover a {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .nav-menu li.active a {
            background: rgba(255,255,255,0.15);
            color: white;
        }

        .nav-menu li.active a i {
            color: white;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 2rem;
            transition: all 0.3s ease;
        }

        /* Floating Dashboard Button - NEW! */
        .floating-dashboard-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            z-index: 1001;
            transition: all 0.3s ease;
            animation: float 3s ease-in-out infinite;
            border: 3px solid white;
        }

        .floating-dashboard-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.6);
        }

        .floating-dashboard-btn .tooltip {
            position: absolute;
            left: 70px;
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
            font-weight: 500;
        }

        .floating-dashboard-btn:hover .tooltip {
            opacity: 1;
            visibility: visible;
            left: 80px;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        /* Show floating button only in ADHD mode */
        body.adhd-profile .floating-dashboard-btn {
            display: flex;
        }

        /* Settings Container */
        .settings-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }
        
        .settings-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .settings-header h1 {
            font-size: 36px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }
        
        .settings-header p {
            color: #6b7280;
            font-size: 16px;
        }
        
        .settings-section {
            margin-bottom: 40px;
            padding: 30px;
            border: 2px solid #f0f0f0;
            border-radius: 20px;
            transition: all 0.3s ease;
        }
        
        .settings-section:hover {
            border-color: #667eea;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.1);
        }
        
        .settings-section h3 {
            color: #1f2937;
            margin-bottom: 20px;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .settings-section h3 span {
            font-size: 28px;
        }
        
        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .profile-card {
            background: #f8fafc;
            border: 3px solid #e2e8f0;
            border-radius: 16px;
            padding: 30px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }
        
        .profile-card:hover::before {
            transform: translateX(0);
        }
        
        .profile-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .profile-card.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, #f0f4ff 0%, #e6e9ff 100%);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .profile-card.selected::after {
            content: '✓';
            position: absolute;
            top: 10px;
            right: 10px;
            width: 30px;
            height: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            animation: popIn 0.3s ease;
        }
        
        @keyframes popIn {
            0% { transform: scale(0); }
            80% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        
        .profile-icon {
            font-size: 48px;
            margin-bottom: 15px;
            transition: transform 0.3s ease;
        }
        
        .profile-card:hover .profile-icon {
            transform: scale(1.1);
        }
        
        .profile-card h4 {
            font-size: 20px;
            color: #1f2937;
            margin-bottom: 10px;
        }
        
        .profile-card p {
            color: #6b7280;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .profile-badge {
            display: inline-block;
            padding: 5px 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
        }
        
        .profile-card.selected .profile-badge {
            opacity: 1;
            transform: translateY(0);
        }
        
        .feature-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 15px;
            justify-content: center;
        }
        
        .feature-tag {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .live-preview {
            margin-top: 30px;
            padding: 25px;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 16px;
            border: 2px dashed #667eea;
        }
        
        .preview-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            color: #1f2937;
            font-size: 18px;
        }
        
        .preview-title span {
            font-size: 24px;
        }
        
        #previewBox {
            background: white;
            padding: 30px;
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        
        #previewBox.dyslexia-preview {
            font-family: 'OpenDyslexic', 'Comic Sans MS', sans-serif !important;
            letter-spacing: 0.12em !important;
            line-height: 1.8 !important;
            background: #fef9e7 !important;
            color: #2c3e50 !important;
        }
        
        #previewBox.adhd-preview {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-left: 4px solid #6366f1;
        }
        
        #previewBox.adhd-preview .preview-chunk {
            background: #f8f9fa;
            padding: 15px;
            margin: 15px 0;
            border-radius: 10px;
            animation: fadeIn 0.5s ease;
        }
        
        #previewBox.high-contrast-preview {
            background: #000000 !important;
            color: #ffff00 !important;
            border: 2px solid #ffff00 !important;
        }
        
        .slider-container {
            margin: 20px 0;
        }
        
        .slider {
            width: 100%;
            height: 8px;
            border-radius: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            outline: none;
            -webkit-appearance: none;
        }
        
        .slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 25px;
            height: 25px;
            background: white;
            border: 2px solid #667eea;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
            transition: all 0.2s ease;
        }
        
        .slider::-webkit-slider-thumb:hover {
            transform: scale(1.2);
            background: #667eea;
        }
        
        .size-display {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            color: #6b7280;
            font-size: 14px;
        }
        
        #sizeValue {
            font-weight: bold;
            color: #667eea;
            font-size: 18px;
        }
        
        .toggle-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            background: #f8fafc;
            border-radius: 12px;
            margin: 10px 0;
            transition: all 0.3s ease;
        }
        
        .toggle-item:hover {
            background: #f0f4ff;
        }
        
        .toggle-info h4 {
            color: #1f2937;
            margin-bottom: 5px;
        }
        
        .toggle-info p {
            color: #6b7280;
            font-size: 14px;
        }
        
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .3s;
            border-radius: 34px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .3s;
            border-radius: 50%;
        }
        
        input:checked + .toggle-slider {
            background: linear-gradient(90deg, #667eea, #764ba2);
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }
        
        .theme-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        
        .theme-card {
            cursor: pointer;
            text-align: center;
            padding: 20px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            transition: all 0.3s;
            position: relative;
        }
        
        .theme-card.selected {
            border-color: #4361ee !important;
            box-shadow: 0 10px 20px rgba(67, 97, 238, 0.2);
            transform: translateY(-2px);
        }
        
        .theme-card.selected::after {
            content: '✓';
            position: absolute;
            top: 10px;
            right: 10px;
            width: 24px;
            height: 24px;
            background: #4361ee;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        
        .save-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .save-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .success-message {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            animation: slideDown 0.5s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOutRight {
            to { transform: translateX(100%); opacity: 0; }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .settings-container {
                padding: 20px;
            }
            
            .profile-grid {
                grid-template-columns: 1fr;
            }
            
            .theme-grid {
                grid-template-columns: 1fr;
            }
            
            .floating-dashboard-btn {
                width: 50px;
                height: 50px;
                font-size: 20px;
                top: 15px;
                left: 15px;
            }
        }
    </style>
</head>
<body data-profile="<?php echo $user['accessibility_profile'] ?? 'standard'; ?>"
      data-text-size="<?php echo $user['text_size'] ?? 100; ?>"
      data-high-contrast="<?php echo $user['high_contrast'] ?? 0; ?>"
      data-focus-mode="<?php echo $user['focus_mode'] ?? 0; ?>"
      data-theme="<?php echo $user['theme_mode'] ?? 'light'; ?>"
      class="<?php 
        echo ($user['accessibility_profile'] == 'dyslexia' ? 'dyslexia-profile ' : '') . 
             ($user['accessibility_profile'] == 'adhd' ? 'adhd-profile ' : '') .
             ($user['high_contrast'] ? 'high-contrast-mode ' : '') .
             'theme-' . ($user['theme_mode'] ?? 'light');
      ?>">
      
    <!-- Floating Dashboard Button - Shows only in ADHD mode -->
    <div class="floating-dashboard-btn" onclick="window.location.href='dashboard.php'">
        <i class="fas fa-home"></i>
        <span class="tooltip">Back to Dashboard</span>
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
            <div class="settings-container">
                <div class="settings-header">
                    <h1>⚙️ Accessibility Settings</h1>
                    <p>Customize your learning experience based on your needs</p>
                </div>
                
                <?php if (isset($success)): ?>
                    <div class="success-message">
                        <span>✅</span>
                        <span><?php echo $success; ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" id="settingsForm">
                    <div class="settings-section">
                        <h3>
                            <span>👤</span>
                            Learning Profile
                        </h3>
                        <p style="color: #6b7280; margin-bottom: 25px;">Choose the profile that best matches how you learn</p>
                        
                        <div class="profile-grid">
                            <!-- Standard Profile -->
                            <div class="profile-card <?php echo $user['accessibility_profile'] == 'standard' ? 'selected' : ''; ?>" 
                                 onclick="selectProfile('standard')"
                                 data-profile="standard">
                                <div class="profile-icon">📚</div>
                                <h4>Standard</h4>
                                <p>Default reading experience with clean layout and regular spacing</p>
                                <div class="feature-tags">
                                    <span class="feature-tag">Regular font</span>
                                    <span class="feature-tag">Normal spacing</span>
                                    <span class="feature-tag">Standard layout</span>
                                </div>
                                <div class="profile-badge">Active</div>
                            </div>
                            
                            <!-- Dyslexia-Friendly Profile -->
                            <div class="profile-card <?php echo $user['accessibility_profile'] == 'dyslexia' ? 'selected' : ''; ?>" 
                                 onclick="selectProfile('dyslexia')"
                                 data-profile="dyslexia">
                                <div class="profile-icon">🔤</div>
                                <h4>Dyslexia-Friendly</h4>
                                <p>Specialized font, increased letter spacing, and high contrast for easier reading</p>
                                <div class="feature-tags">
                                    <span class="feature-tag">OpenDyslexic font</span>
                                    <span class="feature-tag">Increased spacing</span>
                                    <span class="feature-tag">Warm background</span>
                                </div>
                                <div class="profile-badge">Active</div>
                            </div>
                            
                            <!-- ADHD Focus Profile -->
                            <div class="profile-card <?php echo $user['accessibility_profile'] == 'adhd' ? 'selected' : ''; ?>" 
                                 onclick="selectProfile('adhd')"
                                 data-profile="adhd">
                                <div class="profile-icon">🎯</div>
                                <h4>ADHD Focus</h4>
                                <p>Minimal distractions, chunked content, and focus ruler to maintain attention</p>
                                <div class="feature-tags">
                                    <span class="feature-tag">Hide sidebar</span>
                                    <span class="feature-tag">Focus ruler</span>
                                    <span class="feature-tag">Chunked content</span>
                                </div>
                                <div class="profile-badge">Active</div>
                            </div>
                        </div>
                        
                        <input type="hidden" name="accessibility_profile" id="profileInput" value="<?php echo $user['accessibility_profile']; ?>">
                    </div>

                    <div class="settings-section">
                        <h3>
                            <span>📏</span>
                            Text Size
                        </h3>
                        <div class="slider-container">
                            <input type="range" class="slider" name="text_size" min="70" max="200" value="<?php echo $user['text_size']; ?>" onchange="updateTextSize(this.value)">
                            <div class="size-display">
                                <span>Smaller</span>
                                <span id="sizeValue"><?php echo $user['text_size']; ?>%</span>
                                <span>Larger</span>
                            </div>
                        </div>
                    </div>

                    <div class="settings-section">
                        <h3>
                            <span>🎨</span>
                            Display Options
                        </h3>
                        
                        <div class="toggle-item">
                            <div class="toggle-info">
                                <h4>High Contrast Mode</h4>
                                <p>Black background with yellow text for better visibility</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="high_contrast" id="highContrast" 
                                       <?php echo $user['high_contrast'] ? 'checked' : ''; ?>
                                       onchange="toggleContrastPreview(this.checked)">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        
                        <div class="toggle-item">
                            <div class="toggle-info">
                                <h4>Focus Mode</h4>
                                <p>Hide distractions and show reading progress</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="focus_mode" id="focusMode"
                                       <?php echo $user['focus_mode'] ? 'checked' : ''; ?>
                                       onchange="toggleFocusMode(this.checked)">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>

                    <!-- Theme Mode Selection -->
                    <div class="settings-section">
                        <h3>
                            <span>🎨</span>
                            Theme Mode
                        </h3>
                        <p style="color: #6b7280; margin-bottom: 20px;">Choose your preferred color theme for the website</p>
                        
                        <div class="theme-grid">
                            <!-- Light Mode -->
                            <div class="theme-card <?php echo ($user['theme_mode'] ?? 'light') == 'light' ? 'selected' : ''; ?>" 
                                 onclick="selectTheme('light')" data-theme="light">
                                <div style="font-size: 40px; margin-bottom: 10px;">☀️</div>
                                <h4 style="margin-bottom: 5px;">Light Mode</h4>
                                <p style="font-size: 12px; color: #6b7280;">Default bright theme</p>
                            </div>
                            
                            <!-- Dark Mode -->
                            <div class="theme-card <?php echo ($user['theme_mode'] ?? 'light') == 'dark' ? 'selected' : ''; ?>" 
                                 onclick="selectTheme('dark')" data-theme="dark" style="background: #1e293b; color: white;">
                                <div style="font-size: 40px; margin-bottom: 10px;">🌙</div>
                                <h4 style="margin-bottom: 5px; color: white;">Dark Mode</h4>
                                <p style="font-size: 12px; color: #94a3b8;">Easy on the eyes at night</p>
                            </div>
                            
                            <!-- Eye Comfort Mode -->
                            <div class="theme-card <?php echo ($user['theme_mode'] ?? 'light') == 'eye-comfort' ? 'selected' : ''; ?>" 
                                 onclick="selectTheme('eye-comfort')" data-theme="eye-comfort" style="background: #fbf7e9;">
                                <div style="font-size: 40px; margin-bottom: 10px;">👁️</div>
                                <h4 style="margin-bottom: 5px;">Eye Comfort</h4>
                                <p style="font-size: 12px; color: #5b4636;">Warm sepia tone for reading</p>
                            </div>
                        </div>
                        
                        <input type="hidden" name="theme_mode" id="themeInput" value="<?php echo $user['theme_mode'] ?? 'light'; ?>">
                    </div>

                    <div class="settings-section">
                        <h3>
                            <span>👁️</span>
                            Live Preview
                        </h3>
                        <div class="live-preview">
                            <div class="preview-title">
                                <span>📝</span>
                                <span>How your content will look:</span>
                            </div>
                            <div id="previewBox" class="<?php 
                                echo $user['accessibility_profile'] == 'dyslexia' ? 'dyslexia-preview' : '';
                                echo $user['accessibility_profile'] == 'adhd' ? 'adhd-preview' : '';
                                echo $user['high_contrast'] ? 'high-contrast-preview' : '';
                            ?>">
                                <?php if ($user['accessibility_profile'] == 'adhd'): ?>
                                    <div class="preview-chunk">
                                        <strong>Section 1:</strong> Engineering concepts are easier to understand when broken into small chunks.
                                    </div>
                                    <div class="preview-chunk">
                                        <strong>Section 2:</strong> Each chunk focuses on one main idea at a time.
                                    </div>
                                    <div class="preview-chunk">
                                        <strong>Section 3:</strong> This helps maintain focus and improves comprehension.
                                    </div>
                                <?php else: ?>
                                    <p><strong>Engineering Mathematics</strong> is the study of mathematical methods used in engineering. This includes <strong>calculus</strong>, <strong>linear algebra</strong>, and <strong>differential equations</strong>. These concepts are fundamental to understanding complex engineering problems.</p>
                                    <p style="margin-top: 15px;">With your selected settings, all text will be formatted for better readability and comprehension.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="save-btn">
                        <span>💾</span>
                        Save Settings
                    </button>
                </form>
            </div>
        </main>
    </div>

    <script>
    // Settings page specific functions
    function selectProfile(profile) {
        // Remove selected class from all cards
        document.querySelectorAll('.profile-card').forEach(card => {
            card.classList.remove('selected');
        });
        
        // Add selected class to clicked card
        event.currentTarget.classList.add('selected');
        
        // Update hidden input
        document.getElementById('profileInput').value = profile;
        
        // Update preview
        updatePreview(profile);
        
        // Update body classes
        const body = document.body;
        body.classList.remove('dyslexia-profile', 'adhd-profile');
        if (profile !== 'standard') {
            body.classList.add(profile + '-profile');
        }
        body.dataset.profile = profile;
        
        // Save to localStorage
        localStorage.setItem('accessibility_profile', profile);
        
        // Save to server via AJAX
        saveSetting('profile', profile);
        
        // Show toast notification
        showToast(getProfileName(profile) + ' profile activated! ' + 
                 (profile === 'adhd' ? 'The floating home button will help you navigate.' : ''), 
                 'success');
    }

    function getProfileName(profile) {
        const names = {
            'standard': 'Standard',
            'dyslexia': 'Dyslexia-Friendly',
            'adhd': 'ADHD Focus'
        };
        return names[profile] || profile;
    }

    function updatePreview(profile) {
        const preview = document.getElementById('previewBox');
        
        // Remove all preview classes
        preview.className = '';
        
        // Add appropriate class based on profile
        if (profile === 'dyslexia') {
            preview.classList.add('dyslexia-preview');
            preview.innerHTML = `
                <p><strong style="background: #e0e7ff; padding: 2px 6px; border-radius: 4px;">Engineering Mathematics</strong> is the study of mathematical methods used in engineering.</p>
                <p style="margin-top: 15px;">✅ <strong>Now active on ALL pages:</strong> Dashboard, Branches, Subjects, Notes, and Chat will all use OpenDyslexic font with increased spacing.</p>
                <div style="margin-top: 15px; padding: 10px; background: #e0e7ff; border-radius: 8px;">
                    <span style="font-weight: bold;">✨ Applied everywhere:</span>
                    <ul style="margin-top: 8px; list-style: none; padding-left: 0;">
                        <li style="margin: 5px 0;">✓ OpenDyslexic font on ALL text</li>
                        <li style="margin: 5px 0;">✓ Increased letter spacing globally</li>
                        <li style="margin: 5px 0;">✓ Warm background on all pages</li>
                        <li style="margin: 5px 0;">✓ Keywords highlighted everywhere</li>
                    </ul>
                </div>
            `;
        } else if (profile === 'adhd') {
            preview.classList.add('adhd-preview');
            preview.innerHTML = `
                <div class="preview-chunk" style="background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 10px; border-left: 4px solid #6366f1;">
                    <strong>📘 Section 1:</strong> Content is broken into focused chunks on ALL pages.
                </div>
                <div class="preview-chunk" style="background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 10px; border-left: 4px solid #6366f1;">
                    <strong>📗 Section 2:</strong> Sidebars are hidden everywhere for distraction-free reading.
                </div>
                <div style="margin-top: 15px; padding: 10px; background: #e0e7ff; border-radius: 8px;">
                    <span style="font-weight: bold;">✨ Applied everywhere:</span>
                    <ul style="margin-top: 8px; list-style: none; padding-left: 0;">
                        <li style="margin: 5px 0;">✓ Sidebar hidden on all pages</li>
                        <li style="margin: 5px 0;">✓ <strong>Floating home button added</strong> for easy navigation</li>
                        <li style="margin: 5px 0;">✓ Focus ruler available everywhere</li>
                        <li style="margin: 5px 0;">✓ Content chunked globally</li>
                    </ul>
                </div>
            `;
        } else {
            preview.innerHTML = `
                <p><strong>Engineering Mathematics</strong> is the study of mathematical methods used in engineering. This includes <strong>calculus</strong>, <strong>linear algebra</strong>, and <strong>differential equations</strong>.</p>
                <p style="margin-top: 15px;">Standard view with regular formatting on ALL pages.</p>
                <div style="margin-top: 15px; padding: 10px; background: #e0e7ff; border-radius: 8px;">
                    <span style="font-weight: bold;">✨ Standard mode:</span>
                    <ul style="margin-top: 8px; list-style: none; padding-left: 0;">
                        <li style="margin: 5px 0;">✓ Regular font everywhere</li>
                        <li style="margin: 5px 0;">✓ Normal spacing on all pages</li>
                        <li style="margin: 5px 0;">✓ Standard layout globally</li>
                    </ul>
                </div>
            `;
        }
        
        // Apply high contrast if checked
        if (document.getElementById('highContrast').checked) {
            preview.classList.add('high-contrast-preview');
        }
    }

    function toggleContrastPreview(isChecked) {
        const preview = document.getElementById('previewBox');
        if (isChecked) {
            preview.classList.add('high-contrast-preview');
            document.body.classList.add('high-contrast-mode');
        } else {
            preview.classList.remove('high-contrast-preview');
            document.body.classList.remove('high-contrast-mode');
        }
        
        // Save to server
        saveSetting('high_contrast', isChecked ? 1 : 0);
        
        showToast(isChecked ? 'High contrast mode enabled on ALL pages' : 'High contrast mode disabled', 'info');
    }

    function updateTextSize(size) {
        document.getElementById('sizeValue').textContent = size + '%';
        
        // Apply to preview
        const preview = document.getElementById('previewBox');
        preview.style.fontSize = size + '%';
        
        // Apply to body
        document.body.dataset.textSize = size;
        
        // Save to server (debounced)
        debounceSave('text_size', size);
    }

    function toggleFocusMode(isChecked) {
        if (isChecked) {
            document.body.classList.add('adhd-profile');
        } else {
            document.body.classList.remove('adhd-profile');
        }
        
        saveSetting('focus_mode', isChecked ? 1 : 0);
        
        showToast(isChecked ? 'Focus mode enabled - Sidebar hidden, floating home button added' : 'Focus mode disabled', 'info');
    }

    function selectTheme(theme) {
        // Remove selected class from all theme cards
        document.querySelectorAll('.theme-card').forEach(card => {
            card.classList.remove('selected');
        });
        
        // Add selected class to clicked card
        event.currentTarget.classList.add('selected');
        
        // Update hidden input
        document.getElementById('themeInput').value = theme;
        
        // Update body classes
        const body = document.body;
        body.classList.remove('theme-light', 'theme-dark', 'theme-eye-comfort');
        body.classList.add('theme-' + theme);
        
        // Save to server
        saveSetting('theme_mode', theme);
        
        showToast('Theme changed to ' + theme + ' mode', 'info');
    }

    // Debounce function
    let saveTimeout;
    function debounceSave(setting, value) {
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(() => {
            saveSetting(setting, value);
        }, 500);
    }

    function saveSetting(setting, value) {
        const data = {};
        data[setting] = value;
        
        fetch('save_setting.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log(`✅ Setting ${setting} saved to database and session`);
                
                // Also update the body data attributes
                if (setting === 'profile') {
                    document.body.dataset.profile = value;
                } else if (setting === 'text_size') {
                    document.body.dataset.textSize = value;
                } else if (setting === 'high_contrast') {
                    document.body.dataset.highContrast = value;
                } else if (setting === 'focus_mode') {
                    document.body.dataset.focusMode = value;
                } else if (setting === 'theme_mode') {
                    document.body.dataset.theme = value;
                }
            }
        })
        .catch(error => {
            console.error('Error saving setting:', error);
        });
    }

    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            background: ${type === 'success' ? 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)' : 
                       type === 'warning' ? 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)' :
                       'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'};
            color: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            z-index: 10000;
            animation: slideInRight 0.3s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            max-width: 400px;
        `;
        toast.innerHTML = `
            <span>${type === 'success' ? '✅' : type === 'warning' ? '⚠️' : 'ℹ️'}</span>
            <span>${message}</span>
        `;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    // Initialize preview based on current settings
    document.addEventListener('DOMContentLoaded', function() {
        const currentProfile = '<?php echo $user['accessibility_profile']; ?>';
        const currentTextSize = <?php echo $user['text_size']; ?>;
        const currentHighContrast = <?php echo $user['high_contrast'] ? 'true' : 'false'; ?>;
        
        // Update preview
        updatePreview(currentProfile);
        updateTextSize(currentTextSize);
        
        if (currentHighContrast) {
            document.getElementById('previewBox').classList.add('high-contrast-preview');
        }
        
        // Add animation to profile cards
        document.querySelectorAll('.profile-card').forEach((card, index) => {
            card.style.animation = `slideInUp 0.5s ease ${index * 0.1}s forwards`;
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
        });
        
        // Add animation to theme cards
        document.querySelectorAll('.theme-card').forEach((card, index) => {
            card.style.animation = `fadeIn 0.5s ease ${index * 0.1}s forwards`;
            card.style.opacity = '0';
        });
        
        // Show welcome message
        setTimeout(() => {
            if (currentProfile === 'adhd') {
                showToast('🎯 ADHD mode active - Use the floating home button to navigate!', 'info');
            } else {
                showToast('👋 Click any profile to apply changes to ALL pages instantly!', 'info');
            }
        }, 1000);
    });

    // Add CSS animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOutRight {
            to { transform: translateX(100%); opacity: 0; }
        }
        @keyframes fadeIn {
            to { opacity: 1; }
        }
    `;
    document.head.appendChild(style);
    </script>
</body>
</html>
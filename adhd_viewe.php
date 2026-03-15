<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get note details
$stmt = $pdo->prepare("SELECT * FROM notes WHERE id = ?");
$stmt->execute([$id]);
$note = $stmt->fetch();

if (!$note) {
    header('Location: my_notes.php');
    exit();
}

// Update view count
$pdo->prepare("UPDATE notes SET view_count = view_count + 1 WHERE id = ?")->execute([$id]);

// Get user preferences
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT accessibility_profile, focus_mode FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_prefs = $stmt->fetch();

$adhd_mode = ($user_prefs['accessibility_profile'] == 'adhd' || $user_prefs['focus_mode'] == 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADHD Focus Reader - <?php echo htmlspecialchars($note['title']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #1a1a1a;
            height: 100vh;
            overflow: hidden;
        }
        
        /* Main Container */
        .reader-container {
            display: flex;
            height: 100vh;
            position: relative;
        }
        
        /* Sidebar - Hidden by default for ADHD mode */
        .sidebar {
            width: 280px;
            background: #2d2d2d;
            color: white;
            transition: all 0.3s ease;
            overflow-y: auto;
            position: relative;
            z-index: 10;
        }
        
        .adhd-mode .sidebar {
            width: 0;
            opacity: 0;
            pointer-events: none;
        }
        
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #404040;
        }
        
        .sidebar-header h2 {
            color: #667eea;
            font-size: 20px;
        }
        
        .sidebar-menu {
            padding: 15px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: #e0e0e0;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 5px;
            gap: 12px;
        }
        
        .sidebar-menu a:hover {
            background: #404040;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            background: #000;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .adhd-mode .main-content {
            margin-left: 0;
        }
        
        /* Toolbar */
        .toolbar {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 5;
        }
        
        .toolbar-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .toolbar-title {
            font-size: 18px;
            font-weight: 600;
        }
        
        .toolbar-actions {
            display: flex;
            gap: 12px;
        }
        
        .toolbar-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        
        .toolbar-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }
        
        .toolbar-btn.active {
            background: #10b981;
        }
        
        /* PDF Container */
        .pdf-container {
            height: calc(100vh - 70px);
            position: relative;
            overflow: hidden;
        }
        
        /* PDF Viewer with Blur */
        .pdf-viewer {
            width: 100%;
            height: 100%;
            border: none;
            transition: filter 0.3s ease;
        }
        
        .blur-active .pdf-viewer {
            filter: blur(4px);
        }
        
        /* Focus Roller */
        .focus-roller {
            position: absolute;
            left: 0;
            right: 0;
            height: 180px;
            background: rgba(102, 126, 234, 0.15);
            border-top: 4px solid #667eea;
            border-bottom: 4px solid #667eea;
            pointer-events: none;
            z-index: 20;
            display: none;
            box-shadow: 0 0 30px rgba(102, 126, 234, 0.3);
            backdrop-filter: blur(2px);
        }
        
        .roller-active .focus-roller {
            display: block;
        }
        
        /* Roller handles */
        .roller-handle {
            position: absolute;
            right: 20px;
            width: 40px;
            height: 40px;
            background: #667eea;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            pointer-events: auto;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            transition: all 0.3s;
        }
        
        .roller-handle:hover {
            transform: scale(1.1);
            background: #5a67d8;
        }
        
        .roller-handle.top {
            top: -20px;
        }
        
        .roller-handle.bottom {
            bottom: -20px;
        }
        
        /* Focus Overlay */
        .focus-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            pointer-events: none;
            z-index: 15;
            display: none;
        }
        
        .overlay-active .focus-overlay {
            display: block;
        }
        
        /* Reading Progress Bar */
        .progress-bar {
            position: absolute;
            top: 0;
            left: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb);
            width: 0%;
            transition: width 0.1s;
            z-index: 25;
        }
        
        /* ADHD Controls Panel */
        .adhd-controls {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            z-index: 30;
            width: 300px;
            border: 2px solid #667eea;
        }
        
        .adhd-controls h3 {
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .control-group {
            margin-bottom: 15px;
        }
        
        .control-label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #555;
        }
        
        .control-btn {
            width: 100%;
            padding: 10px;
            background: #f0f0f0;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .control-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .control-btn:hover {
            background: #e0e0e0;
        }
        
        .control-btn.active:hover {
            background: #5a67d8;
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
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 3px;
        }
        
        .slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 20px;
            height: 20px;
            background: white;
            border: 2px solid #667eea;
            border-radius: 50%;
            cursor: pointer;
        }
        
        .timer-display {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            color: #667eea;
            margin: 10px 0;
        }
        
        /* Sound Waves Animation */
        .sound-wave {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            height: 40px;
        }
        
        .wave-bar {
            width: 8px;
            height: 20px;
            background: #667eea;
            border-radius: 4px;
            animation: wave 1s ease-in-out infinite;
        }
        
        .wave-bar:nth-child(2) { animation-delay: 0.1s; height: 30px; }
        .wave-bar:nth-child(3) { animation-delay: 0.2s; height: 40px; }
        .wave-bar:nth-child(4) { animation-delay: 0.3s; height: 30px; }
        .wave-bar:nth-child(5) { animation-delay: 0.4s; height: 20px; }
        
        @keyframes wave {
            0%, 100% { transform: scaleY(1); }
            50% { transform: scaleY(1.5); }
        }
        
        /* Chunk Mode */
        .chunk-mode .pdf-viewer {
            filter: brightness(0.5);
        }
        
        .chunk-highlight {
            position: absolute;
            background: rgba(102, 126, 234, 0.2);
            border: 3px solid #667eea;
            border-radius: 8px;
            pointer-events: none;
            z-index: 22;
            display: none;
        }
        
        .chunk-active .chunk-highlight {
            display: block;
        }
        
        /* Quick Stats */
        .quick-stats {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 8px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #667eea;
        }
        
        .stat-label {
            font-size: 11px;
            color: #666;
        }
        
        /* Hide elements when not in ADHD mode */
        .adhd-controls {
            display: <?php echo $adhd_mode ? 'block' : 'none'; ?>;
        }
        
        /* Default mode without ADHD features */
        <?php if (!$adhd_mode): ?>
        .focus-roller, .focus-overlay, .chunk-highlight {
            display: none !important;
        }
        <?php endif; ?>
    </style>
</head>
<body class="<?php echo $adhd_mode ? 'adhd-mode roller-active' : ''; ?>" id="mainBody">
    <div class="reader-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>🧠 NeuroLearn</h2>
            </div>
            <div class="sidebar-menu">
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="my_notes.php"><i class="fas fa-sticky-note"></i> My Notes</a>
                <a href="branches.php"><i class="fas fa-book-open"></i> Branches</a>
                <a href="chat.php"><i class="fas fa-robot"></i> AI Tutor</a>
                <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content" id="mainContent">
            <!-- Progress Bar -->
            <div class="progress-bar" id="progressBar"></div>
            
            <!-- Toolbar -->
            <div class="toolbar">
                <div class="toolbar-left">
                    <span class="toolbar-title"><i class="fas fa-file-pdf"></i> <?php echo htmlspecialchars($note['title']); ?></span>
                    <?php if ($adhd_mode): ?>
                    <span class="toolbar-btn" style="background: #10b981;">
                        <i class="fas fa-brain"></i> ADHD Focus Mode Active
                    </span>
                    <?php endif; ?>
                </div>
                <div class="toolbar-actions">
                    <a href="my_notes.php" class="toolbar-btn">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    <a href="download_note.php?id=<?php echo $id; ?>" class="toolbar-btn">
                        <i class="fas fa-download"></i> Download
                    </a>
                </div>
            </div>
            
            <!-- PDF Container -->
            <div class="pdf-container" id="pdfContainer">
                <!-- Focus Roller -->
                <div class="focus-roller" id="focusRoller">
                    <div class="roller-handle top" onclick="moveRoller(-50)" title="Move Up">
                        <i class="fas fa-chevron-up"></i>
                    </div>
                    <div class="roller-handle bottom" onclick="moveRoller(50)" title="Move Down">
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
                
                <!-- Focus Overlay (blurs everything except roller area) -->
                <div class="focus-overlay" id="focusOverlay"></div>
                
                <!-- Chunk Highlight -->
                <div class="chunk-highlight" id="chunkHighlight"></div>
                
                <!-- PDF Viewer -->
                <iframe src="<?php echo $note['file_path']; ?>" class="pdf-viewer" id="pdfViewer"></iframe>
            </div>
        </div>
    </div>
    
    <!-- ADHD Controls Panel -->
    <div class="adhd-controls" id="adhdControls">
        <h3><i class="fas fa-brain" style="color: #667eea;"></i> ADHD Focus Tools</h3>
        
        <div class="quick-stats">
            <div class="stat-item">
                <div class="stat-value" id="focusTime">00:00</div>
                <div class="stat-label">Focus Time</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" id="pageProgress">0%</div>
                <div class="stat-label">Progress</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" id="breaksTaken">0</div>
                <div class="stat-label">Breaks</div>
            </div>
        </div>
        
        <div class="control-group">
            <label class="control-label">
                <i class="fas fa-ruler"></i> Focus Roller
            </label>
            <button class="control-btn" id="toggleRollerBtn" onclick="toggleRoller()">
                <i class="fas fa-toggle-off"></i> Enable Focus Roller
            </button>
            <div style="margin-top: 10px;">
                <div class="slider-container">
                    <span style="font-size: 12px;">Height:</span>
                    <input type="range" class="slider" id="rollerHeight" min="100" max="300" value="180" onchange="adjustRollerHeight(this.value)">
                    <span id="heightValue">180px</span>
                </div>
            </div>
        </div>
        
        <div class="control-group">
            <label class="control-label">
                <i class="fas fa-eye-slash"></i> Blur Effect
            </label>
            <button class="control-btn" id="toggleBlurBtn" onclick="toggleBlur()">
                <i class="fas fa-toggle-off"></i> Enable Blur
            </button>
            <div style="margin-top: 10px;">
                <div class="slider-container">
                    <span style="font-size: 12px;">Blur:</span>
                    <input type="range" class="slider" id="blurAmount" min="2" max="10" value="4" onchange="adjustBlur(this.value)">
                    <span id="blurValue">4px</span>
                </div>
            </div>
        </div>
        
        <div class="control-group">
            <label class="control-label">
                <i class="fas fa-layer-group"></i> Chunk Reading
            </label>
            <button class="control-btn" id="toggleChunkBtn" onclick="toggleChunk()">
                <i class="fas fa-toggle-off"></i> Enable Chunk Mode
            </button>
        </div>
        
        <div class="control-group">
            <label class="control-label">
                <i class="fas fa-volume-up"></i> White Noise
            </label>
            <button class="control-btn" id="toggleSoundBtn" onclick="toggleSound()">
                <i class="fas fa-toggle-off"></i> Play White Noise
            </button>
            <div class="sound-wave" id="soundWave" style="display: none;">
                <div class="wave-bar"></div>
                <div class="wave-bar"></div>
                <div class="wave-bar"></div>
                <div class="wave-bar"></div>
                <div class="wave-bar"></div>
            </div>
        </div>
        
        <div class="control-group">
            <label class="control-label">
                <i class="fas fa-clock"></i> Break Timer
            </label>
            <div class="timer-display" id="timerDisplay">25:00</div>
            <button class="control-btn" id="startTimerBtn" onclick="startBreakTimer()">
                <i class="fas fa-play"></i> Start 25min Timer
            </button>
        </div>
        
        <div class="control-group">
            <button class="control-btn" style="background: #10b981; color: white;" onclick="resetAll()">
                <i class="fas fa-undo"></i> Reset All
            </button>
        </div>
    </div>

    <script>
        // ===== ADHD FOCUS TOOLS =====
        let rollerActive = <?php echo $adhd_mode ? 'true' : 'false'; ?>;
        let blurActive = false;
        let chunkActive = false;
        let soundActive = false;
        let audioContext = null;
        let noiseNode = null;
        let focusTime = 0;
        let breakCount = 0;
        let timerInterval;
        let breakTimer;
        let breakTimeLeft = 25 * 60; // 25 minutes
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            if (rollerActive) {
                document.getElementById('toggleRollerBtn').innerHTML = '<i class="fas fa-toggle-on"></i> Disable Focus Roller';
                document.getElementById('toggleRollerBtn').classList.add('active');
            }
            
            // Start focus timer
            startFocusTimer();
            
            // Track scroll progress
            document.getElementById('pdfViewer').addEventListener('load', function() {
                setupScrollTracking();
            });
        });
        
        // ===== FOCUS ROLLER =====
        function toggleRoller() {
            const body = document.getElementById('mainBody');
            const btn = document.getElementById('toggleRollerBtn');
            
            if (rollerActive) {
                body.classList.remove('roller-active');
                btn.innerHTML = '<i class="fas fa-toggle-off"></i> Enable Focus Roller';
                btn.classList.remove('active');
                rollerActive = false;
            } else {
                body.classList.add('roller-active');
                btn.innerHTML = '<i class="fas fa-toggle-on"></i> Disable Focus Roller';
                btn.classList.add('active');
                rollerActive = true;
            }
        }
        
        function moveRoller(amount) {
            const roller = document.getElementById('focusRoller');
            const currentTop = parseFloat(roller.style.top) || window.innerHeight / 2 - 90;
            let newTop = currentTop + amount;
            
            // Keep within bounds
            newTop = Math.max(0, Math.min(window.innerHeight - 180, newTop));
            
            roller.style.top = newTop + 'px';
        }
        
        function adjustRollerHeight(height) {
            const roller = document.getElementById('focusRoller');
            roller.style.height = height + 'px';
            document.getElementById('heightValue').textContent = height + 'px';
            
            // Adjust handles position
            const handles = document.querySelectorAll('.roller-handle');
            handles.forEach(handle => {
                if (handle.classList.contains('top')) {
                    handle.style.top = '-' + (height/2 - 20) + 'px';
                } else {
                    handle.style.bottom = '-' + (height/2 - 20) + 'px';
                }
            });
        }
        
        // ===== BLUR EFFECT =====
        function toggleBlur() {
            const container = document.getElementById('pdfContainer');
            const btn = document.getElementById('toggleBlurBtn');
            
            if (blurActive) {
                container.classList.remove('blur-active');
                btn.innerHTML = '<i class="fas fa-toggle-off"></i> Enable Blur';
                btn.classList.remove('active');
                blurActive = false;
            } else {
                container.classList.add('blur-active');
                btn.innerHTML = '<i class="fas fa-toggle-on"></i> Disable Blur';
                btn.classList.add('active');
                blurActive = true;
            }
        }
        
        function adjustBlur(amount) {
            const viewer = document.getElementById('pdfViewer');
            viewer.style.filter = 'blur(' + amount + 'px)';
            document.getElementById('blurValue').textContent = amount + 'px';
        }
        
        // ===== CHUNK MODE =====
        function toggleChunk() {
            const container = document.getElementById('pdfContainer');
            const btn = document.getElementById('toggleChunkBtn');
            
            if (chunkActive) {
                container.classList.remove('chunk-active');
                btn.innerHTML = '<i class="fas fa-toggle-off"></i> Enable Chunk Mode';
                btn.classList.remove('active');
                chunkActive = false;
            } else {
                container.classList.add('chunk-active');
                btn.innerHTML = '<i class="fas fa-toggle-on"></i> Disable Chunk Mode';
                btn.classList.add('active');
                chunkActive = true;
                
                // Show chunk highlight
                showChunkHighlight();
            }
        }
        
        function showChunkHighlight() {
            const highlight = document.getElementById('chunkHighlight');
            const roller = document.getElementById('focusRoller');
            
            if (rollerActive) {
                const rollerRect = roller.getBoundingClientRect();
                highlight.style.top = rollerRect.top + 'px';
                highlight.style.left = '20px';
                highlight.style.right = '20px';
                highlight.style.height = '100px';
            }
        }
        
        // ===== WHITE NOISE =====
        function toggleSound() {
            const btn = document.getElementById('toggleSoundBtn');
            const wave = document.getElementById('soundWave');
            
            if (soundActive) {
                stopWhiteNoise();
                btn.innerHTML = '<i class="fas fa-toggle-off"></i> Play White Noise';
                btn.classList.remove('active');
                wave.style.display = 'none';
                soundActive = false;
            } else {
                playWhiteNoise();
                btn.innerHTML = '<i class="fas fa-toggle-on"></i> Stop White Noise';
                btn.classList.add('active');
                wave.style.display = 'flex';
                soundActive = true;
            }
        }
        
        function playWhiteNoise() {
            try {
                audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const bufferSize = 2 * audioContext.sampleRate;
                const noiseBuffer = audioContext.createBuffer(1, bufferSize, audioContext.sampleRate);
                const output = noiseBuffer.getChannelData(0);
                
                for (let i = 0; i < bufferSize; i++) {
                    output[i] = Math.random() * 2 - 1;
                }
                
                noiseNode = audioContext.createBufferSource();
                noiseNode.buffer = noiseBuffer;
                noiseNode.loop = true;
                
                const gainNode = audioContext.createGain();
                gainNode.gain.value = 0.1;
                
                noiseNode.connect(gainNode);
                gainNode.connect(audioContext.destination);
                noiseNode.start();
            } catch (e) {
                alert('Could not play white noise: ' + e.message);
            }
        }
        
        function stopWhiteNoise() {
            if (noiseNode) {
                noiseNode.stop();
                noiseNode.disconnect();
            }
            if (audioContext) {
                audioContext.close();
            }
        }
        
        // ===== BREAK TIMER =====
        function startBreakTimer() {
            const btn = document.getElementById('startTimerBtn');
            const timerDisplay = document.getElementById('timerDisplay');
            
            if (breakTimer) {
                clearInterval(breakTimer);
                breakTimer = null;
                breakTimeLeft = 25 * 60;
                timerDisplay.textContent = '25:00';
                btn.innerHTML = '<i class="fas fa-play"></i> Start 25min Timer';
                return;
            }
            
            btn.innerHTML = '<i class="fas fa-stop"></i> Stop Timer';
            breakTimer = setInterval(function() {
                breakTimeLeft--;
                
                if (breakTimeLeft <= 0) {
                    clearInterval(breakTimer);
                    breakTimer = null;
                    alert('⏰ Time for a break! Stand up and stretch for 2 minutes.');
                    breakCount++;
                    document.getElementById('breaksTaken').textContent = breakCount;
                    breakTimeLeft = 25 * 60;
                    timerDisplay.textContent = '25:00';
                    btn.innerHTML = '<i class="fas fa-play"></i> Start 25min Timer';
                } else {
                    const minutes = Math.floor(breakTimeLeft / 60);
                    const seconds = breakTimeLeft % 60;
                    timerDisplay.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                }
            }, 1000);
        }
        
        // ===== FOCUS TIMER =====
        function startFocusTimer() {
            timerInterval = setInterval(function() {
                focusTime++;
                const minutes = Math.floor(focusTime / 60);
                const seconds = focusTime % 60;
                document.getElementById('focusTime').textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            }, 1000);
        }
        
        // ===== SCROLL TRACKING =====
        function setupScrollTracking() {
            try {
                const pdfViewer = document.getElementById('pdfViewer');
                pdfViewer.contentWindow.addEventListener('scroll', function() {
                    const scrollTop = this.scrollY;
                    const scrollHeight = this.document.documentElement.scrollHeight - this.window.innerHeight;
                    const progress = (scrollTop / scrollHeight) * 100;
                    
                    document.getElementById('progressBar').style.width = progress + '%';
                    document.getElementById('pageProgress').textContent = Math.round(progress) + '%';
                    
                    // Auto chunk highlight
                    if (chunkActive) {
                        showChunkHighlight();
                    }
                });
            } catch (e) {
                console.log('Scroll tracking limited due to cross-origin');
            }
        }
        
        // ===== RESET ALL =====
        function resetAll() {
            // Reset roller
            if (rollerActive) toggleRoller();
            
            // Reset blur
            if (blurActive) toggleBlur();
            
            // Reset chunk
            if (chunkActive) toggleChunk();
            
            // Reset sound
            if (soundActive) toggleSound();
            
            // Reset timer
            if (breakTimer) {
                clearInterval(breakTimer);
                breakTimer = null;
                breakTimeLeft = 25 * 60;
                document.getElementById('timerDisplay').textContent = '25:00';
                document.getElementById('startTimerBtn').innerHTML = '<i class="fas fa-play"></i> Start 25min Timer';
            }
            
            // Reset roller height
            adjustRollerHeight(180);
            document.getElementById('rollerHeight').value = 180;
            
            // Reset blur amount
            adjustBlur(4);
            document.getElementById('blurAmount').value = 4;
            
            // Reset focus time (optional - you might want to keep it)
        }
        
        // Move roller with mouse
        document.addEventListener('mousemove', function(e) {
            if (rollerActive) {
                const roller = document.getElementById('focusRoller');
                const height = parseInt(roller.style.height) || 180;
                let newTop = e.clientY - height / 2;
                
                // Keep within bounds
                newTop = Math.max(0, Math.min(window.innerHeight - height, newTop));
                roller.style.top = newTop + 'px';
                
                // Update chunk highlight if active
                if (chunkActive) {
                    showChunkHighlight();
                }
            }
        });
        
        // Clean up on unload
        window.addEventListener('beforeunload', function() {
            if (soundActive) {
                stopWhiteNoise();
            }
            if (timerInterval) {
                clearInterval(timerInterval);
            }
            if (breakTimer) {
                clearInterval(breakTimer);
            }
        });
    </script>
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</body>
</html>
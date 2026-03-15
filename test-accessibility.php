<?php
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accessibility Test Page</title>
    <link rel="stylesheet" href="css/accessibility.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/opendyslexic@0.1.0/opendyslexic.css">
    <script src="js/global-accessibility.js" defer></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            transition: all 0.3s ease;
        }
        .test-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #333; }
        p { margin: 15px 0; line-height: 1.6; }
        .test-section {
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .test-buttons {
            display: flex;
            gap: 10px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        .test-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
        }
        .btn-standard { background: #6c757d; color: white; }
        .btn-dyslexia { background: #667eea; color: white; }
        .btn-adhd { background: #764ba2; color: white; }
        .btn-contrast { background: #000; color: #ff0; }
        .current-settings {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .setting-item {
            margin: 10px 0;
            padding: 10px;
            background: white;
            border-radius: 5px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-active { background: #28a745; color: white; }
        .status-inactive { background: #dc3545; color: white; }
    </style>
</head>
<body data-profile="standard" data-text-size="100" data-high-contrast="0" data-focus-mode="0">
    <div class="test-container">
        <h1>🔧 Accessibility Test Page</h1>
        <p>This page tests if accessibility settings are working globally.</p>
        
        <div class="current-settings">
            <h3>📊 Current Settings (from localStorage)</h3>
            <div id="settings-display">
                <div class="setting-item">
                    <strong>Profile:</strong> <span id="display-profile">Loading...</span>
                    <span id="profile-badge" class="status-badge">...</span>
                </div>
                <div class="setting-item">
                    <strong>Text Size:</strong> <span id="display-textSize">Loading...</span>%
                </div>
                <div class="setting-item">
                    <strong>High Contrast:</strong> <span id="display-contrast">Loading...</span>
                    <span id="contrast-badge" class="status-badge">...</span>
                </div>
                <div class="setting-item">
                    <strong>Focus Mode:</strong> <span id="display-focus">Loading...</span>
                    <span id="focus-badge" class="status-badge">...</span>
                </div>
            </div>
        </div>

        <div class="test-section">
            <h3>🎯 Manual Test Buttons</h3>
            <p>Click these buttons to manually set accessibility modes:</p>
            <div class="test-buttons">
                <button class="test-btn btn-standard" onclick="manualSetProfile('standard')">📚 Standard</button>
                <button class="test-btn btn-dyslexia" onclick="manualSetProfile('dyslexia')">🔤 Dyslexia</button>
                <button class="test-btn btn-adhd" onclick="manualSetProfile('adhd')">🎯 ADHD</button>
                <button class="test-btn btn-contrast" onclick="manualToggleContrast()">🌓 Toggle Contrast</button>
            </div>
        </div>

        <div class="test-section">
            <h3>📝 Test Content</h3>
            <p><strong>Engineering Mathematics</strong> is the study of mathematical methods used in engineering. This includes <strong>calculus</strong>, <strong>linear algebra</strong>, and <strong>differential equations</strong>. These concepts are fundamental to understanding complex engineering problems.</p>
            <p>The <em>Quick Brown Fox</em> jumps over the lazy dog. This sentence contains every letter of the alphabet and is useful for testing font changes.</p>
            <ul>
                <li>Item 1 with some text</li>
                <li>Item 2 with more text</li>
                <li>Item 3 with additional content</li>
            </ul>
        </div>

        <div class="test-section">
            <h3>🔍 Debug Information</h3>
            <div id="debug-info" style="background: #000; color: #0f0; padding: 15px; border-radius: 5px; font-family: monospace;">
                Loading debug info...
            </div>
        </div>
    </div>

    <script>
    // Function to update display
    function updateDisplay() {
        const profile = localStorage.getItem('accessibility_profile') || 'standard';
        const textSize = localStorage.getItem('accessibility_text_size') || '100';
        const highContrast = localStorage.getItem('accessibility_high_contrast') === '1';
        const focusMode = localStorage.getItem('accessibility_focus_mode') === '1';
        
        document.getElementById('display-profile').textContent = profile;
        document.getElementById('display-textSize').textContent = textSize;
        document.getElementById('display-contrast').textContent = highContrast ? 'Enabled' : 'Disabled';
        document.getElementById('display-focus').textContent = focusMode ? 'Enabled' : 'Disabled';
        
        // Update badges
        const profileBadge = document.getElementById('profile-badge');
        profileBadge.textContent = profile.toUpperCase();
        profileBadge.className = 'status-badge status-active';
        
        const contrastBadge = document.getElementById('contrast-badge');
        contrastBadge.textContent = highContrast ? 'ON' : 'OFF';
        contrastBadge.className = `status-badge ${highContrast ? 'status-active' : 'status-inactive'}`;
        
        const focusBadge = document.getElementById('focus-badge');
        focusBadge.textContent = focusMode ? 'ON' : 'OFF';
        focusBadge.className = `status-badge ${focusMode ? 'status-active' : 'status-inactive'}`;
        
        // Update debug info
        const debug = document.getElementById('debug-info');
        debug.innerHTML = `
            localStorage Items:<br>
            - accessibility_profile: ${localStorage.getItem('accessibility_profile') || 'not set'}<br>
            - accessibility_text_size: ${localStorage.getItem('accessibility_text_size') || 'not set'}<br>
            - accessibility_high_contrast: ${localStorage.getItem('accessibility_high_contrast') || 'not set'}<br>
            - accessibility_focus_mode: ${localStorage.getItem('accessibility_focus_mode') || 'not set'}<br>
            <br>
            Body Classes: ${document.body.className || 'none'}<br>
            Body Data Attributes:<br>
            - data-profile: ${document.body.dataset.profile}<br>
            - data-text-size: ${document.body.dataset.textSize}<br>
            - data-high-contrast: ${document.body.dataset.highContrast}<br>
            - data-focus-mode: ${document.body.dataset.focusMode}<br>
        `;
    }

    // Manual functions
    function manualSetProfile(profile) {
        localStorage.setItem('accessibility_profile', profile);
        if (window.globalAccessibility) {
            window.globalAccessibility.updateProfile(profile);
        } else {
            // Fallback
            document.body.classList.remove('dyslexia-profile', 'adhd-profile');
            if (profile !== 'standard') {
                document.body.classList.add(profile + '-profile');
            }
            document.body.dataset.profile = profile;
        }
        updateDisplay();
        alert(`Profile set to: ${profile}. Check other pages!`);
    }

    function manualToggleContrast() {
        const current = localStorage.getItem('accessibility_high_contrast') === '1';
        const newValue = !current;
        localStorage.setItem('accessibility_high_contrast', newValue ? '1' : '0');
        if (window.globalAccessibility) {
            window.globalAccessibility.updateHighContrast(newValue);
        } else {
            document.body.classList.toggle('high-contrast', newValue);
            document.body.dataset.highContrast = newValue ? '1' : '0';
        }
        updateDisplay();
    }

    // Update display every second
    setInterval(updateDisplay, 1000);
    
    // Initial update
    setTimeout(updateDisplay, 500);
    </script>
</body>
</html>
/**
 * Study Timer with Auto-Simplification
 * Countdown timer that triggers text simplification when it reaches zero
 */

class StudyTimer {
    constructor() {
        this.timeLeft = 300; // 5 minutes in seconds (300 seconds)
        this.defaultTime = 300; // Default 5 minutes
        this.timerInterval = null;
        this.isRunning = false;
        this.simplificationCount = 0;
        this.maxSimplifications = 5; // Maximum times to auto-simplify
        this.simplifiedElements = new Set(); // Track which elements have been simplified
        
        this.init();
    }
    
    init() {
        // Create timer UI
        this.createTimerUI();
        
        // Load saved preferences
        this.loadPreferences();
        
        // Start timer automatically
        this.startTimer();
        
        // Add event listeners
        this.addEventListeners();
    }
    
    createTimerUI() {
        // Create timer container
        const timerContainer = document.createElement('div');
        timerContainer.className = 'study-timer-container';
        timerContainer.id = 'studyTimer';
        timerContainer.innerHTML = `
            <div class="timer-header">
                <div class="timer-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="timer-title">Study Focus Timer</div>
                <div class="timer-controls">
                    <button class="timer-control-btn" onclick="studyTimer.toggleTimer()" id="timerToggleBtn">
                        <i class="fas fa-pause"></i>
                    </button>
                    <button class="timer-control-btn" onclick="studyTimer.resetTimer()">
                        <i class="fas fa-redo-alt"></i>
                    </button>
                    <button class="timer-control-btn" onclick="studyTimer.showSettings()">
                        <i class="fas fa-cog"></i>
                    </button>
                </div>
            </div>
            <div class="timer-display" id="timerDisplay">
                <span id="minutes">05</span>:<span id="seconds">00</span>
            </div>
            <div class="timer-progress">
                <div class="timer-progress-bar" id="timerProgress"></div>
            </div>
            <div class="timer-info">
                <span class="timer-simplification-count">
                    <i class="fas fa-sparkles"></i> 
                    <span id="simplificationCount">0</span>/<span id="maxSimplifications">5</span>
                </span>
                <span class="timer-status" id="timerStatus">
                    <i class="fas fa-play"></i> Running
                </span>
            </div>
            <div class="timer-message" id="timerMessage" style="display: none;">
                <i class="fas fa-bell"></i>
                <span id="timerMessageText"></span>
            </div>
        `;
        
        // Insert at the top of dashboard, before welcome section
        const welcomeSection = document.querySelector('.welcome-section');
        if (welcomeSection) {
            welcomeSection.parentNode.insertBefore(timerContainer, welcomeSection);
        } else {
            // Fallback: insert at beginning of main content
            const mainContent = document.querySelector('.main-content .content-container');
            if (mainContent) {
                mainContent.insertBefore(timerContainer, mainContent.firstChild);
            }
        }
        
        // Add styles
        this.addStyles();
    }
    
    addStyles() {
        const style = document.createElement('style');
        style.textContent = `
            /* Study Timer Styles */
            .study-timer-container {
                background: linear-gradient(135deg, #667eea, #764ba2);
                border-radius: 16px;
                padding: 20px;
                margin-bottom: 20px;
                color: white;
                box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
                animation: timerSlideDown 0.5s ease;
            }
            
            @keyframes timerSlideDown {
                from {
                    opacity: 0;
                    transform: translateY(-30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .timer-header {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 15px;
            }
            
            .timer-icon {
                width: 40px;
                height: 40px;
                background: rgba(255, 255, 255, 0.2);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 20px;
            }
            
            .timer-title {
                flex: 1;
                font-size: 16px;
                font-weight: 600;
                opacity: 0.9;
            }
            
            .timer-controls {
                display: flex;
                gap: 8px;
            }
            
            .timer-control-btn {
                width: 36px;
                height: 36px;
                background: rgba(255, 255, 255, 0.2);
                border: none;
                border-radius: 50%;
                color: white;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.2s;
                font-size: 14px;
            }
            
            .timer-control-btn:hover {
                background: rgba(255, 255, 255, 0.3);
                transform: scale(1.1);
            }
            
            .timer-display {
                font-size: 48px;
                font-weight: 700;
                text-align: center;
                font-family: 'Courier New', monospace;
                letter-spacing: 5px;
                margin: 10px 0;
                text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            }
            
            .timer-progress {
                height: 6px;
                background: rgba(255, 255, 255, 0.2);
                border-radius: 3px;
                margin: 15px 0;
                overflow: hidden;
            }
            
            .timer-progress-bar {
                height: 100%;
                width: 100%;
                background: linear-gradient(90deg, #fff, #f0f0f0);
                border-radius: 3px;
                transition: width 1s linear;
            }
            
            .timer-info {
                display: flex;
                justify-content: space-between;
                align-items: center;
                font-size: 13px;
                opacity: 0.9;
                margin-top: 10px;
            }
            
            .timer-simplification-count {
                background: rgba(255, 255, 255, 0.2);
                padding: 4px 10px;
                border-radius: 20px;
            }
            
            .timer-simplification-count i {
                margin-right: 5px;
            }
            
            .timer-status {
                background: rgba(255, 255, 255, 0.2);
                padding: 4px 10px;
                border-radius: 20px;
            }
            
            .timer-status.paused {
                background: rgba(245, 158, 11, 0.3);
            }
            
            .timer-message {
                margin-top: 15px;
                padding: 10px;
                background: rgba(255, 255, 255, 0.15);
                border-radius: 8px;
                display: flex;
                align-items: center;
                gap: 10px;
                animation: messagePulse 2s infinite;
                border-left: 3px solid #fbbf24;
            }
            
            @keyframes messagePulse {
                0%, 100% { background: rgba(255, 255, 255, 0.15); }
                50% { background: rgba(255, 255, 255, 0.25); }
            }
            
            .timer-message i {
                color: #fbbf24;
                font-size: 18px;
            }
            
            /* Timer Settings Modal */
            .timer-settings-modal {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10010;
                display: none;
                animation: fadeIn 0.3s ease;
            }
            
            .timer-settings-modal.show {
                display: flex;
            }
            
            .timer-settings-content {
                background: white;
                border-radius: 20px;
                width: 400px;
                max-width: 90%;
                padding: 25px;
                animation: slideUp 0.3s ease;
            }
            
            .timer-settings-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            }
            
            .timer-settings-header h3 {
                color: #1f2937;
                font-size: 20px;
                margin: 0;
            }
            
            .timer-settings-close {
                background: none;
                border: none;
                font-size: 20px;
                cursor: pointer;
                color: #6b7280;
            }
            
            .timer-settings-group {
                margin-bottom: 20px;
            }
            
            .timer-settings-group label {
                display: block;
                margin-bottom: 8px;
                color: #4b5563;
                font-weight: 600;
                font-size: 14px;
            }
            
            .timer-settings-input {
                width: 100%;
                padding: 10px;
                border: 2px solid #e5e7eb;
                border-radius: 10px;
                font-size: 14px;
            }
            
            .timer-settings-input:focus {
                outline: none;
                border-color: #667eea;
            }
            
            .timer-settings-buttons {
                display: flex;
                gap: 10px;
                margin-top: 20px;
            }
            
            .timer-settings-btn {
                flex: 1;
                padding: 12px;
                border: none;
                border-radius: 10px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s;
            }
            
            .timer-settings-btn.primary {
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: white;
            }
            
            .timer-settings-btn.primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            }
            
            .timer-settings-btn.secondary {
                background: #f3f4f6;
                color: #4b5563;
            }
            
            .timer-settings-btn.secondary:hover {
                background: #e5e7eb;
            }
            
            /* Simplified text animation */
            @keyframes simplifyHighlight {
                0% { background-color: #fef3c7; }
                50% { background-color: #fde68a; }
                100% { background-color: #fef3c7; }
            }
            
            .timer-simplified {
                animation: simplifyHighlight 2s ease;
                border-left: 4px solid #f59e0b;
                padding-left: 15px;
            }
            
            /* ADHD mode adjustments */
            body.adhd-mode .study-timer-container {
                margin-top: 0;
            }
            
            /* Dark mode support */
            @media (prefers-color-scheme: dark) {
                .timer-settings-content {
                    background: #1f2937;
                }
                
                .timer-settings-header h3 {
                    color: #f3f4f6;
                }
                
                .timer-settings-group label {
                    color: #9ca3af;
                }
                
                .timer-settings-input {
                    background: #374151;
                    border-color: #4b5563;
                    color: #f3f4f6;
                }
            }
            
            /* Mobile responsive */
            @media (max-width: 768px) {
                .timer-display {
                    font-size: 36px;
                }
                
                .timer-info {
                    flex-direction: column;
                    gap: 10px;
                    align-items: flex-start;
                }
            }
        `;
        
        document.head.appendChild(style);
    }
    
    addEventListeners() {
        // Listen for page visibility changes
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.pauseTimer();
            } else {
                // Don't auto-resume, let user decide
            }
        });
    }
    
    startTimer() {
        if (this.isRunning) return;
        
        this.isRunning = true;
        this.updateTimerStatus('running');
        
        this.timerInterval = setInterval(() => {
            if (this.timeLeft > 0) {
                this.timeLeft--;
                this.updateTimerDisplay();
                this.updateProgressBar();
                
                // Check if timer reached zero
                if (this.timeLeft === 0) {
                    this.timerComplete();
                }
            }
        }, 1000);
    }
    
    pauseTimer() {
        if (!this.isRunning) return;
        
        clearInterval(this.timerInterval);
        this.isRunning = false;
        this.updateTimerStatus('paused');
        document.getElementById('timerToggleBtn').innerHTML = '<i class="fas fa-play"></i>';
    }
    
    toggleTimer() {
        if (this.isRunning) {
            this.pauseTimer();
        } else {
            this.startTimer();
            document.getElementById('timerToggleBtn').innerHTML = '<i class="fas fa-pause"></i>';
        }
    }
    
    resetTimer() {
        this.pauseTimer();
        this.timeLeft = this.defaultTime;
        this.updateTimerDisplay();
        this.updateProgressBar();
        this.hideMessage();
        
        // Reset toggle button icon
        document.getElementById('timerToggleBtn').innerHTML = '<i class="fas fa-play"></i>';
    }
    
    timerComplete() {
        this.pauseTimer();
        
        // Show message
        this.showMessage('Time\'s up! Simplifying text for better understanding...');
        
        // Trigger simplification
        this.simplifyPageContent();
        
        // Increment simplification count
        this.simplificationCount++;
        this.updateSimplificationCount();
        
        // Check if max simplifications reached
        if (this.simplificationCount >= this.maxSimplifications) {
            this.showMessage('Maximum simplifications reached. Take a break!');
            setTimeout(() => {
                this.resetTimer();
                this.simplificationCount = 0;
                this.simplifiedElements.clear();
                this.updateSimplificationCount();
            }, 5000);
        } else {
            // Restart timer after 3 seconds
            setTimeout(() => {
                this.resetTimer();
                this.startTimer();
                document.getElementById('timerToggleBtn').innerHTML = '<i class="fas fa-pause"></i>';
            }, 3000);
        }
    }
    
    simplifyPageContent() {
        // Find all text elements to simplify
        const textElements = document.querySelectorAll(
            'p, .branch-description, .stat-label, .ai-tutor-text p, .welcome-section p, li'
        );
        
        textElements.forEach(element => {
            // Skip if already simplified
            if (this.simplifiedElements.has(element)) return;
            
            const originalText = element.innerText;
            if (originalText.length < 50) return; // Skip short text
            
            // Simplify the text
            const simplified = this.simplifyText(originalText);
            
            // Create simplified version
            const simplifiedDiv = document.createElement('div');
            simplifiedDiv.className = 'simplified-text timer-simplified';
            simplifiedDiv.innerHTML = `
                <span class="simplified-badge">
                    <i class="fas fa-sparkles"></i> Auto-Simplified (Timer)
                </span>
                <div class="simplified-content">${simplified}</div>
                <div class="original-text-toggle" onclick="this.parentElement.nextSibling.style.display='block';this.style.display='none'">
                    <i class="fas fa-undo-alt"></i> Show original
                </div>
            `;
            
            // Store original
            const originalDiv = document.createElement('div');
            originalDiv.className = 'original-text';
            originalDiv.style.display = 'none';
            originalDiv.style.marginTop = '10px';
            originalDiv.style.padding = '10px';
            originalDiv.style.background = '#f3f4f6';
            originalDiv.style.borderRadius = '8px';
            originalDiv.style.fontSize = '0.9em';
            originalDiv.style.color = '#6b7280';
            originalDiv.textContent = originalText;
            
            // Replace content
            element.innerHTML = '';
            element.appendChild(simplifiedDiv);
            element.appendChild(originalDiv);
            
            // Mark as simplified
            this.simplifiedElements.add(element);
        });
        
        // Show success message
        this.showToast('✨ Content simplified for better focus');
    }
    
    simplifyText(text) {
        // Simple simplification rules
        return text
            .replace(/utilize/g, 'use')
            .replace(/implement/g, 'use')
            .replace(/facilitate/g, 'help')
            .replace(/commence/g, 'start')
            .replace(/terminate/g, 'end')
            .replace(/numerous/g, 'many')
            .replace(/subsequent/g, 'next')
            .replace(/prior to/g, 'before')
            .replace(/in order to/g, 'to')
            .replace(/due to the fact that/g, 'because')
            .replace(/in the event that/g, 'if')
            .replace(/with the exception of/g, 'except')
            .replace(/approximately/g, 'about')
            .replace(/sufficient/g, 'enough')
            .replace(/additional/g, 'more')
            .replace(/demonstrate/g, 'show')
            .replace(/construct/g, 'build')
            .replace(/locate/g, 'find');
    }
    
    updateTimerDisplay() {
        const minutes = Math.floor(this.timeLeft / 60);
        const seconds = this.timeLeft % 60;
        
        document.getElementById('minutes').textContent = minutes.toString().padStart(2, '0');
        document.getElementById('seconds').textContent = seconds.toString().padStart(2, '0');
    }
    
    updateProgressBar() {
        const progress = (this.timeLeft / this.defaultTime) * 100;
        document.getElementById('timerProgress').style.width = progress + '%';
    }
    
    updateTimerStatus(status) {
        const statusEl = document.getElementById('timerStatus');
        if (status === 'running') {
            statusEl.innerHTML = '<i class="fas fa-play"></i> Running';
            statusEl.classList.remove('paused');
        } else {
            statusEl.innerHTML = '<i class="fas fa-pause"></i> Paused';
            statusEl.classList.add('paused');
        }
    }
    
    updateSimplificationCount() {
        document.getElementById('simplificationCount').textContent = this.simplificationCount;
        document.getElementById('maxSimplifications').textContent = this.maxSimplifications;
    }
    
    showMessage(message) {
        const messageEl = document.getElementById('timerMessage');
        document.getElementById('timerMessageText').textContent = message;
        messageEl.style.display = 'flex';
        
        // Auto hide after 5 seconds
        setTimeout(() => {
            this.hideMessage();
        }, 5000);
    }
    
    hideMessage() {
        document.getElementById('timerMessage').style.display = 'none';
    }
    
    showSettings() {
        // Create modal if it doesn't exist
        if (!document.getElementById('timerSettingsModal')) {
            this.createSettingsModal();
        }
        
        // Show modal
        document.getElementById('timerSettingsModal').classList.add('show');
        
        // Set current values
        document.getElementById('settingsMinutes').value = Math.floor(this.defaultTime / 60);
        document.getElementById('settingsMaxSimplifications').value = this.maxSimplifications;
    }
    
    createSettingsModal() {
        const modal = document.createElement('div');
        modal.className = 'timer-settings-modal';
        modal.id = 'timerSettingsModal';
        modal.innerHTML = `
            <div class="timer-settings-content">
                <div class="timer-settings-header">
                    <h3><i class="fas fa-clock"></i> Timer Settings</h3>
                    <button class="timer-settings-close" onclick="studyTimer.closeSettings()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="timer-settings-group">
                    <label>Timer Duration (minutes)</label>
                    <input type="number" id="settingsMinutes" class="timer-settings-input" min="1" max="60" value="5">
                </div>
                
                <div class="timer-settings-group">
                    <label>Max Auto-Simplifications</label>
                    <input type="number" id="settingsMaxSimplifications" class="timer-settings-input" min="1" max="10" value="5">
                </div>
                
                <div class="timer-settings-buttons">
                    <button class="timer-settings-btn primary" onclick="studyTimer.saveSettings()">
                        <i class="fas fa-check"></i> Save
                    </button>
                    <button class="timer-settings-btn secondary" onclick="studyTimer.closeSettings()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
    }
    
    saveSettings() {
        const minutes = parseInt(document.getElementById('settingsMinutes').value) || 5;
        const maxSimplifications = parseInt(document.getElementById('settingsMaxSimplifications').value) || 5;
        
        this.defaultTime = minutes * 60;
        this.maxSimplifications = maxSimplifications;
        
        // Reset timer with new time
        this.timeLeft = this.defaultTime;
        this.updateTimerDisplay();
        this.updateProgressBar();
        
        // Save to localStorage
        localStorage.setItem('studyTimer_duration', this.defaultTime);
        localStorage.setItem('studyTimer_maxSimplifications', this.maxSimplifications);
        
        this.closeSettings();
        this.showToast('⚙️ Settings saved');
    }
    
    closeSettings() {
        document.getElementById('timerSettingsModal').classList.remove('show');
    }
    
    loadPreferences() {
        // Load from localStorage
        const savedDuration = localStorage.getItem('studyTimer_duration');
        const savedMaxSimplifications = localStorage.getItem('studyTimer_maxSimplifications');
        
        if (savedDuration) {
            this.defaultTime = parseInt(savedDuration);
            this.timeLeft = this.defaultTime;
        }
        
        if (savedMaxSimplifications) {
            this.maxSimplifications = parseInt(savedMaxSimplifications);
        }
        
        this.updateTimerDisplay();
        this.updateProgressBar();
        this.updateSimplificationCount();
    }
    
    showToast(message) {
        const toast = document.createElement('div');
        toast.className = 'toast-message';
        toast.style.background = 'linear-gradient(135deg, #667eea, #764ba2)';
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
}

// Initialize timer
document.addEventListener('DOMContentLoaded', () => {
    window.studyTimer = new StudyTimer();
});
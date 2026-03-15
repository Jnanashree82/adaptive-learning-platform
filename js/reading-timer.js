/**
 * Reading Timer & Nudge System
 * Tracks reading time and provides simplified text when users struggle
 */

class ReadingTimer {
    constructor() {
        this.startTime = null;
        this.currentElement = null;
        this.readingSpeed = 200; // Average reading speed: 200 words per minute
        this.thresholdMultiplier = 2.5; // Trigger nudge after 2.5x expected time
        this.nudgeCount = 0;
        this.maxNudges = 3;
        this.simplifiedTexts = new Map(); // Cache for simplified texts
        this.isTracking = false;
        this.timerInterval = null;
        this.currentParagraph = null;
        this.paragraphStartTime = null;
        
        this.init();
    }
    
    init() {
        // Create nudge UI
        this.createNudgeUI();
        
        // Add event listeners to paragraphs
        this.trackParagraphs();
        
        // Listen for scroll to reset timer
        window.addEventListener('scroll', () => this.resetTimer(), { passive: true });
        
        // Listen for clicks on paragraphs
        document.addEventListener('click', (e) => {
            const paragraph = e.target.closest('p, .paragraph, .branch-description, .stat-label, .ai-tutor-text p');
            if (paragraph) {
                this.startTracking(paragraph);
            }
        });
        
        // Observe new content being added
        this.observeNewContent();
    }
    
    createNudgeUI() {
        // Create nudge container
        const nudgeContainer = document.createElement('div');
        nudgeContainer.className = 'nudge-container';
        nudgeContainer.id = 'nudgeContainer';
        nudgeContainer.innerHTML = `
            <div class="nudge-content">
                <div class="nudge-icon">
                    <i class="fas fa-lightbulb"></i>
                </div>
                <div class="nudge-message" id="nudgeMessage">
                    Taking longer to read? Would you like a simpler version?
                </div>
                <div class="nudge-actions">
                    <button class="nudge-btn nudge-btn-primary" onclick="readingTimer.simplifyText()">
                        <i class="fas fa-sparkles"></i> Simplify
                    </button>
                    <button class="nudge-btn nudge-btn-secondary" onclick="readingTimer.dismissNudge()">
                        <i class="fas fa-times"></i> Dismiss
                    </button>
                    <button class="nudge-btn nudge-btn-link" onclick="readingTimer.neverShow()">
                        Don't show again
                    </button>
                </div>
                <div class="nudge-timer" id="nudgeTimer">
                    <i class="fas fa-hourglass-half"></i>
                    <span id="timeSpent">0:00</span>
                </div>
            </div>
        `;
        
        document.body.appendChild(nudgeContainer);
        
        // Add styles
        this.addStyles();
    }
    
    addStyles() {
        const style = document.createElement('style');
        style.textContent = `
            /* Nudge Container */
            .nudge-container {
                position: fixed;
                bottom: 30px;
                left: 30px;
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
            
            /* Simplified text highlight */
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
            
            /* Progress indicator */
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
            
            /* ADHD mode adjustments */
            body.adhd-mode .nudge-container {
                bottom: 200px;
                left: 20px;
            }
            
            /* Dark mode support */
            @media (prefers-color-scheme: dark) {
                .nudge-container {
                    background: #1f2937;
                    border-left-color: #fbbf24;
                }
                
                .nudge-message {
                    color: #f3f4f6;
                }
                
                .nudge-icon {
                    background: #374151;
                }
                
                .nudge-icon i {
                    color: #fbbf24;
                }
                
                .nudge-timer {
                    color: #9ca3af;
                    border-top-color: #374151;
                }
            }
            
            /* Mobile responsive */
            @media (max-width: 768px) {
                .nudge-container {
                    width: calc(100% - 40px);
                    left: 20px;
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
        `;
        
        document.head.appendChild(style);
        
        // Add progress bar
        const progressBar = document.createElement('div');
        progressBar.className = 'reading-progress';
        progressBar.id = 'readingProgress';
        document.body.appendChild(progressBar);
    }
    
    trackParagraphs() {
        // Track all paragraphs and text elements
        const textElements = document.querySelectorAll(
            'p, .branch-description, .stat-label, .ai-tutor-text p, .welcome-section p, li'
        );
        
        textElements.forEach(element => {
            element.addEventListener('mouseenter', () => {
                this.startTracking(element);
            });
            
            element.addEventListener('mouseleave', () => {
                this.stopTracking();
            });
        });
    }
    
    startTracking(element) {
        // Don't track if user has disabled nudges
        if (localStorage.getItem('disableNudges') === 'true') return;
        
        // Stop previous tracking
        this.stopTracking();
        
        // Start tracking new element
        this.currentElement = element;
        this.paragraphStartTime = Date.now();
        this.isTracking = true;
        
        // Calculate expected reading time
        const wordCount = this.countWords(element.innerText);
        const expectedTime = (wordCount / this.readingSpeed) * 60 * 1000; // in milliseconds
        
        // Set threshold
        this.threshold = expectedTime * this.thresholdMultiplier;
        
        // Start timer
        this.timerInterval = setInterval(() => {
            this.updateTimer();
        }, 1000);
    }
    
    stopTracking() {
        this.isTracking = false;
        this.currentElement = null;
        this.paragraphStartTime = null;
        
        if (this.timerInterval) {
            clearInterval(this.timerInterval);
            this.timerInterval = null;
        }
        
        // Hide nudge if showing
        this.hideNudge();
    }
    
    updateTimer() {
        if (!this.isTracking || !this.paragraphStartTime) return;
        
        const elapsed = Date.now() - this.paragraphStartTime;
        const seconds = Math.floor(elapsed / 1000);
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        
        // Update progress bar
        const progress = (elapsed / this.threshold) * 100;
        document.getElementById('readingProgress').style.width = Math.min(progress, 100) + '%';
        
        // Check if we need to show nudge
        if (elapsed > this.threshold && this.nudgeCount < this.maxNudges) {
            this.showNudge(minutes, remainingSeconds);
        }
    }
    
    showNudge(minutes, seconds) {
        const nudgeContainer = document.getElementById('nudgeContainer');
        const timeSpent = document.getElementById('timeSpent');
        
        timeSpent.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        nudgeContainer.classList.add('show');
        
        this.nudgeCount++;
    }
    
    hideNudge() {
        document.getElementById('nudgeContainer').classList.remove('show');
    }
    
    async simplifyText() {
        if (!this.currentElement) return;
        
        const originalText = this.currentElement.innerText;
        
        // Check if we already have simplified version cached
        if (this.simplifiedTexts.has(originalText)) {
            this.displaySimplifiedText(this.simplifiedTexts.get(originalText));
            return;
        }
        
        // Show loading state
        this.currentElement.style.opacity = '0.6';
        
        try {
            // Simulate AI simplification (replace with actual API call)
            const simplified = await this.simulateSimplification(originalText);
            
            // Cache the result
            this.simplifiedTexts.set(originalText, simplified);
            
            // Display simplified text
            this.displaySimplifiedText(simplified);
        } catch (error) {
            console.error('Error simplifying text:', error);
            alert('Sorry, could not simplify the text at this moment.');
        } finally {
            this.currentElement.style.opacity = '1';
            this.hideNudge();
        }
    }
    
    simulateSimplification(text) {
        return new Promise((resolve) => {
            setTimeout(() => {
                // Simple simplification rules
                let simplified = text
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
                    .replace(/with the exception of/g, 'except');
                
                // Break long sentences
                if (simplified.length > 200) {
                    simplified = simplified.replace(/\. /g, '.\n\n');
                }
                
                resolve(simplified);
            }, 1000);
        });
    }
    
    displaySimplifiedText(simplifiedText) {
        const originalText = this.currentElement.innerText;
        const originalHTML = this.currentElement.innerHTML;
        
        // Create simplified version container
        const simplifiedDiv = document.createElement('div');
        simplifiedDiv.className = 'simplified-text';
        simplifiedDiv.innerHTML = `
            <span class="simplified-badge">
                <i class="fas fa-sparkles"></i> Simplified Version
            </span>
            <div class="simplified-content">${simplifiedText}</div>
            <div class="original-text-toggle" onclick="readingTimer.toggleOriginal(this, \`${originalText.replace(/`/g, '\\`')}\`)">
                <i class="fas fa-undo-alt"></i> Show original
            </div>
        `;
        
        // Replace current content
        this.currentElement.innerHTML = '';
        this.currentElement.appendChild(simplifiedDiv);
        
        // Show success message
        this.showToast('✨ Text simplified for easier reading');
    }
    
    toggleOriginal(toggleElement, originalText) {
        const simplifiedDiv = toggleElement.closest('.simplified-text');
        const content = simplifiedDiv.querySelector('.simplified-content');
        
        if (content.style.display === 'none') {
            content.style.display = 'block';
            toggleElement.innerHTML = '<i class="fas fa-undo-alt"></i> Show original';
        } else {
            content.style.display = 'none';
            toggleElement.innerHTML = '<i class="fas fa-redo-alt"></i> Show simplified';
            
            // Add original text after toggle
            let originalDiv = simplifiedDiv.querySelector('.original-text');
            if (!originalDiv) {
                originalDiv = document.createElement('div');
                originalDiv.className = 'original-text';
                originalDiv.style.marginTop = '10px';
                originalDiv.style.padding = '10px';
                originalDiv.style.background = '#f3f4f6';
                originalDiv.style.borderRadius = '8px';
                originalDiv.style.fontSize = '0.9em';
                originalDiv.style.color = '#6b7280';
                originalDiv.textContent = originalText;
                simplifiedDiv.appendChild(originalDiv);
            }
        }
    }
    
    dismissNudge() {
        this.hideNudge();
        this.showToast('Nudge dismissed');
    }
    
    neverShow() {
        localStorage.setItem('disableNudges', 'true');
        this.hideNudge();
        this.showToast('Nudges disabled. You can enable them in Settings.');
    }
    
    resetTimer() {
        this.stopTracking();
        document.getElementById('readingProgress').style.width = '0%';
    }
    
    countWords(text) {
        return text.trim().split(/\s+/).length;
    }
    
    showToast(message) {
        const toast = document.createElement('div');
        toast.className = 'toast-message';
        toast.style.background = '#f59e0b';
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    observeNewContent() {
        // Watch for dynamically added content
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1) { // Element node
                        const paragraphs = node.querySelectorAll('p, .branch-description, .stat-label');
                        paragraphs.forEach(p => {
                            p.addEventListener('mouseenter', () => this.startTracking(p));
                        });
                    }
                });
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
}

// Initialize reading timer
document.addEventListener('DOMContentLoaded', () => {
    window.readingTimer = new ReadingTimer();
});
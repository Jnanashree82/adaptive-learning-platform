/**
 * Voice Agent - Text-to-Speech for NeuroLearn
 * Reads content aloud with accessibility features
 */

class VoiceAgent {
    constructor() {
        this.synth = window.speechSynthesis;
        this.voices = [];
        this.currentUtterance = null;
        this.isSpeaking = false;
        this.isPaused = false;
        this.rate = 1.0;
        this.pitch = 1.0;
        this.volume = 1.0;
        this.selectedVoice = null;
        this.highlightMode = true;
        this.autoReadMode = false;
        
        this.init();
    }
    
    init() {
        // Load available voices
        this.loadVoices();
        
        // Listen for voice changes
        if (speechSynthesis.onvoiceschanged !== undefined) {
            speechSynthesis.onvoiceschanged = () => this.loadVoices();
        }
        
        // Create UI
        this.createVoiceUI();
        
        // Add keyboard shortcut (Alt+V to toggle)
        document.addEventListener('keydown', (e) => {
            if (e.altKey && e.key === 'v') {
                e.preventDefault();
                this.toggleVoicePanel();
            }
        });
        
        // Add highlightable class to text elements
        this.addHighlightableElements();
    }
    
    loadVoices() {
        this.voices = this.synth.getVoices();
        
        // Try to find a good default voice (preferably English)
        this.selectedVoice = this.voices.find(voice => 
            voice.lang.includes('en-US') && !voice.name.includes('Google')
        ) || this.voices.find(voice => 
            voice.lang.includes('en')
        ) || this.voices[0];
        
        // Update voice selector if it exists
        this.updateVoiceSelector();
    }
    
    createVoiceUI() {
        // Create floating voice button
        const voiceButton = document.createElement('div');
        voiceButton.className = 'voice-agent-button';
        voiceButton.innerHTML = `
            <div class="voice-icon">
                <i class="fas fa-volume-up"></i>
            </div>
        `;
        voiceButton.onclick = () => this.toggleVoicePanel();
        document.body.appendChild(voiceButton);
        
        // Create voice control panel
        const panel = document.createElement('div');
        panel.className = 'voice-agent-panel';
        panel.id = 'voiceAgentPanel';
        panel.innerHTML = `
            <div class="voice-panel-header">
                <h3><i class="fas fa-microphone-alt"></i> Voice Reader</h3>
                <button class="voice-close-btn" onclick="voiceAgent.toggleVoicePanel()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="voice-panel-content">
                <!-- Voice Selection -->
                <div class="voice-control-group">
                    <label><i class="fas fa-robot"></i> Voice</label>
                    <select id="voiceSelect" class="voice-select">
                        <option value="">Loading voices...</option>
                    </select>
                </div>
                
                <!-- Speed Control -->
                <div class="voice-control-group">
                    <label><i class="fas fa-tachometer-alt"></i> Speed</label>
                    <div class="voice-slider-container">
                        <span class="speed-label">Slow</span>
                        <input type="range" id="rateSlider" min="0.5" max="2" step="0.1" value="1.0">
                        <span class="speed-label">Fast</span>
                    </div>
                    <div class="voice-value-display" id="rateValue">1.0x</div>
                </div>
                
                <!-- Pitch Control -->
                <div class="voice-control-group">
                    <label><i class="fas fa-chart-line"></i> Pitch</label>
                    <div class="voice-slider-container">
                        <span class="pitch-label">Low</span>
                        <input type="range" id="pitchSlider" min="0.5" max="2" step="0.1" value="1.0">
                        <span class="pitch-label">High</span>
                    </div>
                    <div class="voice-value-display" id="pitchValue">1.0</div>
                </div>
                
                <!-- Control Buttons -->
                <div class="voice-control-buttons">
                    <button class="voice-btn voice-btn-primary" onclick="voiceAgent.readPage()">
                        <i class="fas fa-play"></i> Read Page
                    </button>
                    <button class="voice-btn voice-btn-secondary" onclick="voiceAgent.stop()">
                        <i class="fas fa-stop"></i> Stop
                    </button>
                    <button class="voice-btn voice-btn-secondary" onclick="voiceAgent.pause()">
                        <i class="fas fa-pause"></i> Pause
                    </button>
                    <button class="voice-btn voice-btn-secondary" onclick="voiceAgent.resume()">
                        <i class="fas fa-play"></i> Resume
                    </button>
                </div>
                
                <!-- Options -->
                <div class="voice-options">
                    <label class="voice-checkbox">
                        <input type="checkbox" id="highlightMode" checked onchange="voiceAgent.toggleHighlightMode()">
                        <span>Highlight while reading</span>
                    </label>
                    <label class="voice-checkbox">
                        <input type="checkbox" id="autoReadMode" onchange="voiceAgent.toggleAutoRead()">
                        <span>Auto-read new content</span>
                    </label>
                </div>
                
                <!-- Read Selection Button -->
                <button class="voice-btn voice-btn-block" onclick="voiceAgent.readSelection()">
                    <i class="fas fa-highlighter"></i> Read Selected Text
                </button>
                
                <!-- Status -->
                <div class="voice-status" id="voiceStatus">
                    <i class="fas fa-info-circle"></i> Ready
                </div>
            </div>
        `;
        
        document.body.appendChild(panel);
        
        // Add event listeners after panel is created
        setTimeout(() => {
            this.initializeControls();
        }, 100);
    }
    
    initializeControls() {
        const voiceSelect = document.getElementById('voiceSelect');
        const rateSlider = document.getElementById('rateSlider');
        const pitchSlider = document.getElementById('pitchSlider');
        
        if (voiceSelect) {
            voiceSelect.addEventListener('change', (e) => {
                const voiceName = e.target.value;
                this.selectedVoice = this.voices.find(v => v.name === voiceName) || null;
            });
        }
        
        if (rateSlider) {
            rateSlider.addEventListener('input', (e) => {
                this.rate = parseFloat(e.target.value);
                document.getElementById('rateValue').textContent = this.rate.toFixed(1) + 'x';
            });
        }
        
        if (pitchSlider) {
            pitchSlider.addEventListener('input', (e) => {
                this.pitch = parseFloat(e.target.value);
                document.getElementById('pitchValue').textContent = this.pitch.toFixed(1);
            });
        }
        
        this.updateVoiceSelector();
    }
    
    updateVoiceSelector() {
        const voiceSelect = document.getElementById('voiceSelect');
        if (!voiceSelect) return;
        
        voiceSelect.innerHTML = '';
        
        this.voices.forEach(voice => {
            const option = document.createElement('option');
            option.value = voice.name;
            option.textContent = `${voice.name} (${voice.lang})`;
            if (this.selectedVoice && voice.name === this.selectedVoice.name) {
                option.selected = true;
            }
            voiceSelect.appendChild(option);
        });
    }
    
    toggleVoicePanel() {
        const panel = document.getElementById('voiceAgentPanel');
        panel.classList.toggle('active');
    }
    
    setStatus(message, isError = false) {
        const status = document.getElementById('voiceStatus');
        if (status) {
            status.innerHTML = `<i class="fas fa-${isError ? 'exclamation-circle' : 'info-circle'}"></i> ${message}`;
            status.style.backgroundColor = isError ? '#fee2e2' : '#f3f4f6';
            status.style.color = isError ? '#dc2626' : '#4b5563';
        }
    }
    
    readText(text, element = null) {
        if (!text || text.trim() === '') {
            this.setStatus('No text to read', true);
            return;
        }
        
        // Stop any current speech
        this.stop();
        
        // Create utterance
        const utterance = new SpeechSynthesisUtterance(text);
        
        // Set properties
        utterance.voice = this.selectedVoice;
        utterance.rate = this.rate;
        utterance.pitch = this.pitch;
        utterance.volume = this.volume;
        
        // Event handlers
        utterance.onstart = () => {
            this.isSpeaking = true;
            this.setStatus('Reading...');
            if (this.highlightMode && element) {
                this.highlightElement(element);
            }
        };
        
        utterance.onend = () => {
            this.isSpeaking = false;
            this.setStatus('Finished');
            if (this.highlightMode) {
                this.removeHighlight();
            }
        };
        
        utterance.onerror = (event) => {
            this.isSpeaking = false;
            this.setStatus('Error: ' + event.error, true);
        };
        
        utterance.onpause = () => {
            this.isPaused = true;
            this.setStatus('Paused');
        };
        
        utterance.onresume = () => {
            this.isPaused = false;
            this.setStatus('Resumed');
        };
        
        this.currentUtterance = utterance;
        this.synth.speak(utterance);
    }
    
    readPage() {
        // Get main content
        const mainContent = document.querySelector('main') || document.querySelector('.main-content') || document.body;
        
        // Get all text elements
        const textElements = mainContent.querySelectorAll('h1, h2, h3, h4, p, li, .stat-number, .stat-label, .branch-name, .branch-description');
        
        let fullText = '';
        const elements = [];
        
        textElements.forEach(el => {
            const text = el.innerText.trim();
            if (text) {
                fullText += text + '. ';
                elements.push(el);
            }
        });
        
        // Read the page title first
        const pageTitle = document.querySelector('h1') || document.querySelector('.welcome-section h1');
        if (pageTitle) {
            fullText = pageTitle.innerText + '. ' + fullText;
        }
        
        this.readText(fullText, elements[0]);
        this.setStatus('Reading page content...');
    }
    
    readSelection() {
        const selection = window.getSelection();
        const text = selection.toString().trim();
        
        if (text) {
            // Get the selected element
            let element = null;
            if (selection.rangeCount > 0) {
                element = selection.getRangeAt(0).commonAncestorContainer;
                if (element.nodeType === Node.TEXT_NODE) {
                    element = element.parentElement;
                }
            }
            
            this.readText(text, element);
            this.setStatus('Reading selected text...');
        } else {
            this.setStatus('No text selected', true);
        }
    }
    
    stop() {
        this.synth.cancel();
        this.isSpeaking = false;
        this.isPaused = false;
        this.setStatus('Stopped');
        this.removeHighlight();
    }
    
    pause() {
        if (this.isSpeaking && !this.isPaused) {
            this.synth.pause();
        }
    }
    
    resume() {
        if (this.isPaused) {
            this.synth.resume();
        }
    }
    
    toggleHighlightMode() {
        this.highlightMode = document.getElementById('highlightMode').checked;
    }
    
    toggleAutoRead() {
        this.autoReadMode = document.getElementById('autoReadMode').checked;
        
        if (this.autoReadMode) {
            this.setStatus('Auto-read enabled');
            // Auto-read new content when page loads
            setTimeout(() => this.readPage(), 500);
        } else {
            this.setStatus('Auto-read disabled');
        }
    }
    
    highlightElement(element) {
        this.removeHighlight();
        
        if (element) {
            element.classList.add('voice-reading-highlight');
            
            // Scroll to element if not in view
            element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
    
    removeHighlight() {
        document.querySelectorAll('.voice-reading-highlight').forEach(el => {
            el.classList.remove('voice-reading-highlight');
        });
    }
    
    addHighlightableElements() {
        // Add a class to make elements highlightable on hover
        const style = document.createElement('style');
        style.textContent = `
            .voice-highlightable {
                cursor: pointer;
                transition: all 0.2s ease;
            }
            .voice-highlightable:hover {
                background-color: rgba(139, 92, 246, 0.2);
                outline: 2px solid #8b5cf6;
            }
            .voice-reading-highlight {
                background-color: rgba(139, 92, 246, 0.3) !important;
                outline: 3px solid #8b5cf6 !important;
                transition: all 0.2s ease;
            }
        `;
        document.head.appendChild(style);
        
        // Add click-to-read functionality to main content
        setTimeout(() => {
            document.querySelectorAll('h1, h2, h3, h4, p, li, .stat-card, .branch-card').forEach(el => {
                el.classList.add('voice-highlightable');
                el.addEventListener('click', (e) => {
                    // Don't interfere with links and buttons
                    if (e.target.tagName === 'A' || e.target.tagName === 'BUTTON') return;
                    
                    const text = el.innerText.trim();
                    if (text) {
                        this.readText(text, el);
                    }
                });
            });
        }, 1000);
    }
}

// Initialize voice agent when page loads
document.addEventListener('DOMContentLoaded', () => {
    window.voiceAgent = new VoiceAgent();
});
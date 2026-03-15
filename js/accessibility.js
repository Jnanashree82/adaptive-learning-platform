// Global Accessibility Manager - Simplified Version
(function() {
    console.log('🎯 Global Accessibility Script Loaded');
    
    class GlobalAccessibility {
        constructor() {
            this.init();
        }

        init() {
            console.log('Initializing Global Accessibility...');
            this.loadFromLocalStorage();
            this.applySettings();
            this.setupListeners();
            this.showDebugInfo();
        }

        loadFromLocalStorage() {
            // Load settings from localStorage with defaults
            this.profile = localStorage.getItem('accessibility_profile') || 'standard';
            this.textSize = parseInt(localStorage.getItem('accessibility_text_size')) || 100;
            this.highContrast = localStorage.getItem('accessibility_high_contrast') === '1';
            this.focusMode = localStorage.getItem('accessibility_focus_mode') === '1';
            
            console.log('📦 Loaded from localStorage:', {
                profile: this.profile,
                textSize: this.textSize,
                highContrast: this.highContrast,
                focusMode: this.focusMode
            });
        }

        applySettings() {
            const body = document.body;
            
            // Remove all existing classes
            body.classList.remove('dyslexia-profile', 'adhd-profile', 'high-contrast', 
                                 'text-size-small', 'text-size-medium', 'text-size-large', 
                                 'text-size-xlarge', 'text-size-xxlarge');
            
            // Apply profile class
            if (this.profile !== 'standard') {
                body.classList.add(this.profile + '-profile');
                console.log(`✅ Applied ${this.profile} profile class`);
            }
            
            // Apply text size class
            let sizeClass = 'text-size-medium';
            if (this.textSize <= 80) sizeClass = 'text-size-small';
            else if (this.textSize <= 100) sizeClass = 'text-size-medium';
            else if (this.textSize <= 120) sizeClass = 'text-size-large';
            else if (this.textSize <= 150) sizeClass = 'text-size-xlarge';
            else sizeClass = 'text-size-xxlarge';
            body.classList.add(sizeClass);
            
            // Apply high contrast
            if (this.highContrast) {
                body.classList.add('high-contrast');
                console.log('✅ Applied high contrast');
            }
            
            // Apply focus mode
            if (this.focusMode) {
                body.classList.add('focus-mode');
                console.log('✅ Applied focus mode');
            }
            
            // Update data attributes
            body.dataset.profile = this.profile;
            body.dataset.textSize = this.textSize;
            body.dataset.highContrast = this.highContrast ? '1' : '0';
            body.dataset.focusMode = this.focusMode ? '1' : '0';
            
            console.log('🎨 Current body classes:', body.className);
        }

        setupListeners() {
            // Listen for storage changes (from other tabs)
            window.addEventListener('storage', (e) => {
                console.log('📡 Storage changed:', e.key, e.newValue);
                this.loadFromLocalStorage();
                this.applySettings();
            });
            
            // Custom event for same-tab updates
            window.addEventListener('accessibility-update', () => {
                console.log('🔄 Received update event');
                this.loadFromLocalStorage();
                this.applySettings();
            });
        }

        showDebugInfo() {
            // Add debug info to console
            console.log('🔍 Current Settings:', {
                profile: this.profile,
                textSize: this.textSize,
                highContrast: this.highContrast,
                focusMode: this.focusMode,
                bodyClasses: document.body.className
            });
        }

        updateProfile(profile) {
            console.log('📝 Updating profile to:', profile);
            this.profile = profile;
            localStorage.setItem('accessibility_profile', profile);
            this.applySettings();
            
            // Dispatch event for same-tab listeners
            window.dispatchEvent(new CustomEvent('accessibility-update'));
        }

        updateTextSize(size) {
            this.textSize = size;
            localStorage.setItem('accessibility_text_size', size);
            this.applySettings();
            window.dispatchEvent(new CustomEvent('accessibility-update'));
        }

        updateHighContrast(enabled) {
            this.highContrast = enabled;
            localStorage.setItem('accessibility_high_contrast', enabled ? '1' : '0');
            this.applySettings();
            window.dispatchEvent(new CustomEvent('accessibility-update'));
        }

        updateFocusMode(enabled) {
            this.focusMode = enabled;
            localStorage.setItem('accessibility_focus_mode', enabled ? '1' : '0');
            this.applySettings();
            window.dispatchEvent(new CustomEvent('accessibility-update'));
        }
    }

    // Initialize and make global
    window.globalAccessibility = new GlobalAccessibility();
})();
// Global Accessibility Manager
class GlobalAccessibility {
    constructor() {
        this.init();
    }

    init() {
        // Load settings from localStorage
        this.loadFromLocalStorage();
        
        // Apply settings to current page
        this.applySettings();
        
        // Listen for storage events (for cross-tab sync)
        window.addEventListener('storage', (e) => {
            this.handleStorageChange(e);
        });
        
        console.log('🌍 Global Accessibility initialized with settings:', {
            profile: this.profile,
            textSize: this.textSize,
            highContrast: this.highContrast,
            focusMode: this.focusMode
        });
    }

    loadFromLocalStorage() {
        // Load settings from localStorage
        this.profile = localStorage.getItem('accessibility_profile') || 'standard';
        this.textSize = parseInt(localStorage.getItem('accessibility_text_size')) || 100;
        this.highContrast = localStorage.getItem('accessibility_high_contrast') === '1';
        this.focusMode = localStorage.getItem('accessibility_focus_mode') === '1';
        
        // If no settings in localStorage, try to get from PHP session (via data attributes)
        if (!localStorage.getItem('accessibility_profile')) {
            const body = document.body;
            this.profile = body.dataset.profile || 'standard';
            this.textSize = parseInt(body.dataset.textSize) || 100;
            this.highContrast = body.dataset.highContrast === '1';
            this.focusMode = body.dataset.focusMode === '1';
            
            // Save to localStorage
            this.saveToLocalStorage();
        }
    }

    saveToLocalStorage() {
        localStorage.setItem('accessibility_profile', this.profile);
        localStorage.setItem('accessibility_text_size', this.textSize);
        localStorage.setItem('accessibility_high_contrast', this.highContrast ? '1' : '0');
        localStorage.setItem('accessibility_focus_mode', this.focusMode ? '1' : '0');
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
        }
        
        // Apply focus mode
        if (this.focusMode) {
            body.classList.add('focus-mode');
            this.initFocusRuler();
        }
        
        // Update data attributes
        body.dataset.profile = this.profile;
        body.dataset.textSize = this.textSize;
        body.dataset.highContrast = this.highContrast ? '1' : '0';
        body.dataset.focusMode = this.focusMode ? '1' : '0';
        
        // Dispatch event for other components
        window.dispatchEvent(new CustomEvent('accessibilityChanged', {
            detail: {
                profile: this.profile,
                textSize: this.textSize,
                highContrast: this.highContrast,
                focusMode: this.focusMode
            }
        }));
        
        // Show indicator that settings are applied
        this.showSettingsIndicator();
    }

    showSettingsIndicator() {
        // Remove existing indicator
        const oldIndicator = document.querySelector('.settings-indicator');
        if (oldIndicator) oldIndicator.remove();
        
        // Create new indicator
        const indicator = document.createElement('div');
        indicator.className = 'settings-indicator';
        indicator.style.cssText = `
            position: fixed;
            bottom: 20px;
            left: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 12px;
            z-index: 9999;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            animation: slideInLeft 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        `;
        
        let profileIcon = '📚';
        if (this.profile === 'dyslexia') profileIcon = '🔤';
        if (this.profile === 'adhd') profileIcon = '🎯';
        
        indicator.innerHTML = `
            <span>${profileIcon}</span>
            <span>${this.getProfileName(this.profile)} • ${this.textSize}%</span>
            ${this.highContrast ? '<span>🌓</span>' : ''}
        `;
        
        document.body.appendChild(indicator);
        
        // Remove after 3 seconds
        setTimeout(() => {
            indicator.style.animation = 'slideOutLeft 0.3s ease';
            setTimeout(() => indicator.remove(), 300);
        }, 3000);
    }

    getProfileName(profile) {
        const names = {
            'standard': 'Standard',
            'dyslexia': 'Dyslexia-Friendly',
            'adhd': 'ADHD Focus'
        };
        return names[profile] || profile;
    }

    updateProfile(profile) {
        this.profile = profile;
        this.saveToLocalStorage();
        this.applySettings();
        
        // Dispatch event
        window.dispatchEvent(new CustomEvent('accessibilityChange', {
            detail: { type: 'profile', value: profile }
        }));
    }

    updateTextSize(size) {
        this.textSize = size;
        this.saveToLocalStorage();
        this.applySettings();
        
        window.dispatchEvent(new CustomEvent('accessibilityChange', {
            detail: { type: 'textSize', value: size }
        }));
    }

    updateHighContrast(enabled) {
        this.highContrast = enabled;
        this.saveToLocalStorage();
        this.applySettings();
        
        window.dispatchEvent(new CustomEvent('accessibilityChange', {
            detail: { type: 'highContrast', value: enabled }
        }));
    }

    updateFocusMode(enabled) {
        this.focusMode = enabled;
        this.saveToLocalStorage();
        this.applySettings();
        
        window.dispatchEvent(new CustomEvent('accessibilityChange', {
            detail: { type: 'focusMode', value: enabled }
        }));
    }

    initFocusRuler() {
        if (document.querySelector('.focus-ruler')) return;
        
        const ruler = document.createElement('div');
        ruler.className = 'focus-ruler';
        ruler.id = 'focusRuler';
        document.body.appendChild(ruler);
        
        document.addEventListener('mousemove', (e) => {
            ruler.style.top = (e.clientY - 60) + 'px';
        });
    }

    toggleFocusRuler() {
        const ruler = document.getElementById('focusRuler');
        if (ruler) {
            ruler.classList.toggle('active');
        }
    }

    handleStorageChange(e) {
        switch(e.key) {
            case 'accessibility_profile':
                this.profile = e.newValue;
                this.applySettings();
                break;
            case 'accessibility_text_size':
                this.textSize = parseInt(e.newValue);
                this.applySettings();
                break;
            case 'accessibility_high_contrast':
                this.highContrast = e.newValue === '1';
                this.applySettings();
                break;
            case 'accessibility_focus_mode':
                this.focusMode = e.newValue === '1';
                this.applySettings();
                break;
        }
    }
}

// Initialize global accessibility
const globalAccessibility = new GlobalAccessibility();
window.globalAccessibility = globalAccessibility;

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInLeft {
        from {
            transform: translateX(-100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutLeft {
        to {
            transform: translateX(-100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
// Theme Loader - Apply saved theme on all pages
document.addEventListener('DOMContentLoaded', function() {
    // Check localStorage first
    let theme = localStorage.getItem('theme_mode');
    
    // If not in localStorage, check session data from PHP (via body attribute)
    if (!theme && document.body.dataset.theme) {
        theme = document.body.dataset.theme;
    }
    
    // Apply theme
    if (theme === 'dark') {
        document.body.classList.add('dark-mode');
    } else if (theme === 'eye-comfort') {
        document.body.classList.add('eye-comfort-mode');
    }
    
    // Add floating theme toggle button
    addThemeToggle();
});

function addThemeToggle() {
    // Check if toggle already exists
    if (document.querySelector('.mode-toggle')) return;
    
    const toggle = document.createElement('div');
    toggle.className = 'mode-toggle';
    toggle.innerHTML = `
        <button class="mode-btn ${!document.body.classList.contains('dark-mode') && !document.body.classList.contains('eye-comfort-mode') ? 'active' : ''}" onclick="switchTheme('light')" title="Light Mode">☀️</button>
        <button class="mode-btn ${document.body.classList.contains('dark-mode') ? 'active' : ''}" onclick="switchTheme('dark')" title="Dark Mode">🌙</button>
        <button class="mode-btn ${document.body.classList.contains('eye-comfort-mode') ? 'active' : ''}" onclick="switchTheme('eye-comfort')" title="Eye Comfort">👁️</button>
    `;
    document.body.appendChild(toggle);
}

function switchTheme(theme) {
    // Remove all theme classes
    document.body.classList.remove('dark-mode', 'eye-comfort-mode');
    
    // Add selected theme
    if (theme === 'dark') {
        document.body.classList.add('dark-mode');
    } else if (theme === 'eye-comfort') {
        document.body.classList.add('eye-comfort-mode');
    }
    
    // Update active state of buttons
    document.querySelectorAll('.mode-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
    
    // Save to localStorage
    localStorage.setItem('theme_mode', theme);
    
    // Save to server if logged in
    if (typeof saveTheme === 'function') {
        saveTheme(theme);
    } else {
        // Fallback fetch
        fetch('save_theme.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ theme: theme })
        }).catch(error => console.error('Error saving theme:', error));
    }
    
    // Show toast notification
    showThemeToast(theme);
}

function showThemeToast(theme) {
    const messages = {
        'light': '☀️ Light mode activated',
        'dark': '🌙 Dark mode activated',
        'eye-comfort': '👁️ Eye comfort mode activated'
    };
    
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        bottom: 80px;
        left: 20px;
        padding: 10px 20px;
        background: ${theme === 'dark' ? '#1e293b' : theme === 'eye-comfort' ? '#b85e3a' : '#4361ee'};
        color: white;
        border-radius: 30px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        z-index: 1001;
        animation: slideInLeft 0.3s ease;
        font-size: 14px;
    `;
    toast.textContent = messages[theme];
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOutLeft 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 2000);
}

// Add animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInLeft {
        from { transform: translateX(-100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOutLeft {
        to { transform: translateX(-100%); opacity: 0; }
    }
`;
document.head.appendChild(style);
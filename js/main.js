// Text size management
let currentTextSize = localStorage.getItem('textSize') || 'medium';

function toggleTextSize() {
    const sizes = ['small', 'medium', 'large', 'xlarge'];
    let currentIndex = sizes.indexOf(currentTextSize);
    currentIndex = (currentIndex + 1) % sizes.length;
    currentTextSize = sizes[currentIndex];
    
    document.body.className = document.body.className
        .replace(/text-size-\w+/, '')
        .trim();
    document.body.classList.add(`text-size-${currentTextSize}`);
    
    localStorage.setItem('textSize', currentTextSize);
}

// High contrast toggle
function toggleContrast() {
    document.body.classList.toggle('high-contrast');
    localStorage.setItem('highContrast', document.body.classList.contains('high-contrast'));
}

// Dyslexia-friendly font
function toggleDyslexiaFont() {
    document.body.classList.toggle('dyslexia-font');
}

// Reduced motion
function toggleReducedMotion() {
    document.body.classList.toggle('reduced-motion');
}

// Load saved preferences
window.addEventListener('load', function() {
    // Load text size
    const savedSize = localStorage.getItem('textSize');
    if (savedSize) {
        currentTextSize = savedSize;
        document.body.classList.add(`text-size-${savedSize}`);
    }
    
    // Load contrast
    if (localStorage.getItem('highContrast') === 'true') {
        document.body.classList.add('high-contrast');
    }
    
    // Initialize tooltips
    initTooltips();
});

// Tooltips for better navigation
function initTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    tooltipElements.forEach(el => {
        el.addEventListener('mouseenter', showTooltip);
        el.addEventListener('mouseleave', hideTooltip);
    });
}

function showTooltip(e) {
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = e.target.dataset.tooltip;
    document.body.appendChild(tooltip);
    
    const rect = e.target.getBoundingClientRect();
    tooltip.style.top = rect.top - 30 + 'px';
    tooltip.style.left = rect.left + 'px';
    
    setTimeout(() => {
        tooltip.remove();
    }, 3000);
}

function hideTooltip() {
    const tooltip = document.querySelector('.tooltip');
    if (tooltip) tooltip.remove();
}
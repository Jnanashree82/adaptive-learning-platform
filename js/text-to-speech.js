let speechEnabled = false;
let currentUtterance = null;

function toggleSpeech() {
    speechEnabled = !speechEnabled;
    const btn = document.querySelector('.action-btn:last-child');
    btn.style.backgroundColor = speechEnabled ? '#4CAF50' : '';
}

function speakText(text) {
    if (!speechEnabled) return;
    
    // Cancel any ongoing speech
    if (currentUtterance) {
        window.speechSynthesis.cancel();
    }
    
    currentUtterance = new SpeechSynthesisUtterance(text);
    
    // Get available voices
    const voices = window.speechSynthesis.getVoices();
    
    // Try to find a natural sounding voice
    const preferredVoice = voices.find(voice => 
        voice.name.includes('Google UK') || voice.name.includes('Microsoft David')
    );
    
    if (preferredVoice) {
        currentUtterance.voice = preferredVoice;
    }
    
    // Adjust for neurodivergent users
    currentUtterance.rate = 0.9; // Slightly slower
    currentUtterance.pitch = 1;
    currentUtterance.volume = 1;
    
    window.speechSynthesis.speak(currentUtterance);
}

// Auto-speak page content for users with reading difficulties
function autoSpeakContent() {
    if (speechEnabled) {
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            const text = mainContent.innerText;
            speakText(text.substring(0, 500)); // Speak first 500 chars
        }
    }
}

// Speak on hover for better accessibility
document.addEventListener('mouseover', function(e) {
    if (speechEnabled && e.target.classList.contains('speak-hover')) {
        speakText(e.target.innerText);
    }
});
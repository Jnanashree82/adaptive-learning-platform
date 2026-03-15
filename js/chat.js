let chatHistory = [];
let isProcessing = false;

async function sendMessage() {
    if (isProcessing) return;
    
    const input = document.getElementById('userInput');
    const message = input.value.trim();
    
    if (!message) return;
    
    // Add user message to chat
    addMessage(message, 'user');
    input.value = '';
    
    isProcessing = true;
    
    try {
        // Show typing indicator
        showTypingIndicator();
        
        // Send to Python backend
        const response = await fetch('http://localhost:5000/chat', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ message: message })
        });
        
        const data = await response.json();
        
        // Remove typing indicator
        removeTypingIndicator();
        
        if (data.error) {
            addMessage('Sorry, I encountered an error. Please try again.', 'ai');
        } else {
            addMessage(data.response, 'ai');
            
            // If it's a fast response, highlight it
            if (data.fast_response) {
                highlightFastResponse();
            }
        }
        
    } catch (error) {
        removeTypingIndicator();
        addMessage('Network error. Please check your connection.', 'ai');
        console.error('Error:', error);
    }
    
    isProcessing = false;
}

function addMessage(text, sender) {
    const messagesDiv = document.getElementById('chatMessages');
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${sender}-message`;
    
    const contentDiv = document.createElement('div');
    contentDiv.className = 'message-content';
    contentDiv.textContent = text;
    
    messageDiv.appendChild(contentDiv);
    messagesDiv.appendChild(messageDiv);
    
    // Scroll to bottom
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

function showTypingIndicator() {
    const messagesDiv = document.getElementById('chatMessages');
    const indicator = document.createElement('div');
    indicator.className = 'message ai-message typing-indicator';
    indicator.id = 'typingIndicator';
    indicator.innerHTML = '<div class="message-content">AI is thinking...</div>';
    messagesDiv.appendChild(indicator);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

function removeTypingIndicator() {
    const indicator = document.getElementById('typingIndicator');
    if (indicator) {
        indicator.remove();
    }
}

function highlightFastResponse() {
    const lastMessage = document.querySelector('.ai-message:last-child');
    if (lastMessage) {
        lastMessage.style.backgroundColor = '#e8f5e8';
        lastMessage.style.borderLeft = '4px solid #4CAF50';
    }
}

function startVoiceInput() {
    if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
        alert('Voice input is not supported in your browser. Please use Chrome or Edge.');
        return;
    }
    
    const recognition = new (window.SpeechRecognition || window.webkitSpeechRecognition)();
    recognition.lang = 'en-US';
    recognition.interimResults = false;
    recognition.maxAlternatives = 1;
    
    recognition.start();
    
    recognition.onresult = function(event) {
        const speechResult = event.results[0][0].transcript;
        document.getElementById('userInput').value = speechResult;
        sendMessage();
    };
    
    recognition.onerror = function(event) {
        alert('Voice recognition error: ' + event.error);
    };
}

function speakLastMessage() {
    const lastMessage = document.querySelector('.ai-message:last-child .message-content');
    if (lastMessage) {
        const utterance = new SpeechSynthesisUtterance(lastMessage.textContent);
        utterance.rate = 0.9; // Slightly slower for better comprehension
        utterance.pitch = 1;
        window.speechSynthesis.speak(utterance);
    }
}

// Enter key to send (Shift+Enter for new line)
document.getElementById('userInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});
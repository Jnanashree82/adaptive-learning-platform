<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['username'] ?? 'there';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeuroLearn AI - Engineering Tutor | NeuroLearn</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/adaptive.css">
    <link rel="stylesheet" href="css/accessibility.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="js/global-accessibility.js" defer></script>
    
    <style>
        /* Your existing dashboard styles plus Nova AI styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .dashboard {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid #e5e7eb;
        }

        .sidebar-header h2 {
            color: #667eea;
            font-size: 24px;
        }

        .nav-menu {
            flex: 1;
            padding: 20px 0;
            list-style: none;
        }

        .nav-menu li {
            margin: 5px 15px;
        }

        .nav-menu li a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #6b7280;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
            gap: 12px;
        }

        .nav-menu li.active a {
            background: linear-gradient(135deg, #667eea20, #764ba220);
            color: #667eea;
            font-weight: 600;
        }

        .nav-menu li a:hover {
            background: #f8fafc;
            color: #667eea;
        }

        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
            min-height: 100vh;
        }

        /* Nova AI Chat Container */
        .nova-container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            overflow: hidden;
            height: 85vh;
            display: flex;
            flex-direction: column;
        }

        .nova-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.2);
        }

        .header-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .bot-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 24px;
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .header-info h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: white;
        }

        .status {
            font-size: 0.85rem;
            color: #10b981;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .status::before {
            content: '';
            display: block;
            width: 8px;
            height: 8px;
            background-color: #10b981;
            border-radius: 50%;
            box-shadow: 0 0 8px #10b981;
        }

        .header-actions {
            display: flex;
            gap: 12px;
        }

        .icon-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .icon-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.1);
        }

        .chat-main {
            flex-grow: 1;
            padding: 24px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .message {
            display: flex;
            flex-direction: column;
            max-width: 80%;
            animation: fadeIn 0.3s ease forwards;
            opacity: 0;
            transform: translateY(10px);
        }

        @keyframes fadeIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .user-message {
            align-self: flex-end;
        }

        .bot-message {
            align-self: flex-start;
        }

        .message-content {
            padding: 14px 18px;
            border-radius: 18px;
            line-height: 1.6;
            font-size: 0.95rem;
        }

        .user-message .message-content {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-bottom-right-radius: 4px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .bot-message .message-content {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            color: white;
            border-bottom-left-radius: 4px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .message-time {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.6);
            margin-top: 6px;
        }

        .user-message .message-time {
            text-align: right;
        }

        .chat-footer {
            padding: 20px 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.2);
        }

        .input-container {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            padding: 8px 16px;
            transition: all 0.2s ease;
        }

        .input-container:focus-within {
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.3);
        }

        textarea {
            flex-grow: 1;
            background: transparent;
            border: none;
            color: white;
            font-family: inherit;
            font-size: 0.95rem;
            resize: none;
            outline: none;
            max-height: 120px;
            padding: 8px 0;
            line-height: 1.5;
        }

        textarea::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .send-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .send-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .footer-text {
            text-align: center;
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.6);
            margin-top: 12px;
        }

        .typing-indicator {
            display: flex;
            gap: 4px;
            padding: 4px 8px;
            align-items: center;
            height: 24px;
        }

        .typing-dot {
            width: 6px;
            height: 6px;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 50%;
            animation: bounce 1.4s infinite ease-in-out both;
        }

        .typing-dot:nth-child(1) { animation-delay: -0.32s; }
        .typing-dot:nth-child(2) { animation-delay: -0.16s; }

        @keyframes bounce {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }

        pre {
            background: rgba(0, 0, 0, 0.3);
            padding: 12px;
            border-radius: 8px;
            overflow-x: auto;
            margin: 10px 0;
        }

        code {
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            .message {
                max-width: 90%;
            }
        }
    </style>
</head>
<body data-profile="<?php echo $_SESSION['accessibility']['profile'] ?? 'standard'; ?>"
      data-text-size="<?php echo $_SESSION['accessibility']['text_size'] ?? 100; ?>"
      data-high-contrast="<?php echo $_SESSION['accessibility']['high_contrast'] ?? 0; ?>"
      data-focus-mode="<?php echo $_SESSION['accessibility']['focus_mode'] ?? 0; ?>">
    
    <div class="dashboard">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h2>🧠 NeuroLearn</h2>
            </div>
           <ul class="nav-menu">
    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
        <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    </li>
    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'branches.php' ? 'active' : ''; ?>">
        <a href="branches.php"><i class="fas fa-book-open"></i> Branches</a>
    </li>
    <!-- ADD THIS LINE - PDF Analyzer -->
    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'pdf-analyzer.php' ? 'active' : ''; ?>">
        <a href="pdf-analyzer.php"><i class="fas fa-file-pdf"></i> PDF Analyzer</a>
    </li>
    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'my_notes.php' ? 'active' : ''; ?>">
        <a href="my_notes.php"><i class="fas fa-sticky-note"></i> My Notes</a>
    </li>
    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'chat.php' ? 'active' : ''; ?>">
        <a href="chat.php"><i class="fas fa-robot"></i> AI Tutor</a>
    </li>
    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
        <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
    </li>
    <li>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </li>
</ul>
        </nav>

        <!-- Main Content with Nova AI -->
        <main class="main-content">
            <div class="nova-container">
                <header class="nova-header">
                    <div class="header-info">
                        <div class="bot-avatar">
                            <i class="fa-solid fa-robot"></i>
                        </div>
                        <div>
                            <h1>NeuroLearn AI</h1>
                            <p class="status">Engineering Tutor</p>
                        </div>
                    </div>
                    <div class="header-actions">
                        <button id="theme-toggle" class="icon-btn"><i class="fa-solid fa-moon"></i></button>
                        <button id="models-btn" class="icon-btn" title="Available Models"><i class="fa-solid fa-cube"></i></button>
                        <button id="reset-key-btn" class="icon-btn" title="Reset"><i class="fa-solid fa-rotate-right"></i></button>
                    </div>
                </header>

                <main class="chat-main" id="chat-box">
                    <div class="message bot-message">
                        <div class="message-content">
                            <p>👋 Hello <?php echo htmlspecialchars($user_name); ?>! I'm Nova AI, your engineering tutor powered by Google Gemini.</p>
                            <p>I can help you with:</p>
                            <ul>
                                <li>📚 Data Structures & Algorithms</li>
                                <li>⚡ Ohm's Law & Electronics</li>
                                <li>🔥 Thermodynamics</li>
                                <li>🐍 Python Programming</li>
                                <li>🏗️ Civil Engineering</li>
                                <li>🤖 AI & Machine Learning</li>
                            </ul>
                            <p><strong>What would you like to learn today?</strong></p>
                        </div>
                        <div class="message-time">Just now</div>
                    </div>
                </main>

                <footer class="chat-footer">
                    <div class="input-container">
                        <textarea id="user-input" placeholder="Ask me anything about engineering..." rows="1"></textarea>
                        <button id="send-btn" class="send-btn"><i class="fa-solid fa-paper-plane"></i></button>
                    </div>
                    <div class="footer-text" id="model-info">Powered by Gemini 2.0 Flash</div>
                </footer>
            </div>
        </main>
    </div>

    <script>
    // ===== NOVA AI - GEMINI INTEGRATION =====
    document.addEventListener('DOMContentLoaded', () => {
        const chatBox = document.getElementById('chat-box');
        const userInput = document.getElementById('user-input');
        const sendBtn = document.getElementById('send-btn');
        const themeToggle = document.getElementById('theme-toggle');
        const modelsBtn = document.getElementById('models-btn');
        const resetKeyBtn = document.getElementById('reset-key-btn');
        const modelInfo = document.getElementById('model-info');

        // YOUR ACTUAL GEMINI API KEY
        const GEMINI_API_KEY = 'AIzaSyC-7b5tjUHFIg-Her_-m6ISoPnbx2be1Jw';
        
        // Available models from your account
        const AVAILABLE_MODELS = [
            'gemini-2.5-flash',
            'gemini-2.5-pro',
            'gemini-2.0-flash',
            'gemini-2.0-flash-lite'
        ];
        
        // Default model
        let currentModel = localStorage.getItem('gemini_selected_model') || 'gemini-2.5-flash';
        modelInfo.textContent = `Powered by ${currentModel}`;

        // Auto-resize textarea
        userInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
            if (this.value === '') {
                this.style.height = 'auto';
            }
        });

        // Theme toggle
        themeToggle.addEventListener('click', () => {
            document.body.classList.toggle('light-theme');
            const icon = themeToggle.querySelector('i');
            if (document.body.classList.contains('light-theme')) {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            } else {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
            }
        });

        // Show available models
        modelsBtn.addEventListener('click', () => {
            let message = "📋 **Available Models:**\n\n";
            AVAILABLE_MODELS.forEach((model, index) => {
                message += `${index + 1}. ${model} ${model === currentModel ? '✅ (current)' : ''}\n`;
            });
            message += "\nTo switch models, type: `/model gemini-2.5-flash`";
            addMessage(message, 'bot');
        });

        // Reset to default
        resetKeyBtn.addEventListener('click', () => {
            currentModel = 'gemini-2.5-flash';
            localStorage.setItem('gemini_selected_model', currentModel);
            modelInfo.textContent = `Powered by ${currentModel}`;
            addMessage("🔄 Reset to default model: gemini-2.5-flash", 'bot');
        });

        // Handle pressing Enter
        userInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                handleSend();
            }
        });

        sendBtn.addEventListener('click', (e) => {
            e.preventDefault();
            handleSend();
        });

        async function handleSend() {
            const text = userInput.value.trim();
            if (text === '') return;

            // Reset input
            userInput.value = '';
            userInput.style.height = 'auto';

            // Add user message
            addMessage(text, 'user');

            // Show typing indicator
            const typingId = showTypingIndicator();

            // Check for special commands
            if (text.toLowerCase().startsWith('/model ')) {
                const requestedModel = text.substring(7).trim();
                if (AVAILABLE_MODELS.includes(requestedModel)) {
                    currentModel = requestedModel;
                    localStorage.setItem('gemini_selected_model', currentModel);
                    modelInfo.textContent = `Powered by ${currentModel}`;
                    removeTypingIndicator(typingId);
                    addMessage(`✅ Switched to model: ${currentModel}`, 'bot');
                } else {
                    removeTypingIndicator(typingId);
                    addMessage(`❌ Model not found. Available models: ${AVAILABLE_MODELS.join(', ')}`, 'bot');
                }
                return;
            }

            if (text.toLowerCase() === '/models') {
                removeTypingIndicator(typingId);
                let message = "📋 **Available Models:**\n\n";
                AVAILABLE_MODELS.forEach(model => {
                    message += `• ${model}\n`;
                });
                addMessage(message, 'bot');
                return;
            }

            try {
                // Call Gemini API
                const response = await fetch(`https://generativelanguage.googleapis.com/v1beta/models/${currentModel}:generateContent?key=${GEMINI_API_KEY}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        contents: [{
                            parts: [{
                                text: `You are an expert engineering tutor. Answer the following question in detail with examples, formulas, and practical applications. Be accurate and educational: ${text}`
                            }]
                        }],
                        generationConfig: {
                            temperature: 0.7,
                            maxOutputTokens: 2048,
                            topP: 0.95,
                            topK: 40
                        }
                    })
                });

                const data = await response.json();
                removeTypingIndicator(typingId);

                if (data.candidates && data.candidates[0] && data.candidates[0].content) {
                    const aiResponse = data.candidates[0].content.parts[0].text;
                    addMessage(aiResponse, 'bot');
                    
                    // Save to chat history (if you want)
                    saveChat(text, aiResponse);
                } else {
                    console.error('API Error:', data);
                    if (data.error) {
                        addMessage(`⚠️ API Error: ${data.error.message}`, 'bot');
                    } else {
                        addMessage("⚠️ Sorry, I couldn't generate a response. Please try again.", 'bot');
                    }
                }
            } catch (error) {
                removeTypingIndicator(typingId);
                console.error('Error:', error);
                addMessage(`⚠️ Error: ${error.message}`, 'bot');
            }
        }

        function addMessage(text, sender) {
            const messageDiv = document.createElement('div');
            messageDiv.classList.add('message', `${sender}-message`);

            const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

            // Format text
            let formattedText = text
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>')
                .replace(/```(.*?)```/gs, '<pre><code>$1</code></pre>')
                .replace(/`(.*?)`/g, '<code>$1</code>')
                .replace(/\n/g, '<br>');

            messageDiv.innerHTML = `
                <div class="message-content">
                    ${formattedText}
                </div>
                <div class="message-time">${time}</div>
            `;

            chatBox.appendChild(messageDiv);
            scrollToBottom();
        }

        function showTypingIndicator() {
            const id = 'typing-' + Date.now();
            const typingDiv = document.createElement('div');
            typingDiv.classList.add('message', 'bot-message');
            typingDiv.id = id;

            typingDiv.innerHTML = `
                <div class="message-content">
                    <div class="typing-indicator">
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                    </div>
                </div>
            `;

            chatBox.appendChild(typingDiv);
            scrollToBottom();
            return id;
        }

        function removeTypingIndicator(id) {
            const indicator = document.getElementById(id);
            if (indicator) {
                indicator.remove();
            }
        }

        function scrollToBottom() {
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        function saveChat(message, response) {
            // Optional: Save to your database using save_chat.php
            fetch('save_chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    message: message,
                    response: response,
                    type: 'text'
                })
            }).catch(error => console.error('Error saving chat:', error));
        }

        // Focus input on load
        userInput.focus();
    });
    </script>
        <!-- Main Content -->
    <main class="main-content">
        ...
    </main>

    <?php include 'includes/voice-agent.php'; ?>

    <script>
    // ===== TEXT SIZE MANAGEMENT =====
    ...
    </script>
    <script> window.chtlConfig = { chatbotId: "6586829846" } </script>
    <script async data-id="6586829846" id="chtl-script" type="text/javascript" src="https://chatling.ai/js/embed.js"></script>
</body>
</html>
</body>
</html>
<?php
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeuroLearn - Fun Engineering Adventure</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/accessibility.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
     <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/adaptive.css">
    <link rel="stylesheet" href="css/accessibility.css">
    <link rel="stylesheet" href="css/modes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- JavaScript -->
    <script src="js/global-accessibility.js" defer></script>
    <script src="js/theme-loader.js" defer></script>
    <script src="js/accessibility.js" defer></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', 'Comic Sans MS', 'Chalkboard SE', sans-serif;
            overflow-x: hidden;
            color: white;
            background: #0a0a0a;
            transition: color 0.3s ease;
        }

        /* Full Screen Slider Background */
        .slider-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            background: #0a0a0a;
        }

        .slide-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            opacity: 0;
            transition: opacity 1.5s ease-in-out;
        }

        .slide-bg.active {
            opacity: 1;
        }

        .slide-bg::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(0,0,0,0.3) 0%, rgba(0,0,0,0.1) 100%);
        }

        /* Navbar - Fun Style */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            background: rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
            padding: 1rem 2rem;
            animation: slideDown 0.8s ease;
        }

        @keyframes slideDown {
            from { transform: translateY(-100%); }
            to { transform: translateY(0); }
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 2.2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #fff, #4facfe, #00f2fe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 10px;
            text-shadow: 0 0 20px rgba(79, 172, 254, 0.5);
        }

        .logo i {
            font-size: 2.5rem;
            color: #4facfe;
            -webkit-text-fill-color: initial;
            filter: drop-shadow(0 0 10px #4facfe);
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            padding: 0.6rem 1.2rem;
            border-radius: 30px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            text-shadow: 0 2px 5px rgba(0,0,0,0.3);
        }

        .nav-links a:not(.btn-primary) {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(5px);
        }

        .nav-links a:not(.btn-primary):hover {
            background: rgba(255, 255, 255, 0.25);
            transform: scale(1.05);
            box-shadow: 0 0 20px rgba(255,255,255,0.3);
        }

        .btn-primary {
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            color: white !important;
            box-shadow: 0 10px 20px rgba(79, 172, 254, 0.4);
            animation: glow 2s infinite;
        }

        @keyframes glow {
            0%, 100% { box-shadow: 0 10px 20px rgba(79, 172, 254, 0.4); }
            50% { box-shadow: 0 15px 30px rgba(79, 172, 254, 0.8); }
        }

        .btn-primary:hover {
            transform: scale(1.1) !important;
            box-shadow: 0 20px 40px rgba(79, 172, 254, 0.8);
        }

        /* Hero Section */
        .hero-section {
            position: relative;
            z-index: 2;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 6rem 2rem 4rem;
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
        }

        .hero-content {
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 50px;
            padding: 2.5rem;
            margin-bottom: 2.5rem;
            max-width: 800px;
            animation: fadeInUp 0.8s ease;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hero-content h1 {
            font-size: 4rem;
            font-weight: 800;
            margin-bottom: 1rem;
            line-height: 1.2;
            text-shadow: 0 10px 20px rgba(0,0,0,0.5);
            background: linear-gradient(135deg, #fff, #4facfe, #00f2fe, #a18cd1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: titleGlow 3s infinite;
        }

        @keyframes titleGlow {
            0%, 100% { filter: drop-shadow(0 0 10px rgba(79,172,254,0.5)); }
            50% { filter: drop-shadow(0 0 30px rgba(79,172,254,0.8)); }
        }

        .hero-content p {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            color: white;
            text-shadow: 0 5px 10px rgba(0,0,0,0.5);
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        .hero-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .hero-buttons a {
            padding: 1rem 2.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            border: 2px solid transparent;
        }

        .hero-buttons a i {
            font-size: 1.3rem;
            animation: spin 3s infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            25% { transform: rotate(10deg); }
            75% { transform: rotate(-10deg); }
            100% { transform: rotate(0deg); }
        }

        .hero-buttons a:first-child {
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            color: white;
            box-shadow: 0 15px 30px rgba(79, 172, 254, 0.4);
        }

        .hero-buttons a:first-child:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 20px 40px rgba(79, 172, 254, 0.6);
        }

        .hero-buttons a:last-child {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.4);
        }

        .hero-buttons a:last-child:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-5px) scale(1.05);
            border-color: rgba(255, 255, 255, 0.6);
        }

        /* Fun Stats */
        .fun-stats {
            display: flex;
            gap: 2rem;
            justify-content: center;
            margin-bottom: 3rem;
            flex-wrap: wrap;
        }

        .stat-item {
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            border-radius: 40px;
            padding: 1.2rem 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            border: 2px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            min-width: 180px;
            animation: float 3s infinite;
        }

        .stat-item:nth-child(1) { animation-delay: 0s; }
        .stat-item:nth-child(2) { animation-delay: 0.5s; }
        .stat-item:nth-child(3) { animation-delay: 1s; }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .stat-item:hover {
            transform: scale(1.05) translateY(-5px);
            background: rgba(0, 0, 0, 0.4);
            border-color: rgba(255, 255, 255, 0.4);
            box-shadow: 0 20px 30px rgba(0,0,0,0.3);
        }

        .stat-icon {
            font-size: 2.2rem;
            filter: drop-shadow(0 0 10px rgba(255,255,255,0.5));
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: white;
            line-height: 1.2;
            text-shadow: 0 0 10px rgba(255,255,255,0.5);
        }

        .stat-label {
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.95);
        }

        /* Features Grid */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            width: 100%;
            margin-top: 1rem;
        }

        .feature-card {
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 30px;
            padding: 2rem 1.5rem;
            text-align: center;
            color: white;
            transition: all 0.4s ease;
            cursor: pointer;
            animation: fadeInUp 0.8s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .feature-card:nth-child(1) { animation-delay: 0.1s; }
        .feature-card:nth-child(2) { animation-delay: 0.2s; }
        .feature-card:nth-child(3) { animation-delay: 0.3s; }
        .feature-card:nth-child(4) { animation-delay: 0.4s; }

        .feature-card:hover {
            transform: translateY(-10px) scale(1.02);
            background: rgba(0, 0, 0, 0.4);
            border-color: rgba(255, 255, 255, 0.4);
            box-shadow: 0 30px 40px rgba(0, 0, 0, 0.4);
        }

        .feature-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            filter: drop-shadow(0 0 15px currentColor);
            animation: iconPulse 2s infinite;
        }

        @keyframes iconPulse {
            0%, 100% { transform: scale(1); filter: drop-shadow(0 0 15px currentColor); }
            50% { transform: scale(1.1); filter: drop-shadow(0 0 25px currentColor); }
        }

        .feature-card:nth-child(1) .feature-icon { color: #ff6b6b; }
        .feature-card:nth-child(2) .feature-icon { color: #4ecdc4; }
        .feature-card:nth-child(3) .feature-icon { color: #ffe66d; }
        .feature-card:nth-child(4) .feature-icon { color: #a8e6cf; }

        .feature-card h3 {
            font-size: 1.6rem;
            margin-bottom: 0.8rem;
            font-weight: 700;
            text-shadow: 0 2px 5px rgba(0,0,0,0.3);
        }

        .feature-card p {
            font-size: 1rem;
            line-height: 1.5;
            opacity: 0.95;
            flex: 1;
        }

        /* Color Magic Controls */
        .color-controls {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            padding: 1.2rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            z-index: 1000;
            display: none;
            animation: slideInRight 0.5s ease;
            border: 2px solid rgba(255,255,255,0.2);
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .color-controls.show {
            display: block;
        }

        .color-controls h4 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.1rem;
            text-align: center;
        }

        .color-palette {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.8rem;
            margin-bottom: 1rem;
        }

        .color-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 3px solid white;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .color-btn:hover {
            transform: scale(1.2);
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
        }

        .color-btn.red { background: #ff4444; }
        .color-btn.blue { background: #4facfe; }
        .color-btn.green { background: #00c851; }
        .color-btn.yellow { background: #ffbb33; }
        .color-btn.purple { background: #aa66cc; }
        .color-btn.pink { background: #ff6b6b; }
        .color-btn.orange { background: #ff8800; }
        .color-btn.teal { background: #39cccc; }

        .reset-color {
            width: 100%;
            padding: 0.8rem;
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            color: white;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 0.5rem;
        }

        .reset-color:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 20px rgba(79,172,254,0.4);
        }

        .close-controls {
            position: absolute;
            top: 10px;
            right: 15px;
            background: none;
            border: none;
            font-size: 1.2rem;
            color: #666;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .close-controls:hover {
            color: #ff4444;
            transform: scale(1.2);
        }

        /* Color classes for text */
        body.color-red .hero-content h1,
        body.color-red .hero-content p,
        body.color-red .feature-card h3,
        body.color-red .feature-card p,
        body.color-red .stat-number,
        body.color-red .stat-label {
            color: #ff4444 !important;
        }

        body.color-blue .hero-content h1,
        body.color-blue .hero-content p,
        body.color-blue .feature-card h3,
        body.color-blue .feature-card p,
        body.color-blue .stat-number,
        body.color-blue .stat-label {
            color: #4facfe !important;
        }

        body.color-green .hero-content h1,
        body.color-green .hero-content p,
        body.color-green .feature-card h3,
        body.color-green .feature-card p,
        body.color-green .stat-number,
        body.color-green .stat-label {
            color: #00c851 !important;
        }

        body.color-yellow .hero-content h1,
        body.color-yellow .hero-content p,
        body.color-yellow .feature-card h3,
        body.color-yellow .feature-card p,
        body.color-yellow .stat-number,
        body.color-yellow .stat-label {
            color: #ffbb33 !important;
        }

        body.color-purple .hero-content h1,
        body.color-purple .hero-content p,
        body.color-purple .feature-card h3,
        body.color-purple .feature-card p,
        body.color-purple .stat-number,
        body.color-purple .stat-label {
            color: #aa66cc !important;
        }

        /* Talking Books functionality */
        .speaking-mode .feature-card {
            border-color: #4ecdc4 !important;
            box-shadow: 0 0 30px #4ecdc4 !important;
        }

        .speaking-indicator {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #4ecdc4;
            color: white;
            padding: 10px 20px;
            border-radius: 30px;
            font-weight: 600;
            display: none;
            align-items: center;
            gap: 10px;
            z-index: 1001;
            animation: pulse 1s infinite;
        }

        .speaking-indicator.show {
            display: flex;
        }

        .speaking-indicator i {
            animation: speakPulse 1s infinite;
        }

        @keyframes speakPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }

        /* AI Buddy functionality */
        .ai-buddy-popup {
            position: fixed;
            bottom: 100px;
            right: 30px;
            background: white;
            border-radius: 30px;
            padding: 1.5rem;
            width: 300px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            display: none;
            z-index: 1002;
            animation: slideInUp 0.5s ease;
        }

        .ai-buddy-popup.show {
            display: block;
        }

        .ai-buddy-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 1rem;
            color: #333;
        }

        .ai-buddy-header i {
            font-size: 2rem;
            color: #ffe66d;
        }

        .ai-buddy-message {
            background: #f0f4ff;
            padding: 1rem;
            border-radius: 20px;
            color: #333;
            margin-bottom: 1rem;
        }

        .ai-buddy-input {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e0e7ff;
            border-radius: 15px;
            margin-bottom: 0.5rem;
        }

        .ai-buddy-send {
            width: 100%;
            padding: 0.8rem;
            background: linear-gradient(135deg, #ffe66d, #ffbb33);
            border: none;
            border-radius: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .ai-buddy-send:hover {
            transform: scale(1.05);
        }

        .close-ai {
            position: absolute;
            top: 10px;
            right: 15px;
            background: none;
            border: none;
            font-size: 1.2rem;
            color: #666;
            cursor: pointer;
        }

        /* Quick Learning animation */
        .quick-learning-active .feature-card {
            animation: quickLearn 1s infinite !important;
        }

        @keyframes quickLearn {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .hero-content h1 {
                font-size: 3.5rem;
            }

            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2.8rem;
            }

            .hero-content p {
                font-size: 1.3rem;
            }

            .features-grid {
                grid-template-columns: 1fr;
                gap: 1.2rem;
            }

            .fun-stats {
                flex-direction: column;
                align-items: center;
                width: 100%;
            }

            .stat-item {
                width: 100%;
                justify-content: center;
            }

            .color-controls {
                bottom: 20px;
                right: 20px;
                left: 20px;
                width: auto;
            }

            .color-palette {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (max-width: 480px) {
            .nav-container {
                flex-direction: column;
                gap: 1rem;
            }

            .hero-content h1 {
                font-size: 2.2rem;
            }

            .hero-content {
                padding: 1.5rem;
            }

            .hero-buttons {
                flex-direction: column;
            }

            .hero-buttons a {
                width: 100%;
            }
        }
    </style>
</head>
<body data-profile="<?php echo $_SESSION['accessibility']['profile'] ?? 'standard'; ?>"
      data-text-size="<?php echo $_SESSION['accessibility']['text_size'] ?? 100; ?>"
      data-high-contrast="<?php echo $_SESSION['accessibility']['high_contrast'] ?? 0; ?>"
      data-focus-mode="<?php echo $_SESSION['accessibility']['focus_mode'] ?? 0; ?>"
      data-theme="<?php echo $_SESSION['accessibility']['theme_mode'] ?? 'light'; ?>"
      id="mainBody">
    
    <!-- Full Screen Slider Background -->
    <div class="slider-background" id="sliderBackground">
        <div class="slide-bg active" style="background-image: url('https://images.unsplash.com/photo-1555949963-aa79dcee981c?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');"></div>
        <div class="slide-bg" style="background-image: url('https://images.unsplash.com/photo-1550751827-4bd374c3f58b?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');"></div>
        <div class="slide-bg" style="background-image: url('https://images.unsplash.com/photo-1541701494587-cb58502866ab?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');"></div>
        <div class="slide-bg" style="background-image: url('https://images.unsplash.com/photo-1446776811953-b23d57bd21aa?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');"></div>
        <div class="slide-bg" style="background-image: url('https://images.unsplash.com/photo-1555066931-4365d14bab8c?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');"></div>
        <div class="slide-bg" style="background-image: url('https://images.unsplash.com/photo-1519999482643-250698d42fd0?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');"></div>
        <div class="slide-bg" style="background-image: url('https://images.unsplash.com/photo-1485827404703-89b55fcc595e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');"></div>
        <div class="slide-bg" style="background-image: url('https://images.unsplash.com/photo-1541701494587-cb58502866ab?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');"></div>
        <div class="slide-bg" style="background-image: url('https://images.unsplash.com/photo-1542751371-adc38448a05e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');"></div>
        <div class="slide-bg" style="background-image: url('https://images.unsplash.com/photo-1626379692970-6f178be6aa6f?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');"></div>
    </div>

    <!-- Color Magic Control Panel -->
    <div class="color-controls" id="colorControls">
        <button class="close-controls" onclick="closeColorControls()">
            <i class="fas fa-times"></i>
        </button>
        <h4><i class="fas fa-palette"></i> Choose Your Color</h4>
        <div class="color-palette">
            <button class="color-btn red" onclick="changeColor('red')" title="Red"></button>
            <button class="color-btn blue" onclick="changeColor('blue')" title="Blue"></button>
            <button class="color-btn green" onclick="changeColor('green')" title="Green"></button>
            <button class="color-btn yellow" onclick="changeColor('yellow')" title="Yellow"></button>
            <button class="color-btn purple" onclick="changeColor('purple')" title="Purple"></button>
            <button class="color-btn pink" onclick="changeColor('pink')" title="Pink"></button>
            <button class="color-btn orange" onclick="changeColor('orange')" title="Orange"></button>
            <button class="color-btn teal" onclick="changeColor('teal')" title="Teal"></button>
        </div>
        <button class="reset-color" onclick="resetColor()">
            <i class="fas fa-undo"></i> Reset to Default
        </button>
    </div>

    <!-- Speaking Indicator -->
    <div class="speaking-indicator" id="speakingIndicator">
        <i class="fas fa-volume-up"></i>
        <span>Reading Mode Active</span>
        <button onclick="stopSpeaking()" style="background: none; border: none; color: white; margin-left: 10px; cursor: pointer;">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- AI Buddy Popup -->
    <div class="ai-buddy-popup" id="aiBuddyPopup">
        <button class="close-ai" onclick="closeAIBuddy()">
            <i class="fas fa-times"></i>
        </button>
        <div class="ai-buddy-header">
            <i class="fas fa-robot"></i>
            <h4>AI Buddy</h4>
        </div>
        <div class="ai-buddy-message" id="aiMessage">
            Hi! I'm your AI learning buddy. Ask me anything!
        </div>
        <input type="text" class="ai-buddy-input" id="aiInput" placeholder="Type your question...">
        <button class="ai-buddy-send" onclick="askAI()">
            <i class="fas fa-paper-plane"></i> Send
        </button>
    </div>

    <div class="landing-page">
        <nav class="navbar">
            <div class="nav-container">
                <div class="logo">
                    <i class="fas fa-brain"></i>
                    NeuroLearn
                </div>
                <div class="nav-links">
                    <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <a href="register.php" class="btn-primary"><i class="fas fa-user-plus"></i> Sign up</a>
                </div>
            </div>
        </nav>
        
        <main class="hero-section">
            <div class="hero-content">
                <h1>🎮 Learn Like a GAME!</h1>
                <p>🚀 Explore • Create • Level Up • Have Fun</p>
                <div class="hero-buttons">
                    <a href="register.php">
                        <i class="fas fa-rocket"></i>
                        Start Your Adventure
                    </a>
                    <a href="#features">
                        <i class="fas fa-compass"></i>
                        Discover Powers
                    </a>
                </div>
            </div>

            <!-- Fun Stats -->
            <div class="fun-stats">
                <div class="stat-item">
                    <span class="stat-icon">🎮</span>
                    <div>
                        <div class="stat-number">100+</div>
                        <div class="stat-label">Quests</div>
                    </div>
                </div>
                <div class="stat-item">
                    <span class="stat-icon">🏆</span>
                    <div>
                        <div class="stat-number">50+</div>
                        <div class="stat-label">Achievements</div>
                    </div>
                </div>
                <div class="stat-item">
                    <span class="stat-icon">🤖</span>
                    <div>
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">AI Buddy</div>
                    </div>
                </div>
            </div>
            
            <!-- Features Grid -->
            <div class="features-grid" id="features">
                <!-- Color Magic -->
                <div class="feature-card" onclick="openColorMagic()">
                    <div class="feature-icon">🎨</div>
                    <h3>Color Magic</h3>
                    <p>Change colors, fonts & sizes - Make it YOUR world!</p>
                </div>
                
                <!-- Talking Books -->
                <div class="feature-card" onclick="toggleTalkingBooks()">
                    <div class="feature-icon">🎧</div>
                    <h3>Talking Books</h3>
                    <p>Listen to stories while playing! No reading needed.</p>
                </div>
                
                <!-- AI Game Buddy -->
                <div class="feature-card" onclick="openAIBuddy()">
                    <div class="feature-icon">🤖</div>
                    <h3>AI Game Buddy</h3>
                    <p>Ask questions, get help - like magic!</p>
                </div>
                
                <!-- Quick Learning -->
                <div class="feature-card" onclick="toggleQuickLearning()">
                    <div class="feature-icon">⚡</div>
                    <h3>Quick Learning</h3>
                    <p>Simple, fun, exciting - Learn while having fun!</p>
                </div>
            </div>
        </main>
    </div>

    <script>
    // Background Slider
    const slides = document.querySelectorAll('.slide-bg');
    let currentSlide = 0;

    function changeBackground() {
        slides[currentSlide].classList.remove('active');
        currentSlide = (currentSlide + 1) % slides.length;
        slides[currentSlide].classList.add('active');
    }

    setInterval(changeBackground, 4000);

    // Color Magic Functions
    function openColorMagic() {
        document.getElementById('colorControls').classList.add('show');
        showToast('🎨 Color Magic activated! Choose your favorite color!');
    }

    function closeColorControls() {
        document.getElementById('colorControls').classList.remove('show');
    }

    function changeColor(color) {
        const body = document.getElementById('mainBody');
        // Remove all color classes
        body.classList.remove('color-red', 'color-blue', 'color-green', 'color-yellow', 'color-purple', 'color-pink', 'color-orange', 'color-teal');
        // Add selected color class
        body.classList.add('color-' + color);
        
        // Change gradient colors
        const logo = document.querySelector('.logo');
        if (color === 'red') {
            logo.style.background = 'linear-gradient(135deg, #fff, #ff4444, #ff6b6b)';
            logo.style.webkitBackgroundClip = 'text';
        } else if (color === 'blue') {
            logo.style.background = 'linear-gradient(135deg, #fff, #4facfe, #00f2fe)';
            logo.style.webkitBackgroundClip = 'text';
        } else if (color === 'green') {
            logo.style.background = 'linear-gradient(135deg, #fff, #00c851, #2ecc71)';
            logo.style.webkitBackgroundClip = 'text';
        }
        
        showToast(`✨ Color changed to ${color}!`);
    }

    function resetColor() {
        const body = document.getElementById('mainBody');
        body.classList.remove('color-red', 'color-blue', 'color-green', 'color-yellow', 'color-purple', 'color-pink', 'color-orange', 'color-teal');
        
        const logo = document.querySelector('.logo');
        logo.style.background = 'linear-gradient(135deg, #fff, #4facfe, #00f2fe)';
        logo.style.webkitBackgroundClip = 'text';
        
        showToast('🌈 Colors reset to default!');
    }

    // Talking Books Functions
    let speakingActive = false;
    let speechSynth = window.speechSynthesis;
    let currentUtterance = null;

    function toggleTalkingBooks() {
        speakingActive = !speakingActive;
        const indicator = document.getElementById('speakingIndicator');
        const body = document.getElementById('mainBody');
        
        if (speakingActive) {
            indicator.classList.add('show');
            body.classList.add('speaking-mode');
            readPageContent();
            showToast('🎧 Reading mode activated! Listening to page content...');
        } else {
            indicator.classList.remove('show');
            body.classList.remove('speaking-mode');
            stopSpeaking();
        }
    }

    function readPageContent() {
        if (!speakingActive) return;
        
        // Get all text content
        const heroTitle = document.querySelector('.hero-content h1')?.innerText || '';
        const heroText = document.querySelector('.hero-content p')?.innerText || '';
        const featureTexts = Array.from(document.querySelectorAll('.feature-card h3, .feature-card p'))
            .map(el => el.innerText).join('. ');
        
        const fullText = `Welcome to NeuroLearn. ${heroTitle}. ${heroText}. Features include: ${featureTexts}`;
        
        const utterance = new SpeechSynthesisUtterance(fullText);
        utterance.rate = 0.9;
        utterance.pitch = 1;
        utterance.onend = function() {
            if (speakingActive) {
                setTimeout(readPageContent, 5000); // Repeat every 5 seconds
            }
        };
        
        speechSynth.cancel();
        speechSynth.speak(utterance);
        currentUtterance = utterance;
    }

    function stopSpeaking() {
        speakingActive = false;
        speechSynth.cancel();
        document.getElementById('speakingIndicator').classList.remove('show');
        document.getElementById('mainBody').classList.remove('speaking-mode');
    }

    // AI Buddy Functions
    function openAIBuddy() {
        document.getElementById('aiBuddyPopup').classList.add('show');
        showToast('🤖 AI Buddy is here to help!');
    }

    function closeAIBuddy() {
        document.getElementById('aiBuddyPopup').classList.remove('show');
    }

    function askAI() {
        const input = document.getElementById('aiInput');
        const question = input.value.trim();
        const messageEl = document.getElementById('aiMessage');
        
        if (question) {
            messageEl.innerHTML = `You asked: "${question}"<br><br>🤖 I'm your AI buddy! In the full version, I'll give you smart answers about engineering, learning, and more!`;
            input.value = '';
            
            // Simulate typing
            messageEl.style.animation = 'none';
            setTimeout(() => {
                messageEl.style.animation = 'fadeInUp 0.5s ease';
            }, 10);
        } else {
            messageEl.innerHTML = 'Please type a question first! 🤔';
        }
    }

    // Quick Learning Function
    let quickLearningActive = false;

    function toggleQuickLearning() {
        quickLearningActive = !quickLearningActive;
        const body = document.getElementById('mainBody');
        
        if (quickLearningActive) {
            body.classList.add('quick-learning-active');
            showToast('⚡ Quick Learning mode activated! Everything moves faster!');
            
            // Speed up background slider
            clearInterval(window.sliderInterval);
            window.sliderInterval = setInterval(changeBackground, 2000);
        } else {
            body.classList.remove('quick-learning-active');
            showToast('✨ Back to normal pace');
            
            // Reset background slider
            clearInterval(window.sliderInterval);
            window.sliderInterval = setInterval(changeBackground, 4000);
        }
    }

    // Toast notification
    function showToast(message) {
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            bottom: 100px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            color: white;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            z-index: 2000;
            animation: slideInUp 0.5s ease;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        `;
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'fadeOut 0.5s ease';
            setTimeout(() => toast.remove(), 500);
        }, 3000);
    }

    // Add fadeOut animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeOut {
            from { opacity: 1; transform: translateX(-50%) translateY(0); }
            to { opacity: 0; transform: translateX(-50%) translateY(20px); }
        }
    `;
    document.head.appendChild(style);

    // Intersection observer for feature cards
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    });

    document.querySelectorAll('.feature-card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.style.transition = 'all 0.6s ease';
        observer.observe(card);
    });

    // Click effect
    document.querySelectorAll('.feature-card, .btn-primary, .stat-item').forEach(el => {
        el.addEventListener('click', (e) => {
            if (!el.classList.contains('feature-card')) {
                el.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    el.style.transform = '';
                }, 200);
            }
        });
    });

    // Preload images
    function preloadImages() {
        const images = [
            'https://images.unsplash.com/photo-1555949963-aa79dcee981c?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80',
            'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80',
            'https://images.unsplash.com/photo-1541701494587-cb58502866ab?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80',
            'https://images.unsplash.com/photo-1446776811953-b23d57bd21aa?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80',
            'https://images.unsplash.com/photo-1555066931-4365d14bab8c?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80',
            'https://images.unsplash.com/photo-1519999482643-250698d42fd0?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80',
            'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80',
            'https://images.unsplash.com/photo-1541701494587-cb58502866ab?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80',
            'https://images.unsplash.com/photo-1542751371-adc38448a05e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80',
            'https://images.unsplash.com/photo-1626379692970-6f178be6aa6f?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80'
        ];
        
        images.forEach(src => {
            const img = new Image();
            img.src = src;
        });
    }
    
    window.addEventListener('load', preloadImages);
    window.sliderInterval = setInterval(changeBackground, 4000);
    </script>
</body>
</html>
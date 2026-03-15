<?php
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $full_name = $_POST['full_name'];
    $neuro_type = $_POST['neuro_type'];
    
    // Set initial accessibility profile based on neuro type
    $accessibility_profile = 'standard';
    if ($neuro_type == 'dyslexia') {
        $accessibility_profile = 'dyslexia';
    } elseif ($neuro_type == 'adhd' || $neuro_type == 'both') {
        $accessibility_profile = 'adhd';
    }
    
    $sql = "INSERT INTO users (username, email, password, full_name, neuro_type, accessibility_profile) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    try {
        $stmt->execute([$username, $email, $password, $full_name, $neuro_type, $accessibility_profile]);
        header('Location: login.php?registered=1');
        exit();
    } catch(PDOException $e) {
        $error = "Registration failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - NeuroLearn</title>
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Full Screen Slider Background - Same as front page */
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
            background: linear-gradient(135deg, rgba(0,0,0,0.4) 0%, rgba(0,0,0,0.2) 100%);
        }

        /* Auth Container - White middle box with animations */
        .auth-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 500px;
            margin: 2rem;
            animation: floatIn 0.8s ease;
        }

        @keyframes floatIn {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .auth-box {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 40px;
            padding: 2.5rem;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.2);
            animation: glowPulse 3s infinite;
        }

        @keyframes glowPulse {
            0%, 100% { box-shadow: 0 30px 60px rgba(79, 172, 254, 0.2); }
            50% { box-shadow: 0 40px 80px rgba(79, 172, 254, 0.4); }
        }

        .auth-box h2 {
            font-size: 2.2rem;
            margin-bottom: 1rem;
            color: #1e293b;
            text-align: center;
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: titleGlow 3s infinite;
        }

        @keyframes titleGlow {
            0%, 100% { filter: drop-shadow(0 0 5px rgba(79,172,254,0.3)); }
            50% { filter: drop-shadow(0 0 15px rgba(79,172,254,0.6)); }
        }

        .info-box {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 15px;
            animation: slideInLeft 0.6s ease;
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .info-box h4 {
            color: #1976d2;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-box h4 i {
            animation: spin 3s infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            25% { transform: rotate(10deg); }
            75% { transform: rotate(-10deg); }
            100% { transform: rotate(0deg); }
        }

        .auth-form {
            margin-top: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            animation: fadeInUp 0.5s ease;
            animation-fill-mode: both;
        }

        .form-group:nth-child(1) { animation-delay: 0.1s; }
        .form-group:nth-child(2) { animation-delay: 0.2s; }
        .form-group:nth-child(3) { animation-delay: 0.3s; }
        .form-group:nth-child(4) { animation-delay: 0.4s; }
        .form-group:nth-child(5) { animation-delay: 0.5s; }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .form-group label i {
            color: #4facfe;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 1rem 1.2rem;
            border: 2px solid #e0e7ff;
            border-radius: 20px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #4facfe;
            box-shadow: 0 0 0 4px rgba(79, 172, 254, 0.2);
            transform: scale(1.02);
        }

        .form-group input:hover,
        .form-group select:hover {
            border-color: #00f2fe;
        }

        /* Profile Preview */
        #profilePreview {
            margin: 20px 0;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 20px;
            animation: slideInUp 0.5s ease;
            border-left: 4px solid #4facfe;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        #previewText {
            margin-top: 10px;
            color: #2c3e50;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        /* Button */
        .btn-primary {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #eef0f1, #00f2fe);
            color: white;
            border: none;
            border-radius: 30px;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 1rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }

        .btn-primary:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 20px 40px rgba(79, 172, 254, 0.4);
        }

        .btn-primary i {
            animation: bounce 1s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateX(0); }
            50% { transform: translateX(5px); }
        }

        .auth-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #2c3e50;
        }

        .auth-link a {
            color: #4facfe;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .auth-link a:hover {
            color: #00f2fe;
            text-decoration: underline;
        }

        .error {
            background: #fee2e2;
            color: #dc2626;
            padding: 1rem;
            border-radius: 15px;
            margin-bottom: 1rem;
            border-left: 4px solid #dc2626;
            animation: shake 0.5s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .auth-container {
                margin: 1rem;
            }
            
            .auth-box {
                padding: 1.5rem;
            }
            
            .auth-box h2 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body data-profile="<?php echo $_SESSION['accessibility']['profile'] ?? 'standard'; ?>"
      data-text-size="<?php echo $_SESSION['accessibility']['text_size'] ?? 100; ?>"
      data-high-contrast="<?php echo $_SESSION['accessibility']['high_contrast'] ?? 0; ?>"
      data-focus-mode="<?php echo $_SESSION['accessibility']['focus_mode'] ?? 0; ?>"
      data-theme="<?php echo $_SESSION['accessibility']['theme_mode'] ?? 'light'; ?>"
      class="<?php echo isset($_SESSION['accessibility']) ? '' : ''; ?>">
    
    <!-- Full Screen Slider Background - Images with robots, books, brains, and learning themes -->
    <div class="slider-background" id="sliderBackground">
        <!-- Image 1: Robot and AI learning -->
        <div class="slide-bg active" style="background-image: url('https://images.unsplash.com/photo-1485827404703-89b55fcc595e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');"></div>
        
        <!-- Image 2: Books and library with brain concept -->
        <div class="slide-bg" style="background-image: url('https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');"></div>
        
        <!-- Image 3: Brain network / neural connections -->
        <div class="slide-bg" style="background-image: url('https://images.unsplash.com/photo-1555949963-aa79dcee981c?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');"></div>
        
        <!-- Image 4: Robot reading a book -->
        <div class="slide-bg" style="background-image: url('https://images.unsplash.com/photo-1531746790731-6c087fecd65a?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');"></div>
        
        <!-- Image 5: Brain with lights/technology -->
        <div class="slide-bg" style="background-image: url('https://images.unsplash.com/photo-1550751827-4bd374c3f58b?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');"></div>
        
        <!-- Image 6: Modern library with technology -->
        <div class="slide-bg" style="background-image: url('https://images.unsplash.com/photo-1507842217343-583bb7270b66?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');"></div>
        
        <!-- Image 7: AI robot concept -->
        <div class="slide-bg" style="background-image: url('https://images.unsplash.com/photo-1535378919842-064a1c6485a0?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');"></div>
        
        <!-- Image 8: Brain scan / neuroscience -->
        <div class="slide-bg" style="background-image: url('https://images.unsplash.com/photo-1559757175-0b30bd97c52d?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');"></div>
        
        <!-- Image 9: Student studying with tech -->
        <div class="slide-bg" style="background-image: url('https://images.unsplash.com/photo-1516321497487-e288fb19713f?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');"></div>
        
        <!-- Image 10: Abstract brain/learning concept -->
        <div class="slide-bg" style="background-image: url('https://images.unsplash.com/photo-1542744094-3a31f272c2d8?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');"></div>
    </div>

    <div class="auth-container">
        <div class="auth-box">
            <h2><i class="fas fa-brain"></i> Create Your Account</h2>
            
            <?php if(isset($error)) echo "<p class='error'><i class='fas fa-exclamation-circle'></i> $error</p>"; ?>
            
            <div class="info-box">
                <h4><i class="fas fa-magic"></i> 🌟 Personalized Learning</h4>
                <p>Select your learning preference to get automatically optimized settings. You can always adjust these later in Settings.</p>
            </div>
            
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Full Name</label>
                    <input type="text" name="full_name" placeholder="Enter your full name" required>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-at"></i> Username</label>
                    <input type="text" name="username" placeholder="Choose a username" required>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" name="email" placeholder="your@email.com" required>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" placeholder="Create a password" required>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-robot"></i> Learning Preference</label>
                    <select name="neuro_type" id="neuroType" onchange="showProfilePreview()">
                        <option value="">Standard Learning Profile</option>
                        <option value="dyslexia">📖 Dyslexia (specialized font & spacing)</option>
                        <option value="adhd">🎯 ADHD (focus mode, chunked content)</option>
                        <option value="both">🌟 Both Dyslexia & ADHD</option>
                        <option value="other">✨ Other</option>
                    </select>
                </div>
                
                <div id="profilePreview" style="display: none;">
                    <strong><i class="fas fa-eye"></i> Preview:</strong>
                    <p id="previewText">Your selected profile will optimize your learning experience.</p>
                </div>
                
                <button type="submit" class="btn-primary">
                    <i class="fas fa-rocket"></i> Start Your Journey
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>
            
            <p class="auth-link">Already have an account? <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></p>
        </div>
    </div>

    <script>
    // Background Slider - Same as front page
    const slides = document.querySelectorAll('.slide-bg');
    let currentSlide = 0;

    function changeBackground() {
        slides[currentSlide].classList.remove('active');
        currentSlide = (currentSlide + 1) % slides.length;
        slides[currentSlide].classList.add('active');
    }

    setInterval(changeBackground, 4000);

    // Profile preview function
    function showProfilePreview() {
        const neuroType = document.getElementById('neuroType').value;
        const preview = document.getElementById('profilePreview');
        const previewText = document.getElementById('previewText');
        
        if (neuroType) {
            preview.style.display = 'block';
            preview.style.animation = 'slideInUp 0.5s ease';
            
            switch(neuroType) {
                case 'dyslexia':
                    previewText.innerHTML = '✨ <strong>Dyslexia-friendly mode:</strong> OpenDyslexic font, increased letter spacing, high contrast options, and text-to-speech support.';
                    break;
                case 'adhd':
                    previewText.innerHTML = '🎯 <strong>ADHD focus mode:</strong> Minimized distractions, chunked content, focus ruler, reading timer, and voice controls.';
                    break;
                case 'both':
                    previewText.innerHTML = '🌟 <strong>Combined mode:</strong> Dyslexia font with ADHD focus features. Full customization available in settings.';
                    break;
                case 'other':
                    previewText.innerHTML = '✨ <strong>Standard mode:</strong> You can enable specific accessibility features in Settings later.';
                    break;
                default:
                    previewText.innerHTML = 'Standard mode with optional accessibility features you can enable later.';
            }
        } else {
            preview.style.display = 'none';
        }
    }

    // Preload images
    function preloadImages() {
        const images = [
            'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80',
            'https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80',
            'https://images.unsplash.com/photo-1555949963-aa79dcee981c?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80',
            'https://images.unsplash.com/photo-1531746790731-6c087fecd65a?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80',
            'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80',
            'https://images.unsplash.com/photo-1507842217343-583bb7270b66?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80',
            'https://images.unsplash.com/photo-1535378919842-064a1c6485a0?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80',
            'https://images.unsplash.com/photo-1559757175-0b30bd97c52d?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80',
            'https://images.unsplash.com/photo-1516321497487-e288fb19713f?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80',
            'https://images.unsplash.com/photo-1542744094-3a31f272c2d8?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80'
        ];
        
        images.forEach(src => {
            const img = new Image();
            img.src = src;
        });
    }
    
    window.addEventListener('load', preloadImages);
    </script>
</body>
</html>
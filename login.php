<?php
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['neuro_type'] = $user['neuro_type'];
        
        // Set accessibility preferences
        $_SESSION['accessibility'] = [
            'profile' => $user['accessibility_profile'] ?? 'standard',
            'text_size' => $user['text_size'] ?? 100,
            'color_blind_mode' => $user['color_blind_mode'] ?? 'none',
            'high_contrast' => $user['high_contrast'] ?? 0,
            'focus_mode' => $user['focus_mode'] ?? 0,
            'theme_mode' => $user['theme_mode'] ?? 'light'
        ];
        
        header('Location: dashboard.php');
        exit();
    } else {
        $error = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - NeuroLearn</title>
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

        /* Full Screen Slider Background - Same as front page and register.php */
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
            max-width: 450px;
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
            margin-bottom: 1.5rem;
            color: #1e293b;
            text-align: center;
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: titleGlow 3s infinite;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .auth-box h2 i {
            font-size: 2rem;
            color: #4facfe;
            -webkit-text-fill-color: initial;
            animation: spin 3s infinite;
        }

        @keyframes titleGlow {
            0%, 100% { filter: drop-shadow(0 0 5px rgba(79,172,254,0.3)); }
            50% { filter: drop-shadow(0 0 15px rgba(79,172,254,0.6)); }
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            25% { transform: rotate(10deg); }
            75% { transform: rotate(-10deg); }
            100% { transform: rotate(0deg); }
        }

        /* Success Message */
        .success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            padding: 1rem;
            border-radius: 15px;
            margin-bottom: 1rem;
            border-left: 4px solid #28a745;
            animation: slideInLeft 0.5s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .success i {
            font-size: 1.2rem;
            animation: bounce 1s infinite;
        }

        /* Error Message */
        .error {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #dc2626;
            padding: 1rem;
            border-radius: 15px;
            margin-bottom: 1rem;
            border-left: 4px solid #dc2626;
            animation: shake 0.5s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .error i {
            font-size: 1.2rem;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
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

        @keyframes bounce {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }

        /* Form Styles */
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
            gap: 8px;
        }

        .form-group label i {
            color: #4facfe;
            animation: labelPulse 2s infinite;
        }

        @keyframes labelPulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .form-group input {
            width: 100%;
            padding: 1rem 1.2rem;
            border: 2px solid #e0e7ff;
            border-radius: 20px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }

        .form-group input:focus {
            outline: none;
            border-color: #4facfe;
            box-shadow: 0 0 0 4px rgba(79, 172, 254, 0.2);
            transform: scale(1.02);
        }

        .form-group input:hover {
            border-color: #00f2fe;
        }

        .form-group input::placeholder {
            color: #a0aec0;
            font-size: 0.9rem;
        }

        /* Button */
        .btn-primary {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #4facfe, #00f2fe);
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
            animation: rocketMove 1s infinite;
        }

        @keyframes rocketMove {
            0%, 100% { transform: translateX(0); }
            50% { transform: translateX(5px) rotate(5deg); }
        }

        /* Auth Link */
        .auth-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #2c3e50;
            font-size: 0.95rem;
            animation: fadeIn 1s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .auth-link a {
            color: #4facfe;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .auth-link a:hover {
            color: #00f2fe;
            transform: translateX(5px);
        }

        .auth-link a i {
            font-size: 0.9rem;
            transition: transform 0.3s ease;
        }

        .auth-link a:hover i {
            transform: translateX(3px);
        }

        /* Forgot password link (optional) */
        .forgot-password {
            text-align: right;
            margin-top: 0.5rem;
            font-size: 0.85rem;
        }

        .forgot-password a {
            color: #6c757d;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .forgot-password a:hover {
            color: #4facfe;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .auth-container {
                margin: 1rem;
            }
            
            .auth-box {
                padding: 1.8rem;
            }
            
            .auth-box h2 {
                font-size: 1.8rem;
            }
            
            .auth-box h2 i {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 480px) {
            .auth-box {
                padding: 1.5rem;
            }
            
            .auth-box h2 {
                font-size: 1.5rem;
            }
            
            .btn-primary {
                font-size: 1rem;
                padding: 0.9rem;
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
    
    <!-- Full Screen Slider Background - Same as register.php -->
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
            <h2>
                <i class="fas fa-brain"></i>
                Welcome Back
                <i class="fas fa-robot"></i>
            </h2>
            
            <?php if(isset($error)): ?>
                <p class="error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </p>
            <?php endif; ?>
            
            <?php if(isset($_GET['registered'])): ?>
                <p class="success">
                    <i class="fas fa-check-circle"></i>
                    Registration successful! Please login.
                </p>
            <?php endif; ?>
            
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label>
                        <i class="fas fa-user"></i>
                        Username or Email
                    </label>
                    <input type="text" name="username" placeholder="Enter your username or email" required>
                </div>
                
                <div class="form-group">
                    <label>
                        <i class="fas fa-lock"></i>
                        Password
                    </label>
                    <input type="password" name="password" placeholder="Enter your password" required>
                </div>
                
                <div class="forgot-password">
                    <a href="forgot-password.php"><i class="fas fa-question-circle"></i> Forgot Password?</a>
                </div>
                
                <button type="submit" class="btn-primary">
                    <i class="fas fa-rocket"></i>
                    Login to Your Account
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>
            
            <p class="auth-link">
                Don't have an account? 
                <a href="register.php">
                    Register Now <i class="fas fa-arrow-right"></i>
                </a>
            </p>
        </div>
    </div>

    <script>
    // Background Slider - Same as register.php
    const slides = document.querySelectorAll('.slide-bg');
    let currentSlide = 0;

    function changeBackground() {
        slides[currentSlide].classList.remove('active');
        currentSlide = (currentSlide + 1) % slides.length;
        slides[currentSlide].classList.add('active');
    }

    setInterval(changeBackground, 4000);

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

    // Add floating animation to success/error messages
    const messages = document.querySelectorAll('.success, .error');
    messages.forEach(msg => {
        msg.addEventListener('mouseenter', () => {
            msg.style.transform = 'scale(1.02)';
        });
        msg.addEventListener('mouseleave', () => {
            msg.style.transform = 'scale(1)';
        });
    });

    // Add input focus effects
    const inputs = document.querySelectorAll('input');
    inputs.forEach(input => {
        input.addEventListener('focus', () => {
            input.parentElement.style.transform = 'scale(1.02)';
        });
        input.addEventListener('blur', () => {
            input.parentElement.style.transform = 'scale(1)';
        });
    });
    </script>
</body>
</html>
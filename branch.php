<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$branch_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get branch info
$sql = "SELECT * FROM branches WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$branch_id]);
$branch = $stmt->fetch();

if (!$branch) {
    header('Location: dashboard.php');
    exit();
}

// Get semesters for this branch
$sql = "SELECT * FROM semesters WHERE branch_id = ? ORDER BY semester_number";
$stmt = $pdo->prepare($sql);
$stmt->execute([$branch_id]);
$semesters = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
     <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($branch['name']); ?> - Semesters</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/accessibility.css">  <!-- ADD THIS -->
    <script src="js/accessibility.js" defer></script>  
        <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/accessibility.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/opendyslexic@0.1.0/opendyslexic.css">
     <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/adaptive.css">
    <link rel="stylesheet" href="css/accessibility.css">
    <link rel="stylesheet" href="css/modes.css"> <!-- ADD THIS NEW FILE -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- JavaScript -->
    <script src="js/global-accessibility.js" defer></script>
    <script src="js/theme-loader.js" defer></script> <!-- ADD THIS NEW FILE -->
    
    <!-- Add this line - IMPORTANT -->
    <script src="js/global-accessibility.js" defer></script>
  
    <style>
        .semesters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        .semester-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            border: 2px solid transparent;
        }
        .semester-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(99, 102, 241, 0.2);
            border-color: #6366f1;
        }
        .semester-number {
            font-size: 36px;
            font-weight: bold;
            color: #6366f1;
            margin-bottom: 10px;
        }
        .semester-name {
            font-size: 20px;
            color: #1f2937;
            margin-bottom: 10px;
        }
        .subject-count {
            color: #6b7280;
            font-size: 14px;
        }
        .back-button {
            display: inline-block;
            padding: 10px 20px;
            background: #6366f1;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .back-button:hover {
            background: #4f52e0;
        }
        .branch-header {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            padding: 40px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .branch-icon-large {
            font-size: 64px;
            margin-bottom: 20px;
        }
    </style>
    <!-- Your existing meta tags and title -->
    
    <!-- Your existing CSS files -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/accessibility.css"> <!-- Make sure this is included -->
    
    <!-- Add these lines -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/opendyslexic@0.1.0/opendyslexic.css">
    <script src="js/global-accessibility.js" defer></script>
    
    <!-- Your existing styles -->
</head>
<body>
    <body class="<?php echo $user['high_contrast'] ? 'high-contrast' : ''; ?>"
      data-profile="<?php echo $_SESSION['accessibility']['profile'] ?? 'standard'; ?>"
      data-text-size="<?php echo $_SESSION['accessibility']['text_size'] ?? 100; ?>"
      data-high-contrast="<?php echo $_SESSION['accessibility']['high_contrast'] ?? 0; ?>"
      data-focus-mode="<?php echo $_SESSION['accessibility']['focus_mode'] ?? 0; ?>">
    <div class="dashboard">
        <nav class="sidebar">
            <div class="sidebar-header">
                <h2>NeuroLearn</h2>
            </div>
            <ul class="nav-menu">
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li class="active"><a href="branches.php">📚 Engineering Branches</a></li>
                <li><a href="my_notes.php">📝 My Notes</a></li>
                <li><a href="chat.php">🤖 AI Tutor</a></li>
                <li><a href="settings.php">⚙️ Settings</a></li>
                <li><a href="logout.php">🚪 Logout</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <a href="dashboard.php" class="back-button">← Back to Dashboard</a>
            // In branch.php, when displaying subjects
<div class="subject-card" onclick="window.location.href='subject.php?id=<?php echo $subject['id']; ?>'">
    <!-- subject content -->
</div>
            <div class="branch-header">
                <div class="branch-icon-large"><?php echo $branch['icon']; ?></div>
                <h1><?php echo htmlspecialchars($branch['name']); ?></h1>
                <p><?php echo htmlspecialchars($branch['description']); ?></p>
            </div>

            <h2 style="margin-bottom: 20px;">📚 Select Semester</h2>
            
            <div class="semesters-grid">
                <?php foreach($semesters as $semester): 
                    // Get subject count for this semester
                    $sql = "SELECT COUNT(*) FROM subjects WHERE semester_id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$semester['id']]);
                    $subject_count = $stmt->fetchColumn();
                ?>
                <div class="semester-card" onclick="window.location.href='semester.php?id=<?php echo $semester['id']; ?>'">
                    <div class="semester-number"><?php echo $semester['semester_number']; ?></div>
                    <div class="semester-name"><?php echo htmlspecialchars($semester['semester_name']); ?></div>
                    <div class="subject-count"><?php echo $subject_count; ?> Subjects</div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</body>
</html>
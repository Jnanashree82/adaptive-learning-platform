<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$branch_id = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : 0;

// Get branch info
$branch = $pdo->prepare("SELECT * FROM branches WHERE id = ?");
$branch->execute([$branch_id]);
$branch = $branch->fetch();

if (!$branch) {
    header('Location: branches.php');
    exit();
}

// Get all semesters for this branch with subject counts
$semesters = $pdo->prepare("
    SELECT s.*, 
           COUNT(DISTINCT sub.id) as subject_count,
           COUNT(DISTINCT n.id) as note_count
    FROM semesters s
    LEFT JOIN subjects sub ON s.id = sub.semester_id
    LEFT JOIN notes n ON sub.id = n.subject_id
    WHERE s.branch_id = ?
    GROUP BY s.id
    ORDER BY s.semester_number
");
$semesters->execute([$branch_id]);
$semesters = $semesters->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($branch['name']); ?> - Semesters</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/accessibility.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .dashboard { display: flex; min-height: 100vh; }
        .sidebar {
            width: 280px;
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        .sidebar-header { padding: 25px 20px; border-bottom: 1px solid #e5e7eb; }
        .sidebar-header h2 { color: #667eea; font-size: 24px; }
        .nav-menu { list-style: none; padding: 20px 0; }
        .nav-menu li { margin: 5px 15px; }
        .nav-menu li a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #6b7280;
            text-decoration: none;
            border-radius: 10px;
            gap: 12px;
        }
        .nav-menu li.active a {
            background: linear-gradient(135deg, #667eea20, #764ba220);
            color: #667eea;
            font-weight: 600;
        }
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .branch-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 30px;
        }
        .branch-header h1 { font-size: 36px; margin-bottom: 10px; }
        .semesters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        .semester-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        .semester-card:hover {
            transform: translateY(-5px);
            border-color: #667eea;
            box-shadow: 0 20px 40px rgba(102,126,234,0.2);
        }
        .semester-number {
            font-size: 42px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 10px;
        }
        .semester-name { font-size: 20px; font-weight: 600; color: #1e293b; margin-bottom: 15px; }
        .semester-stats {
            display: flex;
            gap: 15px;
            color: #6b7280;
            font-size: 14px;
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
        }
        .stat { display: flex; align-items: center; gap: 5px; }
        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        @media (max-width: 768px) {
            .sidebar { width: 100%; height: auto; position: relative; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <nav class="sidebar">
            <div class="sidebar-header"><h2>🧠 NeuroLearn</h2></div>
            <ul class="nav-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li class="active"><a href="branches.php"><i class="fas fa-code-branch"></i> Branches</a></li>
                <li><a href="my_notes.php"><i class="fas fa-sticky-note"></i> My Notes</a></li>
                <li><a href="chat.php"><i class="fas fa-robot"></i> AI Tutor</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <div class="container">
                <a href="branches.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Branches</a>
                
                <div class="branch-header">
                    <h1><?php echo $branch['icon']; ?> <?php echo htmlspecialchars($branch['name']); ?></h1>
                    <p><?php echo htmlspecialchars($branch['description']); ?></p>
                </div>

                <h2 style="color: white; margin-bottom: 20px;">📚 Select Semester</h2>
                
                <div class="semesters-grid">
                    <?php foreach ($semesters as $sem): ?>
                    <div class="semester-card" onclick="window.location.href='subjects.php?semester_id=<?php echo $sem['id']; ?>'">
                        <div class="semester-number"><?php echo $sem['semester_number']; ?></div>
                        <div class="semester-name"><?php echo htmlspecialchars($sem['semester_name']); ?></div>
                        <div class="semester-stats">
                            <div class="stat"><i class="fas fa-book"></i> <?php echo $sem['subject_count']; ?> Subjects</div>
                            <div class="stat"><i class="fas fa-file-alt"></i> <?php echo $sem['note_count']; ?> Notes</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
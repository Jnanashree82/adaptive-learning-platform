<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$semester_id = isset($_GET['semester_id']) ? (int)$_GET['semester_id'] : 0;

// Get semester info
$semester = $pdo->prepare("
    SELECT s.*, b.name as branch_name, b.icon as branch_icon 
    FROM semesters s
    JOIN branches b ON s.branch_id = b.id
    WHERE s.id = ?
");
$semester->execute([$semester_id]);
$semester = $semester->fetch();

if (!$semester) {
    header('Location: branches.php');
    exit();
}

// Get subjects with notes
$subjects = $pdo->prepare("
    SELECT sub.*, COUNT(n.id) as note_count
    FROM subjects sub
    LEFT JOIN notes n ON sub.id = n.subject_id
    WHERE sub.semester_id = ?
    GROUP BY sub.id
    ORDER BY sub.subject_code
");
$subjects->execute([$semester_id]);
$subjects = $subjects->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semester <?php echo $semester['semester_number']; ?> - Subjects</title>
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
        .header-section {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
        }
        .breadcrumb a { color: white; opacity: 0.9; text-decoration: none; }
        .subjects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        .subject-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.3s;
        }
        .subject-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(102,126,234,0.2);
        }
        .subject-code {
            color: #667eea;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .subject-name {
            font-size: 20px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 10px;
        }
        .subject-description {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 15px;
        }
        .subject-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
        }
        .note-count {
            background: #e0e7ff;
            color: #667eea;
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
        }
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
                <li><a href="branches.php"><i class="fas fa-code-branch"></i> Branches</a></li>
                <li><a href="my_notes.php"><i class="fas fa-sticky-note"></i> My Notes</a></li>
                <li><a href="chat.php"><i class="fas fa-robot"></i> AI Tutor</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <div class="container">
                <a href="semester.php?branch_id=<?php echo $semester['branch_id']; ?>" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Semesters</a>
                
                <div class="header-section">
                    <div class="breadcrumb">
                        <a href="dashboard.php">Dashboard</a> › 
                        <a href="branches.php">Branches</a> › 
                        <a href="semester.php?branch_id=<?php echo $semester['branch_id']; ?>"><?php echo $semester['branch_name']; ?></a> › 
                        <span>Semester <?php echo $semester['semester_number']; ?></span>
                    </div>
                    <h1><?php echo $semester['branch_icon']; ?> Semester <?php echo $semester['semester_number']; ?> Subjects</h1>
                    <p>Select a subject to view available notes</p>
                </div>

                <div class="subjects-grid">
                    <?php foreach ($subjects as $subject): ?>
                    <div class="subject-card" onclick="window.location.href='notes.php?subject_id=<?php echo $subject['id']; ?>'">
                        <div class="subject-code"><?php echo htmlspecialchars($subject['subject_code']); ?></div>
                        <div class="subject-name"><?php echo htmlspecialchars($subject['subject_name']); ?></div>
                        <div class="subject-description"><?php echo htmlspecialchars($subject['description'] ?? 'No description available'); ?></div>
                        <div class="subject-footer">
                            <span><i class="fas fa-star" style="color: #fbbf24;"></i> <?php echo $subject['credits'] ?? 4; ?> Credits</span>
                            <span class="note-count"><i class="fas fa-file-alt"></i> <?php echo $subject['note_count']; ?> Notes</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
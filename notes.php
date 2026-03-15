<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;

// Get subject info
$subject = $pdo->prepare("
    SELECT sub.*, s.semester_number, b.name as branch_name, b.icon as branch_icon
    FROM subjects sub
    JOIN semesters s ON sub.semester_id = s.id
    JOIN branches b ON s.branch_id = b.id
    WHERE sub.id = ?
");
$subject->execute([$subject_id]);
$subject = $subject->fetch();

if (!$subject) {
    header('Location: dashboard.php');
    exit();
}

// Get notes
$notes = $pdo->prepare("SELECT * FROM notes WHERE subject_id = ? ORDER BY created_at DESC");
$notes->execute([$subject_id]);
$notes = $notes->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $subject['subject_name']; ?> - Notes</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea, #764ba2);
            min-height: 100vh;
            padding: 30px;
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
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .breadcrumb a { color: white; opacity: 0.9; text-decoration: none; }
        
        .notes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .note-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        .note-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }
        
        .note-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .note-meta {
            display: flex;
            gap: 15px;
            color: #666;
            font-size: 12px;
            margin: 15px 0;
        }
        
        .file-info {
            background: #f0f0f0;
            padding: 8px;
            border-radius: 5px;
            font-size: 13px;
            margin: 10px 0;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .btn-view {
            background: #667eea;
            color: white;
        }
        
        .btn-download {
            background: #10b981;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0,0,0,0.2);
        }
        
        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 20px;
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
                <li class="active"><a href="my_notes.php"><i class="fas fa-sticky-note"></i> My Notes</a></li>
                <li><a href="chat.php"><i class="fas fa-robot"></i> AI Tutor</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <div class="container">
                <a href="subjects.php?semester_id=<?php echo $subject['semester_id']; ?>" class="back-btn">← Back</a>
                
                <div class="header-section">
                    <div class="breadcrumb">
                        <a href="dashboard.php">Dashboard</a> › 
                        <a href="branches.php">Branches</a> › 
                        <a href="semester.php?branch_id=<?php echo $subject['branch_id']; ?>"><?php echo $subject['branch_name']; ?></a> › 
                        <a href="subjects.php?semester_id=<?php echo $subject['semester_id']; ?>">Semester <?php echo $subject['semester_number']; ?></a> › 
                        <span><?php echo $subject['subject_name']; ?></span>
                    </div>
                    <h1><?php echo $subject['branch_icon']; ?> <?php echo $subject['subject_name']; ?></h1>
                    <p>Subject Code: <?php echo $subject['subject_code']; ?></p>
                </div>

                <?php if (empty($notes)): ?>
                    <div style="text-align: center; padding: 50px; background: white; border-radius: 10px;">
                        <h3>No notes available for this subject</h3>
                    </div>
                <?php else: ?>
                    <div class="notes-grid">
                        <?php foreach ($notes as $note): ?>
                            <?php 
                            $file_ext = pathinfo($note['file_path'], PATHINFO_EXTENSION);
                            $icon = '📄';
                            if ($file_ext == 'pdf') $icon = '📕';
                            elseif ($file_ext == 'jpg' || $file_ext == 'png') $icon = '🖼️';
                            elseif ($file_ext == 'doc' || $file_ext == 'docx') $icon = '📘';
                            ?>
                            <div class="note-card">
                                <div class="note-title"><?php echo $icon; ?> <?php echo $note['title']; ?></div>
                                <div class="file-info">
                                    📁 <?php echo strtoupper($file_ext); ?> File
                                </div>
                                <div class="note-meta">
                                    <span>👁️ <?php echo $note['view_count']; ?> views</span>
                                    <span>📥 <?php echo $note['download_count']; ?> downloads</span>
                                </div>
                                <div class="btn-group">
                                    <a href="view_note.php?id=<?php echo $note['id']; ?>" class="btn btn-view">👁️ View</a>
                                   <a href="download_note.php?id=<?php echo $note['id']; ?>" class="btn btn-download">📥 Download</a>
                                   <a href="adhd_viewer.php?id=<?php echo $note['id']; ?>" class="btn btn-special" style="background: #8b5cf6;">
    <i class="fas fa-brain"></i> ADHD Focus Mode
</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
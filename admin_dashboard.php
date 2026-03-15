<?php
session_start();
require_once 'config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit();
}

// Get counts for dashboard
$total_branches = $pdo->query("SELECT COUNT(*) FROM branches")->fetchColumn();
$total_semesters = $pdo->query("SELECT COUNT(*) FROM semesters")->fetchColumn();
$total_subjects = $pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
$total_notes = $pdo->query("SELECT COUNT(*) FROM notes")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - NeuroLearn</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f1f5f9;
        }
        
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
        }
        
        .admin-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-header h1 {
            font-size: 32px;
        }
        
        .admin-header h1 i {
            margin-right: 10px;
        }
        
        .logout-btn {
            background: white;
            color: #667eea;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background: linear-gradient(135deg, #667eea20, #764ba220);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: #667eea;
        }
        
        .stat-info h3 {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 5px;
        }
        
        .stat-info p {
            color: #64748b;
            font-size: 14px;
        }
        
        .admin-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
        }
        
        .admin-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .card-header h2 {
            font-size: 20px;
            color: #1e293b;
        }
        
        .card-header h2 i {
            color: #667eea;
            margin-right: 10px;
        }
        
        .btn-add {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3);
        }
        
        .btn-edit {
            background: #f59e0b;
            color: white;
            padding: 4px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
            margin-right: 5px;
        }
        
        .btn-delete {
            background: #ef4444;
            color: white;
            padding: 4px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
        }
        
        .items-list {
            list-style: none;
        }
        
        .items-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .items-list li:last-child {
            border-bottom: none;
        }
        
        .items-list li:hover {
            background: #f8fafc;
        }
        
        .item-info {
            flex: 1;
        }
        
        .item-title {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 4px;
        }
        
        .item-subtitle {
            font-size: 12px;
            color: #64748b;
        }
        
        .item-actions {
            display: flex;
            gap: 8px;
        }
        
        .view-all {
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }
        
        .view-all a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }
        
        .view-all a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <div>
                <h1><i class="fas fa-crown"></i> Admin Dashboard</h1>
                <p>Manage your engineering education platform</p>
            </div>
            <div>
                <a href="admin_upload.php" class="logout-btn" style="margin-right: 10px; background: #10b981; color: white;"><i class="fas fa-cloud-upload-alt"></i> Upload Notes</a>
                <a href="admin_logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-code-branch"></i></div>
                <div class="stat-info">
                    <h3><?php echo $total_branches; ?></h3>
                    <p>Engineering Branches</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
                <div class="stat-info">
                    <h3><?php echo $total_semesters; ?></h3>
                    <p>Total Semesters</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-book"></i></div>
                <div class="stat-info">
                    <h3><?php echo $total_subjects; ?></h3>
                    <p>Subjects</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
                <div class="stat-info">
                    <h3><?php echo $total_notes; ?></h3>
                    <p>Notes Uploaded</p>
                </div>
            </div>
        </div>
        
        <div class="admin-grid">
            <!-- Branches Section -->
            <div class="admin-card">
                <div class="card-header">
                    <h2><i class="fas fa-code-branch"></i> Engineering Branches</h2>
                    <a href="admin_branches.php" class="btn-add"><i class="fas fa-plus"></i> Manage</a>
                </div>
                <ul class="items-list">
                    <?php
                    $branches = $pdo->query("SELECT * FROM branches ORDER BY name LIMIT 5")->fetchAll();
                    foreach ($branches as $branch):
                    ?>
                    <li>
                        <div class="item-info">
                            <div class="item-title"><?php echo htmlspecialchars($branch['name']); ?></div>
                            <div class="item-subtitle">Code: <?php echo $branch['code']; ?> | Icon: <?php echo $branch['icon']; ?></div>
                        </div>
                        <div class="item-actions">
                            <a href="admin_edit_branch.php?id=<?php echo $branch['id']; ?>" class="btn-edit"><i class="fas fa-edit"></i></a>
                            <a href="admin_delete.php?type=branch&id=<?php echo $branch['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></a>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <div class="view-all">
                    <a href="admin_branches.php">View All Branches →</a>
                </div>
            </div>
            
            <!-- Semesters Section -->
            <div class="admin-card">
                <div class="card-header">
                    <h2><i class="fas fa-layer-group"></i> Semesters</h2>
                    <a href="admin_semesters.php" class="btn-add"><i class="fas fa-plus"></i> Manage</a>
                </div>
                <ul class="items-list">
                    <?php
                    $semesters = $pdo->query("
                        SELECT s.*, b.name as branch_name 
                        FROM semesters s
                        JOIN branches b ON s.branch_id = b.id
                        ORDER BY s.id DESC LIMIT 5
                    ")->fetchAll();
                    foreach ($semesters as $sem):
                    ?>
                    <li>
                        <div class="item-info">
                            <div class="item-title"><?php echo $sem['semester_name']; ?></div>
                            <div class="item-subtitle"><?php echo $sem['branch_name']; ?> | Semester <?php echo $sem['semester_number']; ?></div>
                        </div>
                        <div class="item-actions">
                            <a href="admin_edit_semester.php?id=<?php echo $sem['id']; ?>" class="btn-edit"><i class="fas fa-edit"></i></a>
                            <a href="admin_delete.php?type=semester&id=<?php echo $sem['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></a>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <div class="view-all">
                    <a href="admin_semesters.php">View All Semesters →</a>
                </div>
            </div>
            
            <!-- Subjects Section -->
            <div class="admin-card">
                <div class="card-header">
                    <h2><i class="fas fa-book"></i> Subjects</h2>
                    <a href="admin_subjects.php" class="btn-add"><i class="fas fa-plus"></i> Add New</a>
                </div>
                <ul class="items-list">
                    <?php
                    $subjects = $pdo->query("
                        SELECT sub.*, s.semester_number, b.name as branch_name 
                        FROM subjects sub
                        JOIN semesters s ON sub.semester_id = s.id
                        JOIN branches b ON s.branch_id = b.id
                        ORDER BY sub.id DESC LIMIT 5
                    ")->fetchAll();
                    foreach ($subjects as $subject):
                    ?>
                    <li>
                        <div class="item-info">
                            <div class="item-title"><?php echo htmlspecialchars($subject['subject_name']); ?></div>
                            <div class="item-subtitle">
                                <?php echo $subject['subject_code']; ?> | 
                                <?php echo $subject['branch_name']; ?> - Sem <?php echo $subject['semester_number']; ?> | 
                                Credits: <?php echo $subject['credits']; ?>
                            </div>
                        </div>
                        <div class="item-actions">
                            <a href="admin_edit_subject.php?id=<?php echo $subject['id']; ?>" class="btn-edit"><i class="fas fa-edit"></i></a>
                            <a href="admin_delete.php?type=subject&id=<?php echo $subject['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></a>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <div class="view-all">
                    <a href="admin_subjects.php">View All Subjects →</a>
                </div>
            </div>
            
            <!-- Recent Notes Section -->
            <div class="admin-card">
                <div class="card-header">
                    <h2><i class="fas fa-file-alt"></i> Recent Notes</h2>
                    <a href="admin_upload.php" class="btn-add"><i class="fas fa-cloud-upload-alt"></i> Upload</a>
                </div>
                <ul class="items-list">
                    <?php
                    $notes = $pdo->query("
                        SELECT n.*, s.subject_name 
                        FROM notes n
                        JOIN subjects s ON n.subject_id = s.id
                        ORDER BY n.created_at DESC LIMIT 5
                    ")->fetchAll();
                    foreach ($notes as $note):
                    ?>
                    <li>
                        <div class="item-info">
                            <div class="item-title"><?php echo htmlspecialchars($note['title']); ?></div>
                            <div class="item-subtitle">
                                <?php echo $note['subject_name']; ?> | 
                                <i class="far fa-eye"></i> <?php echo $note['view_count']; ?> | 
                                <i class="fas fa-download"></i> <?php echo $note['download_count']; ?>
                            </div>
                        </div>
                        <div class="item-actions">
                            <a href="view_note.php?id=<?php echo $note['id']; ?>" class="btn-edit" target="_blank"><i class="fas fa-eye"></i></a>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <div class="view-all">
                    <a href="admin_notes.php">View All Notes →</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
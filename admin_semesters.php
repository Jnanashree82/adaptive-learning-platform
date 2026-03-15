<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit();
}

// Get all branches for dropdown
$branches = $pdo->query("SELECT * FROM branches ORDER BY name")->fetchAll();

// Handle Add Semester
if (isset($_POST['add_semester'])) {
    $branch_id = $_POST['branch_id'];
    $semester_number = $_POST['semester_number'];
    $semester_name = $_POST['semester_name'];
    
    $sql = "INSERT INTO semesters (branch_id, semester_number, semester_name) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$branch_id, $semester_number, $semester_name]);
    
    header('Location: admin_semesters.php?msg=added');
    exit();
}

// Get all semesters
$semesters = $pdo->query("
    SELECT s.*, b.name as branch_name 
    FROM semesters s
    JOIN branches b ON s.branch_id = b.id
    ORDER BY b.name, s.semester_number
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Semesters - Admin</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f1f5f9;
            padding: 30px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 28px;
        }
        
        .back-btn {
            background: white;
            color: #667eea;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
        }
        
        .form-card, .list-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        .form-card h2, .list-card h2 {
            margin-bottom: 20px;
            color: #1e293b;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #1e293b;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .form-control:focus {
            border-color: #667eea;
            outline: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102,126,234,0.3);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #f8fafc;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #e2e8f0;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .btn-edit {
            background: #f59e0b;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
        }
        
        .btn-delete {
            background: #ef4444;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
        }
        
        .alert {
            background: #d1fae5;
            color: #065f46;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-layer-group"></i> Manage Semesters</h1>
            <a href="admin_dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
        
        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'added'): ?>
            <div class="alert">Semester added successfully!</div>
        <?php endif; ?>
        
        <div class="content-grid">
            <!-- Add Semester Form -->
            <div class="form-card">
                <h2><i class="fas fa-plus-circle"></i> Add New Semester</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Select Branch</label>
                        <select name="branch_id" class="form-control" required>
                            <option value="">-- Select Branch --</option>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?php echo $branch['id']; ?>"><?php echo htmlspecialchars($branch['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Semester Number</label>
                        <input type="number" name="semester_number" class="form-control" min="1" max="8" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Semester Name</label>
                        <input type="text" name="semester_name" class="form-control" placeholder="e.g., Semester 1" required>
                    </div>
                    
                    <button type="submit" name="add_semester" class="btn-primary">
                        <i class="fas fa-save"></i> Add Semester
                    </button>
                </form>
            </div>
            
            <!-- Semesters List -->
            <div class="list-card">
                <h2><i class="fas fa-list"></i> Existing Semesters</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Branch</th>
                            <th>Semester</th>
                            <th>Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($semesters as $sem): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($sem['branch_name']); ?></td>
                            <td><?php echo $sem['semester_number']; ?></td>
                            <td><?php echo htmlspecialchars($sem['semester_name']); ?></td>
                            <td>
                                <a href="admin_edit_semester.php?id=<?php echo $sem['id']; ?>" class="btn-edit"><i class="fas fa-edit"></i> Edit</a>
                                <a href="admin_delete.php?type=semester&id=<?php echo $sem['id']; ?>" class="btn-delete" onclick="return confirm('Delete this semester?')"><i class="fas fa-trash"></i> Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
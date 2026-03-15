<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit();
}

// Handle Add Branch
if (isset($_POST['add_branch'])) {
    $name = $_POST['name'];
    $code = $_POST['code'];
    $description = $_POST['description'];
    $icon = $_POST['icon'] ?? '📚';
    
    $sql = "INSERT INTO branches (name, code, description, icon) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $code, $description, $icon]);
    
    header('Location: admin_branches.php?msg=added');
    exit();
}

// Get all branches
$branches = $pdo->query("SELECT * FROM branches ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Branches - Admin</title>
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
        
        tr:hover {
            background: #f8fafc;
        }
        
        .btn-edit {
            background: #f59e0b;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
            margin-right: 5px;
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
            <h1><i class="fas fa-code-branch"></i> Manage Engineering Branches</h1>
            <a href="admin_dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
        
        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'added'): ?>
            <div class="alert">Branch added successfully!</div>
        <?php endif; ?>
        
        <div class="content-grid">
            <!-- Add Branch Form -->
            <div class="form-card">
                <h2><i class="fas fa-plus-circle"></i> Add New Branch</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Branch Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g., Computer Science Engineering" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Branch Code</label>
                        <input type="text" name="code" class="form-control" placeholder="e.g., CSE" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Icon (Emoji)</label>
                        <input type="text" name="icon" class="form-control" placeholder="e.g., 💻" value="📚">
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Brief description of the branch"></textarea>
                    </div>
                    
                    <button type="submit" name="add_branch" class="btn-primary">
                        <i class="fas fa-save"></i> Add Branch
                    </button>
                </form>
            </div>
            
            <!-- Branches List -->
            <div class="list-card">
                <h2><i class="fas fa-list"></i> Existing Branches</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Icon</th>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($branches as $branch): ?>
                        <tr>
                            <td><?php echo $branch['icon']; ?></td>
                            <td><?php echo htmlspecialchars($branch['name']); ?></td>
                            <td><?php echo $branch['code']; ?></td>
                            <td>
                                <a href="admin_edit_branch.php?id=<?php echo $branch['id']; ?>" class="btn-edit"><i class="fas fa-edit"></i> Edit</a>
                                <a href="admin_delete.php?type=branch&id=<?php echo $branch['id']; ?>" class="btn-delete" onclick="return confirm('Delete this branch?')"><i class="fas fa-trash"></i> Delete</a>
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
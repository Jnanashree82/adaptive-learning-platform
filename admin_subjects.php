<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit();
}

// Get all branches for dropdown
$branches = $pdo->query("SELECT * FROM branches ORDER BY name")->fetchAll();

// Get semesters based on branch (for AJAX)
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_semesters') {
    header('Content-Type: application/json');
    $branch_id = $_GET['branch_id'] ?? 0;
    
    $stmt = $pdo->prepare("SELECT * FROM semesters WHERE branch_id = ? ORDER BY semester_number");
    $stmt->execute([$branch_id]);
    $semesters = $stmt->fetchAll();
    
    echo json_encode($semesters);
    exit();
}

// Handle Add Subject
if (isset($_POST['add_subject'])) {
    $semester_id = $_POST['semester_id'];
    $subject_code = $_POST['subject_code'];
    $subject_name = $_POST['subject_name'];
    $description = $_POST['description'];
    $credits = $_POST['credits'];
    
    $sql = "INSERT INTO subjects (semester_id, subject_code, subject_name, description, credits) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$semester_id, $subject_code, $subject_name, $description, $credits]);
    
    header('Location: admin_subjects.php?msg=added');
    exit();
}

// Get all subjects
$subjects = $pdo->query("
    SELECT sub.*, s.semester_number, b.name as branch_name 
    FROM subjects sub
    JOIN semesters s ON sub.semester_id = s.id
    JOIN branches b ON s.branch_id = b.id
    ORDER BY b.name, s.semester_number, sub.subject_name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subjects - Admin</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f1f5f9;
            padding: 30px;
        }
        
        .container {
            max-width: 1400px;
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
        
        .loading {
            text-align: center;
            padding: 10px;
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-book"></i> Manage Subjects</h1>
            <a href="admin_dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
        
        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'added'): ?>
            <div class="alert">Subject added successfully!</div>
        <?php endif; ?>
        
        <div class="content-grid">
            <!-- Add Subject Form -->
            <div class="form-card">
                <h2><i class="fas fa-plus-circle"></i> Add New Subject</h2>
                <form method="POST" id="subjectForm">
                    <div class="form-group">
                        <label>Select Branch</label>
                        <select class="form-control" id="branchSelect" required>
                            <option value="">-- Select Branch --</option>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?php echo $branch['id']; ?>"><?php echo htmlspecialchars($branch['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Select Semester</label>
                        <select name="semester_id" id="semesterSelect" class="form-control" required>
                            <option value="">-- Select Branch First --</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Subject Code</label>
                        <input type="text" name="subject_code" class="form-control" placeholder="e.g., 18CS32" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Subject Name</label>
                        <input type="text" name="subject_name" class="form-control" placeholder="e.g., Data Structures" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Brief description"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Credits</label>
                        <input type="number" name="credits" class="form-control" value="4" min="1" max="6">
                    </div>
                    
                    <button type="submit" name="add_subject" class="btn-primary">
                        <i class="fas fa-save"></i> Add Subject
                    </button>
                </form>
            </div>
            
            <!-- Subjects List -->
            <div class="list-card">
                <h2><i class="fas fa-list"></i> Existing Subjects</h2>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Branch</th>
                                <th>Sem</th>
                                <th>Credits</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subjects as $subject): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                                <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                <td><?php echo htmlspecialchars($subject['branch_name']); ?></td>
                                <td><?php echo $subject['semester_number']; ?></td>
                                <td><?php echo $subject['credits']; ?></td>
                                <td>
                                    <a href="admin_edit_subject.php?id=<?php echo $subject['id']; ?>" class="btn-edit"><i class="fas fa-edit"></i></a>
                                    <a href="admin_delete.php?type=subject&id=<?php echo $subject['id']; ?>" class="btn-delete" onclick="return confirm('Delete this subject?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.getElementById('branchSelect').addEventListener('change', function() {
        const branchId = this.value;
        const semesterSelect = document.getElementById('semesterSelect');
        
        if (branchId) {
            semesterSelect.innerHTML = '<option value="">Loading...</option>';
            
            fetch(`admin_subjects.php?ajax=get_semesters&branch_id=${branchId}`)
                .then(response => response.json())
                .then(data => {
                    semesterSelect.innerHTML = '<option value="">-- Select Semester --</option>';
                    data.forEach(sem => {
                        semesterSelect.innerHTML += `<option value="${sem.id}">Semester ${sem.semester_number}: ${sem.semester_name}</option>`;
                    });
                });
        } else {
            semesterSelect.innerHTML = '<option value="">-- Select Branch First --</option>';
        }
    });
    </script>
</body>
</html>
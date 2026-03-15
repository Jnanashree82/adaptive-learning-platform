<?php
require_once 'config/database.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit();
}

// Get all branches
$branches = $pdo->query("SELECT * FROM branches ORDER BY name")->fetchAll();

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $branch_id = $_POST['branch_id'] ?? 0;
    $semester = $_POST['semester'] ?? '';
    $subject_name = $_POST['subject_name'] ?? '';
    $subject_code = $_POST['subject_code'] ?? '';
    $title = $_POST['title'] ?? '';
    
    if (!$branch_id || !$semester || !$subject_name || !$subject_code || !$title) {
        $message = "❌ Please fill all fields";
        $message_type = 'error';
    } else {
        try {
            // 1. Create or get semester
            $stmt = $pdo->prepare("SELECT id FROM semesters WHERE branch_id = ? AND semester_number = ?");
            $stmt->execute([$branch_id, $semester]);
            $sem = $stmt->fetch();
            
            if (!$sem) {
                $stmt = $pdo->prepare("INSERT INTO semesters (branch_id, semester_number, semester_name) VALUES (?, ?, ?)");
                $stmt->execute([$branch_id, $semester, "Semester " . $semester]);
                $semester_id = $pdo->lastInsertId();
            } else {
                $semester_id = $sem['id'];
            }
            
            // 2. Create or get subject
            $stmt = $pdo->prepare("SELECT id FROM subjects WHERE semester_id = ? AND subject_name = ?");
            $stmt->execute([$semester_id, $subject_name]);
            $sub = $stmt->fetch();
            
            if (!$sub) {
                $stmt = $pdo->prepare("INSERT INTO subjects (semester_id, subject_code, subject_name) VALUES (?, ?, ?)");
                $stmt->execute([$semester_id, $subject_code, $subject_name]);
                $subject_id = $pdo->lastInsertId();
            } else {
                $subject_id = $sub['id'];
            }
            
            // 3. Handle file upload
            if (isset($_FILES['note_file']) && $_FILES['note_file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['note_file'];
                $file_name = $file['name'];
                $file_tmp = $file['tmp_name'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                // Allowed file types
                $allowed = ['pdf', 'txt', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
                
                if (!in_array($file_ext, $allowed)) {
                    $message = "❌ File type not allowed. Allowed: " . implode(', ', $allowed);
                    $message_type = 'error';
                } else {
                    // Create upload directory
                    $upload_dir = 'uploads/notes/';
                    $full_upload_dir = __DIR__ . DIRECTORY_SEPARATOR . $upload_dir;
                    
                    if (!file_exists($full_upload_dir)) {
                        mkdir($full_upload_dir, 0777, true);
                    }
                    
                    // Create unique filename
                    $new_filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file_name);
                    $file_path = $upload_dir . $new_filename;
                    $full_file_path = $full_upload_dir . $new_filename;
                    
                    // Move file
                    if (move_uploaded_file($file_tmp, $full_file_path)) {
                        // Check what columns exist
                        $columns = $pdo->query("SHOW COLUMNS FROM notes")->fetchAll(PDO::FETCH_COLUMN);
                        
                        if (in_array('file_name', $columns)) {
                            // New structure with file_name
                            $sql = "INSERT INTO notes (subject_id, title, file_name, file_path, file_type, view_count, download_count) 
                                    VALUES (?, ?, ?, ?, ?, 0, 0)";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([$subject_id, $title, $file_name, $file_path, $file_ext]);
                        } else {
                            // Old structure
                            $sql = "INSERT INTO notes (subject_id, title, file_path, file_type, view_count, download_count) 
                                    VALUES (?, ?, ?, ?, 0, 0)";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([$subject_id, $title, $file_path, $file_ext]);
                        }
                        
                        $message = "✅ File uploaded successfully!";
                        $message_type = 'success';
                    } else {
                        $message = "❌ Error moving file. Check folder permissions.";
                        $message_type = 'error';
                    }
                }
            } else {
                $message = "❌ Please select a file.";
                $message_type = 'error';
            }
        } catch (PDOException $e) {
            $message = "❌ Database error: " . $e->getMessage();
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload Notes - Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea, #764ba2);
            min-height: 100vh;
            padding: 30px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        h1 { color: #667eea; margin-bottom: 30px; text-align: center; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        select, input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        button {
            width: 100%;
            padding: 15px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover { background: #5a67d8; }
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .logout { text-align: right; margin-bottom: 20px; }
        .logout a { color: #667eea; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logout">
            <a href="admin_logout.php">Logout</a>
        </div>
        
        <h1>📤 Upload Notes</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Branch</label>
                <select name="branch_id" required>
                    <option value="">Select Branch</option>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?php echo $b['id']; ?>"><?php echo $b['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Semester</label>
                <select name="semester" required>
                    <option value="">Select Semester</option>
                    <?php for($i=1; $i<=8; $i++): ?>
                        <option value="<?php echo $i; ?>">Semester <?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Subject Code</label>
                <input type="text" name="subject_code" placeholder="e.g., 18CS32" required>
            </div>
            
            <div class="form-group">
                <label>Subject Name</label>
                <input type="text" name="subject_name" placeholder="e.g., Data Structures" required>
            </div>
            
            <div class="form-group">
                <label>Note Title</label>
                <input type="text" name="title" placeholder="e.g., Chapter 1" required>
            </div>
            
            <div class="form-group">
                <label>Choose File</label>
                <input type="file" name="note_file" accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png" required>
            </div>
            
            <button type="submit">Upload Notes</button>
        </form>
    </div>
</body>
</html>
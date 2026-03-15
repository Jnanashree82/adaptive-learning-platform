<?php
session_start();
require_once 'config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit();
}

// Get all branches for dropdown
$branches = $pdo->query("SELECT * FROM branches ORDER BY name")->fetchAll();

// Handle file upload
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_id = $_POST['subject_id'] ?? 0;
    $title = $_POST['title'] ?? '';
    $note_type = $_POST['note_type'] ?? 'theory';
    $difficulty = $_POST['difficulty'] ?? 'intermediate';
    
    if (isset($_FILES['note_file']) && $_FILES['note_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['note_file'];
        $file_name = $file['name'];
        $file_tmp = $file['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $allowed = ['pdf', 'txt', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        
        if (!in_array($file_ext, $allowed)) {
            $error_message = "File type not allowed. Allowed: " . implode(', ', $allowed);
        } else {
            $upload_dir = 'uploads/notes/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $new_file_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file_name);
            $file_path = $upload_dir . $new_file_name;
            
            if (move_uploaded_file($file_tmp, $file_path)) {
                $sql = "INSERT INTO notes (subject_id, title, file_path, file_type, note_type, difficulty) 
                        VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([$subject_id, $title, $file_path, $file_ext, $note_type, $difficulty]);
                
                if ($result) {
                    $success_message = "Notes uploaded successfully!";
                } else {
                    $error_message = "Error saving to database.";
                }
            } else {
                $error_message = "Error uploading file.";
            }
        }
    } else {
        $error_message = "Please select a file to upload.";
    }
}

// Handle AJAX request for subjects
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_subjects') {
    header('Content-Type: application/json');
    
    $branch_id = $_GET['branch_id'] ?? 0;
    
    if (!$branch_id) {
        echo json_encode(['error' => 'No branch selected']);
        exit();
    }
    
    try {
        $sql = "SELECT s.id, s.subject_name, s.subject_code, sem.semester_number 
                FROM subjects s
                JOIN semesters sem ON s.semester_id = sem.id
                WHERE sem.branch_id = ?
                ORDER BY sem.semester_number, s.subject_name";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$branch_id]);
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($subjects)) {
            echo json_encode(['error' => 'No subjects found for this branch']);
        } else {
            echo json_encode($subjects);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

// Get recent uploads
$recent = $pdo->query("
    SELECT n.*, s.subject_name, b.name as branch_name 
    FROM notes n
    JOIN subjects s ON n.subject_id = s.id
    JOIN semesters sem ON s.semester_id = sem.id
    JOIN branches b ON sem.branch_id = b.id
    ORDER BY n.created_at DESC
    LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Notes - Admin</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: #f1f5f9;
        }
        
        .admin-container {
            max-width: 1200px;
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
            font-size: 28px;
        }
        
        .admin-header h1 i {
            margin-right: 10px;
        }
        
        .logout-btn {
            background: white;
            color: #667eea;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .upload-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .upload-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .card-title {
            font-size: 20px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .card-title i {
            color: #667eea;
            margin-right: 10px;
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
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .file-upload-area {
            border: 2px dashed #667eea;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            background: #f8fafc;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .file-upload-area:hover {
            background: #ede9fe;
        }
        
        .file-upload-area i {
            font-size: 48px;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .file-upload-area p {
            color: #1e293b;
            font-weight: 500;
        }
        
        .file-upload-area small {
            color: #64748b;
        }
        
        .file-info {
            margin-top: 15px;
            padding: 15px;
            background: #d1fae5;
            border-radius: 8px;
            color: #065f46;
            display: none;
        }
        
        .file-info.show {
            display: block;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            width: 100%;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        
        .upload-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .upload-item:hover {
            background: #f8fafc;
        }
        
        .item-info h4 {
            font-size: 16px;
            color: #1e293b;
            margin-bottom: 5px;
        }
        
        .item-meta {
            display: flex;
            gap: 15px;
            font-size: 12px;
            color: #64748b;
        }
        
        .item-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .badge-theory { background: #dbeafe; color: #1e40af; }
        .badge-pdf { background: #fee2e2; color: #991b1b; }
        
        .view-link {
            color: #667eea;
            text-decoration: none;
            font-size: 13px;
        }
        
        .view-link:hover {
            text-decoration: underline;
        }
        
        .debug-info {
            background: #1e293b;
            color: #0ff;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            margin-top: 20px;
            white-space: pre-wrap;
        }
        
        .loading {
            text-align: center;
            padding: 10px;
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <div>
                <h1><i class="fas fa-cloud-upload-alt"></i> Upload Notes</h1>
                <p>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?>! Add notes to any subject</p>
            </div>
            <a href="admin_logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <div class="upload-grid">
            <!-- Upload Form -->
            <div class="upload-card">
                <div class="card-title">
                    <i class="fas fa-plus-circle"></i> Add New Notes
                </div>
                
                <form method="POST" enctype="multipart/form-data" id="uploadForm">
                    <div class="form-group">
                        <label><i class="fas fa-code-branch"></i> Select Branch</label>
                        <select class="form-control" id="branchSelect" required>
                            <option value="">-- Choose Branch --</option>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?php echo $branch['id']; ?>"><?php echo htmlspecialchars($branch['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-book"></i> Select Subject</label>
                        <select name="subject_id" id="subjectSelect" class="form-control" required>
                            <option value="">-- Select Branch First --</option>
                        </select>
                        <div id="subjectLoading" class="loading" style="display: none;">
                            <i class="fas fa-spinner fa-spin"></i> Loading subjects...
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-heading"></i> Note Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g., Chapter 1: Introduction" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Note Type</label>
                        <select name="note_type" class="form-control">
                            <option value="theory">📘 Theory Notes</option>
                            <option value="numerical">🧮 Numerical Problems</option>
                            <option value="formula">📐 Formula Sheet</option>
                            <option value="previous_paper">📝 Previous Year Paper</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-chart-line"></i> Difficulty</label>
                        <select name="difficulty" class="form-control">
                            <option value="beginner">🌟 Beginner</option>
                            <option value="intermediate" selected>📊 Intermediate</option>
                            <option value="advanced">🚀 Advanced</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-file"></i> Upload File</label>
                        <div class="file-upload-area" onclick="document.getElementById('fileInput').click()">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Click to browse or drag and drop</p>
                            <small>Supported: PDF, DOC, TXT, JPG, PNG (Max 20MB)</small>
                            <input type="file" name="note_file" id="fileInput" style="display: none;" accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png" onchange="handleFileSelect(this)">
                        </div>
                        <div class="file-info" id="fileInfo"></div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Upload Notes
                    </button>
                </form>
            </div>
            
            <!-- Recent Uploads -->
            <div class="upload-card">
                <div class="card-title">
                    <i class="fas fa-history"></i> Recent Uploads
                </div>
                
                <?php if (empty($recent)): ?>
                    <p style="text-align: center; color: #94a3b8; padding: 40px;">
                        <i class="fas fa-folder-open" style="font-size: 48px; margin-bottom: 10px;"></i><br>
                        No notes uploaded yet
                    </p>
                <?php else: ?>
                    <?php foreach ($recent as $note): ?>
                    <div class="upload-item">
                        <div class="item-info">
                            <h4>
                                <i class="fas fa-file-pdf" style="color: #ef4444;"></i>
                                <?php echo htmlspecialchars($note['title']); ?>
                            </h4>
                            <div class="item-meta">
                                <span><i class="fas fa-book"></i> <?php echo htmlspecialchars($note['subject_name']); ?></span>
                                <span><i class="fas fa-code-branch"></i> <?php echo htmlspecialchars($note['branch_name']); ?></span>
                                <span class="badge badge-<?php echo $note['note_type']; ?>"><?php echo ucfirst($note['note_type']); ?></span>
                            </div>
                        </div>
                        <a href="subject.php?id=<?php echo $note['subject_id']; ?>" class="view-link" target="_blank">
                            <i class="fas fa-external-link-alt"></i> View
                        </a>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Debug Information -->
        <div class="debug-info" id="debugInfo" style="display: none;"></div>
    </div>

    <script>
    function handleFileSelect(input) {
        const fileInfo = document.getElementById('fileInfo');
        if (input.files.length > 0) {
            const file = input.files[0];
            const size = (file.size / 1024 / 1024).toFixed(2);
            fileInfo.innerHTML = `
                <i class="fas fa-check-circle"></i> 
                <strong>${file.name}</strong><br>
                Size: ${size} MB
            `;
            fileInfo.classList.add('show');
        }
    }
    
    document.getElementById('branchSelect').addEventListener('change', function() {
        const branchId = this.value;
        const subjectSelect = document.getElementById('subjectSelect');
        const loading = document.getElementById('subjectLoading');
        const debugInfo = document.getElementById('debugInfo');
        
        if (!branchId) {
            subjectSelect.innerHTML = '<option value="">-- Select Branch First --</option>';
            return;
        }
        
        // Show loading
        subjectSelect.style.display = 'none';
        loading.style.display = 'block';
        debugInfo.style.display = 'none';
        
        // Create URL with proper parameters
        const url = `admin_upload.php?ajax=get_subjects&branch_id=${branchId}`;
        console.log('Fetching:', url);
        
        fetch(url)
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Received data:', data);
                
                // Hide loading
                loading.style.display = 'none';
                subjectSelect.style.display = 'block';
                
                // Clear current options
                subjectSelect.innerHTML = '';
                
                // Check for error
                if (data.error) {
                    subjectSelect.innerHTML = `<option value="">${data.error}</option>`;
                    debugInfo.style.display = 'block';
                    debugInfo.textContent = `Error: ${data.error}`;
                    return;
                }
                
                // Check if data is array
                if (!Array.isArray(data)) {
                    subjectSelect.innerHTML = '<option value="">Invalid data format</option>';
                    debugInfo.style.display = 'block';
                    debugInfo.textContent = `Invalid data: ${JSON.stringify(data)}`;
                    return;
                }
                
                if (data.length === 0) {
                    subjectSelect.innerHTML = '<option value="">No subjects found</option>';
                    return;
                }
                
                // Add default option
                subjectSelect.innerHTML = '<option value="">-- Select Subject --</option>';
                
                // Add subjects
                data.forEach(subject => {
                    const option = document.createElement('option');
                    option.value = subject.id;
                    option.textContent = `Semester ${subject.semester_number}: ${subject.subject_name}`;
                    subjectSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Fetch error:', error);
                loading.style.display = 'none';
                subjectSelect.style.display = 'block';
                subjectSelect.innerHTML = '<option value="">Error loading subjects</option>';
                
                debugInfo.style.display = 'block';
                debugInfo.textContent = `Connection Error: ${error.message}\nCheck console for details.`;
            });
    });
    </script>
</body>
</html>
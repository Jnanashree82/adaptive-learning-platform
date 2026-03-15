<?php
session_start();
// Simple admin check - you can add proper authentication later
if(!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin - Add VTU Notes</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 { color: #667eea; margin-bottom: 30px; }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        select, input, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
        }
        textarea {
            height: 400px;
            font-family: monospace;
        }
        button {
            background: #28a745;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background: #218838;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <body data-profile="<?php echo $_SESSION['accessibility']['profile'] ?? 'standard'; ?>"
      data-text-size="<?php echo $_SESSION['accessibility']['text_size'] ?? 100; ?>"
      data-high-contrast="<?php echo $_SESSION['accessibility']['high_contrast'] ?? 0; ?>"
      data-focus-mode="<?php echo $_SESSION['accessibility']['focus_mode'] ?? 0; ?>">
    <div class="container">
        <h1>📝 Add VTU Notes</h1>
        
        <?php
        if(isset($_POST['save'])) {
            $branch = $_POST['branch'];
            $semester = $_POST['semester'];
            $subject = $_POST['subject'];
            $content = $_POST['content'];
            
            // Create folder path
            $folder = "D:/xampp/htdocs/adaptive-learning-platform/vtu_notes/$branch/Semester$semester/";
            
            // Create folder if not exists
            if(!file_exists($folder)) {
                mkdir($folder, 0777, true);
            }
            
            // Save file
            $filename = $folder . $subject . ".txt";
            file_put_contents($filename, $content);
            
            echo '<div class="success">✅ Notes saved successfully!</div>';
        }
        ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Select Branch:</label>
                <select name="branch" required>
                    <option value="">Choose Branch</option>
                    <option value="CSE">Computer Science Engineering (CSE)</option>
                    <option value="ISE">Information Science Engineering (ISE)</option>
                    <option value="ECE">Electronics & Communication (ECE)</option>
                    <option value="EEE">Electrical & Electronics (EEE)</option>
                    <option value="ME">Mechanical Engineering (ME)</option>
                    <option value="CE">Civil Engineering (CE)</option>
                    <option value="AIML">AI & Machine Learning (AIML)</option>
                    <option value="AIDS">AI & Data Science (AIDS)</option>
                    <option value="CSD">Computer Science & Design (CSD)</option>
                    <option value="CSE-CY">CSE Cyber Security</option>
                    <option value="CSE-DS">CSE Data Science</option>
                    <option value="CSE-IOT">CSE Internet of Things</option>
                    <option value="AE">Aeronautical Engineering</option>
                    <option value="AU">Automobile Engineering</option>
                    <option value="BT">Biotechnology</option>
                    <option value="CHE">Chemical Engineering</option>
                    <option value="MINING">Mining Engineering</option>
                    <option value="TEXTILE">Textile Engineering</option>
                    <option value="MARINE">Marine Engineering</option>
                    <option value="AGRI">Agricultural Engineering</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Semester:</label>
                <select name="semester" required>
                    <option value="">Choose Semester</option>
                    <?php for($i=1; $i<=8; $i++): ?>
                    <option value="<?php echo $i; ?>">Semester <?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Subject Name:</label>
                <input type="text" name="subject" placeholder="e.g., C-Programming" required>
            </div>
            
            <div class="form-group">
                <label>Notes Content:</label>
                <textarea name="content" placeholder="Paste your notes here..." required></textarea>
            </div>
            
            <button type="submit" name="save">💾 Save Notes</button>
        </form>
    </div>
</body>
</html>
<!-- In subject.php, where you display notes -->
<?php foreach($notes as $note): ?>
<div class="note-card">
    <div class="note-header">
        <span class="note-type"><?php echo ucfirst($note['note_type']); ?></span>
        <span class="difficulty"><?php echo ucfirst($note['difficulty']); ?></span>
    </div>
    <h3><?php echo htmlspecialchars($note['title']); ?></h3>
    
    <?php if ($note['file_path']): ?>
    <div class="file-info">
        <i class="fas fa-file-pdf"></i> PDF File
    </div>
    <?php endif; ?>
    
    <div class="note-actions">
        <a href="view_note.php?id=<?php echo $note['id']; ?>" class="btn-view">View</a>
        <a href="download.php?id=<?php echo $note['id']; ?>" class="btn-download">Download</a>
    </div>
</div>
<?php endforeach; ?>
<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$subject_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get subject info with semester and branch
$sql = "SELECT sub.*, s.semester_number, s.semester_name, b.id as branch_id, 
               b.name as branch_name, b.icon as branch_icon
        FROM subjects sub
        JOIN semesters s ON sub.semester_id = s.id
        JOIN branches b ON s.branch_id = b.id
        WHERE sub.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$subject_id]);
$subject = $stmt->fetch();

if (!$subject) {
    header('Location: dashboard.php');
    exit();
}

// Get notes for this subject
$sql = "SELECT * FROM notes WHERE subject_id = ? ORDER BY 
        CASE difficulty 
            WHEN 'beginner' THEN 1 
            WHEN 'intermediate' THEN 2 
            WHEN 'advanced' THEN 3 
        END, created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$subject_id]);
$notes = $stmt->fetchAll();

// Update view count for subject
$sql = "UPDATE subjects SET view_count = view_count + 1 WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$subject_id]);

// Handle note download
if (isset($_GET['download_note'])) {
    $note_id = (int)$_GET['download_note'];
    
    // Get note info
    $sql = "SELECT * FROM notes WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$note_id]);
    $note = $stmt->fetch();
    
    if ($note) {
        // Update download count
        $sql = "UPDATE notes SET download_count = download_count + 1 WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$note_id]);
        
        // If there's a file path, download that file
        if ($note['file_path'] && file_exists($note['file_path'])) {
            $file = $note['file_path'];
            $filename = $note['title'] . '.' . pathinfo($file, PATHINFO_EXTENSION);
            
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($file));
            readfile($file);
            exit();
        } 
        // If no file, create text file from content
        else if ($note['content']) {
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="' . $note['title'] . '.txt"');
            echo $note['content'];
            exit();
        }
    }
}

// Handle AJAX request for note content
if (isset($_GET['ajax']) && $_GET['ajax'] == 'get_note') {
    $note_id = (int)$_GET['note_id'];
    $sql = "SELECT * FROM notes WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$note_id]);
    $note = $stmt->fetch();
    
    if ($note) {
        // Update view count
        $sql = "UPDATE notes SET view_count = view_count + 1 WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$note_id]);
        
        echo json_encode([
            'success' => true,
            'title' => $note['title'],
            'content' => $note['content'],
            'file_path' => $note['file_path'],
            'file_type' => $note['file_type']
        ]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($subject['subject_name']); ?> - Notes</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/adaptive.css">
    <link rel="stylesheet" href="css/accessibility.css">
    <link rel="stylesheet" href="css/modes.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/opendyslexic@0.1.0/opendyslexic.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="js/global-accessibility.js" defer></script>
    <script src="js/theme-loader.js" defer></script>

    <style>
        .notes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .note-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }
        
        .note-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(67, 97, 238, 0.15);
            border-color: #4361ee;
        }
        
        .note-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .note-type {
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .type-theory { background: #dbeafe; color: #1e40af; }
        .type-numerical { background: #dcfce7; color: #166534; }
        .type-formula { background: #fef9c3; color: #854d0e; }
        .type-previous_paper { background: #f3e8ff; color: #6b21a8; }
        
        .difficulty-badge {
            padding: 4px 8px;
            border-radius: 30px;
            font-size: 10px;
            font-weight: 600;
        }
        
        .difficulty-beginner { background: #dbeafe; color: #1e40af; }
        .difficulty-intermediate { background: #f3e8ff; color: #6b21a8; }
        .difficulty-advanced { background: #fee2e2; color: #991b1b; }
        
        .note-title {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 10px;
            line-height: 1.4;
        }
        
        .note-preview {
            color: #64748b;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .note-meta {
            display: flex;
            gap: 15px;
            font-size: 12px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
        }
        
        .note-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-view {
            background: #4361ee;
            color: white;
        }
        
        .btn-view:hover {
            background: #3a56d4;
            transform: translateY(-2px);
        }
        
        .btn-download {
            background: #10b981;
            color: white;
        }
        
        .btn-download:hover {
            background: #0d9e6e;
            transform: translateY(-2px);
        }
        
        .header-section {
            background: linear-gradient(135deg, #4361ee, #3f37c9);
            color: white;
            padding: 40px;
            border-radius: 16px;
            margin-bottom: 30px;
        }
        
        .breadcrumb {
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .breadcrumb a {
            color: white;
            opacity: 0.9;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            opacity: 1;
            text-decoration: underline;
        }
        
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 16px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .filter-btn {
            padding: 8px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 30px;
            background: white;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s;
        }
        
        .filter-btn:hover,
        .filter-btn.active {
            background: #4361ee;
            color: white;
            border-color: #4361ee;
        }
        
        .stats-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            background: #f8fafc;
            border-radius: 30px;
            font-size: 12px;
        }
        
        /* Modal for viewing notes */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 16px;
            padding: 30px;
            max-width: 800px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }
        
        .modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: #f1f5f9;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            transition: all 0.3s;
        }
        
        .modal-close:hover {
            background: #e2e8f0;
            transform: scale(1.1);
        }
        
        .modal-title {
            font-size: 24px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 20px;
            padding-right: 40px;
        }
        
        .modal-body {
            line-height: 1.8;
            color: #334155;
        }
        
        .modal-body pre {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
        }
        
        .no-notes {
            text-align: center;
            padding: 50px;
            color: #94a3b8;
            font-size: 16px;
        }
        
        .file-indicator {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            color: #64748b;
        }
        
        .file-indicator i {
            color: #ef4444;
        }
        
        .ai-assist-btn {
            background: #10b981;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            transition: background 0.3s;
        }
        
        .ai-assist-btn:hover {
            background: #059669;
        }
    </style>
</head>
<body data-profile="<?php echo $_SESSION['accessibility']['profile'] ?? 'standard'; ?>"
      data-text-size="<?php echo $_SESSION['accessibility']['text_size'] ?? 100; ?>"
      data-high-contrast="<?php echo $_SESSION['accessibility']['high_contrast'] ?? 0; ?>"
      data-focus-mode="<?php echo $_SESSION['accessibility']['focus_mode'] ?? 0; ?>"
      data-theme="<?php echo $_SESSION['accessibility']['theme_mode'] ?? 'light'; ?>">
    
    <div class="dashboard">
        <nav class="sidebar">
            <div class="sidebar-header">
                <h2>🧠 NeuroLearn</h2>
            </div>
            <ul class="nav-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="branches.php"><i class="fas fa-book-open"></i> Branches</a></li>
                <li><a href="my_notes.php"><i class="fas fa-sticky-note"></i> My Notes</a></li>
                <li><a href="chat.php"><i class="fas fa-robot"></i> AI Tutor</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <div class="header-section">
                <div class="breadcrumb">
                    <a href="dashboard.php">Dashboard</a> › 
                    <a href="branch.php?id=<?php echo $subject['branch_id']; ?>"><?php echo htmlspecialchars($subject['branch_name']); ?></a> › 
                    <a href="semester.php?id=<?php echo $subject['semester_id']; ?>">Semester <?php echo $subject['semester_number']; ?></a> › 
                    <span><?php echo htmlspecialchars($subject['subject_name']); ?></span>
                </div>
                
                <h1><?php echo $subject['branch_icon']; ?> <?php echo htmlspecialchars($subject['subject_name']); ?></h1>
                <p><?php echo htmlspecialchars($subject['description']); ?></p>
                <p><strong>Subject Code:</strong> <?php echo htmlspecialchars($subject['subject_code']); ?> | <strong>Credits:</strong> <?php echo $subject['credits']; ?></p>
                
                <button class="ai-assist-btn" onclick="window.location.href='chat.php?subject=<?php echo urlencode($subject['subject_name']); ?>'">
                    <i class="fas fa-robot"></i> Ask AI about <?php echo htmlspecialchars($subject['subject_name']); ?>
                </button>
            </div>

            <div class="filter-section">
                <button class="filter-btn active" onclick="filterNotes('all')">All Notes</button>
                <button class="filter-btn" onclick="filterNotes('theory')">Theory</button>
                <button class="filter-btn" onclick="filterNotes('numerical')">Numerical</button>
                <button class="filter-btn" onclick="filterNotes('formula')">Formulas</button>
                <button class="filter-btn" onclick="filterNotes('previous_paper')">Previous Papers</button>
                <select onchange="filterDifficulty(this.value)" style="padding: 8px 16px; border-radius: 30px; border: 2px solid #e2e8f0;">
                    <option value="all">All Difficulties</option>
                    <option value="beginner">Beginner</option>
                    <option value="intermediate">Intermediate</option>
                    <option value="advanced">Advanced</option>
                </select>
            </div>

            <div class="notes-grid" id="notesGrid">
                <?php if (empty($notes)): ?>
                    <div class="no-notes">
                        <i class="fas fa-book-open" style="font-size: 48px; margin-bottom: 20px; color: #cbd5e1;"></i>
                        <h3>No Notes Available</h3>
                        <p>Check back later for study materials</p>
                    </div>
                <?php else: ?>
                    <?php foreach($notes as $note): ?>
                    <div class="note-card" data-type="<?php echo $note['note_type']; ?>" data-difficulty="<?php echo $note['difficulty']; ?>">
                        <div class="note-header">
                            <span class="note-type type-<?php echo $note['note_type']; ?>">
                                <?php 
                                    $type_labels = [
                                        'theory' => '📘 Theory',
                                        'numerical' => '🧮 Numerical',
                                        'formula' => '📐 Formula',
                                        'previous_paper' => '📝 Previous Paper',
                                        'reference' => '📚 Reference'
                                    ];
                                    echo $type_labels[$note['note_type']] ?? ucfirst($note['note_type']);
                                ?>
                            </span>
                            <span class="difficulty-badge difficulty-<?php echo $note['difficulty']; ?>">
                                <?php echo ucfirst($note['difficulty']); ?>
                            </span>
                        </div>
                        
                        <h3 class="note-title"><?php echo htmlspecialchars($note['title']); ?></h3>
                        
                        <div class="note-preview">
                            <?php 
                            if ($note['content']) {
                                echo htmlspecialchars(substr($note['content'], 0, 150)) . '...';
                            } else {
                                echo 'No preview available. Click to view full content.';
                            }
                            ?>
                        </div>
                        
                        <?php if ($note['file_path']): ?>
                        <div class="file-indicator">
                            <i class="fas fa-file-pdf"></i> 
                            <?php echo strtoupper(pathinfo($note['file_path'], PATHINFO_EXTENSION)); ?> File
                        </div>
                        <?php endif; ?>
                        
                        <div class="note-meta">
                            <span><i class="far fa-eye"></i> <?php echo $note['view_count']; ?> views</span>
                            <span><i class="fas fa-download"></i> <?php echo $note['download_count']; ?> downloads</span>
                            <span><i class="far fa-calendar"></i> <?php echo date('M d, Y', strtotime($note['created_at'])); ?></span>
                        </div>
                        
                        <div class="note-actions">
                            <button class="btn btn-view" onclick="viewNote(<?php echo $note['id']; ?>)">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <button class="btn btn-download" onclick="downloadNote(<?php echo $note['id']; ?>)">
                                <i class="fas fa-download"></i> Download
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal for viewing notes -->
    <div class="modal" id="noteModal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
            <h2 class="modal-title" id="modalTitle"></h2>
            <div class="modal-body" id="modalBody"></div>
            <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
                <button class="btn btn-download" id="modalDownloadBtn">
                    <i class="fas fa-download"></i> Download
                </button>
            </div>
        </div>
    </div>

    <script>
    function filterNotes(type) {
        const notes = document.querySelectorAll('.note-card');
        document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');
        
        notes.forEach(note => {
            if (type === 'all' || note.dataset.type === type) {
                note.style.display = 'block';
            } else {
                note.style.display = 'none';
            }
        });
    }

    function filterDifficulty(difficulty) {
        const notes = document.querySelectorAll('.note-card');
        notes.forEach(note => {
            if (difficulty === 'all' || note.dataset.difficulty === difficulty) {
                note.style.display = 'block';
            } else {
                note.style.display = 'none';
            }
        });
    }

    function viewNote(noteId) {
        fetch(`subject.php?ajax=get_note&note_id=${noteId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('modalTitle').textContent = data.title;
                    
                    let content = data.content;
                    if (content) {
                        // Format content
                        content = content.replace(/\n/g, '<br>');
                        content = content.replace(/```(.*?)```/gs, '<pre><code>$1</code></pre>');
                    } else {
                        content = '<p class="no-notes">No content available. Please download the file.</p>';
                    }
                    
                    document.getElementById('modalBody').innerHTML = content;
                    
                    // Set download button
                    document.getElementById('modalDownloadBtn').onclick = function() {
                        downloadNote(noteId);
                    };
                    
                    document.getElementById('noteModal').classList.add('active');
                }
            });
    }

    function downloadNote(noteId) {
        window.location.href = `subject.php?download_note=${noteId}`;
    }

    function closeModal() {
        document.getElementById('noteModal').classList.remove('active');
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('noteModal');
        if (event.target === modal) {
            closeModal();
        }
    }
    </script>
</body>
</html>
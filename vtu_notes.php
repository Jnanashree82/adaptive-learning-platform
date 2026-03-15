<?php
// Function to get PDF files in a directory
function getPDFFiles($branch, $semester) {
    $dir = "vtu_notes/" . $branch . "/Semester" . $semester . "/";
    $pdfs = [];
    
    if (is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file != "." && $file != ".." && strtolower(pathinfo($file, PATHINFO_EXTENSION)) == "pdf") {
                $pdfs[] = [
                    'name' => pathinfo($file, PATHINFO_FILENAME),
                    'file' => $file,
                    'size' => filesize($dir . $file),
                    'modified' => date("d M Y", filemtime($dir . $file))
                ];
            }
        }
    }
    return $pdfs;
}

// Get all available PDF notes
function getAllPDFNotes() {
    $base_dir = "vtu_notes/";
    $branches = [];
    
    if (is_dir($base_dir)) {
        $branch_folders = scandir($base_dir);
        foreach ($branch_folders as $branch) {
            if ($branch != "." && $branch != ".." && is_dir($base_dir . $branch)) {
                $semesters = [];
                $sem_folders = scandir($base_dir . $branch);
                foreach ($sem_folders as $sem) {
                    if ($sem != "." && $sem != ".." && is_dir($base_dir . $branch . "/" . $sem)) {
                        $pdfs = getPDFFiles($branch, str_replace("Semester", "", $sem));
                        if (!empty($pdfs)) {
                            $semesters[str_replace("Semester", "", $sem)] = $pdfs;
                        }
                    }
                }
                $branches[$branch] = $semesters;
            }
        }
    }
    return $branches;
}

// Get all available PDF notes
$available_notes = getAllPDFNotes();
?>
<!DOCTYPE html>
<html>
<head>
    <title>VTU Engineering Notes - PDF Format</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .header h1 { font-size: 36px; margin-bottom: 10px; }
        .header p { font-size: 18px; opacity: 0.9; }
        
        .stats-bar {
            background: #f8f9fa;
            padding: 20px;
            display: flex;
            justify-content: space-around;
            border-bottom: 1px solid #dee2e6;
        }
        .stat-item {
            text-align: center;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            color: #6c757d;
        }
        
        .nav-bar {
            background: #f8f9fa;
            padding: 15px 30px;
            display: flex;
            gap: 20px;
            border-bottom: 1px solid #dee2e6;
        }
        .nav-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
        }
        .nav-btn:hover { background: #5a67d8; }
        
        .path {
            color: #6c757d;
            line-height: 40px;
        }
        
        .content { padding: 30px; }
        
        /* Branches Grid */
        .branches-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        .branch-card {
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .branch-card:hover {
            border-color: #667eea;
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(102,126,234,0.2);
        }
        .branch-icon { font-size: 48px; margin-bottom: 15px; }
        .branch-name { font-size: 20px; font-weight: bold; }
        .branch-code {
            color: #667eea;
            font-size: 14px;
            margin: 5px 0;
        }
        .branch-count {
            color: #28a745;
            font-size: 14px;
            margin-top: 10px;
        }
        
        /* Semester Tabs */
        .semester-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 20px 0 30px;
        }
        .semester-tab {
            padding: 12px 24px;
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }
        .semester-tab:hover { background: #e9ecef; }
        .semester-tab.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        .semester-tab.has-pdfs {
            border-color: #28a745;
            background: #f0fff4;
        }
        
        /* PDF Grid */
        .pdf-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
        }
        .pdf-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s;
        }
        .pdf-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .pdf-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }
        .pdf-name {
            font-size: 16px;
            font-weight: bold;
            margin: 10px 0;
            color: #333;
        }
        .pdf-meta {
            color: #6c757d;
            font-size: 12px;
            margin: 10px 0;
        }
        .pdf-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .pdf-btn {
            flex: 1;
            padding: 8px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .view-btn {
            background: #667eea;
            color: white;
        }
        .view-btn:hover { background: #5a67d8; }
        .download-btn {
            background: #28a745;
            color: white;
        }
        .download-btn:hover { background: #218838; }
        
        /* PDF Viewer */
        .pdf-viewer-container {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 30px;
        }
        .pdf-viewer-header {
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .pdf-viewer-title {
            font-size: 24px;
            color: #333;
        }
        .pdf-viewer-meta {
            color: #6c757d;
            margin-top: 10px;
        }
        .pdf-viewer {
            width: 100%;
            height: 600px;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .back-btn {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin: 20px;
        }
        .back-btn:hover { background: #5a6268; }
        
        .no-pdfs {
            text-align: center;
            padding: 50px;
            color: #6c757d;
        }
        
        .refresh-btn {
            background: #ffc107;
            color: #333;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-left: auto;
        }
        
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📚 VTU Engineering Notes</h1>
            <p>PDF format notes from VTU circulars and official sources</p>
        </div>
        
        <?php
        // Calculate stats
        $total_branches = count($available_notes);
        $total_pdfs = 0;
        
        foreach ($available_notes as $branch => $semesters) {
            foreach ($semesters as $semester => $pdfs) {
                $total_pdfs += count($pdfs);
            }
        }
        ?>
        
        <div class="stats-bar">
            <div class="stat-item">
                <div class="stat-value"><?php echo $total_branches; ?></div>
                <div class="stat-label">Branches</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $total_pdfs; ?></div>
                <div class="stat-label">PDF Notes</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">VTU</div>
                <div class="stat-label">Official Source</div>
            </div>
        </div>
        
        <div class="nav-bar">
            <button class="nav-btn" onclick="showHome()">🏠 Home</button>
            <span class="path" id="path">Branches</span>
            <button class="refresh-btn" onclick="location.reload()">🔄 Refresh</button>
        </div>
        
        <div class="content" id="content">
            <!-- Branches will be loaded here -->
        </div>
    </div>

    <script>
    // Pass PHP data to JavaScript
    const availableNotes = <?php echo json_encode($available_notes); ?>;
    
    const branches = {
        <?php foreach ($available_notes as $branch => $semesters): ?>
        "<?php echo $branch; ?>": {
            "name": "<?php 
                $names = [
                    "CSE" => "Computer Science Engineering",
                    "ECE" => "Electronics & Communication",
                    "ME" => "Mechanical Engineering",
                    "CE" => "Civil Engineering",
                    "ISE" => "Information Science",
                    "AIML" => "AI & Machine Learning",
                    "AIDS" => "AI & Data Science",
                    "CSE-CY" => "CSE Cyber Security",
                    "CSE-DS" => "CSE Data Science",
                    "CSE-IOT" => "CSE Internet of Things"
                ];
                echo isset($names[$branch]) ? $names[$branch] : $branch;
            ?>",
            "icon": "<?php 
                $icons = [
                    "CSE" => "💻", "ECE" => "📡", "ME" => "🔧", "CE" => "🏗️",
                    "ISE" => "🌐", "AIML" => "🤖", "AIDS" => "📊", "CSE-CY" => "🔐",
                    "CSE-DS" => "📈", "CSE-IOT" => "📱"
                ];
                echo isset($icons[$branch]) ? $icons[$branch] : "📚";
            ?>",
            "semesters": <?php echo json_encode(array_keys($semesters)); ?>
        },
        <?php endforeach; ?>
    };

    let currentBranch = '';
    let currentSemester = 1;
    let currentPDF = null;

    window.onload = function() {
        showBranches();
    };

    function showBranches() {
        let html = '<div class="branches-grid">';
        
        for(let code in branches) {
            let pdfCount = 0;
            if(availableNotes[code]) {
                for(let sem in availableNotes[code]) {
                    pdfCount += availableNotes[code][sem].length;
                }
            }
            
            html += `
                <div class="branch-card" onclick="loadSemesters('${code}')">
                    <div class="branch-icon">${branches[code].icon}</div>
                    <div class="branch-name">${branches[code].name}</div>
                    <div class="branch-code">${code}</div>
                    <div class="branch-count">📄 ${pdfCount} PDF notes</div>
                </div>
            `;
        }
        
        html += '</div>';
        
        // Add info box
        html += `
            <div class="info-box">
                <strong>ℹ️ About PDF Notes:</strong><br>
                • Notes are in PDF format - you can view or download them<br>
                • Click on a branch to see available semesters<br>
                • Green highlighted semesters have PDF notes available<br>
                • PDFs are sourced from VTU circulars and GitHub repositories
            </div>
        `;
        
        document.getElementById('content').innerHTML = html;
        document.getElementById('path').innerHTML = 'Branches';
    }

    function loadSemesters(branch) {
        currentBranch = branch;
        
        let html = '<button class="back-btn" onclick="showBranches()">← Back to Branches</button>';
        html += `<h2 style="margin-bottom: 20px;">${branches[branch].icon} ${branches[branch].name}</h2>`;
        html += '<div class="semester-tabs">';
        
        // Check which semesters have PDFs
        let semestersWithPDFs = availableNotes[branch] ? Object.keys(availableNotes[branch]) : [];
        
        for(let i = 1; i <= 8; i++) {
            let hasPDFs = semestersWithPDFs.includes(i.toString());
            let pdfCount = hasPDFs ? availableNotes[branch][i].length : 0;
            
            html += `<div class="semester-tab ${hasPDFs ? 'has-pdfs' : ''} ${i === 1 ? 'active' : ''}" 
                           onclick="${hasPDFs ? 'loadPDFs(' + i + ')' : 'alert(\'No PDF notes available for Semester ' + i + '\')'}"
                           style="${!hasPDFs ? 'opacity:0.6;' : ''}">
                        Semester ${i} ${hasPDFs ? '📄 (' + pdfCount + ')' : '⏳'}
                    </div>`;
        }
        
        html += '</div>';
        html += '<div id="pdfsContainer"></div>';
        
        document.getElementById('content').innerHTML = html;
        document.getElementById('path').innerHTML = `Branches > ${branch}`;
        
        // Load first semester that has PDFs
        if(semestersWithPDFs.length > 0) {
            loadPDFs(parseInt(semestersWithPDFs[0]));
        }
    }

    function loadPDFs(semester) {
        currentSemester = semester;
        
        // Update active tab
        document.querySelectorAll('.semester-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        event.target.classList.add('active');
        
        let pdfs = availableNotes[currentBranch]?.[semester] || [];
        
        if(pdfs.length > 0) {
            let html = '<h3 style="margin: 20px 0;">📄 Available PDF Notes</h3>';
            html += '<div class="pdf-grid">';
            
            pdfs.forEach(pdf => {
                // Format file size
                let size = pdf.size;
                let sizeStr = '';
                if(size < 1024) sizeStr = size + ' B';
                else if(size < 1024*1024) sizeStr = (size/1024).toFixed(1) + ' KB';
                else sizeStr = (size/(1024*1024)).toFixed(1) + ' MB';
                
                html += `
                    <div class="pdf-card">
                        <div class="pdf-icon">📕</div>
                        <div class="pdf-name">${pdf.name.replace(/-/g, ' ')}</div>
                        <div class="pdf-meta">
                            📅 ${pdf.modified}<br>
                            📦 ${sizeStr}
                        </div>
                        <div class="pdf-actions">
                            <a href="view_pdf.php?branch=${currentBranch}&semester=${semester}&file=${encodeURIComponent(pdf.file)}" 
                               class="pdf-btn view-btn" target="_blank">👁️ View</a>
                            <a href="vtu_notes/${currentBranch}/Semester${semester}/${encodeURIComponent(pdf.file)}" 
                               class="pdf-btn download-btn" download>📥 Download</a>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            document.getElementById('pdfsContainer').innerHTML = html;
        } else {
            document.getElementById('pdfsContainer').innerHTML = '<div class="no-pdfs">📭 No PDF notes found for this semester</div>';
        }
    }

    function showHome() {
        showBranches();
    }
    </script>
</body>
</html>
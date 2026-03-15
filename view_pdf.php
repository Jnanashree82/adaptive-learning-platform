<?php
$branch = $_GET['branch'];
$semester = $_GET['semester'];
$file = $_GET['file'];

$pdf_path = "vtu_notes/" . $branch . "/Semester" . $semester . "/" . $file;

if (!file_exists($pdf_path)) {
    die("PDF file not found");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>View PDF - <?php echo pathinfo($file, PATHINFO_FILENAME); ?></title>
    <style>
        body { margin: 0; padding: 0; background: #f5f5f5; }
        .toolbar {
            background: #333;
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .toolbar a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            background: #007bff;
            border-radius: 4px;
        }
        .toolbar a:hover { background: #0056b3; }
        .pdf-container {
            width: 100%;
            height: calc(100vh - 70px);
        }
        .pdf-container iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <span>📄 <?php echo pathinfo($file, PATHINFO_FILENAME); ?></span>
        <a href="vtu_notes.php">← Back to Notes</a>
    </div>
    <div class="pdf-container">
        <iframe src="<?php echo $pdf_path; ?>" type="application/pdf"></iframe>
    </div>
</body>
</html>
<?php
require_once 'config/database.php';

echo "<h2>Subjects in Database</h2>";

// Get all subjects with branch info
$sql = "SELECT s.*, sem.semester_number, b.name as branch_name, b.id as branch_id
        FROM subjects s
        JOIN semesters sem ON s.semester_id = sem.id
        JOIN branches b ON sem.branch_id = b.id
        ORDER BY b.name, sem.semester_number";

$subjects = $pdo->query($sql)->fetchAll();

if (empty($subjects)) {
    echo "<p style='color: red;'>No subjects found in database!</p>";
} else {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Subject Name</th><th>Code</th><th>Semester</th><th>Branch</th><th>Branch ID</th></tr>";
    
    foreach ($subjects as $subject) {
        echo "<tr>";
        echo "<td>{$subject['id']}</td>";
        echo "<td>{$subject['subject_name']}</td>";
        echo "<td>{$subject['subject_code']}</td>";
        echo "<td>{$subject['semester_number']}</td>";
        echo "<td>{$subject['branch_name']}</td>";
        echo "<td>{$subject['branch_id']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test specific branch
echo "<h3>Test Branch ID 1:</h3>";
$test = $pdo->prepare("
    SELECT s.* FROM subjects s
    JOIN semesters sem ON s.semester_id = sem.id
    WHERE sem.branch_id = 1
");
$test->execute([1]);
$results = $test->fetchAll();

if (empty($results)) {
    echo "<p>No subjects for branch ID 1</p>";
} else {
    echo "<p>Found " . count($results) . " subjects for branch ID 1</p>";
}
?>
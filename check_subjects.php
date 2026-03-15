<?php
require_once 'config/database.php';

echo "<h1>🔍 Subject Database Check</h1>";

// Check all branches first
$branches = $pdo->query("SELECT * FROM branches")->fetchAll();
echo "<h2>Branches in Database:</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Name</th><th>Code</th></tr>";
foreach ($branches as $branch) {
    echo "<tr>";
    echo "<td>{$branch['id']}</td>";
    echo "<td>{$branch['name']}</td>";
    echo "<td>{$branch['code']}</td>";
    echo "</tr>";
}
echo "</table>";

// Check all semesters
$semesters = $pdo->query("SELECT * FROM semesters")->fetchAll();
echo "<h2>Semesters in Database:</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Branch ID</th><th>Semester Number</th><th>Semester Name</th></tr>";
foreach ($semesters as $sem) {
    echo "<tr>";
    echo "<td>{$sem['id']}</td>";
    echo "<td>{$sem['branch_id']}</td>";
    echo "<td>{$sem['semester_number']}</td>";
    echo "<td>{$sem['semester_name']}</td>";
    echo "</tr>";
}
echo "</table>";

// Check all subjects
$subjects = $pdo->query("SELECT * FROM subjects")->fetchAll();
echo "<h2>Subjects in Database:</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Semester ID</th><th>Subject Name</th><th>Subject Code</th></tr>";
foreach ($subjects as $subject) {
    echo "<tr>";
    echo "<td>{$subject['id']}</td>";
    echo "<td>{$subject['semester_id']}</td>";
    echo "<td>{$subject['subject_name']}</td>";
    echo "<td>{$subject['subject_code']}</td>";
    echo "</tr>";
}
echo "</table>";

// Test with branch ID 1
echo "<h2>Test Subjects for Branch ID 1:</h2>";
$sql = "SELECT s.*, sem.semester_number 
        FROM subjects s
        JOIN semesters sem ON s.semester_id = sem.id
        WHERE sem.branch_id = 1";
$test = $pdo->query($sql);
$results = $test->fetchAll();

if (empty($results)) {
    echo "<p style='color: red;'>❌ No subjects found for branch ID 1</p>";
} else {
    echo "<p style='color: green;'>✅ Found " . count($results) . " subjects for branch ID 1</p>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Subject Name</th><th>Semester</th></tr>";
    foreach ($results as $r) {
        echo "<tr><td>{$r['id']}</td><td>{$r['subject_name']}</td><td>{$r['semester_number']}</td></tr>";
    }
    echo "</table>";
}
?>
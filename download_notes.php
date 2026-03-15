<?php
$branch = $_GET['branch'];
$semester = $_GET['semester'];
$subject = $_GET['subject'];

$file = "vtu_notes/" . $branch . "/Semester" . $semester . "/" . $subject . ".txt";

if(file_exists($file)) {
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="' . $subject . '_Notes.txt"');
    readfile($file);
} else {
    echo "File not found";
}
?>
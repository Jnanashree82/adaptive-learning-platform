<?php
$branch = $_GET['branch'];
$semester = $_GET['semester'];
$subject = $_GET['subject'];

$file = "vtu_notes/" . $branch . "/Semester" . $semester . "/" . $subject . ".txt";

if(file_exists($file)) {
    $content = file_get_contents($file);
    // Convert to HTML with proper formatting
    $content = nl2br(htmlspecialchars($content));
    $content = preg_replace('/={3,}/', '<hr>', $content);
    $content = preg_replace('/MODULE \d+:([^<]+)/', '<h3 class="module-heading">MODULE $1</h3>', $content);
    $content = preg_replace('/•([^<]+)/', '<li>$1</li>', $content);
    $content = preg_replace('/(\d\..+?)(?=<br>|$)/', '<p class="question">$1</p>', $content);
    
    echo json_encode(['content' => $content]);
} else {
    echo json_encode(['content' => false]);
}
?>
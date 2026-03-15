<?php
$branch = $_GET['branch'];
$semester = $_GET['semester'];

$folder = "vtu_notes/$branch/Semester$semester/";
$subjects = [];

if(file_exists($folder)) {
    $files = scandir($folder);
    foreach($files as $file) {
        if($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) == 'txt') {
            $subjects[] = pathinfo($file, PATHINFO_FILENAME);
        }
    }
}

header('Content-Type: application/json');
echo json_encode($subjects);
?>
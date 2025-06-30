<?php
// download.php
include __DIR__ . '/config.php';
session_start();

$fid = (int)$_GET['file_id'];
$f = mysqli_query($mysqli, "SELECT * FROM files WHERE id=" . $fid)->fetch_assoc();

if (!$f) exit('파일 없음');

$path = __DIR__ . '/uploads/' . $f['saved_name'];

if (!is_file($path)) exit('파일이 존재하지 않습니다');

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="'. basename($f['orig_name']) .'"');
header('Content-Length: ' . filesize($path));

readfile($path);
exit;
?>
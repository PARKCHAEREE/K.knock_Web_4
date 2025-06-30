<?php
$host   = '%';
$db     = 'myapp';
$user   = 'cherry';
$pass   = 'pcr0723';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('DB 연결 실패: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

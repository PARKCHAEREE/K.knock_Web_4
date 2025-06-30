<?php
// config.php
// MySQLi 연결 정보만 담습니다.
$mysqli = mysqli_connect('localhost','cherry','pcr0723','db');
if (!$mysqli) {
    die('DB 연결 실패: '.mysqli_connect_error());
}
mysqli_set_charset($mysqli, 'utf8mb4');

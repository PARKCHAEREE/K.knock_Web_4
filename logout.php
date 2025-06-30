<?php
// 세션 시작: 세션 데이터를 조작하려면 항상 이 함수를 호출해야 합니다.
session_start();

// 모든 세션 변수 제거
$_SESSION = array();

// 세션 쿠키 삭제 (옵션: 세션 ID가 저장된 쿠키를 삭제하여 브라우저에도 세션 정보가 남지 않도록 함)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 세션 파괴: 서버에 저장된 세션 데이터를 완전히 삭제합니다.
session_destroy();

// 로그아웃 후 리다이렉트할 페이지 지정
// 일반적으로 로그인 페이지나 웹사이트의 메인 페이지로 리다이렉트합니다.
header("Location: login.php"); // login.php로 리다이렉트
exit; // 리다이렉트 후 스크립트 실행 중지
?>

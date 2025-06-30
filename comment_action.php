<?php
// 설정 파일과 인증 파일 포함
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

// 사용자 인증 상태 확인
// 세션에 user_id가 없거나 비어있으면 로그인 페이지로 리다이렉트
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 요청 모드 가져오기 (write 또는 delete)
$mode = $_REQUEST['mode'] ?? '';

// 댓글 작성 모드
if ($mode === 'write') {
    // 필수 필드 (post_id, content) 존재 여부 확인
    if (!isset($_POST['post_id']) || !isset($_POST['content'])) {
        die("오류: 필수 입력 필드(게시글 ID 또는 댓글 내용)가 누락되었습니다.");
    }
    
    // 게시글 ID와 댓글 내용 가져오기
    $pid = (int)$_POST['post_id']; // 정수형으로 강제 변환
    $txt = trim($_POST['content']); // 양쪽 공백 제거
    
    // 입력값 유효성 검사 시작
    if ($pid <= 0) {
        // post_id가 유효하지 않은 경우 (숫자가 아니거나 0 이하)
        die("오류: 잘못된 게시글 ID가 전달되었습니다. 게시글 ID는 1 이상의 정수여야 합니다.");
    }
    
    if (empty($txt)) {
        // 댓글 내용이 비어있는 경우
        die("오류: 댓글 내용을 입력해주세요.");
    }
    
    if (strlen($txt) > 1000) {
        // 댓글 내용이 너무 긴 경우 (최대 1000자 제한)
        die("오류: 댓글이 너무 깁니다. 최대 1000자까지 입력 가능합니다.");
    }
    // 입력값 유효성 검사 끝
    
    // 해당 게시글이 'posts' 테이블에 존재하는지 확인
    $check_stmt = mysqli_prepare($mysqli, "SELECT id FROM posts WHERE id = ?");
    if (!$check_stmt) {
        die("데이터베이스 오류: 게시글 존재 여부 확인 중 문제가 발생했습니다. (" . mysqli_error($mysqli) . ")");
    }
    
    mysqli_stmt_bind_param($check_stmt, 'i', $pid);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($result) === 0) {
        // 해당 ID의 게시글이 존재하지 않는 경우
        die("오류: 존재하지 않는 게시글에 댓글을 작성할 수 없습니다.");
    }
    mysqli_stmt_close($check_stmt); // 준비된 문장 닫기
    
    // 준비된 문장을 사용하여 'comments' 테이블에 안전하게 댓글 삽입
    $stmt = mysqli_prepare($mysqli, "INSERT INTO comments (post_id, user_id, content, reg_date) VALUES (?,?,?,NOW())");
    if (!$stmt) {
        die("데이터베이스 오류: 댓글 삽입 준비 중 문제가 발생했습니다. (" . mysqli_error($mysqli) . ")");
    }
    
    mysqli_stmt_bind_param($stmt, 'iis', $pid, $_SESSION['user_id'], $txt);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt); // 준비된 문장 닫기
        // 댓글 저장 성공 시 게시글 페이지로 안전하게 리다이렉트
        header("Location: post_view.php?id=" . urlencode($pid));
        exit;
    } else {
        mysqli_stmt_close($stmt); // 준비된 문장 닫기
        die("데이터베이스 오류: 댓글 저장에 실패했습니다. 다시 시도해주세요. (" . mysqli_error($mysqli) . ")");
    }
    
} elseif ($mode === 'delete') { // 댓글 삭제 모드
    // 필수 매개변수 (댓글 ID, 게시글 ID) 존재 여부 확인
    if (!isset($_GET['id']) || !isset($_GET['post_id'])) {
        die("오류: 댓글 삭제에 필요한 매개변수(댓글 ID 또는 게시글 ID)가 누락되었습니다.");
    }
    
    // 댓글 ID와 게시글 ID 가져오기
    $cid = (int)$_GET['id']; // 댓글 ID
    $pid = (int)$_GET['post_id']; // 게시글 ID
    
    // 입력값 유효성 검사
    if ($cid <= 0 || $pid <= 0) {
        die("오류: 삭제하려는 댓글 또는 게시글의 ID가 잘못되었습니다.");
    }
    
    // 준비된 문장을 사용하여 댓글 삭제 ('id'와 'user_id'가 모두 일치해야 삭제 가능)
    $stmt = mysqli_prepare($mysqli, "DELETE FROM comments WHERE id = ? AND user_id = ?");
    if (!$stmt) {
        die("데이터베이스 오류: 댓글 삭제 준비 중 문제가 발생했습니다. (" . mysqli_error($mysqli) . ")");
    }
    
    mysqli_stmt_bind_param($stmt, 'ii', $cid, $_SESSION['user_id']);
    
    if (mysqli_stmt_execute($stmt)) {
        $affected_rows = mysqli_stmt_affected_rows($stmt); // 삭제된 행의 수 확인
        mysqli_stmt_close($stmt); // 준비된 문장 닫기
        
        // 삭제된 행이 없으면 권한이 없거나 댓글이 존재하지 않음
        if ($affected_rows === 0) {
            die("오류: 해당 댓글을 찾을 수 없거나 삭제 권한이 없습니다.");
        }
        
        // 댓글 삭제 성공 시 게시글 페이지로 안전하게 리다이렉트
        header("Location: post_view.php?id=" . urlencode($pid));
        exit;
    } else {
        mysqli_stmt_close($stmt); // 준비된 문장 닫기
        die("데이터베이스 오류: 댓글 삭제에 실패했습니다. 다시 시도해주세요. (" . mysqli_error($mysqli) . ")");
    }
    
} else {
    // 정의되지 않은 모드로 요청이 들어온 경우
    die("오류: 잘못된 요청 모드입니다. (write 또는 delete만 허용됩니다)");
}
?>

<?php
// 설정 파일과 인증 파일 포함
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

// 사용자 인증 상태 확인: 로그인되지 않았으면 로그인 페이지로 리다이렉트
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 필수 GET 매개변수 (댓글 ID: id, 게시글 ID: post_id) 확인
if (!isset($_GET['id']) || !isset($_GET['post_id'])) {
    die("오류: 댓글 수정에 필요한 필수 매개변수가 누락되었습니다.");
}

// 댓글 ID와 게시글 ID를 정수형으로 변환 및 유효성 검사
$cid = (int)$_GET['id'];
$pid = (int)$_GET['post_id'];

if ($cid <= 0 || $pid <= 0) {
    die("오류: 잘못된 댓글 또는 게시글 ID가 전달되었습니다.");
}

// 준비된 문장을 사용하여 특정 댓글 조회 (보안 강화)
$stmt = mysqli_prepare($mysqli, "SELECT * FROM comments WHERE id = ?");
if (!$stmt) {
    die("데이터베이스 오류: 댓글 조회 준비 중 문제가 발생했습니다. (" . mysqli_error($mysqli) . ")");
}

mysqli_stmt_bind_param($stmt, 'i', $cid); // 댓글 ID 바인딩
mysqli_stmt_execute($stmt); // 쿼리 실행
$result = mysqli_stmt_get_result($stmt); // 결과 가져오기
$c = mysqli_fetch_assoc($result); // 연관 배열로 댓글 데이터 가져오기
mysqli_stmt_close($stmt); // 준비된 문장 닫기

// 댓글 존재 여부 확인
if (!$c) {
    die("오류: 수정하려는 댓글을 찾을 수 없습니다.");
}

// 댓글 수정 권한 확인: 현재 로그인한 사용자와 댓글 작성자가 일치하는지 확인
if ($c['user_id'] != $_SESSION['user_id']) {
    die("오류: 이 댓글을 수정할 권한이 없습니다.");
}

// POST 요청 처리 (댓글 수정 폼이 제출되었을 때)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF 토큰 검증: 보안 강화를 위해 권장되는 단계
    // 세션에 저장된 토큰과 폼에서 전송된 토큰이 일치하는지 확인
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("오류: 잘못된 요청입니다. (CSRF 토큰 불일치)");
    }
    
    // 댓글 내용 가져오기 및 검증
    if (!isset($_POST['content'])) {
        die("오류: 댓글 내용이 필요합니다.");
    }
    
    $txt = trim($_POST['content']); // 양쪽 공백 제거
    
    // 입력값 유효성 검사 (댓글 내용이 비어있거나 너무 길 때)
    if (empty($txt)) {
        die("오류: 댓글 내용을 입력해주세요.");
    }
    
    if (strlen($txt) > 1000) { // 댓글 최대 길이 1000자 제한
        die("오류: 댓글이 너무 깁니다. 최대 1000자까지 입력 가능합니다.");
    }
    
    // 준비된 문장을 사용하여 댓글 업데이트 (내용, 수정일자)
    // 이 쿼리에서 'modified_date' 컬럼이 데이터베이스에 없으면 오류 발생
    $update_stmt = mysqli_prepare($mysqli, "UPDATE comments SET content = ?, modified_date = NOW() WHERE id = ? AND user_id = ?");
    if (!$update_stmt) {
        die("데이터베이스 오류: 댓글 업데이트 준비 중 문제가 발생했습니다. (" . mysqli_error($mysqli) . ")");
    }
    
    mysqli_stmt_bind_param($update_stmt, 'sii', $txt, $cid, $_SESSION['user_id']); // 내용, 댓글 ID, 사용자 ID 바인딩
    
    if (mysqli_stmt_execute($update_stmt)) {
        $affected_rows = mysqli_stmt_affected_rows($update_stmt); // 업데이트된 행의 수 확인
        mysqli_stmt_close($update_stmt); // 준비된 문장 닫기
        
        if ($affected_rows > 0) {
            // 성공적으로 업데이트된 경우 게시글 보기 페이지로 리다이렉트
            header("Location: post_view.php?id=" . urlencode($pid));
            exit;
        } else {
            // 업데이트할 내용이 없거나 이미 같은 내용일 경우
            die("오류: 댓글 수정에 실패했습니다. (변경된 내용이 없거나 오류)");
        }
    } else {
        mysqli_stmt_close($update_stmt); // 준비된 문장 닫기
        die("데이터베이스 오류: 댓글 수정 중 오류가 발생했습니다. (" . mysqli_error($mysqli) . ")");
    }
}

// CSRF 토큰 생성 (페이지 로드 시 또는 필요할 때마다 생성)
// 폼에 숨겨진 필드로 삽입하여 다음 POST 요청 시 검증에 사용
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>댓글 수정</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* 기본적인 스타일 추가 */
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f7f6; color: #333; }
        h2 { color: #34495e; text-align: center; margin-bottom: 25px; }
        .edit-form {
            background-color: #ffffff;
            max-width: 600px;
            margin: 20px auto;
            padding: 30px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }
        .form-group {
            margin-bottom: 20px;
        }
        textarea {
            width: calc(100% - 20px); /* 패딩 고려 */
            min-height: 120px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-family: inherit;
            font-size: 16px;
            resize: vertical; /* 세로 크기 조절 가능 */
            box-sizing: border-box; /* 패딩과 보더를 포함하여 너비 계산 */
        }
        textarea:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        .button-group {
            text-align: right;
            margin-top: 20px;
        }
        .btn {
            padding: 10px 25px;
            margin-left: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: background-color 0.2s ease, opacity 0.2s ease;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .char-counter {
            font-size: 13px;
            color: #777;
            text-align: right;
            margin-top: 8px;
        }
        .char-counter .warning {
            color: #ffc107; /* 노란색 */
            font-weight: bold;
        }
        .char-counter .danger {
            color: #dc3545; /* 빨간색 */
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="edit-form">
        <h2>댓글 수정</h2>
        <form action="" method="post">
            <!-- CSRF 토큰: 보안 강화를 위해 반드시 포함되어야 합니다. -->
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            
            <div class="form-group">
                <textarea name="content" rows="6" maxlength="1000" required 
                          placeholder="댓글을 입력하세요..." 
                          oninput="updateCharCounter(this)"><?= htmlspecialchars($c['content']) ?></textarea>
                <div class="char-counter">
                    <span id="char-count"><?= strlen($c['content']) ?></span>/1000 글자
                </div>
            </div>
            
            <div class="button-group">
                <button type="button" class="btn btn-secondary" 
                        onclick="if(confirm('수정을 취소하시겠습니까? 작성 중인 내용은 저장되지 않습니다.')) location.href='post_view.php?id=<?= urlencode($pid) ?>'">
                    취소
                </button>
                <button type="submit" class="btn btn-primary">수정 완료</button>
            </div>
        </form>
    </div>

    <script>
        // 글자 수 카운터 업데이트 함수
        function updateCharCounter(textarea) {
            const charCount = textarea.value.length;
            const counter = document.getElementById('char-count');
            counter.textContent = charCount;
            
            // 글자 수가 특정 임계치에 도달하면 색상 변경하여 시각적으로 경고
            if (charCount > 900) {
                counter.className = 'danger'; // 빨간색
            } else if (charCount > 800) {
                counter.className = 'warning'; // 노란색
            } else {
                counter.className = ''; // 기본색
            }
        }

        // 페이지 로드 시 초기 글자 수 설정 및 카운터 업데이트
        document.addEventListener('DOMContentLoaded', function() {
            const textarea = document.querySelector('textarea[name="content"]');
            if (textarea) {
                updateCharCounter(textarea);
            }

            // 폼 제출 시 최종 클라이언트 측 유효성 검사 (서버 측 검증도 중요!)
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const content = document.querySelector('textarea[name="content"]').value.trim();
                    
                    if (content.length === 0) {
                        alert('댓글 내용을 입력해주세요.');
                        e.preventDefault(); // 폼 제출 방지
                        return false;
                    }
                    
                    if (content.length > 1000) {
                        alert('댓글이 너무 깁니다. (최대 1000자)');
                        e.preventDefault(); // 폼 제출 방지
                        return false;
                    }
                });
            }
        });
    </script>
</body>
</html>

<?php
// post_write.php
require_once __DIR__ . '/config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// --- 게시판 ID 처리 ---
// URL에서 board_id를 가져옴 (기본값: 1번 게시판)
$boardId = (int)($_GET['board_id'] ?? 1); 
if ($boardId <= 0) {
    $boardId = 1; // 유효하지 않은 board_id는 기본값 1로 설정
}

// 게시판 이름 매핑 (사용자에게 보여줄 이름)
$boardNames = [
    1 => "공지 게시판",
    2 => "바보 게시판"
];

// 현재 게시판 이름 설정
$currentBoardName = $boardNames[$boardId] ?? "알 수 없는 게시판";

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title   = trim($_POST['title']   ?? '');
    $content = trim($_POST['content'] ?? '');
    $selectedBoardId = (int)($_POST['board_id'] ?? $boardId); // POST로 받은 게시판 ID
    
    if (!$title || !$content) {
        $error = '제목과 내용을 모두 입력하세요.';
    } else {
        // 1) 게시글 저장 (board_id 포함)
        $stmt = mysqli_prepare(
            $mysqli,
            "INSERT INTO posts (user_id, board_id, title, content, reg_date)
             VALUES (?, ?, ?, ?, NOW())"
        );
        mysqli_stmt_bind_param($stmt, 'iiss', $_SESSION['user_id'], $selectedBoardId, $title, $content);
        mysqli_stmt_execute($stmt);
        $post_id = mysqli_stmt_insert_id($stmt);
        
        // 2) 파일 업로드 처리
        if (
            isset($_FILES['upload_file']) &&
            $_FILES['upload_file']['error'] === UPLOAD_ERR_OK
        ) {
            $orig = $_FILES['upload_file']['name'];
            $ext  = pathinfo($orig, PATHINFO_EXTENSION);
            $save = uniqid() . ".$ext";
            
            // uploads 폴더가 없으면 생성
            $upload_dir = __DIR__ . "/uploads";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $upload_path = $upload_dir . "/$save";
            
            if (move_uploaded_file($_FILES['upload_file']['tmp_name'], $upload_path)) {
                $s = mysqli_prepare(
                    $mysqli,
                    "INSERT INTO files (post_id, orig_name, saved_name, upload_date)
                     VALUES (?, ?, ?, NOW())"
                );
                mysqli_stmt_bind_param($s, 'iss', $post_id, $orig, $save);
                mysqli_stmt_execute($s);
            }
        }
        
        // 글 등록 후 해당 게시판의 목록 페이지로 이동
        header("Location: post_list.php?board_id=$selectedBoardId");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>글쓰기 - <?= htmlspecialchars($currentBoardName) ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* 기본적인 스타일 - post_list.php와 일관성 유지 */
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background-color: #ffe6f2; /* 연한 핑크 배경 */
            color: #333; 
        }
        h2 { 
            color: #d63384; /* 진한 핑크 헤더 */
            text-align: center; 
            margin-bottom: 30px; 
            font-size: 2em;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }

        /* 폼 컨테이너 */
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        /* 게시판 선택 영역 */
        .board-selector {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #fff0f5;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .board-selector label {
            font-weight: bold;
            color: #d63384;
            font-size: 1.1em;
            margin-right: 15px;
        }
        .board-selector select {
            padding: 8px 12px;
            border: 1px solid #ffb3d9;
            border-radius: 5px;
            background-color: #fff;
            font-size: 15px;
            outline: none;
            transition: border-color 0.2s;
        }
        .board-selector select:focus {
            border-color: #d63384;
        }

        /* 폼 요소들 */
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #d63384;
            font-size: 1.1em;
        }
        input[type="text"], textarea, input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ffb3d9;
            border-radius: 5px;
            font-size: 15px;
            margin-bottom: 20px;
            outline: none;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }
        input[type="text"]:focus, textarea:focus {
            border-color: #d63384;
        }
        textarea {
            resize: vertical;
            min-height: 150px;
        }

        /* 버튼들 */
        .button-group {
            text-align: center;
            margin-top: 20px;
        }
        button {
            padding: 12px 25px;
            border: none;
            border-radius: 20px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin: 0 10px;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        button[type="submit"] {
            background-color: #ff80bf;
            color: white;
        }
        button[type="submit"]:hover {
            background-color: #e660a3;
            transform: translateY(-2px);
        }
        button[type="button"] {
            background-color: #6c757d;
            color: white;
        }
        button[type="button"]:hover {
            background-color: #545b62;
            transform: translateY(-2px);
        }

        /* 에러 메시지 */
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <h2>글쓰기</h2>
    
    <div class="form-container">
        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        
        <form action="" method="post" enctype="multipart/form-data">
            <!-- 게시판 선택 -->
            <div class="board-selector">
                <label for="board_id">게시판 선택:</label>
                <select id="board_id" name="board_id">
                    <?php foreach ($boardNames as $id => $name): ?>
                        <option value="<?= htmlspecialchars($id) ?>" <?= $id == $boardId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <label for="title">제목:</label>
            <input type="text" id="title" name="title" required>
            
            <label for="content">내용:</label>
            <textarea id="content" name="content" rows="8" required></textarea>
            
            <label for="upload_file">파일 첨부:</label>
            <input type="file" id="upload_file" name="upload_file">
            
            <div class="button-group">
                <button type="submit">등록</button>
                <button type="button" onclick="location.href='post_list.php?board_id=<?= htmlspecialchars($boardId) ?>'">취소</button>
            </div>
        </form>
    </div>
</body>
</html>
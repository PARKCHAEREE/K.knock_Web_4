// post_edit.php
<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
// 1) id 파라미터 유효성 체크
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    // id가 없거나 숫자가 아니면 목록으로 되돌아가기
    header('Location: post_list.php');
    exit;
}
$id = (int)$_GET['id'];
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$r  = mysqli_query($mysqli, "SELECT * FROM posts WHERE id=$id")->fetch_assoc();
if (!$r || $r['user_id'] != $_SESSION['user_id']) exit('권한없음');


$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    if (!$title || !$content) {
        $error = '제목과 내용을 모두 입력하세요.';
    } else {
        $stmt = mysqli_prepare($mysqli, "UPDATE posts SET title=?, content=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'ssi', $title, $content, $id);
        mysqli_stmt_execute($stmt);
        // 파일 교체
        if (!empty($_FILES['upload_file']['name'])) {
            mysqli_query($mysqli, "DELETE FROM files WHERE post_id=$id");
            $orig = $_FILES['upload_file']['name'];$ext = pathinfo($orig, PATHINFO_EXTENSION);
            $save = uniqid() . ".$ext";
            move_uploaded_file($_FILES['upload_file']['tmp_name'], __DIR__.'/uploads/'.$save);
            $s = mysqli_prepare($mysqli, "INSERT INTO files (post_id, orig_name, saved_name, upload_date) VALUES (?, ?, ?, NOW())");
            mysqli_stmt_bind_param($s, 'iss', $id, $orig, $save);
            mysqli_stmt_execute($s);
        }
        header("Location: post_view.php?id=$id"); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head><meta charset="UTF-8"><title>글 수정</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
  <h2>글 수정</h2>
  <?php if ($error): ?><p style="color:red;"><?= htmlspecialchars($error) ?></p><?php endif; ?>
  <form action="" method="post" enctype="multipart/form-data">
    제목:<br><input type="text" name="title" value="<?= htmlspecialchars($r['title']) ?>" required><br><br>
    내용:<br><textarea name="content" rows="6" required><?= htmlspecialchars($r['content']) ?></textarea><br><br>
    파일:<br><input type="file" name="upload_file"><br><br>
    <button type="submit">수정</button>
    <button type="button" onclick="location.href='post_view.php?id=$id'">취소</button>
  </form>
</body>
</html>
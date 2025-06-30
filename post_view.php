<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

$id = (int)$_GET['id']; // URL에서 게시글 ID를 정수형으로 가져옴

// 게시글 정보 조회
$post = mysqli_query($mysqli, "SELECT p.*, u.username FROM posts p JOIN users u ON p.user_id=u.id WHERE p.id=$id")->fetch_assoc();
if (!$post) {
    exit('게시물이 존재하지 않습니다.'); // 게시물이 없으면 스크립트 종료
}

// 댓글 목록 조회 (최신순 또는 ID순)
$comments = mysqli_query($mysqli, "SELECT c.*, u.username FROM comments c JOIN users u ON c.user_id=u.id WHERE c.post_id=$id ORDER BY c.id ASC");

// 파일 목록 조회
$files = mysqli_query($mysqli, "SELECT * FROM files WHERE post_id=$id");
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($post['title']) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2><?= htmlspecialchars($post['title']) ?></h2>
    <p><?= nl2br(htmlspecialchars($post['content'])) ?></p>
    <p>작성자: <?= htmlspecialchars($post['username']) ?> | <?= $post['reg_date'] ?></p>
    <hr>
    <?php if (mysqli_num_rows($files) > 0): ?>
        <h4>첨부 파일</h4>
        <?php while($f = mysqli_fetch_assoc($files)): ?>
            <p><a href="download.php?file_id=<?= htmlspecialchars($f['id']) ?>"><?= htmlspecialchars($f['orig_name']) ?></a></p>
        <?php endwhile; ?>
        <hr>
    <?php endif; ?>

    <h3>댓글</h3>
    <?php if (mysqli_num_rows($comments) > 0): ?>
        <?php while($c = mysqli_fetch_assoc($comments)): ?>
            <div>
                <b><?= htmlspecialchars($c['username']) ?></b> (<?= $c['reg_date'] ?>):<br>
                <?= nl2br(htmlspecialchars($c['content'])) ?><br>
                <?php if ($c['user_id'] == $_SESSION['user_id']): ?>
                    <!-- 댓글 수정 링크: post_id에 PHP 변수 $id 사용 -->
                    <a href="comment_edit.php?id=<?= htmlspecialchars($c['id']) ?>&post_id=<?= htmlspecialchars($id) ?>">수정</a> |
                    <!-- 댓글 삭제 링크: post_id에 PHP 변수 $id 사용 -->
                    <a href="comment_action.php?mode=delete&id=<?= htmlspecialchars($c['id']) ?>&post_id=<?= htmlspecialchars($id) ?>" onclick="return confirm('댓글을 삭제하시겠습니까?')">삭제</a>
                <?php endif; ?>
            </div>
            <br> <!-- 댓글 구분선 추가 -->
        <?php endwhile; ?>
    <?php else: ?>
        <p>아직 댓글이 없습니다.</p>
    <?php endif; ?>

    <hr>
    <h4>댓글 작성</h4>
    <form action="comment_action.php" method="post">
        <!-- !!! 이 부분이 가장 중요합니다: name="mode"와 value="write" !!! -->
        <input type="hidden" name="mode" value="write"> 
        <!-- post_id도 제대로 넘겨져야 합니다. -->
        <input type="hidden" name="post_id" value="<?= htmlspecialchars($id) ?>">
        
        <textarea name="content" rows="3" placeholder="댓글을 입력하세요" required></textarea><br>
        <button type="submit">댓글 등록</button>
    </form>
    <p><button onclick="location.href='post_list.php'">목록으로</button></p>
</body>
</html>

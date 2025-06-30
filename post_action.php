// post_action.php (삭제)
<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
if ($_GET['mode'] === 'delete') {
    $id = (int)$_GET['id'];
    mysqli_query($mysqli, "DELETE FROM posts WHERE id=$id AND user_id={$_SESSION['user_id']}");
}
header('Location: post_list.php');
exit;
?>

// post_view.php
<?php
include __DIR__ . '/config.php';
session_start();
if (!isset($_SESSION['user_id'])) header('Location: login.php');
$id = (int)$_GET['id'];
$post = mysqli_query($mysqli, "SELECT p.*, u.username FROM posts p JOIN users u ON p.user_id=u.id WHERE p.id=$id")->fetch_assoc();
if (!$post) exit('게시물 없음');
$comments = mysqli_query($mysqli, "SELECT c.*, u.username FROM comments c JOIN users u ON c.user_id=u.id WHERE c.post_id=$id ORDER BY c.id");
$files = mysqli_query($mysqli, "SELECT * FROM files WHERE post_id=$id");
?>
<!DOCTYPE html>
<html lang="ko">
<head><meta charset="UTF-8"><title><?= htmlspecialchars($post['title']) ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body>
  <h2><?= htmlspecialchars($post['title']) ?></h2>
  <p><?= nl2br(htmlspecialchars($post['content'])) ?></p>
  <p>작성자: <?= htmlspecialchars($post['username']) ?> | <?= $post['reg_date'] ?></p>
  <hr>
  <?php while($f = mysqli_fetch_assoc($files)): ?>
    <a href="download.php?file_id=<?= $f['id'] ?>"><?= htmlspecialchars($f['orig_name']) ?></a><br>
  <?php endwhile; ?>
  <hr>
  <h3>댓글</h3>
  <?php while($c = mysqli_fetch_assoc($comments)): ?>
    <div>
      <b><?= htmlspecialchars($c['username']) ?></b> (<?= $c['reg_date'] ?>):<br>
      <?= nl2br(htmlspecialchars($c['content'])) ?><br>
      <?php if ($c['user_id'] == $_SESSION['user_id']): ?>
        <a href="comment_edit.php?id=<?= $c['id'] ?>&post_id=$id">수정</a> |
        <a href="comment_action.php?mode=delete&id=<?=$c['id']?>&post_id=$id" onclick="return confirm('댓글 삭제?')">삭제</a>
      <?php endif; ?>
    </div>
  <?php endwhile; ?>
  <form action="comment_action.php" method="post">
    <input type="hidden" name="mode" value="write">
    <input type="hidden" name="post_id" value="$id">
    <textarea name="content" rows="3" required></textarea><br>
    <button type="submit">댓글 등록</button>
  </form>
  <p><button onclick="location.href='post_list.php'">목록</button></p>
</body>
</html>
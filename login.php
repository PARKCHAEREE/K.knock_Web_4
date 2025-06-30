<?php
// login.php
session_start();
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/config.php';  // $mysqli 연결

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass =            $_POST['password'] ?? '';

    if (!$user || !$pass) {
        $error = '아이디와 비밀번호를 모두 입력해주세요.';
    } else {
        // 사용자 조회
        $stmt = mysqli_prepare($mysqli,
            "SELECT id, password FROM users WHERE username = ? OR email = ?"
        );
        mysqli_stmt_bind_param($stmt, 'ss', $user, $user);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $uid, $hash);
        if (mysqli_stmt_fetch($stmt)) {
            if (password_verify($pass, $hash)) {
                // 로그인 성공
                $_SESSION['user_id']  = $uid;
                $_SESSION['username'] = $user;
                header('Location: post_list.php');
                exit;
            }
        }
        $error = '아이디 또는 비밀번호가 올바르지 않습니다.';
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>Sign in</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <h2>Sign in</h2>

  <?php if ($error): ?>
    <p style="color:red;"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <form action="" method="post">
    <table>
      <tr>
        <td><input
              type="text"
              name="username"
              placeholder="Email or Username"
              required></td>
      </tr>
      <tr>
        <td><input
              type="password"
              name="password"
              placeholder="Password"
              required></td>
      </tr>
      <tr>
        <td>
          <label>
            <input type="checkbox" name="remember">
            Remember me
          </label>
        </td>
      </tr>
      <tr>
        <td>
          <button type="submit" class="btn">Sign in</button>
        </td>
      </tr>
      <tr>
        <td>
          <button
            type="button"
            class="btn"
            onclick="location.href='oauth_google.php'">
            Sign in with Google
          </button>
        </td>
      </tr>
      <tr>
        <td class="join">
          <a href="forgot_password.html">Forgotten your password?</a><br>
          <a href="register.php">Create a new account</a>
        </td>
      </tr>
    </table>
  </form>
</body>
</html>

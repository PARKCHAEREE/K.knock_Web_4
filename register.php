<?php 
// signup.php

session_start();
header('Content-Type: text/html; charset=utf-8');

// DB 접속 설정
require_once __DIR__ . '/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username         = trim($_POST['username']         ?? '');
    $email            = trim($_POST['email']            ?? '');
    $password         =            $_POST['password']         ?? '';
    $confirm_password =            $_POST['confirm_password'] ?? '';

    // 유효성 검사
    if (!$username || !$email || !$password || !$confirm_password) {
        $error = '모든 항목을 입력해주세요.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '유효한 이메일 주소를 입력해주세요.';
    } elseif ($password !== $confirm_password) {
        $error = '비밀번호가 일치하지 않습니다.';
    } else {
        // 중복 확인
        $stmt = mysqli_prepare($mysqli, "SELECT id FROM users WHERE username = ? OR email = ?");
        mysqli_stmt_bind_param($stmt, 'ss', $username, $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = '이미 사용 중인 사용자명 또는 이메일입니다.';
        } else {
            mysqli_stmt_close($stmt);
            // 비밀번호 해시
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($mysqli,
                "INSERT INTO users (username, email, password, reg_date)
                 VALUES (?, ?, ?, NOW())"
            );
            mysqli_stmt_bind_param($stmt, 'sss', $username, $email, $hash);
            if (mysqli_stmt_execute($stmt)) {
                echo "<script>
                        alert('회원가입이 완료되었습니다. 로그인 페이지로 이동합니다.');
                        location.href='login.php';
                      </script>";
                exit;
            } else {
                $error = '가입 중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>Register</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <h2>Register</h2>

  <?php if ($error): ?>
    <p style="color:red;"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <form action="" method="post">
    <table>
      <tr>
        <td><input type="text"     name="username"         placeholder="Name"             required></td>
      </tr>
      <tr>
        <td><input type="email"    name="email"            placeholder="Email"            required></td>
      </tr>
      <tr>
        <td><input type="password" name="password"         placeholder="Password"         required></td>
      </tr>
      <tr>
        <td><input type="password" name="confirm_password" placeholder="Confirm Password" required></td>
      </tr>
      <tr>
        <td class="join">
          <button type="submit">Register</button>
        </td>
      </tr>
      <tr>
        <td>
          이미 계정이 있으시면 <a href="login.php">Sign in</a>
        </td>
      </tr>
    </table>
  </form>
</body>
</html>

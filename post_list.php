<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php'; // session_start()와 $_SESSION['username'] 확인 필요

// 현재 로그인한 사용자의 username을 세션에서 가져옴 (없으면 빈 문자열)
$loggedInUsername = $_SESSION['username'] ?? ''; 

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
    // 필요하다면 여기에 더 많은 게시판 추가 (예: 3 => "공지사항 게시판")
];

// 현재 게시판 이름 설정
$currentBoardName = $boardNames[$boardId] ?? "알 수 없는 게시판";

// --- 검색 기능 처리 ---
$searchQuery = trim($_GET['search'] ?? ''); // 제목/내용 검색어
$userSearchQuery = trim($_GET['user_search'] ?? ''); // 작성자 검색어

$whereClauses = ["p.board_id = " . $boardId]; // 기본적으로 현재 board_id로 필터링

// 제목 또는 내용 검색어가 있을 경우
if (!empty($searchQuery)) {
    // SQL 인젝션 방지를 위해 mysqli_real_escape_string 사용 (Prepared Statement가 더 안전)
    $escapedSearchQuery = mysqli_real_escape_string($mysqli, $searchQuery);
    $whereClauses[] = "(p.title LIKE '%" . $escapedSearchQuery . "%' OR p.content LIKE '%" . $escapedSearchQuery . "%')";
}

// 작성자 검색어가 있을 경우
if (!empty($userSearchQuery)) {
    $escapedUserSearchQuery = mysqli_real_escape_string($mysqli, $userSearchQuery);
    $whereClauses[] = "u.username LIKE '%" . $escapedUserSearchQuery . "%'";
}

$whereSql = '';
if (!empty($whereClauses)) {
    $whereSql = " WHERE " . implode(' AND ', $whereClauses);
}


// --- 정렬 기능 처리 ---
$sortOrder = $_GET['sort'] ?? 'latest'; // 기본값: 최신순

$orderBySql = '';
switch ($sortOrder) {
    case 'oldest':
        $orderBySql = "ORDER BY p.id ASC"; // 오래된 순
        break;
    case 'latest':
    default:
        $orderBySql = "ORDER BY p.id DESC"; // 최신순
        break;
}

// --- 게시물 목록 조회 ---
$sql = "SELECT p.id, p.title, u.username, p.reg_date
        FROM posts p
        JOIN users u ON p.user_id = u.id
        " . $whereSql . "
        " . $orderBySql;

$Q = mysqli_query($mysqli, $sql);

// 쿼리 실행 실패 시 오류 처리
if (!$Q) {
    die("데이터베이스 쿼리 실행 중 오류 발생: " . mysqli_error($mysqli));
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($currentBoardName) ?> - 게시판</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* 기본적인 스타일 */
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background-color: #ffe6f2; /* 연한 핑크 배경 */
            color: #333; 
        }
        h1 { 
            color: #d63384; /* 진한 핑크 헤더 */
            text-align: center; 
            margin-bottom: 30px; 
            font-size: 2.5em;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }

        /* 컨트롤 영역 */
        .board-selector, .search-sort-area {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #fff0f5; /* 더 연한 핑크 */
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            flex-wrap: wrap; 
            gap: 10px; /* 요소들 간 간격 */
        }
        .board-selector span {
            font-weight: bold;
            margin-right: 10px;
            font-size: 1.1em;
            color: #d63384;
        }
        .board-selector a, .control-buttons button {
            padding: 10px 18px;
            border: none;
            border-radius: 20px; /* 둥근 버튼 */
            background-color: #ff80bf; /* 중간 핑크 */
            color: white;
            cursor: pointer;
            text-decoration: none;
            font-size: 15px;
            font-weight: bold;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .board-selector a:hover, .control-buttons button:hover {
            background-color: #e660a3; /* 호버 시 약간 진하게 */
            transform: translateY(-2px);
        }
        .board-selector a.current-board {
            background-color: #d63384; /* 현재 게시판은 더 진하게 */
            pointer-events: none; /* 클릭 비활성화 */
        }

        /* 검색 및 정렬 폼 요소 */
        .search-sort-area label {
            color: #d63384;
            font-weight: bold;
        }
        .search-sort-area select, .search-sort-area input[type="text"] {
            font-size: 15px;
            padding: 8px 12px;
            border: 1px solid #ffb3d9; /* 연한 핑크 보더 */
            border-radius: 5px;
            background-color: #fff;
            outline: none;
            transition: border-color 0.2s;
        }
        .search-sort-area select:focus, .search-sort-area input[type="text"]:focus {
            border-color: #d63384;
        }
        .search-sort-area button {
            background-color: #8a2be2; /* 보라색 계열로 포인트 */
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .search-sort-area button:hover {
            background-color: #6a0dad;
        }

        /* 테이블 스타일 */
        table { 
            width: 100%; 
            border-collapse: separate; /* border-radius 적용을 위해 */
            border-spacing: 0;
            margin-top: 20px; 
            background-color: #ffffff; 
            border-radius: 10px; 
            overflow: hidden; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
        }
        th, td { 
            border-bottom: 1px solid #f9e6f2; /* 연한 핑크 경계선 */
            padding: 15px; 
            text-align: left; 
        }
        th { 
            background-color: #ffb3d9; /* 테이블 헤더 핑크 */
            color: #7b1d44; /* 진한 핑크 텍스트 */
            font-weight: bold; 
            font-size: 1.05em;
        }
        th:first-child { border-top-left-radius: 10px; }
        th:last-child { border-top-right-radius: 10px; }
        tr:last-child td { border-bottom: none; } /* 마지막 행 하단 선 제거 */

        td a { 
            text-decoration: none; 
            color: #d63384; 
            font-weight: bold;
        }
        td a:hover { text-decoration: underline; }
        .action-links a {
            margin-right: 10px;
            color: #8a2be2; /* 수정/삭제 링크도 보라색 */
            text-decoration: none;
            font-weight: bold;
        }
        .action-links a:hover {
            text-decoration: underline;
        }
        .no-posts {
            text-align: center;
            padding: 30px;
            color: #999;
            font-style: italic;
        }
        .control-buttons {
            text-align: right;
            margin-bottom: 20px;
        }
        .control-buttons button:nth-child(2) { /* 로그아웃 버튼 */
            background-color: #dc3545; /* 빨간색 유지 */
        }
        .control-buttons button:nth-child(2):hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <h1><?= htmlspecialchars($currentBoardName) ?></h1>

    <div class="board-selector">
        <span>게시판 선택:</span>
        <?php foreach ($boardNames as $id => $name): ?>
            <a href="post_list.php?board_id=<?= htmlspecialchars($id) ?>" class="<?= $id == $boardId ? 'current-board' : '' ?>">
                <?= htmlspecialchars($name) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <form action="post_list.php" method="GET" class="search-sort-area">
        <!-- 현재 게시판 ID를 숨겨진 필드로 전달하여 검색/정렬 후에도 동일한 게시판에 머물도록 함 -->
        <input type="hidden" name="board_id" value="<?= htmlspecialchars($boardId) ?>">
        
        <label for="search">검색:</label>
        <input type="text" id="search" name="search" placeholder="제목 또는 내용" value="<?= htmlspecialchars($searchQuery) ?>">
        
        <label for="user_search">작성자:</label>
        <input type="text" id="user_search" name="user_search" placeholder="작성자 이름" value="<?= htmlspecialchars($userSearchQuery) ?>">
        
        <label for="sort">정렬:</label>
        <select id="sort" name="sort">
            <option value="latest" <?= $sortOrder == 'latest' ? 'selected' : '' ?>>최신순</option>
            <option value="oldest" <?= $sortOrder == 'oldest' ? 'selected' : '' ?>>오래된순</option>
        </select>
        
        <button type="submit">검색/정렬</button>
    </form>

    <div class="control-buttons">
        <!-- 글쓰기 버튼 클릭 시 현재 게시판 ID를 함께 넘겨줌 -->
        <button onclick="location.href='post_write.php?board_id=<?= htmlspecialchars($boardId) ?>'">글쓰기</button>
        <button onclick="location.href='logout.php'">로그아웃</button>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>제목</th>
                <th>작성자</th>
                <th>등록일</th>
                <th>액션</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if (mysqli_num_rows($Q) > 0): 
                while ($r = mysqli_fetch_assoc($Q)): 
            ?>
                <tr>
                    <td><?= htmlspecialchars($r['id']) ?></td>
                    <td>
                        <a href="post_view.php?id=<?= htmlspecialchars($r['id']) ?>&board_id=<?= htmlspecialchars($boardId) ?>">
                            <?= htmlspecialchars($r['title']) ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars($r['username']) ?></td>
                    <td><?= htmlspecialchars($r['reg_date']) ?></td>
                    <td class="action-links">
                        <?php 
                        // 로그인한 사용자와 게시글 작성자가 같을 경우에만 수정/삭제 링크 표시
                        if (!empty($loggedInUsername) && $r['username'] === $loggedInUsername): 
                        ?>
                            <a href="post_edit.php?id=<?= htmlspecialchars($r['id']) ?>&board_id=<?= htmlspecialchars($boardId) ?>">수정</a> |
                            <a href="post_action.php?mode=delete&id=<?= htmlspecialchars($r['id']) ?>&board_id=<?= htmlspecialchars($boardId) ?>" onclick="return confirm('이 게시글을 정말 삭제하시겠습니까?')">삭제</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php 
                endwhile;
            else: 
            ?>
                <tr>
                    <td colspan="5" class="no-posts">게시글이 없습니다.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>

<?php
session_start();
require_once 'config.php';   // DSN·DB_USER·DB_PASSWORD 정의

// ────────────────────────────────────────────────
// 1. POST 요청이면 DB INSERT 처리
// ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // (1) 로그인 확인
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.html');   exit;
    }

    // (2) 입력값 검증
    $themeText = trim($_POST['theme'] ?? '');
    if ($themeText === '') {
        $error = 'テーマが入力されていません。';
    } else {
        // 세션에 theme_text를 저장
        $_SESSION['theme_text'] = $themeText;

        // 'writing.php'에서 button을 누를 경우 DB에 저장하도록 함
        try {
            // (3) DB 연결
            $pdo = new PDO(DSN, DB_USER, DB_PASSWORD,
                           [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

            // (4) INSERT
            $sql  = 'INSERT INTO theme (user_id, theme_text, created_at)
                     VALUES (:user_id, :theme_text, NOW())';
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':user_id',  $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindValue(':theme_text', $themeText,          PDO::PARAM_STR);
            $stmt->execute();

            // (5) theme_id를 세션 저장하기
            $themeId = (int) $pdo->lastInsertId();   // ← 새로 생긴 theme.id
            $_SESSION['theme_id'] = $themeId;

            // (6) 성공 시 다음 단계로
            header('Location: writing.php?theme_id=' . $themeId);
            exit;

        } catch (PDOException $e) {
            $error = 'データベースエラー: ' .
                     htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
        header('Location: writing.php?theme_id=' . $themeId);
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8" />
  <title>第二言語のライティング自己学習システム</title>
  <style>
    html,
    body {
      margin: 0;
      font-family: sans-serif;
      background-color: rgb(244, 246, 255);
      height: 100%;
    }

    body {
      display: flex;
      flex-direction: column;
      margin: 0;
    }
  
    .header {
      background-color: black;
      color: white;
      padding: 10px 20px;
      font-size: 18px;
    }
    /* === (기존 CSS 그대로) === */
    .question{font-size:26px;margin-bottom:20px;}
    .theme-container{padding-top:60px;display:flex;justify-content:center;
                     align-items:center;height:80vh;box-sizing:border-box;}
    .theme-form{display:flex;flex-direction:column;align-items:center;width:70%;}
    .theme-form textarea{padding:10px;font-size:16px;border:1px solid #333;width:100%;}
    .theme-form button{margin-top:20px;padding:10px;font-size:18px;background:#000;
                       color:#fff;border:none;cursor:pointer;}
    .error{color:red;margin-bottom:15px;}


    .button-row {
      display: flex;
      justify-content: space-between; /* 좌우로 버튼 분배 */
      margin-top: 12px;
      width: 100%;
    }

    .back-button, .next-button {
      padding: 10px 20px;
      font-size: 18px;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      color: white;
      /* margin: 0 15px; */
      box-shadow: 3px 2px 2px grey;
    }
  </style>
</head>
<body>
  <!-- 헤더 -->
  <?php include __DIR__.'/header.php'; ?>

  <!-- 메인 -->
  <div class="theme-container">
    
    <form method="POST" action="theme.php" class="theme-form">
      <h2 class="question" style="font-size: 30px;">英語の文章にしたい日本語の文章を入力してください。</h2>

      <!-- 오류 메시지 표시 -->
      <?php if (!empty($error)): ?>
        <div class="error"><?= $error ?></div>
      <?php endif; ?>

      <textarea name="theme" rows="4" style="font-size: 18px;"></textarea>

      <div class="button-row">
        <button type="button" class="back-button" style="background-color: #6c757d;" onclick="location.href='index.php'" class="back-button">◀︎ ホームに戻る</button>
        <button type="submit" class="next-button" style="background-color: rgb(16, 55, 92);" class="next-button">ライティングに進む ▶︎</button>
      </div>
    </form>
  </div>
</body>
</html>
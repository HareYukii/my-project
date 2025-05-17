<?php
session_start();
require_once 'config.php';           // ← DSN・DB_USER・DB_PASSWORD 정의

/* ─────────── 1. POST 요청이면 로그인 처리 ─────────── */
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $userid   = trim($_POST['userid']   ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($userid === '' || $password === '') {
        $error = 'ユーザーIDとパスワードを入力してください。';
    } else {
        try {
            $pdo = new PDO(DSN, DB_USER, DB_PASSWORD, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);

            $stmt = $pdo->prepare('SELECT * FROM users WHERE userid = :userid');
            $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($userid == $user['userid'] && $password == $user['password']) {
                // 로그인 성공 → 세션 저장
                $_SESSION['user_id'] = $user['id'];      // 숫자 PK
                $_SESSION['userid']  = $user['userid'];  // 문자열 ID (필요 시)

                header('Location: index.php');          // 홈으로 이동
                exit;
            } else {
                $error = 'ユーザーIDまたはパスワードが間違っています。';
            }
        } catch (PDOException $e) {
            $error = 'データベース接続エラー: ' .
                     htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }
}

/* ─────────── 2. 폼 화면 출력 (GET 또는 실패 시) ─────────── */
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>第二言語のライティング自己学習システム</title>
  <link rel="stylesheet" href="style.css" />
  <style>
    html,
    body {
      margin: 0;
      font-family: sans-serif;
      background-color: white;
      height: 100%;
    }

    body {
      display: flex;
      flex-direction: column;
      margin: 0;
    }

    .header{
      background:rgb(16, 55, 92); color:#fff;
      display:flex; align-items:center;
      padding:14px 20px; font-weight:550;
      font-size: 20px;
    }
    .title-link { color:#fff; text-decoration:none; }
    .header-right{ margin-left:auto; display:flex; align-items:center; gap:12px; }
  
    .title-link { color:#fff; text-decoration:none; }

    .login-container {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 80vh;
    }

    .login-form {
    display: flex;
    flex-direction: column;
    width: 300px;
    }

    .login-form label {
    margin-top: 15px;
    font-size: 16px;
    }
  
    .login-form input {
      padding: 10px;
      font-size: 16px;
      border: 1px solid #333;
    }
  
    .login-form button {
      margin: auto;
      margin-top: 20px;
      padding: 10px 20px;
      box-shadow: 3px 2px 2px grey;
      width: fit-content;
      font-size: 18px;
      background-color: rgb(16, 55, 92);
      color: white;
      border: none;
      border-radius: 10px;
      cursor: pointer;
    }
    
    .login-form button:hover {
      background-color: #333;
    }

  </style>
</head>
<body>
  <div class="header">
    <span><a href="index.php" class="title-link">第二言語のライティング自己学習システム</a></span>
  </div>

  <div class="login-container">
    <?php if ($error): ?>
      <div class="login-error" style="color:red;margin-bottom:10px">
        <?= $error ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="login.php" class="login-form">
      <label for="userid" style="font-size: 20px;">User&nbsp;ID</label>
      <input type="text" id="userid" name="userid" style="font-size: 20px;" required/>

      <label for="password" style="font-size: 20px;">Password</label>
      <input type="password" id="password" name="password" style="font-size: 20px;" required />

      <button type="submit">Login</button>
    </form>
  </div>
</body>
</html>
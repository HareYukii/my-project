<?php
/*─────────────── ① 세션 & 로그인 확인 ───────────────*/
session_start();
require_once __DIR__.'/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

/*─────────────── ② 사용자 이름을 (최초 1회) 캐싱 ─────────*/
if (empty($_SESSION['user_name'])) {
    try {
        $pdo = new PDO(DSN, DB_USER, DB_PASSWORD,
                       [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);

        $stmt = $pdo->prepare('SELECT name FROM users WHERE id = :id');
        $stmt->bindValue(':id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $_SESSION['user_name'] = $row ? $row['name'] : '名無し';
    } catch (PDOException $e) {
        $_SESSION['user_name'] = '名無し';
    }
}
?>
<!--─────────────── ③ 헤더 HTML/CSS ───────────────-->
<style>
  .header{
    background:rgb(16, 55, 92); color:#fff;
    display:flex; align-items:center;
    padding:14px 20px; font-weight:550;
    font-size: 20px;
  }
  .title-link { color:#fff; text-decoration:none; }
  .header-right{ margin-left:auto; display:flex; align-items:center; gap:12px; }
  .user-name{ font-size:16px; font-weight: 300;}
  .logout-btn{
    color:white; background: rgb(16, 55, 92); text-decoration:none;
    padding:4px 10px; border-radius:4px; font-size:14px; border: none;
  }
  
</style>

<div class="header">
  <span><a href="index.php" class="title-link">第二言語のライティング自己学習システム</a></span>
  <div class="header-right">
    <span class="user-name" style="font-size: 18px;"><?= htmlspecialchars($_SESSION['userid'],ENT_QUOTES,'UTF-8') . ' / ' . htmlspecialchars($_SESSION['user_name'],ENT_QUOTES,'UTF-8') ?> </span>
    <a class="logout-btn" href="logout.php" style="font-size: 18px;">ログアウト</a>
  </div>
</div>
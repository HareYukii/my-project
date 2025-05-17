<?php
session_start();
include 'config.php';   // DSN·DB_USER·DB_PASSWORD 정의

$user_id = $_SESSION['user_id'];

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

// DB에 접속하기
$pdo = new PDO(DSN, DB_USER, DB_PASSWORD,
[PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// DB에서 학습자가 학습한 theme의 ID와 text 불러오기
$sql1  = 'SELECT id, theme_text, created_at FROM theme WHERE user_id = :user_id';
$stmt1 = $pdo->prepare($sql1);
$stmt1->execute([':user_id' => $user_id]);
$response1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);

// $theme_ids와 $theme_texts 생성
$theme_ids = [];
$theme_texts = [];
$created_at = [];
foreach ($response1 as $row) {
    $theme_ids[] = $row['id'];
    $theme_texts[] = $row['theme_text'];
    $created_at[] = $row['created_at'];
}

// echo "<br>" . "theme_ids" . "<br>";
// var_dump($theme_ids);
// echo "<br>";
// echo "<br>" . "theme_texts" . "<br>";
// var_dump($theme_texts);
// echo "<br>";

// DB에서 학습자가 작성한 writing_text와 approach 불러오기
$response2_list = [];
$sql2  = 'SELECT writing_text, approach FROM l2_writing WHERE theme_id = :theme_id';
$stmt2 = $pdo->prepare($sql2);
foreach ($theme_ids as $theme_id) {
  $stmt2->execute([':theme_id' => $theme_id]);
  $response2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
  $response2_list[] = $response2;
}

// $writing_text_list 생성하기
$writing_text_list = [];
foreach ($response2_list as $group) {
    $texts = [];

    foreach ($group as $entry) {
        if (isset($entry['writing_text'])) {
            $texts[] = $entry['writing_text'];
        }
    }

    $writing_text_list[] = $texts;
}

// echo "<br>" . "writing_text_list" . "<br>";
// var_dump($writing_text_list);
// echo "<br>";

// $approach_list 생성하기
// $approach_list = [];
// foreach ($response2_list as $group) {
//     $texts = [];

//     foreach ($group as $entry) {
//         if (isset($entry['approach'])) {
//             $texts[] = $entry['approach'];
//         }
//     }

//     $approach_list[] = $texts;
// }

// echo "<br>" . "approach_list" . "<br>";
// var_dump($approach_list);
// echo "<br>";

// DB에서 학습자가 작성한 reflection, created_at 불러오기
$response3_list = [];
$sql3  = 'SELECT reflection_text FROM reflection WHERE theme_id = :theme_id';
$stmt3 = $pdo->prepare($sql3);
foreach ($theme_ids as $theme_id) {
  $stmt3->execute([':theme_id' => $theme_id]);
  $response3 = $stmt3->fetchAll(PDO::FETCH_ASSOC);
  $response3_list[] = $response3;
}

// $reflection_text_list 생성하기
$reflection_text_list = [];
foreach ($response3_list as $group) {
    $texts = [];

    foreach ($group as $entry) {
        if (isset($entry['reflection_text'])) {
            $texts[] = $entry['reflection_text'];
        }
    }

    $reflection_text_list[] = $texts;
}

// echo "<br>" . "reflection_text_list" . "<br>";
// var_dump($reflection_text_list);
// echo "<br>";

// $created_at_list 생성하기
// $created_at_list = [];
// foreach ($response3_list as $group) {
//     $texts = [];

//     foreach ($group as $entry) {
//         if (isset($entry['created_at'])) {
//             $texts[] = $entry['created_at'];
//         }
//     }

//     $created_at_list[] = $texts;
// }

// echo "<br>" . "created_at_list" . "<br>";
// var_dump($created_at_list);
// echo "<br>";

?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>振り返り一覧</title>
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
      .writing-btn {
        color:white; background: rgb(16, 55, 92); text-decoration: underline;
        padding: 0px 10px; border-radius:4px; font-size: 20px; font-weight: 550;border: none; margin-left: 20px;
        height: fit-content;
      }
      .logout-btn{
        color:white; background: rgb(16, 55, 92); text-decoration:none;
        padding:4px 10px; border-radius:4px; font-size:14px; border: none;
      }

      body {
        margin: 0;
        font-family: "Hiragino Kaku Gothic ProN", sans-serif;
        background-color: #fff;
      }

      .container {
        max-width: 700px;
        margin: 20px auto;
        padding: 40px;
        background-color: white;
        border: solid #333 1px;
        border-radius: 20px;
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
      }

      .title {
        font-size: 40px;
        font-weight: 600;
        color: rgb(16, 55, 92);
        text-align: center!important;
        margin-bottom: 25px;
      }
      .card {
        border: 1px solid #333;
        border-radius: 14px;
        padding: 10px;
        margin-bottom: 14px;
        background-color: #fff;
        cursor: pointer;
        font-size: 18px;
        font-weight: 550;
      }

      .card:nth-child(even) {
        background-color: #eee;
      }

      .card p {
        margin: 6px 0;
        line-height: 1.5;
      }
      
      .card {cursor: pointer;}
  </style>
</head>
<body>

  <div class="header">
    <span><a href="index.php" class="title-link">第二言語のライティング自己学習システム</a></span>
    <a class="writing-btn" href="theme.php">ライティングに進む</a>
    <div class="header-right">
      <span class="user-name" style="font-size: 18px;"><?= htmlspecialchars($_SESSION['userid'],ENT_QUOTES,'UTF-8') . ' / ' . htmlspecialchars($_SESSION['user_name'],ENT_QUOTES,'UTF-8') ?> </span>
      <a class="logout-btn" href="logout.php" style="font-size: 18px;">ログアウト</a>
    </div>
  </div>

  <div class="container">
  
    <div class="title">今までのライティング</div>

    <?php for ($x = count($theme_ids) - 1; $x >= 0; $x--): ?>
      <div class="card" onclick="location.href='show_reflection.php?theme_id=<?= $theme_ids[$x] ?>'">
        <p>日にち：<?= $created_at[$x] ?></p>
        <p>日本語の文章：<?= $theme_texts[$x] ?></p>
        <p>教訓：<?= $reflection_text_list[$x][0] ?></p>
      </div>
    <?php endfor; ?>

  </div>

</body>
</html>
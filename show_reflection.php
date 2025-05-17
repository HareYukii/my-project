<?php
session_start();
include 'config.php';   // DSN·DB_USER·DB_PASSWORD 정의

$theme_id = isset($_GET['theme_id']) ? (int)$_GET['theme_id'] : null;
if (!$theme_id) {
    die('theme_id が指定されていません');
}

// DB에 접속하기
$pdo = new PDO(DSN, DB_USER, DB_PASSWORD,
[PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// DB에서 학습자가 학습한 theme의 ID와 text 불러오기
$sql1  = 'SELECT theme_text FROM theme WHERE id = :id';
$stmt1 = $pdo->prepare($sql1);
$stmt1->execute([':id' => $theme_id]);
$theme_text = $stmt1->fetchAll(PDO::FETCH_ASSOC);

// echo "<br>" . "theme_text" . "<br>";
// var_dump($theme_text);
// echo "<br>";

// DB에서 학습자가 작성한 writing_text와 approach 불러오기
$response2_list = [];
$sql2  = 'SELECT writing_text, approach FROM l2_writing WHERE theme_id = :theme_id';
$stmt2 = $pdo->prepare($sql2);
$stmt2->execute([':theme_id' => $theme_id]);
$response2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
$response2_list[] = $response2;

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
$approach_list = [];
foreach ($response2_list as $group) {
    $texts = [];

    foreach ($group as $entry) {
        if (isset($entry['approach'])) {
            $texts[] = $entry['approach'];
        }
    }

    $approach_list[] = $texts;
}

// echo "<br>" . "approach_list" . "<br>";
// var_dump($approach_list);
// echo "<br>";

// DB에서 학습자가 작성한 reflection_text 불러오기
$response3_list = [];
$sql3  = 'SELECT reflection_text FROM reflection WHERE theme_id = :theme_id';
$stmt3 = $pdo->prepare($sql3);
$stmt3->execute([':theme_id' => $theme_id]);
$reflection_text = $stmt3->fetchAll(PDO::FETCH_ASSOC);

// echo "<br>" . "reflection_text" . "<br>";
// var_dump($reflection_text);
// echo "<br>";

?>


<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>第二言語のライティング自己学習システム</title>

<style>
/* ---------- 공통 ---------- */
body{
  margin:0; font-family:"Segoe UI",sans-serif; background:#f5f5f5;
}

.header {
    background-color: black;
    color: white;
    padding: 10px 20px;
    font-size: 18px;
}

.title-button {
  background-color: black;
  color: white;
  text-decoration: none;
  text-align: center;
  font-size: 16px;
  display: inline-block;
}

/* 큰 외곽 박스 */
.sheet-wrapper{
  border:2px solid #000; margin:10px auto; margin-top: 30px; max-width:760px;
  background:#fff; padding:14px 18px; position: relative;
}

/* 검정 타이틀 바 */
.block-title{
  background:rgb(16, 55, 92); color:#fff; padding:6px 10px;
  font-weight:600; font-size: 20px;margin:0 0 6px 0;
}

/* ----- ② 회차별 카드 ----- */
.reflect-block{
  display:flex; gap:14px; border:1px solid #000;
  margin-top:14px; background:#fcfcfc;
}
.iter-badge{
  width:70px; background:rgb(16, 55, 92); color:#fff;
  display:flex; align-items:center; justify-content:center;
  font-weight:600; writing-mode:vertical-rl;   /* 세로 문자 대신 padding… */
  flex-direction:column; font-size: 20px;
}
.iter-badge span{
  writing-mode:horizontal-tb;
}
.reflect-fields{ flex:1; padding:12px 10px; }
.reflect-fields label{
  display:block; font-size:14px; font-weight:600; margin-top:6px;
}

#complete-area {
  margin-top: 15px;
}

.content-box {
  font-size: 18px;
}

/* ----- ③ 教訓化 영역 ----- */
#reflection div{
  width:100%; height:110px; resize:vertical;
}

.action-button {
  flex: 1;
  padding: 10px 20px;
  font-size: 18px;
  background-color: rgb(16, 55, 92);
  color: white;
  border: none;
  border-radius: 10px;
  box-shadow: 3px 2px 2px grey;
  cursor: pointer;
  display: block;
  margin: 0 auto;
}

/* ----- 반응형 살짝 ----- */
@media(max-width:560px){
  .reflect-block{ flex-direction:column; }
  .iter-badge{ width:auto; flex-direction:row; padding:4px 0; }
}

</style>
</head>
<body>

<!-- 헤더 -->
<?php include __DIR__.'/header.php'; ?>
<!-- <div class="header"><a href="index.php" class="title-button">第二言語のライティング自己学習システム</a></div> -->

<!-- 시트 Wrapper -->
<div class="sheet-wrapper">

  <!-- 英語の文章にした内容 -->
  <div id="theme-area">
    <p class="block-title" style="font-size: 20px;">英語の文章にした内容</p>
    <div class="content-box"><?= $theme_text[0]["theme_text"] ?></div>
  </div>

  <!-- ===== forループでカード生成 ===== -->
  <?php for ($x = 0; $x < count($writing_text_list[0]); $x++): ?>
    <div class="reflect-block">
      <div class="iter-badge" style="font-size: 20px;"><span><?= $x + 1 ?>回目</span></div>
      <div class="reflect-fields">
        <label style="font-size: 20px;">英語の文章</label>
        <div class="content-box">
          <?= $writing_text_list[0][$x] ?>
        </div>
        <label style="font-size: 20px;">工夫したこと</label>
        <div class="content-box">
          <?= $approach_list[0][$x] ?>
        </div>
      </div>
    </div>
  <?php endfor; ?>

  <!-- 教訓化 -->
  <form method="POST" class="writing-form">
    <div id="reflection" style="margin-top:18px;">
      <p class="block-title">教訓化</p>
      <div  style="font-size: 18px;">
        <?= $reflection_text[0]["reflection_text"] ?>
      </div>
    </div>
  </form>
</div><!-- sheet-wrapper -->
<button type="button" onclick="document.location='index.php'" class="action-button">ホームに戻る</button>

</body>
</html>
<?php
session_start();
include 'config.php';   // DSN·DB_USER·DB_PASSWORD 정의

$theme_id = $_SESSION['theme_id'];

// DB에서 학습자가 작성한 영문과 工夫点 불러오기
$pdo = new PDO(DSN, DB_USER, DB_PASSWORD,
[PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$sql1  = 'SELECT theme_text FROM theme WHERE id = :theme_id';
$stmt1 = $pdo->prepare($sql1);
$stmt1->execute([':theme_id' => $theme_id]);
$theme_text = $stmt1->fetch(PDO::FETCH_ASSOC);

$sql2  = 'SELECT writing_text, approach FROM l2_writing WHERE theme_id = :theme_id';
$stmt2 = $pdo->prepare($sql2);
$stmt2->execute([':theme_id' => $theme_id]);
$rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);

$repetition = count($rows);

$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $reflection_text = trim($_POST['reflection_text'] ?? '');
  // DB에 reflection_text 저장하기
  $pdo = new PDO(DSN, DB_USER, DB_PASSWORD,
  [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

  $sql  = 'INSERT INTO reflection (theme_id, reflection_text, created_at)
  VALUES (:theme_id, :reflection_text, NOW())';
  $stmt = $pdo->prepare($sql);
  $stmt->bindValue(':theme_id', $theme_id, PDO::PARAM_INT);
  $stmt->bindValue(':reflection_text', $reflection_text, PDO::PARAM_STR);
  $stmt->execute();

  // 저장 후 성공메시지 출력
  $success = true;
}
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

.button-row {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  margin: auto;
  margin-top: 20px;
  width: 60%;
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
  border:2px solid #000; margin:10px auto; margin-top: 20px; width:60%;
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

/* ----- ③ 教訓化 영역 ----- */
#reflection textarea{
  width:100%; min-height:110px; resize:vertical; font-size: 18px;
}

.action-button {
  padding: 10px 20px;
  font-size: 18px;
  color: white;
  border: none;
  border-radius: 10px;
  box-shadow: 3px 2px 2px grey;
  cursor: pointer;
}

.content-box {
  font-size: 18px;
} 

/* ----- 반응형 살짝 ----- */
@media(max-width:560px){
  .reflect-block{ flex-direction:column; }
  .iter-badge{ width:auto; flex-direction:row; padding:4px 0; }
}

.success-msg {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);

  background: whitesmoke;
  color: black;
  border: 3px solid black;
  padding: 14px 24px;
  border-radius: 6px;
  font-weight: bold;
  box-shadow: 0 2px 6px gray;
  z-index: 10;
  text-align: center;
}
</style>
</head>
<body>

<!-- 헤더 -->
<?php include __DIR__.'/header.php'; ?>
<!-- <div class="header"><a href="index.php" class="title-button">第二言語のライティング自己学習システム</a></div> -->

<div class="button-row">
  <button type="button" class="action-button" onclick="document.location='writing.php'" style="background-color: #6c757d;">◀︎ 前に戻る</button>
  <button type="submit" class="action-button" form="reflectionForm" style="background-color: rgb(16, 55, 92);">振り返りを登録する ▶︎</button>
</div>

<!-- 시트 Wrapper -->
<div class="sheet-wrapper">
  <!-- 英語の文章にした内容 -->
  <div id="theme-area">
    <p class="block-title">英語の文章にした内容</p>
    <div class="content-box"><?= $theme_text["theme_text"] ?></div>
  </div>

  <!-- ===== forループでカード生成 ===== -->
  <?php for ($i = 1; $i <= $repetition; $i++): ?>
    <div class="reflect-block">
      <div class="iter-badge"><span><?= $i ?>回目</span></div>
      <div class="reflect-fields">
        <label style="font-size: 18px;">英語の文章</label>
        <div class="content-box">
          <?= $rows[$i - 1]["writing_text"] ?>
        </div>
        <label style="font-size: 18px;">工夫したこと</label>
        <div class="content-box">
          <?= $rows[$i - 1]["approach"] ?>
        </div>
      </div>
    </div>
  <?php endfor; ?>

  <!-- 教訓化 -->
  <form method="POST" class="writing-form" id="reflectionForm">
    <div id="reflection" style="margin-top:18px;">
      <p class="block-title">教訓化</p>
      <textarea name="reflection_text" placeholder="できたことやできなかったこと、学んだことについて振り返ろう" required></textarea>
    </div>
  </form>

  <?php if ($success): ?>
    <div class="success-msg">保存が完了しました。3秒後にホームに戻ります。</div>

    <script>
    setTimeout(function() {
      window.location.href = 'index.php';
    }, 3000);
  </script>
  <?php endif; ?>

</div><!-- sheet-wrapper -->
</body>
</html>
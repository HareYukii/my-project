<?php
session_start();
include 'config.php';

$themeId = $_SESSION['theme_id'] ?? 'default';

/* ─────────────────────────────────────────────
   0.  apiKey, model 설정
   ──────────────────────────────────────────── */
$apiKey = 'YOUR_API_KEY';
$model = 'gpt-4o-mini';

// 페이지 번호 기본 설정
$selectedPageNum = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$selectedPageNum = max(1, $selectedPageNum); // 1 이상

// DB 연결
$pdo = new PDO(DSN, DB_USER, DB_PASSWORD,
[PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// 전체 페이지 수 조회
$sql = "SELECT COUNT(*) FROM l2_writing WHERE theme_id = :theme_id";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':theme_id', $themeId, PDO::PARAM_STR);
$stmt->execute();

$pageNum = (int)$stmt->fetchColumn() + 1;

// 이전, 다음 페이지 번호 계산
$prevPage = max(1, $selectedPageNum - 1);
$nextPage = min($pageNum, $selectedPageNum + 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ① textarea 값 세션에 저장
    $writing_text = trim($_POST['writing_text'] ?? '');
    if ($writing_text === '') {
        $error = 'テーマが入力されていません。';
    } else {
        // 세션에 writing_text를 저장
        $_SESSION['writing_text'] = $writing_text;
    }

    $approach = trim($_POST['approach'] ?? '');
    if ($approach === '') {
        $error = 'テーマが入力されていません。';
    } else {
        // 세션에 approach를 저장
        $_SESSION['approach'] = $approach;
    }

    $_SESSION['writing_text'] = trim($_POST['writing_text'] ?? '');
    $_SESSION['approach']     = trim($_POST['approach']     ?? '');

    // ② 빈 글이면 에러
    if ($_SESSION['writing_text'] === '') {
        $feedback = '英文が入力されていません。';
    } else {

        // ③ OpenAI Chat Completions 호출
        $sysPrompt = "
                      #役割
                      ユーザーは第二言語のライティングを学んでいる学習者です。
                      あなたは英語教師として、学習者が入力した英文から語彙・表現，文法事項，文構造に関するエラーを探してください。
                      そして、あなたは文章から改善すべき箇所を太字として提示してください。
                      ユーザーは、あなたが太字を入れた文章を参考にして、英文を推敲します。
                      ユーザーが推敲した英文を入力したら、その文章が正しく修正されているか確認し、修正されなかった箇所を太字として提示してください。

                      #条件
                      ・あなたが学習者に見せる英文の内容は学習者が入力した和文の内容のままにしてください。
                      ・学習者が入力した英文以外の英文は見せないでください。
                      ・日本語で案内してください。
                      ・太字の部分の再考以外、一切提案・修正点を提示しないでください。

                      #出力形式
                      太字の部分を再考してみましょう。
                      → I **goes** to **home** every day.
                      ";

        $userPrompt = "入力 学習者の和文: " . $_SESSION['theme_text'] . "学習者の英文： " . $_SESSION['writing_text'];

        $body = [
          'model' => 'gpt-4o-mini',
          'messages' => [
            ['role'=>'system', 'content'=>$sysPrompt],
            ['role'=>'user', 'content'=>$userPrompt]
          ],
          'temperature'=>0.2
        ];

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
          CURLOPT_HTTPHEADER      => [
              'Content-Type: application/json',
              'Authorization: Bearer '.$apiKey,
          ],
          CURLOPT_POST            => true,
          CURLOPT_POSTFIELDS      => json_encode($body, JSON_UNESCAPED_UNICODE),
          CURLOPT_RETURNTRANSFER  => true,
          CURLOPT_TIMEOUT         => 30,
        ]);

        // API로부터 apiResponse를 받아 feedback 생성
        $apiResponse = curl_exec($ch);

        if ($apiResponse === false) {
            $feedback = 'APIリクエスト失敗: ' . curl_error($ch);
        } else {
            // ① JSON → PHP 배열
            $data = json_decode($apiResponse, true);
        
            // ② 파싱 성공 & 원하는 필드 존재 여부 확인
            if (json_last_error() === JSON_ERROR_NONE &&
                isset($data['choices'][0]['message']['content'])) {
        
                // ③ content만 추출하여 $feedback 갱신
                $feedback = $data['choices'][0]['message']['content'];
        
            } else {
                $feedback = 'フィードバックの解析に失敗しました。応答: ' . $apiResponse;
            }
        }
      
        // $feedback을 세션에 저장
        $_SESSION['feedback'] = $feedback;

        // ④ DB에 textarea 값 저장하기
        $pdo = new PDO(DSN, DB_USER, DB_PASSWORD,
                           [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        
        $sql  = 'INSERT INTO l2_writing (theme_id, writing_text, approach, feedback, created_at)
        VALUES (:theme_id, :writing_text, :approach, :feedback, NOW())';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':theme_id', $_SESSION['theme_id'], PDO::PARAM_INT);
        $stmt->bindValue(':writing_text', $writing_text, PDO::PARAM_STR);
        $stmt->bindValue(':approach', $approach, PDO::PARAM_STR);
        $stmt->bindValue(':feedback', $feedback, PDO::PARAM_STR);
        $stmt->execute();

        // ❶ 현재 테마 ID 확보
        $themeId = $_SESSION['theme_id'] ?? 'default';   // fallback 가능

        // ❷ 아직 배열이 없다면 초기화
        if (!isset($_SESSION['fb_history'][$themeId])) {
            $_SESSION['fb_history'][$themeId] = [];
        }

        // ❸ 히스토리 푸시
        $_SESSION['fb_history'][$themeId][] = [
          'user' => $writing_text,
          'ai'   => $feedback
        ];

        header('Location: writing.php?theme_id=' . $themeId . '&page=' . $selectedPageNum);  // PRG 패턴
        exit;
    }
}
/* ② 페이지 표시 파트에서 history 렌더링 */
$history = $_SESSION['fb_history'][$themeId] ?? [];   // 해당 테마의 이력만 가져옴
?>


<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
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
      /* max-width: 1000px;
      width: 80%; */
    }
    
    .header {
      background-color: black;
      color: white;
      padding: 10px 20px;
      font-size: 18px;
    }

    .contents-wrapper {
      width: 90%;
      align-items: center;
      margin: 0 auto;
      /* justify-content: center; */
      /* justify-items: center; */
    }

    .top-row {
      display: flex;
      flex-direction: column;
      align-items: left;
      padding: 20px;
      gap: 10px;
      margin: 0 auto;
      margin-top: 20px;
      justify-content: center;
      background-color: #fff;
      /* width: 100%; */
      border: solid #333 1px;
      border-radius: 10px;
    }

    .topic-display {
      display: flex;
      align-items: center;
      min-height: 20px;
      padding: 10px;
      font-size: 16px;
      border: 1px solid #333;
      border-radius: 10px;
      width: 100%;
      box-sizing: border-box;
      /* white-space: pre-wrap;     개행 유지 + 폭 넘으면 자동 줄바꿈 */
      /* overflow-wrap: break-word; 너무 긴 단어 줄바꿈 */
      background-color:rgb(245, 245, 245); /* (선택) 시각적 구분 */
    }

    .main-area {
      display: flex;
      padding: 20px 0px; 
      gap: 1%;
      height: 63vh;
      width: 100%;
      margin: 0 auto;
      align-items: stretch;
    }

    .user-section,
    .feedback-section,
    .user-writing,
    .user-revision {
      border: 1px solid #333;
      border-radius: 10px;
      padding: 15px;
      background-color: white;
      display: flex;
      flex: 1;
      flex-direction: column;
    }

    .feedback-section {
      flex: 1;
      order: 2;
    }

    .feedback-box{
      flex: 1;               /* 원하는 높이 */
      padding: 10px;
      overflow-y: auto;            /* 내용이 넘치면 세로 스크롤 */
      background:#fff;
      /* white-space: pre-wrap;       개행 유지 + 폭 넘으면 줄바꿈 */
      overflow-wrap: break-word;
      background-color:rgb(245, 245, 245); /* (선택) 시각적 구분 */
      border: solid #333 1px;
      border-radius: 10px;
    }

    .feedback-count-wrapper {
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 16px 0;           /* 위아래 간격 */
      gap: 10px;                /* 텍스트와 선 사이 여백 */
    }

    .feedback-count-text {
      white-space: nowrap;
      font-weight: bold;
      color: #333;
    }

    .line {
      flex-grow: 1;
      height: 1px;
      background-color: #ccc;
    }

    /* 사용자/AI 메시지 구분용 예시 */
    .msg-user{
      color: black; 
      padding: 10px; 
      border-radius: 30px; 
      background-color: rgb(243, 198, 35); 
      width: fit-content;
      max-width: 80%;
      margin-top: 20px;
      margin-bottom: 20px; 
      margin-left: auto; 
      margin-right: 0; 
      line-height: 1.6;
      font-size: 18px;
    }
    .msg-ai  {
      color: #fff; 
      padding: 10px; 
      border-radius: 30px; 
      background-color: rgb(235, 131, 23); 
      width: fit-content;
      font-weight: 400; 
      margin-top: 20px;
      margin-bottom: 20px;
      line-height: 1.6;
      font-size: 18px;
    }
    .msg-ai  strong{color:rgb(16, 55, 92);}  /* 변환한 bold */

    .user-section {
      gap: 10px;
      order: 1;
    }

    .writing-form {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 3px;
      height: 100%;
    }

    .title-row {
      display: flex;
      align-items: center;     /* 세로 정렬 맞춤 */
      gap: 5px;               /* 요소 간 간격 (선택) */
    }

    .section-title {
      color: black;
      padding: 5px;
      font-weight: 550;
      font-size: 20px;
    }

    textarea {
      flex: 1;
      height: 150px;
      padding: 10px;
      font-size: 18px;
      resize: vertical;
      border: solid #333 1px;
      border-radius: 10px;
      outline: none;
    }

    .button-row {
      display: flex;
      justify-content: space-between;
      gap: 10px;
      margin-top: 20px;
    }

    .action-button {
      padding: 10px;
      font-size: 16px;
      color: white;
      border: none;
      border-radius: 10px;
      cursor: pointer;
    }

    .feedback-button {
      /* flex: 1; */
      height: 40px;
      width: fit-content;
      margin: auto;
      margin-top: 10px;
      align-items: center;
      padding: 10px;
      font-size: 16px;
      font-weight: 550;
      background-color: rgb(235, 131, 23);
      color: white;
      border: none;
      border-radius: 10px;
      cursor: pointer;
    }

    .action-button:hover {
      background-color: #444;
    }

    .necessary {
      background-color: rgb(243, 198, 35);
      border-radius: 20px;
      width: fit-content;
      padding: 6px;
      font-weight: 550;
    }

    .pagination {
      margin: auto;
    }

    .pagination input {
      height: 30px;
      width: 30px;
      font-size: 20px;
    }

    .pagination button {
      background-color: white;
      border: none;
      cursor: pointer;
    }
  </style>

</head>
<body>
  <?php include __DIR__.'/header.php'; ?>

  <div class="contents-wrapper">

    <div class="button-row">
      <button type="button" class="action-button" onclick="document.location='theme.php'" style="background-color: #6c757d; box-shadow: 3px 2px 2px grey; font-size:18px;">◀︎ 前に戻る</button>
      <button type="button" onclick="document.location='reflection.php'" class="action-button"
      style="background-color: rgb(16, 55, 92); box-shadow: 3px 2px 2px grey; font-size:18px;">振り返りに進む ▶︎</button>
    </div>

    <div class="top-row">
      <div class="language-select">
        <label style="font-weight: 550; font-size: 20px;">英語の文章にしたい内容</label>
      </div>
      <div class="topic-display" style="font-size: 18px;">
        <?php
          // 세션에 값이 있으면 표시, 없으면 안내문
          if (!empty($_SESSION['theme_text'])) {
              echo nl2br(
                  htmlspecialchars(trim($_SESSION['theme_text']), ENT_QUOTES, 'UTF-8')
              );
          } else {
              echo '（テーマがまだ設定されていません）';
          }
        ?>
      </div>
    </div>

    <div class="main-area">
      <div class="feedback-section">
        <div class="section-title">
          ③ 生成AIからのフィードバック
        </div>

        <div id="feedback-box" class="feedback-box">
          <?php foreach ($history as $i => $pair): ?>
            <div class="feedback-entry">
            <div class="feedback-count-wrapper">
              <span class="line"></span>
              <span class="feedback-count-text"><?= trim(($i + 1) . '回目のフィードバック') ?></span>
              <span class="line"></span>
            </div>

              <p class="msg-user"><?= nl2br(htmlspecialchars($pair['user'], ENT_QUOTES, 'UTF-8')) ?></p>

              <?php
                // **bold** → <strong>bold</strong> 변환
                $ai = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $pair['ai']);
              ?>
              <p class="msg-ai"><?= nl2br($ai) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
        
      </div>

      <div class="user-section">
        <form method="POST" action="writing.php" class="writing-form" id="writingForm">
          <div class="title-row">
            <div class="section-title">① 英語の文章</div>
            <div class="necessary">必須</div>
          </div>
          <textarea class="writing-textarea" name="writing_text" placeholder="英語の文章を作成してください。" style="font-size: 18px;" required></textarea>
          <div class="title-row" style="margin-top: 10px;">
            <div class="section-title">② 工夫したこと</div>
            <div class="necessary">必須</div>
          </div>
          <textarea class="writing-textarea" name="approach" placeholder="英語の文章を作成するときに、工夫したことを書いてください。" style="font-size: 18px;" required></textarea>
          <button type="submit" class="feedback-button" form="writingForm" style="box-shadow: 3px 2px 2px grey;">
            フィードバックをもらう
            <!-- <img src="img/chatbot.png" style="height: 30px;"/> -->
          </button>
        </form>

      </div>
    </div>
  </div>
  
</body>
</html>

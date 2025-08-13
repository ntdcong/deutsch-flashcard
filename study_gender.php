<?php
// study_gender.php — Quiz Giống (der/die/das) | UI & flow giống study_quiz.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require 'db.php';

// Cho phép public token hoặc login
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

// Xác định action từ client (AJAX)
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ---- Helpers ----
function normalize($str) {
    return mb_strtolower(trim(preg_replace('/\s+/', ' ', $str)));
}
function strip_article($word) {
    $word = trim(preg_replace('/\s+/', ' ', (string)$word));
    // Bỏ der|die|das ở đầu nếu có
    return trim(preg_replace('/^(der|die|das)\s+/i', '', $word));
}
// Lấy từ tiếp theo, ưu tiên review_queue
function getNextVocabIndex(&$quiz_data, &$vocabs) {
    if (!empty($quiz_data['review_queue'])) {
        return array_shift($quiz_data['review_queue']);
    }
    $quiz_data['current_index'] = ($quiz_data['current_index'] + 1);
    if ($quiz_data['current_index'] >= count($vocabs)) {
        return false;
    }
    return $quiz_data['current_index'];
}

// --- Xử lý các yêu cầu AJAX ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['get_question', 'submit_answer', 'reset'], true)) {
    header('Content-Type: application/json; charset=utf-8');

    $token = $_POST['token'] ?? $_GET['token'] ?? '';
    $notebook_id = (int)($_POST['notebook_id'] ?? $_GET['notebook_id'] ?? 0);
    // Xác thực quyền truy cập
    if ($token !== '') {
        $stmt = $pdo->prepare('SELECT * FROM notebooks WHERE public_token = ? AND is_public = 1');
        $stmt->execute([$token]);
        $notebook = $stmt->fetch();
        if (!$notebook) { echo json_encode(['error' => 'Link không hợp lệ hoặc sổ tay không công khai!']); exit; }
        $notebook_id = (int)$notebook['id'];
    } else {
        if (!$user_id) { echo json_encode(['error' => 'Vui lòng đăng nhập.']); exit; }
        $stmt = $pdo->prepare('SELECT * FROM notebooks WHERE id=? AND user_id=?');
        $stmt->execute([$notebook_id, $user_id]);
        $notebook = $stmt->fetch();
        if (!$notebook) { echo json_encode(['error' => 'Không tìm thấy sổ tay hoặc bạn không có quyền truy cập!']); exit; }
    }
    $quiz_session_key = 'gender_quiz_' . $notebook_id;

    // --- GET QUESTION ---
    if ($action === 'get_question') {
        if (!isset($_SESSION[$quiz_session_key])) {
            // Lấy từ vựng có genus
            $stmt = $pdo->prepare("
                SELECT id, word, phonetic, genus
                FROM vocabularies
                WHERE notebook_id = ?
                  AND genus IS NOT NULL
                  AND TRIM(genus) <> ''
            ");
            $stmt->execute([$notebook_id]);
            $all = $stmt->fetchAll();

            if (!$all) {
                echo json_encode(['error' => 'Sổ tay chưa có danh từ có giống (genus).']);
                exit;
            }

            // Chuẩn hoá & lọc hợp lệ (der/die/das)
            $vocabs = [];
            foreach ($all as $r) {
                $g = normalize($r['genus']);
                if (!in_array($g, ['der','die','das'], true)) continue;
                $vocabs[] = [
                    'id'        => (int)$r['id'],
                    'word'      => $r['word'],
                    'phonetic'  => $r['phonetic'],
                    'genus'     => $g,
                    'display'   => strip_article($r['word']),
                ];
            }
            if (!$vocabs) {
                echo json_encode(['error' => 'Không có hàng nào có genus hợp lệ (der/die/das).']);
                exit;
            }

            // Trộn ngẫu nhiên một lần
            shuffle($vocabs);

            // Khởi tạo session quiz
            $_SESSION[$quiz_session_key] = [
                'vocabs'         => array_values($vocabs),
                'current_index'  => 0, // bắt đầu từ 0
                'stats'          => ['correct' => 0, 'incorrect' => 0, 'streak' => 0, 'max_streak' => 0],
                'review_queue'   => [],
                'answered_vocabs'=> [],
            ];
        }

        $quiz = &$_SESSION[$quiz_session_key];
        $vocabs = $quiz['vocabs'];
        $i = $quiz['current_index'];
        $stats = $quiz['stats'];

        // Hết câu & không còn ôn lại
        if ($i >= count($vocabs) && empty($quiz['review_queue'])) {
            echo json_encode([
                'success' => true,
                'quiz_finished' => true,
                'stats' => $stats,
                'total_questions' => count($vocabs),
                'current_index' => count($vocabs),
                'progress_percent' => 100
            ]);
            exit;
        }

        $v = $vocabs[$i];
        $total = count($vocabs);
        $progress = ($i / $total) * 100;
        if ($progress > 100) $progress = 100;

        // mode giữ tên 'choice' để reuse UI (render radio). choices = 3 mạo từ
        $choices = ['der','die','das'];
        shuffle($choices);

        echo json_encode([
            'success' => true,
            'vocab' => [
                'id' => $v['id'],
                'word' => $v['display'],        // HIỂN THỊ từ đã bỏ mạo từ
                'phonetic' => $v['phonetic'],
                'meaning' => ''                 // để trống (UI tái sử dụng không cần)
            ],
            'mode' => 'choice',
            'choices' => $choices,
            'stats' => $stats,
            'total_questions' => $total,
            'current_index' => $i,
            'progress_percent' => $progress
        ]);
        exit;
    }

    // --- SUBMIT ANSWER ---
    if ($action === 'submit_answer') {
        if (!isset($_SESSION[$quiz_session_key])) {
            echo json_encode(['error' => 'Dữ liệu quiz không tồn tại.']);
            exit;
        }
        $quiz = &$_SESSION[$quiz_session_key];
        $vocabs = $quiz['vocabs'];
        $i = $quiz['current_index'];
        $stats = &$quiz['stats'];
        $review = &$quiz['review_queue'];
        $answered_vocabs = &$quiz['answered_vocabs'];

        $user_answer = normalize($_POST['user_answer'] ?? '');
        $v = $vocabs[$i];
        $correct_answer = $v['genus'];

        $is_correct = ($user_answer === $correct_answer);

        // Stats
        if ($is_correct) {
            $stats['correct']++;
            $stats['streak']++;
            if ($stats['streak'] > $stats['max_streak']) $stats['max_streak'] = $stats['streak'];
        } else {
            $stats['incorrect']++;
            $stats['streak'] = 0;
        }

        // Ghi nhớ đã trả lời gần đây
        $answered_vocabs[] = $i;
        if (count($answered_vocabs) > 10) array_shift($answered_vocabs);

        // Dọn khỏi review nếu có (trường hợp câu lấy từ queue)
        $review = array_values(array_filter($review, fn($idx) => $idx != $i));

        // Thêm vào queue nếu sai
        if (!$is_correct && !in_array($i, $review, true)) $review[] = $i;

        // Lấy chỉ số tiếp theo
        $next_i = getNextVocabIndex($quiz, $vocabs);
        if ($next_i === false) {
            echo json_encode([
                'success' => true,
                'quiz_finished' => true,
                'stats' => $stats,
                'total_questions' => count($vocabs),
                'current_index' => count($vocabs),
                'progress_percent' => 100
            ]);
            exit;
        }
        $quiz['current_index'] = $next_i;

        $next = $vocabs[$next_i];
        $total = count($vocabs);
        $progress = ($next_i / $total) * 100;
        if ($progress > 100) $progress = 100;

        $next_choices = ['der','die','das'];
        shuffle($next_choices);

        // Có phải câu ôn lại không?
        $is_review = !empty($quiz['review_queue']) && (reset($quiz['review_queue']) == $next_i || in_array($next_i, $quiz['review_queue'], true));

        echo json_encode([
            'success' => true,
            'result' => [
                'is_correct' => $is_correct,
                'user_answer' => $user_answer,
                'correct_answer' => $correct_answer,
                'is_review' => $is_review
            ],
            'next_vocab' => [
                'id' => $next['id'],
                'word' => $next['display'],
                'phonetic' => $next['phonetic'],
                'meaning' => ''
            ],
            'next_mode' => 'choice',
            'next_choices' => $next_choices,
            'stats' => $stats,
            'total_questions' => $total,
            'current_index' => $next_i,
            'progress_percent' => $progress
        ]);
        exit;
    }

    // --- RESET ---
    if ($action === 'reset') {
        $notebook_id = (int)($_POST['notebook_id'] ?? $_GET['notebook_id'] ?? 0);
        $quiz_session_key = 'gender_quiz_' . $notebook_id;
        unset($_SESSION[$quiz_session_key]);
        echo json_encode(['success' => true, 'message' => 'Quiz đã được đặt lại.']);
        exit;
    }

    echo json_encode(['error' => 'Hành động không hợp lệ.']);
    exit;
}

// --- Render trang (GET) ---
$token = $_GET['token'] ?? '';
if ($token !== '') {
    $stmt = $pdo->prepare('SELECT * FROM notebooks WHERE public_token = ? AND is_public = 1');
    $stmt->execute([$token]);
    $notebook = $stmt->fetch();
    if (!$notebook) { die('Link không hợp lệ hoặc sổ tay không công khai!'); }
    $notebook_id = (int)$notebook['id'];
} else {
    if (!$user_id) { header('Location: login.php'); exit; }
    $notebook_id = (int)($_GET['notebook_id'] ?? 0);
    $stmt = $pdo->prepare('SELECT * FROM notebooks WHERE id=? AND user_id=?');
    $stmt->execute([$notebook_id, $user_id]);
    $notebook = $stmt->fetch();
    if (!$notebook) { die('Không tìm thấy sổ tay hoặc bạn không có quyền truy cập!'); }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Quiz Giống (der/die/das)</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --card-bg: #ffffff;
        --card-radius: 20px;
        --card-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        --transition-speed: 0.3s;
    }
    *{box-sizing:border-box}
    body{background:var(--primary-gradient);font-family:'Segoe UI',system-ui,-apple-system,sans-serif;margin:0;min-height:100vh;display:flex;flex-direction:column}
    .navbar{background-color:rgba(255,255,255,.95)!important;box-shadow:0 2px 20px rgba(0,0,0,.1);backdrop-filter:blur(10px);padding:12px 0;flex-shrink:0}
    .navbar-brand{font-weight:600;color:#4a5568!important}
    .quiz-container{flex:1;display:flex;flex-direction:column;padding:20px 15px;width:100%}
    .header-section{text-align:center;margin-bottom:25px;color:white;width:100%}
    .header-section h1{font-size:1.8rem;font-weight:700;margin-bottom:10px;text-shadow:0 2px 4px rgba(0,0,0,.1)}
    .progress-container{background:rgba(255,255,255,.2);border-radius:50px;padding:3px;margin:0 auto 15px;max-width:300px}
    .progress-bar{height:12px;background:white;border-radius:50px;transition:width .3s ease}
    .stats-container{display:flex;justify-content:center;gap:15px;margin-bottom:20px;flex-wrap:wrap}
    .stat-badge{background:rgba(255,255,255,.2);color:white;padding:8px 16px;border-radius:50px;font-size:.9rem;font-weight:500}
    .quiz-card{border-radius:var(--card-radius);box-shadow:var(--card-shadow);background:var(--card-bg);border:none;transition:transform var(--transition-speed) ease, box-shadow var(--transition-speed) ease;max-width:800px;width:100%;margin:0 auto;position:relative;overflow:hidden}
    .quiz-card:hover{transform:translateY(-5px);box-shadow:0 20px 40px rgba(0,0,0,.15)}
    .word-display{font-size:2.2rem;font-weight:700;color:#2d3748;margin-bottom:10px;text-align:center}
    .phonetic{font-size:1.1rem;color:#718096;margin-bottom:20px;text-align:center}
    .form-check{margin-bottom:12px;padding:12px 15px;border:2px solid #e2e8f0;border-radius:12px;transition:all var(--transition-speed) ease;cursor:pointer}
    .form-check:hover{border-color:#cbd5e0;background-color:#f7fafc}
    .form-check-input:checked + .form-check-label{color:#2d3748;font-weight:500}
    .form-check-input:checked{background-color:#48bb78;border-color:#48bb78}
    .btn-quiz{border-radius:12px;padding:12px 20px;font-weight:600;font-size:1.1rem;transition:all var(--transition-speed) ease;border:none;width:100%;margin-bottom:15px}
    .btn-quiz:hover{transform:translateY(-2px);box-shadow:0 6px 15px rgba(0,0,0,.1)}
    .btn-check{background:linear-gradient(135deg,#43e97b 0%,#38f9d7 100%);color:#fff}
    .btn-next-main{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff}
    .result-alert{border-radius:12px;font-weight:500;padding:20px;margin:20px 0;text-align:center}
    @media (min-width:768px){
        .quiz-container{flex-direction:row;align-items:flex-start;gap:20px;padding:20px}
        .sidebar{width:250px;background:rgba(255,255,255,.1);border-radius:15px;padding:20px;backdrop-filter:blur(10px);box-shadow:0 5px 15px rgba(0,0,0,.05);height:fit-content}
        .main-content{flex:1;display:flex;flex-direction:column;align-items:center}
        .quiz-card{max-width:600px}
        .stats-container{flex-direction:column;align-items:flex-start;gap:10px}
        .stat-badge{width:100%;text-align:center}
        .navigation-buttons{display:flex;flex-direction:column;gap:10px;margin-top:20px}
        .nav-btn{width:100%;text-align:left}
    }
    @media (max-width:767px){
        .header-section h1{font-size:1.6rem}
        .word-display{font-size:1.8rem}
        .phonetic{font-size:1rem}
        .stat-badge{padding:6px 12px;font-size:.8rem}
        .form-check{padding:10px 12px;margin-bottom:10px}
    }
</style>
</head>
<body>
<nav class="navbar navbar-light shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="<?= isset($token) && $token !== '' ? 'public_notebook.php?token=' . urlencode($token) : 'dashboard.php' ?>">
            <i class="bi bi-arrow-left"></i> Quay lại
        </a>
        <span class="navbar-text text-truncate" style="max-width: 200px;">
            <?= htmlspecialchars($notebook['title']) ?>
        </span>
        <div class="d-flex">
            <button id="reset-btn" class="btn btn-outline-secondary btn-sm me-2">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>
    </div>
</nav>

<div class="quiz-container">
    <!-- Sidebar desktop -->
    <div class="sidebar d-none d-md-block">
        <h5 class="text-white mb-3">Thống kê</h5>
        <div class="stats-container">
            <div class="stat-badge"><i class="bi bi-collection"></i> <span id="total-count">0</span> từ</div>
            <div class="stat-badge"><i class="bi bi-check-circle"></i> <span id="correct-count">0</span> đúng</div>
            <div class="stat-badge"><i class="bi bi-lightning"></i> <span id="streak-count">0</span> chuỗi</div>
        </div>
        <h5 class="text-white mt-4 mb-3">Điều hướng</h5>
        <div class="navigation-buttons">
            <a href="dashboard.php" class="btn btn-light nav-btn"><i class="bi bi-journals"></i> Về sổ tay</a>
            <button id="reset-btn-sidebar" class="btn btn-warning nav-btn"><i class="bi bi-arrow-clockwise"></i> Làm lại</button>
            <a href="logout.php" class="btn btn-danger nav-btn"><i class="bi bi-box-arrow-right"></i> Đăng xuất</a>
        </div>
    </div>

    <!-- Nội dung chính -->
    <div class="main-content">
        <div class="header-section d-md-none">
            <h1>🧠 Quiz Giống (der/die/das)</h1>
            <div class="progress-container">
                <div class="progress-bar" id="progress-bar" style="width: 0%"></div>
            </div>
            <div class="stats-container d-md-none">
                <div class="stat-badge"><i class="bi bi-collection"></i> <span id="mobile-total-count">0</span> từ</div>
                <div class="stat-badge"><i class="bi bi-check-circle"></i> <span id="mobile-correct-count">0</span> đúng</div>
                <div class="stat-badge"><i class="bi bi-lightning"></i> <span id="mobile-streak-count">0</span> chuỗi</div>
            </div>
        </div>

        <div class="quiz-card p-4" id="quiz-area"><!-- nội dung câu hỏi --></div>
        <div class="p-3 text-center mt-4 mb-4 text-white">
            <i class="bi bi-info-circle"></i>
            Câu hỏi được xáo trộn, chỉ lặp lại các từ trả lời sai.
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const notebookId = <?= json_encode($notebook_id) ?>;
    const publicToken = <?= json_encode($token ?? '') ?>;
    if (!notebookId) { alert('Thiếu thông tin sổ tay.'); return; }

    const quizArea = document.getElementById('quiz-area');
    const resetBtn = document.getElementById('reset-btn');
    const resetBtnSidebar = document.getElementById('reset-btn-sidebar');

    function updateStats(stats, total) {
        document.getElementById('total-count').textContent = total;
        document.getElementById('correct-count').textContent = stats.correct;
        document.getElementById('streak-count').textContent = stats.streak;

        document.getElementById('mobile-total-count').textContent = total;
        document.getElementById('mobile-correct-count').textContent = stats.correct;
        document.getElementById('mobile-streak-count').textContent = stats.streak;
    }
    function updateProgress(percent) {
        document.getElementById('progress-bar').style.width = percent + '%';
    }

    function showQuestion(q) {
        if (q.quiz_finished) { showQuizFinished(q); return; }
        let html = `
            <div class="text-center mb-4">
                <div class="word-display">${q.vocab.word}</div>
                ${q.vocab.phonetic ? `<div class="phonetic">[${q.vocab.phonetic}]</div>` : ''}
            </div>
            <form id="quiz-form" class="mb-3">
                <input type="hidden" name="vocab_id" value="${q.vocab.id}">
        `;
        // Luôn 'choice' với 3 mạo từ
        q.choices.forEach((choice, i) => {
            html += `
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="answer" id="choice_${i}" value="${choice}" required>
                    <label class="form-check-label h5 fw-normal w-100" for="choice_${i}" style="cursor:pointer">${choice}</label>
                </div>`;
        });
        html += `
                <button type="submit" class="btn btn-quiz btn-check">
                    <i class="bi bi-check-circle"></i> Kiểm tra
                </button>
            </form>`;
        quizArea.innerHTML = html;
        updateStats(q.stats, q.total_questions);
        updateProgress(q.progress_percent);

        document.getElementById('quiz-form').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            const userAnswer = formData.get('answer');
            submitAnswer(userAnswer);
        });
        document.querySelectorAll('.form-check').forEach(function(fc){
            fc.addEventListener('click', function(e){
                if (e.target.tagName === 'INPUT') return;
                const radio = fc.querySelector('input[type="radio"]');
                if (radio && !radio.disabled) {
                    radio.checked = true;
                    document.getElementById('quiz-form').dispatchEvent(new Event('submit'));
                }
            });
        });
    }

    function showResult(r) {
        if (r.quiz_finished) { showQuizFinished(r); return; }
        let resultHtml = `
            <div class="mt-2">
                <div class="result-alert ${r.result.is_correct ? 'alert-success' : 'alert-danger'}">
                    ${r.result.is_correct
                        ? `<div class="h4 mt-2 mb-0">CHÍNH XÁC!</div>`
                        : `<div class="h4 mt-3 mb-1">SAI RỒI!</div>
                           <div class="fw-normal">Đáp án đúng: <b>${r.result.correct_answer}</b></div>
                           ${r.result.user_answer ? `<div class="fw-normal mt-1">Bạn trả lời: <span class="text-decoration-line-through">${r.result.user_answer}</span></div>` : ''}`
                    }
                </div>
                <div class="text-center">
                    <button id="next-btn" class="btn btn-quiz btn-next-main">
                        ${r.result.is_review ? 'Ôn lại <i class="bi bi-arrow-counterclockwise"></i>' : 'Câu tiếp <i class="bi bi-arrow-right-circle"></i>'}
                    </button>
                </div>
            </div>`;
        quizArea.innerHTML = resultHtml;
        updateStats(r.stats, r.total_questions);
        updateProgress(r.progress_percent);

        if (r.result.is_correct) document.getElementById('audio-correct').play();
        else document.getElementById('audio-wrong').play();

        document.getElementById('next-btn').addEventListener('click', loadNextQuestion);
    }

    function showQuizFinished(d) {
        let html = `
            <div class="quiz-finished text-white">
                <h2>🎉 Hoàn thành Quiz Giống</h2>
                <ul class="list-unstyled">
                    <li>Tổng số từ: <strong>${d.total_questions}</strong></li>
                    <li>Đúng: <strong class="text-success">${d.stats.correct}</strong></li>
                    <li>Sai: <strong class="text-danger">${d.stats.incorrect}</strong></li>
                    <li>Chuỗi đúng dài nhất: <strong>${d.stats.max_streak}</strong></li>
                </ul>
                <button id="restart-btn" class="btn btn-primary btn-lg">Làm lại từ đầu</button>
            </div>`;
        quizArea.innerHTML = html;
        updateStats(d.stats, d.total_questions);
        updateProgress(d.progress_percent);
        document.getElementById('audio-finish').play();
        document.getElementById('restart-btn').addEventListener('click', resetQuiz);
    }

    function submitAnswer(userAnswer) {
        fetch('study_gender.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=submit_answer&notebook_id=${notebookId}&user_answer=${encodeURIComponent(userAnswer)}${publicToken ? `&token=${encodeURIComponent(publicToken)}` : ''}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.error) { alert('Lỗi: ' + data.error); return; }
            if (data.success) showResult(data);
        })
        .catch(console.error);
    }

    function loadNextQuestion() {
        fetch('study_gender.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=get_question&notebook_id=${notebookId}${publicToken ? `&token=${encodeURIComponent(publicToken)}` : ''}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.error) { alert('Lỗi: ' + data.error); return; }
            if (data.success) showQuestion(data);
        })
        .catch(console.error);
    }

    function loadInitialQuestion() {
        fetch('study_gender.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=get_question&notebook_id=${notebookId}${publicToken ? `&token=${encodeURIComponent(publicToken)}` : ''}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.error) { alert('Lỗi: ' + data.error); return; }
            if (data.success) showQuestion(data);
        })
        .catch(console.error);
    }

    function resetQuiz() {
        fetch('study_gender.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=reset&notebook_id=${notebookId}${publicToken ? `&token=${encodeURIComponent(publicToken)}` : ''}`
        })
        .then(r => r.json())
        .then(data => { if (data.success) loadInitialQuestion(); })
        .catch(console.error);
    }

    // Gắn sự kiện
    resetBtn.addEventListener('click', resetQuiz);
    resetBtnSidebar.addEventListener('click', resetQuiz);

    // Tải câu đầu
    loadInitialQuestion();

    // Phím tắt: Space/Enter => submit, ArrowRight => Next
    document.addEventListener('keydown', function(e){
        const form = document.getElementById('quiz-form');
        const nextBtn = document.getElementById('next-btn');
        if (form && (e.code === 'Space' || e.code === 'Enter')) {
            e.preventDefault();
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) form.dispatchEvent(new Event('submit'));
        }
        if (nextBtn && e.code === 'ArrowRight') nextBtn.click();
    });
});
</script>
<audio id="audio-correct" src="assets/correct.mp3" preload="auto"></audio>
<audio id="audio-wrong" src="assets/wrong.mp3" preload="auto"></audio>
<audio id="audio-finish" src="assets/finish.mp3" preload="auto"></audio>
</body>
</html>

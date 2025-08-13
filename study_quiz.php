<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require 'db.php';

// Cho phép truy cập công khai bằng token hoặc truy cập riêng tư khi đã đăng nhập
$user_id = $_SESSION['user_id'] ?? null;

// Xác định hành động từ client (AJAX)
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// --- Xử lý các yêu cầu AJAX ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['get_question', 'submit_answer', 'reset'])) {
    header('Content-Type: application/json');

    $token = $_POST['token'] ?? $_GET['token'] ?? '';
    $notebook_id = (int)($_POST['notebook_id'] ?? $_GET['notebook_id'] ?? 0);

    // Xác thực quyền truy cập: token công khai hoặc chủ sở hữu
    if ($token !== '') {
        $stmt = $pdo->prepare('SELECT * FROM notebooks WHERE public_token = ? AND is_public = 1');
        $stmt->execute([$token]);
        $notebook = $stmt->fetch();
        if (!$notebook) {
            echo json_encode(['error' => 'Link không hợp lệ hoặc sổ tay không công khai.']);
            exit;
        }
        $notebook_id = (int)$notebook['id'];
    } else {
        if (!$user_id) {
            echo json_encode(['error' => 'Vui lòng đăng nhập.']);
            exit;
        }
        $stmt = $pdo->prepare('SELECT * FROM notebooks WHERE id=? AND user_id=?');
        $stmt->execute([$notebook_id, $user_id]);
        $notebook = $stmt->fetch();
        if (!$notebook) {
            echo json_encode(['error' => 'Không tìm thấy sổ tay hoặc bạn không có quyền truy cập!']);
            exit;
        }
    }

    $quiz_session_key = 'quiz_data_' . $notebook_id;

    // Hàm chuẩn hóa chuỗi để so sánh
    function normalize($str) {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $str)));
    }

    // Hàm lấy từ tiếp theo
    // Ưu tiên từ trong hàng đợi ôn lại (từ sai)
    // Nếu hàng đợi trống, tăng chỉ số tuần tự
    function getNextVocabIndex(&$quiz_data, &$vocabs) {
        // Nếu có từ trong hàng đợi ôn lại (từ trả lời sai), lấy từ đó
        if (!empty($quiz_data['review_queue'])) {
            // Lấy và trả về phần tử đầu tiên từ hàng đợi
            return array_shift($quiz_data['review_queue']);
        }
        // Ngược lại, tăng chỉ số hiện tại trong danh sách tuần tự
        $quiz_data['current_index'] = ($quiz_data['current_index'] + 1);
        // Nếu vượt quá tổng số từ, DỪNG quiz (trả về false)
        if ($quiz_data['current_index'] >= count($vocabs)) {
            return false; // Dừng quiz, không quay lại từ đầu nữa
        }
        return $quiz_data['current_index'];
    }

    // --- Hành động: Lấy câu hỏi ---
    if ($action === 'get_question') {
        // Khởi tạo hoặc lấy lại dữ liệu quiz từ session
        if (!isset($_SESSION[$quiz_session_key])) {
            // Lấy tất cả từ vựng trong sổ tay
            $stmt = $pdo->prepare('SELECT * FROM vocabularies WHERE notebook_id=? ORDER BY id ASC'); // Lấy theo thứ tự ID để dễ quản lý nếu cần
            $stmt->execute([$notebook_id]);
            $all_vocabs = $stmt->fetchAll();
            if (!$all_vocabs) {
                echo json_encode(['error' => 'Sổ tay chưa có từ vựng!']);
                exit;
            }
            // TRỘN NGẪU NHIÊN MỘT LẦN khi khởi tạo
            shuffle($all_vocabs);
            // Khởi tạo dữ liệu quiz trong session
            $_SESSION[$quiz_session_key] = [
                'vocabs' => $all_vocabs, // Danh sách từ vựng (đã trộn một lần)
                'current_index' => 0,    // Vị trí hiện tại trong danh sách (tuần tự)
                'stats' => ['correct' => 0, 'incorrect' => 0, 'streak' => 0, 'max_streak' => 0],
                'review_queue' => [],    // Hàng đợi các từ cần ôn lại (chỉ từ trả lời SAI)
                'answered_vocabs' => []  // Danh sách các từ đã được trả lời (để tránh chọn lại ngay trong đáp án sai - có thể giữ lại nếu cần)
            ];
        }

        $quiz_data = &$_SESSION[$quiz_session_key];
        $vocabs = $quiz_data['vocabs'];
        $current_index = $quiz_data['current_index'];
        $stats = $quiz_data['stats'];

        // Nếu đã hết từ và không còn từ sai để ôn lại, trả về quiz_finished
        if ($current_index >= count($vocabs) && empty($quiz_data['review_queue'])) {
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

        // Xác định chế độ câu hỏi
        $mode = (count($vocabs) < 4) ? 'input' : 'choice';

        // Tạo các lựa chọn sai
        $choices = [];
        $vocab = $vocabs[$current_index];
        if ($mode === 'choice') {
            $choices[] = $vocab['meaning']; // Thêm đáp án đúng
            // Danh sách các từ vựng có thể dùng để tạo lựa chọn sai (trừ từ đang hỏi và những từ đã trả lời gần đây)
            $potential_wrong_answers = array_filter($vocabs, function($v) use ($vocab, $quiz_data, $vocabs) {
                // Kiểm tra chỉ số thay vì ID để phù hợp với mảng $vocabs
                $index_in_vocabs = array_search($v, $vocabs);
                return $v['id'] != $vocab['id'] && !in_array($index_in_vocabs, $quiz_data['answered_vocabs']);
            });
            // Nếu không đủ từ không trùng, dùng lại danh sách đầy đủ (trừ từ đang hỏi)
            if (count($potential_wrong_answers) < 3) {
                $potential_wrong_answers = array_filter($vocabs, function($v) use ($vocab) {
                    return $v['id'] != $vocab['id'];
                });
            }
            // Chuyển mảng để dễ xử lý chỉ số
            $potential_wrong_answers = array_values($potential_wrong_answers);
            // Chọn ngẫu nhiên 3 từ sai từ danh sách tiềm năng
            $selected_wrong = [];
            while (count($selected_wrong) < 3 && !empty($potential_wrong_answers)) {
                $rand_key = array_rand($potential_wrong_answers);
                $selected_wrong[] = $potential_wrong_answers[$rand_key]['meaning'];
                // Xóa phần tử đã chọn để tránh trùng lặp
                unset($potential_wrong_answers[$rand_key]);
                // Đặt lại chỉ số mảng
                $potential_wrong_answers = array_values($potential_wrong_answers);
            }
            $choices = array_merge($choices, $selected_wrong);
            shuffle($choices); // Trộn các lựa chọn
        }

        $total_questions = count($vocabs);
        // Tiến trình dựa trên chỉ số hiện tại trong danh sách tuần tự
        $progress_percent = ($current_index / $total_questions) * 100;
        if ($progress_percent > 100) $progress_percent = 100; // Đảm bảo không vượt quá 100%

        echo json_encode([
            'success' => true,
            'vocab' => [
                'id' => $vocab['id'],
                'word' => $vocab['word'],
                'phonetic' => $vocab['phonetic'],
                'meaning' => $vocab['meaning']
            ],
            'mode' => $mode,
            'choices' => $choices,
            'stats' => $stats,
            'total_questions' => $total_questions,
            'current_index' => $current_index,
            'progress_percent' => $progress_percent
        ]);
        exit;
    }

    // --- Hành động: Gửi câu trả lời ---
    if ($action === 'submit_answer') {
        if (!isset($_SESSION[$quiz_session_key])) {
            echo json_encode(['error' => 'Dữ liệu quiz không tồn tại.']);
            exit;
        }

        $quiz_data = &$_SESSION[$quiz_session_key];
        $vocabs = $quiz_data['vocabs'];
        $current_index = $quiz_data['current_index'];
        $stats = &$quiz_data['stats'];
        $review_queue = &$quiz_data['review_queue'];
        $answered_vocabs = &$quiz_data['answered_vocabs'];

        $user_answer = trim($_POST['user_answer'] ?? '');
        $vocab = $vocabs[$current_index];
        $correct_answer = $vocab['meaning'];
        $is_correct = normalize($user_answer) === normalize($correct_answer);
        $processed_vocab_index = $current_index; // Chỉ số của từ vừa xử lý

        // Cập nhật thống kê
        if ($is_correct) {
            $stats['correct']++;
            $stats['streak']++;
            if ($stats['streak'] > $stats['max_streak']) {
                $stats['max_streak'] = $stats['streak'];
            }
        } else {
            $stats['incorrect']++;
            $stats['streak'] = 0;
        }

        // Ghi nhớ từ đã trả lời để tránh chọn lại ngay trong đáp án sai (có thể giữ lại nếu cần)
        $answered_vocabs[] = $current_index;
        if (count($answered_vocabs) > 10) { // Giới hạn kích thước danh sách
            array_shift($answered_vocabs);
        }

        // *** LOGIC MỚI: Xử lý lại hàng đợi ôn tập (chỉ chứa từ SAI) ***
        // 1. Đảm bảo từ đã xử lý được loại bỏ khỏi review_queue (nếu có - trường hợp nó được lấy từ queue)
        // (Vì nó đã được lấy ra bằng getNextVocabIndex trước khi hiển thị)
        $quiz_data['review_queue'] = array_filter($quiz_data['review_queue'], function($index) use ($processed_vocab_index) {
            return $index != $processed_vocab_index; // So sánh giá trị chỉ số
        });
        // Đặt lại chỉ số mảng sau khi filter
        $quiz_data['review_queue'] = array_values($quiz_data['review_queue']);

        // 2. Quyết định có thêm từ đã xử lý vào lại hàng đợi ôn tập hay không
        if (!$is_correct) {
            // Nếu trả lời SAI: Thêm ngay vào cuối hàng đợi để ôn lại
            // Kiểm tra nếu từ này chưa có trong hàng đợi để tránh trùng lặp
            if (!in_array($processed_vocab_index, $quiz_data['review_queue'])) {
                $quiz_data['review_queue'][] = $processed_vocab_index; // Thêm vào cuối
                 // Giới hạn độ dài hàng đợi nếu cần (ví dụ: 50 từ)
                 // if (count($quiz_data['review_queue']) > 50) { array_shift($quiz_data['review_queue']); } // Giữ lại 50 từ cuối cùng
            }
        }
        // Nếu trả lời ĐÚNG: Không làm gì thêm với hàng đợi. Từ đó sẽ không xuất hiện lại
        // trừ khi nó được thêm vào do trả lời sai ở lần khác.

        // --- Lấy từ vựng tiếp theo ---
        $next_index = getNextVocabIndex($quiz_data, $vocabs); // Cập nhật $quiz_data['current_index'] bên trong
        // Kiểm tra nếu hết từ và không có từ ôn lại
        if ($next_index === false) {
             echo json_encode([
                'success' => true,
                'quiz_finished' => true, // Gửi tín hiệu kết thúc quiz
                'stats' => $stats,
                'total_questions' => count($vocabs),
                'current_index' => count($vocabs),
                'progress_percent' => 100
            ]);
            exit;
        }
        $quiz_data['current_index'] = $next_index; // Đảm bảo session được cập nhật

        $next_vocab = $vocabs[$next_index];
        $total_questions = count($vocabs);
        $progress_percent = ($next_index / $total_questions) * 100;
        if($progress_percent > 100) $progress_percent = 100;

        // Xác định chế độ câu hỏi cho từ tiếp theo
        $next_mode = (count($vocabs) < 4) ? 'input' : 'choice';

        // Tạo lựa chọn cho từ tiếp theo
        $next_choices = [];
        if ($next_mode === 'choice') {
            $next_choices[] = $next_vocab['meaning'];
            $potential_wrong_answers = array_filter($vocabs, function($v) use ($next_vocab, $quiz_data, $vocabs) {
                $index_in_vocabs = array_search($v, $vocabs);
                return $v['id'] != $next_vocab['id'] && !in_array($index_in_vocabs, $quiz_data['answered_vocabs']);
            });
            if (count($potential_wrong_answers) < 3) {
                $potential_wrong_answers = array_filter($vocabs, function($v) use ($next_vocab) {
                    return $v['id'] != $next_vocab['id'];
                });
            }
            $potential_wrong_answers = array_values($potential_wrong_answers);
            $selected_wrong = [];
            while (count($selected_wrong) < 3 && !empty($potential_wrong_answers)) {
                $rand_key = array_rand($potential_wrong_answers);
                $selected_wrong[] = $potential_wrong_answers[$rand_key]['meaning'];
                unset($potential_wrong_answers[$rand_key]);
                $potential_wrong_answers = array_values($potential_wrong_answers);
            }
            $next_choices = array_merge($next_choices, $selected_wrong);
            shuffle($next_choices);
        }

        // Kiểm tra nếu từ tiếp theo là từ ôn lại (lấy từ hàng đợi)
        $is_review = !empty($quiz_data['review_queue']) && (reset($quiz_data['review_queue']) == $next_index || in_array($next_index, $quiz_data['review_queue']));

        echo json_encode([
            'success' => true,
            'result' => [
                'is_correct' => $is_correct,
                'user_answer' => $user_answer,
                'correct_answer' => $correct_answer,
                'is_review' => $is_review // Thêm thông tin ôn lại
            ],
            'next_vocab' => [
                'id' => $next_vocab['id'],
                'word' => $next_vocab['word'],
                'phonetic' => $next_vocab['phonetic'],
                'meaning' => $next_vocab['meaning']
            ],
            'next_mode' => $next_mode,
            'next_choices' => $next_choices,
            'stats' => $stats,
            'total_questions' => $total_questions,
            'current_index' => $next_index,
            'progress_percent' => $progress_percent
        ]);
        exit;
    }

    // --- Hành động: Đặt lại quiz ---
    if ($action === 'reset') {
        unset($_SESSION[$quiz_session_key]);
        echo json_encode(['success' => true, 'message' => 'Quiz đã được đặt lại.']);
        exit;
    }

    echo json_encode(['error' => 'Hành động không hợp lệ.']);
    exit;
}

// --- Hiển thị trang HTML (GET request thông thường) ---
$token = $_GET['token'] ?? '';
if ($token !== '') {
    // Truy cập công khai bằng token
    $stmt = $pdo->prepare('SELECT * FROM notebooks WHERE public_token = ? AND is_public = 1');
    $stmt->execute([$token]);
    $notebook = $stmt->fetch();
    if (!$notebook) { die('Link không hợp lệ hoặc sổ tay không công khai!'); }
    $notebook_id = (int)$notebook['id'];
} else {
    // Truy cập riêng tư cần đăng nhập
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
    $user_id = $_SESSION['user_id'];
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
    <title>Quiz - Flashcard Đức</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --card-bg: #ffffff;
            --card-radius: 20px;
            --card-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            --correct-bg: #d4edda;
            --incorrect-bg: #f8d7da;
            --streak-color: #ffc107;
            --transition-speed: 0.3s;
        }
        * { box-sizing: border-box; }
        body {
            background: var(--primary-gradient);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            margin: 0; padding: 0; min-height: 100vh; display: flex; flex-direction: column;
        }
        .navbar {
            background-color: rgba(255, 255, 255, 0.95) !important;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            padding: 12px 0; flex-shrink: 0;
        }
        .navbar-brand { font-weight: 600; color: #4a5568 !important; }
        .quiz-container {
            flex: 1; display: flex; flex-direction: column; padding: 20px 15px; width: 100%;
        }
        .header-section {
            text-align: center; margin-bottom: 25px; color: white; width: 100%;
        }
        .header-section h1 {
            font-size: 1.8rem; font-weight: 700; margin-bottom: 10px; text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .progress-container {
            background: rgba(255, 255, 255, 0.2); border-radius: 50px; padding: 3px;
            margin: 0 auto 15px; max-width: 300px;
        }
        .progress-bar {
            height: 12px; background: white; border-radius: 50px; transition: width 0.3s ease;
        }
        .stats-container {
            display: flex; justify-content: center; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;
        }
        .stat-badge {
            background: rgba(255, 255, 255, 0.2); color: white; padding: 8px 16px;
            border-radius: 50px; font-size: 0.9rem; font-weight: 500;
        }
        .quiz-card {
            border-radius: var(--card-radius); box-shadow: var(--card-shadow);
            background: var(--card-bg); border: none;
            transition: transform var(--transition-speed) ease, box-shadow var(--transition-speed) ease;
            max-width: 800px; width: 100%; margin: 0 auto; position: relative; overflow: hidden;
        }
        .quiz-card:hover {
            transform: translateY(-5px); box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        .word-display {
            font-size: 2.2rem; font-weight: 700; color: #2d3748; margin-bottom: 10px; text-align: center;
        }
        .phonetic {
            font-size: 1.1rem; color: #718096; margin-bottom: 20px; text-align: center;
        }
        .form-check {
            margin-bottom: 12px; padding: 12px 15px; border: 2px solid #e2e8f0;
            border-radius: 12px; transition: all var(--transition-speed) ease; cursor: pointer;
        }
        .form-check:hover {
            border-color: #cbd5e0; background-color: #f7fafc;
        }
        .form-check-input:checked + .form-check-label {
            color: #2d3748; font-weight: 500;
        }
        .form-check-input:checked {
            background-color: #48bb78; border-color: #48bb78;
        }
        .input-answer {
            margin-bottom: 20px;
        }
        .input-answer input {
            border: 2px solid #e2e8f0; border-radius: 12px; padding: 12px 15px;
            font-size: 1.1rem; transition: border-color var(--transition-speed) ease;
        }
        .input-answer input:focus {
            border-color: #4299e1; box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
        }
        .btn-quiz {
            border-radius: 12px; padding: 12px 20px; font-weight: 600; font-size: 1.1rem;
            transition: all var(--transition-speed) ease; border: none; width: 100%; margin-bottom: 15px;
        }
        .btn-quiz:hover {
            transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0,0,0,0.1);
        }
        .btn-check { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; }
        .btn-next-main { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .result-alert {
            border-radius: 12px; font-weight: 500; padding: 20px; margin: 20px 0; text-align: center;
        }
        .encouragement {
            font-size: 1.3rem; font-weight: 700; margin-bottom: 15px; text-align: center;
        }
        .btn-review {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%); color: white;
            border: none; border-radius: 12px; padding: 10px 20px; font-weight: 600;
            margin-top: 10px; width: 100%;
        }
        .btn-review:hover {
            transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0,0,0,0.1);
        }
        .empty-state {
            text-align: center; color: white; padding: 40px 20px;
        }
        .empty-state i {
            font-size: 3rem; margin-bottom: 20px; opacity: 0.8;
        }
        .quiz-finished {
            text-align: center; padding: 40px 20px;
        }
        .quiz-finished h2 {
            color: #fff; margin-bottom: 20px;
        }
        /* Desktop styles */
        @media (min-width: 768px) {
            .header-section h1 { font-size: 2.2rem; }
            .word-display { font-size: 2.5rem; }
            .btn-quiz { padding: 14px 25px; font-size: 1.2rem; }
            .quiz-container {
                flex-direction: row; align-items: flex-start; gap: 20px; padding: 20px;
            }
            .sidebar {
                width: 250px; background: rgba(255, 255, 255, 0.1); border-radius: 15px;
                padding: 20px; backdrop-filter: blur(10px); box-shadow: 0 5px 15px rgba(0,0,0,0.05);
                height: fit-content;
            }
            .main-content {
                flex: 1; display: flex; flex-direction: column; align-items: center;
            }
            .quiz-card { width: 100%; max-width: 600px; }
            .stats-container {
                flex-direction: column; align-items: flex-start; gap: 10px;
            }
            .stat-badge {
                width: 100%; text-align: center;
            }
            .navigation-buttons {
                display: flex; flex-direction: column; gap: 10px; margin-top: 20px;
            }
            .nav-btn {
                width: 100%; text-align: left;
            }
        }
        /* Mobile optimizations */
        @media (max-width: 767px) {
            .quiz-container { padding: 15px 10px; }
            .header-section h1 { font-size: 1.6rem; }
            .word-display { font-size: 1.8rem; }
            .phonetic { font-size: 1rem; }
            .stat-badge { padding: 6px 12px; font-size: 0.8rem; }
            .form-check { padding: 10px 12px; margin-bottom: 10px; }
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
    <!-- Sidebar cho desktop -->
    <div class="sidebar d-none d-md-block">
        <h5 class="text-white mb-3">Thống kê</h5>
        <div class="stats-container">
            <div class="stat-badge">
                <i class="bi bi-collection"></i>
                <span id="total-count">0</span> từ
            </div>
            <div class="stat-badge">
                <i class="bi bi-check-circle"></i>
                <span id="correct-count">0</span> đúng
            </div>
            <div class="stat-badge">
                <i class="bi bi-lightning"></i>
                <span id="streak-count">0</span> chuỗi
            </div>
        </div>
        <h5 class="text-white mt-4 mb-3">Điều hướng</h5>
        <div class="navigation-buttons">
            <a href="dashboard.php" class="btn btn-light nav-btn">
                <i class="bi bi-journals"></i> Về sổ tay
            </a>
            <button id="reset-btn-sidebar" class="btn btn-warning nav-btn">
                <i class="bi bi-arrow-clockwise"></i> Làm lại
            </button>
            <a href="logout.php" class="btn btn-danger nav-btn">
                <i class="bi bi-box-arrow-right"></i> Đăng xuất
            </a>
        </div>
    </div>
    <!-- Nội dung chính -->
    <div class="main-content">
        <div class="header-section d-md-none">
            <h1>🧠 Quiz Từ Vựng</h1>
            <div class="progress-container">
                <div class="progress-bar" id="progress-bar" style="width: 0%"></div>
            </div>
            <div class="stats-container d-md-none">
                <div class="stat-badge">
                    <i class="bi bi-collection"></i>
                    <span id="mobile-total-count">0</span> từ
                </div>
                <div class="stat-badge">
                    <i class="bi bi-check-circle"></i>
                    <span id="mobile-correct-count">0</span> đúng
                </div>
                <div class="stat-badge">
                    <i class="bi bi-lightning"></i>
                    <span id="mobile-streak-count">0</span> chuỗi
                </div>
            </div>
        </div>

        <div class="quiz-card p-4" id="quiz-area">
            <!-- Nội dung câu hỏi sẽ được load ở đây -->
        </div>
         <div class="p-3 text-center mt-4 mb-4 text-white">
            <i class="bi bi-info-circle"></i>
             Các câu hỏi sẽ được chọn ngẫu nhiên từ sổ tay và chỉ lặp lại các câu trả lời sai. Hãy cố gắng trả lời đúng để nâng cao vốn từ vựng của mình!
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const notebookId = <?= json_encode($notebook_id) ?>; // Truyền ID từ PHP sang JS
    const publicToken = <?= json_encode($token ?? '') ?>;
    if (!notebookId) {
        alert('Thiếu thông tin sổ tay.');
        return;
    }

    const quizArea = document.getElementById('quiz-area');
    const resetBtn = document.getElementById('reset-btn');
    const resetBtnSidebar = document.getElementById('reset-btn-sidebar');

    // Hàm cập nhật thống kê
    function updateStats(stats, total) {
        document.getElementById('total-count').textContent = total;
        document.getElementById('correct-count').textContent = stats.correct;
        document.getElementById('streak-count').textContent = stats.streak;

        document.getElementById('mobile-total-count').textContent = total;
        document.getElementById('mobile-correct-count').textContent = stats.correct;
        document.getElementById('mobile-streak-count').textContent = stats.streak;
    }

    // Hàm cập nhật thanh tiến trình
    function updateProgress(percent) {
        document.getElementById('progress-bar').style.width = percent + '%';
    }

    // Hàm hiển thị câu hỏi
    function showQuestion(questionData) {
         if (questionData.quiz_finished) {
            showQuizFinished(questionData);
            return;
        }
        let html = `
            <div class="text-center mb-4">
                <div class="word-display">${questionData.vocab.word}</div>
                ${questionData.vocab.phonetic ? `<div class="phonetic">[${questionData.vocab.phonetic}]</div>` : ''}
            </div>
            <form id="quiz-form" class="mb-3">
                <input type="hidden" name="vocab_id" value="${questionData.vocab.id}">
        `;
        if (questionData.mode === 'choice') {
            questionData.choices.forEach((choice, i) => {
                html += `
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="answer" id="choice_${i}" value="${choice}" required>
                        <label class="form-check-label h5 fw-normal w-100" for="choice_${i}" style="cursor: pointer;">${choice}</label>
                    </div>
                `;
            });
        } else {
            html += `
                <div class="input-answer">
                    <input type="text" name="answer" class="form-control" placeholder="Nhập nghĩa tiếng Việt..." required>
                </div>
            `;
        }
        html += `
                <button type="submit" class="btn btn-quiz btn-check">
                    <i class="bi bi-check-circle"></i> Kiểm tra
                </button>
            </form>
        `;
        quizArea.innerHTML = html;
        updateStats(questionData.stats, questionData.total_questions);
        updateProgress(questionData.progress_percent);

        // Gắn sự kiện submit form
        document.getElementById('quiz-form').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            const userAnswer = formData.get('answer');
            submitAnswer(userAnswer);
        });

        // Gắn sự kiện click vào label để chọn radio và submit
        document.querySelectorAll('.form-check').forEach(function(formCheck) {
            formCheck.addEventListener('click', function(e) {
                if (e.target.tagName === 'INPUT') return;
                const radio = formCheck.querySelector('input[type="radio"]');
                if (radio && !radio.disabled) {
                    radio.checked = true;
                    document.getElementById('quiz-form').dispatchEvent(new Event('submit'));
                }
            });
        });
    }

    // Hàm hiển thị kết quả
    function showResult(resultData) {
         if (resultData.quiz_finished) {
            showQuizFinished(resultData);
            return;
        }
        let resultHtml = `
            <div class="mt-2">
                <div class="result-alert ${resultData.result.is_correct ? 'alert-success' : 'alert-danger'}">
                    ${resultData.result.is_correct ?
                        `<div class="h4 mt-2 mb-0">CHÍNH XÁC!</div>` :
                        `<div class="h4 mt-3 mb-1">SAI RỒI!</div>
                         <div class="fw-normal">Đáp án đúng: <b>${resultData.result.correct_answer}</b></div>
                         ${resultData.result.user_answer ? `<div class="fw-normal mt-1">Bạn trả lời: <span class="text-decoration-line-through">${resultData.result.user_answer}</span></div>` : ''}`
                    }
                </div>
                <div class="text-center">
                    <button id="next-btn" class="btn btn-quiz btn-next-main">
                        ${resultData.result.is_review ? 'Ôn lại <i class="bi bi-arrow-counterclockwise"></i>' : 'Câu tiếp <i class="bi bi-arrow-right-circle"></i>'}
                    </button>
                </div>
            </div>
        `;
        quizArea.innerHTML = resultHtml;
        updateStats(resultData.stats, resultData.total_questions);
        updateProgress(resultData.progress_percent);

        // Phát âm thanh
        if (resultData.result.is_correct) {
            document.getElementById('audio-correct').play();
        } else {
            document.getElementById('audio-wrong').play();
        }

        // Gắn sự kiện click nút tiếp theo
        document.getElementById('next-btn').addEventListener('click', function () {
            loadNextQuestion();
        });
    }

     // Hàm hiển thị thông báo kết thúc quiz (tùy chọn)
    function showQuizFinished(data) {
        let finishedHtml = `
            <div class="quiz-finished"> 
                <p> 🎉 Bạn đã hoàn thành bài Quiz của danh sách từ vựng này.</p>
                <p><strong>Thống kê cuối cùng:</strong></p>
                <ul class="list-unstyled">
                    <li>Tổng số từ: <strong>${data.total_questions}</strong></li>
                    <li>Trả lời đúng: <strong class="text-success">${data.stats.correct}</strong></li>
                    <li>Trả lời sai: <strong class="text-danger">${data.stats.incorrect}</strong></li>
                    <li>Chuỗi đúng dài nhất: <strong>${data.stats.max_streak}</strong></li>
                </ul>
                <button id="restart-btn" class="btn btn-primary btn-lg">Làm lại từ đầu</button>
            </div>
        `;
        quizArea.innerHTML = finishedHtml;
        updateStats(data.stats, data.total_questions);
        updateProgress(data.progress_percent);

        // Phát âm thanh hoàn thành
        document.getElementById('audio-finish').play();

        document.getElementById('restart-btn').addEventListener('click', function() {
            resetQuiz(); // Gọi hàm reset để bắt đầu lại
        });
    }


    // Hàm gửi câu trả lời
    function submitAnswer(userAnswer) {
        fetch('study_quiz.php', { // Gửi AJAX đến chính tệp này
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=submit_answer&notebook_id=${notebookId}&user_answer=${encodeURIComponent(userAnswer)}${publicToken ? `&token=${encodeURIComponent(publicToken)}` : ''}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert('Lỗi: ' + data.error);
                return;
            }
            if (data.success) {
                showResult(data); // Có thể là kết quả hoặc thông báo kết thúc
            }
        })
        .catch(error => console.error('Error:', error));
    }

    // Hàm tải câu hỏi tiếp theo (sau khi kết quả)
    function loadNextQuestion() {
        fetch('study_quiz.php', { // Gửi AJAX đến chính tệp này
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=get_question&notebook_id=${notebookId}${publicToken ? `&token=${encodeURIComponent(publicToken)}` : ''}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert('Lỗi: ' + data.error);
                return;
            }
            if (data.success) {
                showQuestion(data); // Có thể là câu hỏi hoặc thông báo kết thúc
            }
        })
        .catch(error => console.error('Error:', error));
    }

    // Hàm tải câu hỏi đầu tiên hoặc trạng thái hiện tại
    function loadInitialQuestion() {
        fetch('study_quiz.php', { // Gửi AJAX đến chính tệp này
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=get_question&notebook_id=${notebookId}${publicToken ? `&token=${encodeURIComponent(publicToken)}` : ''}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert('Lỗi: ' + data.error);
                return;
            }
            if (data.success) {
                showQuestion(data); // Có thể là câu hỏi hoặc thông báo kết thúc
            }
        })
        .catch(error => console.error('Error:', error));
    }

    // Hàm reset quiz
    function resetQuiz() {
        // if (!confirm('Bạn có chắc chắn muốn làm lại quiz?')) return; // Có thể bỏ confirm nếu muốn
        fetch('study_quiz.php', { // Gửi AJAX đến chính tệp này
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=reset&notebook_id=${notebookId}${publicToken ? `&token=${encodeURIComponent(publicToken)}` : ''}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert('Lỗi: ' + data.error);
                return;
            }
            if (data.success) {
                loadInitialQuestion(); // Tải lại câu hỏi đầu tiên
            }
        })
        .catch(error => console.error('Error:', error));
    }

    // Gắn sự kiện cho nút reset
    resetBtn.addEventListener('click', resetQuiz);
    resetBtnSidebar.addEventListener('click', resetQuiz);

    // Tải câu hỏi đầu tiên khi trang load xong
    loadInitialQuestion();

    // Thêm phím tắt
    document.addEventListener('keydown', function(e) {
        const form = document.getElementById('quiz-form');
        const nextBtn = document.getElementById('next-btn');

        if (form && (e.code === 'Space' || e.code === 'Enter')) {
            e.preventDefault();
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                form.dispatchEvent(new Event('submit'));
            }
        }
        if (nextBtn && e.code === 'ArrowRight') {
            nextBtn.click();
        }
    });

});
</script>
<audio id="audio-correct" src="assets/correct.mp3" preload="auto"></audio>
<audio id="audio-wrong" src="assets/wrong.mp3" preload="auto"></audio>
<audio id="audio-finish" src="assets/finish.mp3" preload="auto"></audio>
</body>
</html>
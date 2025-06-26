<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
$notebook_id = (int)($_GET['notebook_id'] ?? 0);

// Kiểm tra quyền sở hữu sổ tay
$stmt = $pdo->prepare('SELECT * FROM notebooks WHERE id=? AND user_id=?');
$stmt->execute([$notebook_id, $user_id]);
$notebook = $stmt->fetch();
if (!$notebook) {
    die('Không tìm thấy sổ tay hoặc bạn không có quyền truy cập!');
}

// Reset quiz nếu yêu cầu
if (isset($_GET['reset'])) {
    unset($_SESSION['quiz_vocabs'][$notebook_id]);
    header("Location: quiz.php?notebook_id=$notebook_id");
    exit;
}

// Khởi tạo quiz nếu chưa có
if (!isset($_SESSION['quiz_vocabs'][$notebook_id])) {
    $stmt = $pdo->prepare('SELECT * FROM vocabularies WHERE notebook_id=? ORDER BY RAND()');
    $stmt->execute([$notebook_id]);
    $_SESSION['quiz_vocabs'][$notebook_id] = $stmt->fetchAll();
}

$vocabs = $_SESSION['quiz_vocabs'][$notebook_id];
if (!$vocabs) {
    die('Sổ tay chưa có từ vựng!');
}

function normalize($str) {
    return mb_strtolower(trim(preg_replace('/\s+/', ' ', $str)));
}

$index = isset($_GET['i']) ? (int)$_GET['i'] : 0;
if ($index < 0) $index = 0;
if ($index >= count($vocabs)) $index = 0;
$vocab = $vocabs[$index];

$show_answer = false;
$is_correct = null;
$user_answer = '';
$mode = 'choice';
if (count($vocabs) < 4) $mode = 'input';

if (isset($_POST['answer'])) {
    $user_answer = trim($_POST['answer']);
    $show_answer = true;
    $is_correct = normalize($user_answer) === normalize($vocab['meaning']);
}

$choices = [];
if ($mode === 'choice') {
    $choices[] = $vocab['meaning'];
    $used = [$vocab['id']];
    while (count($choices) < 4) {
        $rand = $vocabs[array_rand($vocabs)];
        if (!in_array($rand['id'], $used) && $rand['meaning']) {
            $choices[] = $rand['meaning'];
            $used[] = $rand['id'];
        }
    }
    shuffle($choices);
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
        body { background: linear-gradient(120deg, #e0eafc, #cfdef3); font-family: 'Segoe UI', sans-serif; }
        .navbar { background: #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .quiz-card { border-radius: 1rem; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .btn-lg i { margin-right: 0.5rem; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">
            <i class="bi bi-lightning-charge text-warning"></i> Flashcard Đức
        </a>
        <div class="d-flex">
            <a href="dashboard.php" class="btn btn-outline-secondary btn-sm me-2">← Về sổ tay</a>
            <a href="logout.php" class="btn btn-danger btn-sm">Đăng xuất</a>
        </div>
    </div>
</nav>
<div class="container py-4">
    <div class="text-center mb-3">
        <h3>🧠 Quiz từ vựng</h3>
        <div class="text-muted">Sổ tay: <b><?= htmlspecialchars($notebook['title']) ?></b></div>
        <span class="badge bg-secondary mt-2">Câu <?= $index+1 ?>/<?= count($vocabs) ?></span>
    </div>

    <div class="card quiz-card p-4 mx-auto" style="max-width: 600px;">
        <div class="text-center">
            <div class="display-5 fw-bold"><?= htmlspecialchars($vocab['word']) ?></div>
            <?php if ($vocab['phonetic']): ?>
                <div class="text-muted mb-3">[<?= htmlspecialchars($vocab['phonetic']) ?>]</div>
            <?php endif; ?>
        </div>

        <form method="post">
            <?php if ($mode === 'choice'): ?>
                <?php foreach ($choices as $c): ?>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="answer" id="c<?= md5($c) ?>" value="<?= htmlspecialchars($c) ?>" required <?= $show_answer ? 'disabled' : '' ?>>
                        <label class="form-check-label" for="c<?= md5($c) ?>">
                            <?= htmlspecialchars($c) ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <input type="text" name="answer" class="form-control mb-3" placeholder="Nhập nghĩa tiếng Việt" required value="<?= htmlspecialchars($user_answer) ?>" <?= $show_answer ? 'readonly' : '' ?>>
            <?php endif; ?>
            <?php if (!$show_answer): ?>
                <button class="btn btn-primary w-100">Kiểm tra</button>
            <?php endif; ?>
        </form>

        <?php if ($show_answer): ?>
            <div class="mt-4">
                <?php if ($is_correct): ?>
                    <div class="alert alert-success text-center">Chính xác! 🎉</div>
                <?php else: ?>
                    <div class="alert alert-danger text-center">
                        Sai rồi!<br>Đáp án đúng là: <b><?= htmlspecialchars($vocab['meaning']) ?></b>
                    </div>
                <?php endif; ?>
                <div class="text-center mt-3">
                    <a href="?notebook_id=<?= $notebook_id ?>&i=<?= ($index+1)%count($vocabs) ?>" class="btn btn-outline-secondary">
                        Câu tiếp →
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

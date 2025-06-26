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

// API endpoints cho AJAX
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    if ($_GET['action'] === 'get_vocab') {
        $index = (int)($_GET['index'] ?? 0);
        $stmt = $pdo->prepare('SELECT v.*, ls.status FROM vocabularies v
            LEFT JOIN learning_status ls ON v.id = ls.vocab_id AND ls.user_id = ?
            WHERE v.notebook_id = ? ORDER BY v.created_at DESC');
        $stmt->execute([$user_id, $notebook_id]);
        $vocabs = $stmt->fetchAll();
        if (!$vocabs) {
            echo json_encode(['error' => 'Sổ tay chưa có từ vựng!']);
            exit;
        }
        $index = max(0, min($index, count($vocabs) - 1));
        $vocab = $vocabs[$index];
        echo json_encode([
            'vocab' => $vocab,
            'index' => $index,
            'total' => count($vocabs),
            'notebook_title' => $notebook['title']
        ]);
        exit;
    }
    if ($_GET['action'] === 'update_status' && $_POST) {
        $vocab_id = (int)$_POST['vocab_id'];
        $status = $_POST['status'] === 'known' ? 'known' : 'unknown';
        $stmt = $pdo->prepare('SELECT id FROM learning_status WHERE user_id=? AND vocab_id=?');
        $stmt->execute([$user_id, $vocab_id]);
        if ($stmt->fetch()) {
            $pdo->prepare('UPDATE learning_status SET status=?, last_reviewed=NOW() WHERE user_id=? AND vocab_id=?')
                ->execute([$status, $user_id, $vocab_id]);
        } else {
            $pdo->prepare('INSERT INTO learning_status (user_id, vocab_id, status, last_reviewed) VALUES (?, ?, ?, NOW())')
                ->execute([$user_id, $vocab_id, $status]);
        }
        echo json_encode(['success' => true]);
        exit;
    }
}

// Lấy danh sách từ vựng
$stmt = $pdo->prepare('SELECT v.*, ls.status FROM vocabularies v
    LEFT JOIN learning_status ls ON v.id = ls.vocab_id AND ls.user_id = ?
    WHERE v.notebook_id = ? ORDER BY v.created_at DESC');
$stmt->execute([$user_id, $notebook_id]);
$vocabs = $stmt->fetchAll();
if (!$vocabs) {
    die('Sổ tay chưa có từ vựng!');
}

// Xác định từ đang học
$index = isset($_GET['i']) ? (int)$_GET['i'] : 0;
$index = max(0, min($index, count($vocabs) - 1));
$vocab = $vocabs[$index];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Học Flashcard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
    :root {
        --primary-bg: linear-gradient(to right, #e0eafc, #cfdef3);
        --card-radius: 1.25rem;
        --card-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        --transition: all 0.4s ease;
        --btn-radius: 0.8rem;
    }

    body {
        background: var(--primary-bg);
        font-family: "Segoe UI", sans-serif;
        margin: 0;
        padding: 0;
    }

    .navbar {
        background-color: #fff !important;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .container {
        padding: 20px 15px;
    }

    .flashcard {
        perspective: 1000px;
        max-width: 420px;
        margin: 30px auto;
        position: relative;
    }

    .card-inner {
        width: 100%;
        height: 280px;
        position: relative;
        transform-style: preserve-3d;
        transition: var(--transition);
    }

    .flipped .card-inner {
        transform: rotateY(180deg);
    }

    .card-front, .card-back {
        position: absolute;
        width: 100%;
        height: 100%;
        border-radius: var(--card-radius);
        backface-visibility: hidden;
        background: #fff;
        box-shadow: var(--card-shadow);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        text-align: center;
    }

    .card-front {
        z-index: 2;
    }

    .card-back {
        transform: rotateY(180deg);
        text-align: left;
        font-size: 1rem;
        padding: 2rem 1.5rem;
        line-height: 1.6;
    }

    .card-front #word-display {
        font-size: 2rem;
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 10px;
    }

    .card-front #phonetic {
        font-size: 1rem;
        color: #718096;
        font-style: italic;
    }

    .btn-audio {
        font-size: 0.9rem;
        padding: 8px 14px;
        border-radius: 50px;
        margin-top: 10px;
    }

    .btn {
        transition: var(--transition);
        border-radius: var(--btn-radius);
    }

    .btn:hover {
        transform: translateY(-1px);
    }

    .badge {
        font-size: 0.95rem;
        padding: 5px 10px;
        border-radius: 0.6rem;
    }

    #status-badge {
        margin-left: 10px;
    }

    .btn-known {
        background: linear-gradient(135deg, #38a169, #48bb78);
        color: #fff;
        border: none;
    }

    .btn-unknown {
        background: linear-gradient(135deg, #ed8936, #f6ad55);
        color: #fff;
        border: none;
    }

    .btn-known:hover,
    .btn-unknown:hover {
        opacity: 0.9;
    }

    .mt-4 button {
        min-width: 120px;
    }

    @media (max-width: 576px) {
        .container {
            padding: 10px 8px;
        }

        .flashcard {
            max-width: 95vw;
        }

        .card-inner {
            height: 220px;
        }

        .card-front, .card-back {
            padding: 1.2rem;
            font-size: 1rem;
        }

        .card-front #word-display {
            font-size: 1.6rem;
        }

        .btn-audio {
            font-size: 0.8rem;
            padding: 6px 10px;
        }

        .btn {
            font-size: 0.9rem;
        }
        .swipe-left {
            animation: swipeLeft 0.4s ease forwards;
        }
        .swipe-right {
            animation: swipeRight 0.4s ease forwards;
        }
        @keyframes swipeLeft {
            0% { transform: translateX(0) rotate(0deg); opacity: 1; }
            100% { transform: translateX(-150%) rotate(-15deg); opacity: 0; }
        }
        @keyframes swipeRight {
            0% { transform: translateX(0) rotate(0deg); opacity: 1; }
            100% { transform: translateX(150%) rotate(15deg); opacity: 0; }
        }
    }
</style>

</head>
<body>

<nav class="navbar navbar-light bg-light shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php"><i class="bi bi-arrow-left"></i> Quay lại</a>
        <span class="navbar-text">Sổ tay: <strong><?= htmlspecialchars($notebook['title']) ?></strong></span>
    </div>
</nav>

<div class="container mt-4 text-center">
    <h3 class="mb-3">📖 Học Flashcard</h3>

    <div class="mb-3">
        <span class="badge bg-secondary" id="progress-badge">Từ <?= $index+1 ?>/<?= count($vocabs) ?></span>
        <span id="status-badge">
        <?php if ($vocab['status'] === 'known'): ?>
            <span class="badge bg-success">Đã biết</span>
        <?php elseif ($vocab['status'] === 'unknown'): ?>
            <span class="badge bg-warning text-dark">Chưa biết</span>
        <?php endif; ?>
        </span>
    </div>

    <div class="flashcard" id="flashcard">
        <div class="card-inner" id="cardInner">
            <div class="card-front">
                <div id="word-display"><?= htmlspecialchars($vocab['word']) ?></div>
                <?php if ($vocab['phonetic']): ?>
                    <div class="text-muted" id="phonetic" style="font-size: 1rem;">[<?= htmlspecialchars($vocab['phonetic']) ?>]</div>
                <?php else: ?>
                    <div id="phonetic"></div>
                <?php endif; ?>
                <button onclick="speakWord(document.getElementById('word-display').textContent)" class="btn btn-sm btn-outline-primary mt-2 btn-audio">
                    <i class="bi bi-volume-up"></i> Nghe phát âm
                </button>
            </div>
            <div class="card-back">
                <div><strong>Nghĩa:</strong> <span id="meaning"><?= nl2br(htmlspecialchars($vocab['meaning'])) ?></span></div>
                <div id="note"><?php if ($vocab['note']): ?><strong>Ghi chú:</strong> <?= nl2br(htmlspecialchars($vocab['note'])) ?><?php endif; ?></div>
                <div id="plural"><?php if ($vocab['plural']): ?><strong>Số nhiều:</strong> <?= htmlspecialchars($vocab['plural']) ?><?php endif; ?></div>
                <div id="genus"><?php if ($vocab['genus']): ?><strong>Giống:</strong> <?= htmlspecialchars($vocab['genus']) ?><?php endif; ?></div>
            </div>
        </div>
    </div>

    <button class="btn btn-outline-primary mb-3" onclick="flipCard()">
        <i class="bi bi-arrow-repeat"></i> Lật thẻ
    </button>

    <div class="d-inline-block">
        <button id="btn-known" class="btn btn-success me-2"><i class="bi bi-check-circle"></i> Đã biết</button>
        <button id="btn-unknown" class="btn btn-warning text-dark"><i class="bi bi-question-circle"></i> Chưa biết</button>
    </div>

    <div class="mt-4">
        <button id="btn-prev" class="btn btn-outline-secondary me-2"><i class="bi bi-chevron-left"></i> Trước</button>
        <button id="btn-next" class="btn btn-outline-secondary">Tiếp <i class="bi bi-chevron-right"></i></button>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/hammerjs@2.0.8/hammer.min.js"></script>
<script>
const notebookId = <?= $notebook_id ?>;
let currentIndex = <?= $index ?>;
let totalVocabs = <?= count($vocabs) ?>;
let isFlipped = false;

function flipCard() {
    document.getElementById('flashcard').classList.toggle('flipped');
    isFlipped = !isFlipped;
}

function speakWord(text) {
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = 'de-DE'; // tiếng Đức
    speechSynthesis.speak(utterance);
}

async function loadVocab(index) {
    const res = await fetch(`?action=get_vocab&index=${index}&notebook_id=${notebookId}`);
    const data = await res.json();
    if (data.error) return alert(data.error);
    // Cập nhật giao diện
    document.getElementById('word-display').textContent = data.vocab.word;
    document.getElementById('phonetic').textContent = data.vocab.phonetic ? `[${data.vocab.phonetic}]` : '';
    document.getElementById('meaning').innerHTML = escapeHtml(data.vocab.meaning);
    document.getElementById('note').innerHTML = data.vocab.note ? `<strong>Ghi chú:</strong> ${escapeHtml(data.vocab.note)}` : '';
    document.getElementById('plural').innerHTML = data.vocab.plural ? `<strong>Số nhiều:</strong> ${escapeHtml(data.vocab.plural)}` : '';
    document.getElementById('genus').innerHTML = data.vocab.genus ? `<strong>Giống:</strong> ${escapeHtml(data.vocab.genus)}` : '';
    document.getElementById('progress-badge').textContent = `Từ ${data.index+1}/${data.total}`;
    let statusHtml = '';
    if (data.vocab.status === 'known') statusHtml = '<span class="badge bg-success">Đã biết</span>';
    else if (data.vocab.status === 'unknown') statusHtml = '<span class="badge bg-warning text-dark">Chưa biết</span>';
    document.getElementById('status-badge').innerHTML = statusHtml;
    currentIndex = data.index;
    totalVocabs = data.total;
    // Reset flip nếu đang lật
    const flashcard = document.getElementById('flashcard');
    if (isFlipped) { flashcard.classList.remove('flipped'); isFlipped = false; }
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>');
}

document.getElementById('btn-prev').onclick = () => {
    let idx = (currentIndex - 1 + totalVocabs) % totalVocabs;
    loadVocab(idx);
};

document.getElementById('btn-next').onclick = () => {
    let idx = (currentIndex + 1) % totalVocabs;
    loadVocab(idx);
};

document.getElementById('btn-known').onclick = () => updateStatus('known');
document.getElementById('btn-unknown').onclick = () => updateStatus('unknown');

async function updateStatus(status) {
    const vocab = document.getElementById('word-display').textContent;
    // Lấy id từ server (an toàn hơn là client lưu id)
    const res = await fetch(`?action=get_vocab&index=${currentIndex}&notebook_id=${notebookId}`);
    const data = await res.json();
    if (!data.vocab) return;
    await fetch(`?action=update_status&notebook_id=${notebookId}`, {
        method: 'POST',
        body: new URLSearchParams({ vocab_id: data.vocab.id, status })
    });
    // Tự động chuyển sang từ tiếp theo
    let idx = (currentIndex + 1) % totalVocabs;
    loadVocab(idx);
}

// Swipe gesture
const flashcardContainer = document.getElementById('flashcard');
const mc = new Hammer(flashcardContainer);
mc.get('swipe').set({ direction: Hammer.DIRECTION_HORIZONTAL });

mc.on('swipeleft', () => {
    flashcardContainer.classList.add('swipe-left');
    setTimeout(() => {
        let idx = (currentIndex + 1) % totalVocabs;
        loadVocab(idx);
        flashcardContainer.classList.remove('swipe-left');
    }, 400); // khớp với thời gian animation
});

mc.on('swiperight', () => {
    flashcardContainer.classList.add('swipe-right');
    setTimeout(() => {
        let idx = (currentIndex - 1 + totalVocabs) % totalVocabs;
        loadVocab(idx);
        flashcardContainer.classList.remove('swipe-right');
    }, 400);
});

</script>
</body>
</html>

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

// Lấy tất cả từ vựng một lần để tải trước
$stmt = $pdo->prepare('SELECT v.*, ls.status FROM vocabularies v
    LEFT JOIN learning_status ls ON v.id = ls.vocab_id AND ls.user_id = ?
    WHERE v.notebook_id = ? ORDER BY v.created_at DESC');
$stmt->execute([$user_id, $notebook_id]);
$all_vocabs = $stmt->fetchAll();

// API để cập nhật trạng thái vẫn giữ nguyên
if (isset($_GET['action']) && $_GET['action'] === 'update_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $vocab_id = (int)($_POST['vocab_id'] ?? 0);
    $status = $_POST['status'] === 'known' ? 'known' : 'unknown';

    if ($vocab_id > 0) {
        $stmt = $pdo->prepare('SELECT id FROM learning_status WHERE user_id=? AND vocab_id=?');
        $stmt->execute([$user_id, $vocab_id]);
        if ($stmt->fetch()) {
            $pdo->prepare('UPDATE learning_status SET status=?, last_reviewed=NOW() WHERE user_id=? AND vocab_id=?')
                ->execute([$status, $user_id, $vocab_id]);
        } else {
            $pdo->prepare('INSERT INTO learning_status (user_id, vocab_id, status, last_reviewed) VALUES (?, ?, ?, NOW())')
                ->execute([$user_id, $vocab_id, $status]);
        }
        echo json_encode(['success' => true, 'message' => 'Cập nhật thành công.']);
        exit;
    }
    echo json_encode(['success' => false, 'message' => 'Thiếu ID từ vựng.']);
    exit;
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Học Flashcard - Trải nghiệm mới</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --primary-bg: linear-gradient(to right, #e0eafc, #cfdef3);
            --card-radius: 1.25rem;
            --card-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            --transition-speed: 0.5s;
        }

        body {
            background: var(--primary-bg);
            font-family: "Segoe UI", sans-serif;
            margin: 0;
            padding: 0;
            overflow: hidden;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar {
            background-color: #fff !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            flex-shrink: 0;
        }

        .main-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 15px;
            overflow-y: auto;
        }
        
        .card-stack-container {
            perspective: 2000px;
            width: 100%;
            max-width: 420px;
            min-height: 280px;
            margin: 20px auto;
            position: relative;
        }

        .flashcard {
            width: 100%;
            height: 280px;
            position: absolute;
            top: 0;
            left: 0;
            cursor: grab;
            user-select: none;
            transform-style: preserve-3d;
            transition: transform var(--transition-speed) ease, opacity var(--transition-speed) ease;
        }

        .flashcard.dragging { transition: none; }
        .flashcard.card--next { transform: translateY(10px) scale(0.95); opacity: 0.7; pointer-events: none; }
        .flashcard.card--after-next { transform: translateY(20px) scale(0.90); opacity: 0.4; pointer-events: none; }
        .flashcard.card--out { pointer-events: none; }
        
        .card-inner {
            width: 100%; height: 100%; position: relative;
            transform-style: preserve-3d;
            transition: transform 0.6s cubic-bezier(0.25, 1, 0.5, 1);
        }

        .flashcard.flipped .card-inner { transform: rotateY(180deg); }

        .card-front, .card-back {
            position: absolute; width: 100%; height: 100%;
            border-radius: var(--card-radius);
            backface-visibility: hidden;
            background: #fff; box-shadow: var(--card-shadow);
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            padding: 2rem; text-align: center; overflow: hidden;
        }

        .card-back {
            transform: rotateY(180deg); text-align: left;
            font-size: 1rem; padding: 1.5rem; line-height: 1.6;
            justify-content: flex-start; overflow-y: auto;
            align-items: center; justify-content: center;
            padding: 2rem; text-align: center; overflow: hidden;
        }
        .card-back > div { margin-bottom: 8px; }
        .card-front #word-display { font-size: 2rem; font-weight: 600; }
        
        .swipe-indicator {
            position: absolute; top: 20px; border-radius: 10px;
            padding: 5px 15px; font-weight: bold; color: #fff;
            opacity: 0; transition: opacity 0.2s ease-in-out;
            text-transform: uppercase;
        }
        .swipe-indicator.left {
            right: 20px; background-color: rgba(237, 137, 54, 0.8);
            border: 2px solid #ed8936; transform: rotate(15deg);
        }
         .swipe-indicator.right {
            left: 20px; background-color: rgba(56, 161, 105, 0.8);
            border: 2px solid #38a169; transform: rotate(-15deg);
        }

        @media (max-width: 576px) {
            .card-stack-container { min-height: 250px; }
            .flashcard { height: 250px; }
            .card-front #word-display { font-size: 1.6rem; }
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

<!-- Dữ liệu từ vựng được truyền an toàn tại đây -->
<script type="application/json" id="vocab-data">
    <?= json_encode($all_vocabs, JSON_UNESCAPED_UNICODE) ?>
</script>


<div class="main-content">
    <h3 class="mb-2">📖 Học Flashcard</h3>

    <div class="mb-2 text-center">
        <span class="badge bg-secondary" id="progress-badge"></span>
        <span id="status-badge" class="ms-2"></span>
    </div>

    <div class="card-stack-container" id="cardStackContainer">
        <div class="card-stack" id="cardStack">
            <!-- Các thẻ flashcard sẽ được JS chèn vào đây -->
        </div>
    </div>

    <div class="d-flex justify-content-center align-items-center mt-3" style="gap: 15px;">
        <button id="btn-unknown" class="btn btn-warning text-dark btn-lg"><i class="bi bi-x-lg"></i></button>
        <button id="btn-flip" class="btn btn-outline-primary"><i class="bi bi-arrow-repeat"></i> Lật thẻ</button>
        <button id="btn-known" class="btn btn-success btn-lg"><i class="bi bi-check-lg"></i></button>
    </div>

    <div class="mt-4">
        <button id="btn-prev" class="btn btn-outline-secondary me-2"><i class="bi bi-chevron-left"></i> Trước</button>
        <button id="btn-next" class="btn btn-outline-secondary">Tiếp <i class="bi bi-chevron-right"></i></button>
    </div>

    <div class="text-center text-muted mt-3" style="font-size: 0.95rem;">
        Vuốt thẻ sang <strong>trái</strong> để đánh dấu <span class="text-warning fw-bold">chưa biết</span>, 
        vuốt thẻ sang <strong>phải</strong> để đánh dấu <span class="text-success fw-bold">đã biết</span>.
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/hammerjs@2.0.8/hammer.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- KHỞI TẠO BIẾN ---
    const notebookId = <?= $notebook_id ?>;
    let allVocabs = [];
    let currentIndex = 0;
    let isAnimating = false;

    // Lấy dữ liệu an toàn từ thẻ script JSON
    try {
        const vocabDataElement = document.getElementById('vocab-data');
        allVocabs = JSON.parse(vocabDataElement.textContent);
    } catch (e) {
        console.error("Lỗi khi đọc dữ liệu từ vựng:", e);
    }

    // Xác định chỉ số bắt đầu từ URL
    const urlParams = new URLSearchParams(window.location.search);
    const initialIndexParam = parseInt(urlParams.get('i'), 10);
    if (!isNaN(initialIndexParam) && initialIndexParam >= 0 && initialIndexParam < allVocabs.length) {
        currentIndex = initialIndexParam;
    }


    const cardStack = document.getElementById('cardStack');
    const progressBadge = document.getElementById('progress-badge');
    const statusBadge = document.getElementById('status-badge');

    // --- CÁC HÀM QUẢN LÝ THẺ ---

    function createCardElement(vocab, index) {
        const card = document.createElement('div');
        card.className = 'flashcard';
        card.dataset.index = index;
        card.dataset.id = vocab.id;

        card.innerHTML = `
            <div class="swipe-indicator left">Chưa biết</div>
            <div class="swipe-indicator right">Đã biết</div>
            <div class="card-inner">
                <div class="card-front">
                    <div>${escapeHtml(vocab.word)}</div>
                    ${vocab.phonetic ? `<div class="text-muted" style="font-size: 1rem;">[${escapeHtml(vocab.phonetic)}]</div>` : ''}
                    <button class="btn btn-sm btn-outline-primary mt-2 btn-audio">
                        <i class="bi bi-volume-up"></i> Nghe
                    </button>
                </div>
                <div class="card-back">
                    <div><strong>Nghĩa:</strong> ${nl2br(escapeHtml(vocab.meaning))}</div>
                    ${vocab.note ? `<div><strong>Ghi chú:</strong> ${nl2br(escapeHtml(vocab.note))}</div>` : ''}
                    ${vocab.plural ? `<div><strong>Số nhiều:</strong> ${escapeHtml(vocab.plural)}</div>` : ''}
                    ${vocab.genus ? `<div><strong>Giống:</strong> ${escapeHtml(vocab.genus)}</div>` : ''}
                </div>
            </div>
        `;
        card.querySelector('.btn-audio').addEventListener('click', (e) => {
            e.stopPropagation();
            speakWord(vocab.word);
        });
        return card;
    }

    function updateCardStack() {
        cardStack.innerHTML = ''; 
        
        const cardsToCreate = [];
        if (allVocabs[currentIndex]) {
            cardsToCreate.push({vocab: allVocabs[currentIndex], index: currentIndex});
        }
        const nextIndex = (currentIndex + 1) % allVocabs.length;
        if (allVocabs.length > 1 && nextIndex !== currentIndex) {
             cardsToCreate.push({vocab: allVocabs[nextIndex], index: nextIndex});
        }
        const afterNextIndex = (currentIndex + 2) % allVocabs.length;
         if (allVocabs.length > 2 && afterNextIndex !== currentIndex && afterNextIndex !== nextIndex) {
             cardsToCreate.push({vocab: allVocabs[afterNextIndex], index: afterNextIndex});
        }

        cardsToCreate.reverse().forEach((data, i) => {
            const cardEl = createCardElement(data.vocab, data.index);
            if (i === 1) cardEl.classList.add('card--next');
            if (i === 0) cardEl.classList.add('card--after-next');
            cardStack.appendChild(cardEl);
        });

        if (cardStack.children.length > 0) {
            setupHammer(cardStack.lastChild);
        }
        updateUI();
    }
    
    function updateUI() {
        if (!allVocabs[currentIndex]) return;
        progressBadge.textContent = `Từ ${currentIndex + 1}/${allVocabs.length}`;
        const currentVocab = allVocabs[currentIndex];
        let statusHtml = '';
        if (currentVocab.status === 'known') {
            statusHtml = '<span class="badge bg-success">Đã biết</span>';
        } else if (currentVocab.status === 'unknown') {
            statusHtml = '<span class="badge bg-warning text-dark">Chưa biết</span>';
        }
        statusBadge.innerHTML = statusHtml;
    }

    // --- XỬ LÝ VUỐT (HAMMER.JS) ---

    function setupHammer(element) {
        const mc = new Hammer(element);

        mc.on('panstart', () => element.classList.add('dragging'));

        mc.on('pan', (e) => {
            element.style.transform = `translate(${e.deltaX}px, ${e.deltaY}px) rotate(${e.deltaX / 20}deg)`;
            
            element.querySelector('.swipe-indicator.right').style.opacity = Math.max(0, e.deltaX / 100);
            element.querySelector('.swipe-indicator.left').style.opacity = Math.max(0, -e.deltaX / 100);

            const nextCard = cardStack.querySelector('.card--next');
            if(nextCard) {
                const dragRatio = Math.min(1, Math.abs(e.deltaX) / (element.offsetWidth / 2));
                const scale = 0.95 + (0.05 * dragRatio);
                const translateY = 10 - (10 * dragRatio);
                nextCard.style.transform = `translateY(${translateY}px) scale(${scale})`;
            }
        });

        mc.on('panend', (e) => {
            element.classList.remove('dragging');
            const swipeThreshold = element.offsetWidth / 3;

            if (Math.abs(e.deltaX) > swipeThreshold) {
                const direction = e.deltaX > 0 ? 'right' : 'left';
                const flyOutX = (direction === 'right' ? 1 : -1) * (element.offsetWidth * 1.5);
                element.style.transform = `translate(${flyOutX}px, ${e.deltaY * 5}px) rotate(${e.deltaX / 10}deg)`;
                element.classList.add('card--out');
                processSwipe(direction === 'right' ? 'known' : 'unknown');
            } else {
                element.style.transform = '';
                element.querySelector('.swipe-indicator.right').style.opacity = 0;
                element.querySelector('.swipe-indicator.left').style.opacity = 0;
                const nextCard = cardStack.querySelector('.card--next');
                if(nextCard) nextCard.style.transform = 'translateY(10px) scale(0.95)';
            }
        });
    }

    // --- HÀNH ĐỘNG VÀ SỰ KIỆN ---

    function processSwipe(status) {
        if (isAnimating) return;
        isAnimating = true;

        const currentCardEl = cardStack.lastChild;
        if(!currentCardEl) { isAnimating = false; return; }
        
        updateStatusOnServer(currentCardEl.dataset.id, status);
        allVocabs[parseInt(currentCardEl.dataset.index)].status = status;

        setTimeout(() => {
            currentCardEl.remove();
            
            const nextCard = cardStack.querySelector('.card--next');
            if (nextCard) nextCard.classList.remove('card--next');

            const afterNextCard = cardStack.querySelector('.card--after-next');
            if (afterNextCard) {
                 afterNextCard.classList.remove('card--after-next');
                 afterNextCard.classList.add('card--next');
            }
            
            currentIndex = (currentIndex + 1) % allVocabs.length;
            
            const newCardIndex = (currentIndex + 2) % allVocabs.length;
            if (allVocabs.length > 2 && newCardIndex !== currentIndex && newCardIndex !== (currentIndex + 1) % allVocabs.length) {
                const newCard = createCardElement(allVocabs[newCardIndex], newCardIndex);
                newCard.classList.add('card--after-next');
                cardStack.insertBefore(newCard, cardStack.firstChild);
            }
            
            if (cardStack.children.length > 0) setupHammer(cardStack.lastChild);
            updateUI();
            isAnimating = false;
        }, 100);
    }
    
    function flipCard() {
        const topCard = cardStack.lastChild;
        if (topCard) topCard.classList.toggle('flipped');
    }

    function speakWord(text) {
        speechSynthesis.cancel();
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'de-DE';
        speechSynthesis.speak(utterance);
    }
    
    async function updateStatusOnServer(vocabId, status) {
       try {
           await fetch(`?action=update_status¬ebook_id=${notebookId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `vocab_id=${vocabId}&status=${status}`
           });
       } catch (error) {
           console.error("Lỗi khi cập nhật trạng thái:", error);
       }
    }

    function triggerSwipeAnimation(direction) {
        const topCard = cardStack.lastChild;
        if (topCard && !isAnimating) {
            topCard.style.transition = 'transform 0.5s ease';
            const x_direction = direction === 'right' ? 1 : -1;
            topCard.style.transform = `translateX(${x_direction * 150}%) rotate(${x_direction * 15}deg)`;
            processSwipe(direction === 'right' ? 'known' : 'unknown');
        }
    }

    // --- GẮN SỰ KIỆN CHO CÁC NÚT ---
    document.getElementById('btn-flip').addEventListener('click', flipCard);
    document.getElementById('btn-known').addEventListener('click', () => triggerSwipeAnimation('right'));
    document.getElementById('btn-unknown').addEventListener('click', () => triggerSwipeAnimation('left'));
    document.getElementById('btn-next').addEventListener('click', () => {
        if (!isAnimating) processSwipe(allVocabs[currentIndex]?.status || 'unknown');
    });
    document.getElementById('btn-prev').addEventListener('click', () => {
        if (isAnimating || allVocabs.length < 2) return;
        currentIndex = (currentIndex - 1 + allVocabs.length) % allVocabs.length;
        updateCardStack();
    });

    // --- HÀM TIỆN ÍCH ---
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.innerText = text;
        return div.innerHTML;
    }
    function nl2br(str) {
        if (!str) return '';
        return str.replace(/(\r\n|\n\r|\r|\n)/g, '<br>');
    }

    // --- KHỞI ĐỘNG ỨNG DỤNG ---
    if(allVocabs && allVocabs.length > 0) {
        updateCardStack();
    } else {
        cardStackContainer.innerHTML = '<p class="text-center mt-5">Sổ tay này chưa có từ vựng nào để học.</p>';
        document.querySelector('.main-content > .d-flex').style.display = 'none';
        document.querySelector('.main-content > .mt-4').style.display = 'none';
        document.getElementById('progress-badge').style.display = 'none';
    }
});
</script>

</body>
</html>
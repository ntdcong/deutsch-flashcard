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
            --primary-bg: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --card-bg: #ffffff;
            --card-radius: 20px;
            --card-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            --transition-speed: 0.4s;
            --known-color: #48bb78;
            --unknown-color: #ed8936;
        }
        
        * {
            box-sizing: border-box;
        }
        
        body { 
            background: var(--primary-bg); 
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; 
            margin: 0; 
            padding: 0; 
            min-height: 100vh;
            display: flex; 
            flex-direction: column; 
        }
        
        .navbar { 
            background-color: rgba(255, 255, 255, 0.95) !important; 
            box-shadow: 0 2px 20px rgba(0,0,0,0.1); 
            backdrop-filter: blur(10px);
            padding: 12px 0;
            flex-shrink: 0; 
        }
        
        .navbar-brand {
            font-weight: 600;
            color: #4a5568 !important;
        }
        
        .main-content { 
            flex-grow: 1; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            padding: 20px 15px; 
            width: 100%;
        }
        
        .header-section {
            text-align: center;
            margin-bottom: 25px;
            color: white;
            width: 100%;
        }
        
        .header-section h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .progress-container {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50px;
            padding: 3px;
            margin: 0 auto 15px;
            max-width: 300px;
        }
        
        .progress-bar {
            height: 12px;
            background: white;
            border-radius: 50px;
            transition: width 0.3s ease;
        }
        
        .stats-container {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .stat-badge {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .card-stack-container { 
            perspective: 2000px; 
            width: 100%; 
            max-width: 380px; 
            height: 260px; 
            margin: 0 auto 25px; 
            position: relative; 
        }
        
        .flashcard { 
            width: 100%; 
            height: 100%; 
            position: absolute; 
            top: 0; 
            left: 0; 
            cursor: grab; 
            user-select: none; 
            transform-style: preserve-3d; 
            transition: transform var(--transition-speed) cubic-bezier(0.4,0.2,0.2,1), 
                        opacity var(--transition-speed) cubic-bezier(0.4,0.2,0.2,1);
            z-index: 2; 
            border-radius: var(--card-radius);
        }
        
        .flashcard.dragging { 
            transition: none; 
        }
        
        .flashcard.card--next { 
            transform: translateY(8px) scale(0.96); 
            opacity: 0.8; 
            pointer-events: none; 
            z-index: 1; 
        }
        
        .flashcard.card--after-next { 
            transform: translateY(16px) scale(0.92); 
            opacity: 0.6; 
            pointer-events: none; 
            z-index: 0; 
        }
        
        .flashcard.card--out { 
            pointer-events: none; 
        }
        
        .card-inner { 
            width: 100%; 
            height: 100%; 
            position: relative; 
            transform-style: preserve-3d; 
            transition: transform 0.6s cubic-bezier(0.25, 1, 0.5, 1); 
            border-radius: var(--card-radius);
        }
        
        .flashcard.flipped .card-inner { 
            transform: rotateY(180deg); 
        }
        
        .card-front, .card-back { 
            position: absolute; 
            width: 100%; 
            height: 100%; 
            border-radius: var(--card-radius); 
            backface-visibility: hidden; 
            background: var(--card-bg);
            box-shadow: var(--card-shadow);
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center; 
            padding: 30px 25px; 
            text-align: center; 
            overflow: hidden;
        }
        
        .card-back { 
            transform: rotateY(180deg); 
            position: absolute; 
            width: 100%; 
            height: 100%; 
            display: flex; 
            background:rgb(208, 222, 255);
            flex-direction: column; 
            align-items: center; 
            justify-content: center; 
            padding: 30px 25px; 
            text-align: center; 
            overflow: hidden;
        }
        
        .card-back-content {
            width: 100%;
        }
        
        .card-front #word-display { 
            font-size: 2.2rem; 
            font-weight: 700; 
            color: #2d3748;
            margin-bottom: 10px;
        }
        
        .card-front .phonetic {
            font-size: 1.1rem;
            color: #718096;
            margin-bottom: 20px;
        }
        
        .card-back .vocab-info {
            margin-bottom: 15px;
        }
        
        .card-back .vocab-info strong {
            color: #4a5568;
        }
        
        .swipe-indicator { 
            position: absolute; 
            top: 20px; 
            border-radius: 12px; 
            padding: 8px 20px; 
            font-weight: 600; 
            color: #fff; 
            opacity: 0; 
            transition: opacity 0.2s ease-in-out; 
            text-transform: uppercase; 
            pointer-events: none; 
            font-size: 0.9rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .swipe-indicator.left { 
            right: 20px; 
            background-color: var(--unknown-color); 
            border: 2px solid rgba(237, 137, 54, 0.8); 
            transform: rotate(10deg); 
        }
        
        .swipe-indicator.right { 
            left: 20px; 
            background-color: var(--known-color); 
            border: 2px solid rgba(56, 161, 105, 0.8); 
            transform: rotate(-10deg); 
        }
        
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .btn-action {
            min-width: 120px;
            padding: 12px 20px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.2s ease;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        
        .btn-action:active {
            transform: translateY(0);
        }
        
        .navigation-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .btn-nav {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            font-size: 1.2rem;
            color: #4a5568;
            transition: all 0.2s ease;
        }
        
        .btn-nav:hover {
            background: white;
            transform: scale(1.1);
        }
        
        .status-buttons {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 10px;
        }
        
        .btn-status {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            font-size: 1.5rem;
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            transition: all 0.2s ease;
        }
        
        .btn-status:hover {
            transform: scale(1.1);
        }
        
        .btn-unknown {
            background: linear-gradient(135deg, #FF8E53 0%, #FE6B8B 100%);
            color: white;
        }
        
        .btn-known {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
        }
        
        .empty-state {
            text-align: center;
            color: white;
            padding: 40px 20px;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 20px;
            opacity: 0.8;
        }
        
        /* Desktop styles */
        @media (min-width: 768px) {
            .header-section h1 {
                font-size: 2.2rem;
            }
            
            .card-stack-container {
                max-width: 420px;
                height: 300px;
            }
            
            .card-front #word-display {
                font-size: 2.5rem;
            }
            
            .action-buttons {
                gap: 25px;
            }
            
            .btn-action {
                min-width: 140px;
                padding: 14px 25px;
            }
        }
        
        /* Mobile optimizations */
        @media (max-width: 576px) {
            .main-content {
                padding: 15px 10px;
            }
            
            .header-section h1 {
                font-size: 1.6rem;
            }
            
            .card-stack-container {
                height: 240px;
            }
            
            .card-front #word-display {
                font-size: 1.8rem;
            }
            
            .card-front .phonetic {
                font-size: 1rem;
            }
            
            .btn-action {
                min-width: 100px;
                padding: 10px 15px;
                font-size: 0.9rem;
            }
            
            .status-buttons {
                gap: 20px;
            }
            
            .btn-status {
                width: 60px;
                height: 60px;
                font-size: 1.3rem;
            }
        }
        
        /* Scrollbar styling */
        .card-back::-webkit-scrollbar {
            width: 6px;
        }
        
        .card-back::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        
        .card-back::-webkit-scrollbar-thumb {
            background: #c5c5c5;
            border-radius: 3px;
        }
        
        .card-back::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-light shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">
            <i class="bi bi-arrow-left"></i> Quay lại
        </a>
        <span class="navbar-text text-truncate" style="max-width: 200px;">
            <?= htmlspecialchars($notebook['title']) ?>
        </span>
    </div>
</nav>

<script type="application/json" id="vocab-data">
    <?= json_encode($all_vocabs, JSON_UNESCAPED_UNICODE) ?>
</script>

<div class="main-content">
    <div class="header-section">
        <h1>📖 Học Flashcard</h1>
        <div class="progress-container">
            <div class="progress-bar" id="progress-bar"></div>
        </div>
        <div class="stats-container">
            <div class="stat-badge">
                <i class="bi bi-collection"></i> 
                <span id="total-count">0</span> từ
            </div>
            <div class="stat-badge">
                <i class="bi bi-check-circle"></i> 
                <span id="known-count">0</span> đã biết
            </div>
        </div>
    </div>
    
    <div class="card-stack-container" id="cardStackContainer">
        <div class="card-stack" id="cardStack"></div>
    </div>
    
    <div class="action-buttons">
        <button id="btn-prev" class="btn btn-light btn-action">Trước</button>
        <button id="btn-flip" class="btn btn-primary btn-action">Lật thẻ</button>
        <button id="btn-next" class="btn btn-light btn-action">Tiếp</button>
    </div>
    
    <div class="status-buttons">
        <button id="btn-unknown" class="btn btn-unknown btn-status" title="Chưa biết">
            <i class="bi bi-x-lg"></i>
        </button>
        <button id="btn-known" class="btn btn-known btn-status" title="Đã biết">
            <i class="bi bi-check-lg"></i>
        </button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const notebookId = <?= $notebook_id ?>;
    let allVocabs = [];
    let currentIndex = 0;
    let isAnimating = false;
    
    try {
        const vocabDataElement = document.getElementById('vocab-data');
        allVocabs = JSON.parse(vocabDataElement.textContent);
    } catch (e) { 
        console.error("Lỗi khi đọc dữ liệu từ vựng:", e); 
    }
    
    const urlParams = new URLSearchParams(window.location.search);
    const initialIndexParam = parseInt(urlParams.get('i'), 10);
    if (!isNaN(initialIndexParam) && initialIndexParam >= 0 && initialIndexParam < allVocabs.length) {
        currentIndex = initialIndexParam;
    }
    
    const cardStack = document.getElementById('cardStack');
    const progressBar = document.getElementById('progress-bar');
    const totalCount = document.getElementById('total-count');
    const knownCount = document.getElementById('known-count');
    
    // Initialize counts
    totalCount.textContent = allVocabs.length;
    updateKnownCount();
    
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
                    <div id="word-display">${escapeHtml(vocab.word)}</div>
                    ${vocab.phonetic ? `<div class="phonetic">[${escapeHtml(vocab.phonetic)}]</div>` : ''}
                    <button class="btn btn-sm btn-outline-primary mt-2 btn-audio">
                        <i class="bi bi-volume-up"></i> Nghe
                    </button>
                </div>
                <div class="card-back">
                    <div class="card-back-content">
                        <div class="vocab-info"><strong>Nghĩa:</strong> ${nl2br(escapeHtml(vocab.meaning))}</div>
                        ${vocab.note ? `<div class="vocab-info"><strong>Ghi chú:</strong> ${nl2br(escapeHtml(vocab.note))}</div>` : ''}
                        ${vocab.plural ? `<div class="vocab-info"><strong>Số nhiều:</strong> ${escapeHtml(vocab.plural)}</div>` : ''}
                        ${vocab.genus ? `<div class="vocab-info"><strong>Giống:</strong> ${escapeHtml(vocab.genus)}</div>` : ''}
                    </div>
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
        
        updateUI();
    }
    
    function updateUI() {
        if (!allVocabs[currentIndex]) return;
        
        // Update progress bar
        const progressPercent = ((currentIndex + 1) / allVocabs.length) * 100;
        progressBar.style.width = `${progressPercent}%`;
        
        // Update known count
        updateKnownCount();
    }
    
    function updateKnownCount() {
        const known = allVocabs.filter(v => v.status === 'known').length;
        knownCount.textContent = known;
    }
    
    function speakWord(text) {
        if ('speechSynthesis' in window) {
            speechSynthesis.cancel();
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'de-DE';
            utterance.rate = 0.8;
            speechSynthesis.speak(utterance);
        }
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
    
    document.getElementById('btn-flip').addEventListener('click', () => {
        const topCard = cardStack.lastChild;
        if (topCard) {
            topCard.classList.toggle('flipped');
        }
    });
    
    document.getElementById('btn-known').addEventListener('click', () => {
        if (!isAnimating) {
            processSwipe('known');
        }
    });
    
    document.getElementById('btn-unknown').addEventListener('click', () => {
        if (!isAnimating) {
            processSwipe('unknown');
        }
    });
    
    document.getElementById('btn-next').addEventListener('click', () => {
        if (!isAnimating) processSwipe(allVocabs[currentIndex]?.status || 'unknown');
    });
    
    document.getElementById('btn-prev').addEventListener('click', () => {
        if (isAnimating || allVocabs.length < 2) return;
        currentIndex = (currentIndex - 1 + allVocabs.length) % allVocabs.length;
        updateCardStack();
    });
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.innerText = text;
        return div.innerHTML;
    }
    
    function nl2br(str) {
        if (!str) return '';
        return str.replace(/(\r\n|\r|\n)/g, '<br>');
    }
    
    function processSwipe(status) {
        const currentCardEl = cardStack.lastChild;
        if(currentCardEl) {
            // Add swipe animation
            currentCardEl.classList.add('card--out');
            if (status === 'known') {
                currentCardEl.style.transform = 'translateX(200%) rotate(30deg)';
            } else {
                currentCardEl.style.transform = 'translateX(-200%) rotate(-30deg)';
            }
            
            // Show indicator
            const indicator = currentCardEl.querySelector(`.swipe-indicator.${status === 'known' ? 'right' : 'left'}`);
            if (indicator) {
                indicator.style.opacity = '1';
            }
        }
        
        updateStatusOnServer(allVocabs[currentIndex].id, status);
        allVocabs[currentIndex].status = status;
        currentIndex = (currentIndex + 1) % allVocabs.length;
        
        setTimeout(() => {
            updateCardStack();
            isAnimating = false;
        }, 400); 
    }
    
    if(allVocabs && allVocabs.length > 0) {
        updateCardStack();
    } else {
        document.getElementById('cardStackContainer').innerHTML = `
            <div class="empty-state">
                <i class="bi bi-book"></i>
                <h3>Sổ tay trống</h3>
                <p>Chưa có từ vựng nào trong sổ tay này để học.</p>
            </div>
        `;
        document.querySelector('.action-buttons').style.display = 'none';
        document.querySelector('.status-buttons').style.display = 'none';
        document.querySelector('.progress-container').style.display = 'none';
        document.querySelector('.stats-container').style.display = 'none';
    }
    
    // Keyboard shortcuts
    document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') {
            document.getElementById('btn-prev').click();
        } else if (e.key === 'ArrowRight') {
            document.getElementById('btn-next').click();
        } else if (e.key === ' ' || e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('btn-flip').click();
        } else if (e.key === 'k' || e.key === 'K') {
            document.getElementById('btn-known').click();
        } else if (e.key === 'u' || e.key === 'U') {
            document.getElementById('btn-unknown').click();
        }
    });
});
</script>
</body>
</html>

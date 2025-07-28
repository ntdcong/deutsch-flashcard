<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Trang chủ - Germanly</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --btn-gradient: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            --hover-gradient: linear-gradient(90deg, #ff6b6b 0%, #ffa502 100%);
        }

        body {
            background: var(--primary-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Montserrat', 'Segoe UI', sans-serif;
            padding: 1rem;
            overflow-x: hidden;
        }

        .home-card {
            background: #fff;
            border-radius: 2rem;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.2);
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 500px;
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: all 0.4s ease;
        }

        .home-card::before {
            content: "";
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
            z-index: 0;
            pointer-events: none;
        }

        .logo {
            font-size: 2.8rem;
            font-weight: 900;
            color: #ff6b6b;
            letter-spacing: 2px;
            text-shadow: 2px 2px 0 #ffd166;
            margin-bottom: 0.5rem;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .tagline {
            font-size: 1.1rem;
            color: #555;
            margin-bottom: 1.5rem;
            font-style: italic;
        }

        .meme-img {
            max-width: 300px;
            height: auto;
            border-radius: 1rem;
            border: 4px dashed #ffa502;
            margin-bottom: 1.8rem;
            box-shadow: 0 6px 16px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .meme-img:hover {
            transform: scale(1.05) rotate(2deg);
        }

        .option-list {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
            margin-bottom: 2rem;
        }

        .option-btn {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
            padding: 1.1rem 1.5rem;
            border-radius: 1.2rem;
            border: none;
            background: var(--btn-gradient);
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 6px 16px rgba(118, 75, 162, 0.2);
            text-decoration: none;
            position: relative;
            overflow: hidden;
        }

        .option-btn::after {
            content: "👉";
            position: absolute;
            right: 20px;
            opacity: 0;
            transition: all 0.3s ease;
        }

        .option-btn:hover::after {
            opacity: 1;
            right: 15px;
        }

        .option-btn:hover {
            background: var(--hover-gradient);
            box-shadow: 0 8px 20px rgba(255, 107, 107, 0.3);
            transform: translateY(-3px);
        }

        .footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
            color: #888;
        }

        .footer a {
            color: #667eea;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="home-card mx-auto">
        <div class="logo">GERMANLY</div>
        <img src="assets/meme.jpg" alt="Germanly Meme" class="img-fluid meme-img mx-auto">

        <div class="option-list">
            <a href="dashboard.php" class="option-btn">
                <i class="bi bi-collection"></i> Flashcard & Quiz
            </a>
            <a href="cases_detail.php" class="option-btn">
                <i class="bi bi-shuffle"></i> Học "Biến Cách"
            </a>
            <a href="basic_knowledge.php" class="option-btn">
                <i class="bi bi-book"></i> Kiến Thức Căn Bản
            </a>
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
            <a href="admin_dashboard.php" class="option-btn" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);">
                <i class="bi bi-shield-check"></i> Admin Dashboard
            </a>
            <?php endif; ?>
        </div>

        <div class="footer">
            Made with ❤️ & 🍕 by <a href="https://www.facebook.com/di.cieng">Duy Công</a> • © <?= date('Y') ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
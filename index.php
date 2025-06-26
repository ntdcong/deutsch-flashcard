<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Flashcard Tiếng Đức</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(120deg, #e0eafc, #cfdef3);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: "Segoe UI", sans-serif;
        }

        .hero-box {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            padding: 3rem 2rem;
            max-width: 600px;
            width: 100%;
            text-align: center;
            transition: all 0.3s ease-in-out;
        }

        .hero-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .logo {
            font-size: 2.5rem;
            font-weight: 700;
            color: #0d6efd;
        }

        .btn-primary, .btn-outline-primary {
            transition: all 0.2s ease;
        }

        .btn-lg i {
            margin-right: 0.5rem;
        }

        .footer {
            margin-top: 2rem;
            font-size: 0.9rem;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="hero-box">
        <div class="logo mb-3">
            🇩🇪 Flashcard Tiếng Đức
        </div>
        <h2 class="mb-3">Học từ vựng tiếng Đức hiện đại và hiệu quả</h2>
        <p class="mb-4 text-muted">Tạo sổ tay cá nhân, thêm từ, học với flashcard và quiz. Giao diện đẹp, hỗ trợ nhập Excel!</p>

        <div class="d-grid gap-3 d-md-flex justify-content-md-center">
            <a href="login.php" class="btn btn-primary btn-lg">
                <i class="bi bi-box-arrow-in-right"></i> Đăng nhập
            </a>
            <a href="register.php" class="btn btn-outline-primary btn-lg">
                <i class="bi bi-person-plus"></i> Đăng ký
            </a>
        </div>

        <div class="footer mt-5">&copy; <?= date('Y') ?> Flashcard Tiếng Đức</div>
    </div>

    <!-- Bootstrap JS (optional if using dropdown, modal, etc.) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

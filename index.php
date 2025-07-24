<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: home.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Flashcard Tiếng Đức - Học từ vựng hiệu quả</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Ứng dụng học từ vựng tiếng Đức với flashcard và quiz">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .main-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 3rem 2.5rem;
            max-width: 480px;
            width: 100%;
            text-align: center;
        }

        .logo {
            font-size: 2.5rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .subtitle {
            color: #6c757d;
            font-size: 0.95rem;
            margin-bottom: 2rem;
            font-weight: 500;
        }

        .main-title {
            font-size: 1.75rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1rem;
            line-height: 1.3;
        }

        .description {
            color: #6c757d;
            font-size: 1.1rem;
            margin-bottom: 2.5rem;
            line-height: 1.6;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 2.5rem;
            padding: 1.5rem 0;
            border-top: 1px solid #e9ecef;
            border-bottom: 1px solid #e9ecef;
        }

        .feature {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .feature i {
            font-size: 1.8rem;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .btn-group-custom {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .btn-custom {
            flex: 1;
            padding: 1rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
        }

        .btn-primary-custom {
            background: #667eea;
            color: white;
            border: 2px solid #667eea;
        }

        .btn-primary-custom:hover {
            background: #5a67d8;
            border-color: #5a67d8;
            color: white;
            transform: translateY(-1px);
        }

        .btn-secondary-custom {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-secondary-custom:hover {
            background: #667eea;
            color: white;
            transform: translateY(-1px);
        }

        .footer {
            color: #6c757d;
            font-size: 0.9rem;
        }

        /* Mobile optimizations */
        @media (max-width: 768px) {
            body {
                padding: 0.5rem;
            }

            .main-card {
                padding: 2.5rem 2rem;
            }

            .logo {
                font-size: 2.2rem;
            }

            .main-title {
                font-size: 1.5rem;
            }

            .description {
                font-size: 1rem;
            }

            .btn-group-custom {
                flex-direction: column;
                gap: 0.75rem;
            }

            .features {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .feature {
                flex-direction: row;
                text-align: left;
                gap: 1rem;
            }

            .feature i {
                margin-bottom: 0;
                flex-shrink: 0;
            }
        }

        @media (max-width: 480px) {
            .main-card {
                padding: 2rem 1.5rem;
            }

            .logo {
                font-size: 2rem;
            }

            .main-title {
                font-size: 1.4rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-card">
        <div class="logo">
            <i class="bi bi-card-text me-2"></i>Flashcard
        </div>
        <div class="subtitle">Tiếng Đức</div>

        <h1 class="main-title">Học từ vựng hiệu quả với flashcard</h1>
        <p class="description">
            Tạo bộ thẻ từ vựng cá nhân, luyện tập với flashcard và kiểm tra kiến thức bằng quiz.
        </p>

        <div class="features">
            <div class="feature">
                <i class="bi bi-lightning"></i>
                <span>Học nhanh</span>
            </div>
            <div class="feature">
                <i class="bi bi-graph-up"></i>
                <span>Theo dõi tiến độ</span>
            </div>
            <div class="feature">
                <i class="bi bi-phone"></i>
                <span>Mọi thiết bị</span>
            </div>
        </div>

        <div class="btn-group-custom">
            <a href="login.php" class="btn-custom btn-primary-custom">
                <i class="bi bi-box-arrow-in-right"></i>
                Đăng nhập
            </a>
            <a href="register.php" class="btn-custom btn-secondary-custom">
                <i class="bi bi-person-plus"></i>
                Đăng ký
            </a>
        </div>

        <div class="footer">
            © <?= date('Y') ?> By Duy Công
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
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
    <title>Flashcard Tiếng Đức - Học từ vựng hiệu quả</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Ứng dụng học từ vựng tiếng Đức với flashcard và quiz">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts - Nunito -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #6c5ce7;
            --secondary-color: #a29bfe;
            --accent-color: #fd79a8;
            --text-color: #2d3436;
            --light-color: #dfe6e9;
            --dark-color: #2d3436;
            --card-shadow: 0 10px 30px rgba(108, 92, 231, 0.2);
            --button-shadow: 0 10px 20px rgba(108, 92, 231, 0.3);
            --hover-transform: translateY(-3px);
        }

        * {
            transition: all 0.3s ease;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background: linear-gradient(135deg, #a29bfe 0%, #6c5ce7 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            color: var(--text-color);
        }

        .main-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 1.5rem;
            box-shadow: var(--card-shadow);
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 500px;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: fadeIn 0.8s ease-out;
        }

        .main-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 60%);
            transform: rotate(30deg);
            z-index: 0;
        }

        .main-card > * {
            position: relative;
            z-index: 1;
        }

        .logo {
            font-size: 2.4rem;
            font-weight: 800;
            color: var(--primary-color);
            letter-spacing: 1px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
            display: inline-block;
            position: relative;
            margin-bottom: 0.5rem;
        }

        .logo i {
            color: var(--accent-color);
            font-size: 0.9em;
            animation: pulse 2s infinite;
            vertical-align: middle;
            margin-right: 0.2rem;
        }

        .subtitle {
            color: var(--accent-color);
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .main-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 1rem;
            line-height: 1.3;
        }

        .description {
            color: #6c757d;
            font-size: 1.1rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 2.5rem;
            padding: 1.5rem;
            background: rgba(108, 92, 231, 0.05);
            border-radius: 1rem;
        }

        .feature {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: var(--primary-color);
            font-size: 0.95rem;
            font-weight: 600;
        }

        .feature i {
            font-size: 2rem;
            color: var(--accent-color);
            margin-bottom: 0.75rem;
        }

        .btn-group-custom {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .btn-custom {
            flex: 1;
            padding: 0.9rem 1.2rem;
            border-radius: 1rem;
            font-weight: 700;
            font-size: 1.1rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            box-shadow: var(--button-shadow);
            position: relative;
            overflow: hidden;
            border: none;
        }

        .btn-custom::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 70%);
            opacity: 0;
            transform: scale(1);
            transition: transform 0.6s, opacity 0.6s;
        }

        .btn-primary-custom {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .btn-primary-custom:hover {
            transform: var(--hover-transform);
            box-shadow: 0 15px 25px rgba(108, 92, 231, 0.4);
            background: linear-gradient(45deg, var(--secondary-color), var(--primary-color));
            color: white;
        }

        .btn-primary-custom:active::after {
            opacity: 1;
            transform: scale(0);
            transition: 0s;
        }

        .btn-secondary-custom {
            background: white;
            color: var(--primary-color);
            border: 2px solid var(--primary-color) !important;
        }

        .btn-secondary-custom:hover {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            transform: var(--hover-transform);
            box-shadow: 0 15px 25px rgba(108, 92, 231, 0.4);
        }

        .footer {
            color: rgba(45, 52, 54, 0.7);
            font-size: 0.9rem;
            font-weight: 500;
            text-align: center;
        }

        .footer span {
            color: var(--accent-color);
            font-weight: 700;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        /* Mobile optimizations */
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            .main-card {
                padding: 2rem 1.5rem;
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
                gap: 1.2rem;
                padding: 1.2rem;
            }

            .feature {
                flex-direction: row;
                text-align: left;
                gap: 1rem;
                align-items: flex-start;
            }

            .feature i {
                margin-bottom: 0;
                flex-shrink: 0;
            }
        }

        @media (max-width: 480px) {
            .main-card {
                padding: 1.8rem 1.2rem;
            }

            .logo {
                font-size: 2rem;
            }

            .main-title {
                font-size: 1.4rem;
            }

            .btn-custom {
                padding: 0.8rem 1rem;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-card">
        <div class="text-center w-100">
            <div class="logo">
                <i></i> GERMANLY
            </div>
            <div class="subtitle">Cùng Học Tiếng Đức</div>

            <p class="description">
                Tạo bộ thẻ từ vựng cá nhân, luyện tập với flashcard và kiểm tra kiến thức bằng quiz.
            </p>

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
                &copy; <?= date('Y') ?> Made with ❤️ & 🍕 by <span>Duy Công</span>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
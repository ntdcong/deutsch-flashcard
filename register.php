<?php
session_start();
require 'db.php';
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
$errors = [];
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email không hợp lệ.";
    if (strlen($password) < 6) $errors[] = "Mật khẩu phải có ít nhất 6 ký tự.";
    if ($password !== $confirm) $errors[] = "Xác nhận mật khẩu không khớp.";
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = "Email này đã được đăng ký.";
        }
    }
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, 'user')");
        $stmt->execute([$email, $hash]);
        $_SESSION['user_id'] = $pdo->lastInsertId();
        header('Location: home.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký - Germanly</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6c5ce7;
            --secondary-color: #a29bfe;
            --accent-color: #fd79a8;
            --text-color: #2d3436;
            --light-color: #dfe6e9;
            --dark-color: #2d3436;
            --success-color: #00b894;
            --card-shadow: 0 10px 30px rgba(108, 92, 231, 0.2);
            --input-shadow: 0 5px 15px rgba(108, 92, 231, 0.1);
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

        .register-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 1.5rem;
            box-shadow: var(--card-shadow);
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 450px;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: fadeIn 0.8s ease-out;
        }

        .register-card::before {
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

        .register-card > * {
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
        }

        .logo i {
            color: var(--accent-color);
            font-size: 0.9em;
            animation: pulse 2s infinite;
            vertical-align: middle;
            margin-right: 0.2rem;
        }

        h4 {
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
            font-size: 1.5rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .form-control {
            border: none;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 1rem;
            padding: 0.8rem 1.2rem;
            font-size: 1rem;
            box-shadow: var(--input-shadow);
            border: 1px solid rgba(108, 92, 231, 0.1);
            margin-bottom: 0.5rem;
        }

        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.25);
            border-color: var(--primary-color);
            transform: var(--hover-transform);
        }

        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
            width: 100%;
        }

        .position-relative {
            width: 100%;
            position: relative;
            display: block;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 1rem;
            padding: 0.8rem 1.5rem;
            font-weight: 700;
            font-size: 1.1rem;
            box-shadow: var(--button-shadow);
            position: relative;
            overflow: hidden;
        }

        .btn-primary:hover {
            transform: var(--hover-transform);
            box-shadow: 0 15px 25px rgba(108, 92, 231, 0.4);
            background: linear-gradient(45deg, var(--secondary-color), var(--primary-color));
        }

        .btn-primary::after {
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

        .btn-primary:active::after {
            opacity: 1;
            transform: scale(0);
            transition: 0s;
        }

        .alert-danger {
            background-color: rgba(253, 121, 168, 0.15);
            border: none;
            color: #e84393;
            border-radius: 1rem;
            font-weight: 600;
            animation: shake 0.5s ease-in-out;
        }

        .alert-danger ul {
            padding-left: 1.5rem;
        }

        .alert-danger li {
            margin-bottom: 0.3rem;
        }

        a {
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: none;
            position: relative;
        }

        a:hover {
            color: var(--accent-color);
        }

        a::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 2px;
            bottom: -2px;
            left: 0;
            background-color: var(--accent-color);
            transform: scaleX(0);
            transform-origin: bottom right;
            transition: transform 0.3s;
        }

        a:hover::after {
            transform: scaleX(1);
            transform-origin: bottom left;
        }

        .footer {
            text-align: center;
            margin-top: 2rem;
            font-size: 0.9rem;
            color: rgba(45, 52, 54, 0.7);
            font-weight: 500;
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

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }

        @media (max-width: 576px) {
            .register-card {
                padding: 2rem 1.5rem;
                margin: 0 1rem;
                width: 90%;
                max-width: 100%;
            }
            
            .logo {
                font-size: 2rem;
            }
            
            h4 {
                font-size: 1.3rem;
            }
            
            .form-control {
                padding: 0.7rem 1rem;
                width: 100%;
            }
            
            .btn-primary {
                padding: 0.7rem 1.2rem;
                width: 100%;
            }
            
            .input-group, .position-relative, form {
                width: 100%;
                display: block;
            }
        }
    </style>
</head>
<body>
    <div class="register-card mx-auto">
        <div class="text-center mb-4 w-100">
            <div class="logo mb-2"><i class="bi bi-lightning-charge"></i> Germanly</div>
            <h4>Đăng ký tài khoản</h4>
            <p class="text-muted">Tạo tài khoản để bắt đầu học tiếng Đức!</p>
        </div>
        
        <?php if ($errors): ?>
            <div class="alert alert-danger mb-4">
                <ul class="mb-0">
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="post" novalidate class="w-100">
            <div class="input-group">
                <div class="position-relative">                  
                    <input type="email" name="email" id="email" class="form-control" 
                           placeholder="Nhập email của bạn" value="<?= htmlspecialchars($email) ?>" required>
                </div>
            </div>
            
            <div class="input-group">
                <div class="position-relative">
                    <input type="password" name="password" id="password" class="form-control input-with-icon" 
                           placeholder="Tạo mật khẩu (ít nhất 6 ký tự)" required minlength="6">
                </div>
            </div>
            
            <div class="input-group">
                <div class="position-relative">
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control input-with-icon" 
                           placeholder="Xác nhận mật khẩu" required>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary w-100 mb-3">
                Đăng ký
            </button>
        </form>
        
        <div class="mt-4 text-center">
            <p>Đã có tài khoản? <a href="login.php" class="ms-1">Đăng nhập</a></p>
        </div>
        
        <div class="footer">
            &copy; <?= date('Y') ?> Made with ❤️ & 🍕 by Duy Công
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

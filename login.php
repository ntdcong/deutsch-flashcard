<?php
session_start();
require 'db.php';
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare('SELECT id, password FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        header('Location: dashboard.php');
        exit;
    } else {
        $message = 'Email hoặc mật khẩu không đúng!';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập - Flashcard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(120deg, #e0eafc, #cfdef3); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { background: #fff; border-radius: 1.2rem; box-shadow: 0 8px 32px rgba(0,0,0,0.13); padding: 2.2rem 1.5rem 1.5rem 1.5rem; width: 100%; max-width: 400px; }
        .logo { font-size: 2.2rem; font-weight: bold; color: #0d6efd; letter-spacing: 1px; }
        .form-label { font-weight: 500; }
        .form-control:focus { box-shadow: 0 0 0 0.2rem rgba(13,110,253,.18); }
        .btn-primary { font-weight: 600; border-radius: 0.7rem; font-size: 1.1rem; }
        .footer { text-align: center; margin-top: 1.5rem; font-size: 0.93rem; color: #888; }
        @media (max-width: 576px) { .login-card { padding: 1.2rem 0.5rem; } }
    </style>
</head>
<body>
    <div class="login-card mx-auto">
        <div class="text-center mb-4">
            <div class="logo mb-2"><i class="bi bi-lightning-charge text-warning"></i> Flashcard</div>
            <h4 class="mb-0">Đăng nhập</h4>
        </div>
        <?php if ($message): ?>
            <div class="alert alert-danger text-center py-2"><?= $message ?></div>
        <?php endif; ?>
        <form method="post" novalidate>
            <div class="mb-3">
                <label class="form-label" for="email"><i class="bi bi-envelope-fill me-1"></i>Email</label>
                <input type="email" class="form-control" name="email" id="email" required autofocus autocomplete="username">
            </div>
            <div class="mb-3">
                <label class="form-label" for="password"><i class="bi bi-lock-fill me-1"></i>Mật khẩu</label>
                <input type="password" class="form-control" name="password" id="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary w-100 mb-2">
                <i class="bi bi-box-arrow-in-right me-1"></i> Đăng nhập
            </button>
        </form>
        <div class="mt-3 text-center">
            Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a>
        </div>
        <div class="footer">&copy; <?= date('Y') ?> By Duy Công</div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

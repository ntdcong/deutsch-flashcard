<?php
session_start();
require 'db.php';
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
$errors = [];
$username = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (!$username) $errors[] = "Tên người dùng là bắt buộc.";
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
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $hash]);
        $_SESSION['user_id'] = $pdo->lastInsertId();
        header('Location: dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký - Flashcard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(120deg, #e0eafc, #cfdef3); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .register-card { background: #fff; border-radius: 1.2rem; box-shadow: 0 8px 32px rgba(0,0,0,0.13); padding: 2.2rem 1.5rem 1.5rem 1.5rem; width: 100%; max-width: 420px; }
        .logo { font-size: 2.2rem; font-weight: bold; color: #0d6efd; letter-spacing: 1px; }
        .form-label { font-weight: 500; }
        .form-control:focus { box-shadow: 0 0 0 0.2rem rgba(13,110,253,.18); }
        .btn-primary { font-weight: 600; border-radius: 0.7rem; font-size: 1.1rem; }
        .footer { text-align: center; margin-top: 1.5rem; font-size: 0.93rem; color: #888; }
        @media (max-width: 576px) { .register-card { padding: 1.2rem 0.5rem; } }
    </style>
</head>
<body>
    <div class="register-card mx-auto">
        <div class="text-center mb-4">
            <div class="logo mb-2"><i class="bi bi-lightning-charge text-warning"></i> Flashcard</div>
            <h4 class="mb-0">Đăng ký tài khoản</h4>
        </div>
        <?php if ($errors): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form method="post" novalidate>
            <div class="mb-3">
                <label for="email" class="form-label"><i class="bi bi-envelope-fill me-1"></i> Email</label>
                <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label"><i class="bi bi-lock-fill me-1"></i> Mật khẩu</label>
                <input type="password" name="password" id="password" class="form-control" required minlength="6">
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label"><i class="bi bi-shield-lock-fill me-1"></i> Xác nhận mật khẩu</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
            </div>
            <button class="btn btn-primary w-100 mb-2">Đăng ký</button>
        </form>
        <div class="text-center mt-3">
            Đã có tài khoản? <a href="login.php">Đăng nhập</a>
        </div>
        <div class="footer">&copy; <?= date('Y') ?> By Duy Công</div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

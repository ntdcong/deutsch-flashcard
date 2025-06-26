<?php
session_start();
require 'db.php';

$errors = [];
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // Kiểm tra dữ liệu đầu vào
    if (!$username) $errors[] = "Tên người dùng là bắt buộc.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email không hợp lệ.";
    if (strlen($password) < 6) $errors[] = "Mật khẩu phải có ít nhất 6 ký tự.";
    if ($password !== $confirm) $errors[] = "Xác nhận mật khẩu không khớp.";

    // Kiểm tra email đã tồn tại chưa
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = "Email này đã được đăng ký.";
        }
    }

    // Đăng ký nếu không có lỗi
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
    <title>Đăng ký - Flashcard Đức</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(to right, #e0eafc, #cfdef3);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: "Segoe UI", sans-serif;
        }

        .card {
            border-radius: 1rem;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            padding: 2rem;
            width: 100%;
            max-width: 420px;
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(13,110,253,.25);
        }

        .form-label {
            font-weight: 500;
        }

        .btn-primary {
            transition: all 0.2s ease-in-out;
        }

        .logo {
            font-size: 2rem;
            font-weight: bold;
            color: #0d6efd;
        }

        .footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
            color: #888;
        }
    </style>
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="mx-auto bg-white p-4 rounded shadow" style="max-width: 500px;">
        <h3 class="mb-4 text-center">Đăng ký tài khoản</h3>

        <?php if ($errors): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-3">
                <label for="username" class="form-label">Tên người dùng</label>
                <input type="text" name="username" id="username" class="form-control"
                       value="<?= htmlspecialchars($username) ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Địa chỉ email</label>
                <input type="email" name="email" id="email" class="form-control"
                       value="<?= htmlspecialchars($email) ?>" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Mật khẩu</label>
                <input type="password" name="password" id="password" class="form-control" required minlength="6">
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Xác nhận mật khẩu</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
            </div>
            <button class="btn btn-primary w-100">Đăng ký</button>
        </form>

        <div class="text-center mt-3">
            Đã có tài khoản? <a href="login.php">Đăng nhập</a>
        </div>
    </div>
</div>
</body>
</html>

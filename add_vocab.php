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

$message = '';

// Thêm từ mới
if (isset($_POST['add_vocab'])) {
    $word = trim($_POST['word'] ?? '');
    $phonetic = trim($_POST['phonetic'] ?? '');
    $meaning = trim($_POST['meaning'] ?? '');
    $note = trim($_POST['note'] ?? '');
    $plural = trim($_POST['plural'] ?? '');
    $genus = trim($_POST['genus'] ?? '');
    if ($word && $meaning) {
        $stmt = $pdo->prepare('INSERT INTO vocabularies (notebook_id, word, phonetic, meaning, note, plural, genus) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$notebook_id, $word, $phonetic, $meaning, $note, $plural, $genus]);
        $message = '✅ Đã thêm từ mới!';
    } else {
        $message = '❌ Vui lòng nhập từ vựng và nghĩa!';
    }
}

// Xóa từ
if (isset($_GET['delete'])) {
    $vocab_id = (int)$_GET['delete'];
    $pdo->prepare('DELETE FROM learning_status WHERE vocab_id=?')->execute([$vocab_id]);
    $pdo->prepare('DELETE FROM vocabularies WHERE id=? AND notebook_id=?')->execute([$vocab_id, $notebook_id]);
    $message = '🗑️ Đã xóa từ!';
}

// Lấy danh sách từ vựng
$stmt = $pdo->prepare('SELECT * FROM vocabularies WHERE notebook_id=? ORDER BY created_at DESC');
$stmt->execute([$notebook_id]);
$vocabs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý từ vựng</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background: #f4f7fb;
            font-family: "Segoe UI", sans-serif;
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, .25);
        }

        .card-form {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        }

        .table th, .table td {
            vertical-align: middle;
        }

        .btn-sm i {
            margin-right: 4px;
        }

        .navbar-brand {
            font-weight: bold;
            color: #0d6efd;
        }

        .navbar-light {
            background: linear-gradient(to right, #e0eafc, #cfdef3);
        }
    </style>
</head>
<body>

<nav class="navbar navbar-light">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php"><i class="bi bi-arrow-left"></i> Trở lại</a>
        <span class="navbar-text">
            Sổ tay: <strong><?= htmlspecialchars($notebook['title']) ?></strong>
        </span>
    </div>
</nav>

<div class="container my-4">
    <?php if ($message): ?>
        <div class="alert alert-info"><?= $message ?></div>
    <?php endif; ?>

    <div class="card-form mb-4">
        <h5 class="mb-3"><i class="bi bi-journal-plus text-primary"></i> Thêm từ vựng mới</h5>
        <form method="post" class="row g-2">
            <div class="col-md-2 col-6">
                <input type="text" name="word" class="form-control" placeholder="Từ vựng" required>
            </div>
            <div class="col-md-2 col-6">
                <input type="text" name="phonetic" class="form-control" placeholder="Phiên âm">
            </div>
            <div class="col-md-2 col-6">
                <input type="text" name="meaning" class="form-control" placeholder="Nghĩa" required>
            </div>
            <div class="col-md-2 col-6">
                <input type="text" name="note" class="form-control" placeholder="Ghi chú">
            </div>
            <div class="col-md-2 col-6">
                <input type="text" name="plural" class="form-control" placeholder="Số nhiều">
            </div>
            <div class="col-md-1 col-4">
                <input type="text" name="genus" class="form-control" placeholder="Giống">
            </div>
            <div class="col-md-1 col-4">
                <button class="btn btn-success w-100" name="add_vocab"><i class="bi bi-plus-circle"></i> Thêm</button>
            </div>
        </form>
    </div>

    <h5 class="mb-3">📚 Danh sách từ vựng</h5>
    <div class="table-responsive">
        <table class="table table-bordered table-hover table-striped align-middle">
            <thead class="table-light">
                <tr class="text-center">
                    <th>Từ</th>
                    <th>Phiên âm</th>
                    <th>Nghĩa</th>
                    <th>Ghi chú</th>
                    <th>Số nhiều</th>
                    <th>Giống</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($vocabs as $v): ?>
                <tr>
                    <td><?= htmlspecialchars($v['word']) ?></td>
                    <td><?= htmlspecialchars($v['phonetic']) ?></td>
                    <td><?= htmlspecialchars($v['meaning']) ?></td>
                    <td><?= htmlspecialchars($v['note']) ?></td>
                    <td><?= htmlspecialchars($v['plural']) ?></td>
                    <td><?= htmlspecialchars($v['genus']) ?></td>
                    <td class="text-center">
                        <a href="?notebook_id=<?= $notebook_id ?>&delete=<?= $v['id'] ?>" class="btn btn-danger btn-sm"
                           onclick="return confirm('Bạn có chắc chắn muốn xoá từ này?');">
                           <i class="bi bi-trash"></i> Xoá
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($vocabs)): ?>
                <tr><td colspan="7" class="text-center text-muted">Chưa có từ nào trong sổ tay này.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

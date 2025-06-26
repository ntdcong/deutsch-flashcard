<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
$message = '';

// Thêm sổ tay
if (isset($_POST['add_notebook'])) {
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    if ($title) {
        $stmt = $pdo->prepare('INSERT INTO notebooks (user_id, title, description) VALUES (?, ?, ?)');
        $stmt->execute([$user_id, $title, $desc]);
        $message = 'Đã thêm sổ tay!';
    } else {
        $message = 'Vui lòng nhập tiêu đề!';
    }
}

// Xóa sổ tay
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare('DELETE FROM learning_status WHERE vocab_id IN (SELECT id FROM vocabularies WHERE notebook_id=?)')->execute([$id]);
    $pdo->prepare('DELETE FROM vocabularies WHERE notebook_id=?')->execute([$id]);
    $pdo->prepare('DELETE FROM notebooks WHERE id=? AND user_id=?')->execute([$id, $user_id]);
    $message = 'Đã xóa sổ tay!';
}

// Cập nhật sổ tay
if (isset($_POST['edit_notebook'])) {
    $id = (int)$_POST['notebook_id'];
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    if ($title) {
        $stmt = $pdo->prepare('UPDATE notebooks SET title=?, description=? WHERE id=? AND user_id=?');
        $stmt->execute([$title, $desc, $id, $user_id]);
        $message = 'Đã cập nhật sổ tay!';
    } else {
        $message = 'Vui lòng nhập tiêu đề!';
    }
}

// Lấy danh sách sổ tay
$stmt = $pdo->prepare('SELECT * FROM notebooks WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$user_id]);
$notebooks = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Sổ tay - Flashcard Đức</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f2f6fc; font-family: "Segoe UI", sans-serif; }
        .navbar { background: linear-gradient(to right, #e0eafc, #cfdef3); box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05); }
        .navbar-brand { font-weight: bold; font-size: 1.5rem; color: #0d6efd; }
        .card { border-radius: 1rem; transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
        .btn-sm i { margin-right: 4px; }
        .form-control:focus { box-shadow: 0 0 0 0.2rem rgba(13,110,253,.25); }
        .section-title { font-weight: 600; margin-bottom: 1rem; color: #333; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">🇩🇪 Flashcard Đức</a>
        <div class="d-flex">
            <a href="logout.php" class="btn btn-outline-danger">
                <i class="bi bi-box-arrow-right"></i> Đăng xuất
            </a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h2 class="section-title">📘 Sổ tay từ vựng của bạn</h2>
    <?php if ($message): ?><div class="alert alert-info"><?= $message ?></div><?php endif; ?>

    <form method="post" class="row g-3 align-items-center mb-4">
        <div class="col-md-4">
            <input type="text" name="title" class="form-control" placeholder="Tiêu đề sổ tay" required>
        </div>
        <div class="col-md-5">
            <input type="text" name="description" class="form-control" placeholder="Mô tả (tuỳ chọn)">
        </div>
        <div class="col-md-3">
            <button class="btn btn-success w-100" name="add_notebook">
                <i class="bi bi-journal-plus"></i> Thêm sổ tay
            </button>
        </div>
    </form>

    <div class="row">
        <?php if (count($notebooks) === 0): ?>
            <div class="col-12 text-center text-muted">Bạn chưa có sổ tay nào. Hãy tạo một sổ tay mới!</div>
        <?php endif; ?>
        <?php foreach ($notebooks as $nb): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?= htmlspecialchars($nb['title']) ?></h5>
                        <p class="card-text text-muted flex-grow-1"><?= nl2br(htmlspecialchars($nb['description'])) ?></p>

                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <a href="study_flashcard.php?notebook_id=<?= $nb['id'] ?>" class="btn btn-outline-warning btn-sm">
                                <i class="bi bi-card-text"></i> Flashcard
                            </a>
                            <a href="study_quiz.php?notebook_id=<?= $nb['id'] ?>" class="btn btn-outline-info btn-sm">
                                <i class="bi bi-question-circle"></i> Quiz
                            </a>
                        </div>

                        <div class="dropdown action-dropdown mt-auto">
                            <button class="btn btn-outline-secondary dropdown-toggle w-100" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-list"></i> Hành động
                            </button>
                            <ul class="dropdown-menu w-100">
                                <li><a class="dropdown-item" href="add_vocab.php?notebook_id=<?= $nb['id'] ?>">
                                    <i class="bi bi-pencil-square"></i> Quản lý từ</a></li>
                                <li><a class="dropdown-item" href="import_excel.php?notebook_id=<?= $nb['id'] ?>">
                                    <i class="bi bi-file-earmark-excel"></i> Import Excel</a></li>
                                <li><button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editModal<?= $nb['id'] ?>">
                                    <i class="bi bi-pencil"></i> Sửa sổ tay</button></li>
                                <li><a class="dropdown-item text-danger" href="?delete=<?= $nb['id'] ?>" onclick="return confirm('Bạn có chắc chắn muốn xoá sổ tay này?');">
                                    <i class="bi bi-trash"></i> Xoá</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal sửa sổ tay -->
            <div class="modal fade" id="editModal<?= $nb['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <form method="post" class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Chỉnh sửa sổ tay</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="notebook_id" value="<?= $nb['id'] ?>">
                            <div class="mb-3">
                                <label class="form-label">Tiêu đề</label>
                                <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($nb['title']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mô tả</label>
                                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($nb['description']) ?></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="edit_notebook" class="btn btn-primary">Lưu thay đổi</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$notebook = null;
$share_owner = null;

if (isset($_GET['code'])) {
    $share_code = $_GET['code'];
    
    // Lấy thông tin chia sẻ
    $stmt = $pdo->prepare('SELECT s.*, n.title, n.description, n.group_id, u.id as share_user_id 
                          FROM notebook_shares s 
                          JOIN notebooks n ON s.notebook_id = n.id 
                          JOIN users u ON s.user_id = u.id 
                          WHERE s.share_code = ?');
    $stmt->execute([$share_code]);
    $share = $stmt->fetch();
    
    if (!$share) {
        $message = 'Link chia sẻ không hợp lệ hoặc đã hết hạn!';
    } else {
        $notebook = [
            'id' => $share['notebook_id'],
            'title' => $share['title'],
            'description' => $share['description'],
            'group_id' => $share['group_id']
        ];
        $share_owner = $share['share_user_id']; // Sử dụng user_id thay vì username
        
        // Kiểm tra nếu người dùng hiện tại là người chia sẻ
        if ($share['user_id'] == $user_id) {
            $message = 'Bạn không thể nhập sổ tay của chính mình!';
            $notebook = null;
        }
    }
}

// Xử lý nhập sổ tay
if (isset($_POST['import_notebook']) && isset($_POST['notebook_id']) && isset($_POST['share_code'])) {
    $notebook_id = (int)$_POST['notebook_id'];
    $share_code = $_POST['share_code'];
    $new_title = trim($_POST['title'] ?? '');
    $new_desc = trim($_POST['description'] ?? '');
    $group_id = $_POST['group_id'] !== '' ? (int)$_POST['group_id'] : null;
    
    // Kiểm tra lại thông tin chia sẻ
    $stmt = $pdo->prepare('SELECT s.* FROM notebook_shares s WHERE s.share_code = ? AND s.notebook_id = ?');
    $stmt->execute([$share_code, $notebook_id]);
    $share = $stmt->fetch();
    
    if (!$share) {
        $message = 'Link chia sẻ không hợp lệ!';
    } else if ($share['user_id'] == $user_id) {
        $message = 'Bạn không thể nhập sổ tay của chính mình!';
    } else {
        // Bắt đầu transaction
        $pdo->beginTransaction();
        
        try {
            // Tạo sổ tay mới
            $stmt = $pdo->prepare('INSERT INTO notebooks (user_id, title, description, group_id) VALUES (?, ?, ?, ?)');
            $stmt->execute([$user_id, $new_title, $new_desc, $group_id]);
            $new_notebook_id = $pdo->lastInsertId();
            
            // Sao chép từ vựng
            $stmt = $pdo->prepare('INSERT INTO vocabularies (notebook_id, word, meaning, note, created_at) 
                                  SELECT ?, word, meaning, note, NOW() 
                                  FROM vocabularies 
                                  WHERE notebook_id = ?');
            $stmt->execute([$new_notebook_id, $notebook_id]);
            
            $pdo->commit();
            $message = 'Đã nhập sổ tay thành công!';
            header('Location: dashboard.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = 'Có lỗi xảy ra: ' . $e->getMessage();
        }
    }
}

// Lấy danh sách nhóm
$stmt = $pdo->prepare('SELECT * FROM notebook_groups WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$user_id]);
$groups = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Nhập sổ tay được chia sẻ - Flashcard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f2f6fc; font-family: 'Montserrat', 'Segoe UI', sans-serif; }
        .navbar { background: linear-gradient(to right,rgb(90, 97, 229),rgb(123, 244, 224)); box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05); }
        .navbar-brand { font-weight: bold; font-size: 1.5rem; color:rgb(255, 255, 255); }
        .import-card { background: #fff; border-radius: 1.2rem; box-shadow: 0 4px 24px rgba(0,0,0,0.07); padding: 2rem; margin-top: 2rem; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="home.php">GERMANLY</a>
        <div class="d-flex">
            <a href="dashboard.php" class="btn btn-outline-light me-2">
                <i class="bi bi-journals"></i> Sổ tay
            </a>
            <a href="logout.php" class="btn btn-outline-danger">
                <i class="bi bi-box-arrow-right"></i> Đăng xuất
            </a>
        </div>
    </div>
</nav>

<div class="container mt-4 mb-5">
    <div class="import-card">
        <h2 class="mb-4"><i class="bi bi-download"></i> Nhập sổ tay được chia sẻ</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-info"><?= $message ?></div>
        <?php endif; ?>
        
        <?php if ($notebook): ?>
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Thông tin sổ tay được chia sẻ</h5>
                </div>
                <div class="card-body">
                    <p><strong>Tiêu đề:</strong> <?= htmlspecialchars($notebook['title']) ?></p>
                    <p><strong>Mô tả:</strong> <?= nl2br(htmlspecialchars($notebook['description'])) ?></p>
                    <p><strong>Người chia sẻ:</strong> ID: <?= htmlspecialchars($share_owner) ?></p>
                </div>
            </div>
            
            <form method="post">
                <input type="hidden" name="notebook_id" value="<?= $notebook['id'] ?>">
                <input type="hidden" name="share_code" value="<?= htmlspecialchars($_GET['code']) ?>">
                
                <div class="mb-3">
                    <label class="form-label">Tiêu đề mới</label>
                    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($notebook['title']) ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Mô tả</label>
                    <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($notebook['description']) ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Nhóm</label>
                    <select name="group_id" class="form-select">
                        <option value="">-- Không thuộc nhóm --</option>
                        <?php foreach ($groups as $g): ?>
                            <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" name="import_notebook" class="btn btn-success"><i class="bi bi-download"></i> Nhập sổ tay</button>
                    <a href="dashboard.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Quay lại</a>
                </div>
            </form>
        <?php else: ?>
            <div class="text-center">
                <a href="dashboard.php" class="btn btn-primary"><i class="bi bi-arrow-left"></i> Quay lại</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
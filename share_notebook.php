<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$share_link = '';

if (isset($_GET['notebook_id'])) {
    $notebook_id = (int)$_GET['notebook_id'];
    
    // Kiểm tra sổ tay có thuộc về người dùng không
    $stmt = $pdo->prepare('SELECT * FROM notebooks WHERE id = ? AND user_id = ?');
    $stmt->execute([$notebook_id, $user_id]);
    $notebook = $stmt->fetch();
    
    if (!$notebook) {
        $message = 'Sổ tay không tồn tại hoặc bạn không có quyền chia sẻ!';
    } else {
        // Kiểm tra xem đã có mã chia sẻ chưa
        $stmt = $pdo->prepare('SELECT * FROM notebook_shares WHERE notebook_id = ? AND user_id = ?');
        $stmt->execute([$notebook_id, $user_id]);
        $share = $stmt->fetch();
        
        if ($share) {
            // Đã có mã chia sẻ, lấy ra
            $share_code = $share['share_code'];
        } else {
            // Tạo mã chia sẻ mới
            $share_code = md5($notebook_id . $user_id . time() . rand(1000, 9999));
            
            // Lưu vào CSDL
            $stmt = $pdo->prepare('INSERT INTO notebook_shares (notebook_id, user_id, share_code) VALUES (?, ?, ?)');
            $stmt->execute([$notebook_id, $user_id, $share_code]);
        }
        
        // Tạo link chia sẻ
        $share_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/import_shared.php?code=" . $share_code;
        $message = 'Đã tạo link chia sẻ thành công!';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chia sẻ sổ tay - Flashcard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f2f6fc; font-family: 'Montserrat', 'Segoe UI', sans-serif; }
        .navbar { background: linear-gradient(to right,rgb(90, 97, 229),rgb(123, 244, 224)); box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05); }
        .navbar-brand { font-weight: bold; font-size: 1.5rem; color:rgb(255, 255, 255); }
        .share-card { background: #fff; border-radius: 1.2rem; box-shadow: 0 4px 24px rgba(0,0,0,0.07); padding: 2rem; margin-top: 2rem; }
        .share-link { background: #f8f9fa; padding: 1rem; border-radius: 0.5rem; margin: 1rem 0; word-break: break-all; }
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
    <div class="share-card">
        <h2 class="mb-4"><i class="bi bi-share"></i> Chia sẻ sổ tay</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-info"><?= $message ?></div>
        <?php endif; ?>
        
        <?php if ($share_link): ?>
            <div class="mb-4">
                <h5>Link chia sẻ:</h5>
                <div class="share-link"><?= $share_link ?></div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" onclick="copyShareLink()"><i class="bi bi-clipboard"></i> Sao chép link</button>
                    <a href="dashboard.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Quay lại</a>
                </div>
            </div>
            <div class="alert alert-warning">
                <i class="bi bi-info-circle"></i> Lưu ý: Người nhận link có thể nhập sổ tay này vào tài khoản của họ. Link này không có thời hạn.
            </div>
        <?php else: ?>
            <div class="text-center">
                <a href="dashboard.php" class="btn btn-primary"><i class="bi bi-arrow-left"></i> Quay lại</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function copyShareLink() {
    const shareLink = document.querySelector('.share-link').textContent;
    navigator.clipboard.writeText(shareLink).then(() => {
        alert('Đã sao chép link chia sẻ!');
    });
}
</script>
</body>
</html>
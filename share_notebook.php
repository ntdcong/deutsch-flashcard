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
    $stmt = $pdo->prepare('SELECT title FROM notebooks WHERE id = ? AND user_id = ?');
    $stmt->execute([$notebook_id, $user_id]);
    $notebook = $stmt->fetch();
    
    if (!$notebook) {
        $message = 'Sổ tay không tồn tại hoặc bạn không có quyền chia sẻ!';
    } else {
        // Kiểm tra hoặc tạo mã chia sẻ
        $stmt = $pdo->prepare('SELECT share_code FROM notebook_shares WHERE notebook_id = ? AND user_id = ?');
        $stmt->execute([$notebook_id, $user_id]);
        $share = $stmt->fetch();
        
        if ($share) {
            $share_code = $share['share_code'];
        } else {
            $share_code = md5($notebook_id . $user_id . time() . rand(1000, 9999));
            $stmt = $pdo->prepare('INSERT INTO notebook_shares (notebook_id, user_id, share_code) VALUES (?, ?, ?)');
            $stmt->execute([$notebook_id, $user_id, $share_code]);
        }
        
        $share_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
                      "://" . $_SERVER['HTTP_HOST'] . "/import_shared.php?code=" . $share_code;
        $message = '✅ Đã tạo link chia sẻ thành công!';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chia sẻ sổ tay - GERMANLY</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: #f2f6fc;
            font-family: 'Montserrat', 'Segoe UI', sans-serif;
            color: #333;
        }
        .navbar {
            background: linear-gradient(to right, #5a61e5, #7bf4e0);
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
            padding: 0.75rem 0;
        }
        .navbar-brand {
            font-weight: 700;
            font-size: 1.6rem;
            color: white;
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }
        .share-card {
            background: #ffffff;
            border-radius: 1.5rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            padding: 2.5rem;
            margin-top: 2.5rem;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        .share-link {
            background: #f1f3f5;
            border: 1px solid #dee2e6;
            padding: 1rem;
            border-radius: 0.75rem;
            font-family: monospace;
            font-size: 0.95rem;
            word-break: break-all;
            color: #1a73e8;
        }
        .btn-custom {
            border-radius: 0.5rem;
            padding: 0.6rem 1.2rem;
            font-weight: 500;
        }
        .alert-warning {
            border-left: 4px solid #ffc107;
            background-color: #fffbeb;
            color: #856404;
        }
        .text-center a.btn {
            width: auto;
            min-width: 140px;
        }
        .icon-header {
            color: #5a61e5;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="home.php">GERMANLY</a>
            <div class="d-flex gap-2">
                <a href="dashboard.php" class="btn btn-outline-light btn-sm d-flex align-items-center">
                    <i class="bi bi-journals me-1"></i> Sổ tay
                </a>
                <a href="logout.php" class="btn btn-outline-danger btn-sm d-flex align-items-center">
                    <i class="bi bi-box-arrow-right me-1"></i> Đăng xuất
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-5 mb-5">
        <div class="share-card">
            <h2 class="mb-4">
                <i class="bi bi-share icon-header"></i> Chia sẻ sổ tay
            </h2>

            <?php if ($message): ?>
                <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <?php if ($share_link): ?>
                <div class="mb-4">
                    <p><strong>Sổ tay:</strong> <?= htmlspecialchars($notebook['title'] ?? 'Không xác định') ?></p>
                    <h5 class="mt-3">🔗 Link chia sẻ:</h5>
                    <div class="share-link"><?= htmlspecialchars($share_link) ?></div>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <button class="btn btn-primary btn-custom" onclick="copyShareLink()">
                            <i class="bi bi-clipboard"></i> Sao chép link
                        </button>
                        <a href="dashboard.php" class="btn btn-outline-secondary btn-custom">
                            <i class="bi bi-arrow-left"></i> Quay lại
                        </a>
                    </div>
                </div>

                <div class="alert alert-warning">
                    <i class="bi bi-info-circle-fill"></i>
                    <strong>Lưu ý:</strong> Bất kỳ ai có link này đều có thể <strong>nhập sổ tay vào tài khoản của họ</strong>.
                    Link không có thời hạn. Hãy chia sẻ cẩn thận!
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <p class="text-muted">Không thể tạo link chia sẻ.</p>
                    <a href="dashboard.php" class="btn btn-primary btn-custom">
                        <i class="bi bi-arrow-left"></i> Quay lại danh sách
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyShareLink() {
            const linkElement = document.querySelector('.share-link');
            if (!linkElement) {
                alert('Không tìm thấy link để sao chép!');
                return;
            }
            const linkText = linkElement.textContent.trim();

            navigator.clipboard.writeText(linkText)
                .then(() => {
                    alert('✅ Đã sao chép link chia sẻ!');
                })
                .catch(err => {
                    console.error('Lỗi clipboard:', err);
                    alert('❌ Không thể sao chép. Vui lòng chọn và copy thủ công.');
                });
        }
    </script>
</body>
</html>
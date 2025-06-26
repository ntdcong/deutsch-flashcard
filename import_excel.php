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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel']) && $_FILES['excel']['error'] === 0) {
    require_once __DIR__ . '/vendor/autoload.php';

    $file = $_FILES['excel']['tmp_name'];
    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        $count = 0;
        foreach ($rows as $i => $row) {
            if ($i === 0) continue; // bỏ dòng tiêu đề
            $word = trim($row[0] ?? '');
            $phonetic = trim($row[1] ?? '');
            $meaning = trim($row[2] ?? '');
            $note = trim($row[3] ?? '');
            $plural = trim($row[4] ?? '');
            $genus = trim($row[5] ?? '');
            if ($word && $meaning) {
                $stmt = $pdo->prepare('INSERT INTO vocabularies (notebook_id, word, phonetic, meaning, note, plural, genus) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$notebook_id, $word, $phonetic, $meaning, $note, $plural, $genus]);
                $count++;
            }
        }
        $message = "✅ Đã import <b>$count</b> từ vựng thành công!";
    } catch (Exception $e) {
        $message = '❌ Lỗi đọc file: ' . htmlspecialchars($e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Import từ Excel - Flashcard Đức</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(to right, #e0eafc, #cfdef3);
            font-family: "Segoe UI", sans-serif;
            min-height: 100vh;
        }

        .card {
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 10px 20px rgba(0,0,0,0.08);
            background: #fff;
            max-width: 600px;
            margin: auto;
        }

        .btn i {
            margin-right: 5px;
        }

        .example-box {
            background: #f8f9fa;
            border: 1px dashed #ccc;
            padding: 1rem;
            border-radius: 8px;
        }

        .back-link {
            text-decoration: none;
            color: #0d6efd;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-light bg-light">
    <div class="container">
        <a class="back-link" href="dashboard.php"><i class="bi bi-arrow-left"></i> Quay lại Sổ tay</a>
        <span class="navbar-text">
            Đang import cho sổ tay: <strong><?= htmlspecialchars($notebook['title']) ?></strong>
        </span>
    </div>
</nav>

<div class="container mt-5">
    <div class="card">
        <h4 class="mb-4"><i class="bi bi-file-earmark-excel-fill text-success"></i> Import từ vựng từ file Excel</h4>

        <?php if ($message): ?>
            <div class="alert alert-info"><?= $message ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="mb-4">
            <div class="mb-3">
                <label class="form-label">Chọn file Excel (.xlsx)</label>
                <input type="file" name="excel" accept=".xlsx" required class="form-control">
            </div>
            <button class="btn btn-success">
                <i class="bi bi-upload"></i> Import dữ liệu
            </button>
        </form>

        <div class="example-box">
            <strong>📌 Mẫu Excel cần có:</strong><br>
            Dòng đầu tiên là tiêu đề cột:
            <code>Từ vựng | Phiên âm | Nghĩa | Ghi chú | Số nhiều | Giống</code><br>
            <a href="assets/sample.xlsx" download class="btn btn-sm btn-outline-primary mt-2">
                <i class="bi bi-download"></i> Tải file mẫu
            </a>
        </div>
    </div>
</div>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

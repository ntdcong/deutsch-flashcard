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
$dataPreview = [];
$previewMode = false;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 15;
$totalPages = 1;

// Xử lý khi có POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['reset'])) {
        // Reset session khi chọn file mới
        unset($_SESSION['data_preview']);
        unset($_SESSION['uploaded_file_name']);
        unset($_SESSION['uploaded_file_data']);
        $previewMode = false;
    } elseif (isset($_FILES['excel']) && $_FILES['excel']['error'] === 0) {
        require_once __DIR__ . '/vendor/autoload.php';

        $file = $_FILES['excel']['tmp_name'];
        $fileName = $_FILES['excel']['name'];
        
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
            $dataPreview = [];

            foreach ($rows as $i => $row) {
                if ($i === 0) continue; // Bỏ dòng tiêu đề
                
                // Chỉ thêm hàng có dữ liệu
                $word = trim($row[0] ?? '');
                $meaning = trim($row[2] ?? '');
                
                if ($word || $meaning) { // Nếu có ít nhất từ vựng hoặc nghĩa
                    $dataPreview[] = [
                        'word' => $word,
                        'phonetic' => trim($row[1] ?? ''),
                        'meaning' => $meaning,
                        'note' => trim($row[3] ?? ''),
                        'plural' => trim($row[4] ?? ''),
                        'genus' => trim($row[5] ?? ''),
                    ];
                }
            }

            // Lưu dữ liệu và tên file vào session
            $_SESSION['data_preview'] = $dataPreview;
            $_SESSION['uploaded_file_name'] = $fileName;
            $_SESSION['uploaded_file_data'] = base64_encode(file_get_contents($file)); // Lưu file để có thể dùng lại
            
            if (isset($_POST['preview'])) {
                $previewMode = true;
            } elseif (isset($_POST['import'])) {
                $dataToImport = $_SESSION['data_preview'] ?? [];
                $count = 0;
                foreach ($dataToImport as $item) {
                    if ($item['word'] && $item['meaning']) {
                        $stmt = $pdo->prepare('INSERT INTO vocabularies (notebook_id, word, phonetic, meaning, note, plural, genus) VALUES (?, ?, ?, ?, ?, ?, ?)');
                        $stmt->execute([$notebook_id, $item['word'], $item['phonetic'], $item['meaning'], $item['note'], $item['plural'], $item['genus']]);
                        $count++;
                    }
                }
                // Xóa session sau khi import
                unset($_SESSION['data_preview']);
                unset($_SESSION['uploaded_file_name']);
                unset($_SESSION['uploaded_file_data']);
                $message = "✅ Đã import <b>$count</b> từ vựng thành công!";
                $previewMode = false;
            }
        } catch (Exception $e) {
            $message = '❌ Lỗi đọc file: ' . htmlspecialchars($e->getMessage());
        }
    } elseif (isset($_POST['import'])) {
        // Import từ session
        $dataToImport = $_SESSION['data_preview'] ?? [];
        $count = 0;
        foreach ($dataToImport as $item) {
            if ($item['word'] && $item['meaning']) {
                $stmt = $pdo->prepare('INSERT INTO vocabularies (notebook_id, word, phonetic, meaning, note, plural, genus) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$notebook_id, $item['word'], $item['phonetic'], $item['meaning'], $item['note'], $item['plural'], $item['genus']]);
                $count++;
            }
        }
        // Xóa session sau khi import
        unset($_SESSION['data_preview']);
        unset($_SESSION['uploaded_file_name']);
        unset($_SESSION['uploaded_file_data']);
        $message = "✅ Đã import <b>$count</b> từ vựng thành công!";
        $previewMode = false;
    }
}

// Lấy dữ liệu từ session nếu đang ở chế độ xem trước
if (isset($_SESSION['data_preview'])) {
    $dataPreview = $_SESSION['data_preview'];
    $totalItems = count($dataPreview);
    $totalPages = ceil($totalItems / $perPage);
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;
    $pagedData = array_slice($dataPreview, $offset, $perPage);
    $previewMode = true;
} else {
    $pagedData = [];
    $previewMode = false;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Import từ Excel - Flashcard Đức</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
            max-width: 900px;
            margin: auto;
        }
        .example-box {
            background: #f8f9fa;
            border: 1px dashed #ccc;
            padding: 1rem;
            border-radius: 8px;
        }
        .drop-area {
            border: 2px dashed #0d6efd;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }
        .drop-area.highlight {
            background: #e0f0ff;
            border-color: #0b5ed7;
        }
        .file-info {
            margin-top: 1rem;
            padding: 0.5rem;
            background: #e7f1ff;
            border-radius: 5px;
            display: none;
        }
        .back-link {
            text-decoration: none;
            color: #0d6efd;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .pagination-nav {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
        }
        .pagination-btn {
            padding: 8px 15px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            color: #0d6efd;
        }
        .pagination-btn:hover {
            background: #e9ecef;
        }
        .pagination-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .current-page {
            font-weight: bold;
            color: #0d6efd;
            padding: 8px 15px;
        }
        .reset-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
            display: none;
        }
        .reset-btn:hover {
            background: #c82333;
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

        <form method="post" enctype="multipart/form-data" id="uploadForm">
            <div class="drop-area" id="dropArea">
                <button type="button" class="reset-btn" id="resetBtn" title="Chọn file khác">×</button>
                <i class="bi bi-cloud-arrow-up" style="font-size: 2rem; color: #0d6efd;"></i>
                <p class="mb-2">Kéo thả file Excel (.xlsx) vào đây hoặc <strong>chọn file</strong></p>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('fileInput').click();">
                    <i class="bi bi-folder2-open"></i> Chọn file
                </button>
                <input type="file" name="excel" id="fileInput" accept=".xlsx" style="display: none;">
                
                <div class="file-info" id="fileInfo">
                    <i class="bi bi-file-earmark-excel"></i> 
                    <span id="fileName"></span>
                </div>
            </div>

            <?php if ($previewMode && !empty($dataPreview)): ?>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <h5 class="mb-0">📋 Xem trước dữ liệu (<?= count($dataPreview) ?> từ):</h5>
                    <span>Trang <?= $page ?> / <?= $totalPages ?></span>
                </div>
                
                <div class="table-responsive mt-3">
                    <table class="table table-bordered table-sm table-hover">
                        <thead class="table-light">
                            <tr>
                                <th width="15%">Từ vựng</th>
                                <th width="15%">Phiên âm</th>
                                <th width="25%">Nghĩa</th>
                                <th width="20%">Ghi chú</th>
                                <th width="15%">Số nhiều</th>
                                <th width="10%">Giống</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($pagedData)): ?>
                                <?php foreach ($pagedData as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['word']) ?></td>
                                        <td><?= htmlspecialchars($row['phonetic']) ?></td>
                                        <td><?= htmlspecialchars($row['meaning']) ?></td>
                                        <td><?= htmlspecialchars($row['note']) ?></td>
                                        <td><?= htmlspecialchars($row['plural']) ?></td>
                                        <td><?= htmlspecialchars($row['genus']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">Không có dữ liệu</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Phân trang -->
                <div class="pagination-nav">
                    <?php if ($page > 1): ?>
                        <a href="?notebook_id=<?= $notebook_id ?>&page=<?= $page - 1 ?>" class="pagination-btn">
                            <i class="bi bi-chevron-left"></i> Trước
                        </a>
                    <?php else: ?>
                        <span class="pagination-btn disabled">
                            <i class="bi bi-chevron-left"></i> Trước
                        </span>
                    <?php endif; ?>

                    <span class="current-page">Trang <?= $page ?></span>

                    <?php if ($page < $totalPages): ?>
                        <a href="?notebook_id=<?= $notebook_id ?>&page=<?= $page + 1 ?>" class="pagination-btn">
                            Sau <i class="bi bi-chevron-right"></i>
                        </a>
                    <?php else: ?>
                        <span class="pagination-btn disabled">
                            Sau <i class="bi bi-chevron-right"></i>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" name="import" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Xác nhận Import (<?= count($dataPreview) ?> từ)
                    </button>
                    <button type="submit" name="reset" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-repeat"></i> Chọn file khác
                    </button>
                </div>
            <?php else: ?>
                <button type="submit" name="preview" class="btn btn-primary w-100" id="previewBtn" disabled>
                    <i class="bi bi-eye"></i> Xem trước dữ liệu
                </button>
            <?php endif; ?>
        </form>

        <div class="example-box mt-4">
            <strong>📌 Mẫu Excel cần có:</strong><br>
            Dòng đầu tiên là tiêu đề cột:
            <code>Từ vựng | Phiên âm | Nghĩa | Ghi chú | Số nhiều | Giống</code><br>
            <a href="assets/sample.xlsx" download class="btn btn-sm btn-outline-primary mt-2">
                <i class="bi bi-download"></i> Tải file mẫu
            </a>
        </div>
    </div>
</div>

<script>
    const dropArea = document.getElementById('dropArea');
    const fileInput = document.getElementById('fileInput');
    const fileInfo = document.getElementById('fileInfo');
    const fileName = document.getElementById('fileName');
    const previewBtn = document.getElementById('previewBtn');
    const resetBtn = document.getElementById('resetBtn');

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropArea.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, unhighlight, false);
    });

    function highlight() {
        dropArea.classList.add('highlight');
    }

    function unhighlight() {
        dropArea.classList.remove('highlight');
    }

    dropArea.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files.length) {
            fileInput.files = files;
            showFileInfo(files[0].name);
            previewBtn.disabled = false;
            resetBtn.style.display = 'block';
        }
    }

    fileInput.addEventListener('change', function() {
        if (this.files.length) {
            showFileInfo(this.files[0].name);
            previewBtn.disabled = false;
            resetBtn.style.display = 'block';
        } else {
            hideFileInfo();
            previewBtn.disabled = true;
            resetBtn.style.display = 'none';
        }
    });

    function showFileInfo(name) {
        fileName.textContent = name;
        fileInfo.style.display = 'block';
    }

    function hideFileInfo() {
        fileInfo.style.display = 'none';
    }

    resetBtn.addEventListener('click', function() {
        fileInput.value = '';
        hideFileInfo();
        previewBtn.disabled = true;
        this.style.display = 'none';
        
        // Submit form để reset session
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'reset';
        hiddenInput.value = '1';
        
        form.appendChild(hiddenInput);
        document.body.appendChild(form);
        form.submit();
    });

    // Hiển thị tên file nếu đã có trong session
    <?php if (isset($_SESSION['uploaded_file_name'])): ?>
        showFileInfo('<?= addslashes($_SESSION['uploaded_file_name']) ?>');
        previewBtn.disabled = false;
        resetBtn.style.display = 'block';
    <?php endif; ?>
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
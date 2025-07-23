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
$message_type = 'info';

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
        $message_type = 'success';
    } else {
        $message = '❌ Vui lòng nhập từ vựng và nghĩa!';
        $message_type = 'danger';
    }
}

// Xóa từ
if (isset($_GET['delete'])) {
    $vocab_id = (int)$_GET['delete'];
    $pdo->prepare('DELETE FROM learning_status WHERE vocab_id=?')->execute([$vocab_id]);
    $pdo->prepare('DELETE FROM vocabularies WHERE id=? AND notebook_id=?')->execute([$vocab_id, $notebook_id]);
    $message = '🗑️ Đã xóa từ!';
    $message_type = 'warning';
}

// Cập nhật từ vựng
if (isset($_POST['edit_vocab'])) {
    $id = (int)$_POST['vocab_id'];
    $word = trim($_POST['word']);
    $phonetic = trim($_POST['phonetic']);
    $meaning = trim($_POST['meaning']);
    $note = trim($_POST['note']);
    $plural = trim($_POST['plural']);
    $genus = trim($_POST['genus']);

    if ($word && $meaning) {
        $stmt = $pdo->prepare('UPDATE vocabularies SET word=?, phonetic=?, meaning=?, note=?, plural=?, genus=? WHERE id=? AND notebook_id=?');
        $stmt->execute([$word, $phonetic, $meaning, $note, $plural, $genus, $id, $notebook_id]);
        $message = '✏️ Đã cập nhật từ!';
        $message_type = 'success';
    } else {
        $message = '❌ Vui lòng nhập đầy đủ thông tin!';
        $message_type = 'danger';
    }
}

// Tìm kiếm
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$where_clause = '';
$params = [$notebook_id];

if ($search) {
    $where_clause = "AND (word LIKE ? OR meaning LIKE ? OR note LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Tổng số từ vựng
$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM vocabularies WHERE notebook_id=? $where_clause");
$totalStmt->execute($params);
$total = $totalStmt->fetchColumn();
$totalPages = ceil($total / $limit);

// Lấy danh sách từ vựng
$params[] = $limit;
$params[] = $offset;

$stmt = $pdo->prepare("SELECT * FROM vocabularies WHERE notebook_id=? $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->execute($params);
$vocabs = $stmt->fetchAll();

// Lấy từ vựng cần chỉnh sửa (cho modal)
$edit_vocab = null;
if (isset($_GET['edit'])) {
    $vocab_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM vocabularies WHERE id=? AND notebook_id=?');
    $stmt->execute([$vocab_id, $notebook_id]);
    $edit_vocab = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý từ vựng</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            padding-bottom: 20px;
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, .25);
        }

        .card-form {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }

        .btn-sm {
            padding: 6px 10px;
            font-size: 12px;
        }

        .btn-sm i {
            margin-right: 3px;
        }

        .navbar-brand {
            font-weight: 600;
            color:rgb(255, 255, 255);
            font-size: 18px;
        }

        .navbar-light {
            background: linear-gradient(to right,rgb(90, 97, 229),rgb(123, 244, 224));
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar-text {
            color: black !important;
            font-size: 14px;
        }

        .vocab-card {
            background: white;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: 1px solid #eee;
        }

        .vocab-word {
            font-weight: 600;
            color: #2c3e50;
            font-size: 18px;
            margin-bottom: 5px;
        }

        .vocab-phonetic {
            color: #7f8c8d;
            font-style: italic;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .vocab-meaning {
            color: #34495e;
            font-size: 16px;
            margin-bottom: 8px;
        }

        .vocab-detail {
            color: #7f8c8d;
            font-size: 13px;
            margin-bottom: 3px;
        }

        .vocab-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }

        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .search-form {
            margin-bottom: 20px;
        }

        .search-form .form-control {
            border-radius: 25px 0 0 25px;
            border: 2px solid #dee2e6;
        }

        .search-form .btn {
            border-radius: 0 25px 25px 0;
            border: 2px solid #0d6efd;
        }

        .pagination {
            margin-top: 20px;
        }

        .pagination .page-link {
            border-radius: 8px !important;
            margin: 0 2px;
            border: 1px solid #dee2e6;
        }

        .pagination .active .page-link {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px 8px 0 0 !important;
        }

        .btn-modal {
            padding: 8px 16px;
            font-size: 14px;
        }

        /* Desktop only */
        @media (min-width: 768px) {
            .mobile-view {
                display: none;
            }
        }

        /* Mobile only */
        @media (max-width: 767.98px) {
            .desktop-view {
                display: none;
            }

            .card-form {
                padding: 15px;
            }

            .vocab-card {
                padding: 12px;
            }

            .vocab-word {
                font-size: 16px;
            }

            .vocab-meaning {
                font-size: 15px;
            }

            .vocab-detail {
                font-size: 12px;
            }

            .navbar-brand {
                font-size: 16px;
            }

            .navbar-text {
                font-size: 12px;
                max-width: 150px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .btn-group-sm .btn {
                padding: 4px 8px;
                font-size: 11px;
            }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">
            <i class="bi bi-arrow-left"></i> Trở lại
        </a>
        <span class="navbar-text text-truncate text-black fs-6 " style="max-width: 500px;">
            <i class="bi bi-book"></i> Sổ tay:
            <?= htmlspecialchars($notebook['title']) ?>
        </span>
    </div>
</nav>

<div class="container-fluid px-3 px-md-4 mt-3">
    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Nút mở modal thêm từ -->
    <div class="d-grid gap-2 mb-3">
        <button class="btn btn-success btn-modal" data-bs-toggle="modal" data-bs-target="#addVocabModal">
            <i class="bi bi-plus-circle"></i> Thêm từ vựng mới
        </button>
    </div>

    <!-- Tìm kiếm -->
    <div class="search-form">
        <form method="get" class="d-flex">
            <input type="hidden" name="notebook_id" value="<?= $notebook_id ?>">
            <input type="text" name="search" class="form-control" placeholder="Tìm từ vựng, nghĩa, ghi chú..." value="<?= htmlspecialchars($search) ?>">
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-search"></i>
            </button>
        </form>
    </div>

    <!-- Hiển thị kết quả -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">
            <i class="bi bi-book"></i> Từ vựng 
            <?php if ($search): ?>
                <small class="text-muted">(Tìm: "<?= htmlspecialchars($search) ?>")</small>
            <?php endif; ?>
        </h5>
        <small class="text-muted">
            <?= $total ?> từ • Trang <?= $page ?>/<?= $totalPages ?>
        </small>
    </div>

    <!-- View cho Desktop (Table) -->
    <div class="desktop-view">
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
                        <th width="120">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($vocabs as $v): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($v['word']) ?></strong></td>
                        <td><?= htmlspecialchars($v['phonetic']) ?></td>
                        <td><?= htmlspecialchars($v['meaning']) ?></td>
                        <td><?= htmlspecialchars($v['note']) ?></td>
                        <td><?= htmlspecialchars($v['plural']) ?></td>
                        <td><?= htmlspecialchars($v['genus']) ?></td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm" role="group">
                                <button class="btn btn-warning" onclick="openEditModal(<?= $v['id'] ?>, '<?= htmlspecialchars($v['word']) ?>', '<?= htmlspecialchars($v['phonetic']) ?>', '<?= htmlspecialchars($v['meaning']) ?>', '<?= htmlspecialchars($v['note']) ?>', '<?= htmlspecialchars($v['plural']) ?>', '<?= htmlspecialchars($v['genus']) ?>')">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <a href="?notebook_id=<?= $notebook_id ?>&delete=<?= $v['id'] ?>" class="btn btn-danger"
                                   onclick="return confirm('Bạn có chắc chắn muốn xoá từ này?');">
                                   <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($vocabs)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-emoji-frown fs-1 d-block mb-2"></i>
                            Không tìm thấy từ vựng nào
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- View cho Mobile (Cards) -->
    <div class="mobile-view">
        <?php foreach ($vocabs as $v): ?>
            <div class="vocab-card">
                <div class="vocab-word">
                    <?= htmlspecialchars($v['word']) ?>
                    <?php if ($v['phonetic']): ?>
                        <span class="vocab-phonetic">/<?= htmlspecialchars($v['phonetic']) ?>/</span>
                    <?php endif; ?>
                </div>
                <div class="vocab-meaning"><?= htmlspecialchars($v['meaning']) ?></div>
                
                <?php if ($v['note']): ?>
                    <div class="vocab-detail">
                        <i class="bi bi-info-circle"></i> <?= htmlspecialchars($v['note']) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($v['plural']): ?>
                    <div class="vocab-detail">
                        <i class="bi bi-collection"></i> Số nhiều: <?= htmlspecialchars($v['plural']) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($v['genus']): ?>
                    <div class="vocab-detail">
                        <i class="bi bi-gender-ambiguous"></i> Giống: <?= htmlspecialchars($v['genus']) ?>
                    </div>
                <?php endif; ?>
                
                <div class="vocab-actions">
                    <button class="btn btn-warning btn-sm flex-fill" onclick="openEditModal(<?= $v['id'] ?>, '<?= htmlspecialchars($v['word']) ?>', '<?= htmlspecialchars($v['phonetic']) ?>', '<?= htmlspecialchars($v['meaning']) ?>', '<?= htmlspecialchars($v['note']) ?>', '<?= htmlspecialchars($v['plural']) ?>', '<?= htmlspecialchars($v['genus']) ?>')">
                        <i class="bi bi-pencil"></i> Sửa
                    </button>
                    <a href="?notebook_id=<?= $notebook_id ?>&delete=<?= $v['id'] ?>" class="btn btn-danger btn-sm flex-fill"
                       onclick="return confirm('Bạn có chắc chắn muốn xoá từ này?');">
                       <i class="bi bi-trash"></i> Xoá
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (empty($vocabs)): ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-emoji-frown fs-1 d-block mb-2"></i>
                <p>Không tìm thấy từ vựng nào</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Phân trang -->
    <?php if ($totalPages > 1): ?>
        <nav>
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="?notebook_id=<?= $notebook_id ?>&page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<!-- Modal Thêm Từ Vựng -->
<div class="modal fade" id="addVocabModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-journal-plus"></i> Thêm từ vựng mới
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Từ vựng <span class="text-danger">*</span></label>
                            <input type="text" name="word" class="form-control" placeholder="Nhập từ vựng" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phiên âm</label>
                            <input type="text" name="phonetic" class="form-control" placeholder="Ví dụ: /wɝːd/">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Nghĩa <span class="text-danger">*</span></label>
                            <input type="text" name="meaning" class="form-control" placeholder="Nhập nghĩa của từ" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ghi chú</label>
                            <input type="text" name="note" class="form-control" placeholder="Ghi chú bổ sung">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Số nhiều</label>
                            <input type="text" name="plural" class="form-control" placeholder="Plural form">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Giống</label>
                            <input type="text" name="genus" class="form-control" placeholder="Giống danh từ">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x"></i> Hủy
                    </button>
                    <button type="submit" name="add_vocab" class="btn btn-success">
                        <i class="bi bi-plus-circle"></i> Thêm từ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Chỉnh Sửa Từ Vựng -->
<div class="modal fade" id="editVocabModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil"></i> Chỉnh sửa từ vựng
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <input type="hidden" name="vocab_id" id="edit_vocab_id">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Từ vựng <span class="text-danger">*</span></label>
                            <input type="text" name="word" id="edit_word" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phiên âm</label>
                            <input type="text" name="phonetic" id="edit_phonetic" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Nghĩa <span class="text-danger">*</span></label>
                            <input type="text" name="meaning" id="edit_meaning" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ghi chú</label>
                            <input type="text" name="note" id="edit_note" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Số nhiều</label>
                            <input type="text" name="plural" id="edit_plural" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Giống</label>
                            <input type="text" name="genus" id="edit_genus" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x"></i> Hủy
                    </button>
                    <button type="submit" name="edit_vocab" class="btn btn-warning">
                        <i class="bi bi-save"></i> Lưu thay đổi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Hàm mở modal chỉnh sửa với dữ liệu
function openEditModal(id, word, phonetic, meaning, note, plural, genus) {
    document.getElementById('edit_vocab_id').value = id;
    document.getElementById('edit_word').value = word;
    document.getElementById('edit_phonetic').value = phonetic;
    document.getElementById('edit_meaning').value = meaning;
    document.getElementById('edit_note').value = note;
    document.getElementById('edit_plural').value = plural;
    document.getElementById('edit_genus').value = genus;
    
    var editModal = new bootstrap.Modal(document.getElementById('editVocabModal'));
    editModal.show();
}

// Tự động mở modal chỉnh sửa nếu có dữ liệu
<?php if ($edit_vocab): ?>
document.addEventListener('DOMContentLoaded', function() {
    openEditModal(
        <?= $edit_vocab['id'] ?>,
        '<?= htmlspecialchars($edit_vocab['word']) ?>',
        '<?= htmlspecialchars($edit_vocab['phonetic']) ?>',
        '<?= htmlspecialchars($edit_vocab['meaning']) ?>',
        '<?= htmlspecialchars($edit_vocab['note']) ?>',
        '<?= htmlspecialchars($edit_vocab['plural']) ?>',
        '<?= htmlspecialchars($edit_vocab['genus']) ?>'
    );
});
<?php endif; ?>

</script>
</body>
</html>
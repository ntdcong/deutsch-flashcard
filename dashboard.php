<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
$message = '';
// Thêm nhóm sổ tay
if (isset($_POST['add_group'])) {
    $group_name = trim($_POST['group_name'] ?? '');
    if ($group_name) {
        $stmt = $pdo->prepare('INSERT INTO notebook_groups (user_id, name) VALUES (?, ?)');
        $stmt->execute([$user_id, $group_name]);
        $message = 'Đã tạo nhóm mới!';
    } else {
        $message = 'Vui lòng nhập tên nhóm!';
    }
}
// Thêm sổ tay
if (isset($_POST['add_notebook'])) {
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $group_id = $_POST['group_id'] !== '' ? (int)$_POST['group_id'] : null;
    if ($title) {
        $stmt = $pdo->prepare('INSERT INTO notebooks (user_id, title, description, group_id) VALUES (?, ?, ?, ?)');
        $stmt->execute([$user_id, $title, $desc, $group_id]);
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
    $group_id = $_POST['group_id'] !== '' ? (int)$_POST['group_id'] : null;
    if ($title) {
        $stmt = $pdo->prepare('UPDATE notebooks SET title=?, description=?, group_id=? WHERE id=? AND user_id=?');
        $stmt->execute([$title, $desc, $group_id, $id, $user_id]);
        $message = 'Đã cập nhật sổ tay!';
    } else {
        $message = 'Vui lòng nhập tiêu đề!';
    }
}
// Xoá nhóm
if (isset($_GET['delete_group'])) {
    $gid = (int)$_GET['delete_group'];
    // Chuyển tất cả sổ tay trong nhóm này về không nhóm
    $pdo->prepare('UPDATE notebooks SET group_id=NULL WHERE group_id=? AND user_id=?')->execute([$gid, $user_id]);
    // Xoá nhóm
    $pdo->prepare('DELETE FROM notebook_groups WHERE id=? AND user_id=?')->execute([$gid, $user_id]);
    $message = 'Đã xoá nhóm. Các sổ tay trong nhóm đã chuyển về "không nhóm"!';
}
// Lấy danh sách nhóm
$stmt = $pdo->prepare('SELECT * FROM notebook_groups WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$user_id]);
$groups = $stmt->fetchAll();
// Lấy danh sách sổ tay theo nhóm
$stmt = $pdo->prepare('SELECT * FROM notebooks WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$user_id]);
$notebooks = $stmt->fetchAll();
// Gom sổ tay theo group_id
$notebooks_by_group = [];
foreach ($notebooks as $nb) {
    $gid = $nb['group_id'] ?? 0;
    $notebooks_by_group[$gid][] = $nb;
}
// Xử lý lọc nhóm
$selected_group = isset($_GET['group']) ? $_GET['group'] : 'all';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Sổ tay - Flashcard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f2f6fc; font-family: "Segoe UI", sans-serif; }
        .navbar { background: linear-gradient(to right,rgb(90, 97, 229),rgb(123, 244, 224)); box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05); }
        .navbar-brand { font-weight: bold; font-size: 1.5rem; color:rgb(255, 255, 255); }
        .group-card { background: #fff; border-radius: 1.2rem; box-shadow: 0 4px 24px rgba(0,0,0,0.07); padding: 1.5rem 1.2rem; margin-bottom: 2rem; }
        .group-header { display: flex; align-items: center; gap: 0.7rem; margin-bottom: 1rem; }
        .group-header .icon { font-size: 2rem; color: #0d6efd; }
        .group-title { font-size: 1.2rem; font-weight: 600; color: #0d6efd; }
        .notebook-grid { display: flex; flex-wrap: wrap; gap: 1.2rem; }
        .notebook-card { background: linear-gradient(135deg,rgba(230, 224, 251, 0.47) 60%,rgba(173, 196, 230, 0.54) 100%); border-radius: 1rem; box-shadow: 0 2px 12px rgba(0,0,0,0.06); padding: 1rem 1rem 0.7rem 1rem; flex: 1 1 220px; min-width: 220px; max-width: 100%; display: flex; flex-direction: column; transition: transform 0.15s, box-shadow 0.15s; position: relative; }
        .notebook-card:hover { transform: translateY(-4px) scale(1.03); box-shadow: 0 8px 32px rgba(0,0,0,0.13); }
        .notebook-title { font-size: 1.1rem; font-weight: 600; color: #333; margin-bottom: 0.3rem; }
        .notebook-desc { font-size: 0.97rem; color: #666; margin-bottom: 0.7rem; min-height: 36px; }
        .notebook-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.5rem; }
        .notebook-actions .btn { font-size: 0.95rem; border-radius: 0.7rem; }
        .notebook-card .dropdown { position: absolute; top: 0.7rem; right: 0.7rem; }
        .floating-btn { position: fixed; bottom: 2.2rem; right: 2.2rem; z-index: 1000; background: linear-gradient(135deg, #0d6efd, #764ba2); color: #fff; border: none; border-radius: 50%; width: 60px; height: 60px; font-size: 2rem; box-shadow: 0 4px 24px rgba(0,0,0,0.18); display: flex; align-items: center; justify-content: center; transition: background 0.2s; }
        .floating-btn:hover { background: linear-gradient(135deg, #764ba2, #0d6efd); color: #fff; }
        @media (max-width: 768px) {
            .group-card { padding: 1rem 0.5rem; }
            .notebook-grid { gap: 0.7rem; }
            .notebook-card { min-width: 90vw; }
        }
        .modal .form-label { font-weight: 500; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Flashcard</a>
        <div class="d-flex">
            <a href="logout.php" class="btn btn-outline-danger">
                <i class="bi bi-box-arrow-right"></i> Đăng xuất
            </a>
        </div>
    </div>
</nav>
<div class="container mt-4 mb-5">
    <!-- Form thêm nhóm và thêm sổ tay -->
    <div class="row g-3 align-items-center mb-3">
        <div class="col-md-5 col-12 mb-2 mb-md-0">
            <form method="post" class="d-flex gap-2">
                <input type="text" name="group_name" class="form-control" placeholder="Tên nhóm mới" required>
                <button class="btn btn-outline-primary" name="add_group"><i class="bi bi-folder-plus"></i> Thêm nhóm mới</button>
            </form>
        </div>
        <div class="col-md-7 col-12">
            <form method="post" class="row g-2 align-items-center">
                <div class="col-12 col-md-3">
                    <input type="text" name="title" class="form-control" placeholder="Tiêu đề sổ tay" required>
                </div>
                <div class="col-12 col-md-3">
                    <input type="text" name="description" class="form-control" placeholder="Mô tả (tuỳ chọn)">
                </div>
                <div class="col-12 col-md-3">
                    <select name="group_id" class="form-select">
                        <option value="">Không nhóm</option>
                        <?php foreach ($groups as $g): ?>
                            <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <button class="btn btn-success" name="add_notebook"><i class="bi bi-journal-plus"></i> Thêm sổ</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Dropdown filter nhóm -->
    <form method="get" class="mb-4 d-flex flex-wrap align-items-center gap-2">
        <label class="form-label mb-0 me-2" for="groupFilter"><i class="bi bi-filter"></i> Nhóm:</label>
        <select name="group" id="groupFilter" class="form-select" style="max-width:220px;" onchange="this.form.submit()">
            <option value="all"<?= $selected_group==='all'?' selected':'' ?>>Tất cả nhóm</option>
            <?php foreach ($groups as $g): ?>
                <option value="<?= $g['id'] ?>"<?= $selected_group==$g['id']?' selected':'' ?>><?= htmlspecialchars($g['name']) ?></option>
            <?php endforeach; ?>
            <option value="none"<?= $selected_group==='none'?' selected':'' ?>>Không thuộc nhóm</option>
        </select>
    </form>
    <?php if ($message): ?><div class="alert alert-info"><?= $message ?></div><?php endif; ?>
    <?php if (count($groups) === 0): ?>
        <div class="text-center my-5">
            <div class="mb-3"><i class="bi bi-folder-plus" style="font-size:3rem;color:#0d6efd;"></i></div>
            <h4>Bạn chưa có nhóm sổ tay nào</h4>
            <button class="btn btn-primary btn-lg mt-3" data-bs-toggle="modal" data-bs-target="#modalAddGroup"><i class="bi bi-plus-circle"></i> Tạo nhóm mới</button>
        </div>
    <?php endif; ?>
    <?php foreach ($groups as $g): ?>
        <?php if ($selected_group==='all' || $selected_group==$g['id']): ?>
        <div class="group-card">
            <div class="group-header">
                <span class="icon"><i class="bi bi-folder-fill"></i></span>
                <span class="group-title"><?= htmlspecialchars($g['name']) ?></span>
                <form method="get" class="d-inline">
                    <input type="hidden" name="delete_group" value="<?= $g['id'] ?>">
                    <button class="btn btn-sm btn-danger ms-2" type="submit" onclick="return confirm('Xoá nhóm này? Các sổ tay sẽ chuyển về không nhóm.')" title="Xoá nhóm" style="min-width:32px;">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
                <?php if (!empty($notebooks_by_group[$g['id']])): ?>
                    <button class="btn btn-sm btn-light ms-auto toggle-group-btn" data-group="<?= $g['id'] ?>">
                        <span class="toggle-label"><i class="bi bi-chevron-down"></i> Xem sổ tay</span>
                    </button>
                <?php endif; ?>
            </div>
            <div class="notebook-grid d-none" id="groupGrid<?= $g['id'] ?>">
                <?php if (!empty($notebooks_by_group[$g['id']])): ?>
                    <?php foreach ($notebooks_by_group[$g['id']] as $nb): ?>
                        <div class="notebook-card">
                            <div class="notebook-title"><i class="bi bi-journal-text me-1"></i> <?= htmlspecialchars($nb['title']) ?></div>
                            <div class="notebook-desc"><?= nl2br(htmlspecialchars($nb['description'])) ?></div>
                            <div class="notebook-actions">
                                <a href="study_flashcard.php?notebook_id=<?= $nb['id'] ?>" class="btn btn-outline-warning btn-sm"><i class="bi bi-card-text"></i> Flashcard</a>
                                <a href="study_quiz.php?notebook_id=<?= $nb['id'] ?>" class="btn btn-outline-info btn-sm"><i class="bi bi-question-circle"></i> Quiz</a>
                                <a href="add_vocab.php?notebook_id=<?= $nb['id'] ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil-square"></i> Từ vựng</a>
                                <a href="import_excel.php?notebook_id=<?= $nb['id'] ?>" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-excel"></i> Excel</a>
                                <a href="#" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $nb['id'] ?>"><i class="bi bi-pencil"></i> Sửa sổ tay</a>
                                <a href="?delete=<?= $nb['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xoá sổ tay này?');"><i class="bi bi-trash"></i> Xoá</a>
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
                                        <div class="mb-3">
                                            <label class="form-label">Nhóm</label>
                                            <select name="group_id" class="form-select">
                                                <option value="">-- Không thuộc nhóm --</option>
                                                <?php foreach ($groups as $gg): ?>
                                                    <option value="<?= $gg['id'] ?>" <?= ($nb['group_id']==$gg['id'])?'selected':'' ?>><?= htmlspecialchars($gg['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
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
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    <?php endforeach; ?>
    <?php if (($selected_group==='all' || $selected_group==='none') && (isset($notebooks_by_group[null]) || isset($notebooks_by_group[0]))): ?>
        <div class="group-card">
            <div class="group-header">
                <span class="icon"><i class="bi bi-folder2-open"></i></span>
                <span class="group-title">Không thuộc nhóm</span>
            </div>
            <div class="notebook-grid">
                <?php foreach (($notebooks_by_group[null] ?? []) + ($notebooks_by_group[0] ?? []) as $nb): ?>
                    <div class="notebook-card">
                        <div class="notebook-title"><i class="bi bi-journal-text me-1"></i> <?= htmlspecialchars($nb['title']) ?></div>
                        <div class="notebook-desc"><?= nl2br(htmlspecialchars($nb['description'])) ?></div>
                        <div class="notebook-actions">
                            <a href="study_flashcard.php?notebook_id=<?= $nb['id'] ?>" class="btn btn-outline-warning btn-sm"><i class="bi bi-card-text"></i> Flashcard</a>
                            <a href="study_quiz.php?notebook_id=<?= $nb['id'] ?>" class="btn btn-outline-info btn-sm"><i class="bi bi-question-circle"></i> Quiz</a>
                            <a href="add_vocab.php?notebook_id=<?= $nb['id'] ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil-square"></i> Từ vựng</a>
                            <a href="import_excel.php?notebook_id=<?= $nb['id'] ?>" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-excel"></i> Excel</a>
                            <a href="#" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $nb['id'] ?>"><i class="bi bi-pencil"></i> Sửa sổ tay</a>
                            <a href="?delete=<?= $nb['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xoá sổ tay này?');"><i class="bi bi-trash"></i> Xoá</a>
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
                                    <div class="mb-3">
                                        <label class="form-label">Nhóm</label>
                                        <select name="group_id" class="form-select">
                                            <option value="">-- Không thuộc nhóm --</option>
                                            <?php foreach ($groups as $gg): ?>
                                                <option value="<?= $gg['id'] ?>" <?= ($nb['group_id']==$gg['id'])?'selected':'' ?>><?= htmlspecialchars($gg['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
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
    <?php endif; ?>
</div>
<!-- Modal tạo nhóm -->
<div class="modal fade" id="modalAddGroup" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tạo nhóm mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Tên nhóm</label>
                    <input type="text" name="group_name" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="add_group" class="btn btn-primary">Tạo nhóm</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
            </div>
        </form>
    </div>
</div>
<!-- Modal tạo sổ tay -->
<div class="modal fade" id="modalAddNotebook" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tạo sổ tay mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Tiêu đề</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Mô tả</label>
                    <textarea name="description" class="form-control" rows="2"></textarea>
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
            </div>
            <div class="modal-footer">
                <button type="submit" name="add_notebook" class="btn btn-success">Tạo sổ tay</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
            </div>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.toggle-group-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var groupId = btn.getAttribute('data-group');
            var grid = document.getElementById('groupGrid' + groupId);
            var expanded = btn.classList.toggle('expanded');
            grid.classList.toggle('d-none', !expanded);
            btn.querySelector('.toggle-label').innerHTML = expanded
                ? '<i class="bi bi-chevron-up"></i> Thu gọn'
                : '<i class="bi bi-chevron-down"></i> Xem sổ tay';
        });
    });
});
</script>
</body>
</html>

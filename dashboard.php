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

// ---- Bổ sung: đếm số từ / sổ tay để hiện footer
$counts = [];
if ($notebooks) {
    $ids = array_column($notebooks, 'id');
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $q = $pdo->prepare("SELECT notebook_id, COUNT(*) cnt FROM vocabularies WHERE notebook_id IN ($in) GROUP BY notebook_id");
    $q->execute($ids);
    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $counts[(int)$r['notebook_id']] = (int)$r['cnt'];
    }
}
// ---- Bổ sung: kiểm tra có genus der/die/das để bật nút Giống
$genderReady = [];
if ($notebooks) {
    $ids = array_column($notebooks, 'id');
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $q = $pdo->prepare("
        SELECT DISTINCT notebook_id
        FROM vocabularies
        WHERE notebook_id IN ($in)
          AND genus IS NOT NULL AND TRIM(genus) <> ''
          AND LOWER(genus) IN ('der','die','das')
    ");
    $q->execute($ids);
    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $genderReady[(int)$r['notebook_id']] = true;
    }
}

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
        .nb2-card{border:1px solid #000000ff;border-radius:16px;background:#f8fcff;box-shadow:0 6px 20px rgba(28,39,61,.05);overflow:hidden;}
        .nb2-header{display:flex;gap:12px;align-items:flex-start;padding:14px;border-bottom:1px solid #d5eaff;background:#a9d5ff;}
        .nb2-avatar{width:40px;height:40px;border-radius:12px;display:grid;place-items:center;flex:0 0 auto;background:linear-gradient(135deg,#f1f6ff,#f9fbff);color:#2563eb;font-size:18px;border:1px solid #e0e7ff;}
        .nb2-title{font-weight:700;color:#1f2937;display:flex;align-items:center;gap:6px;}
        .nb2-title i{color:#374151;}
        .nb2-desc{color:#4b5563;margin-top:2px;}
        .nb2-desc.clamp-2{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
        .nb2-actions{padding:12px;display:flex;flex-wrap:wrap;gap:10px;}
        .nb2-btn{display:flex;align-items:center;justify-content:center;gap:8px;padding:8px 14px;width:130px;border:2px solid #000;border-radius:999px;font-weight:600;font-size:.95rem;background:#fff;text-decoration:none}
        .nb2-btn i{font-size:18px}
        @media(max-width:160px){
        .nb2-btn{width:100%;}}
        .nb2-btn:focus{outline:none;box-shadow:0 0 0 0.15rem rgba(99,102,241,.15)}
        .nb2-warn{border-color:#fbbf24;color:#b45309;}
        .nb2-info{border-color:#38bdf8;color:#0369a1;}
        .nb2-primary{border-color:#3b82f6;color:#1d4ed8;}
        .nb2-dark{border-color:#111827;color:#111827;}
        .nb2-success{border-color:#22c55e;color:#065f46;}
        .nb2-gray{border-color:#9ca3af;color:#374151;}
        .nb2-danger{border-color:#ef4444;color:#991b1b;}
        .nb2-footer{padding:10px 14px;border-top:1px solid #d5eaff;background:#f0f8ff;display:flex;align-items:center;justify-content:space-between;gap:8px;}
        .nb2-meta{color:#4b5563;font-size:.9rem;display:flex;align-items:center;gap:6px;}
        .nb2-mini{display:flex;gap:8px;}
        .nb2-mini .btn{border-radius:10px;padding:6px 10px;font-size:14px;}
        .nb2-grid{display:grid;grid-template-columns:1fr;gap:14px;}
        @media (min-width:576px){.nb2-grid{grid-template-columns:repeat(2,1fr);}}
        @media (min-width:992px){.nb2-grid{grid-template-columns:repeat(3,1fr);}}
        @media (max-width:420px){.nb2-btn{padding:8px 12px;font-size:.9rem;}}
    </style>

    <style>
        .notebook-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px}
        @media(max-width:576px){.notebook-grid{grid-template-columns:1fr;gap:12px}}
        .nb2-card{height:100%}
        .nb2-actions{display:flex;flex-wrap:wrap;gap:10px}
        .nb2-btn{white-space:nowrap}
    </style>

    <style>
        body { background:#F9FAFB; font-family:'Segoe UI', sans-serif; }
        .navbar { background: linear-gradient(to right, rgb(90, 97, 229), rgb(140, 242, 255)); box-shadow:0 2px 8px rgba(0,0,0,.05); }
        .navbar-brand { font-weight:bold; font-size:1.5rem; color:#fff; }
        .group-card { background:#fff; border-radius:1.2rem; box-shadow:0 4px 24px rgba(0,0,0,.07); padding:1.5rem 1.2rem; margin-bottom:2rem; }
        .group-header { display:flex; align-items:center; gap:.7rem; margin-bottom:1rem; }
        .group-header .icon { font-size:2rem; color:#0d6efd; }
        .group-title { font-size:1.2rem; font-weight:600; color:#0d6efd; }
        @media (max-width:768px){ .group-card{padding:1rem .5rem} .notebook-grid{gap:.7rem} }
        .modal .form-label { font-weight:500; }
    </style>
</head>

<body>
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="home.php">GERMANLY</a>
        <div class="d-flex">
            <a href="logout.php" class="btn btn-outline-danger">
                <i class="bi bi-box-arrow-right"></i> Đăng xuất
            </a>
        </div>
    </div>
</nav>

<div class="container mt-4 mb-5">
    <!-- Các nút hành động chính -->
    <div class="mb-4 text-center text-md-start">
        <div class="d-flex flex-wrap justify-content-center justify-content-md-start gap-3">
            <button class="btn d-flex align-items-center gap-2 px-4 py-2 rounded-pill shadow-sm border-0"
                    style="background: linear-gradient(to right, #6a11cb, #2575fc); color: white; font-weight: 500;"
                    data-bs-toggle="modal" data-bs-target="#modalAddGroup">
                <i class="bi bi-folder-plus" style="font-size:1.2rem;"></i><span>Tạo nhóm</span>
            </button>
            <button class="btn d-flex align-items-center gap-2 px-4 py-2 rounded-pill shadow-sm border-0"
                    style="background: linear-gradient(to right, #11998e, #38ef7d); color: white; font-weight: 500;"
                    data-bs-toggle="modal" data-bs-target="#modalAddNotebook">
                <i class="bi bi-journal-plus" style="font-size:1.2rem;"></i><span>Tạo sổ tay</span>
            </button>
            <a href="import_shared.php" class="btn d-flex align-items-center gap-2 px-4 py-2 rounded-pill shadow-sm border-0 text-white"
               style="background: linear-gradient(to right, #f093fb, #f5576c); font-weight: 500;">
                <i class="bi bi-download" style="font-size:1.2rem;"></i><span>Nhập chia sẻ</span>
            </a>
        </div>
    </div>

    <!-- Bộ lọc nhóm -->
    <form method="get" class="mb-4 d-flex flex-column flex-md-row align-items-center gap-2">
        <div class="d-flex align-items-center w-100 w-md-auto">
            <label class="form-label mb-0 me-2" for="groupFilter"><i class="bi bi-filter"></i> Nhóm:</label>
            <select name="group" id="groupFilter" class="form-select" style="max-width:220px;" onchange="this.form.submit()">
                <option value="all" <?= $selected_group === 'all' ? 'selected' : '' ?>>Tất cả nhóm</option>
                <?php foreach ($groups as $g): ?>
                    <option value="<?= $g['id'] ?>" <?= $selected_group == $g['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($g['name']) ?>
                    </option>
                <?php endforeach; ?>
                <option value="none" <?= $selected_group === 'none' ? 'selected' : '' ?>>Không nhóm</option>
            </select>
        </div>
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
        <?php if ($selected_group === 'all' || $selected_group == $g['id']): ?>
            <div class="group-card">
                <div class="group-header" data-group="<?= $g['id'] ?>">
                    <span class="icon"><i class="bi bi-folder-fill"></i></span>
                    <span class="group-title"><?= htmlspecialchars($g['name']) ?></span>
                    <form method="get" class="d-inline">
                        <input type="hidden" name="delete_group" value="<?= $g['id'] ?>">
                        <button class="btn btn-sm btn-danger ms-2" type="submit"
                                onclick="return confirm('Xoá nhóm này? Các sổ tay sẽ chuyển về không nhóm.')"
                                title="Xoá nhóm" style="min-width:32px;">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                    <?php if (!empty($notebooks_by_group[$g['id']])): ?>
                        <i class="bi bi-chevron-down text-muted ms-auto toggle-icon"
                           style="transition: transform 0.2s; cursor: pointer;"
                           data-group="<?= $g['id'] ?>"></i>
                    <?php endif; ?>
                </div>

                <div class="notebook-grid d-none" id="groupGrid<?= $g['id'] ?>">
                    <?php if (!empty($notebooks_by_group[$g['id']])): ?>
                        <?php foreach ($notebooks_by_group[$g['id']] as $nb): ?>
                            <div class="nb2-card mb-3">
                                <div class="nb2-header">
                                    <div class="nb2-avatar"><i class="bi bi-journal-text"></i></div>
                                    <div class="flex-grow-1">
                                        <div class="nb2-title">
                                            <?= htmlspecialchars($nb['title']) ?>
                                        </div>
                                        <div class="nb2-desc clamp-2">
                                            <?= $nb['description'] ? nl2br(htmlspecialchars($nb['description'])) : '<span class="text-muted">Chưa có mô tả…</span>' ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="nb2-actions">
                                    <a href="study_flashcard.php?notebook_id=<?= $nb['id'] ?>" class="nb2-btn nb2-warn">
                                        <i class="bi bi-journal-richtext"></i> Flashcard
                                    </a>
                                    <a href="study_quiz.php?notebook_id=<?= $nb['id'] ?>" class="nb2-btn nb2-info">
                                        <i class="bi bi-question-circle"></i> Quiz
                                    </a>
                                    <a href="add_vocab.php?notebook_id=<?= $nb['id'] ?>" class="nb2-btn nb2-primary">
                                        <i class="bi bi-pencil-square"></i> Từ vựng
                                    </a>

                                    <?php $canGender = !empty($genderReady[(int)$nb['id']]); ?>
                                    <?php if ($canGender): ?>
                                        <a href="study_gender.php?notebook_id=<?= $nb['id'] ?>" class="nb2-btn nb2-dark">
                                            <i class="bi bi-gender-ambiguous"></i> Giống
                                        </a>
                                    <?php else: ?>
                                        <span class="nb2-btn nb2-gray" style="opacity:.6;pointer-events:none" title="Chưa có danh từ có giống">
                                            <i class="bi bi-gender-ambiguous"></i> Giống
                                        </span>
                                    <?php endif; ?>

                                    <a href="import_excel.php?notebook_id=<?= $nb['id'] ?>" class="nb2-btn nb2-success">
                                        <i class="bi bi-file-earmark-excel"></i> Excel
                                    </a>
                                    <a href="share_notebook.php?notebook_id=<?= $nb['id'] ?>" class="nb2-btn nb2-primary">
                                        <i class="bi bi-share"></i> Chia sẻ
                                    </a>
                                </div>

                                <div class="nb2-footer">
                                    <div class="nb2-meta">
                                        <i class="bi bi-collection"></i>
                                        <?= (int)($counts[(int)$nb['id']] ?? 0) ?> từ
                                        <?php if (!empty($nb['created_at'])): ?> • tạo <?= date('d/m/Y', strtotime($nb['created_at'])) ?><?php endif; ?>
                                    </div>
                                    <div class="nb2-mini">
                                        <a href="#" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $nb['id'] ?>" title="Sửa">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="?delete=<?= $nb['id'] ?>" class="btn btn-outline-danger btn-sm"
                                           onclick="return confirm('Bạn có chắc chắn muốn xoá sổ tay này?');" title="Xoá">
                                            <i class="bi bi-trash"></i>
                                        </a>
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
                                            <div class="mb-3">
                                                <label class="form-label">Nhóm</label>
                                                <select name="group_id" class="form-select">
                                                    <option value="">-- Không thuộc nhóm --</option>
                                                    <?php foreach ($groups as $gg): ?>
                                                        <option value="<?= $gg['id'] ?>" <?= ($nb['group_id'] == $gg['id']) ? 'selected' : '' ?>><?= htmlspecialchars($gg['name']) ?></option>
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

    <?php if (($selected_group === 'all' || $selected_group === 'none') && (isset($notebooks_by_group[null]) || isset($notebooks_by_group[0]))): ?>
        <div class="group-card">
            <div class="group-header">
                <span class="icon"><i class="bi bi-folder2-open"></i></span>
                <span class="group-title">Không thuộc nhóm</span>
            </div>
            <div class="notebook-grid">
                <?php foreach (($notebooks_by_group[null] ?? []) + ($notebooks_by_group[0] ?? []) as $nb): ?>
                    <div class="nb2-card mb-3">
                        <div class="nb2-header">
                            <div class="nb2-avatar"><i class="bi bi-journal-text"></i></div>
                            <div class="flex-grow-1">
                                <div class="nb2-title">
                                    <i class="bi bi-journal-text"></i>
                                    <?= htmlspecialchars($nb['title']) ?>
                                </div>
                                <div class="nb2-desc clamp-2">
                                    <?= $nb['description'] ? nl2br(htmlspecialchars($nb['description'])) : '<span class="text-muted">Chưa có mô tả…</span>' ?>
                                </div>
                            </div>
                        </div>

                        <div class="nb2-actions">
                            <a href="study_flashcard.php?notebook_id=<?= $nb['id'] ?>" class="nb2-btn nb2-warn">
                                <i class="bi bi-journal-richtext"></i> Flashcard
                            </a>
                            <a href="study_quiz.php?notebook_id=<?= $nb['id'] ?>" class="nb2-btn nb2-info">
                                <i class="bi bi-question-circle"></i> Quiz
                            </a>
                            <a href="add_vocab.php?notebook_id=<?= $nb['id'] ?>" class="nb2-btn nb2-primary">
                                <i class="bi bi-pencil-square"></i> Từ vựng
                            </a>

                            <?php $canGender = !empty($genderReady[(int)$nb['id']]); ?>
                            <?php if ($canGender): ?>
                                <a href="study_gender.php?notebook_id=<?= $nb['id'] ?>" class="nb2-btn nb2-dark">
                                    <i class="bi bi-gender-ambiguous"></i> Giống
                                </a>
                            <?php else: ?>
                                <span class="nb2-btn nb2-gray" style="opacity:.6;pointer-events:none" title="Chưa có danh từ có giống">
                                    <i class="bi bi-gender-ambiguous"></i> Giống
                                </span>
                            <?php endif; ?>

                            <a href="import_excel.php?notebook_id=<?= $nb['id'] ?>" class="nb2-btn nb2-success">
                                <i class="bi bi-file-earmark-excel"></i> Excel
                            </a>
                            <a href="share_notebook.php?notebook_id=<?= $nb['id'] ?>" class="nb2-btn nb2-primary">
                                <i class="bi bi-share"></i> Chia sẻ
                            </a>
                            <a href="#" class="nb2-btn nb2-gray" data-bs-toggle="modal" data-bs-target="#editModal<?= $nb['id'] ?>">
                                <i class="bi bi-pencil"></i> Sửa
                            </a>
                            <a href="?delete=<?= $nb['id'] ?>" class="nb2-btn nb2-danger"
                               onclick="return confirm('Bạn có chắc chắn muốn xoá sổ tay này?');">
                                <i class="bi bi-trash"></i> Xoá
                            </a>
                        </div>

                        <div class="nb2-footer">
                            <div class="nb2-meta">
                                <i class="bi bi-collection"></i>
                                <?= (int)($counts[(int)$nb['id']] ?? 0) ?> từ
                                <?php if (!empty($nb['created_at'])): ?> • tạo <?= date('d/m/Y', strtotime($nb['created_at'])) ?><?php endif; ?>
                            </div>
                            <div class="nb2-mini">
                                <a href="#" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $nb['id'] ?>" title="Sửa">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="?delete=<?= $nb['id'] ?>" class="btn btn-outline-danger btn-sm"
                                   onclick="return confirm('Bạn có chắc chắn muốn xoá sổ tay này?');" title="Xoá">
                                    <i class="bi bi-trash"></i>
                                </a>
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
                                    <div class="mb-3">
                                        <label class="form-label">Nhóm</label>
                                        <select name="group_id" class="form-select">
                                            <option value="">-- Không thuộc nhóm --</option>
                                            <?php foreach ($groups as $gg): ?>
                                                <option value="<?= $gg['id'] ?>" <?= ($nb['group_id'] == $gg['id']) ? 'selected' : '' ?>><?= htmlspecialchars($gg['name']) ?></option>
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
    document.querySelectorAll('.group-header').forEach(function(header) {
        const groupId = header.getAttribute('data-group');
        const grid = document.getElementById('groupGrid' + groupId);
        const icon = header.querySelector('.toggle-icon');
        if (!grid) return;
        if (icon) icon.style.transform = grid.classList.contains('d-none') ? 'rotate(0deg)' : 'rotate(180deg)';
        header.addEventListener('click', function(e) {
            if (e.target.closest('button[type="submit"], .btn-danger, form, .toggle-icon')) return;
            const isHidden = grid.classList.contains('d-none');
            grid.classList.toggle('d-none', !isHidden);
            if (icon) icon.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
        });
        if (icon) {
            icon.addEventListener('click', function(e) {
                e.stopPropagation();
                const isHidden = grid.classList.contains('d-none');
                grid.classList.toggle('d-none', !isHidden);
                this.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
            });
        }
    });
    document.querySelectorAll('form[method="get"] button[type="submit"]').forEach(btn => {
        btn.addEventListener('click', e => e.stopPropagation());
    });
});
</script>
</body>
</html>

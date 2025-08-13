<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require 'db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
$message = '';

// ====== Actions (giữ nguyên logic) ======
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
    $pdo->prepare('UPDATE notebooks SET group_id=NULL WHERE group_id=? AND user_id=?')->execute([$gid, $user_id]);
    $pdo->prepare('DELETE FROM notebook_groups WHERE id=? AND user_id=?')->execute([$gid, $user_id]);
    $message = 'Đã xoá nhóm. Các sổ tay trong nhóm đã chuyển về "không nhóm"!';
}

// ====== Queries (giữ nguyên chức năng) ======
$stmt = $pdo->prepare('SELECT * FROM notebook_groups WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$user_id]);
$groups = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT * FROM notebooks WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$user_id]);
$notebooks = $stmt->fetchAll();

// Tìm kiếm theo tiêu đề/mô tả
$keyword = trim($_GET['q'] ?? '');
if ($keyword !== '') {
    $kw = mb_strtolower($keyword);
    $notebooks = array_values(array_filter($notebooks, function ($nb) use ($kw) {
        $t = mb_strtolower($nb['title'] ?? '');
        $d = mb_strtolower($nb['description'] ?? '');
        return (mb_stripos($t, $kw) !== false) || (mb_stripos($d, $kw) !== false);
    }));
}

// Đếm số từ / sổ tay (cho danh sách đang hiển thị)
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
// Kiểm tra “Giống”
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

// Gom theo group (sau khi lọc tìm kiếm)
$notebooks_by_group = [];
foreach ($notebooks as $nb) {
    $gid = $nb['group_id'] ?? 0;
    $notebooks_by_group[$gid][] = $nb;
}
// Lọc nhóm
$selected_group = isset($_GET['group']) ? $_GET['group'] : 'all';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý Sổ tay - Flashcard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- libs -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles (giống giao diện mẫu) -->
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --surface: #ffffff;
            --surface-secondary: #f8fafc;
            --surface-tertiary: #f1f5f9;
            --border: rgba(15, 23, 42, .08);
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, .05);
            --shadow: 0 1px 3px rgba(0, 0, 0, .1), 0 1px 2px rgba(0, 0, 0, .06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, .1), 0 4px 6px -2px rgba(0, 0, 0, .05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, .1), 0 10px 10px -5px rgba(0, 0, 0, .04);
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-tertiary: #94a3b8;
            --border-radius: 16px;
            --border-radius-lg: 24px;
        }

        * {
            box-sizing: border-box
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--text-primary);
            line-height: 1.6
        }

        .main-container {
            background: var(--surface-secondary);
            border-radius: var(--border-radius-lg) var(--border-radius-lg) 0 0;
            margin-top: 2rem;
            min-height: calc(100vh - 2rem);
            overflow: hidden
        }

        /* Navbar */
        .modern-navbar {
            background: rgba(255, 255, 255, .95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.75rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none
        }

        .logout-btn {
            background: var(--danger-gradient);
            border: none;
            color: #fff;
            padding: .5rem 1.25rem;
            border-radius: 50px;
            font-weight: 500;
            transition: .3s;
            box-shadow: var(--shadow)
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            color: #fff
        }

        /* Action section */
        .action-section {
            padding: 2rem 0 1rem
        }

        .modern-btn {
            border: none;
            padding: .875rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            color: #fff;
            text-decoration: none;
            transition: .3s cubic-bezier(.4, 0, .2, 1);
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            gap: .5rem
        }

        .modern-btn:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-xl);
            color: #fff
        }

        .modern-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .2), transparent);
            transition: left .5s
        }

        .modern-btn:hover::before {
            left: 100%
        }

        .btn-create-group {
            background: var(--primary-gradient)
        }

        .btn-create-notebook {
            background: var(--success-gradient)
        }

        .btn-import {
            background: var(--secondary-gradient)
        }

        /* Filter */
        .filter-section {
            background: var(--surface);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border)
        }

        .filter-label {
            color: var(--text-secondary);
            font-weight: 500;
            margin-bottom: .5rem
        }

        .modern-select {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: .75rem 1rem;
            background: var(--surface);
            font-weight: 500;
            color: var(--text-primary);
            transition: .3s;
            box-shadow: var(--shadow-sm)
        }

        .modern-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, .1)
        }

        .search-input {
            min-width: 260px
        }

        @media(max-width:576px) {
            .search-input {
                min-width: 0;
                width: 100%
            }
        }

        /* Group card */
        .group-card {
            background: var(--surface);
            border-radius: var(--border-radius-lg);
            margin-bottom: 2rem;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
            transition: .3s
        }

        .group-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl)
        }

        .group-header {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 1.5rem 2rem;
            cursor: pointer;
            transition: .3s;
            border-bottom: 1px solid var(--border);
            position: relative
        }

        .group-header:hover {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%)
        }

        .group-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 3rem;
            height: 3rem;
            background: var(--primary-gradient);
            border-radius: 16px;
            color: #fff;
            font-size: 1.5rem;
            margin-right: 1rem;
            box-shadow: var(--shadow)
        }

        .group-title {
            font-size: 1.375rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0
        }

        .group-delete-btn {
            background: var(--danger-gradient);
            border: none;
            color: #fff;
            padding: .5rem;
            border-radius: 10px;
            transition: .3s;
            margin-left: 1rem
        }

        .group-delete-btn:hover {
            transform: scale(1.1);
            box-shadow: var(--shadow)
        }

        .toggle-icon {
            position: absolute;
            right: 2rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.5rem;
            color: var(--text-tertiary);
            transition: .3s
        }

        /* Notebook grid */
        .notebook-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            padding: 2rem
        }

        @media(max-width:768px) {
            .notebook-grid {
                grid-template-columns: 1fr;
                padding: 1rem;
                gap: 1rem
            }
        }

        /* Notebook card */
        .notebook-card {
            background: var(--surface);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            transition: .3s cubic-bezier(.4, 0, .2, 1);
            height: fit-content
        }

        .notebook-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
            border-color: rgba(102, 126, 234, .2)
        }

        .notebook-header {
            background: linear-gradient(135deg, #fafafa 0%, #f4f4f5 100%);
            padding: 1.5rem;
            border-bottom: 1px solid var(--border)
        }

        .notebook-avatar {
            width: 3rem;
            height: 3rem;
            border-radius: 12px;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.25rem;
            margin-bottom: 1rem;
            box-shadow: var(--shadow)
        }

        .notebook-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: .5rem;
            line-height: 1.4
        }

        .notebook-description {
            color: var(--text-secondary);
            font-size: .9rem;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden
        }

        /* Actions */
        .notebook-actions {
            padding: 1.5rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: .75rem
        }

        @media(max-width:480px) {
            .notebook-actions {
                grid-template-columns: 1fr 1fr
            }
        }

        /* fallback nhỏ */
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            padding: .75rem 1rem;
            border-radius: 10px;
            font-weight: 500;
            font-size: .875rem;
            text-decoration: none;
            transition: .3s;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--text-primary);
            position: relative;
            overflow: hidden
        }

        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow);
            text-decoration: none
        }

        .action-btn.flashcard {
            border-color: #f59e0b;
            color: #f59e0b
        }

        .action-btn.flashcard:hover {
            background: #f59e0b;
            color: #fff
        }

        .action-btn.quiz {
            border-color: #06b6d4;
            color: #06b6d4
        }

        .action-btn.quiz:hover {
            background: #06b6d4;
            color: #fff
        }

        .action-btn.vocab {
            border-color: #3b82f6;
            color: #3b82f6
        }

        .action-btn.vocab:hover {
            background: #3b82f6;
            color: #fff
        }

        .action-btn.gender {
            border-color: #6366f1;
            color: #6366f1
        }

        .action-btn.gender:hover {
            background: #6366f1;
            color: #fff
        }

        .action-btn.excel {
            border-color: #10b981;
            color: #10b981
        }

        .action-btn.excel:hover {
            background: #10b981;
            color: #fff
        }

        .action-btn.share {
            border-color: #8b5cf6;
            color: #8b5cf6
        }

        .action-btn.share:hover {
            background: #8b5cf6;
            color: #fff
        }

        .action-btn.disabled {
            opacity: .5;
            pointer-events: none;
            color: var(--text-tertiary);
            border-color: var(--border)
        }

        /* 2 dòng gọn trên mobile: 3 cột => 6 nút = 2 hàng */
        @media(max-width:576px) {
            .notebook-actions {
                grid-template-columns: repeat(3, minmax(0, 1fr))
            }

            .action-btn {
                padding: .65rem .5rem;
                font-size: .82rem
            }

            .action-btn i {
                font-size: 1rem
            }

            .action-btn span {
                white-space: nowrap
            }

            .only-desktop {
                display: none !important
            }
        }

        .only-mobile {
            display: none
        }

        @media(max-width:576px) {
            .only-mobile {
                display: inline-flex
            }
        }

        /* Footer mini actions */
        .notebook-footer {
            padding: 1rem 1.5rem;
            background: var(--surface-secondary);
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem
        }

        .notebook-meta {
            color: var(--text-secondary);
            font-size: .875rem;
            display: flex;
            align-items: center;
            gap: .5rem
        }

        .notebook-actions-mini {
            display: flex;
            gap: .5rem
        }

        .mini-btn {
            padding: .5rem;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--text-secondary);
            transition: .3s;
            text-decoration: none
        }

        .mini-btn:hover {
            background: var(--text-secondary);
            color: #fff;
            text-decoration: none
        }

        .mini-btn.danger:hover {
            background: #ef4444;
            border-color: #ef4444
        }

        /* Empty */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary)
        }

        .empty-state-icon {
            width: 4rem;
            height: 4rem;
            background: var(--primary-gradient);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: #fff;
            font-size: 2rem
        }

        .empty-state h3 {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 1rem
        }

        /* Modal */
        .modal-content {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--shadow-xl)
        }

        .modal-header {
            background: var(--surface-secondary);
            border-bottom: 1px solid var(--border);
            border-radius: var(--border-radius) var(--border-radius) 0 0
        }

        .modal-title {
            font-weight: 600;
            color: var(--text-primary)
        }

        .form-label {
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: .5rem
        }

        .form-control,
        .form-select {
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: .75rem 1rem;
            transition: .3s;
            background: var(--surface)
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, .1);
            outline: none
        }

        .modal-footer {
            border-top: 1px solid var(--border);
            background: var(--surface-secondary)
        }

        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            padding: .75rem 1.5rem;
            border-radius: 10px;
            font-weight: 500
        }

        .btn-success {
            background: var(--success-gradient);
            border: none;
            padding: .75rem 1.5rem;
            border-radius: 10px;
            font-weight: 500
        }

        .btn-secondary {
            background: var(--surface-tertiary);
            border: 1px solid var(--border);
            color: var(--text-secondary);
            padding: .75rem 1.5rem;
            border-radius: 10px;
            font-weight: 500
        }

        .modern-alert {
            background: rgba(102, 126, 234, .1);
            border: 1px solid rgba(102, 126, 234, .2);
            border-radius: var(--border-radius);
            padding: 1rem 1.5rem;
            color: #4c51bf;
            margin-bottom: 2rem
        }

        .fade-in {
            animation: fadeIn .5s ease-in
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px)
            }

            to {
                opacity: 1;
                transform: translateY(0)
            }
        }

        .slide-down {
            animation: slideDown .3s ease-out
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px)
            }

            to {
                opacity: 1;
                transform: translateY(0)
            }
        }

        .d-none {
            display: none !important
        }

        @media(max-width:768px) {
            .main-container {
                margin-top: 0;
                border-radius: 0
            }

            .action-section {
                padding: 1.5rem 1rem 1rem
            }

            .modern-btn {
                padding: .75rem 1.5rem;
                font-size: .9rem
            }

            .group-header {
                padding: 1rem 1.5rem
            }

            .group-icon {
                width: 2.5rem;
                height: 2.5rem;
                font-size: 1.25rem
            }

            .group-title {
                font-size: 1.125rem
            }

            .toggle-icon {
                right: 1.5rem
            }
        }

        .modal,
        .modal-backdrop {
            will-change: opacity, transform;
            backface-visibility: hidden
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="modern-navbar">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <a class="navbar-brand" href="home.php">GERMANLY</a>
                <a href="logout.php" class="logout-btn">
                    <i class="bi bi-box-arrow-right me-2"></i>Đăng xuất
                </a>
            </div>
        </div>
    </nav>

    <div class="main-container">
        <div class="container">
            <!-- Action Section -->
            <div class="action-section">
                <div class="d-flex flex-column flex-md-row align-items-center justify-content-center gap-3">
                    <button class="modern-btn btn-create-group" data-bs-toggle="modal" data-bs-target="#modalAddGroup">
                        <i class="bi bi-folder-plus"></i><span>Tạo nhóm</span>
                    </button>
                    <button class="modern-btn btn-create-notebook" data-bs-toggle="modal" data-bs-target="#modalAddNotebook">
                        <i class="bi bi-journal-plus"></i><span>Tạo sổ tay</span>
                    </button>
                    <a href="import_shared.php" class="modern-btn btn-import">
                        <i class="bi bi-download"></i><span>Nhập chia sẻ</span>
                    </a>
                </div>
            </div>

            <!-- Filter Section (thêm ô tìm kiếm) -->
            <div class="filter-section">
                <form method="get" class="w-100">
                    <div class="row g-3 align-items-center">
                        <div class="col-12 col-md-auto">
                            <label class="filter-label d-flex align-items-center gap-2 mb-0">
                                <i class="bi bi-filter"></i> Lọc theo nhóm:
                            </label>
                        </div>

                        <div class="col-12 col-md-12">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" name="q" value="<?= htmlspecialchars($keyword) ?>" class="form-control search-input" placeholder="Tìm theo tiêu đề hoặc mô tả...">
                                <?php if ($keyword !== ''): ?>
                                    <a class="btn btn-outline-secondary" href="?group=<?= urlencode($selected_group) ?>"><i class="bi bi-x-lg"></i></a>
                                <?php endif; ?>
                                <button class="btn btn-primary" type="submit"><i class="bi bi-arrow-return-left me-1"></i>Tìm</button>
                            </div>
                        </div>

                        <div class="col-12 col-md-3">
                            <select name="group" class="modern-select w-100" onchange="this.form.submit()">
                                <option value="all" <?= $selected_group === 'all' ? 'selected' : '' ?>>Tất cả nhóm</option>
                                <?php foreach ($groups as $g): ?>
                                    <option value="<?= $g['id'] ?>" <?= $selected_group == $g['id'] ? 'selected' : '' ?>><?= htmlspecialchars($g['name']) ?></option>
                                <?php endforeach; ?>
                                <option value="none" <?= $selected_group === 'none' ? 'selected' : '' ?>>Không nhóm</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Alert -->
            <?php if ($message): ?>
                <div class="modern-alert fade-in">
                    <i class="bi bi-info-circle me-2"></i><?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- Empty State -->
            <?php
            $hasNoGroups = count($groups) === 0;
            $hasNoUngroup = empty($notebooks_by_group[null]) && empty($notebooks_by_group[0]);
            $hasNoAny = empty($notebooks);
            if ($hasNoAny):
            ?>
                <div class="empty-state fade-in">
                    <div class="empty-state-icon"><i class="bi bi-folder-plus"></i></div>
                    <h3>Chào mừng đến với GERMANLY</h3>
                    <p class="mb-4">Chưa có sổ tay nào<?= $keyword ? ' khớp từ khóa "' . htmlspecialchars($keyword) . '"' : '' ?>. Bắt đầu bằng cách tạo nhóm/sổ tay mới!</p>
                    <button class="modern-btn btn-create-group" data-bs-toggle="modal" data-bs-target="#modalAddGroup">
                        <i class="bi bi-plus-circle"></i><span>Tạo nhóm mới</span>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Danh sách nhóm -->
            <?php foreach ($groups as $g): ?>
                <?php if ($selected_group === 'all' || $selected_group == $g['id']): ?>
                    <div class="group-card fade-in">
                        <div class="group-header" data-group="<?= $g['id'] ?>">
                            <div class="d-flex align-items-center">
                                <div class="group-icon"><i class="bi bi-folder-fill"></i></div>
                                <h3 class="group-title mb-0"><?= htmlspecialchars($g['name']) ?></h3>

                                <!-- Xoá nhóm -->
                                <form method="get" class="d-inline" onClick="event.stopPropagation()">
                                    <input type="hidden" name="delete_group" value="<?= $g['id'] ?>">
                                    <button class="group-delete-btn" title="Xoá nhóm"
                                        onclick="return confirm('Xoá nhóm này? Các sổ tay sẽ chuyển về không nhóm.')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                            <?php if (!empty($notebooks_by_group[$g['id']])): ?>
                                <i class="bi bi-chevron-down toggle-icon" data-group="<?= $g['id'] ?>"></i>
                            <?php endif; ?>
                        </div>

                        <div class="notebook-grid d-none" id="groupGrid<?= $g['id'] ?>">
                            <?php if (!empty($notebooks_by_group[$g['id']])): ?>
                                <?php foreach ($notebooks_by_group[$g['id']] as $nb): ?>
                                    <div class="notebook-card">
                                        <div class="notebook-header">
                                            <div class="notebook-avatar"><i class="bi bi-journal-text"></i></div>
                                            <div class="notebook-title"><?= htmlspecialchars($nb['title']) ?></div>
                                            <div class="notebook-description">
                                                <?= $nb['description'] ? nl2br(htmlspecialchars($nb['description'])) : 'Chưa có mô tả…' ?>
                                            </div>
                                        </div>

                                        <div class="notebook-actions">
                                            <a href="study_flashcard.php?notebook_id=<?= $nb['id'] ?>" class="action-btn flashcard">
                                                <i class="bi bi-journal-richtext"></i><span>Thẻ</span>
                                            </a>
                                            <a href="study_quiz.php?notebook_id=<?= $nb['id'] ?>" class="action-btn quiz">
                                                <i class="bi bi-question-circle"></i><span>Quiz</span>
                                            </a>
                                            <a href="add_vocab.php?notebook_id=<?= $nb['id'] ?>" class="action-btn vocab">
                                                <i class="bi bi-pencil-square"></i><span>Từ vựng</span>
                                            </a>

                                            <?php $canGender = !empty($genderReady[(int)$nb['id']]); ?>
                                            <?php if ($canGender): ?>
                                                <a href="study_gender.php?notebook_id=<?= $nb['id'] ?>" class="action-btn gender">
                                                    <i class="bi bi-gender-ambiguous"></i><span>Giống</span>
                                                </a>
                                            <?php else: ?>
                                                <span class="action-btn gender disabled" title="Chưa có danh từ có giống">
                                                    <i class="bi bi-gender-ambiguous"></i><span>Giống</span>
                                                </span>
                                            <?php endif; ?>

                                            <a href="import_excel.php?notebook_id=<?= $nb['id'] ?>" class="action-btn excel">
                                                <i class="bi bi-file-earmark-excel"></i><span>Excel</span>
                                            </a>
                                            <a href="share_notebook.php?notebook_id=<?= $nb['id'] ?>" class="action-btn share">
                                                <i class="bi bi-share"></i><span>Chia sẻ</span>
                                            </a>
                                        </div>

                                        <div class="notebook-footer">
                                            <div class="notebook-meta">
                                                <i class="bi bi-collection"></i>
                                                <span><?= (int)($counts[(int)$nb['id']] ?? 0) ?> từ<?php if (!empty($nb['created_at'])): ?> • tạo <?= date('d/m/Y', strtotime($nb['created_at'])) ?><?php endif; ?></span>
                                            </div>
                                             <div class="notebook-actions-mini">
                                                <button
                                                    type="button"
                                                    class="mini-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editNotebookModal"
                                                    data-id="<?= (int)$nb['id'] ?>"
                                                    data-title="<?= htmlspecialchars($nb['title'], ENT_QUOTES) ?>"
                                                    data-desc="<?= htmlspecialchars($nb['description'] ?? '', ENT_QUOTES) ?>"
                                                    data-group="<?= $nb['group_id'] !== null ? (int)$nb['group_id'] : '' ?>"
                                                    title="Sửa">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <a href="?delete=<?= $nb['id'] ?>" class="mini-btn danger" onclick="return confirm('Bạn có chắc chắn muốn xoá sổ tay này?');" title="Xoá">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>

            <!-- Khu “Không thuộc nhóm” -->
            <?php if (($selected_group === 'all' || $selected_group === 'none') && (isset($notebooks_by_group[null]) || isset($notebooks_by_group[0]))): ?>
                <div class="group-card fade-in">
                    <div class="group-header">
                        <div class="d-flex align-items-center">
                            <div class="group-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);">
                                <i class="bi bi-folder2-open"></i>
                            </div>
                            <h3 class="group-title mb-0">Không thuộc nhóm</h3>
                        </div>
                    </div>

                    <div class="notebook-grid">
                        <?php foreach (($notebooks_by_group[null] ?? []) + ($notebooks_by_group[0] ?? []) as $nb): ?>
                            <div class="notebook-card">
                                <div class="notebook-header">
                                    <div class="notebook-avatar" style="background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);">
                                        <i class="bi bi-journal-text"></i>
                                    </div>
                                    <div class="notebook-title"><?= htmlspecialchars($nb['title']) ?></div>
                                    <div class="notebook-description">
                                        <?= $nb['description'] ? nl2br(htmlspecialchars($nb['description'])) : 'Chưa có mô tả…' ?>
                                    </div>
                                </div>

                                <div class="notebook-actions">
                                    <a href="study_flashcard.php?notebook_id=<?= $nb['id'] ?>" class="action-btn flashcard">
                                        <i class="bi bi-journal-richtext"></i><span>Flashcard</span>
                                    </a>
                                    <a href="study_quiz.php?notebook_id=<?= $nb['id'] ?>" class="action-btn quiz">
                                        <i class="bi bi-question-circle"></i><span>Quiz</span>
                                    </a>
                                    <a href="add_vocab.php?notebook_id=<?= $nb['id'] ?>" class="action-btn vocab">
                                        <i class="bi bi-pencil-square"></i><span>Từ vựng</span>
                                    </a>

                                    <?php $canGender = !empty($genderReady[(int)$nb['id']]); ?>
                                    <?php if ($canGender): ?>
                                        <a href="study_gender.php?notebook_id=<?= $nb['id'] ?>" class="action-btn gender">
                                            <i class="bi bi-gender-ambiguous"></i><span>Giống</span>
                                        </a>
                                    <?php else: ?>
                                        <span class="action-btn gender disabled" title="Chưa có danh từ có giống">
                                            <i class="bi bi-gender-ambiguous"></i><span>Giống</span>
                                        </span>
                                    <?php endif; ?>

                                    <a href="import_excel.php?notebook_id=<?= $nb['id'] ?>" class="action-btn excel">
                                        <i class="bi bi-file-earmark-excel"></i><span>Excel</span>
                                    </a>
                                    <a href="share_notebook.php?notebook_id=<?= $nb['id'] ?>" class="action-btn share">
                                        <i class="bi bi-share"></i><span>Chia sẻ</span>
                                    </a>

                                    <!-- Sửa/Xoá chỉ hiện trong grid trên desktop, mobile ẩn để giữ 2 dòng -->
                                    <button
                                        type="button"
                                        class="action-btn only-desktop"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editNotebookModal"
                                        data-id="<?= (int)$nb['id'] ?>"
                                        data-title="<?= htmlspecialchars($nb['title'], ENT_QUOTES) ?>"
                                        data-desc="<?= htmlspecialchars($nb['description'] ?? '', ENT_QUOTES) ?>"
                                        data-group="<?= $nb['group_id'] !== null ? (int)$nb['group_id'] : '' ?>">
                                        <i class="bi bi-pencil"></i><span>Sửa</span>
                                    </button>
                                    <a href="?delete=<?= $nb['id'] ?>" class="action-btn only-desktop" onclick="return confirm('Bạn có chắc chắn muốn xoá sổ tay này?');">
                                        <i class="bi bi-trash"></i><span>Xoá</span>
                                    </a>
                                </div>

                                <div class="notebook-footer">
                                    <div class="notebook-meta">
                                        <i class="bi bi-collection"></i>
                                        <span><?= (int)($counts[(int)$nb['id']] ?? 0) ?> từ<?php if (!empty($nb['created_at'])): ?> • tạo <?= date('d/m/Y', strtotime($nb['created_at'])) ?><?php endif; ?></span>
                                    </div>
                                     <div class="notebook-actions-mini">
                                        <button
                                            type="button"
                                            class="mini-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editNotebookModal"
                                            data-id="<?= (int)$nb['id'] ?>"
                                            data-title="<?= htmlspecialchars($nb['title'], ENT_QUOTES) ?>"
                                            data-desc="<?= htmlspecialchars($nb['description'] ?? '', ENT_QUOTES) ?>"
                                            data-group="<?= $nb['group_id'] !== null ? (int)$nb['group_id'] : '' ?>"
                                            title="Sửa">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                         <a href="share_notebook.php?notebook_id=<?= $nb['id'] ?>" class="mini-btn" title="Chia sẻ">
                                             <i class="bi bi-share"></i>
                                         </a>
                                        <a href="?delete=<?= $nb['id'] ?>" class="mini-btn danger" onclick="return confirm('Bạn có chắc chắn muốn xoá sổ tay này?');" title="Xoá">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal tạo nhóm -->
    <div class="modal fade" id="modalAddGroup" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="post" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-folder-plus me-2"></i>Tạo nhóm mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tên nhóm</label>
                        <input type="text" name="group_name" class="form-control" placeholder="Nhập tên nhóm..." required>
                        <div class="form-text">Ví dụ: Từ vựng cơ bản, Ngữ pháp nâng cao…</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="add_group" class="btn btn-primary"><i class="bi bi-plus-circle me-2"></i>Tạo nhóm</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal tạo sổ tay -->
    <div class="modal fade" id="modalAddNotebook" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="post" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-journal-plus me-2"></i>Tạo sổ tay mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tiêu đề</label>
                        <input type="text" name="title" class="form-control" placeholder="Nhập tiêu đề sổ tay..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mô tả</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Mô tả ngắn…"></textarea>
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
                    <button type="submit" name="add_notebook" class="btn btn-success"><i class="bi bi-plus-circle me-2"></i>Tạo sổ tay</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal CHỈNH SỬA dùng chung -->
    <div class="modal fade" id="editNotebookModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="post" class="modal-content" id="editNotebookForm">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Chỉnh sửa sổ tay</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="notebook_id" id="editNotebookId" value="">
                    <div class="mb-3">
                        <label class="form-label">Tiêu đề</label>
                        <input type="text" name="title" id="editNotebookTitle" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mô tả</label>
                        <textarea name="description" id="editNotebookDesc" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nhóm</label>
                        <select name="group_id" id="editNotebookGroup" class="form-select">
                            <option value="">-- Không thuộc nhóm --</option>
                            <?php foreach ($groups as $g): ?>
                                <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="edit_notebook" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Lưu thay đổi
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                </div>
            </form>
        </div>
    </div>

    <!-- libs -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle nhóm
            document.querySelectorAll('.group-header').forEach(function(header) {
                const groupId = header.getAttribute('data-group');
                const grid = document.getElementById('groupGrid' + groupId);
                const icon = header.querySelector('.toggle-icon');
                if (!grid) return;

                if (icon) icon.style.transform = grid.classList.contains('d-none') ? 'rotate(0deg)' : 'rotate(180deg)';

                const toggle = () => {
                    const isHidden = grid.classList.contains('d-none');
                    if (isHidden) {
                        grid.classList.remove('d-none');
                        grid.classList.add('slide-down');
                        if (icon) icon.style.transform = 'rotate(180deg)';
                    } else {
                        grid.classList.add('d-none');
                        grid.classList.remove('slide-down');
                        if (icon) icon.style.transform = 'rotate(0deg)';
                    }
                };

                header.addEventListener('click', function(e) {
                    if (e.target.closest('button[type="submit"], .group-delete-btn, form')) return;
                    toggle();
                });
                if (icon) {
                    icon.addEventListener('click', function(e) {
                        e.stopPropagation();
                        toggle();
                    });
                }
            });

            // Chặn nổi bọt ở form GET (xoá nhóm)
            document.querySelectorAll('form[method="get"] button[type="submit"]').forEach(btn => {
                btn.addEventListener('click', e => e.stopPropagation());
            });

            // Hover card
            document.querySelectorAll('.notebook-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-4px)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });

            // Ripple (loại trừ nút mở modal)
            document.querySelectorAll(
                '.modern-btn, .action-btn:not([data-bs-toggle="modal"]), .mini-btn:not([data-bs-toggle="modal"])'
            ).forEach(btn => {
                btn.style.position = 'relative';
                btn.style.overflow = 'hidden';
                btn.addEventListener('click', function(e) {
                    const ripple = document.createElement('span');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    ripple.style.width = ripple.style.height = size + 'px';
                    ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
                    ripple.style.top = (e.clientY - rect.top - size / 2) + 'px';
                    ripple.classList.add('ripple');
                    this.appendChild(ripple);
                    setTimeout(() => ripple.remove(), 600);
                });
            });

            // Auto-hide alert
            setTimeout(function() {
                const alert = document.querySelector('.modern-alert');
                if (alert) {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    setTimeout(() => alert.remove(), 300);
                }
            }, 5000);

            // Modal EDIT: đổ dữ liệu động
            const editModalEl = document.getElementById('editNotebookModal');
            if (editModalEl) {
                editModalEl.addEventListener('show.bs.modal', function(event) {
                    const btn = event.relatedTarget;
                    if (!btn) return;

                    const id = btn.getAttribute('data-id') || '';
                    const title = btn.getAttribute('data-title') || '';
                    const desc = btn.getAttribute('data-desc') || '';
                    const group = btn.getAttribute('data-group') ?? '';

                    document.getElementById('editNotebookId').value = id;
                    document.getElementById('editNotebookTitle').value = title;
                    document.getElementById('editNotebookDesc').value = desc;

                    const select = document.getElementById('editNotebookGroup');
                    if (select) {
                        Array.from(select.options).forEach(opt => {
                            opt.selected = (opt.value === (group === null ? '' : String(group)));
                        });
                    }
                });
            }
        });

        // Ripple CSS runtime
        const style = document.createElement('style');
        style.textContent = `
            .ripple{position:absolute;border-radius:50%;background:rgba(255,255,255,.3);pointer-events:none;transform:scale(0);animation:ripple-animation .6s linear;}
            @keyframes ripple-animation{to{transform:scale(2);opacity:0;}}
        `;
        document.head.appendChild(style);
    </script>
</body>

</html>
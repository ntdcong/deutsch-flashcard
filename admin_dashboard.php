<?php
session_start();
require 'db.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Xử lý các hành động quản lý người dùng
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Thay đổi quyền người dùng
    if ($action === 'change_role') {
        $target_user_id = (int)$_POST['user_id'];
        $new_role = $_POST['new_role'];
        
        if ($new_role === 'admin' || $new_role === 'user') {
            $stmt = $pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
            $stmt->execute([$new_role, $target_user_id]);
            $message = 'Đã cập nhật quyền người dùng!';
        } else {
            $error = 'Quyền không hợp lệ!';
        }
    }
    
    // Xóa người dùng
    if ($action === 'delete_user') {
        $target_user_id = (int)$_POST['user_id'];
        
        // Không cho phép xóa chính mình
        if ($target_user_id === $user_id) {
            $error = 'Không thể xóa tài khoản của chính bạn!';
        } else {
            // Xóa dữ liệu liên quan
            $pdo->prepare('DELETE FROM learning_status WHERE user_id = ?')->execute([$target_user_id]);
            $pdo->prepare('DELETE FROM vocabularies WHERE notebook_id IN (SELECT id FROM notebooks WHERE user_id = ?)')->execute([$target_user_id]);
            $pdo->prepare('DELETE FROM notebooks WHERE user_id = ?')->execute([$target_user_id]);
            $pdo->prepare('DELETE FROM notebook_groups WHERE user_id = ?')->execute([$target_user_id]);
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$target_user_id]);
            $message = 'Đã xóa người dùng và dữ liệu liên quan!';
        }
    }
}

// Lấy danh sách người dùng
$stmt = $pdo->query('SELECT id, email, role, created_at FROM users ORDER BY created_at DESC');
$users = $stmt->fetchAll();

// Thống kê
$stats = [
    'total_users' => $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'total_notebooks' => $pdo->query('SELECT COUNT(*) FROM notebooks')->fetchColumn(),
    'total_vocabularies' => $pdo->query('SELECT COUNT(*) FROM vocabularies')->fetchColumn(),
    'recent_users' => $pdo->query('SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)')->fetchColumn(),
    'recent_vocabularies' => $pdo->query('SELECT COUNT(*) FROM vocabularies WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)')->fetchColumn(),
];

// Lấy top 5 người dùng có nhiều từ vựng nhất
$stmt = $pdo->query('SELECT u.email, COUNT(v.id) as vocab_count 
                    FROM users u 
                    JOIN notebooks n ON u.id = n.user_id 
                    JOIN vocabularies v ON n.id = v.notebook_id 
                    GROUP BY u.id 
                    ORDER BY vocab_count DESC 
                    LIMIT 5');
$top_users = $stmt->fetchAll();

// Lấy danh sách tất cả các sổ tay
$stmt = $pdo->query('SELECT n.id, n.title, n.description, n.created_at, u.email as user_email, 
                    (SELECT COUNT(*) FROM vocabularies WHERE notebook_id = n.id) as vocab_count 
                    FROM notebooks n 
                    JOIN users u ON n.user_id = u.id 
                    ORDER BY n.created_at DESC');
$all_notebooks = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Germanly</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --admin-primary: #4b6584;
            --admin-secondary: #778ca3;
            --admin-accent: #ff6b6b;
            --admin-light: #f5f6fa;
            --admin-dark: #2f3542;
        }
        
        body {
            background-color: var(--admin-light);
            font-family: 'Segoe UI', Roboto, sans-serif;
        }
        
        .admin-sidebar {
            background: var(--admin-dark);
            min-height: 100vh;
            color: white;
            position: fixed;
            width: 250px;
            left: 0;
            top: 0;
            z-index: 1000;
            transition: all 0.3s;
        }
        
        .admin-content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        .admin-header {
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            color: var(--admin-accent);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--admin-dark);
        }
        
        .stat-label {
            color: var(--admin-secondary);
            font-size: 0.9rem;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            border-radius: 5px;
            margin: 5px 0;
            transition: all 0.3s;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .nav-link i {
            margin-right: 10px;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .user-table img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .user-role {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .role-admin {
            background: rgba(255,107,107,0.1);
            color: #ff6b6b;
        }
        
        .role-user {
            background: rgba(46,213,115,0.1);
            color: #2ed573;
        }
        
        .action-btn {
            padding: 5px 10px;
            font-size: 0.8rem;
        }
        
        @media (max-width: 768px) {
            .admin-sidebar {
                width: 70px;
                overflow: hidden;
            }
            
            .admin-sidebar .logo {
                font-size: 1.2rem;
                text-align: center;
                padding: 15px 5px;
            }
            
            .admin-sidebar .nav-link span {
                display: none;
            }
            
            .admin-sidebar .nav-link i {
                margin-right: 0;
                font-size: 1.2rem;
            }
            
            .admin-content {
                margin-left: 70px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="admin-sidebar">
        <div class="logo text-center">
            <i class="bi bi-lightning-charge text-warning"></i> Germanly
        </div>
        <div class="mt-4">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="#dashboard" data-bs-toggle="tab">
                        <i class="bi bi-speedometer2"></i>
                        <span>Tổng quan</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#users" data-bs-toggle="tab">
                        <i class="bi bi-people"></i>
                        <span>Quản lý người dùng</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#content" data-bs-toggle="tab">
                        <i class="bi bi-journal-text"></i>
                        <span>Quản lý nội dung</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#settings" data-bs-toggle="tab">
                        <i class="bi bi-gear"></i>
                        <span>Cài đặt</span>
                    </a>
                </li>
                <li class="nav-item mt-5">
                    <a class="nav-link" href="home.php">
                        <i class="bi bi-house"></i>
                        <span>Về trang chủ</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Đăng xuất</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="admin-content">
        <!-- Header -->
        <div class="admin-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Admin Dashboard</h4>
            <div>
                <span class="me-3"><?= date('d/m/Y') ?></span>
                <span class="badge bg-primary">Admin</span>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <!-- Tab Content -->
        <div class="tab-content">
            <!-- Dashboard Tab -->
            <div class="tab-pane fade show active" id="dashboard">
                <h5 class="mb-4">Thống kê hệ thống</h5>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-value"><?= $stats['total_users'] ?></div>
                                    <div class="stat-label">Tổng số người dùng</div>
                                </div>
                                <div class="stat-icon">
                                    <i class="bi bi-people"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-value"><?= $stats['total_notebooks'] ?></div>
                                    <div class="stat-label">Tổng số sổ tay</div>
                                </div>
                                <div class="stat-icon">
                                    <i class="bi bi-journal-bookmark"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-value"><?= $stats['total_vocabularies'] ?></div>
                                    <div class="stat-label">Tổng số từ vựng</div>
                                </div>
                                <div class="stat-icon">
                                    <i class="bi bi-card-text"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="stat-card">
                            <h6>Người dùng mới (7 ngày qua)</h6>
                            <div class="stat-value"><?= $stats['recent_users'] ?></div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="stat-card">
                            <h6>Từ vựng mới (7 ngày qua)</h6>
                            <div class="stat-value"><?= $stats['recent_vocabularies'] ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="stat-card">
                            <h6 class="mb-4">Top 5 người dùng tích cực</h6>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Email</th>
                                        <th>Số từ vựng</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_users as $user): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td><?= $user['vocab_count'] ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users Tab -->
            <div class="tab-pane fade" id="users">
                <h5 class="mb-4">Quản lý người dùng</h5>
                
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Email</th>
                                        <th>Quyền</th>
                                        <th>Ngày tạo</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= $user['id'] ?></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td>
                                            <span class="user-role <?= $user['role'] === 'admin' ? 'role-admin' : 'role-user' ?>">
                                                <?= $user['role'] === 'admin' ? 'Admin' : 'User' ?>
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-primary action-btn" 
                                                        data-bs-toggle="modal" data-bs-target="#changeRoleModal"
                                                        data-user-id="<?= $user['id'] ?>"
                                                        data-user-email="<?= htmlspecialchars($user['email']) ?>"
                                                        data-user-role="<?= $user['role'] ?>">
                                                    <i class="bi bi-shield"></i> Đổi quyền
                                                </button>
                                                
                                                <?php if ($user['id'] !== $user_id): ?>
                                                <button type="button" class="btn btn-sm btn-outline-danger action-btn"
                                                        data-bs-toggle="modal" data-bs-target="#deleteUserModal"
                                                        data-user-id="<?= $user['id'] ?>"
                                                        data-user-email="<?= htmlspecialchars($user['email']) ?>">
                                                    <i class="bi bi-trash"></i> Xóa
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Tab -->
            <div class="tab-pane fade" id="content">
                <h5 class="mb-4">Quản lý nội dung</h5>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Danh sách tất cả các sổ tay</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Tiêu đề</th>
                                                <th>Người tạo</th>
                                                <th>Số từ vựng</th>
                                                <th>Ngày tạo</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($all_notebooks as $notebook): ?>
                                            <tr>
                                                <td><?= $notebook['id'] ?></td>
                                                <td><?= htmlspecialchars($notebook['title']) ?></td>
                                                <td><?= htmlspecialchars($notebook['user_email']) ?></td>
                                                <td><span class="badge bg-info"><?= $notebook['vocab_count'] ?></span></td>
                                                <td><?= date('d/m/Y', strtotime($notebook['created_at'])) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings Tab -->
            <div class="tab-pane fade" id="settings">
                <h5 class="mb-4">Cài đặt hệ thống</h5>
                
                <div class="card">
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="mb-3">
                                <label class="form-label">Tiêu đề trang web</label>
                                <input type="text" class="form-control" value="Germanly - Học tiếng Đức hiệu quả">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Mô tả trang web</label>
                                <textarea class="form-control" rows="3">Ứng dụng học từ vựng tiếng Đức với flashcard và quiz</textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Email liên hệ</label>
                                <input type="email" class="form-control" value="contact@germanly.com">
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="enableRegistration" checked>
                                <label class="form-check-label" for="enableRegistration">Cho phép đăng ký tài khoản mới</label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Lưu cài đặt
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Role Modal -->
    <div class="modal fade" id="changeRoleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thay đổi quyền người dùng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="change_role">
                        <input type="hidden" name="user_id" id="changeRoleUserId">
                        
                        <p>Bạn đang thay đổi quyền cho người dùng: <strong id="changeRoleUserEmail"></strong></p>
                        
                        <div class="mb-3">
                            <label class="form-label">Quyền mới</label>
                            <select name="new_role" class="form-select" id="newRoleSelect">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Xóa người dùng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="user_id" id="deleteUserId">
                        
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i> Cảnh báo: Hành động này không thể hoàn tác!
                        </div>
                        
                        <p>Bạn có chắc chắn muốn xóa người dùng: <strong id="deleteUserEmail"></strong>?</p>
                        <p>Tất cả dữ liệu liên quan đến người dùng này sẽ bị xóa vĩnh viễn, bao gồm:</p>
                        <ul>
                            <li>Tài khoản người dùng</li>
                            <li>Tất cả sổ tay và nhóm sổ tay</li>
                            <li>Tất cả từ vựng và tiến trình học tập</li>
                        </ul>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-danger">Xác nhận xóa</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Xử lý modal đổi quyền
        document.querySelectorAll('[data-bs-target="#changeRoleModal"]').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                const userEmail = this.getAttribute('data-user-email');
                const userRole = this.getAttribute('data-user-role');
                
                document.getElementById('changeRoleUserId').value = userId;
                document.getElementById('changeRoleUserEmail').textContent = userEmail;
                document.getElementById('newRoleSelect').value = userRole;
            });
        });
        
        // Xử lý modal xóa người dùng
        document.querySelectorAll('[data-bs-target="#deleteUserModal"]').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                const userEmail = this.getAttribute('data-user-email');
                
                document.getElementById('deleteUserId').value = userId;
                document.getElementById('deleteUserEmail').textContent = userEmail;
            });
        });
    </script>
</body>
</html>
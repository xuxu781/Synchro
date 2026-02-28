<?php
/**
 * Copyright (c) 2026 徐州邻兔跃IT工作室
 * 联系邮箱：lintuyue@qq.com
 * 官方网站：lintuyue.com
 * 技术交流QQ群：550133641
 * 
 * 版权所有，禁止未经授权的复制、传播、修改或商业使用。
 * 未经开发者书面授权，任何单位或个人不得以任何形式复制、传播、
 * 展示、修改本软件或制作衍生作品。
 */
require_once __DIR__ . '/../includes/functions.php';

Auth::requirePermission('users_manage');

$db = Database::getInstance();

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_action = $_POST['action'] ?? '';
    $post_id = $_POST['id'] ?? '';
    if ($post_action === 'edit' && $post_id) {
        $action = 'edit';
        $id = $post_id;
    }
}

if ($action === 'delete' && $id) {
    $currentUser = Auth::user();
    if ($id == $currentUser['id']) {
        set_flash_message('error', '不能删除自己的账户');
    } else {
        $userToDelete = $db->fetchOne("SELECT username, name FROM `lty-admin_users` WHERE id = ?", [$id]);
        $db->delete('`lty-admin_users`', 'id = ?', [$id]);
        OperationLog::delete('admin_users', '删除后台用户：' . ($userToDelete['username'] ?? $id), 'admin_user', $id, $userToDelete);
        set_flash_message('success', '删除成功');
    }
    redirect('lty-admin/admin_users.php');
}

if ($action === 'toggle_status' && $id) {
    $currentUser = Auth::user();
    if ($id == $currentUser['id']) {
        set_flash_message('error', '不能禁用自己的账户');
    } else {
        $user = $db->fetchOne("SELECT status, username FROM `lty-admin_users` WHERE id = ?", [$id]);
        if ($user) {
            $newStatus = $user['status'] == 1 ? 0 : 1;
            $db->update('`lty-admin_users`', ['status' => $newStatus], 'id = ?', [$id]);
            OperationLog::update('admin_users', ($newStatus == 1 ? '启用' : '禁用') . '后台用户：' . $user['username'], 'admin_user', $id, ['new_status' => $newStatus]);
            set_flash_message('success', $newStatus == 1 ? '已启用' : '已禁用');
        }
    }
    redirect('lty-admin/admin_users.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $password = $_POST['password'] ?? '';
        $role_id = !empty($_POST['role_id']) ? (int)$_POST['role_id'] : null;
        $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;
        $avatar = trim($_POST['avatar'] ?? '');
        
        if (empty($username) || empty($email)) {
            $error = '请填写用户名和邮箱';
            $showModal = true;
            $adminUser = [
                'username' => $username,
                'email' => $email,
                'name' => $name,
                'role_id' => $role_id,
                'status' => $status,
                'avatar' => $avatar
            ];
            if ($action === 'edit' && $id) {
                $adminUser['id'] = $id;
            }
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = '邮箱格式不正确';
            $showModal = true;
            $adminUser = [
                'username' => $username,
                'email' => $email,
                'name' => $name,
                'role_id' => $role_id,
                'status' => $status,
                'avatar' => $avatar
            ];
            if ($action === 'edit' && $id) {
                $adminUser['id'] = $id;
            }
        } else {
            $checkUsername = $db->fetchOne(
                "SELECT id FROM `lty-admin_users` WHERE username = ?" . ($action === 'edit' && $id ? " AND id != ?" : ""),
                $action === 'edit' && $id ? [$username, $id] : [$username]
            );
            
            if ($checkUsername) {
                $error = '用户名已存在';
                $showModal = true;
                $adminUser = [
                    'username' => $username,
                    'email' => $email,
                    'name' => $name,
                    'role_id' => $role_id,
                    'status' => $status,
                    'avatar' => $avatar
                ];
                if ($action === 'edit' && $id) {
                    $adminUser['id'] = $id;
                }
            } else {
                $checkEmail = $db->fetchOne(
                    "SELECT id FROM `lty-admin_users` WHERE email = ?" . ($action === 'edit' && $id ? " AND id != ?" : ""),
                    $action === 'edit' && $id ? [$email, $id] : [$email]
                );
                
                if ($checkEmail) {
                    $error = '邮箱已被使用';
                    $showModal = true;
                    $adminUser = [
                        'username' => $username,
                        'email' => $email,
                        'name' => $name,
                        'role_id' => $role_id,
                        'status' => $status,
                        'avatar' => $avatar
                    ];
                    if ($action === 'edit' && $id) {
                        $adminUser['id'] = $id;
                    }
                } else {
                    $data = [
                        'username' => $username,
                        'email' => $email,
                        'name' => $name,
                        'role_id' => $role_id,
                        'status' => $status,
                        'avatar' => $avatar
                    ];
                    
                    if ($action === 'add') {
                        if (empty($password)) {
                            $error = '请填写密码';
                            $showModal = true;
                            $adminUser = $data;
                        } elseif (strlen($password) < 6) {
                            $error = '密码长度至少6位';
                            $showModal = true;
                            $adminUser = $data;
                        } else {
                            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
                            $newId = $db->insert('`lty-admin_users`', $data);
                            OperationLog::create('admin_users', '创建后台用户：' . $username, 'admin_user', $newId, ['username' => $username, 'email' => $email, 'name' => $name]);
                            set_flash_message('success', '添加成功');
                            redirect('lty-admin/admin_users.php');
                        }
                    } else {
                        if (!empty($password)) {
                            if (strlen($password) < 6) {
                                $error = '密码长度至少6位';
                                $showModal = true;
                                $adminUser = $data;
                                $adminUser['id'] = $id;
                            } else {
                                $data['password'] = password_hash($password, PASSWORD_DEFAULT);
                                $db->update('`lty-admin_users`', $data, 'id = ?', [$id]);
                                OperationLog::update('admin_users', '更新后台用户：' . $username, 'admin_user', $id, ['username' => $username, 'email' => $email, 'name' => $name]);
                                set_flash_message('success', '更新成功');
                                redirect('lty-admin/admin_users.php');
                            }
                        } else {
                            $db->update('`lty-admin_users`', $data, 'id = ?', [$id]);
                            OperationLog::update('admin_users', '更新后台用户：' . $username, 'admin_user', $id, ['username' => $username, 'email' => $email, 'name' => $name]);
                            set_flash_message('success', '更新成功');
                            redirect('lty-admin/admin_users.php');
                        }
                    }
                }
            }
        }
    }
}

$success = get_flash_message('success');
$flash_error = get_flash_message('error');

if (!isset($showModal)) {
    $showModal = false;
}
if (!isset($adminUser)) {
    $adminUser = null;
}
if (!isset($error)) {
    $error = null;
}

if ($error) {
    $showModal = true;
} elseif ($flash_error) {
    $error = $flash_error;
    if ($action === 'add' || ($action === 'edit' && $id)) {
        $showModal = true;
    }
}

if ($showModal && $action === 'edit' && $id && !$adminUser) {
    $adminUser = $db->fetchOne("SELECT * FROM `lty-admin_users` WHERE id = ?", [$id]);
}

if (($action === 'edit' && $id) || $showModal) {
    if (!$adminUser) {
        $adminUser = $db->fetchOne("SELECT * FROM `lty-admin_users` WHERE id = ?", [$id]);
    }
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($search) {
    $searchParam = '%' . $search . '%';
    $adminUsers = $db->fetchAll(
        "SELECT u.*, r.name as role_name 
         FROM `lty-admin_users` u 
         LEFT JOIN `lty-roles` r ON u.role_id = r.id 
         WHERE u.username LIKE ? OR u.email LIKE ? OR u.name LIKE ? 
         ORDER BY u.id DESC",
        [$searchParam, $searchParam, $searchParam]
    );
} else {
    $adminUsers = $db->fetchAll(
        "SELECT u.*, r.name as role_name 
         FROM `lty-admin_users` u 
         LEFT JOIN `lty-roles` r ON u.role_id = r.id 
         ORDER BY u.id DESC"
    );
}

$roles = $db->fetchAll("SELECT id, name, slug FROM `lty-roles` WHERE status = 1 ORDER BY sort_order ASC");

$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台用户管理 - <?php echo htmlspecialchars(Settings::get('site_name', '官网系统')); ?></title>
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        .status-toggle {
            cursor: pointer;
            transition: all 0.2s;
        }
        .status-toggle:hover {
            opacity: 0.8;
        }
        .password-hint {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>后台用户管理</h1>
            <div style="display: flex; gap: 10px; align-items: center;">
                <div class="search-form" style="display: flex; gap: 8px;">
                    <input type="text" name="search" placeholder="搜索用户名、邮箱、姓名..." value="<?php echo htmlspecialchars($search); ?>" style="padding: 8px 12px; border: 1px solid #e5e7eb; border-radius: 6px; width: 220px;">
                    <button type="button" class="btn btn-outline" onclick="clearSearch()" style="<?php echo $search ? '' : 'display: none;'; ?>">清除</button>
                </div>
                <button onclick="openModal()" class="btn btn-primary">添加用户</button>
            </div>
        </div>

        <div class="content-card">
            <div class="card-body" style="padding: 0;">
                <?php if ($success): ?>
                    <div class="alert alert-success" style="margin: 16px;"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>用户</th>
                                <th>用户名</th>
                                <th>邮箱</th>
                                <th>角色</th>
                                <th>最后登录</th>
                                <th>状态</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($adminUsers as $u): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <?php 
                                            $uAvatar = get_avatar_url($u['avatar'], $u['email'], 64);
                                            if ($uAvatar): ?>
                                                <img src="<?php echo htmlspecialchars($uAvatar); ?>" alt="" class="avatar-preview" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                                <div class="avatar-preview avatar-placeholder" style="display:none;">
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#ffffff">
                                                        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                        <circle cx="12" cy="7" r="4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                </div>
                                            <?php else: ?>
                                                <div class="avatar-preview avatar-placeholder">
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#ffffff">
                                                        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                        <circle cx="12" cy="7" r="4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div style="font-weight: 500;"><?php echo htmlspecialchars($u['name'] ?: $u['username']); ?></div>
                                                <?php if ($u['id'] == $user['id']): ?>
                                                    <span style="font-size: 12px; color: #6b7280;">当前用户</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($u['username']); ?></td>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td>
                                        <?php if ($u['role_name']): ?>
                                            <span class="badge badge-info"><?php echo htmlspecialchars($u['role_name']); ?></span>
                                        <?php else: ?>
                                            <span style="color: #9ca3af;">未分配</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($u['last_login']): ?>
                                            <?php echo date('Y-m-d H:i', strtotime($u['last_login'])); ?>
                                        <?php else: ?>
                                            <span style="color: #9ca3af;">从未登录</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($u['id'] == $user['id']): ?>
                                            <span class="badge badge-success">当前</span>
                                        <?php else: ?>
                                            <a href="admin_users.php?action=toggle_status&id=<?php echo $u['id']; ?>" class="status-toggle">
                                                <?php if ($u['status'] == 1): ?>
                                                    <span class="badge badge-success">启用</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">禁用</span>
                                                <?php endif; ?>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <a href="admin_users.php?action=edit&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline">编辑</a>
                                            <?php if ($u['id'] != $user['id']): ?>
                                                <a href="admin_users.php?action=delete&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('确定要删除此用户吗？');">删除</a>
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

        <div class="main-footer">
            <div class="footer-content">
                <span class="footer-line">© 2026 <?php echo htmlspecialchars(Settings::get('site_name', '官网系统')); ?> 1.0</span>
                <span class="footer-line">徐州邻兔跃IT工作室提供技术支持版权所有</span>
            </div>
        </div>
    </div>

    <div id="userModal" class="modal <?php echo ($action === 'add' || ($action === 'edit' && $adminUser) || $showModal) ? 'active' : ''; ?>">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3><?php echo ($action === 'edit' && $id) ? '编辑用户' : '添加用户'; ?></h3>
                <button onclick="closeModal()" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form method="POST" autocomplete="off">
                    <input type="hidden" name="action" value="<?php echo $action; ?>">
                    <?php if ($action === 'edit' && $id): ?>
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username">用户名 *</label>
                            <input type="text" id="username" name="username" required value="<?php echo $adminUser ? htmlspecialchars($adminUser['username']) : ''; ?>" placeholder="登录用户名">
                        </div>
                        <div class="form-group">
                            <label for="email">邮箱 *</label>
                            <input type="email" id="email" name="email" required value="<?php echo $adminUser ? htmlspecialchars($adminUser['email']) : ''; ?>" placeholder="邮箱地址">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">姓名</label>
                            <input type="text" id="name" name="name" value="<?php echo $adminUser ? htmlspecialchars($adminUser['name'] ?? '') : ''; ?>" placeholder="显示名称">
                        </div>
                        <div class="form-group">
                            <label for="role_id">角色</label>
                            <select id="role_id" name="role_id">
                                <option value="">-- 选择角色 --</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role['id']; ?>" <?php echo ($adminUser && $adminUser['role_id'] == $role['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($role['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">密码 <?php echo $action === 'add' ? '*' : ''; ?></label>
                        <input type="password" id="password" name="password" <?php echo $action === 'add' ? 'required' : ''; ?> placeholder="<?php echo $action === 'edit' ? '留空则不修改' : '至少6位'; ?>" autocomplete="new-password">
                        <div class="password-hint"><?php echo $action === 'edit' ? '留空则不修改密码，否则至少6位' : '密码长度至少6位'; ?></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="avatar">头像URL</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="text" id="avatar" name="avatar" value="<?php echo $adminUser ? htmlspecialchars($adminUser['avatar'] ?? '') : ''; ?>" placeholder="头像图片URL" style="flex: 1;">
                            <button type="button" class="btn btn-outline" onclick="openMediaLibrary('avatar')">选择图片</button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">状态</label>
                        <select id="status" name="status">
                            <option value="1" <?php echo ($adminUser && $adminUser['status'] == 1) ? 'selected' : (!$adminUser ? 'selected' : ''); ?>>启用</option>
                            <option value="0" <?php echo ($adminUser && $adminUser['status'] == 0) ? 'selected' : ''; ?>>禁用</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary"><?php echo $id ? '更新' : '添加'; ?></button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openMediaLibrary(inputId) {
            MediaPicker.open({
                multiple: false,
                onSelect: function(url) {
                    const input = document.getElementById(inputId);
                    if (input) {
                        input.value = url;
                    }
                }
            });
        }
        
        function openModal() {
            window.location.href = 'admin_users.php?action=add';
        }
        
        function closeModal() {
            document.getElementById('userModal').classList.remove('active');
            window.location.href = 'admin_users.php';
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.opacity = '0';
                    alert.style.transition = 'opacity 0.5s ease';
                    setTimeout(function() {
                        alert.remove();
                    }, 500);
                }, 3000);
            });
            
            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput) {
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        const searchValue = searchInput.value.trim();
                        if (searchValue) {
                            window.location.href = 'admin_users.php?search=' + encodeURIComponent(searchValue);
                        } else {
                            window.location.href = 'admin_users.php';
                        }
                    }
                });
            }
        });
        
        function clearSearch() {
            window.location.href = 'admin_users.php';
        }
    </script>
    
    <?php include 'components/media-picker.php'; ?>
    <link rel="stylesheet" href="components/media-picker.css">
    <script src="components/media-picker.js"></script>
</body>
</html>

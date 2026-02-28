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

Auth::requirePermission('roles_manage');

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
    $role = $db->fetchOne("SELECT * FROM `lty-roles` WHERE id = ?", [$id]);
    if ($role && $role['is_system'] == 1) {
        set_flash_message('error', '系统角色不能删除');
    } else {
        $roleName = $role ? $role['name'] : '未知';
        $db->delete('`lty-roles`', 'id = ?', [$id]);
        OperationLog::delete('roles', '删除角色：' . $roleName, 'role', $id, ['name' => $roleName]);
        set_flash_message('success', '删除成功');
    }
    redirect('lty-admin/roles.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;
        $permissions = $_POST['permissions'] ?? [];
        $email_notify_enabled = isset($_POST['email_notify_enabled']) ? (int)$_POST['email_notify_enabled'] : 0;
        $email_notify_types = $_POST['email_notify_types'] ?? [];
        $email_notify_content = trim($_POST['email_notify_content'] ?? '');
        
        if ($action === 'add') {
            $maxSortOrder = $db->fetchOne("SELECT MAX(sort_order) as max_sort FROM `lty-roles`");
            $sort_order = ($maxSortOrder['max_sort'] ?? 0) + 1;
        } else {
            $existingRole = $db->fetchOne("SELECT sort_order FROM `lty-roles` WHERE id = ?", [$id]);
            $sort_order = $existingRole['sort_order'] ?? 0;
        }
        
        if (empty($name) || empty($slug)) {
            $error = '请填写角色名称和标识';
            $showModal = true;
            $role = [
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'status' => $status,
                'sort_order' => $sort_order
            ];
            if ($action === 'edit' && $id) {
                $role['id'] = $id;
            }
        } else {
            $checkSlug = $db->fetchOne(
                "SELECT id FROM `lty-roles` WHERE slug = ?" . ($action === 'edit' && $id ? " AND id != ?" : ""),
                $action === 'edit' && $id ? [$slug, $id] : [$slug]
            );
            
            if ($checkSlug) {
                $error = '角色标识已存在，请使用其他标识';
                $showModal = true;
                $role = [
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $description,
                    'status' => $status,
                    'sort_order' => $sort_order
                ];
                if ($action === 'edit' && $id) {
                    $role['id'] = $id;
                }
            } else {
                $data = [
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $description,
                    'status' => $status,
                    'sort_order' => $sort_order,
                    'email_notify_enabled' => $email_notify_enabled,
                    'email_notify_types' => json_encode($email_notify_types),
                    'email_notify_content' => $email_notify_content
                ];
                
                if ($action === 'edit' && $id) {
                    $existingRole = $db->fetchOne("SELECT is_system FROM `lty-roles` WHERE id = ?", [$id]);
                    if ($existingRole && $existingRole['is_system'] == 1) {
                        unset($data['slug']);
                    }
                    $db->update('`lty-roles`', $data, 'id = ?', [$id]);
                    
                    if (Auth::isSuperAdmin() || Auth::hasPermission('permissions_manage')) {
                        $db->delete('`lty-role_permissions`', 'role_id = ?', [$id]);
                        foreach ($permissions as $permId) {
                            $db->insert('`lty-role_permissions`', [
                                'role_id' => $id,
                                'permission_id' => $permId
                            ]);
                        }
                    }
                    
                    OperationLog::update('roles', '更新角色：' . $name, 'role', $id, ['name' => $name, 'slug' => $slug]);
                    
                    set_flash_message('success', '更新成功');
                    redirect('lty-admin/roles.php');
                } else {
                    $roleId = $db->insert('`lty-roles`', $data);
                    
                    if (Auth::isSuperAdmin() || Auth::hasPermission('permissions_manage')) {
                        foreach ($permissions as $permId) {
                            $db->insert('`lty-role_permissions`', [
                                'role_id' => $roleId,
                                'permission_id' => $permId
                            ]);
                        }
                    }
                    
                    OperationLog::create('roles', '创建角色：' . $name, 'role', $roleId, ['name' => $name, 'slug' => $slug]);
                    
                    set_flash_message('success', '添加成功');
                    redirect('lty-admin/roles.php');
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
if (!isset($role)) {
    $role = null;
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

if ($showModal && $action === 'edit' && $id && !$role) {
    $role = $db->fetchOne("SELECT * FROM `lty-roles` WHERE id = ?", [$id]);
}

if (($action === 'edit' && $id) || $showModal) {
    if (!$role) {
        $role = $db->fetchOne("SELECT * FROM `lty-roles` WHERE id = ?", [$id]);
    }
}

$roles = $db->fetchAll("SELECT r.*, 
    (SELECT COUNT(*) FROM `lty-admin_users` WHERE role_id = r.id) as user_count,
    (SELECT COUNT(*) FROM `lty-role_permissions` WHERE role_id = r.id) as permission_count
    FROM `lty-roles` r 
    ORDER BY r.sort_order ASC, r.id ASC");

$permissions = $db->fetchAll("SELECT * FROM `lty-permissions` WHERE status = 1 ORDER BY sort_order ASC, id ASC");

$permissionGroups = [];
$groupOrder = [];
$customGroupOrder = [
    '仪表盘' => 1,
    '媒体' => 2,
    '官网管理' => 3,
    '互动管理' => 4,
    '开发配置' => 5,
    '系统设置' => 6,
    '其他' => 99
];
foreach ($permissions as $perm) {
    $group = $perm['group'] ?? '其他';
    if (!isset($permissionGroups[$group])) {
        $permissionGroups[$group] = [];
        $groupOrder[$group] = $customGroupOrder[$group] ?? 99;
    }
    $permissionGroups[$group][] = $perm;
}

uksort($permissionGroups, function($a, $b) use ($groupOrder) {
    return ($groupOrder[$a] ?? 99) - ($groupOrder[$b] ?? 99);
});

$rolePermissions = [];
if ($action === 'edit' && $id) {
    $rolePerms = $db->fetchAll("SELECT permission_id FROM `lty-role_permissions` WHERE role_id = ?", [$id]);
    foreach ($rolePerms as $rp) {
        $rolePermissions[] = $rp['permission_id'];
    }
}

$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>团队角色管理 - <?php echo htmlspecialchars(Settings::get('site_name', '官网系统')); ?></title>
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        .permission-groups {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }
        .permission-group {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
        }
        .permission-group-title {
            font-weight: 600;
            color: #374151;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .permission-group-title input[type="checkbox"] {
            width: 16px;
            height: 16px;
        }
        .permission-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .permission-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 8px;
            background: white;
            border-radius: 4px;
        }
        .permission-item input[type="checkbox"] {
            width: 16px;
            height: 16px;
        }
        .permission-item label {
            flex: 1;
            cursor: pointer;
            font-size: 14px;
        }
        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .role-badge.system {
            background: #fef3c7;
            color: #92400e;
        }
        .role-badge.custom {
            background: #dbeafe;
            color: #1e40af;
        }
        .draggable-row {
            cursor: move;
            transition: background-color 0.2s ease;
        }
        .draggable-row:hover {
            background-color: #f8fafc;
        }
        .draggable-row.dragging {
            opacity: 0.5;
            background-color: #e0f2fe;
        }
        .drag-placeholder {
            animation: pulse 1.5s ease-in-out infinite;
        }
        .drag-placeholder td {
            padding: 10px;
            background: #fff9c4;
            border: 2px dashed #fbc02d;
            text-align: center;
            color: #f9a825;
        }
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
        }
        .drag-handle {
            user-select: none;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>团队角色管理</h1>
            <button onclick="openModal()" class="btn btn-primary">添加角色</button>
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
                                <th>角色名称</th>
                                <th>标识</th>
                                <th>描述</th>
                                <th>用户数</th>
                                <th>权限数</th>
                                <th>状态</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody class="draggable-tbody">
                            <?php foreach ($roles as $roleItem): ?>
                                <tr data-id="<?php echo $roleItem['id']; ?>" class="draggable-row" draggable="true">
                                    <td>
                                        <span class="drag-handle" style="cursor: move; margin-right: 8px; color: #9ca3af;">⋮⋮</span>
                                        <?php echo htmlspecialchars($roleItem['name']); ?>
                                        <?php if ($roleItem['is_system'] == 1): ?>
                                            <span class="role-badge system">系统</span>
                                        <?php else: ?>
                                            <span class="role-badge custom">自定义</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><code><?php echo htmlspecialchars($roleItem['slug']); ?></code></td>
                                    <td><?php echo htmlspecialchars($roleItem['description'] ?: '-'); ?></td>
                                    <td><?php echo $roleItem['user_count']; ?></td>
                                    <td><?php echo $roleItem['permission_count']; ?></td>
                                    <td>
                                        <?php if ($roleItem['status'] == 1): ?>
                                            <span class="badge badge-success">启用</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">禁用</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <a href="roles.php?action=edit&id=<?php echo $roleItem['id']; ?>" class="btn btn-sm btn-outline" draggable="false">编辑</a>
                                            <?php if ($roleItem['is_system'] != 1): ?>
                                                <a href="roles.php?action=delete&id=<?php echo $roleItem['id']; ?>" class="btn btn-sm btn-danger" draggable="false" onclick="return confirm('确定要删除此角色吗？');">删除</a>
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

    <div id="roleModal" class="modal <?php echo ($action === 'add' || ($action === 'edit' && $role) || $showModal) ? 'active' : ''; ?>">
        <div class="modal-content" style="max-width: 900px; max-height: 90vh; overflow-y: auto;">
            <div class="modal-header">
                <h3><?php echo ($action === 'edit' && $id) ? '编辑角色' : '添加角色'; ?></h3>
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
                            <label for="name">角色名称 *</label>
                            <input type="text" id="name" name="name" required value="<?php echo $role ? htmlspecialchars($role['name']) : ''; ?>" placeholder="如：内容编辑">
                        </div>
                        <div class="form-group">
                            <label for="slug">角色标识 *</label>
                            <input type="text" id="slug" name="slug" required value="<?php echo $role ? htmlspecialchars($role['slug']) : ''; ?>" placeholder="如：content_editor" <?php echo ($role && $role['is_system'] == 1) ? 'readonly' : ''; ?>>
                            <small style="color: #6b7280; font-size: 12px;">用于程序识别，只能包含字母、数字和下划线</small>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="status">状态</label>
                            <select id="status" name="status">
                                <option value="1" <?php echo ($role && $role['status'] == 1) ? 'selected' : (!$role ? 'selected' : ''); ?>>启用</option>
                                <option value="0" <?php echo ($role && $role['status'] == 0) ? 'selected' : ''; ?>>禁用</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">描述</label>
                        <textarea id="description" name="description" rows="2" placeholder="角色描述"><?php echo $role ? htmlspecialchars($role['description'] ?? '') : ''; ?></textarea>
                    </div>
                    
                    <div class="form-section" style="background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-top: 16px;">
                        <h4 style="margin: 0 0 16px 0; font-size: 15px; color: #374151; display: flex; align-items: center; gap: 8px;">
                            <svg width="18" height="18" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path d="M853.333333 213.333333H170.666667c-46.933333 0-85.333333 38.4-85.333334 85.333334v426.666666c0 46.933333 38.4 85.333333 85.333334 85.333334h682.666666c46.933333 0 85.333333-38.4 85.333334-85.333334V298.666667c0-46.933333-38.4-85.333333-85.333334-85.333334z m0 512H170.666667V341.333333h682.666666v384z" fill="currentColor"/></svg>
                            邮件通知设置
                        </h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email_notify_enabled">启用邮件通知</label>
                                <select id="email_notify_enabled" name="email_notify_enabled">
                                    <option value="0" <?php echo ($role && ($role['email_notify_enabled'] ?? 0) == 0) ? 'selected' : (!$role ? 'selected' : ''); ?>>禁用</option>
                                    <option value="1" <?php echo ($role && ($role['email_notify_enabled'] ?? 0) == 1) ? 'selected' : ''; ?>>启用</option>
                                </select>
                                <small style="color: #6b7280; font-size: 12px;">启用后该角色成员将收到邮件通知</small>
                            </div>
                            <div class="form-group">
                                <label>通知类型</label>
                                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px;">
                                    <?php 
                                    $notifyTypes = [
                                        'contact' => '留言通知',
                                        'system' => '系统通知',
                                        'order' => '订单通知'
                                    ];
                                    $selectedTypes = $role ? json_decode($role['email_notify_types'] ?? '[]', true) : [];
                                    if (!is_array($selectedTypes)) $selectedTypes = [];
                                    foreach ($notifyTypes as $typeKey => $typeName): 
                                    ?>
                                    <label style="display: flex; align-items: center; gap: 4px; padding: 6px 12px; background: white; border: 1px solid #e5e7eb; border-radius: 6px; cursor: pointer; font-size: 13px;">
                                        <input type="checkbox" name="email_notify_types[]" value="<?php echo $typeKey; ?>" <?php echo in_array($typeKey, $selectedTypes) ? 'checked' : ''; ?>>
                                        <?php echo $typeName; ?>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="form-group" style="margin-top: 12px;">
                            <label for="email_notify_content">自定义通知内容</label>
                            <textarea id="email_notify_content" name="email_notify_content" rows="3" placeholder="可自定义邮件通知内容，留空则使用默认模板。支持变量：{name} {email} {phone} {subject} {message} {time}"><?php echo $role ? htmlspecialchars($role['email_notify_content'] ?? '') : ''; ?></textarea>
                            <small style="color: #6b7280; font-size: 12px;">可自定义邮件内容，支持HTML代码</small>
                        </div>
                    </div>
                    
                    <?php if (Auth::isSuperAdmin() || Auth::hasPermission('permissions_manage')): ?>
                    <div class="form-group">
                        <label>权限配置</label>
                        <div style="margin-bottom: 12px;">
                            <button type="button" class="btn btn-sm btn-outline" onclick="selectAllPermissions()">全选</button>
                            <button type="button" class="btn btn-sm btn-outline" onclick="deselectAllPermissions()">取消全选</button>
                        </div>
                        <div class="permission-groups">
                            <?php foreach ($permissionGroups as $groupName => $groupPerms): ?>
                                <div class="permission-group">
                                    <div class="permission-group-title">
                                        <input type="checkbox" onchange="toggleGroupPermissions(this)" id="group_<?php echo htmlspecialchars($groupName); ?>">
                                        <label for="group_<?php echo htmlspecialchars($groupName); ?>"><?php echo htmlspecialchars($groupName); ?></label>
                                    </div>
                                    <div class="permission-list">
                                        <?php foreach ($groupPerms as $perm): ?>
                                            <div class="permission-item">
                                                <input type="checkbox" name="permissions[]" value="<?php echo $perm['id']; ?>" id="perm_<?php echo $perm['id']; ?>" <?php echo in_array($perm['id'], $rolePermissions) ? 'checked' : ''; ?> onchange="updateGroupCheckbox(this)">
                                                <label for="perm_<?php echo $perm['id']; ?>" title="<?php echo htmlspecialchars($perm['description'] ?? ''); ?>">
                                                    <?php echo htmlspecialchars($perm['name']); ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="form-group">
                        <label>权限配置</label>
                        <div style="padding: 20px; background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 8px; text-align: center; color: #6b7280;">
                            <svg width="48" height="48" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg" style="margin-bottom: 12px; opacity: 0.5;">
                                <path d="M512 128c-212 0-384 172-384 384s172 384 384 384 384-172 384-384-172-384-384-384z m0 640c-141.4 0-256-114.6-256-256s114.6-256 256-256 256 114.6 256 256-114.6 256-256 256z" fill="currentColor"/>
                                <path d="M464 384h96v256h-96zM464 704h96v96h-96z" fill="currentColor"/>
                            </svg>
                            <p style="margin: 0; font-size: 14px;">您没有权限管理权限配置</p>
                            <p style="margin: 4px 0 0 0; font-size: 12px; color: #9ca3af;">请联系管理员获取「权限管理」权限</p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div style="margin-top: 20px;">
                        <button type="submit" class="btn btn-primary"><?php echo $id ? '更新' : '添加'; ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openModal() {
            window.location.href = 'roles.php?action=add';
        }
        
        function closeModal() {
            document.getElementById('roleModal').classList.remove('active');
            window.location.href = 'roles.php';
        }
        
        function toggleGroupPermissions(checkbox) {
            const group = checkbox.closest('.permission-group');
            const checkboxes = group.querySelectorAll('.permission-item input[type="checkbox"]');
            checkboxes.forEach(cb => cb.checked = checkbox.checked);
        }
        
        function updateGroupCheckbox(checkbox) {
            const group = checkbox.closest('.permission-group');
            const allCheckboxes = group.querySelectorAll('.permission-item input[type="checkbox"]');
            const groupCheckbox = group.querySelector('.permission-group-title input[type="checkbox"]');
            
            if (groupCheckbox) {
                const allChecked = Array.from(allCheckboxes).every(cb => cb.checked);
                groupCheckbox.checked = allChecked;
            }
        }
        
        function selectAllPermissions() {
            const checkboxes = document.querySelectorAll('.permission-item input[type="checkbox"]');
            checkboxes.forEach(cb => cb.checked = true);
            document.querySelectorAll('.permission-group-title input[type="checkbox"]').forEach(cb => cb.checked = true);
        }
        
        function deselectAllPermissions() {
            const checkboxes = document.querySelectorAll('.permission-item input[type="checkbox"]');
            checkboxes.forEach(cb => cb.checked = false);
            document.querySelectorAll('.permission-group-title input[type="checkbox"]').forEach(cb => cb.checked = false);
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
            
            <?php if ($action === 'edit' && $id): ?>
            document.querySelectorAll('.permission-group').forEach(group => {
                const allCheckboxes = group.querySelectorAll('.permission-item input[type="checkbox"]');
                const groupCheckbox = group.querySelector('.permission-group-title input[type="checkbox"]');
                if (groupCheckbox && allCheckboxes.length > 0) {
                    const allChecked = Array.from(allCheckboxes).every(cb => cb.checked);
                    groupCheckbox.checked = allChecked;
                }
            });
            <?php endif; ?>
            
            const tbody = document.querySelector('.draggable-tbody');
            if (tbody) {
                let draggedRow = null;
                let placeholder = null;
                
                tbody.addEventListener('dragstart', function(e) {
                    draggedRow = e.target.closest('.draggable-row');
                    if (!draggedRow) return;
                    e.dataTransfer.effectAllowed = 'move';
                    draggedRow.classList.add('dragging');
                    e.dataTransfer.setData('text/html', draggedRow.innerHTML);
                });
                
                tbody.addEventListener('dragend', function(e) {
                    if (draggedRow) {
                        draggedRow.classList.remove('dragging');
                        if (placeholder) {
                            placeholder.remove();
                            placeholder = null;
                        }
                        draggedRow = null;
                    }
                });
                
                tbody.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                    
                    const targetRow = e.target.closest('.draggable-row');
                    if (targetRow && targetRow !== draggedRow) {
                        const rect = targetRow.getBoundingClientRect();
                        const midY = rect.top + rect.height / 2;
                        
                        if (!placeholder) {
                            placeholder = document.createElement('tr');
                            placeholder.className = 'drag-placeholder';
                            placeholder.innerHTML = '<td colspan="7">拖放到此处</td>';
                        }
                        
                        if (e.clientY < midY) {
                            tbody.insertBefore(placeholder, targetRow);
                        } else {
                            tbody.insertBefore(placeholder, targetRow.nextSibling);
                        }
                    }
                });
                
                tbody.addEventListener('drop', function(e) {
                    e.preventDefault();
                    
                    if (draggedRow && placeholder) {
                        tbody.insertBefore(draggedRow, placeholder);
                        placeholder.remove();
                        placeholder = null;
                        
                        updateSortOrder(tbody);
                    }
                });
                
                function updateSortOrder(tbody) {
                    const rows = tbody.querySelectorAll('.draggable-row');
                    
                    fetch('../api/roles.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'update_sort',
                            data: Array.from(rows).map((row, index) => ({
                                id: row.dataset.id,
                                sort_order: index + 1
                            }))
                        })
                    }).then(response => response.json())
                      .then(result => {
                          if (result.success) {
                              console.log('排序更新成功');
                          } else {
                              console.error('排序更新失败:', result.error);
                          }
                      })
                      .catch(error => {
                          console.error('请求失败:', error);
                      });
                }
            }
        });
    </script>
</body>
</html>

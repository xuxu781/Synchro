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

Auth::requirePermission('members_manage');

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
    $member = $db->fetchOne("SELECT name FROM `lty-team_members` WHERE id = ?", [$id]);
    $db->delete('`lty-team_members`', 'id = ?', [$id]);
    OperationLog::delete('members', '删除团队成员：' . ($member['name'] ?? $id), 'team_member', $id);
    set_flash_message('success', '删除成功');
    redirect('lty-admin/members.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $members_title = trim($_POST['members_title'] ?? '');
    $members_subtitle = trim($_POST['members_subtitle'] ?? '');
    $members_bg_image = trim($_POST['members_bg_image'] ?? '');
    
    Settings::set('members_title', $members_title);
    Settings::set('members_subtitle', $members_subtitle);
    Settings::set('members_bg_image', $members_bg_image);
    
    OperationLog::update('members', '更新团队成员页面设置', 'settings', null, ['title' => $members_title, 'subtitle' => $members_subtitle]);
    
    set_flash_message('success', '设置保存成功');
    redirect('lty-admin/members.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        $name = trim($_POST['name'] ?? '');
        $position = trim($_POST['position'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $avatar = trim($_POST['avatar'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $employee_id = isset($_POST['employee_id']) ? trim($_POST['employee_id']) : '';
        $status = isset($_POST['status']) ? (int)$_POST['status'] : 0;
        
        if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['avatar_file'];
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 2 * 1024 * 1024;
            
            if ($file['size'] > $maxSize) {
                set_flash_message('error', '文件大小不能超过2MB');
                redirect('lty-admin/members.php?action=' . $action . ($action === 'edit' && $id ? '&id=' . $id : ''));
            }
            
            if (!in_array($file['type'], $allowedTypes)) {
                set_flash_message('error', '不支持的文件类型，请上传 JPG、PNG、GIF 或 WebP 格式的图片');
                redirect('lty-admin/members.php?action=' . $action . ($action === 'edit' && $id ? '&id=' . $id : ''));
            }
            
            $uploadDir = __DIR__ . '/../uploads/members/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'member_' . time() . '_' . uniqid() . '.' . $extension;
            $uploadPath = $uploadDir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $avatar = '/uploads/members/' . $filename;
            }
        }
        
        if ($action === 'add') {
            $maxSortOrder = $db->fetchOne("SELECT MAX(sort_order) as max_sort FROM `lty-team_members`");
            $sort_order = ($maxSortOrder['max_sort'] ?? 0) + 1;
        } else {
            $existingMember = $db->fetchOne("SELECT sort_order FROM `lty-team_members` WHERE id = ?", [$id]);
            $sort_order = $existingMember['sort_order'] ?? 0;
        }
        
        if (empty($name) || empty($position)) {
            $error = '请填写姓名和职位';
            $showModal = true;
            
            $member = [
                'name' => $name,
                'position' => $position,
                'bio' => $bio,
                'avatar' => $avatar,
                'email' => $email,
                'phone' => $phone,
                'employee_id' => $employee_id,
                'sort_order' => $sort_order,
                'status' => $status
            ];
            
            if ($action === 'edit' && $id) {
                $member['id'] = $id;
            }
        }
        
        if (empty($error) && !empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = '请输入正确的邮箱格式';
            $showModal = true;
            
            $member = [
                'name' => $name,
                'position' => $position,
                'bio' => $bio,
                'avatar' => $avatar,
                'email' => $email,
                'phone' => $phone,
                'employee_id' => $employee_id,
                'sort_order' => $sort_order,
                'status' => $status
            ];
            
            if ($action === 'edit' && $id) {
                $member['id'] = $id;
            }
        }
        
        if (empty($error) && !empty($phone) && !preg_match('/^1[3-9]\d{9}$/', $phone)) {
            $error = '请输入正确的手机号格式（11位数字，以1开头）';
            $showModal = true;
            
            $member = [
                'name' => $name,
                'position' => $position,
                'bio' => $bio,
                'avatar' => $avatar,
                'email' => $email,
                'phone' => $phone,
                'employee_id' => $employee_id,
                'sort_order' => $sort_order,
                'status' => $status
            ];
            
            if ($action === 'edit' && $id) {
                $member['id'] = $id;
            }
        }
        
        if (empty($error) && !empty($email)) {
            $checkEmail = $db->fetchOne(
                "SELECT id FROM `lty-team_members` WHERE email = ?" . ($action === 'edit' && $id ? " AND id != ?" : ""),
                $action === 'edit' && $id ? [$email, $id] : [$email]
            );
            
            if ($checkEmail) {
                $error = '邮箱已存在，请使用其他邮箱';
                $showModal = true;
                
                $member = [
                    'name' => $name,
                    'position' => $position,
                    'bio' => $bio,
                    'avatar' => $avatar,
                    'email' => $email,
                    'phone' => $phone,
                    'employee_id' => $employee_id,
                    'sort_order' => $sort_order,
                    'status' => $status
                ];
                
                if ($action === 'edit' && $id) {
                    $member['id'] = $id;
                }
            }
        }
        
        if (empty($error) && !empty($employee_id)) {
            $checkEmployeeId = $db->fetchOne(
                "SELECT id FROM `lty-team_members` WHERE employee_id = ?" . ($action === 'edit' && $id ? " AND id != ?" : ""),
                $action === 'edit' && $id ? [$employee_id, $id] : [$employee_id]
            );
            
            if ($checkEmployeeId) {
                $error = '工号已存在，请使用其他工号';
                $showModal = true;
                
                $member = [
                    'name' => $name,
                    'position' => $position,
                    'bio' => $bio,
                    'avatar' => $avatar,
                    'email' => $email,
                    'phone' => $phone,
                    'employee_id' => $employee_id,
                    'sort_order' => $sort_order,
                    'status' => $status
                ];
                
                if ($action === 'edit' && $id) {
                    $member['id'] = $id;
                }
            }
        }
        
        if (empty($error)) {
            $data = [
                'name' => $name,
                'position' => $position,
                'bio' => $bio,
                'avatar' => $avatar,
                'email' => $email,
                'phone' => $phone,
                'employee_id' => $employee_id,
                'sort_order' => $sort_order,
                'status' => $status
            ];
            
            if ($action === 'edit' && $id) {
                $db->update('`lty-team_members`', $data, 'id = ?', [$id]);
                OperationLog::update('members', '更新团队成员：' . $name, 'team_member', $id, ['name' => $name, 'position' => $position]);
                set_flash_message('success', '更新成功');
                redirect('lty-admin/members.php');
            } else {
                $newId = $db->insert('`lty-team_members`', $data);
                OperationLog::create('members', '创建团队成员：' . $name, 'team_member', $newId, ['name' => $name, 'position' => $position]);
                set_flash_message('success', '添加成功');
                redirect('lty-admin/members.php');
            }
        }
    }
}

$success = get_flash_message('success');
$flash_error = get_flash_message('error');

if (!isset($showModal)) {
    $showModal = false;
}
if (!isset($member)) {
    $member = null;
}
if (!isset($error)) {
    $error = null;
}

if ($action === 'add') {
    $member = null;
}

if ($error) {
    $showModal = true;
} elseif ($flash_error) {
    $error = $flash_error;
    if ($action === 'add' || ($action === 'edit' && $id)) {
        $showModal = true;
    }
}

if ($action === 'edit' && $id && !$member) {
    $member = $db->fetchOne("SELECT * FROM `lty-team_members` WHERE id = ?", [$id]);
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($search) {
    $searchParam = '%' . $search . '%';
    $members = $db->fetchAll(
        "SELECT * FROM `lty-team_members` WHERE name LIKE ? OR position LIKE ? OR employee_id LIKE ? OR email LIKE ? OR phone LIKE ? ORDER BY sort_order ASC, id ASC",
        [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam]
    );
} else {
    $members = $db->fetchAll("SELECT * FROM `lty-team_members` ORDER BY sort_order ASC, id ASC");
}

$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>团队成员管理 - <?php echo htmlspecialchars(Settings::get('site_name', '官网系统')); ?></title>
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>团队成员管理</h1>
            <div style="display: flex; gap: 10px; align-items: center;">
                <div class="search-form" style="display: flex; gap: 8px;">
                    <input type="text" name="search" placeholder="搜索姓名、职位、工号..." value="<?php echo htmlspecialchars($search); ?>" style="padding: 8px 12px; border: 1px solid #e5e7eb; border-radius: 6px; width: 200px;">
                    <button type="button" class="btn btn-outline" onclick="clearSearch()" style="<?php echo $search ? '' : 'display: none;'; ?>">清除</button>
                </div>
                <button onclick="openSettingsModal()" class="btn btn-outline">页面标题设置</button>
                <button onclick="openModal()" class="btn btn-primary">添加成员</button>
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
                                <th>头像</th>
                                <th>姓名</th>
                                <th>职位</th>
                                <th>工号</th>
                                <th>邮箱</th>
                                <th>排序</th>
                                <th>状态</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody id="membersTableBody">
                            <?php foreach ($members as $member): ?>
                                <tr>
                                    <td>
                                        <?php 
                                        $memberAvatar = get_avatar_url($member['avatar'], $member['email'], 64);
                                        if ($memberAvatar): ?>
                                            <img src="<?php echo htmlspecialchars($memberAvatar); ?>" alt="<?php echo htmlspecialchars($member['name']); ?>" class="avatar-preview" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                            <div class="avatar-preview avatar-placeholder" style="display:none;">
                                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#ffffff">
                                                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <circle cx="12" cy="7" r="4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            </div>
                                        <?php else: ?>
                                            <div class="avatar-preview avatar-placeholder">
                                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#ffffff">
                                                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <circle cx="12" cy="7" r="4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($member['name']); ?></td>
                                    <td><?php echo htmlspecialchars($member['position']); ?></td>
                                    <td><?php echo htmlspecialchars($member['employee_id'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($member['email'] ?: '-'); ?></td>
                                    <td><?php echo $member['sort_order']; ?></td>
                                    <td>
                                        <?php if ($member['status'] == 1): ?>
                                            <span class="badge badge-success">在职</span>
                                        <?php elseif ($member['status'] == 2): ?>
                                            <span class="badge badge-warning">离职</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">隐藏</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <a href="members.php?action=edit&id=<?php echo $member['id']; ?>" class="btn btn-sm btn-outline">编辑</a>
                                            <a href="members.php?action=delete&id=<?php echo $member['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('确定要删除吗？');">删除</a>
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

    <div id="memberModal" class="modal <?php echo ($action === 'add' || ($action === 'edit' && $member) || $showModal) ? 'active' : ''; ?>">
        <div class="modal-content">
            <div class="modal-header">
                <h3><?php echo ($action === 'edit' && $id) ? '编辑成员' : '添加成员'; ?></h3>
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
                    
                    <div class="form-group">
                        <label for="name">姓名 *</label>
                        <input type="text" id="name" name="name" required value="<?php echo $member ? htmlspecialchars($member['name']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="position">职位 *</label>
                        <input type="text" id="position" name="position" required value="<?php echo $member ? htmlspecialchars($member['position']) : ''; ?>">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">邮箱</label>
                            <input type="email" id="email" name="email" value="<?php echo $member ? htmlspecialchars($member['email']) : ''; ?>" placeholder="lintuyue@qq.com" oninput="validateEmail(this)">
                            <small id="email_hint" style="color: #6b7280; font-size: 12px; margin-top: 4px; display: block;">请输入有效的邮箱地址</small>
                        </div>
                        <div class="form-group">
                            <label for="phone">电话</label>
                            <input type="text" id="phone" name="phone" value="<?php echo $member ? htmlspecialchars($member['phone']) : ''; ?>" placeholder="请输入11位手机号" maxlength="11" oninput="validatePhone(this)">
                            <small id="phone_hint" style="color: #6b7280; font-size: 12px; margin-top: 4px; display: block;">请输入11位手机号，以1开头</small>
                        </div>
                        <div class="form-group">
                            <label for="employee_id">工号</label>
                            <input type="text" id="employee_id" name="employee_id" value="<?php echo $member ? htmlspecialchars($member['employee_id']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="avatar">头像URL</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="text" id="avatar" name="avatar" value="<?php echo $member ? htmlspecialchars($member['avatar']) : ''; ?>" placeholder="点击选择图片或手动输入图片URL" oninput="previewUrlImage(this)" style="flex: 1;">
                            <button type="button" class="btn btn-outline" onclick="openMediaLibrary('avatar')">选择图片</button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>头像预览</label>
                        <div id="avatar_preview" style="margin-top: 10px;">
                            <?php if ($member && !empty($member['avatar'])): ?>
                                <div style="position: relative; display: inline-block;">
                                    <img src="<?php echo htmlspecialchars($member['avatar']); ?>" alt="预览" style="max-width: 150px; max-height: 150px; object-fit: cover; border-radius: 50%;">
                                    <button type="button" onclick="removeAvatar()" style="position: absolute; top: -8px; right: -8px; width: 24px; height: 24px; border: none; border-radius: 50%; background: #ef4444; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 16px; line-height: 1; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">×</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="bio">简介</label>
                        <textarea id="bio" name="bio" rows="4"><?php echo $member ? htmlspecialchars($member['bio']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">状态</label>
                        <select id="status" name="status">
                            <option value="0" <?php echo ($member && $member['status'] == 0) ? 'selected' : ''; ?>>隐藏</option>
                            <option value="1" <?php echo ($member && $member['status'] == 1) ? 'selected' : (!$member ? 'selected' : ''); ?>>在职</option>
                            <option value="2" <?php echo ($member && $member['status'] == 2) ? 'selected' : ''; ?>>离职</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary"><?php echo $id ? '更新' : '添加'; ?></button>
                </form>
            </div>
        </div>
    </div>

    <div id="settingsModal" class="modal <?php echo isset($_GET['open_settings']) ? 'active' : ''; ?>">
        <div class="modal-content">
            <div class="modal-header">
                <h3>页面标题设置</h3>
                <button onclick="closeSettingsModal()" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="save_settings" value="1">
                    <div class="form-group">
                        <label for="members_title">团队成员标题</label>
                        <input type="text" id="members_title" name="members_title" value="<?php echo htmlspecialchars(Settings::get('members_title', '团队成员')); ?>">
                    </div>
                    <div class="form-group">
                        <label for="members_subtitle">团队成员副标题</label>
                        <input type="text" id="members_subtitle" name="members_subtitle" value="<?php echo htmlspecialchars(Settings::get('members_subtitle', '认识我们的专业团队')); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">保存设置</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function validateEmail(input) {
            var hint = document.getElementById('email_hint');
            var value = input.value.trim();
            
            if (value === '') {
                hint.style.color = '#6b7280';
                hint.textContent = '请输入有效的邮箱地址';
                input.style.borderColor = '';
                return true;
            }
            
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (emailRegex.test(value)) {
                hint.style.color = '#10b981';
                hint.textContent = '✓ 邮箱格式正确';
                input.style.borderColor = '#10b981';
                return true;
            } else {
                hint.style.color = '#ef4444';
                hint.textContent = '✗ 邮箱格式不正确';
                input.style.borderColor = '#ef4444';
                return false;
            }
        }
        
        function validatePhone(input) {
            var hint = document.getElementById('phone_hint');
            var value = input.value.trim();
            
            value = value.replace(/\D/g, '');
            input.value = value;
            
            if (value === '') {
                hint.style.color = '#6b7280';
                hint.textContent = '请输入11位手机号，以1开头';
                input.style.borderColor = '';
                return true;
            }
            
            var phoneRegex = /^1[3-9]\d{9}$/;
            if (phoneRegex.test(value)) {
                hint.style.color = '#10b981';
                hint.textContent = '✓ 手机号格式正确';
                input.style.borderColor = '#10b981';
                return true;
            } else if (value.length < 11) {
                hint.style.color = '#f59e0b';
                hint.textContent = '已输入 ' + value.length + ' 位，还需 ' + (11 - value.length) + ' 位';
                input.style.borderColor = '#f59e0b';
                return false;
            } else {
                hint.style.color = '#ef4444';
                hint.textContent = '✗ 手机号格式不正确，请检查';
                input.style.borderColor = '#ef4444';
                return false;
            }
        }
        
        function openMediaLibrary(inputId) {
            MediaPicker.open({
                multiple: false,
                onSelect: function(url) {
                    const input = document.getElementById(inputId);
                    if (input) {
                        input.value = url;
                        previewUrlImage(input);
                    }
                }
            });
        }
        
        function openModal() {
            window.location.href = 'members.php?action=add';
        }
        
        function closeModal() {
            document.getElementById('memberModal').classList.remove('active');
            window.location.href = 'members.php';
        }
        
        function openSettingsModal() {
            document.getElementById('settingsModal').classList.add('active');
        }
        
        function closeSettingsModal() {
            document.getElementById('settingsModal').classList.remove('active');
        }
        
        function clearForm() {
            const form = document.querySelector('#memberModal form');
            if (!form) return;
            
            form.querySelectorAll('input[type="text"], input[type="email"], textarea').forEach(function(input) {
                input.value = '';
            });
            
            form.querySelectorAll('select').forEach(function(select) {
                if (select.name === 'status') {
                    select.value = '1';
                } else {
                    select.selectedIndex = 0;
                }
            });
            
            const avatarPreview = document.getElementById('avatar_preview');
            if (avatarPreview) {
                avatarPreview.innerHTML = '';
            }
            
            const avatarInput = document.getElementById('avatar');
            if (avatarInput) {
                avatarInput.value = '';
            }
            
            document.getElementById('email_hint').textContent = '请输入有效的邮箱地址';
            document.getElementById('email_hint').style.color = '#6b7280';
            document.getElementById('phone_hint').textContent = '请输入11位手机号，以1开头';
            document.getElementById('phone_hint').style.color = '#6b7280';
        }
        
        let pendingImageFile = null;
        
        function previewImage(input) {
            const preview = document.getElementById('avatar_preview');
            const urlInput = document.getElementById('avatar');
            
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                
                const maxSize = 2 * 1024 * 1024;
                if (file.size > maxSize) {
                    alert('文件大小不能超过2MB');
                    input.value = '';
                    return;
                }
                
                if (!allowedTypes.includes(file.type)) {
                    alert('不支持的文件类型，请上传 JPG、PNG、GIF 或 WebP 格式的图片');
                    input.value = '';
                    return;
                }
                
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="预览" style="max-width: 150px; max-height: 150px; object-fit: cover; border-radius: 50%;">';
                    
                    const formData = new FormData();
                    formData.append('member_avatar', file);
                    
                    fetch('/api/settings.php?action=upload_member', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.url) {
                            urlInput.value = data.url;
                            pendingImageFile = null;
                            input.dataset.fileSelected = 'false';
                        } else {
                            alert('上传失败：' + (data.message || '未知错误'));
                        }
                    })
                    .catch(error => {
                        console.error('上传错误:', error);
                        alert('上传失败：' + error.message);
                    });
                };
                reader.readAsDataURL(file);
            }
        }
        
        function previewUrlImage(input) {
            const preview = document.getElementById('avatar_preview');
            const url = input.value.trim();
            
            if (url) {
                preview.innerHTML = '<div style="position: relative; display: inline-block;"><img src="' + url + '" alt="预览" style="max-width: 150px; max-height: 150px; object-fit: cover; border-radius: 50%;" onerror="this.src=\'\'; this.parentElement.innerHTML=\'<span style=\'color: #999;\'>图片加载失败</span>\'"><button type="button" onclick="removeAvatar()" style="position: absolute; top: -8px; right: -8px; width: 24px; height: 24px; border: none; border-radius: 50%; background: #ef4444; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 16px; line-height: 1; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">×</button></div>';
            } else {
                preview.innerHTML = '';
            }
        }
        
        function removeAvatar() {
            if (!confirm('确定要移除头像吗？')) return;
            const input = document.getElementById('avatar');
            const preview = document.getElementById('avatar_preview');
            input.value = '';
            preview.innerHTML = '';
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const fileInput = document.getElementById('avatar_file');
                    const urlInput = document.getElementById('avatar');
                    
                    if (fileInput.dataset.fileSelected === 'true') {
                        e.preventDefault();
                        alert('请等待图片上传完成后再提交');
                        return;
                    }
                    
                    var emailInput = document.getElementById('email');
                    var phoneInput = document.getElementById('phone');
                    
                    if (emailInput && emailInput.value.trim() && !validateEmail(emailInput)) {
                        e.preventDefault();
                        alert('请输入正确的邮箱格式');
                        emailInput.focus();
                        return;
                    }
                    
                    if (phoneInput && phoneInput.value.trim() && !validatePhone(phoneInput)) {
                        e.preventDefault();
                        alert('请输入正确的手机号格式（11位数字，以1开头）');
                        phoneInput.focus();
                        return;
                    }
                });
            }
            
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
                let searchTimeout;
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(function() {
                        const searchValue = searchInput.value.trim();
                        searchMembers(searchValue);
                    }, 300);
                });
            }
        });
        
        function searchMembers(keyword) {
            const tbody = document.getElementById('membersTableBody');
            const url = new URL(window.location.origin + '/api/members-search.php');
            url.searchParams.set('search', keyword);
            
            fetch(url.toString())
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        tbody.innerHTML = data.html;
                        const clearBtn = document.querySelector('.search-form .btn-outline');
                        if (clearBtn) {
                            clearBtn.style.display = keyword ? 'inline-flex' : 'none';
                        }
                    }
                })
                .catch(error => console.error('搜索错误:', error));
        }
        
        function clearSearch() {
            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput) {
                searchInput.value = '';
                searchMembers('');
            }
        }
        
        <?php if ($action === 'add'): ?>
            document.addEventListener('DOMContentLoaded', function() {
                clearForm();
            });
        <?php endif; ?>
    </script>
    
    <?php include 'components/media-picker.php'; ?>
    <link rel="stylesheet" href="components/media-picker.css">
    <script src="components/media-picker.js"></script>
</body>
</html>

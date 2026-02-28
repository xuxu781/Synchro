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

Auth::requirePermission('behavior_manage');

$db = Database::getInstance();

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

if ($action === 'delete' && $id) {
    $behavior = $db->fetchOne("SELECT title FROM `lty-company_behavior` WHERE id = ?", [$id]);
    $db->delete('company_behavior', 'id = ?', [$id]);
    OperationLog::delete('behavior', '删除行为准则：' . ($behavior['title'] ?? $id), 'company_behavior', $id);
    set_flash_message('success', '删除成功');
    redirect('lty-admin/behavior.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $behavior_title = trim($_POST['behavior_title'] ?? '');
    $behavior_subtitle = trim($_POST['behavior_subtitle'] ?? '');
    
    Settings::set('behavior_title', $behavior_title);
    Settings::set('behavior_subtitle', $behavior_subtitle);
    
    OperationLog::update('behavior', '更新行为准则页面设置', 'settings', null, ['title' => $behavior_title, 'subtitle' => $behavior_subtitle]);
    
    set_flash_message('success', '设置保存成功');
    redirect('lty-admin/behavior.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $icon_svg = trim($_POST['icon_svg'] ?? '');
        $status = isset($_POST['status']) ? 1 : 0;
        
        if ($action === 'add') {
            $maxSortOrder = $db->fetchOne("SELECT MAX(sort_order) as max_sort FROM `lty-company_behavior`");
            $sort_order = ($maxSortOrder['max_sort'] ?? 0) + 1;
        } else {
            $sort_order = (int)($_POST['sort_order'] ?? 0);
        }
        
        if (empty($title) || empty($description)) {
            set_flash_message('error', '请填写标题和描述');
        } else {
            $data = [
                'title' => $title,
                'description' => $description,
                'icon_svg' => $icon_svg,
                'sort_order' => $sort_order,
                'status' => $status
            ];
            
            if ($action === 'edit' && $id) {
                $db->update('`lty-company_behavior`', $data, 'id = ?', [$id]);
                OperationLog::update('behavior', '更新行为准则：' . $title, 'company_behavior', $id, ['title' => $title]);
                set_flash_message('success', '更新成功');
                redirect('lty-admin/behavior.php');
            } else {
                $newId = $db->insert('`lty-company_behavior`', $data);
                OperationLog::create('behavior', '创建行为准则：' . $title, 'company_behavior', $newId, ['title' => $title]);
                set_flash_message('success', '添加成功');
                redirect('lty-admin/behavior.php');
            }
        }
    }
}

$success = get_flash_message('success');
$error = get_flash_message('error');

$showModal = false;
if ($error && ($action === 'add' || ($action === 'edit' && $id))) {
    $showModal = true;
    if ($action === 'edit' && $id) {
        $edit_item = $db->fetchOne("SELECT * FROM `lty-company_behavior` WHERE id = ?", [$id]);
    }
}

$behaviors = $db->fetchAll("SELECT * FROM `lty-company_behavior` ORDER BY sort_order ASC, id ASC");

$edit_item = null;
if (($action === 'edit' && $id) || $showModal) {
    if (!$edit_item) {
        $edit_item = $db->fetchOne("SELECT * FROM `lty-company_behavior` WHERE id = ?", [$id]);
    }
}

$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>行为准则管理 - <?php echo htmlspecialchars(Settings::get('site_name', '官网系统')); ?></title>
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>行为准则管理</h1>
            <div style="display: flex; gap: 10px;">
                <button onclick="openSettingsModal()" class="btn btn-outline">页面标题设置</button>
                <button onclick="openModal()" class="btn btn-primary">添加行为准则</button>
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
                                <th>图标</th>
                                <th>标题</th>
                                <th>描述</th>
                                <th>排序</th>
                                <th>状态</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($behaviors as $behavior): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($behavior['icon_svg'])): ?>
                                            <div style="width: 32px; height: 32px; color: #f59e0b;"><?php echo $behavior['icon_svg']; ?></div>
                                        <?php else: ?>
                                            <span style="color: #9ca3af;">无图标</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($behavior['title']); ?></td>
                                    <td><?php echo htmlspecialchars(mb_substr($behavior['description'], 0, 50)); ?>...</td>
                                    <td><?php echo $behavior['sort_order']; ?></td>
                                    <td>
                                        <?php if ($behavior['status'] == 1): ?>
                                            <span class="badge badge-success">启用</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">禁用</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <a href="behavior.php?action=edit&id=<?php echo $behavior['id']; ?>" class="btn btn-sm btn-outline">编辑</a>
                                            <a href="behavior.php?action=delete&id=<?php echo $behavior['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('确定要删除吗？');">删除</a>
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

    <div id="behaviorModal" class="modal <?php echo ($action === 'add' || ($action === 'edit' && $edit_item) || $showModal) ? 'active' : ''; ?>">
        <div class="modal-content">
            <div class="modal-header">
                <h3><?php echo $action === 'edit' && $edit_item ? '编辑行为准则' : '添加行为准则'; ?></h3>
                <button onclick="closeModal()" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <input type="hidden" name="action" value="<?php echo $action; ?>">
                    
                    <div class="form-group">
                        <label for="title">标题 *</label>
                        <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($edit_item['title'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">描述 *</label>
                        <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($edit_item['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="icon_svg">图标（SVG代码）</label>
                        <textarea id="icon_svg" name="icon_svg" rows="3" placeholder="请输入SVG代码，例如：<svg viewBox='0 0 1024 1024'><path d='...' /></svg>"><?php echo htmlspecialchars($edit_item['icon_svg'] ?? ''); ?></textarea>
                        <small style="color: #6b7280; font-size: 12px;">支持直接粘贴SVG代码</small>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="status" value="1" <?php echo ($edit_item['status'] ?? 1) == 1 ? 'checked' : ''; ?>>
                            启用
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary"><?php echo $action === 'edit' && $edit_item ? '更新' : '添加'; ?></button>
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
                        <label for="behavior_title">行为准则标题</label>
                        <input type="text" id="behavior_title" name="behavior_title" value="<?php echo htmlspecialchars(Settings::get('behavior_title', '行为准则')); ?>">
                    </div>
                    <div class="form-group">
                        <label for="behavior_subtitle">行为准则副标题</label>
                        <input type="text" id="behavior_subtitle" name="behavior_subtitle" value="<?php echo htmlspecialchars(Settings::get('behavior_subtitle', '高道德标准和诚信文化发挥着关键作用')); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">保存设置</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openModal() {
            window.location.href = 'behavior.php?action=add';
        }
        
        function closeModal() {
            document.getElementById('behaviorModal').classList.remove('active');
            window.location.href = 'behavior.php';
        }
        
        function openSettingsModal() {
            document.getElementById('settingsModal').classList.add('active');
        }
        
        function closeSettingsModal() {
            document.getElementById('settingsModal').classList.remove('active');
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
        });
    </script>
</body>
</html>

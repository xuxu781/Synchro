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

Auth::requirePermission('timeline_manage');

$db = Database::getInstance();

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

if ($action === 'delete' && $id) {
    $timeline = $db->fetchOne("SELECT year, title FROM `lty-company_timeline` WHERE id = ?", [$id]);
    $db->delete('`lty-company_timeline`', 'id = ?', [$id]);
    OperationLog::delete('timeline', '删除大事记：' . ($timeline['year'] ?? $id) . ' - ' . ($timeline['title'] ?? ''), 'company_timeline', $id);
    set_flash_message('success', '删除成功');
    redirect('lty-admin/timeline.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $timeline_title = trim($_POST['timeline_title'] ?? '');
    $timeline_subtitle = trim($_POST['timeline_subtitle'] ?? '');
    
    Settings::set('timeline_title', $timeline_title);
    Settings::set('timeline_subtitle', $timeline_subtitle);
    
    OperationLog::update('timeline', '更新大事记页面设置', 'settings', null, ['title' => $timeline_title, 'subtitle' => $timeline_subtitle]);
    
    set_flash_message('success', '设置保存成功');
    redirect('lty-admin/timeline.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        $year = trim($_POST['year'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if ($action === 'add') {
            $maxSortOrder = $db->fetchOne("SELECT MAX(sort_order) as max_sort FROM `lty-company_timeline`");
            $sort_order = ($maxSortOrder['max_sort'] ?? 0) + 1;
        } else {
            $existingItem = $db->fetchOne("SELECT sort_order FROM `lty-company_timeline` WHERE id = ?", [$id]);
            $sort_order = $existingItem['sort_order'] ?? 0;
        }
        $status = isset($_POST['status']) ? 1 : 0;
        
        if (empty($year) || empty($title)) {
            set_flash_message('error', '请填写年份和标题');
        } elseif (!preg_match('/^\d{4}$/', $year)) {
            set_flash_message('error', '年份格式不正确，请输入4位数字年份');
        } else {
            $existingYear = null;
            if ($action === 'add') {
                $existingYear = $db->fetchOne("SELECT id FROM `lty-company_timeline` WHERE year = ?", [$year]);
            } else {
                $existingYear = $db->fetchOne("SELECT id FROM `lty-company_timeline` WHERE year = ? AND id != ?", [$year, $id]);
            }
            
            if ($existingYear) {
                set_flash_message('error', '该年份已存在，请使用其他年份');
            } else {
                $data = [
                    'year' => $year,
                    'title' => $title,
                    'description' => $description,
                    'sort_order' => $sort_order,
                    'status' => $status
                ];
                
                if ($action === 'edit' && $id) {
                    $db->update('`lty-company_timeline`', $data, 'id = ?', [$id]);
                    OperationLog::update('timeline', '更新大事记：' . $year . ' - ' . $title, 'company_timeline', $id, ['year' => $year, 'title' => $title]);
                    set_flash_message('success', '更新成功');
                    redirect('lty-admin/timeline.php');
                } else {
                    $newId = $db->insert('`lty-company_timeline`', $data);
                    OperationLog::create('timeline', '创建大事记：' . $year . ' - ' . $title, 'company_timeline', $newId, ['year' => $year, 'title' => $title]);
                    set_flash_message('success', '添加成功');
                    redirect('lty-admin/timeline.php');
                }
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
        $edit_item = $db->fetchOne("SELECT * FROM `lty-company_timeline` WHERE id = ?", [$id]);
    }
}

$timelines = $db->fetchAll("SELECT * FROM `lty-company_timeline` ORDER BY year ASC, id ASC");

$edit_item = null;
if (($action === 'edit' && $id) || $showModal) {
    if (!$edit_item) {
        $edit_item = $db->fetchOne("SELECT * FROM `lty-company_timeline` WHERE id = ?", [$id]);
    }
}

$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>大事记管理 - <?php echo htmlspecialchars(Settings::get('site_name', '官网系统')); ?></title>
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>大事记管理</h1>
            <div style="display: flex; gap: 10px;">
                <button onclick="openSettingsModal()" class="btn btn-outline">页面标题设置</button>
                <button onclick="openModal()" class="btn btn-primary">添加大事记</button>
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
                                <th>年份</th>
                                <th>标题</th>
                                <th>描述</th>
                                <th>排序</th>
                                <th>状态</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody class="draggable-tbody">
                            <?php foreach ($timelines as $timeline): ?>
                                <tr data-id="<?php echo $timeline['id']; ?>" class="draggable-row">
                                    <td><?php echo htmlspecialchars($timeline['year']); ?></td>
                                    <td><?php echo htmlspecialchars($timeline['title']); ?></td>
                                    <td><?php echo htmlspecialchars(mb_substr($timeline['description'], 0, 50)); ?>...</td>
                                    <td><?php echo $timeline['sort_order']; ?></td>
                                    <td>
                                        <?php if ($timeline['status'] == 1): ?>
                                            <span class="badge badge-success">启用</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">禁用</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <a href="timeline.php?action=edit&id=<?php echo $timeline['id']; ?>" class="btn btn-sm btn-outline">编辑</a>
                                            <a href="timeline.php?action=delete&id=<?php echo $timeline['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('确定要删除吗？');">删除</a>
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

    <div id="timelineModal" class="modal <?php echo ($action === 'add' || ($action === 'edit' && $edit_item) || $showModal) ? 'active' : ''; ?>">
        <div class="modal-content">
            <div class="modal-header">
                <h3><?php echo $action === 'edit' && $edit_item ? '编辑大事记' : '添加大事记'; ?></h3>
                <button onclick="closeModal()" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <input type="hidden" name="action" value="<?php echo $action; ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="year">年份 *</label>
                            <input type="text" id="year" name="year" required value="<?php echo htmlspecialchars($edit_item['year'] ?? ''); ?>" placeholder="例如：2024">
                        </div>
                        <div class="form-group">
                            <label for="title">标题 *</label>
                            <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($edit_item['title'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">描述</label>
                        <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($edit_item['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>排序序号</label>
                        <input type="text" value="<?php echo $action === 'edit' && $edit_item ? ($edit_item['sort_order'] ?? '') : '自动排序'; ?>" readonly style="background-color: #f5f5f5; cursor: not-allowed;">
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
                        <label for="timeline_title">大事记标题</label>
                        <input type="text" id="timeline_title" name="timeline_title" value="<?php echo htmlspecialchars(Settings::get('timeline_title', '大事记')); ?>">
                    </div>
                    <div class="form-group">
                        <label for="timeline_subtitle">大事记副标题</label>
                        <input type="text" id="timeline_subtitle" name="timeline_subtitle" value="<?php echo htmlspecialchars(Settings::get('timeline_subtitle', '记录我们的成长历程')); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">保存设置</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openModal() {
            window.location.href = 'timeline.php?action=add';
        }
        
        function closeModal() {
            document.getElementById('timelineModal').classList.remove('active');
            window.location.href = 'timeline.php';
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

    <style>
        .draggable-row {
            cursor: move;
            transition: background-color 0.2s ease;
        }
        
        .draggable-row.dragging {
            opacity: 0.5;
            background-color: #f5f5f5;
        }
        
        .drag-placeholder {
            background-color: #fff9c4 !important;
        }
    </style>
</body>
</html>

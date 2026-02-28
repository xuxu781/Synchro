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

Auth::requirePermission('products_manage');

$db = Database::getInstance();

$product_id = $_GET['product_id'] ?? '';

if (empty($product_id)) {
    redirect('lty-admin/products.php');
}

$product = $db->fetchOne("SELECT * FROM `lty-products` WHERE id = ?", [$product_id]);

if (!$product) {
    set_flash_message('error', '产品不存在');
    redirect('lty-admin/products.php');
}

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

if ($action === 'delete' && $id) {
    $update = $db->fetchOne("SELECT version FROM `lty-product_updates` WHERE id = ?", [$id]);
    $db->delete('product_updates', 'id = ?', [$id]);
    OperationLog::delete('products', '删除产品更新：' . ($update['version'] ?? $id) . ' (产品ID: ' . $product_id . ')', 'product_update', $id);
    set_flash_message('success', '删除成功');
    redirect('lty-admin/product_updates.php?product_id=' . $product_id);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        $version = trim($_POST['version'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $releaseDate = trim($_POST['release_date'] ?? '');
        $status = isset($_POST['status']) ? 1 : 0;
        
        if ($action === 'add') {
            $maxSortOrder = $db->fetchOne("SELECT MAX(sort_order) as max_sort FROM product_updates WHERE product_id = ?", [$product_id]);
            $sort_order = ($maxSortOrder['max_sort'] ?? 0) + 1;
        } else {
            $existingItem = $db->fetchOne("SELECT sort_order FROM `product_updates` WHERE id = ?", [$id]);
            $sort_order = $existingItem['sort_order'] ?? 0;
        }
        
        if (empty($version) || empty($content) || empty($releaseDate)) {
            set_flash_message('error', '请填写版本号、内容和发布日期');
        } else {
            $data = [
                'product_id' => $product_id,
                'version' => $version,
                'content' => $content,
                'release_date' => $releaseDate,
                'sort_order' => $sort_order,
                'status' => $status
            ];
            
            if ($action === 'edit' && $id) {
                $db->update('`lty-product_updates`', $data, 'id = ?', [$id]);
                OperationLog::update('products', '更新产品更新：' . $version . ' (产品ID: ' . $product_id . ')', 'product_update', $id, ['version' => $version]);
                set_flash_message('success', '更新成功');
                redirect('lty-admin/product_updates.php?product_id=' . $product_id);
            } else {
                $newId = $db->insert('product_updates', $data);
                OperationLog::create('products', '创建产品更新：' . $version . ' (产品ID: ' . $product_id . ')', 'product_update', $newId, ['version' => $version, 'product_id' => $product_id]);
                set_flash_message('success', '添加成功');
                redirect('lty-admin/product_updates.php?product_id=' . $product_id);
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
        $edit_item = $db->fetchOne("SELECT * FROM `lty-product_updates` WHERE id = ?", [$id]);
    }
}

$updates = $db->fetchAll("SELECT * FROM `lty-product_updates` WHERE product_id = ? ORDER BY release_date DESC, sort_order ASC", [$product_id]);

$edit_item = null;
if (($action === 'edit' && $id) || $showModal) {
    if (!$edit_item) {
        $edit_item = $db->fetchOne("SELECT * FROM `product_updates` WHERE id = ?", [$id]);
    }
}

$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>更新日志管理 - <?php echo htmlspecialchars($product['title']); ?> - <?php echo htmlspecialchars(Settings::get('site_name', '官网系统')); ?></title>
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <div style="display: flex; align-items: center; gap: 15px;">
                <a href="products.php" class="btn btn-outline">← 返回产品列表</a>
                <h1>更新日志管理</h1>
            </div>
            <div style="display: flex; align-items: center; gap: 15px;">
                <span style="color: #666;">产品：<?php echo htmlspecialchars($product['title']); ?></span>
                <button onclick="openModal()" class="btn btn-primary">添加更新</button>
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
                                <th>版本</th>
                                <th>发布日期</th>
                                <th>内容</th>
                                <th>排序</th>
                                <th>状态</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody class="draggable-tbody">
                            <?php foreach ($updates as $update): ?>
                                <tr data-id="<?php echo $update['id']; ?>" class="draggable-row" draggable="true">
                                    <td><?php echo htmlspecialchars($update['version']); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($update['release_date'])); ?></td>
                                    <td><?php echo htmlspecialchars(mb_substr($update['content'], 0, 50)); ?>...</td>
                                    <td><?php echo $update['sort_order']; ?></td>
                                    <td>
                                        <?php if ($update['status'] == 1): ?>
                                            <span class="badge badge-success">启用</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">禁用</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <a href="product_updates.php?product_id=<?php echo $product_id; ?>&action=edit&id=<?php echo $update['id']; ?>" class="btn btn-sm btn-outline" draggable="false">编辑</a>
                                            <a href="product_updates.php?product_id=<?php echo $product_id; ?>&action=delete&id=<?php echo $update['id']; ?>" class="btn btn-sm btn-danger" draggable="false" onclick="return confirm('确定要删除吗？');">删除</a>
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

    <div id="updateModal" class="modal <?php echo ($action === 'add' || ($action === 'edit' && $edit_item)) ? 'active' : ''; ?>">
        <div class="modal-content">
            <div class="modal-header">
                <h3><?php echo $action === 'edit' && $edit_item ? '编辑更新' : '添加更新'; ?></h3>
                <button onclick="closeModal()" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form method="POST" autocomplete="off">
                    <input type="hidden" name="action" value="<?php echo $action; ?>">
                    
                    <div class="form-group">
                        <label for="version">版本号 *</label>
                        <input type="text" id="version" name="version" required value="<?php echo ($action === 'edit' && $edit_item) ? htmlspecialchars($edit_item['version']) : ''; ?>" placeholder="如：v1.0.0">
                    </div>
                    
                    <div class="form-group">
                        <label for="release_date">发布日期 *</label>
                        <input type="date" id="release_date" name="release_date" required value="<?php echo ($action === 'edit' && $edit_item) ? date('Y-m-d', strtotime($edit_item['release_date'])) : date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="content">更新内容 *</label>
                        <textarea id="content" name="content" rows="6" required><?php echo ($action === 'edit' && $edit_item) ? htmlspecialchars($edit_item['content']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>排序序号</label>
                        <input type="text" value="<?php echo $action === 'edit' && $edit_item ? ($edit_item['sort_order'] ?? '') : '自动排序'; ?>" readonly style="background-color: #f5f5f5; cursor: not-allowed;">
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="status" value="1" <?php echo ($action === 'edit' && $edit_item && $edit_item['status'] != 1) ? '' : 'checked'; ?>>
                            启用
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary"><?php echo $action === 'edit' && $edit_item ? '更新' : '添加'; ?></button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openModal() {
            window.location.href = 'product_updates.php?product_id=<?php echo $product_id; ?>&action=add';
        }
        
        function closeModal() {
            document.getElementById('updateModal').classList.remove('active');
            window.location.href = 'product_updates.php?product_id=<?php echo $product_id; ?>';
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const tbody = document.querySelector('.draggable-tbody');
            if (!tbody) return;
            
            let draggedRow = null;
            let placeholder = null;
            
            const rows = tbody.querySelectorAll('.draggable-row');
            
            rows.forEach((row, index) => {
                row.addEventListener('dragstart', function(e) {
                    draggedRow = row;
                    draggedRow.classList.add('dragging');
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/html', draggedRow.innerHTML);
                });
                
                row.addEventListener('dragend', function(e) {
                    if (draggedRow) {
                        draggedRow.classList.remove('dragging');
                        if (placeholder) {
                            placeholder.remove();
                            placeholder = null;
                        }
                        draggedRow = null;
                    }
                });
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
                        placeholder.className = 'placeholder';
                        placeholder.innerHTML = '<td colspan="6" style="padding: 20px; text-align: center; color: #999;">拖放到这里</td>';
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
                    
                    const newOrder = Array.from(tbody.querySelectorAll('.draggable-row')).map(row => row.dataset.id);
                    
                    fetch('/api/products.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'reorder_updates',
                            product_id: <?php echo $product_id; ?>,
                            order: newOrder
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                        }
                    })
                    .catch(error => {
                        console.error('排序更新失败:', error);
                    });
                }
            });
        });
    </script>
</body>
</html>

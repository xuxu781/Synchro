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

Auth::requirePermission('culture_manage');

$db = Database::getInstance();

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

if ($action === 'delete' && $id) {
    $culture = $db->fetchOne("SELECT title FROM `lty-company_culture` WHERE id = ?", [$id]);
    $db->delete('`lty-company_culture`', 'id = ?', [$id]);
    OperationLog::delete('culture', '删除企业文化：' . ($culture['title'] ?? $id), 'company_culture', $id);
    set_flash_message('success', '删除成功');
    redirect('lty-admin/culture.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $culture_title = trim($_POST['culture_title'] ?? '');
    $culture_subtitle = trim($_POST['culture_subtitle'] ?? '');
    
    Settings::set('culture_title', $culture_title);
    Settings::set('culture_subtitle', $culture_subtitle);
    
    OperationLog::update('culture', '更新企业文化页面设置', 'settings', null, ['title' => $culture_title, 'subtitle' => $culture_subtitle]);
    
    set_flash_message('success', '设置保存成功');
    redirect('lty-admin/culture.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $imageUrl = trim($_POST['image_url'] ?? '');
        $status = isset($_POST['status']) ? 1 : 0;
        
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image_file'];
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 5 * 1024 * 1024;
            
            if ($file['size'] > $maxSize) {
                set_flash_message('error', '文件大小不能超过5MB');
                redirect('lty-admin/culture.php?action=' . $action . ($action === 'edit' && $id ? '&id=' . $id : ''));
            }
            
            if (!in_array($file['type'], $allowedTypes)) {
                set_flash_message('error', '不支持的文件类型，请上传 JPG、PNG、GIF 或 WebP 格式的图片');
                redirect('lty-admin/culture.php?action=' . $action . ($action === 'edit' && $id ? '&id=' . $id : ''));
            }
            
            $uploadDir = __DIR__ . '/../uploads/culture/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'culture_' . time() . '_' . uniqid() . '.' . $extension;
            $uploadPath = $uploadDir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $imageUrl = '/uploads/culture/' . $filename;
            }
        }
        
        if ($action === 'add') {
            $maxSortOrder = $db->fetchOne("SELECT MAX(sort_order) as max_sort FROM `lty-company_culture`");
            $sort_order = ($maxSortOrder['max_sort'] ?? 0) + 1;
        } else {
            $existingItem = $db->fetchOne("SELECT sort_order FROM `lty-company_culture` WHERE id = ?", [$id]);
            $sort_order = $existingItem['sort_order'] ?? 0;
        }
        
        if (empty($title) || empty($description)) {
            set_flash_message('error', '请填写标题和描述');
        } else {
            $data = [
                'title' => $title,
                'description' => $description,
                'image' => $imageUrl,
                'sort_order' => $sort_order,
                'status' => $status
            ];
            
            if ($action === 'edit' && $id) {
                $db->update('`lty-company_culture`', $data, 'id = ?', [$id]);
                OperationLog::update('culture', '更新企业文化：' . $title, 'company_culture', $id, ['title' => $title]);
                set_flash_message('success', '更新成功');
                redirect('lty-admin/culture.php');
            } else {
                $newId = $db->insert('`lty-company_culture`', $data);
                OperationLog::create('culture', '创建企业文化：' . $title, 'company_culture', $newId, ['title' => $title]);
                set_flash_message('success', '添加成功');
                redirect('lty-admin/culture.php');
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
        $edit_item = $db->fetchOne("SELECT * FROM `lty-company_culture` WHERE id = ?", [$id]);
    }
}

$cultures = $db->fetchAll("SELECT * FROM `lty-company_culture` ORDER BY sort_order ASC, id ASC");

$edit_item = null;
if (($action === 'edit' && $id) || $showModal) {
    if (!$edit_item) {
        $edit_item = $db->fetchOne("SELECT * FROM `lty-company_culture` WHERE id = ?", [$id]);
    }
}

$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>企业文化管理 - <?php echo htmlspecialchars(Settings::get('site_name', '官网系统')); ?></title>
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>企业文化管理</h1>
            <div style="display: flex; gap: 10px;">
                <button onclick="openSettingsModal()" class="btn btn-outline">页面标题设置</button>
                <button onclick="openModal()" class="btn btn-primary">添加企业文化</button>
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
                                <th>图片</th>
                                <th>标题</th>
                                <th>描述</th>
                                <th>排序</th>
                                <th>状态</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody class="draggable-tbody">
                            <?php foreach ($cultures as $culture): ?>
                                <tr data-id="<?php echo $culture['id']; ?>" class="draggable-row" draggable="true">
                                    <td>
                                        <?php if (!empty($culture['image'])): ?>
                                            <img src="<?php echo htmlspecialchars($culture['image']); ?>" alt="" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                                        <?php else: ?>
                                            <span style="color: #999;">无图片</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($culture['title']); ?></td>
                                    <td><?php echo htmlspecialchars(mb_substr($culture['description'], 0, 50)); ?>...</td>
                                    <td><?php echo $culture['sort_order']; ?></td>
                                    <td>
                                        <?php if ($culture['status'] == 1): ?>
                                            <span class="badge badge-success">启用</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">禁用</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <a href="culture.php?action=edit&id=<?php echo $culture['id']; ?>" class="btn btn-sm btn-outline" draggable="false">编辑</a>
                                            <a href="culture.php?action=delete&id=<?php echo $culture['id']; ?>" class="btn btn-sm btn-danger" draggable="false" onclick="return confirm('确定要删除吗？');">删除</a>
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

    <div id="cultureModal" class="modal <?php echo ($action === 'add' || ($action === 'edit' && $edit_item)) ? 'active' : ''; ?>">
        <div class="modal-content">
            <div class="modal-header">
                <h3><?php echo $action === 'edit' && $edit_item ? '编辑企业文化' : '添加企业文化'; ?></h3>
                <button onclick="closeModal()" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form method="POST" autocomplete="off">
                    <input type="hidden" name="action" value="<?php echo $action; ?>">
                    
                    <div class="form-group">
                        <label for="title">标题 *</label>
                        <input type="text" id="title" name="title" required value="<?php echo ($action === 'edit' && $edit_item) ? htmlspecialchars($edit_item['title']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">描述 *</label>
                        <textarea id="description" name="description" rows="4" required><?php echo ($action === 'edit' && $edit_item) ? htmlspecialchars($edit_item['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>排序序号</label>
                        <input type="text" value="<?php echo $action === 'edit' && $edit_item ? ($edit_item['sort_order'] ?? '') : '自动排序'; ?>" readonly style="background-color: #f5f5f5; cursor: not-allowed;">
                    </div>
                    
                    <div class="form-group">
                        <label for="image_url">图片链接</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="text" id="image_url" name="image_url" value="<?php echo ($action === 'edit' && $edit_item) ? htmlspecialchars($edit_item['image']) : ''; ?>" placeholder="点击选择图片或手动输入图片URL" oninput="previewUrlImage(this)" style="flex: 1;">
                            <button type="button" class="btn btn-outline" onclick="openMediaLibrary('image_url')">选择图片</button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>图片预览</label>
                        <div id="image_preview" style="margin-top: 10px;">
                            <?php if ($action === 'edit' && $edit_item && !empty($edit_item['image'])): ?>
                                <div style="position: relative; display: inline-block;">
                                    <img src="<?php echo htmlspecialchars($edit_item['image']); ?>" alt="预览" style="max-width: 200px; max-height: 200px; object-fit: cover; border-radius: 4px;">
                                    <button type="button" onclick="removeMainImage()" style="position: absolute; top: -8px; right: -8px; width: 24px; height: 24px; border: none; border-radius: 50%; background: #ef4444; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 16px; line-height: 1; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">×</button>
                                </div>
                            <?php endif; ?>
                        </div>
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
                        <label for="culture_title">企业文化标题</label>
                        <input type="text" id="culture_title" name="culture_title" value="<?php echo htmlspecialchars(Settings::get('culture_title', '字节范')); ?>">
                    </div>
                    <div class="form-group">
                        <label for="culture_subtitle">企业文化副标题</label>
                        <input type="text" id="culture_subtitle" name="culture_subtitle" value="<?php echo htmlspecialchars(Settings::get('culture_subtitle', '字节范是我们共同认可的行为准则')); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">保存设置</button>
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
                        previewUrlImage(input);
                    }
                }
            });
        }
        
        function openModal() {
            window.location.href = 'culture.php?action=add';
        }
        
        function closeModal() {
            document.getElementById('cultureModal').classList.remove('active');
            window.location.href = 'culture.php';
        }
        
        function openSettingsModal() {
            document.getElementById('settingsModal').classList.add('active');
        }
        
        function closeSettingsModal() {
            document.getElementById('settingsModal').classList.remove('active');
        }
        
        function clearForm() {
            document.querySelector('form').reset();
            document.getElementById('image_preview').innerHTML = '';
        }
        
        let pendingImageFile = null;
        
        function previewImage(input) {
            const preview = document.getElementById('image_preview');
            const urlInput = document.getElementById('image_url');
            
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                
                const maxSize = 5 * 1024 * 1024;
                if (file.size > maxSize) {
                    alert('文件大小不能超过5MB');
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
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="预览" style="max-width: 200px; max-height: 200px; object-fit: cover; border-radius: 4px;">';
                    
                    const formData = new FormData();
                    formData.append('culture_image', file);
                    
                    fetch('/api/settings.php?action=upload_culture', {
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
            const preview = document.getElementById('image_preview');
            const url = input.value.trim();
            
            if (url) {
                preview.innerHTML = '<div style="position: relative; display: inline-block;"><img src="' + url + '" alt="预览" style="max-width: 200px; max-height: 200px; object-fit: cover; border-radius: 4px;" onerror="this.src=\'\'; this.parentElement.innerHTML=\'<span style=\'color: #999;\'>图片加载失败</span>\'"><button type="button" onclick="removeMainImage()" style="position: absolute; top: -8px; right: -8px; width: 24px; height: 24px; border: none; border-radius: 50%; background: #ef4444; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 16px; line-height: 1; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">×</button></div>';
            } else {
                preview.innerHTML = '';
            }
        }
        
        function removeMainImage() {
            if (!confirm('确定要移除图片吗？')) return;
            const input = document.getElementById('image_url');
            const preview = document.getElementById('image_preview');
            input.value = '';
            preview.innerHTML = '';
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const fileInput = document.getElementById('image');
                    const urlInput = document.getElementById('image_url');
                    
                    if (fileInput.dataset.fileSelected === 'true') {
                        e.preventDefault();
                        alert('请等待图片上传完成后再提交');
                    }
                });
            }
            
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
                        placeholder.className = 'drag-placeholder';
                        placeholder.innerHTML = '<td colspan="6" style="padding: 10px; background: #fff9c4; border: 2px dashed #fbc02d; text-align: center; color: #f9a825;">拖放到此处</td>';
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
                    
                    updateSortOrder();
                }
            });
            
            function updateSortOrder() {
                const rows = tbody.querySelectorAll('.draggable-row');
                const ids = [];
                
                rows.forEach((row, index) => {
                    const id = row.dataset.id;
                    ids.push(id);
                    
                    const sortCell = row.querySelector('td:nth-child(4)');
                    if (sortCell) {
                        sortCell.textContent = index + 1;
                    }
                });
                
                fetch('/api/settings.php?action=update_culture_order', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ ids: ids })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        console.error('排序更新失败:', data.message);
                        alert('排序更新失败，请刷新页面重试');
                    }
                })
                .catch(error => {
                    console.error('排序更新错误:', error);
                });
            }
        });
        
        <?php if ($action === 'add'): ?>
            document.addEventListener('DOMContentLoaded', function() {
                clearForm();
            });
        <?php endif; ?>
        
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
            background-color: #e3f2fd !important;
        }
    </style>
    
    <?php include 'components/media-picker.php'; ?>
    <link rel="stylesheet" href="components/media-picker.css">
    <script src="components/media-picker.js"></script>
</body>
</html>

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

if (!Auth::isSuperAdmin() && !Auth::hasPermission('media_manage') && !Auth::hasPermission('media_view')) {
    set_flash_message('error', '您没有权限访问此页面');
    redirect('lty-admin/index.php');
}

$canManage = Auth::isSuperAdmin() || Auth::hasPermission('media_manage');

$db = Database::getInstance();
$user = Auth::user();

$mode = $_GET['mode'] ?? 'manage';

if ($mode === 'select') {
    header('Content-Security-Policy: frame-ancestors *');
}

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

if ($action === 'delete' && $id) {
    if (!$canManage) {
        set_flash_message('error', '您没有权限删除媒体文件');
        redirect('lty-admin/media.php');
    }
    $media = $db->fetchOne("SELECT * FROM `lty-media_library` WHERE id = ?", [$id]);
    if ($media) {
        $filePath = $media['file_path'];
        
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
        
        $db->delete('`lty-media_library`', 'id = ?', [$id]);
        OperationLog::delete('media', '删除媒体文件：' . ($media['original_name'] ?? $id), 'media', $id);
        set_flash_message('success', '删除成功');
    }
    redirect('lty-admin/media.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$canManage) {
        set_flash_message('error', '您没有权限编辑媒体文件');
        redirect('lty-admin/media.php');
    }
    if (isset($_POST['update_media'])) {
        $mediaId = (int)($_POST['media_id'] ?? 0);
        $altText = trim($_POST['alt_text'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $caption = trim($_POST['caption'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        $data = [
            'alt_text' => $altText ?: null,
            'title' => $title ?: null,
            'caption' => $caption ?: null,
            'description' => $description ?: null
        ];
        
        $db->update('`lty-media_library`', $data, 'id = ?', [$mediaId]);
        OperationLog::update('media', '更新媒体文件信息 ID: ' . $mediaId, 'media', $mediaId, $data);
        set_flash_message('success', '更新成功');
        redirect('lty-admin/media.php');
    }
}

$success = get_flash_message('success');
$error = get_flash_message('error');

$page = (int)($_GET['page'] ?? 1);
$perPage = 20;
$offset = ($page - 1) * $perPage;

$filterType = $_GET['filter_type'] ?? '';
$search = $_GET['search'] ?? '';
$filterMonth = $_GET['filter_month'] ?? '';
$filterMissing = isset($_GET['filter_missing']) && $_GET['filter_missing'] == '1';

$whereConditions = ['status = 1'];
$params = [];

if ($filterType) {
    $whereConditions[] = 'file_type = ?';
    $params[] = $filterType;
}

if ($filterMonth) {
    $whereConditions[] = 'DATE_FORMAT(upload_date, "%Y-%m") = ?';
    $params[] = $filterMonth;
}

if ($search) {
    $whereConditions[] = '(original_name LIKE ? OR title LIKE ? OR alt_text LIKE ?)';
    $searchParam = '%' . $search . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$allMediaFiles = $db->fetchAll("SELECT id, file_path FROM `lty-media_library` WHERE status = 1");
$missingIds = [];
foreach ($allMediaFiles as $media) {
    if (!file_exists($media['file_path'])) {
        $missingIds[] = $media['id'];
    }
}

if ($filterMissing && !empty($missingIds)) {
    $whereConditions[] = 'id IN (' . implode(',', array_map('intval', $missingIds)) . ')';
}

$whereClause = implode(' AND ', $whereConditions);

$total = $db->fetchOne("SELECT COUNT(*) as count FROM `lty-media_library` WHERE $whereClause", $params)['count'];
$totalPages = ceil($total / $perPage);

$mediaItems = $db->fetchAll("SELECT * FROM `lty-media_library` WHERE $whereClause ORDER BY upload_date DESC LIMIT $offset, $perPage", $params);

$editMedia = null;
if ($action === 'edit' && $id) {
    $editMedia = $db->fetchOne("SELECT * FROM `lty-media_library` WHERE id = ? AND status = 1", [$id]);
}

$stats = [
    'total' => $db->fetchOne("SELECT COUNT(*) as count FROM `lty-media_library` WHERE status = 1")['count'],
    'image' => $db->fetchOne("SELECT COUNT(*) as count FROM `lty-media_library` WHERE file_type = 'image' AND status = 1")['count'],
    'video' => $db->fetchOne("SELECT COUNT(*) as count FROM `lty-media_library` WHERE file_type = 'video' AND status = 1")['count'],
    'document' => $db->fetchOne("SELECT COUNT(*) as count FROM `lty-media_library` WHERE file_type = 'document' AND status = 1")['count']
];

$stats['missing'] = count($missingIds);

foreach ($mediaItems as &$item) {
    $item['is_missing'] = !file_exists($item['file_path']);
}
unset($item);
$missingIdsJson = json_encode($missingIds);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>媒体库 - <?php echo htmlspecialchars(Settings::get('site_name', '官网系统')); ?></title>
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
            padding: 16px;
        }
        
        .media-grid.list-view {
            grid-template-columns: 1fr;
        }
        
        .media-grid.list-view .media-item {
            display: flex;
            flex-direction: row;
            align-items: stretch;
            padding: 12px;
        }
        
        .media-grid.list-view .media-preview {
            width: 100px;
            height: 100px;
            min-width: 100px;
            aspect-ratio: auto;
            flex-shrink: 0;
        }
        
        .media-grid.list-view .media-preview video {
            object-fit: cover;
        }
        
        .media-grid.list-view .media-info {
            flex: 1;
            padding: 0 16px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .media-grid.list-view .media-name {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .media-grid.list-view .media-meta {
            margin-top: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            font-size: 13px;
        }
        
        .media-grid.list-view .media-meta-item {
            display: flex;
            align-items: center;
            gap: 4px;
            color: var(--text-muted);
        }
        
        .media-grid.list-view .media-meta-item strong {
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        .media-grid.list-view .media-meta-item a {
            color: var(--primary-color);
            text-decoration: none;
            word-break: break-all;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .media-grid.list-view .media-meta-item a:hover {
            text-decoration: underline;
        }
        
        .media-meta-simple {
            display: inline;
        }
        
        .media-meta-detail {
            display: none;
        }
        
        .media-grid.list-view .media-meta-simple {
            display: none;
        }
        
        .media-grid.list-view .media-meta-detail {
            display: inline-flex;
        }
        
        .media-grid.list-view .media-actions {
            position: static;
            display: flex;
            flex-direction: column;
            padding: 0;
            background: transparent;
            gap: 4px;
        }
        
        .media-grid.list-view .media-item:hover .media-actions {
            display: flex;
        }
        
        .view-toggle {
            display: flex;
            gap: 4px;
            background: var(--bg-secondary);
            padding: 4px;
            border-radius: 6px;
        }
        
        .view-toggle-btn {
            padding: 8px 10px;
            border: none;
            background: transparent;
            border-radius: 4px;
            cursor: pointer;
            color: var(--text-muted);
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .view-toggle-btn svg {
            width: 18px;
            height: 18px;
        }
        
        .view-toggle-btn:hover {
            color: var(--text-primary);
        }
        
        .view-toggle-btn.active {
            background: var(--primary-color);
            color: white;
        }
        
        .media-item {
            position: relative;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .media-item:hover {
            border-color: var(--primary-color);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .media-item.selected {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.2);
        }
        
        .media-item.batch-selected {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
        }
        
        .media-item.batch-selected .batch-checkbox {
            display: flex;
        }
        
        .batch-checkbox {
            position: absolute;
            top: 8px;
            left: 8px;
            width: 24px;
            height: 24px;
            background: white;
            border: 2px solid #d1d5db;
            border-radius: 4px;
            display: none;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
            transition: all 0.2s;
        }
        
        .batch-checkbox:hover {
            border-color: #ef4444;
        }
        
        .media-item.batch-selected .batch-checkbox {
            background: #ef4444;
            border-color: #ef4444;
        }
        
        .batch-checkbox svg {
            width: 14px;
            height: 14px;
            color: white;
            display: none;
        }
        
        .media-item.batch-selected .batch-checkbox svg {
            display: block;
        }
        
        .batch-actions {
            display: none;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border: 1px solid #fecaca;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        
        .batch-actions.active {
            display: flex;
        }
        
        .batch-actions-info {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #991b1b;
            font-weight: 500;
        }
        
        .batch-actions-info svg {
            width: 20px;
            height: 20px;
        }
        
        .batch-actions-buttons {
            display: flex;
            gap: 8px;
            margin-left: auto;
        }
        
        .batch-delete-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .batch-delete-btn:hover {
            background: #dc2626;
        }
        
        .batch-delete-btn svg {
            width: 16px;
            height: 16px;
        }
        
        .batch-cancel-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: white;
            color: #6b7280;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .batch-cancel-btn:hover {
            background: #f3f4f6;
        }
        
        .batch-select-all-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: white;
            color: #374151;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .batch-select-all-btn:hover {
            background: #f3f4f6;
        }
        
        .batch-select-all-btn svg {
            width: 16px;
            height: 16px;
        }
        
        .media-preview {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-secondary);
            overflow: hidden;
        }
        
        .media-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .media-preview img.broken-image {
            width: auto;
            height: auto;
            max-width: 60%;
            max-height: 60%;
            opacity: 0.5;
        }
        
        .media-preview .broken-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            color: var(--text-muted);
            font-size: 12px;
        }
        
        .media-preview .broken-placeholder svg {
            width: 32px;
            height: 32px;
            margin-bottom: 4px;
            opacity: 0.5;
        }
        
        .media-item-missing {
            border-color: rgba(239, 68, 68, 0.5);
            background: rgba(239, 68, 68, 0.05);
        }
        
        .media-item-missing:hover {
            border-color: #ef4444;
        }
        
        .media-preview .video-icon,
        .media-preview .document-icon {
            font-size: 48px;
        }
        
        .media-preview video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .document-preview {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
        }
        
        .media-info {
            padding: 8px;
            font-size: 12px;
        }
        
        .media-name {
            font-weight: 600;
            color: var(--text-primary);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            margin-bottom: 4px;
        }
        
        .media-meta {
            color: var(--text-muted);
            display: flex;
            justify-content: space-between;
        }
        
        .media-actions {
            position: absolute;
            top: 8px;
            right: 8px;
            display: none;
            gap: 4px;
        }
        
        .media-item:hover .media-actions {
            display: flex;
        }
        
        .media-action-btn {
            width: 28px;
            height: 28px;
            border: none;
            border-radius: 4px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        
        .media-action-btn svg {
            width: 14px;
            height: 14px;
        }
        
        .media-action-btn:hover {
            background: var(--primary-color);
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .upload-area {
            border: 2px dashed var(--border-color);
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: 16px;
        }
        
        .upload-area:hover,
        .upload-area.dragover {
            border-color: var(--primary-color);
            background: rgba(var(--primary-rgb), 0.05);
        }
        
        .upload-area-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        
        .upload-area-text {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .upload-area-hint {
            color: var(--text-muted);
            font-size: 14px;
        }
        
        .stats-bar {
            display: flex;
            gap: 12px;
            padding: 16px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: 16px;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--bg-secondary);
            border: 2px solid transparent;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        
        .stat-item:hover {
            background: var(--bg-secondary);
            border-color: var(--border-color);
            transform: translateY(-1px);
        }
        
        .stat-item.active {
            background: rgba(var(--primary-rgb), 0.1);
            color: var(--primary-color);
            border-color: var(--primary-color);
            box-shadow: 0 2px 8px rgba(var(--primary-rgb), 0.2);
        }
        
        .stat-item.active .stat-value {
            color: var(--primary-color);
        }
        
        .stat-item.active .stat-label {
            color: var(--primary-color);
        }
        
        .stat-item-warning {
            background: rgba(239, 68, 68, 0.05);
            color: #ef4444;
            border-color: transparent;
        }
        
        .stat-item-warning:hover {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.3);
        }
        
        .stat-item-warning.active {
            background: rgba(239, 68, 68, 0.1);
            border-color: #ef4444;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.2);
        }
        
        .stat-item-warning .stat-value {
            color: #ef4444;
        }
        
        .stat-item-warning .stat-label {
            color: #ef4444;
        }
        
        .stat-label {
            font-size: 12px;
            color: var(--text-muted);
        }
        
        .stat-value {
            font-weight: 600;
            font-size: 18px;
        }
        
        .missing-alert {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 8px;
            margin-bottom: 16px;
        }
        
        .missing-alert-content {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #ef4444;
        }
        
        .missing-alert-content svg {
            width: 20px;
            height: 20px;
        }
        
        .missing-alert-content strong {
            font-weight: 600;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
            border: none;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }
        
        .filter-bar {
            display: flex;
            gap: 12px;
            align-items: center;
            padding: 16px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: 16px;
        }
        
        .filter-bar-inner {
            display: flex;
            gap: 12px;
            flex: 1;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-bar input[type="text"] {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            background: var(--bg-secondary);
            color: var(--text-primary);
            font-size: 14px;
        }
        
        .filter-bar select {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            background: var(--bg-secondary);
            color: var(--text-primary);
            font-size: 14px;
            cursor: pointer;
            min-width: 90px;
            white-space: nowrap;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
            padding: 24px 16px;
        }
        
        .pagination-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            padding: 20px 0;
        }
        
        .pagination-info {
            color: var(--text-muted);
            font-size: 13px;
        }
        
        .pagination-btn {
            min-width: 36px;
            height: 36px;
            padding: 0 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--bg-card);
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .pagination-btn:hover:not(:disabled):not(.active) {
            border-color: var(--primary-color);
            color: var(--primary-color);
            background: rgba(var(--primary-rgb), 0.05);
        }
        
        .pagination-btn.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            box-shadow: 0 2px 8px rgba(var(--primary-rgb), 0.3);
        }
        
        .pagination-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }
        
        .pagination-btn.nav-btn {
            padding: 0 16px;
            gap: 4px;
        }
        
        .pagination-btn.nav-btn svg {
            width: 16px;
            height: 16px;
        }
        
        .pagination-ellipsis {
            color: var(--text-muted);
            padding: 0 4px;
            font-size: 14px;
        }
        
        .media-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .media-modal.active {
            display: flex;
        }
        
        .media-modal-content {
            max-width: 90%;
            max-height: 90%;
            background: var(--bg-card);
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            flex-direction: row;
        }
        
        .media-modal-preview {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #000;
            overflow: hidden;
        }
        
        .media-modal-image {
            max-width: 60%;
            max-height: 90vh;
            object-fit: contain;
        }
        
        .media-modal-video {
            max-width: 60%;
            max-height: 90vh;
            object-fit: contain;
            background: #000;
        }
        
        .media-modal-video-container {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
        }
        
        .media-modal-details {
            padding: 24px;
            flex: 1;
            overflow-y: auto;
        }
        
        .media-modal-details h3 {
            margin: 0 0 8px 0;
            color: var(--text-primary);
        }
        
        .media-modal-details p {
            margin: 4px 0;
            color: var(--text-muted);
            font-size: 14px;
        }
        
        .media-modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 40px;
            height: 40px;
            border: none;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            font-size: 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .media-modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        @media (max-width: 768px) {
            .media-modal-content {
                flex-direction: column;
                max-width: 95%;
                max-height: 95%;
            }

            .media-modal-preview {
                width: 100%;
                max-height: 50vh;
            }

            .media-modal-image {
                max-width: 100%;
                max-height: 50vh;
            }
            
            .media-modal-video {
                max-width: 100%;
                max-height: 50vh;
            }

            .media-modal-details {
                padding: 16px;
                font-size: 13px;
            }

            .media-modal-details h3 {
                font-size: 16px;
                margin-bottom: 12px;
            }

            .media-modal-details p {
                font-size: 13px;
                line-height: 1.6;
            }

            .media-modal-close {
                top: 10px;
                right: 10px;
                width: 36px;
                height: 36px;
                font-size: 20px;
            }
            
            .batch-actions {
                flex-wrap: wrap;
                padding: 12px;
            }
            
            .batch-actions-info {
                width: 100%;
                margin-bottom: 8px;
            }
            
            .batch-actions-buttons {
                width: 100%;
                flex-wrap: wrap;
            }
            
            .batch-select-all-btn,
            .batch-cancel-btn,
            .batch-delete-btn {
                flex: 1;
                min-width: 100px;
                justify-content: center;
            }
            
            .filter-bar {
                flex-wrap: wrap;
                padding: 12px;
            }
            
            .filter-bar-inner {
                width: 100%;
            }
            
            .filter-bar input[type="text"] {
                width: 100%;
                flex: none;
            }
            
            .filter-bar select {
                flex: 1;
                min-width: 0;
            }
            
            .view-toggle {
                width: 100%;
                justify-content: flex-end;
                margin-top: 8px;
            }
        }
    </style>
</head>
<body>
    <?php if ($mode === 'manage'): ?>
        <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <?php endif; ?>

    <div class="main-content" <?php echo $mode === 'select' ? 'style="margin-left: 0;"' : ''; ?>>
        <div class="header">
            <h1><?php echo $mode === 'select' ? '选择媒体' : '媒体库'; ?></h1>
            <?php if ($mode === 'manage'): ?>
                <button onclick="document.getElementById('fileInput').click()" class="btn btn-primary">上传文件</button>
            <?php endif; ?>
            <?php if ($mode === 'select'): ?>
                <button onclick="selectAndClose()" class="btn btn-primary">选择</button>
                <button onclick="window.parent.postMessage({type: 'mediaLibraryClosed'}, '*')" class="btn btn-outline">取消</button>
            <?php endif; ?>
        </div>

        <div class="content-card">
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($canManage): ?>
                <div class="upload-area" id="uploadArea">
                    <div class="upload-area-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width: 48px; height: 48px; opacity: 0.5;">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                    </div>
                    <div class="upload-area-text">点击或拖拽文件到此处上传</div>
                    <div class="upload-area-hint">支持图片、视频、文档等格式，单个文件最大 10MB</div>
                </div>
                
                <input type="file" id="fileInput" multiple accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.json,.xml,.html,.css,.js,.md,.rtf,.bmp,.tiff,.mov,.avi,.wmv" style="display: none;">
                <?php endif; ?>
                
                <div class="stats-bar">
                    <a href="javascript:void(0)" class="stat-item <?php echo !$filterType && !$filterMissing ? 'active' : ''; ?>" onclick="filterByType('')">
                        <div>
                            <div class="stat-value"><?php echo $stats['total']; ?></div>
                            <div class="stat-label">全部</div>
                        </div>
                    </a>
                    <a href="javascript:void(0)" class="stat-item <?php echo $filterType === 'image' ? 'active' : ''; ?>" onclick="filterByType('image')">
                        <div>
                            <div class="stat-value"><?php echo $stats['image']; ?></div>
                            <div class="stat-label">图片</div>
                        </div>
                    </a>
                    <a href="javascript:void(0)" class="stat-item <?php echo $filterType === 'video' ? 'active' : ''; ?>" onclick="filterByType('video')">
                        <div>
                            <div class="stat-value"><?php echo $stats['video']; ?></div>
                            <div class="stat-label">视频</div>
                        </div>
                    </a>
                    <a href="javascript:void(0)" class="stat-item <?php echo $filterType === 'document' ? 'active' : ''; ?>" onclick="filterByType('document')">
                        <div>
                            <div class="stat-value"><?php echo $stats['document']; ?></div>
                            <div class="stat-label">文档</div>
                        </div>
                    </a>
                    <?php if ($stats['missing'] > 0): ?>
                    <a href="javascript:void(0)" class="stat-item stat-item-warning <?php echo $filterMissing ? 'active' : ''; ?>" onclick="filterByMissing()">
                        <div>
                            <div class="stat-value"><?php echo $stats['missing']; ?></div>
                            <div class="stat-label">丢失</div>
                        </div>
                    </a>
                    <?php endif; ?>
                </div>
                
                <?php if ($stats['missing'] > 0): ?>
                <div class="missing-alert">
                    <div class="missing-alert-content">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <span><?php echo $filterMissing ? '当前显示' : '检测到'; ?> <strong><?php echo $stats['missing']; ?></strong> 个丢失的文件</span>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <?php if ($filterMissing): ?>
                        <button class="btn btn-outline btn-sm" onclick="clearMissingFilter()">显示全部</button>
                        <?php endif; ?>
                        <?php if ($canManage): ?>
                        <button class="btn btn-danger btn-sm" onclick="deleteMissingFiles()">删除所有丢失文件</button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="filter-bar">
                    <div class="filter-bar-inner">
                        <input type="text" id="searchInput" placeholder="搜索文件名、标题或替代文本..." value="<?php echo htmlspecialchars($search); ?>">
                        <select id="filterType">
                            <option value="">全部类型</option>
                            <option value="image" <?php echo $filterType === 'image' ? 'selected' : ''; ?>>图片</option>
                            <option value="video" <?php echo $filterType === 'video' ? 'selected' : ''; ?>>视频</option>
                            <option value="document" <?php echo $filterType === 'document' ? 'selected' : ''; ?>>文档</option>
                        </select>
                        <select id="filterMonth">
                            <option value="">全部时间</option>
                            <?php
                            $months = $db->fetchAll("SELECT DISTINCT DATE_FORMAT(upload_date, '%Y-%m') as month, DATE_FORMAT(upload_date, '%Y年%m月') as month_name FROM `lty-media_library` WHERE status = 1 ORDER BY month DESC");
                            foreach ($months as $month):
                            ?>
                                <option value="<?php echo $month['month']; ?>" <?php echo $filterMonth === $month['month'] ? 'selected' : ''; ?>><?php echo $month['month_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="view-toggle">
                        <button class="view-toggle-btn active" onclick="setView('grid')" title="网格视图" id="gridViewBtn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                            </svg>
                        </button>
                        <button class="view-toggle-btn" onclick="setView('list')" title="列表视图" id="listViewBtn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="8" y1="6" x2="21" y2="6"></line>
                                <line x1="8" y1="12" x2="21" y2="12"></line>
                                <line x1="8" y1="18" x2="21" y2="18"></line>
                                <line x1="3" y1="6" x2="3.01" y2="6"></line>
                                <line x1="3" y1="12" x2="3.01" y2="12"></line>
                                <line x1="3" y1="18" x2="3.01" y2="18"></line>
                            </svg>
                        </button>
                        <?php if ($canManage): ?>
                        <button class="view-toggle-btn" onclick="toggleBatchMode()" title="批量选择" id="batchModeBtn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 11 12 14 22 4"></polyline>
                                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                            </svg>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($canManage): ?>
                <div class="batch-actions" id="batchActions">
                    <div class="batch-actions-info">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <span>已选择 <strong id="selectedCount">0</strong> 个文件</span>
                    </div>
                    <div class="batch-actions-buttons">
                        <button class="batch-select-all-btn" onclick="selectAllVisible()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 11 12 14 22 4"></polyline>
                                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                            </svg>
                            全选当前页
                        </button>
                        <button class="batch-cancel-btn" onclick="cancelBatchMode()">取消选择</button>
                        <button class="batch-delete-btn" onclick="batchDelete()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                            批量删除
                        </button>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (empty($mediaItems)): ?>
                    <div style="text-align: center; padding: 60px 20px; color: var(--text-muted);">
                        <div style="font-size: 48px; margin-bottom: 16px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width: 48px; height: 48px; opacity: 0.5;">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                <polyline points="21 15 16 10 5 21"></polyline>
                            </svg>
                        </div>
                        <div style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">暂无媒体文件</div>
                        <div style="font-size: 14px;">点击上方按钮上传文件</div>
                    </div>
                <?php else: ?>
                    <div class="media-grid">
                        <?php foreach ($mediaItems as $media): 
                            $fileTypeName = $media['file_type'] === 'image' ? '图片' : ($media['file_type'] === 'video' ? '视频' : ($media['file_type'] === 'document' ? '文档' : '其他'));
                            $dimension = $media['width'] && $media['height'] ? $media['width'] . ' × ' . $media['height'] : '-';
                            $fileUrl = $media['file_url'];
                            $displayUrl = strlen($fileUrl) > 30 ? substr($fileUrl, 0, 30) . '...' : $fileUrl;
                            $isMissing = $media['is_missing'] ?? false;
                        ?>
                            <div class="media-item<?php echo $isMissing ? ' media-item-missing' : ''; ?>" data-id="<?php echo $media['id']; ?>" data-url="<?php echo htmlspecialchars($media['file_url']); ?>" onclick="handleMediaClick(event, <?php echo $media['id']; ?>)">
                                <div class="batch-checkbox" onclick="event.stopPropagation(); toggleBatchSelect(<?php echo $media['id']; ?>)">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                </div>
                                <div class="media-preview">
                                    <?php if ($isMissing): ?>
                                        <div class="broken-placeholder">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                                <line x1="21" y1="15" x2="15" y2="9"></line>
                                                <line x1="15" y1="15" x2="21" y2="9"></line>
                                            </svg>
                                            <span>文件丢失</span>
                                        </div>
                                    <?php elseif ($media['file_type'] == 'image'): ?>
                                        <img src="<?php echo htmlspecialchars($media['file_url']); ?>" alt="<?php echo htmlspecialchars($media['alt_text'] ?? $media['original_name']); ?>" loading="lazy" onerror="handleImageError(this)">
                                    <?php elseif ($media['file_type'] == 'video'): ?>
                                        <video src="<?php echo htmlspecialchars($media['file_url']); ?>" muted preload="metadata" onloadeddata="this.currentTime=1" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'"></video>
                                        <div class="video-placeholder" style="display: none; width: 100%; height: 100%; align-items: center; justify-content: center; background: var(--bg-secondary);">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 48px; height: 48px; opacity: 0.5;">
                                                <polygon points="23 7 16 12 23 17 23 7"></polygon>
                                                <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
                                            </svg>
                                        </div>
                                    <?php elseif ($media['file_type'] == 'document'): ?>
                                        <div class="document-preview">
                                            <?php 
                                            $fileExt = strtolower(pathinfo($media['original_name'], PATHINFO_EXTENSION));
                                            $iconColor = '#6b7280';
                                            $iconBg = 'var(--bg-secondary)';
                                            
                                            if (in_array($fileExt, ['pdf'])) {
                                                $iconColor = '#ef4444';
                                                $iconBg = 'rgba(239, 68, 68, 0.1)';
                                            } elseif (in_array($fileExt, ['doc', 'docx'])) {
                                                $iconColor = '#3b82f6';
                                                $iconBg = 'rgba(59, 130, 246, 0.1)';
                                            } elseif (in_array($fileExt, ['xls', 'xlsx'])) {
                                                $iconColor = '#10b981';
                                                $iconBg = 'rgba(16, 185, 129, 0.1)';
                                            } elseif (in_array($fileExt, ['ppt', 'pptx'])) {
                                                $iconColor = '#f59e0b';
                                                $iconBg = 'rgba(245, 158, 11, 0.1)';
                                            } elseif (in_array($fileExt, ['txt', 'md'])) {
                                                $iconColor = '#8b5cf6';
                                                $iconBg = 'rgba(139, 92, 246, 0.1)';
                                            } elseif (in_array($fileExt, ['json', 'xml', 'html', 'css', 'js'])) {
                                                $iconColor = '#06b6d4';
                                                $iconBg = 'rgba(6, 182, 212, 0.1)';
                                            } elseif (in_array($fileExt, ['csv'])) {
                                                $iconColor = '#84cc16';
                                                $iconBg = 'rgba(132, 204, 22, 0.1)';
                                            }
                                            ?>
                                            <div class="document-icon-wrapper" style="background: <?php echo $iconBg; ?>; border-radius: 12px; padding: 20px; display: flex; align-items: center; justify-content: center;">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="<?php echo $iconColor; ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 40px; height: 40px;">
                                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                                    <polyline points="14 2 14 8 20 8"></polyline>
                                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                                    <polyline points="10 9 9 9 8 9"></polyline>
                                                </svg>
                                            </div>
                                            <div class="document-ext" style="margin-top: 8px; font-size: 11px; font-weight: 600; color: <?php echo $iconColor; ?>; text-transform: uppercase;"><?php echo $fileExt; ?></div>
                                        </div>
                                    <?php else: ?>
                                        <div class="file-preview">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 48px; height: 48px; opacity: 0.5;">
                                                <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                                                <polyline points="13 2 13 9 20 9"></polyline>
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="media-info">
                                    <div class="media-name" title="<?php echo htmlspecialchars($media['filename'] ?? $media['original_name']); ?>"><?php echo htmlspecialchars($media['filename'] ?? $media['original_name']); ?></div>
                                    <div class="media-meta">
                                        <span class="media-meta-simple"><?php echo formatFileSize($media['file_size']); ?> · <?php echo date('Y-m-d', strtotime($media['upload_date'])); ?></span>
                                        <span class="media-meta-detail"><strong>文件类型：</strong><?php echo $fileTypeName; ?> (<?php echo $media['mime_type'] ?? '-'; ?>)</span>
                                        <span class="media-meta-detail"><strong>文件大小：</strong><?php echo formatFileSize($media['file_size']); ?></span>
                                        <span class="media-meta-detail"><strong>上传时间：</strong><?php echo date('Y-m-d', strtotime($media['upload_date'])); ?></span>
                                        <span class="media-meta-detail"><strong>尺寸：</strong><?php echo $dimension; ?></span>
                                        <span class="media-meta-detail"><strong>文件链接：</strong><a href="<?php echo htmlspecialchars($fileUrl); ?>" target="_blank" onclick="event.stopPropagation()" title="<?php echo htmlspecialchars($fileUrl); ?>"><?php echo htmlspecialchars($displayUrl); ?></a></span>
                                    </div>
                                </div>
                                <div class="media-actions">
                                    <button class="media-action-btn" onclick="event.stopPropagation(); previewMedia(<?php echo $media['id']; ?>)" title="预览">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    </button>
                                    <button class="media-action-btn" onclick="event.stopPropagation(); downloadFile(<?php echo $media['id']; ?>)" title="下载">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                    </button>
                                    <button class="media-action-btn" onclick="event.stopPropagation(); copyUrl(<?php echo $media['id']; ?>)" title="复制链接">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                                    </button>
                                    <?php if ($canManage): ?>
                                    <button class="media-action-btn" onclick="event.stopPropagation(); openEditModal(<?php echo $media['id']; ?>)" title="编辑">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    </button>
                                    <button class="media-action-btn" onclick="event.stopPropagation(); deleteMedia(<?php echo $media['id']; ?>)" title="删除">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination-wrapper">
                            <div class="pagination-info">共 <?php echo $total; ?> 条，第 <?php echo $page; ?> / <?php echo $totalPages; ?> 页</div>
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="media.php?page=<?php echo $page - 1; ?><?php echo $filterType ? '&filter_type=' . $filterType : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $filterMonth ? '&filter_month=' . $filterMonth : ''; ?>" class="pagination-btn nav-btn">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                                上一页
                                    </a>
                                <?php else: ?>
                                    <span class="pagination-btn nav-btn" disabled>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                                上一页
                                    </span>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <?php if ($i === 1 || $i === $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                        <a href="media.php?page=<?php echo $i; ?><?php echo $filterType ? '&filter_type=' . $filterType : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $filterMonth ? '&filter_month=' . $filterMonth : ''; ?>" class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                                    <?php elseif ($i === $page - 3 || $i === $page + 3): ?>
                                        <span class="pagination-ellipsis">...</span>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <a href="media.php?page=<?php echo $page + 1; ?><?php echo $filterType ? '&filter_type=' . $filterType : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $filterMonth ? '&filter_month=' . $filterMonth : ''; ?>" class="pagination-btn nav-btn">
                                下一页
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                                    </a>
                                <?php else: ?>
                                    <span class="pagination-btn nav-btn" disabled>
                                下一页
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="main-footer">
            <div class="footer-content">
                <span class="footer-line">© 2026 <?php echo htmlspecialchars(Settings::get('site_name', '官网系统')); ?> 1.0</span>
                <span class="footer-line">徐州邻兔跃IT工作室提供技术支持版权所有</span>
            </div>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2>编辑媒体</h2>
                <button class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editForm" onsubmit="return submitEditForm(event)">
                    <input type="hidden" name="media_id" id="editMediaId">
                    
                    <div class="form-group">
                        <label>标题</label>
                        <input type="text" name="title" id="editTitle" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label>替代文本</label>
                        <input type="text" name="alt_text" id="editAltText" class="form-control">
                        <small class="form-hint">用于图片的替代文本，有助于SEO和无障碍访问</small>
                    </div>
                    
                    <div class="form-group">
                        <label>说明文字</label>
                        <textarea name="caption" id="editCaption" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>描述</label>
                        <textarea name="description" id="editDescription" class="form-control" rows="4"></textarea>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline" onclick="closeEditModal()">取消</button>
                        <button type="submit" class="btn btn-primary">保存</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="previewModal" class="media-modal">
        <button class="media-modal-close" onclick="closePreviewModal()">×</button>
        <div class="media-modal-content">
            <div class="media-modal-preview">
                <img id="previewImage" class="media-modal-image" src="" alt="" style="display: none;">
                <div id="videoContainer" class="media-modal-video-container" style="display: none;">
                    <video id="previewVideo" class="media-modal-video" controls></video>
                </div>
            </div>
            <div class="media-modal-details" id="previewDetails"></div>
        </div>
    </div>

    <script>
    window.mediaData = <?php echo json_encode($mediaItems, JSON_UNESCAPED_UNICODE); ?>;
    window.missingIds = <?php echo $missingIdsJson; ?>;
    window.canManage = <?php echo $canManage ? 'true' : 'false'; ?>;
    let selectedMediaId = null;
    let searchTimeout = null;
    let batchMode = false;
    let batchSelectedIds = new Set();
    let filterMissing = <?php echo $filterMissing ? 'true' : 'false'; ?>;

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function debounce(func, delay) {
        return function(...args) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => func.apply(this, args), delay);
        };
    }

    function performSearch(page = 1) {
        const searchInput = document.getElementById('searchInput');
        const filterTypeEl = document.getElementById('filterType');
        const filterMonth = document.getElementById('filterMonth');
        const mediaGrid = document.querySelector('.media-grid');
        const paginationContainer = document.querySelector('.pagination');
        const contentCard = document.querySelector('.content-card .card-body');
        
        const params = new URLSearchParams();
        params.append('action', 'list');
        params.append('page', page);
        params.append('per_page', 20);
        if (searchInput.value) params.append('search', searchInput.value);
        if (filterTypeEl.value) params.append('filter_type', filterTypeEl.value);
        if (filterMonth.value) params.append('filter_month', filterMonth.value);
        if (filterMissing) params.append('filter_missing', '1');
        
        const loadingHtml = `<div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: var(--text-muted);"><div style="margin-bottom: 16px;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 48px; height: 48px; animation: spin 1s linear infinite;"><path d="M21 12a9 9 0 11-6.219-8.56"></path></svg></div><div style="font-size: 16px;">加载中...</div></div>`;
        
        const existingEmpty = contentCard.querySelector('[style*="text-align: center"]');
        if (existingEmpty && !mediaGrid) {
            existingEmpty.outerHTML = `<div class="media-grid"></div>`;
        }
        
        const grid = document.querySelector('.media-grid');
        if (grid) {
            grid.innerHTML = loadingHtml;
        }
        
        fetch('api/media.php?' + params.toString())
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateMediaList(data.data, data.pagination);
                    updateStats();
                } else {
                    alert('加载失败：' + data.message);
                }
            })
            .catch(error => {
                console.error('Search error:', error);
                alert('加载失败，请重试');
            });
    }

    function updateMediaList(items, pagination) {
        const contentCard = document.querySelector('.content-card .card-body');
        let mediaGrid = document.querySelector('.media-grid');
        const savedView = localStorage.getItem('mediaViewMode');
        
        if (!mediaGrid) {
            const emptyState = contentCard.querySelector('[style*="text-align: center"]');
            if (emptyState) {
                emptyState.outerHTML = '<div class="media-grid"></div>';
                mediaGrid = document.querySelector('.media-grid');
            } else {
                const statsBar = document.querySelector('.stats-bar');
                const filterBar = document.querySelector('.filter-bar');
                if (statsBar && filterBar) {
                    const newGrid = document.createElement('div');
                    newGrid.className = 'media-grid';
                    filterBar.after(newGrid);
                    mediaGrid = newGrid;
                }
            }
        }
        
        if (savedView === 'list') {
            mediaGrid.classList.add('list-view');
        } else {
            mediaGrid.classList.remove('list-view');
        }
        
        if (!items || items.length === 0) {
            mediaGrid.outerHTML = `<div style="text-align: center; padding: 60px 20px; color: var(--text-muted);"><div style="margin-bottom: 16px;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width: 48px; height: 48px; opacity: 0.5;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg></div><div style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">暂无媒体文件</div><div style="font-size: 14px;">点击上方按钮上传文件</div></div>`;
            updatePagination(pagination);
            return;
        }
        
        let html = '';
        items.forEach(media => {
            const fileTypeName = media.file_type === 'image' ? '图片' : media.file_type === 'video' ? '视频' : media.file_type === 'document' ? '文档' : '其他';
            const dimension = media.width && media.height ? `${media.width} × ${media.height}` : '-';
            const fileUrl = media.file_url;
            const displayUrl = fileUrl.length > 30 ? fileUrl.substring(0, 30) + '...' : fileUrl;
            const isMissing = media.is_missing || false;
            const missingClass = isMissing ? ' media-item-missing' : '';
            
            let previewHtml = '';
            if (isMissing) {
                previewHtml = `<div class="broken-placeholder">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                        <line x1="21" y1="15" x2="15" y2="9"></line>
                        <line x1="15" y1="15" x2="21" y2="9"></line>
                    </svg>
                    <span>文件丢失</span>
                </div>`;
            } else if (media.file_type == 'image') {
                previewHtml = `<img src="${escapeHtml(media.file_url)}" alt="${escapeHtml(media.alt_text || media.original_name)}" loading="lazy" onerror="handleImageError(this)">`;
            } else if (media.file_type == 'video') {
                previewHtml = `<video src="${escapeHtml(media.file_url)}" muted preload="metadata" onloadeddata="this.currentTime=1" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'"></video>
                <div class="video-placeholder" style="display: none; width: 100%; height: 100%; align-items: center; justify-content: center; background: var(--bg-secondary);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 48px; height: 48px; opacity: 0.5;">
                        <polygon points="23 7 16 12 23 17 23 7"></polygon>
                        <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
                    </svg>
                </div>`;
            } else if (media.file_type == 'document') {
                const fileExt = media.original_name ? media.original_name.split('.').pop().toLowerCase() : '';
                let iconColor = '#6b7280';
                let iconBg = 'var(--bg-secondary)';
                
                if (['pdf'].includes(fileExt)) {
                    iconColor = '#ef4444';
                    iconBg = 'rgba(239, 68, 68, 0.1)';
                } else if (['doc', 'docx'].includes(fileExt)) {
                    iconColor = '#3b82f6';
                    iconBg = 'rgba(59, 130, 246, 0.1)';
                } else if (['xls', 'xlsx'].includes(fileExt)) {
                    iconColor = '#10b981';
                    iconBg = 'rgba(16, 185, 129, 0.1)';
                } else if (['ppt', 'pptx'].includes(fileExt)) {
                    iconColor = '#f59e0b';
                    iconBg = 'rgba(245, 158, 11, 0.1)';
                } else if (['txt', 'md'].includes(fileExt)) {
                    iconColor = '#8b5cf6';
                    iconBg = 'rgba(139, 92, 246, 0.1)';
                } else if (['json', 'xml', 'html', 'css', 'js'].includes(fileExt)) {
                    iconColor = '#06b6d4';
                    iconBg = 'rgba(6, 182, 212, 0.1)';
                } else if (['csv'].includes(fileExt)) {
                    iconColor = '#84cc16';
                    iconBg = 'rgba(132, 204, 22, 0.1)';
                }
                
                previewHtml = `<div class="document-preview">
                    <div class="document-icon-wrapper" style="background: ${iconBg}; border-radius: 12px; padding: 20px; display: flex; align-items: center; justify-content: center;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="${iconColor}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 40px; height: 40px;">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                    </div>
                    <div class="document-ext" style="margin-top: 8px; font-size: 11px; font-weight: 600; color: ${iconColor}; text-transform: uppercase;">${fileExt}</div>
                </div>`;
            } else {
                previewHtml = `<div class="file-preview">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 48px; height: 48px; opacity: 0.5;">
                        <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                        <polyline points="13 2 13 9 20 9"></polyline>
                    </svg>
                </div>`;
            }
            
            html += `
                <div class="media-item${missingClass}" data-id="${media.id}" data-url="${escapeHtml(media.file_url)}" onclick="handleMediaClick(event, ${media.id})">
                    <div class="batch-checkbox" onclick="event.stopPropagation(); toggleBatchSelect(${media.id})">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </div>
                    <div class="media-preview">
                        ${previewHtml}
                    </div>
                    <div class="media-info">
                        <div class="media-name" title="${escapeHtml(media.filename || media.original_name)}">${escapeHtml(media.filename || media.original_name)}</div>
                        <div class="media-meta">
                            <span class="media-meta-simple">${formatFileSize(media.file_size)} · ${media.upload_date.substring(0, 10)}</span>
                            <span class="media-meta-detail"><strong>文件类型：</strong>${fileTypeName} (${media.mime_type || '-'})</span>
                            <span class="media-meta-detail"><strong>文件大小：</strong>${formatFileSize(media.file_size)}</span>
                            <span class="media-meta-detail"><strong>上传时间：</strong>${media.upload_date.substring(0, 10)}</span>
                            <span class="media-meta-detail"><strong>尺寸：</strong>${dimension}</span>
                            <span class="media-meta-detail"><strong>文件链接：</strong><a href="${escapeHtml(fileUrl)}" target="_blank" onclick="event.stopPropagation()" title="${escapeHtml(fileUrl)}">${escapeHtml(displayUrl)}</a></span>
                        </div>
                    </div>
                    <div class="media-actions">
                        <button class="media-action-btn" onclick="event.stopPropagation(); openEditModal(${media.id})" title="编辑">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                        </button>
                        <button class="media-action-btn" onclick="event.stopPropagation(); previewMedia(${media.id})" title="预览">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                        <button class="media-action-btn" onclick="event.stopPropagation(); downloadFile(${media.id})" title="下载">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        </button>
                        <button class="media-action-btn" onclick="event.stopPropagation(); copyUrl(${media.id})" title="复制链接">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                        </button>
                        <button class="media-action-btn" onclick="event.stopPropagation(); deleteMedia(${media.id})" title="删除">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                        </button>
                    </div>
                </div>
            `;
        });
        
        mediaGrid.innerHTML = html;
        
        window.mediaData = items;
        
        const gridViewBtn = document.getElementById('gridViewBtn');
        const listViewBtn = document.getElementById('listViewBtn');
        if (savedView === 'list') {
            gridViewBtn.classList.remove('active');
            listViewBtn.classList.add('active');
        } else {
            gridViewBtn.classList.add('active');
            listViewBtn.classList.remove('active');
        }
        
        if (batchMode) {
            document.querySelectorAll('.media-item').forEach(item => {
                item.querySelector('.batch-checkbox').style.display = 'flex';
                const id = parseInt(item.dataset.id);
                if (batchSelectedIds.has(id)) {
                    item.classList.add('batch-selected');
                }
            });
        }
        
        updatePagination(pagination);
    }

    function handleImageError(img) {
        if (img.dataset.errorHandled) return;
        img.dataset.errorHandled = 'true';
        
        const placeholder = document.createElement('div');
        placeholder.className = 'broken-placeholder';
        placeholder.innerHTML = `
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                <line x1="21" y1="15" x2="15" y2="9"></line>
                <line x1="15" y1="15" x2="21" y2="9"></line>
            </svg>
            <span>图片丢失</span>
        `;
        
        img.parentNode.replaceChild(placeholder, img);
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function updatePagination(pagination) {
        let paginationWrapper = document.querySelector('.pagination-wrapper');
        let paginationContainer = document.querySelector('.pagination');
        
        if (!pagination || pagination.total_pages <= 1) {
            if (paginationWrapper) {
                paginationWrapper.remove();
            }
            return;
        }
        
        const { page, total_pages, total } = pagination;
        
        let html = '';
        
        if (page > 1) {
            html += `<button class="pagination-btn nav-btn" onclick="performSearch(${page - 1})">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                上一页
            </button>`;
        } else {
            html += `<span class="pagination-btn nav-btn" disabled>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                上一页
            </span>`;
        }
        
        for (let i = 1; i <= total_pages; i++) {
            if (i === 1 || i === total_pages || (i >= page - 2 && i <= page + 2)) {
                html += `<button class="pagination-btn ${i === page ? 'active' : ''}" onclick="performSearch(${i})">${i}</button>`;
            } else if (i === page - 3 || i === page + 3) {
                html += `<span class="pagination-ellipsis">...</span>`;
            }
        }
        
        if (page < total_pages) {
            html += `<button class="pagination-btn nav-btn" onclick="performSearch(${page + 1})">
                下一页
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </button>`;
        } else {
            html += `<span class="pagination-btn nav-btn" disabled>
                下一页
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </span>`;
        }
        
        if (!paginationWrapper) {
            const mediaGrid = document.querySelector('.media-grid');
            if (mediaGrid) {
                paginationWrapper = document.createElement('div');
                paginationWrapper.className = 'pagination-wrapper';
                paginationWrapper.innerHTML = `
                    <div class="pagination-info">共 ${total} 条，第 ${page} / ${total_pages} 页</div>
                    <div class="pagination">${html}</div>
                `;
                mediaGrid.after(paginationWrapper);
            }
        } else {
            paginationWrapper.innerHTML = `
                <div class="pagination-info">共 ${total} 条，第 ${page} / ${total_pages} 页</div>
                <div class="pagination">${html}</div>
            `;
        }
    }

    function updateStats() {
        fetch('api/media.php?action=stats')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const stats = data.data;
                    const statItems = document.querySelectorAll('.stat-item .stat-value');
                    if (statItems.length >= 4) {
                        statItems[0].textContent = stats.total;
                        statItems[1].textContent = stats.image;
                        statItems[2].textContent = stats.video;
                        statItems[3].textContent = stats.document;
                    }
                }
            });
    }

    function filterByType(type) {
        filterMissing = false;
        const filterType = document.getElementById('filterType');
        filterType.value = type;
        
        updateStatItemActive(type);
        updateMissingAlert();
        
        performSearch(1);
    }

    function filterByMissing() {
        filterMissing = true;
        
        updateStatItemActive('missing');
        updateMissingAlert();
        
        performSearch(1);
    }
    
    function clearMissingFilter() {
        filterMissing = false;
        
        const filterTypeEl = document.getElementById('filterType');
        filterTypeEl.value = '';
        
        updateStatItemActive('');
        updateMissingAlert();
        
        performSearch(1);
    }
    
    function updateStatItemActive(type) {
        document.querySelectorAll('.stat-item').forEach(item => {
            item.classList.remove('active');
        });
        
        const statBar = document.querySelector('.stats-bar');
        if (!statBar) return;
        
        const items = statBar.querySelectorAll('.stat-item');
        items.forEach((item, index) => {
            const label = item.querySelector('.stat-label');
            if (!label) return;
            
            const labelText = label.textContent.trim();
            if (type === '' && labelText === '全部') {
                item.classList.add('active');
            } else if (type === 'image' && labelText === '图片') {
                item.classList.add('active');
            } else if (type === 'video' && labelText === '视频') {
                item.classList.add('active');
            } else if (type === 'document' && labelText === '文档') {
                item.classList.add('active');
            } else if (type === 'missing' && labelText === '丢失') {
                item.classList.add('active');
            }
        });
    }
    
    function updateMissingAlert() {
        const alertContainer = document.querySelector('.missing-alert');
        if (!alertContainer) return;
        
        if (filterMissing) {
            alertContainer.innerHTML = `
                <div class="missing-alert-content">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span>当前显示 <strong>${window.missingIds.length}</strong> 个丢失的文件</span>
                </div>
                <div style="display: flex; gap: 8px;">
                    <button class="btn btn-outline btn-sm" onclick="clearMissingFilter()">显示全部</button>
                    ${window.canManage ? '<button class="btn btn-danger btn-sm" onclick="deleteMissingFiles()">删除所有丢失文件</button>' : ''}
                </div>
            `;
        } else {
            alertContainer.innerHTML = `
                <div class="missing-alert-content">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span>检测到 <strong>${window.missingIds.length}</strong> 个丢失的文件</span>
                </div>
                ${window.canManage ? '<button class="btn btn-danger btn-sm" onclick="deleteMissingFiles()">删除所有丢失文件</button>' : ''}
            `;
        }
    }

    function deleteMissingFiles() {
        if (!window.canManage) {
            alert('您没有权限删除媒体文件');
            return;
        }
        if (confirm('确定要删除所有丢失文件吗？共 ' + window.missingIds.length + ' 个文件记录将被删除。')) {
            const formData = new FormData();
            formData.append('action', 'delete_missing');
            formData.append('ids', JSON.stringify(window.missingIds));
            
            fetch('api/media.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('已删除 ' + (data.deleted || 0) + ' 个丢失文件记录');
                    window.location.reload();
                } else {
                    alert('删除失败：' + (data.message || '未知错误'));
                }
            })
            .catch(err => {
                console.error('Delete error:', err);
                alert('删除失败，请重试');
            });
        }
    }

    function setView(view) {
        const mediaGrid = document.querySelector('.media-grid');
        const gridViewBtn = document.getElementById('gridViewBtn');
        const listViewBtn = document.getElementById('listViewBtn');
        
        if (view === 'list') {
            mediaGrid.classList.add('list-view');
            gridViewBtn.classList.remove('active');
            listViewBtn.classList.add('active');
            localStorage.setItem('mediaViewMode', 'list');
        } else {
            mediaGrid.classList.remove('list-view');
            gridViewBtn.classList.add('active');
            listViewBtn.classList.remove('active');
            localStorage.setItem('mediaViewMode', 'grid');
        }
    }

    function loadViewMode() {
        const savedView = localStorage.getItem('mediaViewMode');
        if (savedView === 'list') {
            setView('list');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const filterType = document.getElementById('filterType');
        const filterMonth = document.getElementById('filterMonth');
        
        if (searchInput) {
            searchInput.addEventListener('input', debounce(function() {
                performSearch();
            }, 500));
        }
        
        if (filterType) {
            filterType.addEventListener('change', function() {
                performSearch();
            });
        }
        
        if (filterMonth) {
            filterMonth.addEventListener('change', function() {
                performSearch();
            });
        }
        
        loadViewMode();
    });

    function selectMedia(id) {
        selectedMediaId = id;
        document.querySelectorAll('.media-item').forEach(item => {
            item.classList.remove('selected');
        });
        const selectedItem = document.querySelector(`.media-item[data-id="${id}"]`);
        if (selectedItem) {
            selectedItem.classList.add('selected');
        }
    }

    function handleMediaClick(event, id) {
        if (batchMode) {
            toggleBatchSelect(id);
        } else {
            selectMedia(id);
        }
    }

    function toggleBatchMode() {
        batchMode = !batchMode;
        const batchModeBtn = document.getElementById('batchModeBtn');
        const batchActions = document.getElementById('batchActions');
        
        if (batchMode) {
            batchModeBtn.classList.add('active');
            batchActions.classList.add('active');
            document.querySelectorAll('.media-item').forEach(item => {
                item.querySelector('.batch-checkbox').style.display = 'flex';
            });
        } else {
            batchModeBtn.classList.remove('active');
            batchActions.classList.remove('active');
            batchSelectedIds.clear();
            updateSelectedCount();
            document.querySelectorAll('.media-item').forEach(item => {
                item.classList.remove('batch-selected');
                item.querySelector('.batch-checkbox').style.display = '';
            });
        }
    }

    function toggleBatchSelect(id) {
        const item = document.querySelector(`.media-item[data-id="${id}"]`);
        if (!item) return;
        
        if (batchSelectedIds.has(id)) {
            batchSelectedIds.delete(id);
            item.classList.remove('batch-selected');
        } else {
            batchSelectedIds.add(id);
            item.classList.add('batch-selected');
        }
        updateSelectedCount();
    }

    function selectAllVisible() {
        const items = document.querySelectorAll('.media-item');
        const allSelected = items.length === batchSelectedIds.size;
        
        if (allSelected) {
            batchSelectedIds.clear();
            items.forEach(item => {
                item.classList.remove('batch-selected');
            });
        } else {
            items.forEach(item => {
                const id = parseInt(item.dataset.id);
                batchSelectedIds.add(id);
                item.classList.add('batch-selected');
            });
        }
        updateSelectedCount();
    }

    function cancelBatchMode() {
        toggleBatchMode();
    }

    function updateSelectedCount() {
        document.getElementById('selectedCount').textContent = batchSelectedIds.size;
    }

    function batchDelete() {
        if (!window.canManage) {
            alert('您没有权限删除媒体文件');
            return;
        }
        if (batchSelectedIds.size === 0) {
            alert('请先选择要删除的文件');
            return;
        }
        
        if (!confirm(`确定要删除选中的 ${batchSelectedIds.size} 个文件吗？此操作不可恢复！`)) {
            return;
        }
        
        const ids = Array.from(batchSelectedIds);
        
        fetch('api/media.php?action=batch_delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ ids: ids })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                batchSelectedIds.clear();
                toggleBatchMode();
                performSearch(1);
                updateStats();
            } else {
                alert('删除失败：' + data.message);
            }
        })
        .catch(error => {
            console.error('Batch delete error:', error);
            alert('删除失败，请重试');
        });
    }

    function openEditModal(id) {
        if (!window.canManage) {
            alert('您没有权限编辑媒体文件');
            return;
        }
        const media = window.mediaData.find(m => m.id == id);
        if (!media) {
            alert('未找到媒体文件');
            return;
        }

        document.getElementById('editMediaId').value = media.id;
        document.getElementById('editTitle').value = media.title || '';
        document.getElementById('editAltText').value = media.alt_text || '';
        document.getElementById('editCaption').value = media.caption || '';
        document.getElementById('editDescription').value = media.description || '';

        document.getElementById('editModal').classList.add('active');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.remove('active');
    }

    function submitEditForm(event) {
        event.preventDefault();
        
        const mediaId = document.getElementById('editMediaId').value;
        const title = document.getElementById('editTitle').value;
        const altText = document.getElementById('editAltText').value;
        const caption = document.getElementById('editCaption').value;
        const description = document.getElementById('editDescription').value;
        
        const data = {
            title: title,
            alt_text: altText,
            caption: caption,
            description: description
        };
        
        fetch('api/media.php?action=update&id=' + mediaId, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                closeEditModal();
                
                const media = window.mediaData.find(m => m.id == mediaId);
                if (media) {
                    media.title = title;
                    media.alt_text = altText;
                    media.caption = caption;
                    media.description = description;
                }
                
                alert('保存成功');
            } else {
                alert('保存失败：' + result.message);
            }
        })
        .catch(error => {
            console.error('Update error:', error);
            alert('保存失败，请重试');
        });
        
        return false;
    }

    function previewMedia(id) {
        const media = window.mediaData.find(m => m.id == id);
        if (!media) {
            alert('未找到媒体文件');
            return;
        }

        let url = media.file_url;
        if (url.startsWith('../') || url.startsWith('./')) {
            const baseUrl = window.location.origin + window.location.pathname.replace(/admin\/[^\/]*$/, '');
            url = baseUrl + url.replace(/^\.\.\//, '');
        }

        const previewImage = document.getElementById('previewImage');
        const previewVideo = document.getElementById('previewVideo');
        const videoContainer = document.getElementById('videoContainer');

        previewImage.style.display = 'none';
        previewVideo.style.display = 'none';
        videoContainer.style.display = 'none';

        if (media.file_type == 'image') {
            previewImage.src = media.file_url;
            previewImage.style.display = 'block';
        } else if (media.file_type == 'video') {
            previewVideo.src = media.file_url;
            previewVideo.style.display = 'block';
            videoContainer.style.display = 'flex';
            
            previewVideo.onloadedmetadata = function() {
                const videoWidth = this.videoWidth;
                const videoHeight = this.videoHeight;
                const aspectRatio = videoWidth / videoHeight;
                
                if (aspectRatio > 1) {
                    this.style.width = '100%';
                    this.style.height = 'auto';
                    this.style.maxWidth = '60vw';
                    this.style.maxHeight = '90vh';
                } else {
                    this.style.height = '100%';
                    this.style.width = 'auto';
                    this.style.maxHeight = '90vh';
                    this.style.maxWidth = '60vw';
                }
                
                this.play().catch(e => {
                    console.log('自动播放被阻止:', e);
                });
            };
            
            previewVideo.load();
        }

        let detailsHtml = `
            <h3>${media.original_name}</h3>
            <p><strong>文件类型：</strong>${media.mime_type}</p>
            <p><strong>文件大小：</strong>${formatFileSize(media.file_size)}</p>
            <p><strong>上传时间：</strong>${new Date(media.upload_date).toLocaleString('zh-CN')}</p>
            ${media.width ? `<p><strong>尺寸：</strong>${media.width} x ${media.height}</p>` : ''}
            <p><strong>文件链接：</strong><a href="${url}" target="_blank" style="color: var(--primary-color); word-break: break-all;">${url}</a></p>
        `;

        if (media.title) {
            detailsHtml += `<p><strong>标题：</strong>${media.title}</p>`;
        }
        if (media.alt_text) {
            detailsHtml += `<p><strong>替代文本：</strong>${media.alt_text}</p>`;
        }
        if (media.caption) {
            detailsHtml += `<p><strong>说明：</strong>${media.caption}</p>`;
        }
        if (media.description) {
            detailsHtml += `<p><strong>描述：</strong>${media.description}</p>`;
        }

        document.getElementById('previewDetails').innerHTML = detailsHtml;

        document.getElementById('previewModal').classList.add('active');
    }
    
    function closePreviewModal() {
        const previewVideo = document.getElementById('previewVideo');
        
        if (previewVideo) {
            previewVideo.pause();
            previewVideo.src = '';
        }
        
        document.getElementById('previewModal').classList.remove('active');
    }

    function downloadFile(id) {
        const media = window.mediaData.find(m => m.id == id);
        if (!media) {
            alert('未找到媒体文件');
            return;
        }

        let url = media.file_url;
        
        if (url.startsWith('../') || url.startsWith('./')) {
            const baseUrl = window.location.origin + window.location.pathname.replace(/admin\/[^\/]*$/, '');
            url = baseUrl + url.replace(/^\.\.\//, '');
        }
        
        const link = document.createElement('a');
        link.href = url;
        link.download = media.original_name || media.filename || 'download';
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function copyUrl(id) {
        const media = window.mediaData.find(m => m.id == id);
        if (!media) {
            alert('未找到媒体文件');
            return;
        }

        let url = media.file_url;
        
        if (url.startsWith('../') || url.startsWith('./')) {
            const baseUrl = window.location.origin + window.location.pathname.replace(/admin\/[^\/]*$/, '');
            url = baseUrl + url.replace(/^\.\.\//, '');
        }
        
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(() => {
                alert('链接已复制到剪贴板');
            }).catch(err => {
                console.error('Clipboard error:', err);
                fallbackCopy(url);
            });
        } else {
            fallbackCopy(url);
        }
    }

    function fallbackCopy(url) {
        const textarea = document.createElement('textarea');
        textarea.value = url;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        textarea.setSelectionRange(0, 99999);
        
        try {
            const successful = document.execCommand('copy');
            document.body.removeChild(textarea);
            if (successful) {
                alert('链接已复制到剪贴板');
            } else {
                alert('复制失败，请手动复制：' + url);
            }
        } catch (err) {
            document.body.removeChild(textarea);
            console.error('Copy error:', err);
            alert('复制失败，请手动复制：' + url);
        }
    }

    function deleteMedia(id) {
        if (!window.canManage) {
            alert('您没有权限删除媒体文件');
            return;
        }
        if (!confirm('确定要删除这个文件吗？')) return;
        
        fetch('api/media.php?action=delete&id=' + id, {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const item = document.querySelector(`.media-item[data-id="${id}"]`);
                if (item) {
                    item.style.transition = 'opacity 0.3s';
                    item.style.opacity = '0';
                    setTimeout(() => {
                        item.remove();
                        updateStats();
                        
                        const mediaGrid = document.querySelector('.media-grid');
                        if (mediaGrid && mediaGrid.children.length === 0) {
                            performSearch(1);
                        }
                    }, 300);
                }
            } else {
                alert('删除失败：' + data.message);
            }
        })
        .catch(error => {
            console.error('Delete error:', error);
            alert('删除失败，请重试');
        });
    }

    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('fileInput');

    uploadArea.addEventListener('click', () => fileInput.click());

    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        handleFiles(e.dataTransfer.files);
    });

    fileInput.addEventListener('change', (e) => {
        handleFiles(e.target.files);
    });

    function handleFiles(files) {
        if (files.length === 0) return;

        const formData = new FormData();
        for (let i = 0; i < files.length; i++) {
            formData.append('files[]', files[i]);
        }

        fetch('api/upload.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.text();
        })
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    alert('上传成功！');
                    performSearch(1);
                } else {
                    let errorMsg = data.message || '未知错误';
                    if (data.errors && data.errors.length > 0) {
                        errorMsg += '\n' + data.errors.join('\n');
                    }
                    alert('上传失败：' + errorMsg);
                }
            } catch (e) {
                alert('上传失败：服务器返回的数据格式错误\n' + text.substring(0, 200));
            }
        })
        .catch(error => {
            alert('上传失败：' + error.message);
        });
    }

    document.getElementById('previewModal').addEventListener('click', (e) => {
        if (e.target === document.getElementById('previewModal')) {
            closePreviewModal();
        }
    });

    document.getElementById('editModal').addEventListener('click', (e) => {
        if (e.target.classList.contains('modal')) {
            closeEditModal();
        }
    });

    window.selectMediaForInput = function(inputId) {
        if (selectedMediaId) {
            const media = window.mediaData.find(m => m.id === selectedMediaId);
            if (media) {
                const input = document.getElementById(inputId);
                if (input) {
                    input.value = media.file_url;
                    if (input.type === 'hidden') {
                        const img = document.getElementById(inputId + '_preview');
                        if (img) {
                            img.src = media.file_url;
                            img.style.display = 'block';
                        }
                    }
                }
            }
        }
        window.closeMediaLibrary();
    };

    function selectAndClose() {
        if (selectedMediaId) {
            const media = window.mediaData.find(m => m.id === selectedMediaId);
            if (media) {
                window.parent.postMessage({
                    type: 'mediaSelected',
                    url: media.file_url,
                    id: media.id
                }, '*');
            }
        } else {
            alert('请先选择一个媒体文件');
        }
    }
    </script>
</body>
</html>

<?php
function formatFileSize($bytes) {
    if ($bytes === 0) return '0 B';
    $k = 1024;
    $sizes = ['B', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}
?>

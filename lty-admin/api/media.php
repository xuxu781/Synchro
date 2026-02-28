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
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

if (!Auth::check()) {
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit;
}

$canView = Auth::isSuperAdmin() || Auth::hasPermission('media_view') || Auth::hasPermission('media_manage');
$canManage = Auth::isSuperAdmin() || Auth::hasPermission('media_manage');

$db = Database::getInstance();
$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

if ($action === 'list') {
    if (!$canView) {
        echo json_encode(['success' => false, 'message' => '无权限查看媒体库']);
        exit;
    }
    
    $page = (int)($_GET['page'] ?? 1);
    $perPage = (int)($_GET['per_page'] ?? 20);
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
    
    if ($filterMissing) {
        $missingWhereClause = 'status = 1';
        $missingParams = [];
        
        if ($filterType) {
            $missingWhereClause .= ' AND file_type = ?';
            $missingParams[] = $filterType;
        }
        
        $allMediaFiles = $db->fetchAll("SELECT id, file_path FROM `lty-media_library` WHERE $missingWhereClause", $missingParams);
        $missingIds = [];
        foreach ($allMediaFiles as $media) {
            if (!file_exists($media['file_path'])) {
                $missingIds[] = $media['id'];
            }
        }
        if (!empty($missingIds)) {
            $whereConditions[] = 'id IN (' . implode(',', array_map('intval', $missingIds)) . ')';
        } else {
            $whereConditions[] = '1 = 0';
        }
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    $total = $db->fetchOne("SELECT COUNT(*) as count FROM `lty-media_library` WHERE $whereClause", $params)['count'];
    $totalPages = ceil($total / $perPage);
    
    $mediaItems = $db->fetchAll("SELECT * FROM `lty-media_library` WHERE $whereClause ORDER BY upload_date DESC LIMIT $offset, $perPage", $params);
    
    $siteUrl = Settings::get('site_url', '');
    if (empty($siteUrl)) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $siteUrl = $protocol . '://' . $host;
    }
    $siteUrl = rtrim($siteUrl, '/');
    
    foreach ($mediaItems as &$item) {
        if (!empty($item['file_url'])) {
            $url = $item['file_url'];
            if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
                $parsedUrl = parse_url($url);
                $path = $parsedUrl['path'] ?? '';
                if (strpos($path, '/uploads/') !== false) {
                    $item['file_url'] = $siteUrl . $path;
                }
            } else {
                $item['file_url'] = $siteUrl . '/' . ltrim($url, '/');
            }
        }
        $item['is_missing'] = !file_exists($item['file_path']);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $mediaItems,
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages
        ]
    ]);
    exit;
}

if ($action === 'get' && isset($_GET['id'])) {
    if (!$canView) {
        echo json_encode(['success' => false, 'message' => '无权限查看媒体库']);
        exit;
    }
    
    $id = (int)$_GET['id'];
    $media = $db->fetchOne("SELECT * FROM `lty-media_library` WHERE id = ? AND status = 1", [$id]);
    
    if ($media) {
        echo json_encode(['success' => true, 'data' => $media]);
    } else {
        echo json_encode(['success' => false, 'message' => '媒体文件不存在']);
    }
    exit;
}

if ($action === 'delete' && isset($_GET['id'])) {
    if (!$canManage) {
        echo json_encode(['success' => false, 'message' => '无权限删除媒体文件']);
        exit;
    }
    
    $id = (int)$_GET['id'];
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => '请求方法错误']);
        exit;
    }
    
    $media = $db->fetchOne("SELECT * FROM `lty-media_library` WHERE id = ?", [$id]);
    if ($media) {
        if (file_exists($media['file_path'])) {
            @unlink($media['file_path']);
        }
        $db->delete('`lty-media_library`', 'id = ?', [$id]);
        OperationLog::delete('media', '删除媒体文件：' . ($media['original_name'] ?? $id), 'media', $id);
        echo json_encode(['success' => true, 'message' => '删除成功']);
    } else {
        echo json_encode(['success' => false, 'message' => '媒体文件不存在']);
    }
    exit;
}

if ($action === 'batch_delete') {
    if (!$canManage) {
        echo json_encode(['success' => false, 'message' => '无权限删除媒体文件']);
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => '请求方法错误']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $ids = $input['ids'] ?? [];
    
    if (empty($ids) || !is_array($ids)) {
        echo json_encode(['success' => false, 'message' => '请选择要删除的文件']);
        exit;
    }
    
    $deletedCount = 0;
    foreach ($ids as $id) {
        $id = (int)$id;
        $media = $db->fetchOne("SELECT * FROM `lty-media_library` WHERE id = ?", [$id]);
        if ($media) {
            if (file_exists($media['file_path'])) {
                @unlink($media['file_path']);
            }
            $db->delete('`lty-media_library`', 'id = ?', [$id]);
            OperationLog::delete('media', '批量删除媒体文件：' . ($media['original_name'] ?? $id), 'media', $id);
            $deletedCount++;
        }
    }
    
    echo json_encode(['success' => true, 'message' => "成功删除 {$deletedCount} 个文件", 'deleted_count' => $deletedCount]);
    exit;
}

if ($action === 'delete_missing') {
    if (!$canManage) {
        echo json_encode(['success' => false, 'message' => '无权限删除媒体文件']);
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => '请求方法错误']);
        exit;
    }
    
    $idsRaw = $_POST['ids'] ?? '[]';
    $ids = json_decode($idsRaw, true);
    
    if (!is_array($ids)) {
        $ids = [];
    }
    
    if (empty($ids)) {
        echo json_encode(['success' => false, 'message' => '没有丢失的文件']);
        exit;
    }
    
    $deletedCount = 0;
    foreach ($ids as $id) {
        $id = (int)$id;
        $media = $db->fetchOne("SELECT * FROM `lty-media_library` WHERE id = ?", [$id]);
        if ($media) {
            if (file_exists($media['file_path'])) {
                @unlink($media['file_path']);
            }
            $db->delete('`lty-media_library`', 'id = ?', [$id]);
            OperationLog::delete('media', '删除丢失媒体文件记录：' . ($media['original_name'] ?? $id), 'media', $id);
            $deletedCount++;
        }
    }
    
    echo json_encode(['success' => true, 'message' => "成功删除 {$deletedCount} 条记录", 'deleted' => $deletedCount]);
    exit;
}

if ($action === 'update' && isset($_GET['id'])) {
    if (!$canManage) {
        echo json_encode(['success' => false, 'message' => '无权限编辑媒体文件']);
        exit;
    }
    
    $id = (int)$_GET['id'];
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => '请求方法错误']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $data = [];
    if (isset($input['alt_text'])) $data['alt_text'] = $input['alt_text'] ?: null;
    if (isset($input['title'])) $data['title'] = $input['title'] ?: null;
    if (isset($input['caption'])) $data['caption'] = $input['caption'] ?: null;
    if (isset($input['description'])) $data['description'] = $input['description'] ?: null;
    
    if (empty($data)) {
        echo json_encode(['success' => false, 'message' => '没有要更新的数据']);
        exit;
    }
    
    $db->update('`lty-media_library`', $data, 'id = ?', [$id]);
    OperationLog::update('media', '更新媒体文件信息 ID: ' . $id, 'media', $id, $data);
    echo json_encode(['success' => true, 'message' => '更新成功']);
    exit;
}

if ($action === 'stats') {
    if (!$canView) {
        echo json_encode(['success' => false, 'message' => '无权限查看媒体库']);
        exit;
    }
    
    $stats = [
        'total' => $db->fetchOne("SELECT COUNT(*) as count FROM `lty-media_library` WHERE status = 1")['count'],
        'image' => $db->fetchOne("SELECT COUNT(*) as count FROM `lty-media_library` WHERE file_type = 'image' AND status = 1")['count'],
        'video' => $db->fetchOne("SELECT COUNT(*) as count FROM `lty-media_library` WHERE file_type = 'video' AND status = 1")['count'],
        'document' => $db->fetchOne("SELECT COUNT(*) as count FROM `lty-media_library` WHERE file_type = 'document' AND status = 1")['count'],
        'total_size' => $db->fetchOne("SELECT SUM(file_size) as size FROM `lty-media_library` WHERE status = 1")['size'] ?: 0
    ];
    
    echo json_encode(['success' => true, 'data' => $stats]);
    exit;
}

echo json_encode(['success' => false, 'message' => '未知的操作']);

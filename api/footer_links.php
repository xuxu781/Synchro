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
require_once __DIR__ . '/../includes/api_security.php';

APISecurity::setSecurityHeaders();
APISecurity::checkRateLimit();

header('Content-Type: application/json');

$db = Database::getInstance();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        try {
            $links = $db->fetchAll("SELECT * FROM `lty-footer_links` ORDER BY sort_order ASC, id ASC");
            json_response(['success' => true, 'data' => $links]);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => '获取列表失败: ' . $e->getMessage()], 500);
        }
        break;
        
    case 'get':
        $id = intval($_GET['id'] ?? 0);
        if (!$id) {
            json_response(['success' => false, 'message' => 'ID参数错误'], 400);
        }
        
        try {
            $link = $db->fetchOne("SELECT * FROM `lty-footer_links` WHERE id = ?", [$id]);
            if (!$link) {
                json_response(['success' => false, 'message' => '链接不存在'], 404);
            }
            json_response(['success' => true, 'data' => $link]);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => '获取失败: ' . $e->getMessage()], 500);
        }
        break;
        
    case 'create':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['success' => false, 'message' => 'Method not allowed'], 405);
        }
        
        require_auth();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $title = trim($data['title'] ?? '');
        $url = trim($data['url'] ?? '');
        $target = trim($data['target'] ?? '_self');
        $status = isset($data['status']) ? intval($data['status']) : 1;
        
        if (empty($title)) {
            json_response(['success' => false, 'message' => '链接标题不能为空'], 400);
        }
        
        if (empty($url)) {
            json_response(['success' => false, 'message' => '链接URL不能为空'], 400);
        }
        
        if (!in_array($target, ['_self', '_blank'])) {
            $target = '_self';
        }
        
        try {
            $maxOrder = $db->fetchOne("SELECT MAX(sort_order) as max_order FROM `lty-footer_links`");
            $sortOrder = ($maxOrder['max_order'] ?? 0) + 1;
            
            $id = $db->insert('`lty-footer_links`', [
                'title' => $title,
                'url' => $url,
                'target' => $target,
                'sort_order' => $sortOrder,
                'status' => $status
            ]);
            
            json_response(['success' => true, 'message' => '添加成功', 'id' => $id]);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => '添加失败: ' . $e->getMessage()], 500);
        }
        break;
        
    case 'update':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['success' => false, 'message' => 'Method not allowed'], 405);
        }
        
        require_auth();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $id = intval($data['id'] ?? 0);
        if (!$id) {
            json_response(['success' => false, 'message' => 'ID参数错误'], 400);
        }
        
        $title = trim($data['title'] ?? '');
        $url = trim($data['url'] ?? '');
        $target = trim($data['target'] ?? '_self');
        $status = isset($data['status']) ? intval($data['status']) : 1;
        
        if (empty($title)) {
            json_response(['success' => false, 'message' => '链接标题不能为空'], 400);
        }
        
        if (empty($url)) {
            json_response(['success' => false, 'message' => '链接URL不能为空'], 400);
        }
        
        if (!in_array($target, ['_self', '_blank'])) {
            $target = '_self';
        }
        
        try {
            $existing = $db->fetchOne("SELECT id FROM `lty-footer_links` WHERE id = ?", [$id]);
            if (!$existing) {
                json_response(['success' => false, 'message' => '链接不存在'], 404);
            }
            
            $db->update('`lty-footer_links`', [
                'title' => $title,
                'url' => $url,
                'target' => $target,
                'status' => $status
            ], 'id = ?', [$id]);
            
            json_response(['success' => true, 'message' => '更新成功']);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => '更新失败: ' . $e->getMessage()], 500);
        }
        break;
        
    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['success' => false, 'message' => 'Method not allowed'], 405);
        }
        
        require_auth();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $id = intval($data['id'] ?? 0);
        if (!$id) {
            json_response(['success' => false, 'message' => 'ID参数错误'], 400);
        }
        
        try {
            $existing = $db->fetchOne("SELECT id FROM `lty-footer_links` WHERE id = ?", [$id]);
            if (!$existing) {
                json_response(['success' => false, 'message' => '链接不存在'], 404);
            }
            
            $db->delete('`lty-footer_links`', 'id = ?', [$id]);
            
            json_response(['success' => true, 'message' => '删除成功']);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => '删除失败: ' . $e->getMessage()], 500);
        }
        break;
        
    case 'update_order':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['success' => false, 'message' => 'Method not allowed'], 405);
        }
        
        require_auth();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['ids']) || !is_array($data['ids'])) {
            json_response(['success' => false, 'message' => '数据格式错误'], 400);
        }
        
        try {
            foreach ($data['ids'] as $index => $id) {
                $db->update('`lty-footer_links`', ['sort_order' => $index + 1], 'id = ?', [intval($id)]);
            }
            
            json_response(['success' => true, 'message' => '排序更新成功']);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => '更新排序失败: ' . $e->getMessage()], 500);
        }
        break;
        
    case 'toggle_status':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['success' => false, 'message' => 'Method not allowed'], 405);
        }
        
        require_auth();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $id = intval($data['id'] ?? 0);
        if (!$id) {
            json_response(['success' => false, 'message' => 'ID参数错误'], 400);
        }
        
        try {
            $existing = $db->fetchOne("SELECT status FROM `lty-footer_links` WHERE id = ?", [$id]);
            if (!$existing) {
                json_response(['success' => false, 'message' => '链接不存在'], 404);
            }
            
            $newStatus = $existing['status'] ? 0 : 1;
            $db->update('`lty-footer_links`', ['status' => $newStatus], 'id = ?', [$id]);
            
            json_response(['success' => true, 'message' => '状态更新成功', 'status' => $newStatus]);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => '更新状态失败: ' . $e->getMessage()], 500);
        }
        break;
        
    default:
        try {
            $links = $db->fetchAll("SELECT id, title, url, target, sort_order FROM `lty-footer_links` WHERE status = 1 ORDER BY sort_order ASC, id ASC");
            json_response(['success' => true, 'data' => $links]);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => '获取数据失败'], 500);
        }
}

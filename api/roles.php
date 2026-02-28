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
    case 'roles':
        try {
            $roles = $db->fetchAll("SELECT id, name, slug, description, sort_order, status FROM `lty-roles` WHERE status = 1 ORDER BY sort_order ASC, id ASC");
            json_response(['success' => true, 'data' => $roles]);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => '获取列表失败: ' . $e->getMessage()], 500);
        }
        break;
        
    case 'update_sort':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['success' => false, 'message' => 'Method not allowed'], 405);
        }
        
        require_auth();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['data']) || !is_array($data['data'])) {
            json_response(['success' => false, 'message' => '数据格式错误'], 400);
        }
        
        try {
            foreach ($data['data'] as $item) {
                $db->update('`lty-roles`', ['sort_order' => intval($item['sort_order'])], 'id = ?', [intval($item['id'])]);
            }
            
            json_response(['success' => true, 'message' => '排序更新成功']);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => '更新排序失败: ' . $e->getMessage()], 500);
        }
        break;
        
    default:
        json_response(['success' => false, 'message' => 'Invalid action'], 400);
}

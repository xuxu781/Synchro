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

$action = $_POST['action'] ?? $_GET['action'] ?? '';

$publicActions = ['roles'];
$authRequiredActions = ['update_sort'];

if (in_array($action, $authRequiredActions)) {
    require_auth();
}

switch ($action) {
    case 'roles':
        try {
            $db = Database::getInstance();
            $roles = $db->fetchAll("SELECT id, name, slug, description, sort_order, status FROM `lty-roles` WHERE status = 1 ORDER BY sort_order ASC, id ASC");
            json_response(['success' => true, 'data' => $roles]);
        } catch (Exception $e) {
            json_response(['error' => 'Failed to fetch roles'], 500);
        }
        break;
        
    case 'update_sort':
        $input = json_decode(file_get_contents('php://input'), true);
        $data = $input['data'] ?? [];
        
        if (empty($data)) {
            json_response(['error' => 'Invalid parameters'], 400);
        }
        
        try {
            $db = Database::getInstance();
            $db->beginTransaction();
            
            foreach ($data as $item) {
                $db->update('`lty-roles`', ['sort_order' => $item['sort_order']], 'id = ?', [$item['id']]);
            }
            
            $db->commit();
            json_response(['success' => true, 'message' => '排序更新成功']);
        } catch (Exception $e) {
            $db->rollBack();
            json_response(['error' => 'Failed to update sort order'], 500);
        }
        break;
        
    default:
        json_response(['error' => 'Invalid action'], 400);
}

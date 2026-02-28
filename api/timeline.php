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

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_POST['action'] ?? $_GET['action'] ?? '';

$publicActions = ['list', 'get'];
$authRequiredActions = ['update_sort'];

if (in_array($action, $authRequiredActions)) {
    require_auth();
}

switch ($action) {
    case 'list':
        try {
            $db = Database::getInstance();
            $timelines = $db->fetchAll("SELECT * FROM `lty-company_timeline` WHERE status = 1 ORDER BY sort_order ASC, id ASC");
            
            json_response(['success' => true, 'data' => $timelines]);
        } catch (Exception $e) {
            json_response(['error' => 'Failed to fetch timelines'], 500);
        }
        break;
        
    case 'get':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($id <= 0) {
            json_response(['error' => 'Invalid timeline ID'], 400);
        }
        
        try {
            $db = Database::getInstance();
            $timeline = $db->fetchOne("SELECT * FROM `lty-company_timeline` WHERE id = ?", [$id]);
            
            if (!$timeline) {
                json_response(['error' => 'Timeline not found'], 404);
            }
            
            json_response(['success' => true, 'data' => $timeline]);
        } catch (Exception $e) {
            json_response(['error' => 'Failed to fetch timeline'], 500);
        }
        break;
        
    case 'update_sort':
        $data = $input['data'] ?? [];
        
        if (empty($data)) {
            json_response(['error' => 'Invalid parameters'], 400);
        }
        
        try {
            $db = Database::getInstance();
            $db->beginTransaction();
            
            foreach ($data as $item) {
                $db->update('`lty-company_timeline`', ['sort_order' => $item['sort_order']], 'id = ?', [$item['id']]);
            }
            
            $db->commit();
            json_response(['success' => true]);
        } catch (Exception $e) {
            $db->rollback();
            json_response(['error' => 'Failed to reorder timelines'], 500);
        }
        break;
        
    default:
        json_response(['error' => 'Invalid action'], 400);
}

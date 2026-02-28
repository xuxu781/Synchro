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

header('Content-Type: application/json; charset=utf-8');

if (!Auth::check()) {
    json_response(['error' => '未授权访问'], 401);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            json_response(['error' => '无效的日志ID'], 400);
        }
        
        $db = Database::getInstance();
        $log = $db->fetchOne("SELECT * FROM `lty-operation_logs` WHERE id = ?", [$id]);
        
        if (!$log) {
            json_response(['error' => '日志不存在'], 404);
        }
        
        if ($log['details']) {
            $log['details_json'] = json_decode($log['details'], true);
        }
        
        json_response(['success' => true, 'data' => $log]);
        break;
        
    case 'stats':
        $db = Database::getInstance();
        
        $todayCount = $db->fetchOne("SELECT COUNT(*) as count FROM `lty-operation_logs` WHERE DATE(created_at) = CURDATE()")['count'];
        $weekCount = $db->fetchOne("SELECT COUNT(*) as count FROM `lty-operation_logs` WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['count'];
        $monthCount = $db->fetchOne("SELECT COUNT(*) as count FROM `lty-operation_logs` WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['count'];
        $totalCount = $db->fetchOne("SELECT COUNT(*) as count FROM `lty-operation_logs`")['count'];
        
        $actionStats = $db->fetchAll(
            "SELECT action, COUNT(*) as count FROM `lty-operation_logs` 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
             GROUP BY action ORDER BY count DESC"
        );
        
        $moduleStats = $db->fetchAll(
            "SELECT module, COUNT(*) as count FROM `lty-operation_logs` 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND module IS NOT NULL
             GROUP BY module ORDER BY count DESC LIMIT 10"
        );
        
        json_response([
            'success' => true,
            'data' => [
                'today' => $todayCount,
                'week' => $weekCount,
                'month' => $monthCount,
                'total' => $totalCount,
                'by_action' => $actionStats,
                'by_module' => $moduleStats
            ]
        ]);
        break;
        
    case 'clean':
        if (!Auth::isSuperAdmin()) {
            json_response(['error' => '权限不足'], 403);
        }
        
        $days = (int)($_POST['days'] ?? 30);
        if ($days < 7) {
            json_response(['error' => '保留天数不能少于7天'], 400);
        }
        
        $db = Database::getInstance();
        $result = $db->query(
            "DELETE FROM `lty-operation_logs` WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$days]
        );
        
        $deletedCount = $result->rowCount();
        
        OperationLog::delete('logs', "清理{$days}天前的操作日志，删除{$deletedCount}条记录", 'operation_logs', null, ['days' => $days, 'deleted' => $deletedCount]);
        
        json_response(['success' => true, 'deleted' => $deletedCount]);
        break;
        
    default:
        json_response(['error' => '未知操作'], 400);
}

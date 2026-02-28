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

require_auth();

header('Content-Type: application/json');

$db = Database::getInstance();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        $members = $db->fetchAll("SELECT * FROM `lty-team_members` ORDER BY sort_order ASC, id ASC");
        json_response(['success' => true, 'data' => $members]);
        break;
        
    case 'get':
        $id = $_GET['id'] ?? '';
        if (!$id) {
            json_response(['error' => 'ID required'], 400);
        }
        
        $member = $db->fetchOne("SELECT * FROM `lty-team_members` WHERE id = ?", [$id]);
        if (!$member) {
            json_response(['error' => 'Member not found'], 404);
        }
        
        json_response(['success' => true, 'data' => $member]);
        break;
        
    case 'create':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['error' => 'Method not allowed'], 405);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['name']) || empty($data['position'])) {
            json_response(['error' => 'Name and position are required'], 400);
        }
        
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            json_response(['error' => '请输入正确的邮箱格式'], 400);
        }
        
        if (!empty($data['phone']) && !preg_match('/^1[3-9]\d{9}$/', $data['phone'])) {
            json_response(['error' => '请输入正确的手机号格式（11位数字，以1开头）'], 400);
        }
        
        if (!empty($data['email'])) {
            $checkEmail = $db->fetchOne("SELECT id FROM `lty-team_members` WHERE email = ?", [$data['email']]);
            if ($checkEmail) {
                json_response(['error' => '邮箱已存在，请使用其他邮箱'], 400);
            }
        }
        
        $id = $db->insert('`lty-team_members`', [
            'name' => $data['name'],
            'position' => $data['position'],
            'bio' => $data['bio'] ?? '',
            'avatar' => $data['avatar'] ?? '',
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? '',
            'sort_order' => $data['sort_order'] ?? 0,
            'status' => $data['status'] ?? 1
        ]);
        
        json_response(['success' => true, 'id' => $id]);
        break;
        
    case 'update':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['error' => 'Method not allowed'], 405);
        }
        
        $id = $_GET['id'] ?? '';
        if (!$id) {
            json_response(['error' => 'ID required'], 400);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['name']) || empty($data['position'])) {
            json_response(['error' => 'Name and position are required'], 400);
        }
        
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            json_response(['error' => '请输入正确的邮箱格式'], 400);
        }
        
        if (!empty($data['phone']) && !preg_match('/^1[3-9]\d{9}$/', $data['phone'])) {
            json_response(['error' => '请输入正确的手机号格式（11位数字，以1开头）'], 400);
        }
        
        if (!empty($data['email'])) {
            $checkEmail = $db->fetchOne("SELECT id FROM `lty-team_members` WHERE email = ? AND id != ?", [$data['email'], $id]);
            if ($checkEmail) {
                json_response(['error' => '邮箱已存在，请使用其他邮箱'], 400);
            }
        }
        
        $db->update('`lty-team_members`', [
            'name' => $data['name'],
            'position' => $data['position'],
            'bio' => $data['bio'] ?? '',
            'avatar' => $data['avatar'] ?? '',
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? '',
            'sort_order' => $data['sort_order'] ?? 0,
            'status' => $data['status'] ?? 1
        ], 'id = ?', [$id]);
        
        json_response(['success' => true]);
        break;
        
    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['error' => 'Method not allowed'], 405);
        }
        
        $id = $_GET['id'] ?? '';
        if (!$id) {
            json_response(['error' => 'ID required'], 400);
        }
        
        $requirePassword = Settings::get('require_password_on_delete', '1');
        if ($requirePassword === '1') {
            $data = json_decode(file_get_contents('php://input'), true);
            $password = $data['password'] ?? '';
            
            if (empty($password)) {
                json_response(['error' => '删除成员需要验证密码'], 400);
            }
            
            require_password_verification($password);
        }
        
        $db->delete('`lty-team_members`', 'id = ?', [$id]);
        
        APISecurity::logSecurityEvent('member_deleted', [
            'member_id' => $id,
            'user_id' => $_SESSION['admin_id']
        ]);
        
        json_response(['success' => true]);
        break;
        
    default:
        json_response(['error' => 'Invalid action'], 400);
}
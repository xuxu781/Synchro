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

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST;
    }
    
    $action = $input['action'] ?? $_GET['action'] ?? '';
    
    if ($action !== 'verify') {
        echo json_encode([
            'success' => false,
            'message' => '无效的操作'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $captcha_code = strtoupper(trim($input['captcha_code'] ?? $_GET['captcha_code'] ?? ''));
    $session_captcha = strtoupper($_SESSION['captcha_code'] ?? '');
    $captcha_time = $_SESSION['captcha_time'] ?? 0;
    
    if (empty($captcha_code)) {
        echo json_encode([
            'success' => false,
            'captcha_error' => true,
            'message' => '请输入验证码'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if (empty($session_captcha) || time() - $captcha_time > 300) {
        echo json_encode([
            'success' => false,
            'captcha_error' => true,
            'message' => '验证码已过期，请刷新验证码'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if ($captcha_code !== $session_captcha) {
        echo json_encode([
            'success' => false,
            'captcha_error' => true,
            'message' => '验证码错误'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    unset($_SESSION['captcha_code']);
    unset($_SESSION['captcha_time']);
    
    $query_value = trim($input['query_value'] ?? $_GET['query_value'] ?? '');
    
    if (empty($query_value)) {
        echo json_encode([
            'success' => false,
            'message' => '请输入查询信息'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $db = Database::getInstance();
    $member = null;
    
    $phoneRegex = '/^1[3-9]\d{9}$/';
    $emailRegex = '/^[^\s@]+@[^\s@]+\.[^\s@]+$/';
    
    if (preg_match($phoneRegex, $query_value)) {
        $member = $db->fetchOne(
            "SELECT * FROM `lty-team_members` WHERE phone = ?",
            [$query_value]
        );
    } elseif (preg_match($emailRegex, $query_value)) {
        $member = $db->fetchOne(
            "SELECT * FROM `lty-team_members` WHERE email = ?",
            [$query_value]
        );
    } else {
        $member = $db->fetchOne(
            "SELECT * FROM `lty-team_members` WHERE name = ? OR employee_id = ?",
            [$query_value, $query_value]
        );
    }
    
    if ($member) {
        $avatar = get_avatar_url($member['avatar'], $member['email'], 100);
        
        $statusText = '未知';
        $statusClass = '';
        switch ($member['status']) {
            case 0:
                $statusText = '隐藏';
                $statusClass = 'hidden';
                break;
            case 1:
                $statusText = '在职';
                $statusClass = 'active';
                break;
            case 2:
                $statusText = '离职';
                $statusClass = 'resigned';
                break;
        }
        
        echo json_encode([
            'success' => true,
            'message' => '查询成功',
            'data' => [
                'id' => $member['id'],
                'name' => $member['name'],
                'position' => $member['position'],
                'bio' => $member['bio'] ?? '',
                'avatar' => $avatar,
                'email' => $member['email'] ?? '',
                'phone' => $member['phone'] ?? '',
                'employee_id' => $member['employee_id'] ?? '',
                'status' => $member['status'],
                'status_text' => $statusText,
                'status_class' => $statusClass,
                'created_at' => $member['created_at']
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $query_value
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '查询失败：' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

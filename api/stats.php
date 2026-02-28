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
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../includes/functions.php';

$db = Database::getInstance();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'track':
        trackPageView();
        break;
    case 'external_link':
        logExternalLink();
        break;
    default:
        echo json_encode(['success' => false, 'message' => '无效的操作']);
        break;
}

function trackPageView() {
    global $db;
    
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }
    
    $pageUrl = trim($data['page_url'] ?? '');
    $pageTitle = trim($data['page_title'] ?? '');
    
    if (empty($pageUrl)) {
        echo json_encode(['success' => false, 'message' => '页面URL不能为空']);
        return;
    }
    
    if (strpos($pageUrl, '://') !== false) {
        $parsed = parse_url($pageUrl);
        $pageUrl = ($parsed['path'] ?? '/') . (isset($parsed['query']) ? '?' . $parsed['query'] : '');
    }
    
    $pageUrl = substr($pageUrl, 0, 500);
    $pageTitle = substr($pageTitle, 0, 255);
    $today = date('Y-m-d');
    
    $existing = $db->fetchOne(
        "SELECT id, view_count FROM `lty-page_views` WHERE page_url = ? AND view_date = ?",
        [$pageUrl, $today]
    );
    
    if ($existing) {
        $db->update(
            '`lty-page_views`',
            ['view_count' => $existing['view_count'] + 1],
            'id = ?',
            [$existing['id']]
        );
    } else {
        $db->insert('`lty-page_views`', [
            'page_url' => $pageUrl,
            'page_title' => $pageTitle,
            'view_date' => $today,
            'view_count' => 1,
            'unique_count' => 1
        ]);
    }
    
    echo json_encode(['success' => true]);
}

function logExternalLink() {
    global $db;
    
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }
    
    $linkUrl = trim($data['link_url'] ?? '');
    $sourcePage = trim($data['source_page'] ?? '');
    
    if (empty($linkUrl)) {
        echo json_encode(['success' => false, 'message' => '链接URL不能为空']);
        return;
    }
    
    $db->insert('`lty-external_link_logs`', [
        'link_url' => substr($linkUrl, 0, 500),
        'source_page' => substr($sourcePage, 0, 500),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500)
    ]);
    
    echo json_encode(['success' => true]);
}

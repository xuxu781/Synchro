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

if (!Auth::check()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '未登录']);
    exit;
}

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '无效的ID']);
    exit;
}

$db = Database::getInstance();
$social = $db->fetchOne("SELECT * FROM `lty-social_links` WHERE id = ?", [$id]);

if ($social) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'social' => $social]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '未找到数据']);
}

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
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../includes/functions.php';

try {
    $db = Database::getInstance();
    $links = $db->fetchAll("SELECT id, platform, url, icon_type, icon_url, svg_code, sort_order FROM `lty-social_links` WHERE status = 1 ORDER BY sort_order ASC, id ASC");
    
    $result = array_map(function($link) {
        return [
            'id' => (int)$link['id'],
            'platform' => $link['platform'],
            'url' => $link['url'],
            'icon_type' => $link['icon_type'],
            'icon_url' => $link['icon_url'],
            'svg_code' => $link['svg_code'],
            'sort_order' => (int)$link['sort_order']
        ];
    }, $links);
    
    echo json_encode([
        'success' => true,
        'data' => $result
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '获取数据失败'
    ]);
}

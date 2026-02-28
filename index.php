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
require_once __DIR__ . '/includes/functions.php';

$page = $_GET['page'] ?? 'home';
$page = trim($page, '/');

switch ($page) {
    case 'team':
        include __DIR__ . '/team.php';
        break;
    case 'contact':
        include __DIR__ . '/contact.php';
        break;
    case 'project':
        include __DIR__ . '/project.php';
        break;
    default:
        include __DIR__ . '/home.php';
}
?>
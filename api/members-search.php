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

header('Content-Type: application/json');

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    $db = Database::getInstance();
    
    if ($search) {
        $searchParam = '%' . $search . '%';
        $members = $db->fetchAll(
            "SELECT * FROM `lty-team_members` WHERE name LIKE ? OR position LIKE ? OR employee_id LIKE ? OR email LIKE ? OR phone LIKE ? ORDER BY sort_order ASC, id ASC",
            [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam]
        );
    } else {
        $members = $db->fetchAll("SELECT * FROM `lty-team_members` ORDER BY sort_order ASC, id ASC");
    }
    
    $html = '';
    foreach ($members as $member) {
        $memberAvatar = get_avatar_url($member['avatar'], $member['email'], 64);
        $placeholderHtml = '<div class="avatar-preview" style="display: flex; align-items: center; justify-content: center; font-size: 20px; color: #fff;">👤</div>';
        $avatarHtml = $memberAvatar 
            ? '<img src="' . htmlspecialchars($memberAvatar) . '" alt="' . htmlspecialchars($member['name']) . '" class="avatar-preview" onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'flex\';"><div class="avatar-preview" style="display: none; align-items: center; justify-content: center; font-size: 20px; color: #fff;">👤</div>'
            : $placeholderHtml;
        
        $statusBadge = '';
        if ($member['status'] == 1) {
            $statusBadge = '<span class="badge badge-success">在职</span>';
        } elseif ($member['status'] == 2) {
            $statusBadge = '<span class="badge badge-warning">离职</span>';
        } else {
            $statusBadge = '<span class="badge badge-secondary">隐藏</span>';
        }
        
        $html .= '<tr>';
        $html .= '<td>' . $avatarHtml . '</td>';
        $html .= '<td>' . htmlspecialchars($member['name']) . '</td>';
        $html .= '<td>' . htmlspecialchars($member['position']) . '</td>';
        $html .= '<td>' . htmlspecialchars($member['employee_id'] ?: '-') . '</td>';
        $html .= '<td>' . htmlspecialchars($member['email'] ?: '-') . '</td>';
        $html .= '<td>' . $member['sort_order'] . '</td>';
        $html .= '<td>' . $statusBadge . '</td>';
        $html .= '<td><div class="actions"><a href="members.php?action=edit&id=' . $member['id'] . '" class="btn btn-sm btn-outline">编辑</a> <a href="members.php?action=delete&id=' . $member['id'] . '" class="btn btn-sm btn-danger" onclick="return confirm(\'确定要删除吗？\');">删除</a></div></td>';
        $html .= '</tr>';
    }
    
    if (empty($members)) {
        $html = '<tr><td colspan="8" style="text-align: center; padding: 40px; color: #999;">暂无数据</td></tr>';
    }
    
    echo json_encode(['success' => true, 'html' => $html]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

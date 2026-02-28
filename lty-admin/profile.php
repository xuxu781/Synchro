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

if (!Auth::check()) {
    redirect('lty-admin/');
}

if (!Auth::hasRole()) {
    set_flash_message('error', '您的账户未分配角色，请联系管理员');
    redirect('lty-admin/index.php');
}

$user = Auth::user();
$displayName = !empty($user['name']) ? $user['name'] : $user['username'];
$avatarUrl = get_avatar_url($user['avatar'], $user['email'], 100);
$initials = '';
$nameParts = explode(' ', $displayName);
foreach ($nameParts as $part) {
    if (isset($part[0])) {
        $initials .= mb_strtoupper($part[0]);
    }
}
if (strlen($initials) > 2) {
    $initials = substr($initials, 0, 2);
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>个人资料 - <?php echo htmlspecialchars(Settings::get('site_name', '官网系统')); ?></title>
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="components/media-picker.css">
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>个人资料</h1>
        </div>

        <div class="profile-page-wrapper">
            <div class="profile-hero">
                <div class="profile-hero-bg">
                    <div class="hero-shape hero-shape-1"></div>
                    <div class="hero-shape hero-shape-2"></div>
                    <div class="hero-shape hero-shape-3"></div>
                </div>
                <div class="profile-hero-content">
                    <div class="profile-avatar-section">
                        <div class="profile-avatar-ring" onclick="openProfileModal()" style="cursor: pointer;" title="点击更换头像">
                            <div class="profile-avatar-main" id="profileAvatarDisplay">
                                <?php if ($avatarUrl): ?>
                                    <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="头像" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                    <span class="avatar-initials" style="display:none;"><?php echo htmlspecialchars($initials); ?></span>
                                <?php else: ?>
                                    <span class="avatar-initials"><?php echo htmlspecialchars($initials); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="avatar-hover-overlay">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <circle cx="12" cy="13" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span>更换头像</span>
                            </div>
                        </div>
                        <button class="avatar-edit-btn" onclick="openProfileModal()" title="更换头像">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="12" cy="13" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                    <div class="profile-hero-info">
                        <h1 class="profile-hero-name"><?php echo htmlspecialchars($displayName); ?></h1>
                        <p class="profile-hero-role">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            系统管理员
                        </p>
                        <p class="profile-hero-email">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M22 6l-10 7L2 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <?php echo htmlspecialchars($user['email']); ?>
                        </p>
                    </div>
                    <div class="profile-hero-actions">
                        <button class="btn btn-hero-primary" onclick="openProfileModal()">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            编辑资料
                        </button>
                        <button class="btn btn-hero-secondary" onclick="openPasswordModal()">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M7 11V7a5 5 0 0110 0v4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            修改密码
                        </button>
                    </div>
                </div>
            </div>

            <div class="profile-grid">
                <div class="profile-main">
                    <div class="profile-section">
                        <div class="section-header">
                            <h2>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                账户信息
                            </h2>
                        </div>
                        <div class="info-cards">
                            <div class="info-card">
                                <div class="info-card-icon blue">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div class="info-card-content">
                                    <span class="info-card-label">用户名</span>
                                    <span class="info-card-value"><?php echo htmlspecialchars($user['username']); ?></span>
                                </div>
                            </div>
                            <div class="info-card">
                                <div class="info-card-icon green">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div class="info-card-content">
                                    <span class="info-card-label">显示名称</span>
                                    <span class="info-card-value"><?php echo !empty($user['name']) ? htmlspecialchars($user['name']) : '-'; ?></span>
                                </div>
                            </div>
                            <div class="info-card">
                                <div class="info-card-icon orange">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M22 6l-10 7L2 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div class="info-card-content">
                                    <span class="info-card-label">电子邮箱</span>
                                    <span class="info-card-value"><?php echo htmlspecialchars($user['email']); ?></span>
                                </div>
                            </div>
                            <div class="info-card">
                                <div class="info-card-icon purple">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M12 6v6l4 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div class="info-card-content">
                                    <span class="info-card-label">注册时间</span>
                                    <span class="info-card-value"><?php echo isset($user['created_at']) ? date('Y-m-d', strtotime($user['created_at'])) : '-'; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="profile-section">
                        <div class="section-header">
                            <h2>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                安全状态
                            </h2>
                        </div>
                        <div class="security-grid">
                            <div class="security-card">
                                <div class="security-icon success">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M22 11.08V12a10 10 0 11-5.93-9.14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M22 4L12 14.01l-3-3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div class="security-content">
                                    <h4>账户安全</h4>
                                    <p>您的账户处于安全状态</p>
                                </div>
                                <span class="security-badge success">良好</span>
                            </div>
                            <div class="security-card">
                                <div class="security-icon warning">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M7 11V7a5 5 0 0110 0v4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div class="security-content">
                                    <h4>密码强度</h4>
                                    <p>建议定期更换密码</p>
                                </div>
                                <span class="security-badge warning">中等</span>
                            </div>
                            <div class="security-card">
                                <div class="security-icon info">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div class="security-content">
                                    <h4>登录保护</h4>
                                    <p>IP绑定已启用</p>
                                </div>
                                <span class="security-badge info">已启用</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="profile-sidebar">
                    <div class="quick-actions-card">
                        <div class="card-title-bar">
                            <h3>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                快捷操作
                            </h3>
                        </div>
                        <div class="quick-actions-list">
                            <a href="settings.php" class="quick-action-item">
                                <div class="quick-action-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div class="quick-action-content">
                                    <span class="quick-action-title">系统设置</span>
                                    <span class="quick-action-desc">配置网站参数</span>
                                </div>
                                <svg class="quick-action-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </a>
                            <a href="media.php" class="quick-action-item">
                                <div class="quick-action-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <circle cx="8.5" cy="8.5" r="1.5" fill="currentColor"/>
                                        <path d="M21 15l-5-5L5 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div class="quick-action-content">
                                    <span class="quick-action-title">媒体库</span>
                                    <span class="quick-action-desc">管理文件资源</span>
                                </div>
                                <svg class="quick-action-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </a>
                            <a href="messages.php" class="quick-action-item">
                                <div class="quick-action-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div class="quick-action-content">
                                    <span class="quick-action-title">消息中心</span>
                                    <span class="quick-action-desc">查看用户留言</span>
                                </div>
                                <svg class="quick-action-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </a>
                            <a href="members.php" class="quick-action-item">
                                <div class="quick-action-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M23 21v-2a4 4 0 00-3-3.87" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M16 3.13a4 4 0 010 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div class="quick-action-content">
                                    <span class="quick-action-title">团队成员</span>
                                    <span class="quick-action-desc">管理成员信息</span>
                                </div>
                                <svg class="quick-action-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </a>
                        </div>
                    </div>

                    <div class="tips-card">
                        <div class="tips-icon">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M12 16v-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M12 8h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h3>安全提示</h3>
                        <ul>
                            <li>定期更换密码以提高账户安全性</li>
                            <li>不要在公共设备上保存登录状态</li>
                            <li>如发现异常登录请及时修改密码</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="main-footer">
            <div class="footer-content">
                <span class="footer-line">© 2026 <?php echo htmlspecialchars(Settings::get('site_name', '官网系统')); ?> 1.0</span>
                <span class="footer-line">徐州邻兔跃IT工作室提供技术支持版权所有</span>
            </div>
        </div>
    </div>

    <div class="modal" id="profileModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>编辑个人资料</h3>
                <button class="modal-close" onclick="closeProfileModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="profileForm">
                    <div class="form-group">
                        <label for="name">名称</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" placeholder="请输入您的名称">
                    </div>
                    <div class="form-group">
                        <label for="email">邮箱 *</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="avatar">头像</label>
                        <div class="avatar-upload-container">
                            <div class="avatar-preview-wrapper">
                                <div class="avatar-preview" id="avatarPreview">
                                    <?php if ($avatarUrl): ?>
                                        <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="头像预览">
                                        <button type="button" class="avatar-remove-btn" onclick="removeAvatarPreview()" title="移除头像">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>
                                    <?php else: ?>
                                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="avatar-upload-actions">
                                <input type="hidden" id="avatar_url" name="avatar_url" value="">
                                <input type="file" id="avatar_file" accept="image/jpeg,image/png,image/gif,image/webp" style="display: none;" onchange="handleAvatarFileSelect(this)">
                                <div class="avatar-upload-buttons">
                                    <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('avatar_file').click()">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M17 8l-5-5-5 5M12 3v12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        上传图片
                                    </button>
                                    <button type="button" class="btn btn-outline btn-sm" onclick="openMediaLibraryForAvatar()">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        媒体库
                                    </button>
                                </div>
                                <p class="avatar-upload-hint">支持 JPG、PNG、GIF、WebP，最大 2MB</p>
                            </div>
                        </div>
                    </div>
                    <div id="profileError" class="alert alert-error" style="display: none;"></div>
                    <div id="profileSuccess" class="alert alert-success" style="display: none;"></div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="profileSubmitBtn">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            保存修改
                        </button>
                        <button type="button" class="btn btn-outline" onclick="closeProfileModal()">取消</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal" id="passwordModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>修改密码</h3>
                <button class="modal-close" onclick="closePasswordModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="passwordForm">
                    <div class="form-group">
                        <label for="current_password">当前密码 *</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">新密码 *</label>
                        <input type="password" id="new_password" name="new_password" required minlength="6">
                        <small style="color: #64748b; font-size: 12px;">密码长度至少6位</small>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">确认新密码 *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                    <div id="passwordError" class="alert alert-error" style="display: none;"></div>
                    <div id="passwordSuccess" class="alert alert-success" style="display: none;"></div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="passwordSubmitBtn">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            修改密码
                        </button>
                        <button type="button" class="btn btn-outline" onclick="closePasswordModal()">取消</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .profile-page-wrapper {
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .profile-hero {
            position: relative;
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 50%, #d97706 100%);
            border-radius: 24px;
            padding: 56px 64px;
            margin-bottom: 32px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(245, 158, 11, 0.25);
        }

        .profile-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(255, 255, 255, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 40%);
            pointer-events: none;
        }

        .profile-hero-bg {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
        }

        .hero-shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(1px);
        }

        .hero-shape-1 {
            width: 300px;
            height: 300px;
            top: -100px;
            right: -50px;
            animation: float 6s ease-in-out infinite, pulse 4s ease-in-out infinite;
        }

        .hero-shape-2 {
            width: 200px;
            height: 200px;
            bottom: -80px;
            left: 10%;
            animation: float 8s ease-in-out infinite reverse, pulse 5s ease-in-out infinite;
        }

        .hero-shape-3 {
            width: 150px;
            height: 150px;
            top: 50%;
            right: 30%;
            animation: float 5s ease-in-out infinite, pulse 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
        }

        @keyframes pulse {
            0%, 100% { opacity: 0.08; transform: scale(1); }
            50% { opacity: 0.15; transform: scale(1.05); }
        }

        .profile-hero-content {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 32px;
        }

        .profile-avatar-section {
            position: relative;
            flex-shrink: 0;
        }

        .profile-avatar-ring {
            position: relative;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.4) 0%, rgba(255, 255, 255, 0.1) 100%);
            padding: 4px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            animation: ringPulse 3s ease-in-out infinite;
        }

        .avatar-hover-overlay {
            position: absolute;
            inset: 4px;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            opacity: 0;
            transition: opacity 0.3s ease;
            backdrop-filter: blur(2px);
        }

        .avatar-hover-overlay svg {
            margin-bottom: 4px;
        }

        .avatar-hover-overlay span {
            font-size: 12px;
            font-weight: 600;
        }

        .profile-avatar-ring:hover .avatar-hover-overlay {
            opacity: 1;
        }

        @keyframes ringPulse {
            0%, 100% { box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15), 0 0 0 0 rgba(255, 255, 255, 0.4); }
            50% { box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15), 0 0 0 8px rgba(255, 255, 255, 0.2); }
        }

        .profile-avatar-main {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.3) 0%, rgba(255, 255, 255, 0.1) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .profile-avatar-main img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar-initials {
            font-size: 36px;
            font-weight: 800;
            color: white;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .avatar-edit-btn {
            position: absolute;
            bottom: 0;
            right: -8px;
            width: 36px;
            height: 36px;
            background: white;
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f59e0b;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
        }

        .avatar-edit-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
        }

        .profile-hero-info {
            flex: 1;
        }

        .profile-hero-name {
            font-size: 32px;
            font-weight: 800;
            color: white;
            margin: 0 0 8px 0;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .profile-hero-role {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 16px;
            color: rgba(255, 255, 255, 0.9);
            margin: 0 0 8px 0;
        }

        .profile-hero-email {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.8);
            margin: 0;
        }

        .profile-hero-actions {
            display: flex;
            gap: 12px;
        }

        .btn-hero-primary {
            background: white;
            color: #f59e0b;
            border: none;
            padding: 14px 28px;
            font-weight: 700;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
            position: relative;
            overflow: hidden;
        }

        .btn-hero-primary::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(245, 158, 11, 0.1);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-hero-primary:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-hero-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
        }

        .btn-hero-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.4);
            padding: 12px 26px;
            backdrop-filter: blur(10px);
        }

        .btn-hero-secondary:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.6);
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 32px;
        }

        .profile-main {
            display: flex;
            flex-direction: column;
            gap: 32px;
        }

        .profile-section {
            background: var(--bg-card);
            border-radius: 20px;
            border: 1px solid var(--border-color);
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .section-header {
            padding: 24px 28px;
            border-bottom: 1px solid var(--border-color);
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.05) 0%, rgba(251, 191, 36, 0.03) 100%);
        }

        .section-header h2 {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
        }

        .section-header h2 svg {
            color: var(--primary-color);
        }

        .info-cards {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            padding: 24px;
        }

        .info-card {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 20px;
            background: var(--bg-tertiary);
            border-radius: 16px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .info-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 0;
            background: linear-gradient(180deg, #fbbf24 0%, #f59e0b 100%);
            transition: height 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.05) 0%, var(--bg-tertiary) 100%);
        }

        .info-card:hover::before {
            height: 100%;
        }

        .info-card-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }

        .info-card:hover .info-card-icon {
            transform: rotate(-5deg) scale(1.1);
        }

        .info-card-icon.blue {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #2563eb;
        }

        .info-card-icon.green {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #059669;
        }

        .info-card-icon.orange {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #d97706;
        }

        .info-card-icon.purple {
            background: linear-gradient(135deg, #e9d5ff 0%, #d8b4fe 100%);
            color: #9333ea;
        }

        .info-card-content {
            display: flex;
            flex-direction: column;
            gap: 4px;
            min-width: 0;
        }

        .info-card-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-card-value {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-primary);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .security-grid {
            display: flex;
            flex-direction: column;
            gap: 16px;
            padding: 24px;
        }

        .security-card {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 20px;
            background: var(--bg-tertiary);
            border-radius: 16px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .security-card::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 16px;
            padding: 2px;
            background: linear-gradient(135deg, transparent 0%, rgba(251, 191, 36, 0.3) 100%);
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .security-card:hover {
            background: rgba(251, 191, 36, 0.08);
            transform: translateX(8px);
        }

        .security-card:hover::after {
            opacity: 1;
        }

        .security-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .security-icon.success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #059669;
        }

        .security-icon.warning {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #d97706;
        }

        .security-icon.info {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #2563eb;
        }

        .security-content {
            flex: 1;
        }

        .security-content h4 {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 4px 0;
        }

        .security-content p {
            font-size: 13px;
            color: var(--text-secondary);
            margin: 0;
        }

        .security-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .security-badge.success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #059669;
        }

        .security-badge.warning {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #d97706;
        }

        .security-badge.info {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #2563eb;
        }

        .profile-sidebar {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .quick-actions-card {
            background: var(--bg-card);
            border-radius: 20px;
            border: 1px solid var(--border-color);
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .card-title-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.05) 0%, rgba(251, 191, 36, 0.03) 100%);
        }

        .card-title-bar h3 {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
        }

        .card-title-bar h3 svg {
            color: var(--primary-color);
        }

        .quick-actions-list {
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .quick-action-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px;
            background: var(--bg-tertiary);
            border-radius: 14px;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .quick-action-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: linear-gradient(180deg, #fbbf24 0%, #f59e0b 100%);
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }

        .quick-action-item:hover {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            transform: translateX(8px);
            box-shadow: 0 4px 16px rgba(245, 158, 11, 0.2);
        }

        .quick-action-item:hover::before {
            transform: scaleY(1);
        }

        .quick-action-icon {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            flex-shrink: 0;
        }

        .quick-action-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .quick-action-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-primary);
        }

        .quick-action-desc {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .quick-action-arrow {
            color: var(--text-muted);
            transition: transform 0.3s ease;
        }

        .quick-action-item:hover .quick-action-arrow {
            transform: translateX(4px);
            color: #b45309;
        }

        .tips-card {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-radius: 20px;
            padding: 28px;
            border: 1px solid rgba(245, 158, 11, 0.3);
            position: relative;
            overflow: hidden;
        }

        .tips-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.3) 0%, transparent 70%);
            animation: shimmer 3s ease-in-out infinite;
        }

        @keyframes shimmer {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(-10px, 10px) rotate(5deg); }
        }

        .tips-icon {
            width: 56px;
            height: 56px;
            background: white;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f59e0b;
            margin-bottom: 16px;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2);
        }

        .tips-card h3 {
            font-size: 18px;
            font-weight: 700;
            color: #92400e;
            margin: 0 0 16px 0;
        }

        .tips-card ul {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .tips-card li {
            position: relative;
            padding-left: 20px;
            font-size: 14px;
            color: #78350f;
            margin-bottom: 10px;
            line-height: 1.5;
        }

        .tips-card li::before {
            content: '';
            position: absolute;
            left: 0;
            top: 8px;
            width: 6px;
            height: 6px;
            background: #d97706;
            border-radius: 50%;
        }

        .tips-card li:last-child {
            margin-bottom: 0;
        }

        .modal {
            backdrop-filter: blur(8px);
        }

        .modal.active .modal-content {
            animation: modalSlideIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .form-group input:focus {
            animation: inputFocus 0.3s ease;
        }

        @keyframes inputFocus {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.01); }
        }

        .avatar-upload-container {
            display: flex;
            align-items: flex-start;
            gap: 24px;
        }

        .avatar-preview-wrapper {
            flex-shrink: 0;
        }

        .avatar-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
            border: 3px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .avatar-preview:hover {
            border-color: var(--primary-color);
            box-shadow: 0 4px 16px rgba(245, 158, 11, 0.2);
        }

        .avatar-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar-preview svg {
            color: #9ca3af;
        }

        .avatar-remove-btn {
            position: absolute;
            top: -4px;
            right: -4px;
            width: 24px;
            height: 24px;
            background: #ef4444;
            border: 2px solid white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            opacity: 0;
            transform: scale(0.8);
            transition: all 0.2s ease;
        }

        .avatar-preview:hover .avatar-remove-btn {
            opacity: 1;
            transform: scale(1);
        }

        .avatar-remove-btn:hover {
            background: #dc2626;
            transform: scale(1.1);
        }

        .avatar-upload-actions {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .avatar-upload-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 13px;
        }

        .avatar-upload-hint {
            font-size: 12px;
            color: var(--text-muted);
            margin: 0;
        }

        .avatar-drag-overlay {
            position: absolute;
            inset: 0;
            background: rgba(245, 158, 11, 0.9);
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            color: white;
            z-index: 10;
        }

        .avatar-drag-overlay.active {
            display: flex;
        }

        .avatar-drag-overlay svg {
            margin-bottom: 8px;
        }

        .avatar-preview.dragging {
            border-color: var(--primary-color);
            border-style: dashed;
        }

        .btn-primary {
            position: relative;
            overflow: hidden;
        }

        .btn-primary::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.4s, height 0.4s;
        }

        .btn-primary:hover::after {
            width: 200px;
            height: 200px;
        }

        @media (max-width: 1200px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }

            .profile-sidebar {
                flex-direction: row;
                flex-wrap: wrap;
            }

            .quick-actions-card,
            .tips-card {
                flex: 1;
                min-width: 280px;
            }
        }

        @media (max-width: 768px) {
            .profile-hero {
                padding: 32px 24px;
            }

            .profile-hero-content {
                flex-direction: column;
                text-align: center;
            }

            .profile-hero-info {
                text-align: center;
            }

            .profile-hero-role,
            .profile-hero-email {
                justify-content: center;
            }

            .profile-hero-actions {
                flex-direction: column;
                width: 100%;
            }

            .btn-hero-primary,
            .btn-hero-secondary {
                width: 100%;
                justify-content: center;
            }

            .info-cards {
                grid-template-columns: 1fr;
            }

            .profile-sidebar {
                flex-direction: column;
            }

            .quick-actions-card,
            .tips-card {
                min-width: 100%;
            }
        }
    </style>

    <script>
        function openProfileModal() {
            document.getElementById('profileModal').classList.add('active');
            document.getElementById('profileError').style.display = 'none';
            document.getElementById('profileSuccess').style.display = 'none';
        }

        function closeProfileModal() {
            document.getElementById('profileModal').classList.remove('active');
            document.getElementById('profileForm').reset();
            resetAvatarPreview();
        }

        function openPasswordModal() {
            document.getElementById('passwordModal').classList.add('active');
            document.getElementById('passwordError').style.display = 'none';
            document.getElementById('passwordSuccess').style.display = 'none';
            document.getElementById('passwordForm').reset();
        }

        function closePasswordModal() {
            document.getElementById('passwordModal').classList.remove('active');
        }
        
        function openMediaLibraryForAvatar() {
            MediaPicker.open({
                multiple: false,
                onSelect: function(url) {
                    updateAvatarPreview(url);
                }
            });
        }

        function updateAvatarPreview(url) {
            const preview = document.getElementById('avatarPreview');
            preview.innerHTML = '<img src="' + url + '" alt="头像预览"><button type="button" class="avatar-remove-btn" onclick="removeAvatarPreview()" title="移除头像"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>';
            document.getElementById('avatar_url').value = url;
            document.getElementById('avatar_file').value = '';
        }

        function removeAvatarPreview() {
            const preview = document.getElementById('avatarPreview');
            preview.innerHTML = '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            document.getElementById('avatar_url').value = '';
            document.getElementById('avatar_file').value = '';
        }

        function handleAvatarFileSelect(input) {
            const file = input.files[0];
            if (!file) return;

            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                alert('只支持 JPG、PNG、GIF、WebP 格式的图片');
                input.value = '';
                return;
            }

            if (file.size > 2 * 1024 * 1024) {
                alert('图片大小不能超过 2MB');
                input.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                updateAvatarPreview(e.target.result);
                uploadAvatarFile(file);
            };
            reader.readAsDataURL(file);
        }

        async function uploadAvatarFile(file) {
            const formData = new FormData();
            formData.append('avatar', file);

            try {
                const response = await fetch('/api/auth.php?action=upload_avatar', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success && result.url) {
                    document.getElementById('avatar_url').value = result.url;
                    updateAvatarPreview(result.url);
                } else {
                    alert(result.error || '上传失败');
                }
            } catch (error) {
                console.error('上传错误:', error);
                alert('上传失败，请稍后重试');
            }
        }

        function initAvatarDragDrop() {
            const preview = document.getElementById('avatarPreview');
            if (!preview) return;

            preview.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('dragging');
            });

            preview.addEventListener('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.remove('dragging');
            });

            preview.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.remove('dragging');

                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    const file = files[0];
                    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    
                    if (!allowedTypes.includes(file.type)) {
                        alert('只支持 JPG、PNG、GIF、WebP 格式的图片');
                        return;
                    }

                    if (file.size > 2 * 1024 * 1024) {
                        alert('图片大小不能超过 2MB');
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function(e) {
                        updateAvatarPreview(e.target.result);
                        uploadAvatarFile(file);
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            initAvatarDragDrop();
        });

        function resetAvatarPreview() {
            const currentAvatar = '<?php echo $avatarUrl; ?>';
            const preview = document.getElementById('avatarPreview');
            
            if (currentAvatar) {
                preview.innerHTML = '<img src="' + currentAvatar + '" alt="头像预览"><button type="button" class="avatar-remove-btn" onclick="removeAvatarPreview()" title="移除头像"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>';
            } else {
                preview.innerHTML = '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            }
            document.getElementById('avatar_url').value = '';
            document.getElementById('avatar_file').value = '';
        }

        document.getElementById('profileForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('profileSubmitBtn');
            const errorDiv = document.getElementById('profileError');
            const successDiv = document.getElementById('profileSuccess');
            const avatarUrl = document.getElementById('avatar_url').value;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> 保存中...';

            const formData = new FormData();
            formData.append('name', document.getElementById('name').value);
            formData.append('email', document.getElementById('email').value);
            
            if (avatarUrl) {
                formData.append('avatar_url', avatarUrl);
            }

            try {
                const response = await fetch('/api/auth.php?action=update_profile', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    successDiv.textContent = result.message;
                    successDiv.style.display = 'block';
                    errorDiv.style.display = 'none';
                    
                    const displayName = result.user.name || result.user.username;
                    document.querySelector('.profile-hero-name').textContent = displayName;
                    document.querySelector('.profile-hero-email').innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M22 6l-10 7L2 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> ' + result.user.email;
                    
                    const infoCards = document.querySelectorAll('.info-card-value');
                    if (infoCards[1]) infoCards[1].textContent = result.user.name || '-';
                    if (infoCards[2]) infoCards[2].textContent = result.user.email;
                    
                    if (result.user.avatar) {
                        const profileAvatar = document.getElementById('profileAvatarDisplay');
                        profileAvatar.innerHTML = '<img src="' + result.user.avatar + '" alt="头像">';
                    }
                    
                    closeProfileModal();
                    location.reload();
                } else {
                    errorDiv.textContent = result.error;
                    errorDiv.style.display = 'block';
                    successDiv.style.display = 'none';
                }
            } catch (error) {
                errorDiv.textContent = '网络错误，请稍后重试';
                errorDiv.style.display = 'block';
                successDiv.style.display = 'none';
            }

            submitBtn.disabled = false;
            submitBtn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> 保存修改';
        });

        document.getElementById('passwordForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('passwordSubmitBtn');
            const errorDiv = document.getElementById('passwordError');
            const successDiv = document.getElementById('passwordSuccess');
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> 修改中...';
            
            const formData = {
                current_password: document.getElementById('current_password').value,
                new_password: document.getElementById('new_password').value,
                confirm_password: document.getElementById('confirm_password').value
            };

            try {
                const response = await fetch('/api/auth.php?action=change_password', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const result = await response.json();

                if (result.success) {
                    successDiv.textContent = result.message;
                    successDiv.style.display = 'block';
                    errorDiv.style.display = 'none';
                    
                    closePasswordModal();
                    document.getElementById('passwordForm').reset();
                } else {
                    errorDiv.textContent = result.error;
                    errorDiv.style.display = 'block';
                    successDiv.style.display = 'none';
                }
            } catch (error) {
                errorDiv.textContent = '网络错误，请稍后重试';
                errorDiv.style.display = 'block';
                successDiv.style.display = 'none';
            }

            submitBtn.disabled = false;
            submitBtn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> 修改密码';
        });

        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.classList.remove('active');
                }
            });
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.active').forEach(modal => {
                    modal.classList.remove('active');
                });
            }
        });
    </script>
    <?php include 'components/media-picker.php'; ?>
    <script src="components/media-picker.js"></script>
</body>
</html>
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
$user = Auth::user();
$db = Database::getInstance();
$unread_messages = $db->fetchOne("SELECT COUNT(*) as count FROM `lty-contact_messages` WHERE status = 0")['count'];
?>
<header class="topbar">
    <div class="topbar-left">
        <button class="sidebar-toggle" id="sidebarToggle">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 12h18M3 6h18M3 18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
        <div class="breadcrumb">
            <?php 
            if ($current_page === 'profile.php') {
                echo '<span class="breadcrumb-item active">个人资料</span>';
            } else {
                $breadcrumb = getBreadcrumbPath($current_page, $menu_items);
                foreach ($breadcrumb as $index => $item) {
                    if ($index > 0) {
                        echo '<span class="breadcrumb-separator">/</span>';
                    }
                    if ($index === count($breadcrumb) - 1) {
                        echo '<span class="breadcrumb-item active">' . htmlspecialchars($item) . '</span>';
                    } else {
                        echo '<span class="breadcrumb-item">' . htmlspecialchars($item) . '</span>';
                    }
                }
            }
            ?>
        </div>
    </div>
    
    <div class="topbar-right">
        <div class="topbar-actions">
            <a href="../" class="topbar-action" title="前台" target="_blank">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <polyline points="9 22 9 12 15 12 15 22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
            
            <a href="messages.php" class="topbar-action" title="留言">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <?php if ($unread_messages > 0): ?>
                    <span class="action-badge"><?php echo $unread_messages; ?></span>
                <?php endif; ?>
            </a>
            
            <a href="settings.php" class="topbar-action" title="设置">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
            
            <?php if (Auth::isSuperAdmin()): ?>
            <button class="topbar-action" id="clearCacheBtn" title="清理缓存">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
            <?php endif; ?>
        </div>
        
        <div class="user-dropdown">
            <button class="user-menu-btn" id="userMenuBtn">
                <div class="user-avatar">
                    <?php 
                    $avatarUrl = $user ? get_avatar_url($user['avatar'] ?? null, $user['email'] ?? null, 40) : null;
                    if (!empty($avatarUrl)): ?>
                        <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="头像" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;" onerror="this.style.display='none';document.getElementById('defaultAvatarSvg').style.display='block';">
                        <svg id="defaultAvatarSvg" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="display:none;">
                            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    <?php else: ?>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    <?php endif; ?>
                </div>
                <span class="user-name"><?php echo htmlspecialchars($user ? (!empty($user['name']) ? $user['name'] : ($user['username'] ?? '用户')) : '用户'); ?></span>
                <svg class="dropdown-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19 9l-7 7-7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
            <div class="user-dropdown-menu" id="userDropdownMenu">
                <a href="profile.php" class="dropdown-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>个人资料</span>
                </a>
                <a href="profile.php#password" class="dropdown-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>修改密码</span>
                </a>
                <div class="dropdown-divider"></div>
                <a href="logout.php" class="dropdown-item logout">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>退出登录</span>
                </a>
            </div>
        </div>
    </div>
</header>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('error', function(e) {
        if (e.target.tagName === 'IMG') {
            if (!e.target.dataset.errorHandled) {
                e.target.dataset.errorHandled = 'true';
                e.target.src = '../assets/images/丢失/图片丢失-copy.png';
            }
        }
    }, true);
    
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userDropdownMenu = document.getElementById('userDropdownMenu');
    const mobileOverlay = document.getElementById('mobileOverlay');
    const clearCacheBtn = document.getElementById('clearCacheBtn');
    
    function isMobile() {
        return window.innerWidth <= 768;
    }
    
    function toggleSidebar() {
        if (isMobile()) {
            sidebar.classList.toggle('mobile-open');
            if (mobileOverlay) {
                mobileOverlay.classList.toggle('active');
            }
        } else {
            sidebar.classList.toggle('collapsed');
        }
    }
    
    function closeSidebar() {
        if (isMobile()) {
            sidebar.classList.remove('mobile-open');
            if (mobileOverlay) {
                mobileOverlay.classList.remove('active');
            }
        }
    }
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleSidebar();
        });
    }
    
    if (mobileOverlay) {
        mobileOverlay.addEventListener('click', function() {
            closeSidebar();
        });
    }
    
    if (sidebar) {
        sidebar.addEventListener('click', function(e) {
            if (isMobile() && e.target.tagName === 'A') {
                closeSidebar();
            }
        });
    }
    
    if (userMenuBtn && userDropdownMenu) {
        userMenuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdownMenu.classList.toggle('active');
        });
        
        document.addEventListener('click', function(e) {
            if (!userMenuBtn.contains(e.target) && !userDropdownMenu.contains(e.target)) {
                userDropdownMenu.classList.remove('active');
            }
        });
    }
    
    if (clearCacheBtn) {
        clearCacheBtn.addEventListener('click', function() {
            if (!confirm('确定要清理缓存吗？')) {
                return;
            }
            
            clearCacheBtn.disabled = true;
            clearCacheBtn.style.opacity = '0.5';
            
            fetch('api/cache.php', {
                method: 'POST',
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('缓存清理成功！');
                } else {
                    alert('清理失败：' + (data.message || '未知错误'));
                }
            })
            .catch(error => {
                alert('请求失败，请稍后重试');
            })
            .finally(() => {
                clearCacheBtn.disabled = false;
                clearCacheBtn.style.opacity = '1';
            });
        });
    }
    
    window.addEventListener('resize', function() {
        if (!isMobile()) {
            sidebar.classList.remove('mobile-open');
            if (mobileOverlay) {
                mobileOverlay.classList.remove('active');
            }
        }
    });
});
</script>

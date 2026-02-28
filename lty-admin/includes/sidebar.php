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
$current_page = basename($_SERVER['PHP_SELF']);
$db = Database::getInstance();
$unread_messages = $db->fetchOne("SELECT COUNT(*) as count FROM `lty-contact_messages` WHERE status = 0")['count'];

function getPageTitle($page, $menu_items) {
    if ($page === 'profile.php') {
        return '个人资料';
    }
    foreach ($menu_items as $item) {
        if (isset($item['url']) && $item['url'] === $page) {
            return $item['title'];
        }
        if (isset($item['items'])) {
            foreach ($item['items'] as $subitem) {
                if (isset($subitem['url']) && $subitem['url'] === $page) {
                    return $subitem['title'];
                }
                if (isset($subitem['items'])) {
                    foreach ($subitem['items'] as $thirditem) {
                        if (isset($thirditem['url']) && $thirditem['url'] === $page) {
                            return $thirditem['title'];
                        }
                    }
                }
            }
        }
    }
    return basename($page, '.php');
}

function getBreadcrumbPath($page, $menu_items) {
    $path = [];
    if ($page === 'profile.php') {
        $path[] = '个人资料';
        return $path;
    }
    foreach ($menu_items as $item) {
        if (isset($item['url']) && $item['url'] === $page) {
            $path[] = $item['title'];
            return $path;
        }
        if (isset($item['items'])) {
            foreach ($item['items'] as $subitem) {
                if (isset($subitem['url']) && $subitem['url'] === $page) {
                    $path[] = $item['category'];
                    $path[] = $subitem['title'];
                    return $path;
                }
                if (isset($subitem['items'])) {
                    foreach ($subitem['items'] as $thirditem) {
                        if (isset($thirditem['url']) && $thirditem['url'] === $page) {
                            $path[] = $item['category'];
                            $path[] = $subitem['category'];
                            $path[] = $thirditem['title'];
                            return $path;
                        }
                    }
                }
            }
        }
    }
    $path[] = basename($page, '.php');
    return $path;
}

function buildMenuTree($menus, $parentId = null) {
    $tree = [];
    foreach ($menus as $menu) {
        $parentMatch = ($parentId === null && $menu['parent_id'] === null) || 
                       ($parentId !== null && $menu['parent_id'] == $parentId);
        if ($parentMatch) {
            $item = [
                'id' => $menu['id'],
                'name' => $menu['name'],
                'slug' => $menu['slug'],
                'url' => $menu['url'],
                'icon' => $menu['icon'],
                'type' => $menu['type'],
            ];
            $children = buildMenuTree($menus, $menu['id']);
            if (!empty($children)) {
                $item['items'] = $children;
            }
            $tree[] = $item;
        }
    }
    return $tree;
}

function renderMenuItem($item, $current_page, $level = 0) {
    $hasChildren = isset($item['items']) && !empty($item['items']);
    $isActive = isset($item['url']) && $item['url'] === $current_page;
    $hasActiveChild = false;
    
    if ($hasChildren) {
        foreach ($item['items'] as $child) {
            if ((isset($child['url']) && $child['url'] === $current_page) || 
                (isset($child['items']) && renderMenuItemCheckActive($child['items'], $current_page))) {
                $hasActiveChild = true;
                break;
            }
        }
    }
    
    if ($level === 0) {
        if ($hasChildren) {
            echo '<li class="nav-category">';
            echo '<div class="category-title" onclick="toggleCategory(' . $item['id'] . ')">';
            echo '<span class="category-icon">' . ($item['icon'] ?? '') . '</span>';
            echo '<span class="category-name">' . htmlspecialchars($item['name']) . '</span>';
            if ($item['slug'] === 'interaction_category') {
                global $unread_messages;
                if ($unread_messages > 0) {
                    echo '<span class="nav-badge">' . $unread_messages . '</span>';
                }
            }
            echo '<span class="category-toggle' . (!$hasActiveChild ? ' rotated' : '') . '">';
            echo '<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">';
            echo '<path d="M2.5 4.5L6 8L9.5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>';
            echo '</svg></span></div>';
            echo '<ul class="category-items' . (!$hasActiveChild ? ' collapsed' : '') . '" id="category-' . $item['id'] . '">';
            foreach ($item['items'] as $child) {
                renderMenuItem($child, $current_page, $level + 1);
            }
            echo '</ul></li>';
        } else {
            echo '<li>';
            echo '<a href="' . htmlspecialchars($item['url'] ?? '#') . '" class="nav-item' . ($isActive ? ' active' : '') . '">';
            echo '<span class="nav-icon">' . ($item['icon'] ?? '') . '</span>';
            echo '<span class="nav-text">' . htmlspecialchars($item['name']) . '</span>';
            echo '</a></li>';
        }
    } elseif ($level === 1) {
        if ($hasChildren) {
            echo '<li class="nav-subcategory">';
            echo '<div class="subcategory-title" onclick="toggleSubcategory(' . $item['parent_id'] . ', ' . $item['id'] . ')">';
            echo '<span class="subcategory-icon">' . ($item['icon'] ?? '') . '</span>';
            echo '<span class="subcategory-name">' . htmlspecialchars($item['name']) . '</span>';
            echo '<span class="subcategory-toggle' . (!$hasActiveChild ? ' rotated' : '') . '">';
            echo '<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">';
            echo '<path d="M2.5 4.5L6 8L9.5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>';
            echo '</svg></span></div>';
            echo '<ul class="subcategory-items' . (!$hasActiveChild ? ' collapsed' : '') . '" id="subcategory-' . $item['parent_id'] . '-' . $item['id'] . '">';
            foreach ($item['items'] as $child) {
                renderMenuItem($child, $current_page, $level + 1);
            }
            echo '</ul></li>';
        } else {
            echo '<li>';
            echo '<a href="' . htmlspecialchars($item['url'] ?? '#') . '" class="nav-item' . ($isActive ? ' active' : '') . '">';
            echo '<span class="nav-icon">' . ($item['icon'] ?? '') . '</span>';
            echo '<span class="nav-text">' . htmlspecialchars($item['name']) . '</span>';
            if ($item['slug'] === 'messages') {
                global $unread_messages;
                if ($unread_messages > 0) {
                    echo '<span class="nav-badge">' . $unread_messages . '</span>';
                }
            }
            echo '</a></li>';
        }
    } else {
        echo '<li>';
        echo '<a href="' . htmlspecialchars($item['url'] ?? '#') . '" class="nav-item' . ($isActive ? ' active' : '') . '">';
        echo '<span class="nav-icon">' . ($item['icon'] ?? '') . '</span>';
        echo '<span class="nav-text">' . htmlspecialchars($item['name']) . '</span>';
        echo '</a></li>';
    }
}

function renderMenuItemCheckActive($items, $current_page) {
    foreach ($items as $item) {
        if (isset($item['url']) && $item['url'] === $current_page) {
            return true;
        }
        if (isset($item['items'])) {
            if (renderMenuItemCheckActive($item['items'], $current_page)) {
                return true;
            }
        }
    }
    return false;
}

$userMenus = Auth::getMenus();
$menuTree = buildMenuTree($userMenus);

$currentUser = Auth::user();
$hasRole = Auth::hasRole();
$isSuperAdmin = Auth::isSuperAdmin();

$menu_items = [];

$menu_items[] = [
    'url' => 'index.php',
    'title' => '仪表盘',
    'icon' => '<svg width="18" height="18" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path d="M128 192h768v64H128zM128 384h768v64H128zM128 576h768v64H128zM128 768h768v64H128z" fill="currentColor"/></svg>'
];

if ($hasRole && ($isSuperAdmin || Auth::hasPermission('media_view') || Auth::hasPermission('media_manage'))) {
    $menu_items[] = [
        'category' => '媒体',
        'icon' => '<svg width="16" height="16" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path d="M128 192h256v192H128zM448 192h256v192H448zM128 448h256v192H128zM448 448h256v192H448z" fill="currentColor"/></svg>',
        'items' => [
            ['url' => 'media.php', 'title' => '媒体库', 'icon' => '<svg width="18" height="18" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path d="M128 192h768v640H128z" fill="currentColor"/><path d="M256 320h192v192H256zM512 320h256v64H512zM512 448h256v64H512zM256 576h512v64H256zM256 704h512v64H256z" fill="#ffffff"/></svg>']
        ]
    ];
}

$homeItems = [];
if ($hasRole) {
    if ($isSuperAdmin || Auth::hasPermission('mission_manage')) {
        $homeItems[] = ['url' => 'mission.php', 'title' => '轮播图管理', 'icon' => '<svg width="18" height="18" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path d="M128 192h768v640H128z" fill="currentColor"/><path d="M416 512l192-128v256z" fill="#ffffff"/></svg>'];
    }
    if ($isSuperAdmin || Auth::hasPermission('story_manage')) {
        $homeItems[] = ['url' => 'story.php', 'title' => '公司简介', 'icon' => '<svg width="18" height="18" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path d="M192 128h640v768H192z" fill="currentColor"/><path d="M256 256h512v64H256zM256 384h512v64H256zM256 512h384v64H256z" fill="#ffffff"/></svg>'];
    }
    if ($isSuperAdmin || Auth::hasPermission('culture_manage')) {
        $homeItems[] = ['url' => 'culture.php', 'title' => '企业文化', 'icon' => '<svg width="18" height="18" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path d="M512 128c-106 0-192 86-192 192 0 64 32 121 80 156v320h224V476c48-35 80-92 80-156 0-106-86-192-192-192z m0 128c35.3 0 64 28.7 64 64s-28.7 64-64 64-64-28.7-64-64 28.7-64 64-64z" fill="currentColor"/></svg>'];
    }
    if ($isSuperAdmin || Auth::hasPermission('timeline_manage')) {
        $homeItems[] = ['url' => 'timeline.php', 'title' => '大事记', 'icon' => '<svg width="18" height="18" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path d="M128 192h192v640H128zM320 320h192v512H320zM512 256h192v576H512zM704 384h192v448H704z" fill="currentColor"/></svg>'];
    }
    if ($isSuperAdmin || Auth::hasPermission('members_manage')) {
        $homeItems[] = ['url' => 'members.php', 'title' => '团队成员', 'icon' => '<svg width="18" height="18" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path d="M512 512c-106 0-192-86-192-192s86-192 192-192 192 86 192 192-86 192-192 192z" fill="currentColor"/><path d="M512 576c-141.4 0-256 114.6-256 256v64h512v-64c0-141.4-114.6-256-256-256z" fill="currentColor"/></svg>'];
    }
    if ($isSuperAdmin || Auth::hasPermission('behavior_manage')) {
        $homeItems[] = ['url' => 'behavior.php', 'title' => '行为准则', 'icon' => '<svg width="18" height="18" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path d="M512 128c-212 0-384 172-384 384s172 384 384 384 384-172 384-384-172-384-384-384z m0 640c-141.4 0-256-114.6-256-256s114.6-256 256-256 256 114.6 256 256-114.6 256-256 256z" fill="currentColor"/><path d="M464 384h96v256h-96zM464 704h96v96h-96z" fill="currentColor"/></svg>'];
    }
    if ($isSuperAdmin || Auth::hasPermission('products_manage')) {
        $homeItems[] = ['url' => 'products.php', 'title' => '产品管理', 'icon' => '<svg width="18" height="18" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path d="M832 256H192v512h640V256z m-64 448H256V320h512v384z" fill="currentColor"/><path d="M320 384h128v64H320zM320 512h128v64H320zM480 384h256v64H480zM480 512h256v64H480z" fill="currentColor"/></svg>'];
    }
    if ($isSuperAdmin || Auth::hasPermission('business_manage')) {
        $homeItems[] = ['url' => 'business.php', 'title' => '业务领域', 'icon' => '<svg width="18" height="18" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path d="M512 128c-212 0-384 172-384 384s172 384 384 384 384-172 384-384-172-384-384-384z m0 640c-141.4 0-256-114.6-256-256s114.6-256 256-256 256 114.6 256 256-114.6 256-256 256z" fill="currentColor"/><path d="M512 320v192l128 128" stroke="currentColor" stroke-width="64" fill="none"/></svg>'];
    }
}

$siteItems = [];
if (!empty($homeItems)) {
    $siteItems[] = [
        'category' => '首页',
        'icon' => '<svg width="16" height="16" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path d="M512 128L128 448v384h256V640h256v192h256V448L512 128z" fill="currentColor"/></svg>',
        'items' => $homeItems
    ];
}

if ($hasRole && ($isSuperAdmin || Auth::hasPermission('contact_manage'))) {
    $siteItems[] = ['url' => 'contact.php', 'title' => '联系我们', 'icon' => '<svg width="18" height="18" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path d="M128 192h768v576L512 640 128 768V192z" fill="currentColor"/><path d="M192 256h640v64H192zM192 384h640v64H192zM192 512h384v64H192z" fill="#ffffff"/></svg>'];
}

if (!empty($siteItems)) {
    $menu_items[] = [
        'category' => '官网管理',
        'icon' => '<svg width="16" height="16" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path d="M128 384h256v448H128zM448 384h192v448H448zM704 384h192v448H704zM128 192h768v128H128z" fill="currentColor"/></svg>',
        'items' => $siteItems
    ];
}

if ($hasRole && ($isSuperAdmin || Auth::hasPermission('messages_manage'))) {
    $menu_items[] = [
        'category' => '互动管理',
        'icon' => '<svg width="16" height="16" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path d="M512 128c-212 0-384 172-384 384s172 384 384 384 384-172 384-384-172-384-384-384z m0 640c-141.4 0-256-114.6-256-256s114.6-256 256-256 256 114.6 256 256-114.6 256-256 256z" fill="currentColor"/><path d="M384 448h64v128h-64zM448 384h128v64H448zM576 448h64v128h-64z" fill="currentColor"/></svg>',
        'badge' => $unread_messages > 0 ? $unread_messages : null,
        'items' => [
            ['url' => 'messages.php', 'title' => '留言管理', 'icon' => '<svg width="18" height="18" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path d="M128 192h768v576L512 640 128 768V192z" fill="currentColor"/><path d="M192 256h640v64H192zM192 384h640v64H192zM192 512h384v64H192z" fill="#ffffff"/></svg>', 'badge' => $unread_messages > 0 ? $unread_messages : null]
        ]
    ];
}

if ($hasRole && ($isSuperAdmin || Auth::hasPermission('roles_manage') || Auth::hasPermission('users_manage') || Auth::hasPermission('logs_view'))) {
    $devItems = [];
    if ($isSuperAdmin || Auth::hasPermission('roles_manage')) {
        $devItems[] = ['url' => 'roles.php', 'title' => '团队角色', 'icon' => '<svg width="18" height="18" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path d="M512 512c-106 0-192-86-192-192s86-192 192-192 192 86 192 192-86 192-192 192z" fill="currentColor"/><path d="M512 576c-141.4 0-256 114.6-256 256v64h512v-64c0-141.4-114.6-256-256-256z" fill="currentColor"/><path d="M704 320c0 106-86 192-192 192s-192-86-192-192 86-192 192-192 192 86 192 192z" fill="currentColor" opacity="0.3"/></svg>'];
    }
    if ($isSuperAdmin || Auth::hasPermission('users_manage')) {
        $devItems[] = ['url' => 'admin_users.php', 'title' => '后台用户', 'icon' => '<svg width="18" height="18" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path d="M512 320c88.4 0 160-71.6 160-160S600.4 0 512 0 352 71.6 352 160s71.6 160 160 160z m0 64c-106.7 0-320 53.3-320 160v128h640V544c0-106.7-213.3-160-320-160z" fill="currentColor"/></svg>'];
    }
    if ($isSuperAdmin || Auth::hasPermission('logs_view')) {
        $devItems[] = ['url' => 'logs.php', 'title' => '操作日志', 'icon' => '<svg width="18" height="18" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path d="M832 128H192c-35.3 0-64 28.7-64 64v640c0 35.3 28.7 64 64 64h640c35.3 0 64-28.7 64-64V192c0-35.3-28.7-64-64-64z m0 704H192V192h640v640z" fill="currentColor"/><path d="M256 320h512v64H256zM256 448h512v64H256zM256 576h384v64H256zM256 704h256v64H256z" fill="currentColor"/></svg>'];
    }
    if (!empty($devItems)) {
        $menu_items[] = [
            'category' => '开发配置',
            'icon' => '<svg width="16" height="16" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path d="M316.8 670.4L137.6 512l179.2-158.4c12.8-11.2 12.8-30.4 0-42.4-11.2-11.2-30.4-11.2-42.4 0L64 490.4c-12.8 11.2-12.8 30.4 0 42.4l210.4 179.2c6.4 4.8 12.8 8 20.8 8 8 0 16-3.2 20.8-8 12.8-12.8 12.8-32 0-43.2z m388.8-380.8c-11.2-11.2-30.4-11.2-42.4 0-12.8 11.2-12.8 30.4 0 42.4L842.4 512l-179.2 158.4c-12.8 11.2-12.8 30.4 0 42.4 6.4 4.8 12.8 8 20.8 8 8 0 16-3.2 20.8-8L915.2 533.6c12.8-11.2 12.8-30.4 0-42.4L705.6 289.6z m-136-43.2c-16-4.8-32 4.8-36.8 20.8L412.8 728c-4.8 16 4.8 32 20.8 36.8 3.2 1.6 6.4 1.6 9.6 1.6 12.8 0 24-8 28.8-22.4l120-460.8c3.2-16-6.4-33.6-22.4-37.6z" fill="currentColor"/></svg>',
            'items' => $devItems
        ];
    }
}

if ($hasRole && ($isSuperAdmin || Auth::hasPermission('settings_manage') || Auth::hasPermission('seo_manage') || Auth::hasPermission('security_logs_view'))) {
    $systemItems = [];
    if ($isSuperAdmin || Auth::hasPermission('settings_manage')) {
        $systemItems[] = ['url' => 'settings.php', 'title' => '网站设置', 'icon' => '<svg width="18" height="18" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path d="M512 64C264.6 64 64 264.6 64 512s200.6 448 448 448 448-200.6 448-448S759.4 64 512 64z m0 820c-205.4 0-372-166.6-372-372s166.6-372 372-372 372 166.6 372 372-166.6 372-372 372z" fill="currentColor"/><path d="M512 192c-176.7 0-320 143.3-320 320s143.3 320 320 320 320-143.3 320-320-143.3-320-320-320z m0 576c-141.4 0-256-114.6-256-256s114.6-256 256-256 256 114.6 256 256-114.6 256-256 256z" fill="currentColor"/><path d="M512 288v448M288 512h448" stroke="currentColor" stroke-width="64"/></svg>'];
    }
    if ($isSuperAdmin || Auth::hasPermission('seo_manage')) {
        $systemItems[] = ['url' => 'seo.php', 'title' => 'SEO优化', 'icon' => '<svg width="18" height="18" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path d="M904.4 795.2L728.8 619.6c36.8-56 58.4-122.4 58.4-194 0-193.6-156.8-350.4-350.4-350.4S86.4 232 86.4 425.6s156.8 350.4 350.4 350.4c71.6 0 138-21.6 194-58.4l175.6 175.6c13.6 13.6 35.2 13.6 48.8 0l49.2-49.2c13.6-13.6 13.6-35.2 0-48.8zM436.8 676c-138.4 0-250.4-112-250.4-250.4s112-250.4 250.4-250.4 250.4 112 250.4 250.4-112 250.4-250.4 250.4z" fill="currentColor"/><path d="M536.8 376h-80v-80c0-11-9-20-20-20s-20 9-20 20v80h-80c-11 0-20 9-20 20s9 20 20 20h80v80c0 11 9 20 20 20s20-9 20-20v-80h80c11 0 20-9 20-20s-9-20-20-20z" fill="currentColor"/><path d="M620 620l-40 40 120 120 40-40z" fill="currentColor"/></svg>'];
    }
    if ($isSuperAdmin || Auth::hasPermission('security_logs_view')) {
        $systemItems[] = ['url' => 'security_logs.php', 'title' => '安全日志', 'icon' => '<svg width="18" height="18" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path d="M512 64C264.6 64 64 264.6 64 512s200.6 448 448 448 448-200.6 448-448S759.4 64 512 64zm0 820c-205.4 0-372-166.6-372-372s166.6-372 372-372 372 166.6 372 372-166.6 372-372 372z" fill="currentColor"/><path d="M464 336a48 48 0 1 0 96 0 48 48 0 1 0-96 0zm72 112h-48c-4.4 0-8 3.6-8 8v272c0 4.4 3.6 8 8 8h48c4.4 0 8-3.6 8-8V456c0-4.4-3.6-8-8-8z" fill="currentColor"/></svg>'];
    }
    if (!empty($systemItems)) {
        $menu_items[] = [
            'category' => '系统设置',
            'icon' => '<svg width="16" height="16" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path d="M512 192c-17.7 0-32 14.3-32 32v64c-35.3 8.6-68.1 24.2-96.4 45.1l-55.4-32c-15.3-8.8-34.8-3.6-43.6 11.7l-56 96.9c-8.8 15.3-3.6 34.8 11.7 43.6l55.4 32c-2.2 17.7-2.2 35.7 0 53.4l-55.4 32c-15.3 8.8-20.5 28.3-11.7 43.6l56 96.9c8.8 15.3 28.3 20.5 43.6 11.7l55.4-32c28.3 20.9 61.1 36.5 96.4 45.1v64c0 17.7 14.3 32 32 32s32-14.3 32-32v-64c35.3-8.6 68.1-24.2 96.4-45.1l55.4 32c15.3 8.8 34.8 3.6 43.6-11.7l56-96.9c8.8-15.3 3.6-34.8-11.7-43.6l-55.4-32c2.2-17.7 2.2-35.7 0-53.4l55.4-32c15.3-8.8 20.5-28.3 11.7-43.6l-56-96.9c-8.8-15.3-28.3-20.5-43.6-11.7l-55.4 32c-28.3-20.9-61.1-36.5-96.4-45.1v-64c0-17.7-14.3-32-32-32z m0 256c53 0 96 43 96 96s-43 96-96 96-96-43-96-96 43-96 96-96z" fill="currentColor"/></svg>',
            'items' => $systemItems
        ];
    }
}

if ($hasRole && ($isSuperAdmin || Auth::hasPermission('about_view'))) {
    $menu_items[] = [
        'url' => 'about.php',
        'title' => '关于程序',
        'icon' => '<svg width="18" height="18" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path d="M512 64C264.6 64 64 264.6 64 512s200.6 448 448 448 448-200.6 448-448S759.4 64 512 64zm0 820c-205.4 0-372-166.6-372-372s166.6-372 372-372 372 166.6 372 372-166.6 372-372 372z" fill="currentColor"/><path d="M464 336a48 48 0 1 0 96 0 48 48 0 1 0-96 0zm72 112h-48c-4.4 0-8 3.6-8 8v272c0 4.4 3.6 8 8 8h48c4.4 0 8-3.6 8-8V456c0-4.4-3.6-8-8-8z" fill="currentColor"/></svg>'
    ];
}
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <img src="../../assets/images/logo/logo.png" alt="Logo" class="logo-image">
        </div>
    </div>
    <nav class="sidebar-nav">
        <ul class="nav-list">
            <?php foreach ($menu_items as $index => $item): ?>
                <?php if (isset($item['url'])): ?>
                    <li>
                        <a href="<?php echo $item['url']; ?>" class="nav-item <?php echo $current_page === $item['url'] ? 'active' : ''; ?>">
                            <span class="nav-icon"><?php echo $item['icon']; ?></span>
                            <span class="nav-text"><?php echo $item['title']; ?></span>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-category">
                        <div class="category-title" onclick="toggleCategory(<?php echo $index; ?>)">
                            <span class="category-icon"><?php echo $item['icon']; ?></span>
                            <span class="category-name"><?php echo $item['category']; ?></span>
                            <?php if (isset($item['badge']) && $item['badge']): ?>
                                <span class="nav-badge"><?php echo $item['badge']; ?></span>
                            <?php endif; ?>
                            <span class="category-toggle">
                                <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M2.5 4.5L6 8L9.5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                        </div>
                        <ul class="category-items" id="category-<?php echo $index; ?>">
                            <?php foreach ($item['items'] as $subindex => $subitem): ?>
                                <?php if (isset($subitem['url'])): ?>
                                    <li>
                                        <a href="<?php echo $subitem['url']; ?>" class="nav-item <?php echo $current_page === $subitem['url'] ? 'active' : ''; ?>">
                                            <span class="nav-icon"><?php echo $subitem['icon']; ?></span>
                                            <span class="nav-text"><?php echo $subitem['title']; ?></span>
                                            <?php if (isset($subitem['badge']) && $subitem['badge']): ?>
                                                <span class="nav-badge"><?php echo $subitem['badge']; ?></span>
                                            <?php endif; ?>
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li class="nav-subcategory">
                                        <div class="subcategory-title" onclick="toggleSubcategory(<?php echo $index; ?>, <?php echo $subindex; ?>)">
                                            <span class="subcategory-icon"><?php echo $subitem['icon']; ?></span>
                                            <span class="subcategory-name"><?php echo $subitem['category']; ?></span>
                                            <span class="subcategory-toggle">
                                                <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M2.5 4.5L6 8L9.5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            </span>
                                        </div>
                                        <ul class="subcategory-items" id="subcategory-<?php echo $index; ?>-<?php echo $subindex; ?>">
                                            <?php foreach ($subitem['items'] as $thirditem): ?>
                                                <li>
                                                    <a href="<?php echo $thirditem['url']; ?>" class="nav-item <?php echo $current_page === $thirditem['url'] ? 'active' : ''; ?>">
                                                        <span class="nav-icon"><?php echo $thirditem['icon']; ?></span>
                                                        <span class="nav-text"><?php echo $thirditem['title']; ?></span>
                                                        <?php if (isset($thirditem['badge']) && $thirditem['badge']): ?>
                                                            <span class="nav-badge"><?php echo $thirditem['badge']; ?></span>
                                                        <?php endif; ?>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </nav>
    <div class="sidebar-footer">
        <div class="footer-info">
            <span class="footer-text">© 2026 <?php echo htmlspecialchars(Settings::get('site_name', '官网系统')); ?> 1.0</span>
            <span class="footer-text">徐州邻兔跃IT工作室提供技术支持版权所有</span>
        </div>
    </div>
</aside>
<div class="mobile-overlay" id="mobileOverlay"></div>
<?php require_once __DIR__ . '/topbar.php'; ?>
<script>
function toggleCategory(index) {
    const categoryItems = document.getElementById('category-' + index);
    const categoryTitle = categoryItems.previousElementSibling;
    const toggleIcon = categoryTitle.querySelector('.category-toggle');
    
    categoryItems.classList.toggle('collapsed');
    toggleIcon.classList.toggle('rotated');
}

function toggleSubcategory(categoryIndex, subcategoryIndex) {
    const subcategoryItems = document.getElementById('subcategory-' + categoryIndex + '-' + subcategoryIndex);
    const subcategoryTitle = subcategoryItems.previousElementSibling;
    const toggleIcon = subcategoryTitle.querySelector('.subcategory-toggle');
    
    subcategoryItems.classList.toggle('collapsed');
    toggleIcon.classList.toggle('rotated');
}

document.addEventListener('DOMContentLoaded', function() {
    const categories = document.querySelectorAll('.nav-category');
    categories.forEach((category) => {
        const categoryItems = category.querySelector('.category-items');
        const hasActiveItem = categoryItems.querySelector('.nav-item.active');
        
        if (!hasActiveItem) {
            categoryItems.classList.add('collapsed');
            const toggleIcon = category.querySelector('.category-toggle');
            if (toggleIcon) {
                toggleIcon.classList.add('rotated');
            }
        }
        
        const subcategories = category.querySelectorAll('.nav-subcategory');
        subcategories.forEach((subcategory) => {
            const subcategoryItems = subcategory.querySelector('.subcategory-items');
            const hasActiveSubItem = subcategoryItems.querySelector('.nav-item.active');
            
            if (!hasActiveSubItem) {
                subcategoryItems.classList.add('collapsed');
                const toggleIcon = subcategory.querySelector('.subcategory-toggle');
                if (toggleIcon) {
                    toggleIcon.classList.add('rotated');
                }
            }
        });
    });
});
</script>

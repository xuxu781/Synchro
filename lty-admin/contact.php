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

Auth::requirePermission('contact_manage');

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add_contact_type') {
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            $maxOrder = $db->fetchOne("SELECT MAX(sort_order) as max_order FROM `lty-contact_types`");
            $sort_order = ($maxOrder['max_order'] ?? 0) + 1;
            
            if (!empty($name)) {
                $newId = $db->insert('lty-contact_types', [
                    'name' => $name,
                    'description' => $description,
                    'sort_order' => $sort_order,
                    'status' => 1
                ]);
                
                OperationLog::create('contact', '创建联系类型：' . $name, 'contact_type', $newId, ['name' => $name]);
                set_flash_message('success', '联系类型添加成功');
            } else {
                set_flash_message('error', '类型名称不能为空');
            }
            redirect('lty-admin/contact.php#types');
            exit;
        }
        
        if ($action === 'update_contact_type') {
            $id = intval($_POST['type_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            if ($id > 0 && !empty($name)) {
                $db->update('lty-contact_types', [
                    'name' => $name,
                    'description' => $description
                ], 'id = ?', [$id]);
                
                OperationLog::update('contact', '更新联系类型：' . $name, 'contact_type', $id, ['name' => $name]);
                set_flash_message('success', '联系类型更新成功');
            } else {
                set_flash_message('error', '参数错误');
            }
            redirect('lty-admin/contact.php#types');
            exit;
        }
        
        if ($action === 'delete_contact_type') {
            $id = intval($_POST['type_id'] ?? 0);
            
            if ($id > 0) {
                $type = $db->fetchOne("SELECT name FROM `lty-contact_types` WHERE id = ?", [$id]);
                $db->delete('lty-contact_types', 'id = ?', [$id]);
                OperationLog::delete('contact', '删除联系类型：' . ($type['name'] ?? $id), 'contact_type', $id);
                set_flash_message('success', '联系类型删除成功');
            }
            redirect('lty-admin/contact.php#types');
            exit;
        }
        
        if ($action === 'update_contact_type_order') {
            header('Content-Type: application/json');
            $orders = $_POST['orders'] ?? [];
            if (is_array($orders)) {
                foreach ($orders as $order => $id) {
                    $db->update('lty-contact_types', ['sort_order' => intval($order) + 1], 'id = ?', [intval($id)]);
                }
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => '无效的数据']);
            }
            exit;
        }
        
        if ($action === 'toggle_contact_type') {
            $id = intval($_POST['type_id'] ?? 0);
            $status = intval($_POST['status'] ?? 0);
            
            if ($id > 0) {
                $db->update('lty-contact_types', ['status' => $status], 'id = ?', [$id]);
                set_flash_message('success', '联系类型状态更新成功');
            }
            redirect('lty-admin/contact.php#types');
            exit;
        }
        
        if ($action === 'add_social_link') {
            $platform = trim($_POST['platform'] ?? '');
            $url = trim($_POST['url'] ?? '');
            $icon_type = $_POST['icon_type'] ?? 'default';
            $icon_url = trim($_POST['icon_url'] ?? '');
            $svg_code = trim($_POST['svg_code'] ?? '');
            $sort_order = intval($_POST['sort_order'] ?? 0);
            
            if ($icon_type === 'svg') {
                $icon_type = 'custom';
                $icon_url = '';
            } elseif ($icon_type === 'custom') {
                $svg_code = '';
            }
            
            if (!empty($platform) && !empty($url)) {
                $newId = $db->insert('lty-social_links', [
                    'platform' => $platform,
                    'url' => $url,
                    'icon_type' => $icon_type,
                    'icon_url' => $icon_url,
                    'svg_code' => $svg_code,
                    'sort_order' => $sort_order,
                    'status' => 1
                ]);
                
                OperationLog::create('contact', '创建社交媒体链接：' . $platform, 'social_link', $newId, ['platform' => $platform, 'url' => $url]);
                set_flash_message('success', '社交媒体链接添加成功');
            } else {
                set_flash_message('error', '平台名称和链接不能为空');
            }
            redirect('lty-admin/contact.php#social');
            exit;
        }
        
        if ($action === 'update_social_link') {
            $id = intval($_POST['social_id'] ?? 0);
            $platform = trim($_POST['platform'] ?? '');
            $url = trim($_POST['url'] ?? '');
            $icon_type = $_POST['icon_type'] ?? 'default';
            $icon_url = trim($_POST['icon_url'] ?? '');
            $svg_code = trim($_POST['svg_code'] ?? '');
            $sort_order = intval($_POST['sort_order'] ?? 0);
            
            if ($icon_type === 'svg') {
                $icon_type = 'custom';
                $icon_url = '';
            } elseif ($icon_type === 'custom') {
                $svg_code = '';
            }
            
            if ($id > 0 && !empty($platform) && !empty($url)) {
                $db->update('lty-social_links', [
                    'platform' => $platform,
                    'url' => $url,
                    'icon_type' => $icon_type,
                    'icon_url' => $icon_url,
                    'svg_code' => $svg_code,
                    'sort_order' => $sort_order
                ], 'id = ?', [$id]);
                
                OperationLog::update('contact', '更新社交媒体链接：' . $platform, 'social_link', $id, ['platform' => $platform, 'url' => $url]);
                set_flash_message('success', '社交媒体链接更新成功');
            } else {
                set_flash_message('error', '参数错误');
            }
            redirect('lty-admin/contact.php#social');
            exit;
        }
        
        if ($action === 'delete_social_link') {
            $id = intval($_POST['social_id'] ?? 0);
            
            if ($id > 0) {
                $link = $db->fetchOne("SELECT platform FROM `lty-social_links` WHERE id = ?", [$id]);
                $db->delete('lty-social_links', 'id = ?', [$id]);
                OperationLog::delete('contact', '删除社交媒体链接：' . ($link['platform'] ?? $id), 'social_link', $id);
                set_flash_message('success', '社交媒体链接删除成功');
            }
            redirect('lty-admin/contact.php#social');
            exit;
        }
        
        if ($action === 'toggle_social_link') {
            $id = intval($_POST['social_id'] ?? 0);
            $status = intval($_POST['status'] ?? 0);
            
            if ($id > 0) {
                $db->update('lty-social_links', ['status' => $status], 'id = ?', [$id]);
                set_flash_message('success', '社交媒体链接状态更新成功');
            }
            redirect('lty-admin/contact.php#social');
            exit;
        }
        
        if ($action === 'update_social_order') {
            header('Content-Type: application/json');
            $orders = $_POST['orders'] ?? [];
            
            if (!empty($orders) && is_array($orders)) {
                foreach ($orders as $order => $id) {
                    $db->update('lty-social_links', ['sort_order' => intval($order) + 1], 'id = ?', [intval($id)]);
                }
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => '无效的数据']);
            }
            exit;
        }
        
        redirect('lty-admin/contact.php');
        exit;
    }
    
    if (isset($_POST['save_title_settings'])) {
        $contact_title = trim($_POST['contact_title'] ?? '');
        $contact_subtitle = trim($_POST['contact_subtitle'] ?? '');
        
        Settings::set('contact_title', $contact_title);
        Settings::set('contact_subtitle', $contact_subtitle);
        
        OperationLog::update('contact', '更新联系我们页面标题设置', 'settings', null, ['title' => $contact_title, 'subtitle' => $contact_subtitle]);
        set_flash_message('success', '页面标题设置保存成功');
        redirect('lty-admin/contact.php');
        exit;
    }
    
    $contact_email = trim($_POST['contact_email'] ?? '');
    $contact_phone = trim($_POST['contact_phone'] ?? '');
    $contact_address = trim($_POST['contact_address'] ?? '');
    $business_email = trim($_POST['business_email'] ?? '');
    $hr_email = trim($_POST['hr_email'] ?? '');
    $media_email = trim($_POST['media_email'] ?? '');
    $working_hours = trim($_POST['working_hours'] ?? '');
    $qq_number = trim($_POST['qq_number'] ?? '');
    $contact_intro = trim($_POST['contact_intro'] ?? '');
    
    $stat_response = trim($_POST['stat_response'] ?? '24h');
    $stat_satisfaction = trim($_POST['stat_satisfaction'] ?? '98%');
    $stat_cases = trim($_POST['stat_cases'] ?? '500+');
    
    Settings::set('contact_email', $contact_email);
    Settings::set('contact_phone', $contact_phone);
    Settings::set('contact_address', $contact_address);
    Settings::set('business_email', $business_email);
    Settings::set('hr_email', $hr_email);
    Settings::set('media_email', $media_email);
    Settings::set('working_hours', $working_hours);
    Settings::set('qq_number', $qq_number);
    Settings::set('contact_intro', $contact_intro);
    Settings::set('stat_response', $stat_response);
    Settings::set('stat_satisfaction', $stat_satisfaction);
    Settings::set('stat_cases', $stat_cases);
    
    OperationLog::update('contact', '更新联系我们设置', 'settings', null, ['email' => $contact_email, 'phone' => $contact_phone]);
    set_flash_message('success', '联系我们设置保存成功');
    redirect('lty-admin/contact.php');
}

function getSocialLinks() {
    $db = Database::getInstance();
    $socialLinks = $db->fetchAll("SELECT * FROM `lty-social_links` ORDER BY sort_order ASC, id ASC");
    return $socialLinks ? $socialLinks : [];
}

function getContactTypes() {
    $db = Database::getInstance();
    $contactTypes = $db->fetchAll("SELECT * FROM `lty-contact_types` ORDER BY sort_order ASC, id ASC");
    return $contactTypes ? $contactTypes : [];
}

function getNextSortOrder() {
    $db = Database::getInstance();
    $result = $db->fetchOne("SELECT MAX(sort_order) as max_order FROM `lty-social_links`");
    return ($result && $result['max_order'] !== null) ? intval($result['max_order']) + 1 : 1;
}

function getDefaultSocialIcon($platform) {
    $icons = [
        '微信' => '<svg viewBox="0 0 1024 1024"><path d="M664.250054 368.541681c10.015098 0 19.892049 0.732687 29.627708 2.038659-26.647917-122.810739-159.358451-214.077703-310.826188-214.077703-169.353083 0-308.085774 114.232694-308.085774 259.274068 0 83.70765 46.165436 152.460344 123.281791 205.78483l-30.838712 91.730191 107.688658-53.455469c38.560521 7.53665 69.466819 15.308511 107.954242 15.308511 9.661473 0 19.230033-0.467618 28.752825-1.216492-6.025089-20.36584-9.278883-41.723264-9.278883-63.862492C402.525721 476.487004 517.108796 368.541681 664.250054 368.541681zM501.760858 297.916c23.200395 0 38.557309 15.120868 38.557309 38.060875 0 22.811407-15.356914 38.232825-38.557309 38.232825-23.107276 0-46.339394-15.421418-46.339394-38.232825C455.421464 313.036868 478.653582 297.916 501.760858 297.916zM284.536595 374.2097c-23.107276 0-46.456607-15.421418-46.456607-38.232825 0-22.940007 23.349331-38.060875 46.456607-38.060875 23.076489 0 38.464059 15.120868 38.464059 38.060875C323.000654 358.788282 307.613084 374.2097 284.536595 374.2097z" fill="currentColor"/><path d="M945.448458 606.151333c0-121.888048-123.386635-221.236753-262.224529-221.236753-146.836891 0-262.376678 99.348705-262.376678 221.236753 0 122.017528 115.539787 221.236753 262.376678 221.236753 30.662556 0 61.619693-7.617744 92.118724-15.30442l84.513836 45.864058-23.226723-76.170797C899.442573 735.776599 945.448458 674.903947 945.448458 606.151333zM621.53249 570.544601c-15.356914 0-30.838712-15.046258-30.838712-30.563732 0-15.335378 15.481798-30.689082 30.838712-30.689082 23.200395 0 38.464059 15.353704 38.464059 30.689082C659.996549 555.498343 644.732885 570.544601 621.53249 570.544601zM744.699473 570.544601c-15.231008 0-30.682386-15.046258-30.682386-30.563732 0-15.335378 15.451378-30.689082 30.682386-30.689082 23.076489 0 38.557309 15.353704 38.557309 30.689082C783.256782 555.498343 767.775962 570.544601 744.699473 570.544601z" fill="currentColor"/></svg>',
        '微博' => '<svg viewBox="0 0 1024 1024"><path d="M877.7472 389.0432c-17.2032-5.9392-28.8768-9.8304-19.968-35.4816 19.3536-55.808 21.3504-103.8848 0.1024-138.24-39.8848-64.512-148.992-61.0304-273.7152-1.7408 0 0-39.2192 17.1008-29.184-13.8752 19.2-61.3376 16.384-112.64-13.568-142.1824-67.9424-67.0784-248.6272 2.4064-403.6096 155.2384C65.3312 318.0032 12.8 418.8672 12.8 505.6c0 166.0416 214.3232 266.9056 423.936 266.9056 274.7392 0 457.472-158.3104 457.472-284.0576 0-75.9808-64.7168-119.0912-116.4544-99.4048zM437.3504 707.6352c-161.792 15.8208-301.3632-56.7808-311.6544-162.2016-10.2912-105.3696 112.2304-203.5712 274.016-219.4432 161.8432-15.8208 301.3632 56.7808 311.6544 162.1504C721.6576 593.5104 599.1424 691.7632 437.3504 707.6352z" fill="currentColor"/><path d="M411.1872 381.5936c-78.4896 16.384-132.6592 76.8-121.0368 134.8608 11.5712 58.112 84.0704 92.8256 162.5088 76.4416 78.5408-16.384 132.6592-76.8 121.088-134.912C562.1248 399.9744 489.6768 365.2096 411.1872 381.5936zM369.7152 514.8672c-24.1152 5.0176-46.8992-7.2704-50.8928-27.4944-3.9424-20.1728 12.2368-40.6016 36.352-45.6704 24.1664-5.0176 46.9504 7.2704 50.8928 27.4944C410.0672 489.3696 393.9328 509.7984 369.7152 514.8672zM424.192 469.6064c-9.0112 1.8944-17.5616-3.1744-19.1488-11.264-1.536-8.0384 4.5056-16.1792 13.5168-18.0224 9.0624-1.8944 17.6128 3.1232 19.2 11.2128C439.3472 459.6224 433.2544 467.712 424.192 469.6064z" fill="currentColor"/><path d="M948.1728 322.56c-25.1392-27.1872-62.3104-34.1504-95.8464-21.7088-9.5744 3.4816-14.4896 14.0288-10.9568 23.6032 3.4816 9.5744 14.0288 14.4896 23.6032 10.9568 19.2-6.912 40.192-3.0208 54.4768 12.3904 14.2336 15.36 17.408 36.9152 9.0112 56.1152-4.0448 9.2672 0.3072 20.0192 9.5744 24.064 9.2672 4.0448 20.0192-0.3072 24.064-9.5744C978.5856 384.9216 973.312 349.7472 948.1728 322.56z" fill="currentColor"/></svg>',
        'LinkedIn' => '<svg viewBox="0 0 1024 1024"><path d="M880 112H144c-17.7 0-32 14.3-32 32v736c0 17.7 14.3 32 32 32h736c17.7 0 32-14.3 32-32V144c0-17.7-14.3-32-32-32zM349.3 793.7H230.6V411.9h118.7v381.8z m-59.3-434c-38 0-68.8-30.8-68.8-68.8s30.8-68.8 68.8-68.8c38 0 68.8 30.8 68.8 68.8s-30.8 68.8-68.8 68.8z m447.7 434H619.2V533.8c0-45.4-16.4-76.5-57.1-76.5-31.1 0-49.7 21-57.9 41.3-3 7.3-3.7 17.3-3.7 27.4v267.7H381.8s1.5-388.1 0-428.3h118.7v60.7c15.8-24.3 43.9-58.9 106.8-58.9 78 0 136.4 51 136.4 160.6v266z" fill="currentColor"/></svg>',
        'GitHub' => '<svg viewBox="0 0 1024 1024"><path d="M512 42.666667C232.533333 42.666667 6.4 268.8 6.4 548.266667c0 222.293333 144.213333 410.88 344.32 477.013333 25.173333 4.693333 34.346667-10.88 34.346667-24.213333 0-11.946667-0.426667-51.626667-0.64-93.653334-140.117333 30.506667-169.6-59.52-169.6-59.52-22.869333-58.133333-55.893333-73.6-55.893334-73.6-45.653333-31.146667 3.456-30.506667 3.456-30.506666 50.517333 3.584 77.141333 51.84 77.141334 51.84 44.885333 76.970667 117.76 54.698667 146.474666 41.813333 4.565333-32.512 17.578667-54.741333 31.957334-67.306667-111.829333-12.8-229.461333-55.893333-229.461334-248.746666 0-54.954667 19.626667-99.84 51.84-135.125334-5.162667-12.757333-22.442667-64.085333 4.906667-133.653333 0 0 42.325333-13.525333 138.624 51.626667 40.192-11.178667 83.285333-16.725333 126.122667-16.938667 42.794667 0.213333 85.930667 5.76 126.165333 16.938667 96.213333-65.152 138.453333-51.626667 138.453333-51.626667 27.434667 69.568 10.154667 120.896 4.992 133.653333 32.298667 35.285333 51.797333 80.170667 51.797334 135.125334 0 193.365333-117.845333 235.776-230.058667 248.277333 18.090667 15.658667 34.176 46.464 34.176 93.653333 0 67.669333-0.597333 122.112-0.597333 138.88 0 13.44 9.088 29.098667 34.602666 24.149334C873.344 958.976 1017.6 770.474667 1017.6 548.266667 1017.6 268.8 791.466667 42.666667 512 42.666667z" fill="currentColor"/></svg>',
        '抖音' => '<svg viewBox="0 0 1024 1024"><path d="M512 128c-212.1 0-384 171.9-384 384s171.9 384 384 384 384-171.9 384-384-171.9-384-384-384z m0 686c-166.6 0-302-135.4-302-302s135.4-302 302-302 302 135.4 302 302-135.4 302-302 302z" fill="currentColor"/><path d="M680.5 396.5c-34.6-6.9-62.5-34.8-69.4-69.4-1.1-5.5-1.7-11.2-1.7-17.1h-55.4v288.4c0 33.1-26.8 59.9-59.9 59.9s-59.9-26.8-59.9-59.9 26.8-59.9 59.9-59.9c6.2 0 12.2 1 17.8 2.7v-56.6c-5.8-0.8-11.7-1.3-17.8-1.3-63.5 0-115.1 51.6-115.1 115.1s51.6 115.1 115.1 115.1 115.1-51.6 115.1-115.1V463.8c21.8 15.5 48.4 24.6 77.1 24.6v-55.4c-1.9 0-3.8-0.1-5.8-0.2v-36.3z" fill="currentColor"/></svg>',
        '小红书' => '<svg viewBox="0 0 1024 1024"><path d="M512 128c-212.1 0-384 171.9-384 384s171.9 384 384 384 384-171.9 384-384-171.9-384-384-384z m0 686c-166.6 0-302-135.4-302-302s135.4-302 302-302 302 135.4 302 302-135.4 302-302 302z" fill="currentColor"/><path d="M640 384h-256c-17.7 0-32 14.3-32 32v192c0 17.7 14.3 32 32 32h256c17.7 0 32-14.3 32-32V416c0-17.7-14.3-32-32-32z m-128 192c-35.3 0-64-28.7-64-64s28.7-64 64-64 64 28.7 64 64-28.7 64-64 64z" fill="currentColor"/></svg>',
        'QQ' => '<svg viewBox="0 0 1024 1024"><path d="M512 128c-212.1 0-384 171.9-384 384s171.9 384 384 384 384-171.9 384-384-171.9-384-384-384z m0 686c-166.6 0-302-135.4-302-302s135.4-302 302-302 302 135.4 302 302-135.4 302-302 302z" fill="currentColor"/><path d="M704 544c0-106-86-192-192-192s-192 86-192 192c0 71.4 39 133.6 96.8 166.8-6.4 18.8-24.8 32.2-46.8 32.2-28 0-50.6-22.6-50.6-50.6 0-14.4 6-27.4 15.6-36.6-30.4-26.4-49.6-65-49.6-108 0-79.4 64.4-143.8 143.8-143.8s143.8 64.4 143.8 143.8c0 43-19.2 81.6-49.6 108 9.6 9.2 15.6 22.2 15.6 36.6 0 28-22.6 50.6-50.6 50.6-22 0-40.4-13.4-46.8-32.2C665 677.6 704 615.4 704 544z" fill="currentColor"/></svg>',
        'B站' => '<svg viewBox="0 0 1024 1024"><path d="M777.8 384h-531.6c-35.3 0-64 28.7-64 64v192c0 35.3 28.7 64 64 64h531.6c35.3 0 64-28.7 64-64V448c0-35.3-28.7-64-64-64zM352 576c-17.7 0-32-14.3-32-32s14.3-32 32-32 32 14.3 32 32-14.3 32-32 32z m320 0c-17.7 0-32-14.3-32-32s14.3-32 32-32 32 14.3 32 32-14.3 32-32 32z" fill="currentColor"/><path d="M288 320l96-96h256l96 96" stroke="currentColor" stroke-width="48" fill="none"/></svg>',
        'Twitter' => '<svg viewBox="0 0 1024 1024"><path d="M928 254.4c-30.6 13.2-63.9 22.8-98.6 27 35.4-21.2 62.4-54.6 75.2-94.6-33.2 19.6-69.8 33.8-108.8 41.4C763.4 195 718.4 172 668 172c-94.6 0-171.4 76.8-171.4 171.4 0 13.4 1.4 26.6 4.4 39.2C356.6 375.6 226.4 305 139.4 196c-14.8 25.4-23.2 54.6-23.2 86 0 59.4 30.2 111.8 76.2 142.4-28-0.8-54.6-8.6-77.8-21.4v2c0 83 59 152.2 137.4 167.8-14.4 4-29.4 6-45 6-11 0-21.8-1-32.2-3 21.8 67.6 85 116.8 159.8 118-58.6 46-132.2 73.4-212.4 73.4-13.8 0-27.4-0.8-40.8-2.4 75.6 48.6 165.4 76.8 262 76.8 314.4 0 486.4-260.4 486.4-486.4 0-7.4-0.2-14.8-0.6-22 33.4-24.2 62.4-54.4 85.2-88.6z" fill="currentColor"/></svg>',
        'Facebook' => '<svg viewBox="0 0 1024 1024"><path d="M928 512c0-229.8-186.2-416-416-416S96 282.2 96 512c0 207.6 152.4 379.4 352 411.4V640h-96v-128h96v-96c0-96 57.6-149.6 145.2-149.6 42 0 86 7.6 86 7.6v94.4h-48.4c-47.8 0-62.8 29.8-62.8 60.2V512h106.8l-17 128H560v283.4c199.6-32 352-203.8 352-411.4z" fill="currentColor"/></svg>',
        'Instagram' => '<svg viewBox="0 0 1024 1024"><path d="M512 96c-114.7 0-129.1 0.5-174.2 2.5-45.1 2.1-75.9 9.2-102.9 19.7-27.9 10.8-51.5 25.3-75.1 48.9-23.6 23.6-38.1 47.2-48.9 75.1-10.5 27-17.6 57.8-19.7 102.9C89.5 390.9 89 405.3 89 520s0.5 129.1 2.5 174.2c2.1 45.1 9.2 75.9 19.7 102.9 10.8 27.9 25.3 51.5 48.9 75.1 23.6 23.6 47.2 38.1 75.1 48.9 27 10.5 57.8 17.6 102.9 19.7 45.1 2.1 59.5 2.5 174.2 2.5s129.1-0.5 174.2-2.5c45.1-2.1 75.9-9.2 102.9-19.7 27.9-10.8 51.5-25.3 75.1-48.9 23.6-23.6 38.1-47.2 48.9-75.1 10.5-27 17.6-57.8 19.7-102.9 2.1-45.1 2.5-59.5 2.5-174.2s-0.5-129.1-2.5-174.2c-2.1-45.1-9.2-75.9-19.7-102.9-10.8-27.9-25.3-51.5-48.9-75.1-23.6-23.6-47.2-38.1-75.1-48.9-27-10.5-57.8-17.6-102.9-19.7C641.1 96.5 626.7 96 512 96z m0 76.2c112.8 0 126.2 0.4 170.8 2.5 41.2 1.9 63.6 8.8 78.5 14.6 19.7 7.7 33.8 16.9 48.6 31.7 14.8 14.8 24 28.9 31.7 48.6 5.8 14.9 12.7 37.3 14.6 78.5 2.1 44.6 2.5 58 2.5 170.8s-0.4 126.2-2.5 170.8c-1.9 41.2-8.8 63.6-14.6 78.5-7.7 19.7-16.9 33.8-31.7 48.6-14.8 14.8-28.9 24-48.6 31.7-14.9 5.8-37.3 12.7-78.5 14.6-44.6 2.1-58 2.5-170.8 2.5s-126.2-0.4-170.8-2.5c-41.2-1.9-63.6-8.8-78.5-14.6-19.7-7.7-33.8-16.9-48.6-31.7-14.8-14.8-24-28.9-31.7-48.6-5.8-14.9-12.7-37.3-14.6-78.5-2.1-44.6-2.5-58-2.5-170.8s0.4-126.2 2.5-170.8c1.9-41.2 8.8-63.6 14.6-78.5 7.7-19.7 16.9-33.8 31.7-48.6 14.8-14.8 28.9-24 48.6-31.7 14.9-5.8 37.3 12.7 78.5 14.6 44.6 2.1 58 2.5 170.8 2.5z m0 129.8c-119.8 0-217 97.2-217 217s97.2 217 217 217 217-97.2 217-217-97.2-217-217-217z m0 357.8c-77.7 0-140.8-63.1-140.8-140.8s63.1-140.8 140.8-140.8 140.8 63.1 140.8 140.8-63.1 140.8-140.8 140.8z m276.2-366.2c0 28-22.7 50.7-50.7 50.7s-50.7-22.7-50.7-50.7 22.7-50.7 50.7-50.7 50.7 22.7 50.7 50.7z" fill="currentColor"/></svg>',
        'YouTube' => '<svg viewBox="0 0 1024 1024"><path d="M941.3 296.1c-10.3-38.6-40.6-69-79.2-79.2C792.2 200 512 200 512 200s-280.2 0-350.1 16.9c-38.6 10.3-69 40.6-79.2 79.2C66 366 66 512 66 512s0 146 16.9 215.9c10.3 38.6 40.6 69 79.2 79.2C231.8 824 512 824 512 824s280.2 0 350.1-16.9c38.6-10.3 69-40.6 79.2-79.2C958 658 958 512 958 512s0-146-16.7-215.9zM423 646V378l232 134-232 134z" fill="currentColor"/></svg>',
    ];
    
    foreach ($icons as $name => $icon) {
        if (stripos($platform, $name) !== false) {
            return $icon;
        }
    }
    
    return '<svg viewBox="0 0 1024 1024"><path d="M512 85.333333c-235.733333 0-426.666667 190.933333-426.666667 426.666667s190.933333 426.666667 426.666667 426.666667 426.666667-190.933333 426.666667-426.666667S747.733333 85.333333 512 85.333333z m0 768c-188.8 0-341.333333-152.533333-341.333333-341.333333s152.533333-341.333333 341.333333-341.333333 341.333333 152.533333 341.333333 341.333333-152.533333 341.333333-341.333333 341.333333z" fill="currentColor"/><path d="M682.666667 512h-128V341.333333c0-23.466667-19.2-42.666667-42.666667-42.666666s-42.666667 19.2-42.666667 42.666666v213.333334c0 23.466667 19.2 42.666667 42.666667 42.666666h170.666667c23.466667 0 42.666667-19.2 42.666666-42.666666s-19.2-42.666667-42.666666-42.666667z" fill="currentColor"/></svg>';
}

$socialLinks = getSocialLinks();
$contactTypes = getContactTypes();
$success = get_flash_message('success');
$error = get_flash_message('error');

$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>联系我们管理 - <?php echo htmlspecialchars(Settings::get('site_name', '官网系统')); ?></title>
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        .page-container { max-width: 1200px; margin: 0 auto; padding: 0 16px; }
        @media (max-width: 768px) { .page-container { padding: 0 12px; } }
        .contact-hero { background: linear-gradient(135deg, #f59e0b 0%, #d97706 50%, #fbbf24 100%); border-radius: 24px; padding: 48px; margin-bottom: 32px; color: white; position: relative; overflow: hidden; box-shadow: 0 20px 60px rgba(245, 158, 11, 0.3); }
        @media (max-width: 768px) { .contact-hero { padding: 32px 24px; border-radius: 16px; margin-bottom: 24px; } }
        .hero-content { position: relative; z-index: 1; }
        .hero-title { font-size: 32px; font-weight: 800; margin-bottom: 12px; }
        @media (max-width: 768px) { .hero-title { font-size: 24px; } }
        .hero-subtitle { font-size: 16px; opacity: 0.95; }
        @media (max-width: 768px) { .hero-subtitle { font-size: 14px; } }
        .main-card { background: white; border-radius: 24px; box-shadow: 0 4px 24px rgba(0,0,0,0.06); overflow: visible; }
        @media (max-width: 768px) { .main-card { border-radius: 16px; } }
        .card-tabs { display: flex; background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 8px; gap: 8px; overflow-x: auto; }
        .card-tab { flex: 1; padding: 16px 24px; border: none; background: transparent; border-radius: 12px; cursor: pointer; font-size: 14px; font-weight: 600; color: #64748b; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 10px; white-space: nowrap; min-width: max-content; }
        .card-tab svg { width: 18px; height: 18px; }
        .card-tab:hover { background: rgba(245, 158, 11, 0.08); color: #f59e0b; }
        .card-tab.active { background: white; color: #f59e0b; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
        .tab-panel { display: none; padding: 32px; }
        .tab-panel.active { display: block; }
        @media (max-width: 768px) { .tab-panel { padding: 24px 20px; } }
        .section-title { font-size: 18px; font-weight: 700; color: #1e293b; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; }
        .section-title::before { content: ''; width: 4px; height: 24px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border-radius: 2px; }
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; margin-bottom: 24px; }
        @media (max-width: 768px) { .form-row { gap: 20px; margin-bottom: 20px; } }
        .form-group { margin-bottom: 24px; }
        .form-group label { display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px; }
        .form-group input, .form-group textarea { width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 14px; transition: all 0.3s ease; background: #fafafa; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #f59e0b; background: white; box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.1); }
        .form-group textarea { min-height: 120px; resize: vertical; }
        .social-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        @media (max-width: 768px) { .social-grid { grid-template-columns: 1fr; } }
        .social-card { background: #fafafa; border: 2px solid #e5e7eb; border-radius: 16px; padding: 20px; transition: all 0.3s ease; cursor: grab; position: relative; }
        .social-card:hover { border-color: #f59e0b; box-shadow: 0 4px 16px rgba(245, 158, 11, 0.15); }
        .social-card.disabled { opacity: 0.6; }
        .social-card.dragging { opacity: 0.5; transform: scale(1.02); box-shadow: 0 10px 30px rgba(0,0,0,0.2); cursor: grabbing; }
        .social-card.drag-over { border-color: #f59e0b; border-style: dashed; }
        .drag-handle { position: absolute; top: 8px; left: 8px; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; color: #9ca3af; cursor: grab; opacity: 0; transition: opacity 0.2s; }
        .social-card:hover .drag-handle { opacity: 1; }
        .drag-handle:active { cursor: grabbing; }
        .social-header { display: flex; align-items: center; gap: 16px; margin-bottom: 16px; }
        .social-icon { width: 48px; height: 48px; border-radius: 12px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); display: flex; align-items: center; justify-content: center; color: white; }
        .social-icon svg { width: 24px; height: 24px; }
        .social-icon img { width: 24px; height: 24px; object-fit: contain; }
        .social-info { flex: 1; }
        .social-name { font-size: 16px; font-weight: 700; color: #1e293b; margin-bottom: 4px; }
        .social-url { font-size: 13px; color: #64748b; word-break: break-all; }
        .social-status { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-active { background: #dcfce7; color: #16a34a; }
        .status-inactive { background: #fee2e2; color: #dc2626; }
        .social-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .empty-state { text-align: center; padding: 60px 20px; color: #64748b; }
        .empty-state svg { width: 64px; height: 64px; margin-bottom: 16px; opacity: 0.5; }
        .empty-state h3 { font-size: 18px; font-weight: 600; margin-bottom: 8px; color: #475569; }
        .empty-state p { font-size: 14px; margin-bottom: 24px; }
        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 2000; }
        .modal-overlay.active { display: flex; }
        .social-modal { background: white; border-radius: 24px; width: 90%; max-width: 560px; max-height: 90vh; overflow-y: auto; transform: scale(0.9); transition: all 0.3s ease; }
        .modal-overlay.active .social-modal { transform: scale(1); }
        .social-modal-header { padding: 24px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between; }
        .social-modal-title { font-size: 20px; font-weight: 700; color: #1e293b; }
        .social-modal-close { width: 36px; height: 36px; border: none; background: #f1f5f9; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; }
        .social-modal-close:hover { background: #e2e8f0; }
        .social-modal-body { padding: 24px; }
        .social-modal-footer { padding: 16px 24px; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 12px; }
        .icon-type-selector { display: flex; gap: 12px; margin-bottom: 16px; }
        .icon-type-option { flex: 1; padding: 16px; border: 2px solid #e5e7eb; border-radius: 12px; cursor: pointer; text-align: center; transition: all 0.3s ease; }
        .icon-type-option:hover { border-color: #f59e0b; }
        .icon-type-option.active { border-color: #f59e0b; background: rgba(245, 158, 11, 0.05); }
        .icon-type-option input { display: none; }
        .icon-type-option svg { width: 32px; height: 32px; margin-bottom: 8px; color: #64748b; }
        .icon-type-option.active svg { color: #f59e0b; }
        .icon-type-option span { display: block; font-size: 13px; font-weight: 600; color: #64748b; }
        .icon-type-option.active span { color: #f59e0b; }
        .icon-preview { width: 80px; height: 80px; border-radius: 16px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); display: flex; align-items: center; justify-content: center; color: white; margin-bottom: 16px; }
        .icon-preview svg { width: 40px; height: 40px; }
        .icon-preview img { width: 40px; height: 40px; object-fit: contain; }
        .alert { padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; animation: slideIn 0.3s ease; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeOut { from { opacity: 1; } to { opacity: 0; transform: translateY(-10px); } }
        .alert.fade-out { animation: fadeOut 0.3s ease forwards; }
        .alert-success { background: #dcfce7; color: #16a34a; }
        .alert-error { background: #fee2e2; color: #dc2626; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>联系我们管理</h1>
            <div style="display: flex; gap: 10px;">
                <button onclick="openTitleModal()" class="btn btn-outline">页面标题设置</button>
            </div>
        </div>
        
        <div class="content-card">
            <div class="card-body">
                <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <div class="tabs-container">
                    <div class="tabs-nav">
                        <button class="tab-btn active" data-tab="basic">基本信息</button>
                        <button class="tab-btn" data-tab="types">联系类型</button>
                        <button class="tab-btn" data-tab="social">社交媒体</button>
                    </div>
                    
                    <div class="tab-content active" id="tab-basic">
                        <form method="POST" action="">
                            <div class="form-section">
                                <h3>联系信息</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="contact_email">主要邮箱</label>
                                        <input type="email" id="contact_email" name="contact_email" value="<?php echo htmlspecialchars(Settings::get('contact_email')); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="contact_phone">联系电话</label>
                                        <input type="text" id="contact_phone" name="contact_phone" value="<?php echo htmlspecialchars(Settings::get('contact_phone')); ?>">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="contact_address">公司地址</label>
                                    <textarea id="contact_address" name="contact_address" rows="2"><?php echo htmlspecialchars(Settings::get('contact_address')); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3>部门邮箱</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="business_email">商务合作邮箱</label>
                                        <input type="email" id="business_email" name="business_email" value="<?php echo htmlspecialchars(Settings::get('business_email')); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="hr_email">招聘邮箱</label>
                                        <input type="email" id="hr_email" name="hr_email" value="<?php echo htmlspecialchars(Settings::get('hr_email')); ?>">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="media_email">媒体合作邮箱</label>
                                        <input type="email" id="media_email" name="media_email" value="<?php echo htmlspecialchars(Settings::get('media_email')); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="qq_number">QQ号码</label>
                                        <input type="text" id="qq_number" name="qq_number" value="<?php echo htmlspecialchars(Settings::get('qq_number')); ?>">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="working_hours">工作时间</label>
                                    <input type="text" id="working_hours" name="working_hours" value="<?php echo htmlspecialchars(Settings::get('working_hours') ?: '周一至周五 9:00-18:00'); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="contact_intro">页面介绍</label>
                                    <textarea id="contact_intro" name="contact_intro" rows="4"><?php echo htmlspecialchars(Settings::get('contact_intro')); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3>统计数据</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="stat_response">快速响应</label>
                                        <input type="text" id="stat_response" name="stat_response" value="<?php echo htmlspecialchars(Settings::get('stat_response') ?: '24h'); ?>" placeholder="如: 24h">
                                    </div>
                                    <div class="form-group">
                                        <label for="stat_satisfaction">客户满意</label>
                                        <input type="text" id="stat_satisfaction" name="stat_satisfaction" value="<?php echo htmlspecialchars(Settings::get('stat_satisfaction') ?: '98%'); ?>" placeholder="如: 98%">
                                    </div>
                                    <div class="form-group">
                                        <label for="stat_cases">成功案例</label>
                                        <input type="text" id="stat_cases" name="stat_cases" value="<?php echo htmlspecialchars(Settings::get('stat_cases') ?: '500+'); ?>" placeholder="如: 500+">
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">保存设置</button>
                        </form>
                    </div>
                    
                    <div class="tab-content" id="tab-types">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                            <h3 class="section-title" style="margin-bottom: 0;">联系类型管理</h3>
                            <button type="button" class="btn btn-primary" onclick="openTypeModal()">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                添加类型
                            </button>
                        </div>
                        
                        <?php if (empty($contactTypes)): ?>
                        <div class="empty-state">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"></path>
                            </svg>
                            <h3>暂无联系类型</h3>
                            <p>点击上方按钮添加第一个联系类型</p>
                        </div>
                        <?php else: ?>
                        <div class="social-grid" id="typeGrid">
                            <?php foreach ($contactTypes as $type): ?>
                            <div class="social-card" data-id="<?php echo $type['id']; ?>" draggable="true">
                                <div class="drag-handle" title="拖动排序">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="9" cy="5" r="1"></circle>
                                        <circle cx="9" cy="12" r="1"></circle>
                                        <circle cx="9" cy="19" r="1"></circle>
                                        <circle cx="15" cy="5" r="1"></circle>
                                        <circle cx="15" cy="12" r="1"></circle>
                                        <circle cx="15" cy="19" r="1"></circle>
                                    </svg>
                                </div>
                                <div class="social-header">
                                    <div class="social-info">
                                        <div class="social-name"><?php echo htmlspecialchars($type['name']); ?></div>
                                        <div class="social-url"><?php echo htmlspecialchars($type['description'] ?: '暂无描述'); ?></div>
                                    </div>
                                    <span class="social-status <?php echo $type['status'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $type['status'] ? '启用' : '禁用'; ?>
                                    </span>
                                </div>
                                <div class="social-actions">
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle_contact_type">
                                        <input type="hidden" name="type_id" value="<?php echo $type['id']; ?>">
                                        <input type="hidden" name="status" value="<?php echo $type['status'] ? 0 : 1; ?>">
                                        <button type="submit" class="btn btn-sm <?php echo $type['status'] ? 'btn-secondary' : 'btn-success'; ?>">
                                            <?php echo $type['status'] ? '禁用' : '启用'; ?>
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="openEditTypeModal(<?php echo htmlspecialchars(json_encode($type), ENT_QUOTES); ?>)">
                                        编辑
                                    </button>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="action" value="delete_contact_type">
                                        <input type="hidden" name="type_id" value="<?php echo $type['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('确定要删除这个联系类型吗？');">
                                            删除
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="tab-content" id="tab-social">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                            <h3 class="section-title" style="margin-bottom: 0;">社交媒体链接</h3>
                            <button type="button" class="btn btn-primary" onclick="openAddModal()">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                添加链接
                            </button>
                        </div>
                        
                        <?php if (empty($socialLinks)): ?>
                        <div class="empty-state">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>
                            </svg>
                            <h3>暂无社交媒体链接</h3>
                            <p>点击上方按钮添加您的社交媒体链接</p>
                        </div>
                        <?php else: ?>
                        <div class="social-grid" id="socialGrid">
                            <?php foreach ($socialLinks as $link): ?>
                            <div class="social-card <?php echo $link['status'] ? '' : 'disabled'; ?>" data-id="<?php echo $link['id']; ?>" draggable="true">
                                <div class="drag-handle" title="拖动排序">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="9" cy="5" r="1"></circle>
                                        <circle cx="9" cy="12" r="1"></circle>
                                        <circle cx="9" cy="19" r="1"></circle>
                                        <circle cx="15" cy="5" r="1"></circle>
                                        <circle cx="15" cy="12" r="1"></circle>
                                        <circle cx="15" cy="19" r="1"></circle>
                                    </svg>
                                </div>
                                <div class="social-header">
                                    <div class="social-icon">
                                        <?php 
                                        if ($link['icon_type'] === 'custom' && !empty($link['svg_code'])) {
                                            echo $link['svg_code'];
                                        } elseif ($link['icon_type'] === 'custom' && !empty($link['icon_url'])) {
                                            echo '<img src="' . htmlspecialchars($link['icon_url']) . '" alt="' . htmlspecialchars($link['platform']) . '">';
                                        } else {
                                            echo getDefaultSocialIcon($link['platform']);
                                        }
                                        ?>
                                    </div>
                                    <div class="social-info">
                                        <div class="social-name"><?php echo htmlspecialchars($link['platform']); ?></div>
                                        <div class="social-url"><?php echo htmlspecialchars($link['url']); ?></div>
                                    </div>
                                    <span class="social-status <?php echo $link['status'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $link['status'] ? '启用' : '禁用'; ?>
                                    </span>
                                </div>
                                <div class="social-actions">
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle_social_link">
                                        <input type="hidden" name="social_id" value="<?php echo $link['id']; ?>">
                                        <input type="hidden" name="status" value="<?php echo $link['status'] ? 0 : 1; ?>">
                                        <button type="submit" class="btn btn-sm <?php echo $link['status'] ? 'btn-secondary' : 'btn-success'; ?>">
                                            <?php echo $link['status'] ? '禁用' : '启用'; ?>
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($link), ENT_QUOTES); ?>)">
                                        编辑
                                    </button>
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('确定要删除这个社交媒体链接吗？');">
                                        <input type="hidden" name="action" value="delete_social_link">
                                        <input type="hidden" name="social_id" value="<?php echo $link['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">删除</button>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    
    <div id="socialModal" class="modal-overlay">
        <div class="social-modal">
            <div class="social-modal-header">
                <h3 class="social-modal-title" id="modalTitle">添加社交媒体链接</h3>
                <button type="button" class="social-modal-close" onclick="closeModal()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <form method="POST" action="" id="socialForm">
                <div class="social-modal-body">
                    <input type="hidden" name="action" id="formAction" value="add_social_link">
                    <input type="hidden" name="social_id" id="socialId" value="">
                    
                    <div class="form-group">
                        <label for="platform">平台名称 *</label>
                        <input type="text" id="platform" name="platform" required placeholder="例如：微信、微博、LinkedIn">
                    </div>
                    
                    <div class="form-group">
                        <label for="url">链接地址 *</label>
                        <input type="url" id="url" name="url" required placeholder="https://...">
                    </div>
                    
                    <div class="form-group">
                        <label>图标类型</label>
                        <div class="icon-type-selector">
                            <label class="icon-type-option active">
                                <input type="radio" name="icon_type" value="default" checked onchange="updateIconPreview()">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                </svg>
                                <span>默认图标</span>
                            </label>
                            <label class="icon-type-option">
                                <input type="radio" name="icon_type" value="custom" onchange="updateIconPreview()">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                    <polyline points="21 15 16 10 5 21"></polyline>
                                </svg>
                                <span>选择媒体</span>
                            </label>
                            <label class="icon-type-option">
                                <input type="radio" name="icon_type" value="svg" onchange="updateIconPreview()">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                                    <polyline points="2 17 12 22 22 17"></polyline>
                                    <polyline points="2 12 12 17 22 12"></polyline>
                                </svg>
                                <span>SVG代码</span>
                            </label>
                        </div>
                    </div>
                    
                    <div id="iconUrlGroup" class="form-group" style="display: none;">
                        <label for="icon_url">图标图片</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="url" id="icon_url" name="icon_url" placeholder="点击选择媒体或手动输入图片URL" style="flex: 1;">
                            <button type="button" class="btn btn-outline" onclick="openIconMediaPicker()">选择媒体</button>
                        </div>
                    </div>
                    
                    <div id="svgCodeGroup" class="form-group" style="display: none;">
                        <label for="svg_code">SVG代码</label>
                        <textarea id="svg_code" name="svg_code" rows="4" placeholder="粘贴SVG代码，例如：<svg>...</svg>"></textarea>
                    </div>
                    
                    <div class="form-group" style="display: none;">
                        <label for="sort_order">排序</label>
                        <input type="number" id="sort_order" name="sort_order" value="0" min="0">
                    </div>
                </div>
                <div class="social-modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">取消</button>
                    <button type="submit" class="btn btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="typeModal" class="modal-overlay">
        <div class="social-modal">
            <div class="social-modal-header">
                <h3 class="social-modal-title" id="typeModalTitle">添加联系类型</h3>
                <button type="button" class="social-modal-close" onclick="closeTypeModal()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <form method="POST" action="" id="typeForm">
                <div class="social-modal-body">
                    <input type="hidden" name="action" id="typeFormAction" value="add_contact_type">
                    <input type="hidden" name="type_id" id="typeId" value="">
                    
                    <div class="form-group">
                        <label for="type_name">类型名称 *</label>
                        <input type="text" id="type_name" name="name" required placeholder="例如：商务合作、技术顾问">
                    </div>
                    
                    <div class="form-group">
                        <label for="type_description">类型描述</label>
                        <textarea id="type_description" name="description" rows="3" placeholder="简要描述该类型的用途"></textarea>
                    </div>
                </div>
                <div class="social-modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeTypeModal()">取消</button>
                    <button type="submit" class="btn btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="titleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>页面标题设置</h3>
                <button onclick="closeTitleModal()" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" name="save_title_settings" value="1">
                    <div class="form-group">
                        <label for="title_input">页面标题</label>
                        <input type="text" id="title_input" name="contact_title" value="<?php echo htmlspecialchars(Settings::get('contact_title', '联系我们')); ?>">
                    </div>
                    <div class="form-group">
                        <label for="subtitle_input">页面副标题</label>
                        <input type="text" id="subtitle_input" name="contact_subtitle" value="<?php echo htmlspecialchars(Settings::get('contact_subtitle', '期待与您的每一次交流，让我们一起创造更多可能')); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">保存设置</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // 自动隐藏提示信息
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.classList.add('fade-out');
                    setTimeout(function() {
                        alert.remove();
                    }, 300);
                }, 3000);
            });
        });
        
        document.querySelectorAll('.tab-btn').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.tab-btn').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(p => p.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('tab-' + this.dataset.tab).classList.add('active');
                history.replaceState(null, null, '#' + this.dataset.tab);
            });
        });
        
        if (window.location.hash === '#social') {
            document.querySelector('[data-tab="social"]').click();
        }
        if (window.location.hash === '#basic') {
            document.querySelector('[data-tab="basic"]').click();
        }
        
        function openTitleModal() {
            document.getElementById('titleModal').classList.add('active');
        }
        
        function closeTitleModal() {
            document.getElementById('titleModal').classList.remove('active');
        }
        
        function openAddModal() {
            document.getElementById('modalTitle').textContent = '添加社交媒体链接';
            document.getElementById('formAction').value = 'add_social_link';
            document.getElementById('socialId').value = '';
            document.getElementById('socialForm').reset();
            document.getElementById('sort_order').value = <?php echo getNextSortOrder(); ?>;
            document.querySelectorAll('.icon-type-option').forEach(opt => opt.classList.remove('active'));
            document.querySelector('.icon-type-option input[value="default"]').closest('.icon-type-option').classList.add('active');
            document.querySelector('.icon-type-option input[value="default"]').checked = true;
            document.getElementById('iconUrlGroup').style.display = 'none';
            document.getElementById('svgCodeGroup').style.display = 'none';
            document.getElementById('socialModal').classList.add('active');
        }
        
        function openEditModal(link) {
            document.getElementById('modalTitle').textContent = '编辑社交媒体链接';
            document.getElementById('formAction').value = 'update_social_link';
            document.getElementById('socialId').value = link.id;
            document.getElementById('platform').value = link.platform;
            document.getElementById('url').value = link.url;
            document.getElementById('sort_order').value = link.sort_order || 0;
            
            document.querySelectorAll('.icon-type-option').forEach(opt => opt.classList.remove('active'));
            
            var hasSvgCode = link.svg_code && link.svg_code.trim() !== '';
            var hasIconUrl = link.icon_url && link.icon_url.trim() !== '';
            
            if (link.icon_type === 'custom' && hasSvgCode) {
                document.querySelector('.icon-type-option input[value="svg"]').closest('.icon-type-option').classList.add('active');
                document.querySelector('.icon-type-option input[value="svg"]').checked = true;
                document.getElementById('svg_code').value = link.svg_code;
                document.getElementById('icon_url').value = '';
                document.getElementById('iconUrlGroup').style.display = 'none';
                document.getElementById('svgCodeGroup').style.display = 'block';
            } else if (link.icon_type === 'custom' && hasIconUrl) {
                document.querySelector('.icon-type-option input[value="custom"]').closest('.icon-type-option').classList.add('active');
                document.querySelector('.icon-type-option input[value="custom"]').checked = true;
                document.getElementById('icon_url').value = link.icon_url;
                document.getElementById('svg_code').value = '';
                document.getElementById('iconUrlGroup').style.display = 'block';
                document.getElementById('svgCodeGroup').style.display = 'none';
            } else {
                document.querySelector('.icon-type-option input[value="default"]').closest('.icon-type-option').classList.add('active');
                document.querySelector('.icon-type-option input[value="default"]').checked = true;
                document.getElementById('icon_url').value = '';
                document.getElementById('svg_code').value = '';
                document.getElementById('iconUrlGroup').style.display = 'none';
                document.getElementById('svgCodeGroup').style.display = 'none';
            }
            
            document.getElementById('socialModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('socialModal').classList.remove('active');
        }
        
        function openTypeModal() {
            document.getElementById('typeModalTitle').textContent = '添加联系类型';
            document.getElementById('typeFormAction').value = 'add_contact_type';
            document.getElementById('typeId').value = '';
            document.getElementById('typeForm').reset();
            document.getElementById('typeModal').classList.add('active');
        }
        
        function openEditTypeModal(type) {
            document.getElementById('typeModalTitle').textContent = '编辑联系类型';
            document.getElementById('typeFormAction').value = 'update_contact_type';
            document.getElementById('typeId').value = type.id;
            document.getElementById('type_name').value = type.name;
            document.getElementById('type_description').value = type.description || '';
            document.getElementById('typeModal').classList.add('active');
        }
        
        function closeTypeModal() {
            document.getElementById('typeModal').classList.remove('active');
        }
        
        document.getElementById('typeModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeTypeModal();
            }
        });
        
        function updateIconPreview() {
            const selectedType = document.querySelector('input[name="icon_type"]:checked').value;
            document.querySelectorAll('.icon-type-option').forEach(opt => opt.classList.remove('active'));
            document.querySelector(`input[name="icon_type"][value="${selectedType}"]`).closest('.icon-type-option').classList.add('active');
            
            document.getElementById('iconUrlGroup').style.display = selectedType === 'custom' ? 'block' : 'none';
            document.getElementById('svgCodeGroup').style.display = selectedType === 'svg' ? 'block' : 'none';
        }
        
        function openIconMediaPicker() {
            MediaPicker.open({
                onSelect: function(url) {
                    document.getElementById('icon_url').value = url;
                }
            });
        }
        
        document.getElementById('socialModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
        
        // 拖动排序功能
        const socialGrid = document.getElementById('socialGrid');
        if (socialGrid) {
            let draggedItem = null;
            
            socialGrid.addEventListener('dragstart', function(e) {
                if (e.target.classList.contains('social-card')) {
                    draggedItem = e.target;
                    e.target.classList.add('dragging');
                    e.dataTransfer.effectAllowed = 'move';
                }
            });
            
            socialGrid.addEventListener('dragend', function(e) {
                if (e.target.classList.contains('social-card')) {
                    e.target.classList.remove('dragging');
                    document.querySelectorAll('.social-card').forEach(card => {
                        card.classList.remove('drag-over');
                    });
                    saveOrder();
                }
            });
            
            socialGrid.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                const card = e.target.closest('.social-card');
                if (card && card !== draggedItem) {
                    card.classList.add('drag-over');
                }
            });
            
            socialGrid.addEventListener('dragleave', function(e) {
                const card = e.target.closest('.social-card');
                if (card) {
                    card.classList.remove('drag-over');
                }
            });
            
            socialGrid.addEventListener('drop', function(e) {
                e.preventDefault();
                const card = e.target.closest('.social-card');
                if (card && draggedItem && card !== draggedItem) {
                    const cards = [...socialGrid.querySelectorAll('.social-card')];
                    const draggedIndex = cards.indexOf(draggedItem);
                    const dropIndex = cards.indexOf(card);
                    
                    if (draggedIndex < dropIndex) {
                        card.parentNode.insertBefore(draggedItem, card.nextSibling);
                    } else {
                        card.parentNode.insertBefore(draggedItem, card);
                    }
                }
            });
        }
        
        function saveOrder() {
            const cards = document.querySelectorAll('.social-card');
            const orders = [];
            cards.forEach((card, index) => {
                orders.push(card.dataset.id);
            });
            
            const formData = new FormData();
            formData.append('action', 'update_social_order');
            orders.forEach((id, index) => {
                formData.append('orders[' + index + ']', id);
            });
            
            fetch('contact.php', {
                method: 'POST',
                body: formData
            }).then(response => response.json())
              .then(data => {
                  if (data.success) {
                  }
              });
        }
        
        const typeGrid = document.getElementById('typeGrid');
        if (typeGrid) {
            let draggedTypeItem = null;
            
            typeGrid.addEventListener('dragstart', function(e) {
                if (e.target.classList.contains('social-card')) {
                    draggedTypeItem = e.target;
                    e.target.classList.add('dragging');
                    e.dataTransfer.effectAllowed = 'move';
                }
            });
            
            typeGrid.addEventListener('dragend', function(e) {
                if (e.target.classList.contains('social-card')) {
                    e.target.classList.remove('dragging');
                    document.querySelectorAll('#typeGrid .social-card').forEach(card => {
                        card.classList.remove('drag-over');
                    });
                    saveTypeOrder();
                }
            });
            
            typeGrid.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                const card = e.target.closest('.social-card');
                if (card && card !== draggedTypeItem) {
                    card.classList.add('drag-over');
                }
            });
            
            typeGrid.addEventListener('dragleave', function(e) {
                const card = e.target.closest('.social-card');
                if (card) {
                    card.classList.remove('drag-over');
                }
            });
            
            typeGrid.addEventListener('drop', function(e) {
                e.preventDefault();
                const card = e.target.closest('.social-card');
                if (card && draggedTypeItem && card !== draggedTypeItem) {
                    const cards = [...typeGrid.querySelectorAll('.social-card')];
                    const draggedIndex = cards.indexOf(draggedTypeItem);
                    const dropIndex = cards.indexOf(card);
                    
                    if (draggedIndex < dropIndex) {
                        card.parentNode.insertBefore(draggedTypeItem, card.nextSibling);
                    } else {
                        card.parentNode.insertBefore(draggedTypeItem, card);
                    }
                }
            });
        }
        
        function saveTypeOrder() {
            const typeGrid = document.getElementById('typeGrid');
            if (!typeGrid) return;
            
            const cards = typeGrid.querySelectorAll('.social-card');
            const orders = [];
            cards.forEach((card) => {
                orders.push(card.dataset.id);
            });
            
            const formData = new FormData();
            formData.append('action', 'update_contact_type_order');
            orders.forEach((id, index) => {
                formData.append('orders[' + index + ']', id);
            });
            
            fetch('contact.php', {
                method: 'POST',
                body: formData
            }).then(response => response.json())
              .then(data => {
                  if (data.success) {
                  }
              });
        }
    </script>
    
    <?php include 'components/media-picker.php'; ?>
    <link rel="stylesheet" href="components/media-picker.css">
    <script src="components/media-picker.js"></script>
</body>
</html>
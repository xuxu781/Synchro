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
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/api_security.php';

APISecurity::setSecurityHeaders();
APISecurity::checkRateLimit();

$action = $_GET['action'] ?? '';

// increment_baidu_submit 不需要身份验证（前端百度推送脚本调用）
if ($action !== 'increment_baidu_submit') {
    require_auth();
}

header('Content-Type: application/json');

$db = Database::getInstance();

switch ($action) {
    case 'list':
        $settings = $db->fetchAll("SELECT * FROM `lty-site_settings`");
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting['setting_key']] = $setting['setting_value'];
        }
        json_response(['success' => true, 'data' => $result]);
        break;
        
    case 'get':
        $key = $_GET['key'] ?? '';
        if (!$key) {
            json_response(['success' => false, 'message' => 'Key required'], 400);
        }
        
        $value = Settings::get($key);
        json_response(['success' => true, 'data' => $value]);
        break;
        
    case 'update':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['success' => false, 'message' => 'Method not allowed'], 405);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $requirePassword = Settings::get('require_password_on_settings', '1');
        if ($requirePassword === '1') {
            $password = $data['password'] ?? '';
            
            if (empty($password)) {
                json_response(['success' => false, 'message' => '更新设置需要验证密码'], 400);
            }
            
            require_password_verification($password);
            unset($data['password']);
        }
        
        foreach ($data as $key => $value) {
            Settings::set($key, $value);
        }
        
        APISecurity::logSecurityEvent('settings_updated', [
            'user_id' => $_SESSION['admin_id'],
            'keys' => array_keys($data)
        ]);
        
        json_response(['success' => true]);
        break;
        
    case 'upload_logo':
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                json_response(['success' => false, 'message' => 'Method not allowed'], 405);
            }
            
            if (!isset($_FILES['logo_image'])) {
                json_response(['success' => false, 'message' => '未选择文件'], 400);
            }
            
            if ($_FILES['logo_image']['error'] !== UPLOAD_ERR_OK) {
                $uploadErrors = [
                    UPLOAD_ERR_INI_SIZE => '文件大小超过php.ini限制',
                    UPLOAD_ERR_FORM_SIZE => '文件大小超过表单限制',
                    UPLOAD_ERR_PARTIAL => '文件只有部分被上传',
                    UPLOAD_ERR_NO_FILE => '没有文件被上传',
                    UPLOAD_ERR_NO_TMP_DIR => '缺少临时目录',
                    UPLOAD_ERR_CANT_WRITE => '无法写入磁盘',
                    UPLOAD_ERR_EXTENSION => 'PHP扩展停止了文件上传'
                ];
                $errorMsg = $uploadErrors[$_FILES['logo_image']['error']] ?? '上传失败';
                json_response(['success' => false, 'message' => $errorMsg], 400);
            }
            
            $file = $_FILES['logo_image'];
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            
            if (!in_array($file['type'], $allowedTypes)) {
                json_response(['success' => false, 'message' => '不支持的文件类型: ' . $file['type']], 400);
            }
            
            $maxSize = 2 * 1024 * 1024;
            if ($file['size'] > $maxSize) {
                json_response(['success' => false, 'message' => '文件大小不能超过2MB'], 400);
            }
            
            $datePath = date('Y/m/d');
            $uploadDir = __DIR__ . '/../uploads/logo/' . $datePath . '/';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    json_response(['success' => false, 'message' => '无法创建上传目录'], 500);
                }
            }
            
            if (!is_writable($uploadDir)) {
                json_response(['success' => false, 'message' => '上传目录不可写'], 500);
            }
            
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'logo_' . time() . '_' . uniqid() . '.' . $extension;
            $filepath = $uploadDir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $siteUrl = Settings::get('site_url', '');
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $currentDomain = $_SERVER['HTTP_HOST'] ?? '';
                $baseUrl = $siteUrl ? $siteUrl : ($protocol . '://' . $currentDomain);
                $url = $baseUrl . '/uploads/logo/' . $datePath . '/' . $filename;
                
                json_response(['success' => true, 'url' => $url]);
            } else {
                json_response(['success' => false, 'message' => '文件保存失败'], 500);
            }
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => '上传错误: ' . $e->getMessage()], 500);
        }
        break;
        
    case 'upload_culture':
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                json_response(['success' => false, 'message' => 'Method not allowed'], 405);
            }
            
            if (!isset($_FILES['culture_image'])) {
                json_response(['success' => false, 'message' => '未选择文件'], 400);
            }
            
            if ($_FILES['culture_image']['error'] !== UPLOAD_ERR_OK) {
                $uploadErrors = [
                    UPLOAD_ERR_INI_SIZE => '文件大小超过php.ini限制',
                    UPLOAD_ERR_FORM_SIZE => '文件大小超过表单限制',
                    UPLOAD_ERR_PARTIAL => '文件只有部分被上传',
                    UPLOAD_ERR_NO_FILE => '没有文件被上传',
                    UPLOAD_ERR_NO_TMP_DIR => '缺少临时目录',
                    UPLOAD_ERR_CANT_WRITE => '无法写入磁盘',
                    UPLOAD_ERR_EXTENSION => 'PHP扩展停止了文件上传'
                ];
                $errorMsg = $uploadErrors[$_FILES['culture_image']['error']] ?? '上传失败';
                json_response(['success' => false, 'message' => $errorMsg], 400);
            }
            
            $file = $_FILES['culture_image'];
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            
            if (!in_array($file['type'], $allowedTypes)) {
                json_response(['success' => false, 'message' => '不支持的文件类型: ' . $file['type']], 400);
            }
            
            $maxSize = 5 * 1024 * 1024;
            if ($file['size'] > $maxSize) {
                json_response(['success' => false, 'message' => '文件大小不能超过5MB'], 400);
            }
            
            $datePath = date('Y/m/d');
            $uploadDir = __DIR__ . '/../uploads/culture/' . $datePath . '/';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    json_response(['success' => false, 'message' => '无法创建上传目录'], 500);
                }
            }
            
            if (!is_writable($uploadDir)) {
                json_response(['success' => false, 'message' => '上传目录不可写'], 500);
            }
            
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'culture_' . time() . '_' . uniqid() . '.' . $extension;
            $filepath = $uploadDir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $siteUrl = Settings::get('site_url', '');
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $currentDomain = $_SERVER['HTTP_HOST'] ?? '';
                $baseUrl = $siteUrl ? $siteUrl : ($protocol . '://' . $currentDomain);
                $url = $baseUrl . '/uploads/culture/' . $datePath . '/' . $filename;
                
                json_response(['success' => true, 'url' => $url]);
            } else {
                json_response(['success' => false, 'message' => '文件保存失败'], 500);
            }
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => '上传错误: ' . $e->getMessage()], 500);
        }
        break;
        
    case 'upload_slide':
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                json_response(['success' => false, 'message' => 'Method not allowed'], 405);
            }
            
            if (!isset($_FILES['slide_image'])) {
                json_response(['success' => false, 'message' => '未选择文件'], 400);
            }
            
            if ($_FILES['slide_image']['error'] !== UPLOAD_ERR_OK) {
                $uploadErrors = [
                    UPLOAD_ERR_INI_SIZE => '文件大小超过php.ini限制',
                    UPLOAD_ERR_FORM_SIZE => '文件大小超过表单限制',
                    UPLOAD_ERR_PARTIAL => '文件只有部分被上传',
                    UPLOAD_ERR_NO_FILE => '没有文件被上传',
                    UPLOAD_ERR_NO_TMP_DIR => '缺少临时目录',
                    UPLOAD_ERR_CANT_WRITE => '无法写入磁盘',
                    UPLOAD_ERR_EXTENSION => 'PHP扩展停止了文件上传'
                ];
                $errorMsg = $uploadErrors[$_FILES['slide_image']['error']] ?? '上传失败';
                json_response(['success' => false, 'message' => $errorMsg], 400);
            }
            
            $file = $_FILES['slide_image'];
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            
            if (!in_array($file['type'], $allowedTypes)) {
                json_response(['success' => false, 'message' => '不支持的文件类型: ' . $file['type']], 400);
            }
            
            $maxSize = 5 * 1024 * 1024;
            if ($file['size'] > $maxSize) {
                json_response(['success' => false, 'message' => '文件大小不能超过5MB'], 400);
            }
            
            $datePath = date('Y/m/d');
            $uploadDir = __DIR__ . '/../uploads/slide/' . $datePath . '/';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    json_response(['success' => false, 'message' => '无法创建上传目录'], 500);
                }
            }
            
            if (!is_writable($uploadDir)) {
                json_response(['success' => false, 'message' => '上传目录不可写'], 500);
            }
            
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'slide_' . time() . '_' . uniqid() . '.' . $extension;
            $filepath = $uploadDir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $siteUrl = Settings::get('site_url', '');
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $currentDomain = $_SERVER['HTTP_HOST'] ?? '';
                $baseUrl = $siteUrl ? $siteUrl : ($protocol . '://' . $currentDomain);
                $url = $baseUrl . '/uploads/slide/' . $datePath . '/' . $filename;
                
                json_response(['success' => true, 'url' => $url]);
            } else {
                json_response(['success' => false, 'message' => '文件保存失败'], 500);
            }
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => '上传错误: ' . $e->getMessage()], 500);
        }
        break;
        
    case 'upload_avatar':
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                json_response(['success' => false, 'message' => 'Method not allowed'], 405);
            }
            
            if (!isset($_FILES['avatar_image'])) {
                json_response(['success' => false, 'message' => '未选择文件'], 400);
            }
            
            if ($_FILES['avatar_image']['error'] !== UPLOAD_ERR_OK) {
                $uploadErrors = [
                    UPLOAD_ERR_INI_SIZE => '文件大小超过php.ini限制',
                    UPLOAD_ERR_FORM_SIZE => '文件大小超过表单限制',
                    UPLOAD_ERR_PARTIAL => '文件只有部分被上传',
                    UPLOAD_ERR_NO_FILE => '没有文件被上传',
                    UPLOAD_ERR_NO_TMP_DIR => '缺少临时目录',
                    UPLOAD_ERR_CANT_WRITE => '无法写入磁盘',
                    UPLOAD_ERR_EXTENSION => 'PHP扩展停止了文件上传'
                ];
                $errorMsg = $uploadErrors[$_FILES['avatar_image']['error']] ?? '上传失败';
                json_response(['success' => false, 'message' => $errorMsg], 400);
            }
            
            $file = $_FILES['avatar_image'];
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            
            if (!in_array($file['type'], $allowedTypes)) {
                json_response(['success' => false, 'message' => '不支持的文件类型: ' . $file['type']], 400);
            }
            
            $maxSize = 2 * 1024 * 1024;
            if ($file['size'] > $maxSize) {
                json_response(['success' => false, 'message' => '文件大小不能超过2MB'], 400);
            }
            
            $datePath = date('Y/m/d');
            $uploadDir = __DIR__ . '/../uploads/avatar/' . $datePath . '/';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    json_response(['success' => false, 'message' => '无法创建上传目录'], 500);
                }
            }
            
            if (!is_writable($uploadDir)) {
                json_response(['success' => false, 'message' => '上传目录不可写'], 500);
            }
            
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'avatar_' . time() . '_' . uniqid() . '.' . $extension;
            $filepath = $uploadDir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $siteUrl = Settings::get('site_url', '');
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $currentDomain = $_SERVER['HTTP_HOST'] ?? '';
                $baseUrl = $siteUrl ? $siteUrl : ($protocol . '://' . $currentDomain);
                $url = $baseUrl . '/uploads/avatar/' . $datePath . '/' . $filename;
                
                json_response(['success' => true, 'url' => $url]);
            } else {
                json_response(['success' => false, 'message' => '文件保存失败'], 500);
            }
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => '上传错误: ' . $e->getMessage()], 500);
        }
        break;
        
    case 'update_slide_order':
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                json_response(['success' => false, 'message' => 'Method not allowed'], 405);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['ids']) || !is_array($data['ids'])) {
                json_response(['success' => false, 'message' => 'Invalid data'], 400);
            }
            
            $ids = $data['ids'];
            
            foreach ($ids as $index => $id) {
                $db->update('`lty-mission_slides`', ['sort_order' => $index + 1], 'id = ?', [$id]);
            }
            
            json_response(['success' => true]);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => '更新排序失败: ' . $e->getMessage()], 500);
        }
        break;
        
    case 'update_culture_order':
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                json_response(['success' => false, 'message' => 'Method not allowed'], 405);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['ids']) || !is_array($data['ids'])) {
                json_response(['success' => false, 'message' => 'Invalid data'], 400);
            }
            
            $ids = $data['ids'];
            
            foreach ($ids as $index => $id) {
                $db->update('`lty-company_culture`', ['sort_order' => $index + 1], 'id = ?', [$id]);
            }
            
            json_response(['success' => true]);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => '更新排序失败: ' . $e->getMessage()], 500);
        }
        break;
        
    case 'update_timeline_order':
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                json_response(['success' => false, 'message' => 'Method not allowed'], 405);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['ids']) || !is_array($data['ids'])) {
                json_response(['success' => false, 'message' => 'Invalid data'], 400);
            }
            
            $ids = $data['ids'];
            
            foreach ($ids as $index => $id) {
                $db->update('`lty-company_timeline`', ['sort_order' => $index + 1], 'id = ?', [$id]);
            }
            
            json_response(['success' => true]);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => '更新排序失败: ' . $e->getMessage()], 500);
        }
        break;
        
    case 'upload_product':
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                json_response(['success' => false, 'message' => 'Method not allowed'], 405);
            }
            
            if (!isset($_FILES['product_image'])) {
                json_response(['success' => false, 'message' => '未选择文件'], 400);
            }
            
            if ($_FILES['product_image']['error'] !== UPLOAD_ERR_OK) {
                $uploadErrors = [
                    UPLOAD_ERR_INI_SIZE => '文件大小超过php.ini限制',
                    UPLOAD_ERR_FORM_SIZE => '文件大小超过表单限制',
                    UPLOAD_ERR_PARTIAL => '文件只有部分被上传',
                    UPLOAD_ERR_NO_FILE => '没有文件被上传',
                    UPLOAD_ERR_NO_TMP_DIR => '缺少临时目录',
                    UPLOAD_ERR_CANT_WRITE => '无法写入磁盘',
                    UPLOAD_ERR_EXTENSION => 'PHP扩展停止了文件上传'
                ];
                $errorMsg = $uploadErrors[$_FILES['product_image']['error']] ?? '上传失败';
                json_response(['success' => false, 'message' => $errorMsg], 400);
            }
            
            $file = $_FILES['product_image'];
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            
            if (!in_array($file['type'], $allowedTypes)) {
                json_response(['success' => false, 'message' => '不支持的文件类型: ' . $file['type']], 400);
            }
            
            $maxSize = 5 * 1024 * 1024;
            if ($file['size'] > $maxSize) {
                json_response(['success' => false, 'message' => '文件大小不能超过5MB'], 400);
            }
            
            $datePath = date('Y/m/d');
            $uploadDir = __DIR__ . '/../uploads/product/' . $datePath . '/';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    json_response(['success' => false, 'message' => '无法创建上传目录'], 500);
                }
            }
            
            if (!is_writable($uploadDir)) {
                json_response(['success' => false, 'message' => '上传目录不可写'], 500);
            }
            
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'product_' . time() . '_' . uniqid() . '.' . $extension;
            $filepath = $uploadDir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $siteUrl = Settings::get('site_url', '');
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $currentDomain = $_SERVER['HTTP_HOST'] ?? '';
                $baseUrl = $siteUrl ? $siteUrl : ($protocol . '://' . $currentDomain);
                $url = $baseUrl . '/uploads/product/' . $datePath . '/' . $filename;
                
                json_response(['success' => true, 'url' => $url]);
            } else {
                json_response(['success' => false, 'message' => '文件保存失败'], 500);
            }
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => '上传错误: ' . $e->getMessage()], 500);
        }
        break;
        
    case 'update_product_order':
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                json_response(['success' => false, 'message' => 'Method not allowed'], 405);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['ids']) || !is_array($data['ids'])) {
                json_response(['success' => false, 'message' => 'Invalid data'], 400);
            }
            
            $ids = $data['ids'];
            
            foreach ($ids as $index => $id) {
                $db->update('`lty-products`', ['sort_order' => $index + 1], 'id = ?', [$id]);
            }
            
            json_response(['success' => true]);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => '更新排序失败: ' . $e->getMessage()], 500);
        }
        break;
        
    case 'increment_baidu_submit':
        $currentCount = intval(Settings::get('baidu_submit_count', '0'));
        Settings::set('baidu_submit_count', $currentCount + 1);
        json_response(['success' => true]);
        break;
        
    default:
        json_response(['success' => false, 'message' => 'Invalid action'], 400);
}
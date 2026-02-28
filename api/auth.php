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
require_once __DIR__ . '/../includes/api_security.php';

APISecurity::setSecurityHeaders();
APISecurity::checkRateLimit();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['error' => 'Method not allowed'], 405);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            json_response(['error' => '用户名和密码不能为空'], 400);
        }
        
        $result = Auth::login($username, $password);
        if ($result['success']) {
            $user = Auth::user();
            json_response([
                'success' => true,
                'user' => $user,
                'csrf_token' => Auth::getCSRFToken()
            ]);
        } elseif ($result['error'] === 'account_disabled') {
            json_response(['error' => '您的账户已被禁用，请联系管理员'], 403);
        } else {
            json_response(['error' => '用户名或密码错误'], 401);
        }
        break;
        
    case 'logout':
        Auth::logout();
        json_response(['success' => true]);
        break;
        
    case 'check':
        if (Auth::check()) {
            json_response([
                'success' => true,
                'user' => Auth::user()
            ]);
        } else {
            json_response(['success' => false], 401);
        }
        break;
        
    case 'update_profile':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['error' => 'Method not allowed'], 405);
        }
        
        if (!Auth::check()) {
            json_response(['error' => '未登录'], 401);
        }
        
        $db = Database::getInstance();
        $userId = $_SESSION['admin_id'];
        
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'multipart/form-data') !== false) {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            
            if (empty($email)) {
                json_response(['error' => '邮箱不能为空'], 400);
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                json_response(['error' => '邮箱格式不正确'], 400);
            }
            
            $updateData = ['email' => $email];
            
            if (!empty($name)) {
                $updateData['name'] = $name;
            }
            
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $avatarFile = $_FILES['avatar'];
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                
                if (!in_array($avatarFile['type'], $allowedTypes)) {
                    json_response(['error' => '只支持 JPG、PNG、GIF、WebP 格式的图片'], 400);
                }
                
                if ($avatarFile['size'] > 2 * 1024 * 1024) {
                    json_response(['error' => '图片大小不能超过 2MB'], 400);
                }
                
                $uploadDir = __DIR__ . '/../uploads/avatar/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $datePath = date('Y/m/d');
                $uploadDir .= $datePath . '/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $extension = pathinfo($avatarFile['name'], PATHINFO_EXTENSION);
                $filename = 'avatar_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
                $filepath = $uploadDir . $filename;
                
                if (!move_uploaded_file($avatarFile['tmp_name'], $filepath)) {
                    json_response(['error' => '头像上传失败'], 500);
                }
                
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $avatarUrl = $protocol . '://' . $host . '/uploads/avatar/' . $datePath . '/' . $filename;
                
                $updateData['avatar'] = $avatarUrl;
            } elseif (!empty($_POST['avatar_url'])) {
                $updateData['avatar'] = $_POST['avatar_url'];
            }
            
            $db->update('`lty-admin_users`', $updateData, 'id = ?', [$userId]);
            
            json_response([
                'success' => true,
                'message' => '个人资料更新成功',
                'user' => Auth::user()
            ]);
        } else {
            $data = json_decode(file_get_contents('php://input'), true);
            $name = trim($data['name'] ?? '');
            $email = trim($data['email'] ?? '');
            
            if (empty($email)) {
                json_response(['error' => '邮箱不能为空'], 400);
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                json_response(['error' => '邮箱格式不正确'], 400);
            }
            
            $updateData = ['email' => $email];
            
            if (!empty($name)) {
                $updateData['name'] = $name;
            }
            
            $db->update('`lty-admin_users`', $updateData, 'id = ?', [$userId]);
            
            json_response([
                'success' => true,
                'message' => '个人资料更新成功',
                'user' => Auth::user()
            ]);
        }
        break;
        
    case 'change_password':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['error' => 'Method not allowed'], 405);
        }
        
        if (!Auth::check()) {
            json_response(['error' => '未登录'], 401);
        }
        
        require_csrf();
        
        $data = json_decode(file_get_contents('php://input'), true);
        $current_password = $data['current_password'] ?? '';
        $new_password = $data['new_password'] ?? '';
        $confirm_password = $data['confirm_password'] ?? '';
        
        if (empty($current_password)) {
            json_response(['error' => '请输入当前密码'], 400);
        }
        
        if (empty($new_password)) {
            json_response(['error' => '请输入新密码'], 400);
        }
        
        if (strlen($new_password) < 6) {
            json_response(['error' => '新密码长度不能少于6位'], 400);
        }
        
        if ($new_password !== $confirm_password) {
            json_response(['error' => '两次输入的密码不一致'], 400);
        }
        
        $db = Database::getInstance();
        $userId = $_SESSION['admin_id'];
        
        $user = $db->fetchOne(
            "SELECT password FROM `lty-admin_users` WHERE id = ?",
            [$userId]
        );
        
        if (!password_verify($current_password, $user['password'])) {
            APISecurity::logSecurityEvent('password_change_failed', [
                'user_id' => $userId,
                'reason' => 'current_password_incorrect'
            ]);
            json_response(['error' => '当前密码不正确'], 400);
        }
        
        $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
        
        $db->update(
            '`lty-admin_users`',
            ['password' => $hashedPassword],
            'id = ?',
            [$userId]
        );
        
        APISecurity::logSecurityEvent('password_changed', [
            'user_id' => $userId
        ]);
        
        json_response([
            'success' => true,
            'message' => '密码修改成功'
        ]);
        break;
        
    case 'upload_avatar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['error' => 'Method not allowed'], 405);
        }
        
        if (!Auth::check()) {
            json_response(['error' => '未登录'], 401);
        }
        
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            json_response(['error' => '请选择要上传的图片'], 400);
        }
        
        $avatarFile = $_FILES['avatar'];
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($avatarFile['type'], $allowedTypes)) {
            json_response(['error' => '只支持 JPG、PNG、GIF、WebP 格式的图片'], 400);
        }
        
        if ($avatarFile['size'] > 2 * 1024 * 1024) {
            json_response(['error' => '图片大小不能超过 2MB'], 400);
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realMime = finfo_file($finfo, $avatarFile['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($realMime, $allowedTypes)) {
            json_response(['error' => '文件类型验证失败'], 400);
        }
        
        $uploadDir = __DIR__ . '/../uploads/avatar/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $datePath = date('Y/m/d');
        $uploadDir .= $datePath . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $extension = strtolower(pathinfo($avatarFile['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $extension = 'jpg';
        }
        
        $filename = 'avatar_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        if (!move_uploaded_file($avatarFile['tmp_name'], $filepath)) {
            json_response(['error' => '头像上传失败'], 500);
        }
        
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $avatarUrl = $protocol . '://' . $host . '/uploads/avatar/' . $datePath . '/' . $filename;
        
        json_response([
            'success' => true,
            'url' => $avatarUrl,
            'message' => '头像上传成功'
        ]);
        break;
        
    default:
        json_response(['error' => 'Invalid action'], 400);
}
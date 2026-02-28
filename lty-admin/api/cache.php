<?php
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

if (!Auth::check()) {
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit;
}

if (!Auth::isSuperAdmin()) {
    echo json_encode(['success' => false, 'message' => '无权限执行此操作']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '请求方法错误']);
    exit;
}

$results = [
    'opcache' => false,
    'settings' => false,
    'auth' => false,
];

if (function_exists('opcache_reset')) {
    $results['opcache'] = opcache_reset();
}

Settings::clearCache();
$results['settings'] = true;

Auth::clearCache();
$results['auth'] = true;

if (session_status() === PHP_SESSION_ACTIVE) {
    $flashMessages = $_SESSION['flash_messages'] ?? null;
    $adminId = $_SESSION['admin_id'] ?? null;
    $adminUsername = $_SESSION['admin_username'] ?? null;
    $loginIp = $_SESSION['login_ip'] ?? null;
    $loginTime = $_SESSION['login_time'] ?? null;
    $lastActivity = $_SESSION['last_activity'] ?? null;
    $userAgent = $_SESSION['user_agent'] ?? null;
    $csrfToken = $_SESSION['csrf_token'] ?? null;
    $csrfTokenTime = $_SESSION['csrf_token_time'] ?? null;
    
    $_SESSION = [];
    
    if ($adminId !== null) $_SESSION['admin_id'] = $adminId;
    if ($adminUsername !== null) $_SESSION['admin_username'] = $adminUsername;
    if ($loginIp !== null) $_SESSION['login_ip'] = $loginIp;
    if ($loginTime !== null) $_SESSION['login_time'] = $loginTime;
    if ($lastActivity !== null) $_SESSION['last_activity'] = $lastActivity;
    if ($userAgent !== null) $_SESSION['user_agent'] = $userAgent;
    if ($csrfToken !== null) $_SESSION['csrf_token'] = $csrfToken;
    if ($csrfTokenTime !== null) $_SESSION['csrf_token_time'] = $csrfTokenTime;
    if ($flashMessages !== null) $_SESSION['flash_messages'] = $flashMessages;
}

$cacheDir = __DIR__ . '/../../cache';
if (is_dir($cacheDir)) {
    $files = glob($cacheDir . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            @unlink($file);
        }
    }
}

echo json_encode([
    'success' => true,
    'message' => '缓存清理完成',
    'details' => $results
]);

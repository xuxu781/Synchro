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
require_once __DIR__ . '/../config.php';

if (!file_exists(__DIR__ . '/../lty-admin/config/database.php')) {
    header('Location: /install.php');
    exit;
}

require_once __DIR__ . '/../lty-admin/config/database.php';

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $this->connection = new PDO($dsn, DB_USER, DB_PASS);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            if (php_sapi_name() === 'cli') {
                die('数据库连接失败：' . $e->getMessage());
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => '数据库连接失败：' . $e->getMessage()]);
            exit;
        }
    }
    
    private function checkRequiredTables() {
        $requiredTables = ['lty-company_culture', 'lty-company_behavior', 'lty-company_timeline', 'lty-social_links'];
        $existingTables = [];
        
        try {
            $stmt = $this->connection->query("SHOW TABLES");
            while ($row = $stmt->fetch()) {
                $existingTables[] = array_values($row)[0];
            }
            
            $missingTables = array_diff($requiredTables, $existingTables);
            
            if (!empty($missingTables)) {
                header('Location: /install.php');
                exit;
            }
        } catch (PDOException $e) {
            header('Location: /install.php');
            exit;
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception('查询失败：' . $e->getMessage());
        }
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    public function insert($table, $data) {
        $table = trim($table, '`');
        $keys = array_keys($data);
        $fields = '`' . implode('`, `', $keys) . '`';
        $placeholders = implode(', ', array_fill(0, count($keys), '?'));
        
        $sql = "INSERT INTO `$table` ($fields) VALUES ($placeholders)";
        $this->query($sql, array_values($data));
        
        return $this->connection->lastInsertId();
    }
    
    public function update($table, $data, $where, $whereParams = []) {
        $table = trim($table, '`');
        $set = [];
        foreach (array_keys($data) as $key) {
            $set[] = "$key = ?";
        }
        $setClause = implode(', ', $set);
        
        $sql = "UPDATE `$table` SET $setClause WHERE $where";
        $params = array_merge(array_values($data), $whereParams);
        
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    public function delete($table, $where, $params = []) {
        $table = trim($table, '`');
        $sql = "DELETE FROM `$table` WHERE $where";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollback() {
        return $this->connection->rollBack();
    }
}

class Settings {
    private static $settings = null;
    
    public static function get($key, $default = '') {
        if (self::$settings === null) {
            self::loadSettings();
        }
        return self::$settings[$key] ?? $default;
    }
    
    public static function set($key, $value) {
        $db = Database::getInstance();
        $existing = $db->fetchOne("SELECT setting_key FROM `lty-site_settings` WHERE setting_key = ?", [$key]);
        
        if ($existing) {
            $db->query("UPDATE `lty-site_settings` SET setting_value = ? WHERE setting_key = ?", [$value, $key]);
        } else {
            $db->query("INSERT INTO `lty-site_settings` (setting_key, setting_value) VALUES (?, ?)", [$key, $value]);
        }
        self::$settings[$key] = $value;
    }
    
    public static function clearCache() {
        self::$settings = null;
    }
    
    private static function loadSettings() {
        $db = Database::getInstance();
        $sql = "SELECT setting_key, setting_value FROM `lty-site_settings`";
        $results = $db->fetchAll($sql);
        
        self::$settings = [];
        foreach ($results as $row) {
            self::$settings[$row['setting_key']] = $row['setting_value'];
        }
    }
}

class Auth {
    private static $userPermissions = null;
    private static $userMenus = null;
    private static $userRole = null;
    
    public static function clearCache() {
        self::$userPermissions = null;
        self::$userMenus = null;
        self::$userRole = null;
    }
    
    public static function check() {
        if (!isset($_SESSION['admin_id'])) {
            return false;
        }
        
        $enableIPBinding = Settings::get('enable_ip_binding', '1');
        if ($enableIPBinding === '1' && isset($_SESSION['login_ip']) && $_SESSION['login_ip'] !== self::getClientIP()) {
            SecurityLog::unauthorizedAccess('IP地址变化，会话已失效', ['original_ip' => $_SESSION['login_ip'] ?? null]);
            self::logout();
            return false;
        }
        
        $sessionTimeout = Settings::get('session_timeout', 30) * 60;
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $sessionTimeout)) {
            SecurityLog::sessionExpired($_SESSION['admin_id'] ?? null, $_SESSION['admin_username'] ?? null);
            self::logout();
            return false;
        }
        
        $db = Database::getInstance();
        $user = $db->fetchOne("SELECT status FROM `lty-admin_users` WHERE id = ?", [$_SESSION['admin_id']]);
        if (!$user || $user['status'] != 1) {
            self::logout();
            return false;
        }
        
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    public static function user() {
        if (!self::check()) {
            return null;
        }
        $db = Database::getInstance();
        try {
            $sql = "SELECT u.id, u.username, u.email, u.name, u.avatar, u.role_id, u.created_at, r.name as role_name, r.slug as role_slug 
                    FROM `lty-admin_users` u 
                    LEFT JOIN `lty-roles` r ON u.role_id = r.id 
                    WHERE u.id = ?";
            return $db->fetchOne($sql, [$_SESSION['admin_id']]);
        } catch (Exception $e) {
            $sql = "SELECT id, username, email, name, avatar, role_id, created_at FROM `lty-admin_users` WHERE id = ?";
            return $db->fetchOne($sql, [$_SESSION['admin_id']]);
        }
    }
    
    public static function getRole() {
        if (self::$userRole !== null) {
            return self::$userRole;
        }
        
        $user = self::user();
        if (!$user || !$user['role_id']) {
            return null;
        }
        
        $db = Database::getInstance();
        try {
            self::$userRole = $db->fetchOne("SELECT * FROM `lty-roles` WHERE id = ?", [$user['role_id']]);
        } catch (Exception $e) {
            self::$userRole = null;
        }
        return self::$userRole;
    }
    
    public static function getPermissions() {
        if (self::$userPermissions !== null) {
            return self::$userPermissions;
        }
        
        $role = self::getRole();
        if (!$role) {
            self::$userPermissions = [];
            return self::$userPermissions;
        }
        
        $db = Database::getInstance();
        try {
            if ($role['slug'] === 'super_admin') {
                $perms = $db->fetchAll("SELECT slug FROM `lty-permissions` WHERE status = 1");
                self::$userPermissions = array_column($perms, 'slug');
            } else {
                $perms = $db->fetchAll(
                    "SELECT p.slug FROM `lty-permissions` p 
                     INNER JOIN `lty-role_permissions` rp ON p.id = rp.permission_id 
                     WHERE rp.role_id = ? AND p.status = 1",
                    [$role['id']]
                );
                self::$userPermissions = array_column($perms, 'slug');
            }
        } catch (Exception $e) {
            self::$userPermissions = [];
        }
        
        return self::$userPermissions;
    }
    
    public static function getMenus() {
        if (self::$userMenus !== null) {
            return self::$userMenus;
        }
        
        $role = self::getRole();
        if (!$role) {
            self::$userMenus = [];
            return self::$userMenus;
        }
        
        $db = Database::getInstance();
        try {
            if ($role['slug'] === 'super_admin') {
                $menus = $db->fetchAll("SELECT * FROM `lty-admin_menus` WHERE status = 1 ORDER BY sort_order ASC");
            } else {
                $menus = $db->fetchAll(
                    "SELECT m.* FROM `lty-admin_menus` m 
                     INNER JOIN `lty-role_menus` rm ON m.id = rm.menu_id 
                     WHERE rm.role_id = ? AND m.status = 1 
                     ORDER BY m.sort_order ASC",
                    [$role['id']]
                );
            }
            self::$userMenus = $menus;
        } catch (Exception $e) {
            self::$userMenus = [];
        }
        
        return self::$userMenus;
    }
    
    public static function hasPermission($permission) {
        try {
            $permissions = self::getPermissions();
            return in_array($permission, $permissions);
        } catch (Exception $e) {
            return false;
        }
    }
    
    public static function hasMenu($menuSlug) {
        try {
            $menus = self::getMenus();
            foreach ($menus as $menu) {
                if ($menu['slug'] === $menuSlug) {
                    return true;
                }
            }
        } catch (Exception $e) {
        }
        return false;
    }
    
    public static function isSuperAdmin() {
        try {
            $role = self::getRole();
            return $role && $role['slug'] === 'super_admin';
        } catch (Exception $e) {
            return false;
        }
    }
    
    public static function hasRole() {
        $user = self::user();
        return $user && $user['role_id'];
    }
    
    public static function requirePermission($permission) {
        if (!self::check()) {
            redirect('lty-admin/');
        }
        
        if (!self::hasRole()) {
            set_flash_message('error', '您的账户未分配角色，请联系管理员');
            redirect('lty-admin/index.php');
        }
        
        if (!self::isSuperAdmin() && !self::hasPermission($permission)) {
            $db = Database::getInstance();
            $permInfo = $db->fetchOne("SELECT name FROM `lty-permissions` WHERE slug = ?", [$permission]);
            $permName = $permInfo ? $permInfo['name'] : '此';
            $user = self::user();
            SecurityLog::permissionDenied($user['id'] ?? null, $user['username'] ?? null, $permission);
            set_flash_message('error', '您没有权限访问' . $permName . '页面');
            redirect('lty-admin/index.php');
        }
    }
    
    public static function login($username, $password) {
        $db = Database::getInstance();
        $field = filter_var($username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $sql = "SELECT id, username, password, status FROM `lty-admin_users` WHERE {$field} = ?";
        $user = $db->fetchOne($sql, [$username]);
        
        if (!$user) {
            return ['success' => false, 'error' => 'invalid_credentials'];
        }
        
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'error' => 'invalid_credentials'];
        }
        
        if ($user['status'] != 1) {
            return ['success' => false, 'error' => 'account_disabled'];
        }
        
        session_regenerate_id(true);
        
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['login_ip'] = self::getClientIP();
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $db->update('`lty-admin_users`', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
        
        self::generateCSRFToken();
        
        OperationLog::login('用户 ' . $user['username'] . ' 登录成功');
        
        return ['success' => true];
    }
    
    public static function logout() {
        $username = $_SESSION['admin_username'] ?? null;
        
        unset($_SESSION['admin_id']);
        unset($_SESSION['admin_username']);
        unset($_SESSION['login_ip']);
        unset($_SESSION['login_time']);
        unset($_SESSION['last_activity']);
        unset($_SESSION['user_agent']);
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_time']);
        
        if ($username) {
            OperationLog::logout('用户 ' . $username . ' 登出成功');
        }
        
        session_destroy();
    }
    
    public static function getClientIP() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
    }
    
    public static function generateCSRFToken() {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        return $token;
    }
    
    public static function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
            SecurityLog::csrfInvalid(['reason' => 'token_not_found']);
            return false;
        }
        
        $csrfExpiry = Settings::get('csrf_token_expiry', 120) * 60;
        if (time() - $_SESSION['csrf_token_time'] > $csrfExpiry) {
            SecurityLog::csrfInvalid(['reason' => 'token_expired']);
            return false;
        }
        
        $valid = hash_equals($_SESSION['csrf_token'], $token);
        if (!$valid) {
            SecurityLog::csrfInvalid(['reason' => 'token_mismatch']);
        }
        return $valid;
    }
    
    public static function getCSRFToken() {
        $csrfExpiry = Settings::get('csrf_token_expiry', 120) * 60;
        if (!isset($_SESSION['csrf_token']) || 
            (time() - $_SESSION['csrf_token_time'] > $csrfExpiry)) {
            return self::generateCSRFToken();
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function requirePasswordVerification($password) {
        if (!self::check()) {
            return false;
        }
        
        $db = Database::getInstance();
        $userId = $_SESSION['admin_id'];
        
        $user = $db->fetchOne(
            "SELECT password FROM `lty-admin_users` WHERE id = ?",
            [$userId]
        );
        
        if (!$user) {
            return false;
        }
        
        return password_verify($password, $user['password']);
    }
}

function json_response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function redirect($url) {
    if (strpos($url, '://') === false) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $url = $protocol . '://' . $host . '/' . ltrim($url, '/\\');
    }
    header("Location: $url");
    exit;
}

function get_image_url($image) {
    if (empty($image)) {
        return '';
    }
    
    $configuredUrl = Settings::get('site_url', '');
    
    if (empty($configuredUrl)) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $currentDomain = $_SERVER['HTTP_HOST'] ?? '';
        $configuredUrl = $protocol . '://' . $currentDomain;
    }
    
    if (strpos($image, '://') !== false) {
        $parsedUrl = parse_url($image);
        $imagePath = $parsedUrl['path'] ?? '';
        
        if (strpos($imagePath, '/uploads/') === 0) {
            return $configuredUrl . $imagePath;
        }
        
        return $image;
    }
    
    if (strpos($image, '/') === 0) {
        return $configuredUrl . $image;
    }
    
    return $configuredUrl . '/' . $image;
}

function set_flash_message($type, $message) {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][$type] = $message;
}

function get_flash_message($type) {
    if (isset($_SESSION['flash_messages'][$type])) {
        $message = $_SESSION['flash_messages'][$type];
        unset($_SESSION['flash_messages'][$type]);
        if (empty($_SESSION['flash_messages'])) {
            unset($_SESSION['flash_messages']);
        }
        return $message;
    }
    return null;
}

function has_flash_message($type) {
    return isset($_SESSION['flash_messages'][$type]);
}

function get_all_flash_messages() {
    if (isset($_SESSION['flash_messages'])) {
        $messages = $_SESSION['flash_messages'];
        unset($_SESSION['flash_messages']);
        return $messages;
    }
    return [];
}

function get_qq_avatar_url($email, $size = 100) {
    if (empty($email)) {
        return '';
    }
    
    $email = strtolower(trim($email));
    
    if (preg_match('/^(\d+)@qq\.com$/i', $email, $matches)) {
        $qq = $matches[1];
        $sizeMap = [
            40 => 1,
            50 => 2,
            100 => 3,
            140 => 4,
            200 => 5
        ];
        $qqSize = $sizeMap[$size] ?? 3;
        return "https://q1.qlogo.cn/g?b=qq&nk={$qq}&s={$qqSize}";
    }
    
    return '';
}

function get_avatar_url($avatar, $email = '', $size = 100) {
    if (!empty($avatar)) {
        return $avatar;
    }
    
    if (!empty($email)) {
        $qqAvatar = get_qq_avatar_url($email, $size);
        if (!empty($qqAvatar)) {
            return $qqAvatar;
        }
    }
    
    return '';
}

function get_logo_html() {
    $logoType = Settings::get('logo_type', 'image');
    
    switch ($logoType) {
        case 'image':
            $logoImage = Settings::get('logo_image', '');
            if (!empty($logoImage)) {
                return '<img src="' . htmlspecialchars(get_image_url($logoImage)) . '" alt="" style="max-height: 40px; vertical-align: middle;" onerror="this.style.display=\'none\'">';
            }
            return '<img src="/assets/images/logo/logo.png" alt="" style="max-height: 40px; vertical-align: middle;" onerror="this.style.display=\'none\'">';
            
        case 'text':
            $logoText = Settings::get('logo_text', '');
            if (!empty($logoText)) {
                return htmlspecialchars($logoText);
            }
            return '';
            
        default:
            return '<img src="/assets/images/logo/logo.png" alt="" style="max-height: 40px; vertical-align: middle;" onerror="this.style.display=\'none\'">';
    }
}

class OperationLog {
    public static function log($action, $module, $description, $targetType = null, $targetId = null, $details = null) {
        $db = Database::getInstance();
        
        $user = Auth::user();
        $userId = $user ? $user['id'] : null;
        $username = $user ? $user['username'] : null;
        
        $data = [
            'user_id' => $userId,
            'username' => $username,
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'details' => $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
            'ip_address' => Auth::getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        try {
            $db->insert('`lty-operation_logs`', $data);
            return true;
        } catch (Exception $e) {
            error_log('Failed to log operation: ' . $e->getMessage());
            return false;
        }
    }
    
    public static function create($module, $description, $targetType = null, $targetId = null, $details = null) {
        return self::log('create', $module, $description, $targetType, $targetId, $details);
    }
    
    public static function update($module, $description, $targetType = null, $targetId = null, $details = null) {
        return self::log('update', $module, $description, $targetType, $targetId, $details);
    }
    
    public static function delete($module, $description, $targetType = null, $targetId = null, $details = null) {
        return self::log('delete', $module, $description, $targetType, $targetId, $details);
    }
    
    public static function login($description = '用户登录') {
        return self::log('login', 'auth', $description);
    }
    
    public static function logout($description = '用户登出') {
        return self::log('logout', 'auth', $description);
    }
    
    public static function export($module, $description, $targetType = null, $targetId = null, $details = null) {
        return self::log('export', $module, $description, $targetType, $targetId, $details);
    }
    
    public static function import($module, $description, $targetType = null, $targetId = null, $details = null) {
        return self::log('import', $module, $description, $targetType, $targetId, $details);
    }
    
    public static function upload($module, $description, $targetType = null, $targetId = null, $details = null) {
        return self::log('upload', $module, $description, $targetType, $targetId, $details);
    }
    
    public static function download($module, $description, $targetType = null, $targetId = null, $details = null) {
        return self::log('download', $module, $description, $targetType, $targetId, $details);
    }
}

class SecurityLog {
    const EVENT_LOGIN_FAILED = 'login_failed';
    const EVENT_UNAUTHORIZED_ACCESS = 'unauthorized_access';
    const EVENT_PERMISSION_DENIED = 'permission_denied';
    const EVENT_CSRF_INVALID = 'csrf_invalid';
    const EVENT_SESSION_EXPIRED = 'session_expired';
    const EVENT_IP_BLOCKED = 'ip_blocked';
    const EVENT_BRUTE_FORCE = 'brute_force';
    
    const SEVERITY_LOW = 'low';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_CRITICAL = 'critical';
    
    private static function isEnabled() {
        return Settings::get('enable_security_log', '1') === '1';
    }
    
    public static function log($eventType, $description, $severity = self::SEVERITY_MEDIUM, $userId = null, $username = null, $details = null) {
        if (!self::isEnabled()) {
            return false;
        }
        
        $db = Database::getInstance();
        
        $data = [
            'event_type' => $eventType,
            'severity' => $severity,
            'user_id' => $userId,
            'username' => $username,
            'ip_address' => Auth::getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'request_url' => $_SERVER['REQUEST_URI'] ?? null,
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? null,
            'description' => $description,
            'details' => $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        try {
            $db->insert('`lty-security_logs`', $data);
            
            if (in_array($severity, [self::SEVERITY_HIGH, self::SEVERITY_CRITICAL])) {
                self::sendSecurityAlert($data);
            }
            
            return true;
        } catch (Exception $e) {
            error_log('Failed to log security event: ' . $e->getMessage());
            return false;
        }
    }
    
    private static function sendSecurityAlert($logData) {
        $emailEnabled = Settings::get('email_enabled', '0') === '1';
        if (!$emailEnabled) {
            return;
        }
        
        $securityAlertEmail = Settings::get('security_alert_email', '1') === '1';
        if (!$securityAlertEmail) {
            return;
        }
        
        require_once __DIR__ . '/../api/email.php';
        
        $db = Database::getInstance();
        
        $roles = $db->fetchAll(
            "SELECT id FROM `lty-roles` WHERE email_notify_enabled = 1 AND status = 1"
        );
        
        if (empty($roles)) {
            return;
        }
        
        $roleIds = array_column($roles, 'id');
        $roleIdsStr = implode(',', $roleIds);
        
        $users = $db->fetchAll(
            "SELECT email, name FROM `lty-admin_users` WHERE role_id IN ($roleIdsStr) AND status = 1 AND email IS NOT NULL AND email != ''"
        );
        
        if (empty($users)) {
            return;
        }
        
        $mailer = new Mailer();
        if (!$mailer->isEnabled()) {
            return;
        }
        
        $siteName = Settings::get('site_name', '网站');
        $siteUrl = Settings::get('site_url', '');
        $eventTypes = self::getEventTypes();
        $severityLevels = self::getSeverityLevels();
        
        $eventTypeName = $eventTypes[$logData['event_type']] ?? $logData['event_type'];
        $severityName = $severityLevels[$logData['severity']] ?? $logData['severity'];
        
        $severityColor = $logData['severity'] === self::SEVERITY_CRITICAL ? '#dc2626' : '#ea580c';
        
        $subject = "【{$siteName}】安全警报：{$eventTypeName}";
        
        $body = '<p class="desc" style="color: ' . $severityColor . '; font-weight: 600;">检测到严重安全事件，请及时处理！</p>';
        
        $body .= '<table class="info-table" cellpadding="0" cellspacing="0">';
        $body .= '<tr><td class="label">事件类型</td><td class="value">' . htmlspecialchars($eventTypeName) . '</td></tr>';
        $body .= '<tr><td class="label">严重程度</td><td class="value" style="color: ' . $severityColor . '; font-weight: 600;">' . htmlspecialchars($severityName) . '</td></tr>';
        $body .= '<tr><td class="label">事件描述</td><td class="value">' . htmlspecialchars($logData['description']) . '</td></tr>';
        
        if ($logData['username']) {
            $body .= '<tr><td class="label">用户名</td><td class="value">' . htmlspecialchars($logData['username']) . '</td></tr>';
        }
        
        $body .= '<tr><td class="label">IP地址</td><td class="value">' . htmlspecialchars($logData['ip_address'] ?? '-') . '</td></tr>';
        
        if ($logData['request_url']) {
            $body .= '<tr><td class="label">请求URL</td><td class="value" style="word-break: break-all;">' . htmlspecialchars($logData['request_url']) . '</td></tr>';
        }
        
        $body .= '<tr><td class="label">发生时间</td><td class="value">' . $logData['created_at'] . '</td></tr>';
        $body .= '</table>';
        
        if (!empty($siteUrl)) {
            $body .= '<p style="margin: 24px 0 0 0; text-align: center;"><a href="' . htmlspecialchars($siteUrl) . '/lty-admin/security_logs.php" class="btn">查看安全日志</a></p>';
        }
        
        $body .= '<div class="warning-box" style="margin-top: 24px;">';
        $body .= '<p class="warning-text"><span class="warning-icon">⚠</span>此邮件为系统自动发送，请勿直接回复。如有疑问请联系系统管理员。</p>';
        $body .= '</div>';
        
        $body = $mailer->wrapEmailTemplate($body, '安全警报通知');
        
        foreach ($users as $user) {
            $mailer->send($user['email'], $subject, $body, true, true);
        }
    }
    
    public static function loginFailed($username, $reason = '密码错误', $details = null) {
        return self::log(
            self::EVENT_LOGIN_FAILED,
            "登录失败: {$username} - {$reason}",
            self::SEVERITY_MEDIUM,
            null,
            $username,
            $details
        );
    }
    
    public static function unauthorizedAccess($description = '未授权访问', $details = null) {
        return self::log(
            self::EVENT_UNAUTHORIZED_ACCESS,
            $description,
            self::SEVERITY_HIGH,
            null,
            null,
            $details
        );
    }
    
    public static function permissionDenied($userId, $username, $permission, $details = null) {
        return self::log(
            self::EVENT_PERMISSION_DENIED,
            "权限拒绝: 用户 {$username} 尝试访问 {$permission}",
            self::SEVERITY_MEDIUM,
            $userId,
            $username,
            $details
        );
    }
    
    public static function csrfInvalid($details = null) {
        return self::log(
            self::EVENT_CSRF_INVALID,
            'CSRF验证失败',
            self::SEVERITY_HIGH,
            null,
            null,
            $details
        );
    }
    
    public static function sessionExpired($userId = null, $username = null, $details = null) {
        return self::log(
            self::EVENT_SESSION_EXPIRED,
            '会话过期',
            self::SEVERITY_LOW,
            $userId,
            $username,
            $details
        );
    }
    
    public static function ipBlocked($ip, $reason = 'IP被封禁', $details = null) {
        return self::log(
            self::EVENT_IP_BLOCKED,
            "IP封禁: {$ip} - {$reason}",
            self::SEVERITY_CRITICAL,
            null,
            null,
            $details
        );
    }
    
    public static function bruteForce($username, $attempts, $details = null) {
        return self::log(
            self::EVENT_BRUTE_FORCE,
            "暴力破解尝试: {$username} ({$attempts}次尝试)",
            self::SEVERITY_CRITICAL,
            null,
            $username,
            $details
        );
    }
    
    public static function getEventTypes() {
        return [
            self::EVENT_LOGIN_FAILED => '登录失败',
            self::EVENT_UNAUTHORIZED_ACCESS => '未授权访问',
            self::EVENT_PERMISSION_DENIED => '权限拒绝',
            self::EVENT_CSRF_INVALID => 'CSRF验证失败',
            self::EVENT_SESSION_EXPIRED => '会话过期',
            self::EVENT_IP_BLOCKED => 'IP封禁',
            self::EVENT_BRUTE_FORCE => '暴力破解'
        ];
    }
    
    public static function getSeverityLevels() {
        return [
            self::SEVERITY_LOW => '低',
            self::SEVERITY_MEDIUM => '中',
            self::SEVERITY_HIGH => '高',
            self::SEVERITY_CRITICAL => '严重'
        ];
    }
    
    public static function getSeverityClass($severity) {
        $classes = [
            self::SEVERITY_LOW => 'text-info',
            self::SEVERITY_MEDIUM => 'text-warning',
            self::SEVERITY_HIGH => 'text-orange',
            self::SEVERITY_CRITICAL => 'text-danger'
        ];
        return $classes[$severity] ?? 'text-secondary';
    }
    
    public static function getSeverityBadgeClass($severity) {
        $classes = [
            self::SEVERITY_LOW => 'badge-info',
            self::SEVERITY_MEDIUM => 'badge-warning',
            self::SEVERITY_HIGH => 'badge-orange',
            self::SEVERITY_CRITICAL => 'badge-danger'
        ];
        return $classes[$severity] ?? 'badge-secondary';
    }
}

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

require_once __DIR__ . '/functions.php';

class APISecurity {
    private static $rateLimitData = [];
    
    public static function checkRateLimit($identifier = null) {
        $limit = Settings::get('api_rate_limit', 100);
        $window = 3600;
        
        if ($identifier === null) {
            $identifier = Auth::check() ? $_SESSION['admin_id'] : self::getClientIP();
        }
        
        $now = time();
        $windowStart = $now - $window;
        
        if (!isset(self::$rateLimitData[$identifier])) {
            self::$rateLimitData[$identifier] = [];
        }
        
        self::$rateLimitData[$identifier] = array_filter(
            self::$rateLimitData[$identifier],
            function($timestamp) use ($windowStart) {
                return $timestamp > $windowStart;
            }
        );
        
        if (count(self::$rateLimitData[$identifier]) >= $limit) {
            json_response([
                'error' => '请求过于频繁，请稍后再试',
                'retry_after' => $window
            ], 429);
        }
        
        self::$rateLimitData[$identifier][] = $now;
        
        header('X-RateLimit-Limit: ' . $limit);
        header('X-RateLimit-Remaining: ' . ($limit - count(self::$rateLimitData[$identifier])));
        header('X-RateLimit-Reset: ' . ($windowStart + $window));
    }
    
    public static function checkCSRF() {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return;
        }
        
        $token = '';
        
        $headers = getallheaders();
        if (isset($headers['X-CSRF-Token'])) {
            $token = $headers['X-CSRF-Token'];
        } elseif (isset($_POST['csrf_token'])) {
            $token = $_POST['csrf_token'];
        } elseif (isset($_GET['csrf_token'])) {
            $token = $_GET['csrf_token'];
        }
        
        if (empty($token)) {
            json_response(['error' => 'CSRF Token 缺失'], 403);
        }
        
        if (!Auth::validateCSRFToken($token)) {
            json_response(['error' => 'CSRF Token 无效或已过期'], 403);
        }
    }
    
    public static function checkHTTPS() {
        if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
            if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
                return;
            }
            
            $protocol = isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ? $_SERVER['HTTP_X_FORWARDED_PROTO'] : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? '';
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            
            if ($protocol !== 'https') {
                header('Location: https://' . $host . $uri);
                exit;
            }
        }
    }
    
    public static function setSecurityHeaders() {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        header('Content-Security-Policy: default-src \'self\'');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    }
    
    public static function validateInput($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[$field] = $rule['message'] ?? '该字段不能为空';
                continue;
            }
            
            if (!empty($value)) {
                if (isset($rule['type'])) {
                    switch ($rule['type']) {
                        case 'email':
                            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                                $errors[$field] = $rule['message'] ?? '邮箱格式不正确';
                            }
                            break;
                        case 'url':
                            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                                $errors[$field] = $rule['message'] ?? 'URL格式不正确';
                            }
                            break;
                        case 'int':
                            if (!is_numeric($value) || (int)$value != $value) {
                                $errors[$field] = $rule['message'] ?? '必须是整数';
                            }
                            break;
                    }
                }
                
                if (isset($rule['min']) && strlen($value) < $rule['min']) {
                    $errors[$field] = $rule['message'] ?? "长度不能少于{$rule['min']}个字符";
                }
                
                if (isset($rule['max']) && strlen($value) > $rule['max']) {
                    $errors[$field] = $rule['message'] ?? "长度不能超过{$rule['max']}个字符";
                }
                
                if (isset($rule['pattern']) && !preg_match($rule['pattern'], $value)) {
                    $errors[$field] = $rule['message'] ?? '格式不正确';
                }
            }
        }
        
        return $errors;
    }
    
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
    
    public static function logSecurityEvent($event, $details = []) {
        $enableLog = Settings::get('enable_security_log', '1');
        if ($enableLog !== '1') {
            return;
        }
        
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => self::getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'user_id' => isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : null,
            'details' => $details
        ];
        
        $logFile = __DIR__ . '/../logs/security.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND);
        
        $eventTypeMap = [
            'unauthorized_access' => 'unauthorized_access',
            'password_verification_failed' => 'login_failed',
            'password_change_failed' => 'login_failed',
            'password_changed' => 'login_failed',
            'csrf_failed' => 'csrf_invalid',
            'rate_limit_exceeded' => 'brute_force',
        ];
        
        $severityMap = [
            'unauthorized_access' => 'high',
            'password_verification_failed' => 'medium',
            'password_change_failed' => 'medium',
            'password_changed' => 'low',
            'csrf_failed' => 'high',
            'rate_limit_exceeded' => 'high',
            'email_test' => 'low',
            'email_sent' => 'low',
            'email_template_sent' => 'low',
            'email_notify_roles' => 'low',
        ];
        
        $eventType = $eventTypeMap[$event] ?? 'unauthorized_access';
        $severity = $severityMap[$event] ?? 'medium';
        
        $userId = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : null;
        $username = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : null;
        
        SecurityLog::log(
            $eventType,
            "API安全事件: {$event}",
            $severity,
            $userId,
            $username,
            $details
        );
    }
    
    private static function getClientIP() {
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
}

function require_auth() {
    if (!Auth::check()) {
        APISecurity::logSecurityEvent('unauthorized_access', [
            'endpoint' => $_SERVER['REQUEST_URI']
        ]);
        json_response(['error' => '未授权访问'], 401);
    }
}

function require_csrf() {
    APISecurity::checkCSRF();
}

function require_rate_limit($identifier = null) {
    APISecurity::checkRateLimit($identifier);
}

function require_password_verification($password) {
    if (!Auth::requirePasswordVerification($password)) {
        APISecurity::logSecurityEvent('password_verification_failed', [
            'endpoint' => $_SERVER['REQUEST_URI'],
            'user_id' => $_SESSION['admin_id'] ?? null
        ]);
        json_response(['error' => '密码验证失败'], 403);
    }
}
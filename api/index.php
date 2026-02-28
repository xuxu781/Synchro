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
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$postData = $_POST;
if (empty($postData)) {
    $input = file_get_contents('php://input');
    if (!empty($input)) {
        parse_str($input, $postData);
    }
}

$action = $postData['action'] ?? $_GET['action'] ?? '';

require_once __DIR__ . '/../includes/functions.php';

function generateVerificationCode() {
    return str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

function buildEmailTemplate($title, $contentHtml, $options = []) {
    $siteName = Settings::get('site_name', '网站');
    $siteUrl = Settings::get('site_url', '');
    $logoImage = Settings::get('logo_image', '');
    $logoUrl = '';
    if (!empty($logoImage)) {
        if (preg_match('/^https?:\/\//i', $logoImage)) {
            $logoUrl = $logoImage;
        } elseif (!empty($siteUrl)) {
            $logoUrl = rtrim($siteUrl, '/') . '/' . ltrim($logoImage, '/');
        } else {
            $logoUrl = $logoImage;
        }
    }
    
    $footerContent1 = Settings::get('email_footer_content1', '');
    $footerContent2 = Settings::get('email_footer_content2', '');
    
    $footerHtml = '';
    if (!empty($footerContent1) || !empty($footerContent2)) {
        $footerHtml = '<tr><td style="padding: 24px 40px; background-color: #fafafa; border-top: 1px solid #eee;">';
        if (!empty($footerContent1)) {
            $footerHtml .= '<p style="margin: 0 0 12px 0; font-size: 13px; color: #666; line-height: 1.6;">' . $footerContent1 . '</p>';
        }
        if (!empty($footerContent2)) {
            $footerHtml .= '<p style="margin: 0; font-size: 13px; color: #666; line-height: 1.6;">' . $footerContent2 . '</p>';
        }
        $footerHtml .= '</td></tr>';
    }
    
    $hideWarning = $options['hideWarning'] ?? false;
    $warningHtml = '';
    if (!$hideWarning) {
        $warningHtml = '<div class="warning-box">
            <p class="warning-text"><span class="warning-icon">⚠</span>安全提示：此邮件由系统自动发送，请勿直接回复。如有疑问，请联系网站管理员。</p>
        </div>';
    }
    
    return '<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>' . htmlspecialchars($title) . '</title>
    <style type="text/css">
        body { margin: 0; padding: 0; background-color: #f5f5f5; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "PingFang SC", "Microsoft YaHei", sans-serif; }
        .email-body { background-color: #f5f5f5; padding: 40px 20px; }
        .email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 4px; overflow: hidden; box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08); }
        .header { padding: 32px 40px; border-bottom: 1px solid #eee; text-align: center; }
        .logo { max-height: 40px; margin-bottom: 12px; }
        .brand-name { font-size: 18px; color: #333; font-weight: 600; margin: 0; }
        .content { padding: 40px; }
        .title { font-size: 20px; color: #333; font-weight: 600; margin: 0 0 24px 0; text-align: center; }
        .desc { font-size: 14px; color: #666; line-height: 1.8; margin: 0 0 32px 0; text-align: center; }
        .code-box { background: linear-gradient(135deg, #ff7a00 0%, #ff9a00 100%); border-radius: 8px; padding: 32px; text-align: center; margin: 0 0 32px 0; }
        .code-label { font-size: 13px; color: rgba(255,255,255,0.9); margin: 0 0 12px 0; }
        .code { font-size: 36px; font-weight: 700; color: #ffffff; letter-spacing: 8px; font-family: "Courier New", Courier, monospace; margin: 0; }
        .info-table { width: 100%; border-collapse: collapse; margin: 0 0 24px 0; }
        .info-table td { padding: 12px 16px; font-size: 14px; border-bottom: 1px solid #f0f0f0; }
        .info-table .label { color: #999; width: 100px; }
        .info-table .value { color: #333; }
        .warning-box { background-color: #fffbe6; border: 1px solid #ffe58f; border-radius: 4px; padding: 16px; margin: 0 0 24px 0; }
        .warning-icon { color: #faad14; font-size: 16px; margin-right: 8px; }
        .warning-text { font-size: 13px; color: #ad8b00; line-height: 1.6; margin: 0; }
        .footer { padding: 24px 40px; background-color: #fafafa; border-top: 1px solid #eee; text-align: center; }
        .footer-text { font-size: 12px; color: #999; line-height: 1.8; margin: 0; }
        .footer-link { color: #ff7a00; text-decoration: none; }
        .divider { height: 1px; background-color: #eee; margin: 16px 0; }
        .content-body { font-size: 14px; color: #333; line-height: 1.8; }
        @media only screen and (max-width: 600px) {
            .email-body { padding: 20px 10px; }
            .header { padding: 24px 20px; }
            .content { padding: 24px 20px; }
            .code { font-size: 28px; letter-spacing: 4px; }
            .footer { padding: 20px; }
        }
    </style>
</head>
<body>
    <div class="email-body">
        <div class="email-container">
            <div class="header">
                ' . (!empty($logoUrl) 
                    ? '<img src="' . htmlspecialchars($logoUrl) . '" alt="' . htmlspecialchars($siteName) . '" style="display: block; margin: 0 auto; max-height: 50px; max-width: 200px;" />' 
                    : '<p class="brand-name">' . htmlspecialchars($siteName) . '</p>') . '
            </div>
            
            <div class="content">
                <p class="title">' . htmlspecialchars($title) . '</p>
                ' . $contentHtml . '
                ' . $warningHtml . '
            </div>
            
            ' . $footerHtml . '
            
            <div class="footer">
                <p class="footer-text">
                    <a href="' . htmlspecialchars($siteUrl) . '" class="footer-link">' . htmlspecialchars($siteName) . '</a>
                    <span style="margin: 0 8px;">|</span>
                    <a href="https://lintuyue.com" class="footer-link" target="_blank">技术支持</a>
                </p>
                <div class="divider"></div>
            </div>
        </div>
    </div>
</body>
</html>';
}

function sendVerificationEmail($to, $code) {
    $emailEnabled = Settings::get('email_enabled', '0') === '1';
    $smtpHost = Settings::get('email_smtp_host', '');
    $smtpUsername = Settings::get('email_smtp_username', '');
    
    if (!$emailEnabled || empty($smtpHost) || empty($smtpUsername)) {
        return ['success' => false, 'message' => '邮件服务未启用或配置不完整'];
    }
    
    $subject = '邮箱验证码';
    
    $contentHtml = '<p class="desc">您好！您正在进行邮箱验证操作，请使用以下验证码完成验证：</p>
        
        <div class="code-box">
            <p class="code-label">验证码</p>
            <p class="code">' . $code . '</p>
        </div>
        
        <table class="info-table" cellpadding="0" cellspacing="0">
            <tr>
                <td class="label">接收邮箱</td>
                <td class="value">' . htmlspecialchars($to) . '</td>
            </tr>
            <tr>
                <td class="label">有效期</td>
                <td class="value">10 分钟</td>
            </tr>
            <tr>
                <td class="label">发送时间</td>
                <td class="value">' . date('Y-m-d H:i:s') . '</td>
            </tr>
        </table>
        
        <div class="warning-box">
            <p class="warning-text"><span class="warning-icon">⚠</span>安全提示：验证码仅用于本次操作，请勿将验证码告知他人。如非本人操作，请忽略此邮件。</p>
        </div>';
    
    $body = buildEmailTemplate('邮箱验证码', $contentHtml, ['hideWarning' => true]);
    
    if (!defined('INCLUDE_MAILER_ONLY')) {
        define('INCLUDE_MAILER_ONLY', true);
    }
    require_once __DIR__ . '/email.php';
    
    $mailer = new Mailer();
    return $mailer->send($to, $subject, $body, true, true);
}

function sendReplyEmail($to, $name, $subject, $content) {
    $emailEnabled = Settings::get('email_enabled', '0') === '1';
    $smtpHost = Settings::get('email_smtp_host', '');
    $smtpUsername = Settings::get('email_smtp_username', '');
    
    if (!$emailEnabled || empty($smtpHost) || empty($smtpUsername)) {
        return ['success' => false, 'message' => '邮件服务未启用或配置不完整'];
    }
    

    
    $contentHtml = '<p class="desc">亲爱的 ' . htmlspecialchars($name) . '，您好！</p>
        <div class="content-body" style="margin: 24px 0; padding: 20px; background-color: #fafafa; border-radius: 8px; border-left: 4px solid #ff7a00;">
            ' . nl2br(htmlspecialchars($content)) . '
        </div>
        <table class="info-table" cellpadding="0" cellspacing="0">
            <tr>
                <td class="label">接收邮箱</td>
                <td class="value">' . htmlspecialchars($to) . '</td>
            </tr>
            <tr>
                <td class="label">发送时间</td>
                <td class="value">' . date('Y-m-d H:i:s') . '</td>
            </tr>
        </table>';
    
    $body = buildEmailTemplate('留言回复', $contentHtml);
    
    if (!defined('INCLUDE_MAILER_ONLY')) {
        define('INCLUDE_MAILER_ONLY', true);
    }
    require_once __DIR__ . '/email.php';
    
    $mailer = new Mailer();
    return $mailer->send($to, $subject, $body, true, true);
}

switch ($action) {
    case 'info':
        $info = [
            'site_name' => Settings::get('site_name', '团队官网'),
            'site_description' => Settings::get('site_description', '专业的团队官方网站'),
            'version' => VERSION,
            'contact_email' => Settings::get('contact_email', ''),
            'contact_phone' => Settings::get('contact_phone', ''),
            'contact_address' => Settings::get('contact_address', '')
        ];
        json_response(['success' => true, 'data' => $info]);
        break;
        
    case 'send_verification_code':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['error' => 'Method not allowed'], 405);
        }
        
        $email = trim($postData['email'] ?? '');
        
        if (empty($email)) {
            json_response(['error' => '请输入邮箱地址'], 400);
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(['error' => '邮箱格式不正确'], 400);
        }
        
        $emailEnabled = Settings::get('email_enabled', '0') === '1';
        $smtpHost = Settings::get('email_smtp_host', '');
        $smtpUsername = Settings::get('email_smtp_username', '');
        
        if (!$emailEnabled || empty($smtpHost) || empty($smtpUsername)) {
            json_response(['error' => '邮件服务未启用或配置不完整，请联系管理员'], 400);
        }
        
        try {
            $db = Database::getInstance();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            
            $code = generateVerificationCode();
            $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            try {
                $db->insert('`lty-email_verification_codes`', [
                    'email' => $email,
                    'code' => $code,
                    'type' => 'contact',
                    'ip_address' => $ip,
                    'expires_at' => $expiresAt
                ]);
            } catch (Exception $e) {
                $db->query("CREATE TABLE IF NOT EXISTS `lty-email_verification_codes` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `email` varchar(100) NOT NULL,
                    `code` varchar(6) NOT NULL,
                    `type` varchar(50) DEFAULT 'contact',
                    `ip_address` varchar(45) DEFAULT NULL,
                    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                    `expires_at` timestamp NOT NULL,
                    `used` tinyint(1) DEFAULT 0,
                    PRIMARY KEY (`id`),
                    KEY `email` (`email`),
                    KEY `code` (`code`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                
                $db->insert('`lty-email_verification_codes`', [
                    'email' => $email,
                    'code' => $code,
                    'type' => 'contact',
                    'ip_address' => $ip,
                    'expires_at' => $expiresAt
                ]);
            }
            
            $result = sendVerificationEmail($email, $code);
            
            if ($result['success']) {
                json_response(['success' => true, 'message' => '验证码已发送到您的邮箱']);
            } else {
                json_response(['error' => $result['message'] ?? '验证码发送失败'], 500);
            }
        } catch (Exception $e) {
            json_response(['error' => '发送失败：' . $e->getMessage()], 500);
        }
        break;
        
    case 'reply_message':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['error' => 'Method not allowed'], 405);
        }
        
        $to = trim($postData['email'] ?? '');
        $name = trim($postData['name'] ?? '');
        $subject = trim($postData['subject'] ?? '');
        $content = trim($postData['content'] ?? '');
        
        if (empty($to) || empty($name) || empty($subject) || empty($content)) {
            json_response(['error' => '请填写完整信息'], 400);
        }
        
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            json_response(['error' => '邮箱格式不正确'], 400);
        }
        
        try {
            $result = sendReplyEmail($to, $name, $subject, $content);
            
            if ($result['success']) {
                json_response(['success' => true, 'message' => '回复邮件发送成功']);
            } else {
                json_response(['error' => $result['message'] ?? '发送失败'], 500);
            }
        } catch (Exception $e) {
            json_response(['error' => '发送失败：' . $e->getMessage()], 500);
        }
        break;
        
    case 'contact':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['error' => 'Method not allowed'], 405);
        }
        
        $type_id = isset($postData['type_id']) && !empty($postData['type_id']) ? (int)$postData['type_id'] : null;
        $name = trim($postData['name'] ?? '');
        $email = trim($postData['email'] ?? '');
        $phone = trim($postData['phone'] ?? '');
        $subject = trim($postData['subject'] ?? '');
        $message = trim($postData['message'] ?? '');
        $verification_code = trim($postData['verification_code'] ?? '');
        
        if (empty($type_id)) {
            json_response(['error' => '请选择咨询类型'], 400);
        }
        
        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            json_response(['error' => '请填写必填项'], 400);
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(['error' => '邮箱格式不正确'], 400);
        }
        
        if (!empty($phone) && !preg_match('/^1[3-9]\d{9}$/', $phone)) {
            json_response(['error' => '手机号格式不正确，请输入11位有效手机号'], 400);
        }
        
        if (empty($verification_code)) {
            json_response(['error' => '请输入邮箱验证码'], 400);
        }
        
        try {
            $db = Database::getInstance();
            
            $validCode = $db->fetchOne(
                "SELECT * FROM `lty-email_verification_codes` 
                 WHERE email = ? AND code = ? AND type = 'contact' 
                 AND used = 0 AND expires_at > NOW()
                 ORDER BY created_at DESC LIMIT 1",
                [$email, $verification_code]
            );
            
            if (!$validCode) {
                SecurityLog::log(
                    'login_failed',
                    "验证码验证失败: {$email}",
                    'medium',
                    null,
                    null,
                    ['email' => $email, 'code' => $verification_code]
                );
                json_response(['error' => '验证码无效或已过期'], 400);
            }
            
            $db->update('`lty-email_verification_codes`', ['used' => 1], 'id = ?', [$validCode['id']]);
            
            $insertData = [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'message' => $message,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
            ];
            
            if ($type_id) {
                $insertData['type_id'] = $type_id;
            }
            
            if (!empty($subject)) {
                $insertData['subject'] = $subject;
            }
            
            $db->insert('`lty-contact_messages`', $insertData);
            
            if (!defined('INCLUDE_MAILER_ONLY')) {
                define('INCLUDE_MAILER_ONLY', true);
            }
            require_once __DIR__ . '/email.php';
            
            $mailer = new Mailer();
            if ($mailer->isEnabled()) {
                $mailer->sendContactNotificationToRoles($insertData);
            }
            
            json_response(['success' => true, 'message' => '提交成功']);
        } catch (Exception $e) {
            json_response(['error' => '提交失败，请稍后重试'], 500);
        }
        break;
        
    default:
        json_response(['error' => 'Invalid action', 'received_action' => $action], 400);
}

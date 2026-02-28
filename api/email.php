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

if (!defined('INCLUDE_MAILER_ONLY')) {
    require_auth();
}

header('Content-Type: application/json');

class Mailer {
    private $smtpHost;
    private $smtpPort;
    private $smtpUsername;
    private $smtpPassword;
    private $smtpEncryption;
    private $fromName;
    private $subjectPrefix;
    private $footerContent1;
    private $footerContent2;
    private $enabled;
    private $socket;
    
    public function __construct() {
        $this->smtpHost = Settings::get('email_smtp_host', '');
        $this->smtpPort = intval(Settings::get('email_smtp_port', '465'));
        $this->smtpUsername = Settings::get('email_smtp_username', '');
        $this->smtpPassword = Settings::get('email_smtp_password', '');
        $this->smtpEncryption = Settings::get('email_smtp_encryption', 'ssl');
        $this->fromName = Settings::get('email_from_name', '');
        $this->subjectPrefix = Settings::get('email_subject_prefix', '');
        $this->footerContent1 = Settings::get('email_footer_content1', '');
        $this->footerContent2 = Settings::get('email_footer_content2', '');
        $this->enabled = Settings::get('email_enabled', '0') === '1';
    }
    
    public function isEnabled() {
        return $this->enabled && !empty($this->smtpHost) && !empty($this->smtpUsername);
    }
    
    public function send($to, $subject, $body, $isHtml = true, $skipFooter = false) {
        if (!$this->isEnabled()) {
            return ['success' => false, 'message' => '邮件服务未启用或配置不完整'];
        }
        
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => '收件人邮箱地址无效'];
        }
        
        $fromEmail = $this->smtpUsername;
        $fromName = !empty($this->fromName) ? $this->fromName : Settings::get('site_name', '网站');
        
        if (!empty($this->subjectPrefix)) {
            $subject = $this->subjectPrefix . $subject;
        }
        
        if (!$skipFooter) {
            if ($isHtml) {
                if (!empty($this->footerContent1)) {
                    $body .= '<br><br>' . $this->footerContent1;
                }
                if (!empty($this->footerContent2)) {
                    $body .= '<br><br>' . $this->footerContent2;
                }
            } else {
                if (!empty($this->footerContent1)) {
                    $body .= "\n\n" . strip_tags($this->footerContent1);
                }
                if (!empty($this->footerContent2)) {
                    $body .= "\n\n" . strip_tags($this->footerContent2);
                }
            }
        }
        
        try {
            if (!$this->connect()) {
                return ['success' => false, 'message' => '无法连接到SMTP服务器'];
            }
            
            $this->readResponse();
            
            $hostname = 'localhost';
            if (isset($_SERVER['HTTP_HOST'])) {
                $hostname = preg_replace('/[^a-zA-Z0-9.\-]/', '', $_SERVER['HTTP_HOST']);
                if (empty($hostname)) $hostname = 'localhost';
            }
            
            if (!$this->sendCmd('EHLO ' . $hostname, '250')) {
                throw new Exception('EHLO命令失败');
            }
            
            if ($this->smtpEncryption === 'tls') {
                if (!$this->sendCmd('STARTTLS', '220')) {
                    throw new Exception('STARTTLS命令失败');
                }
                
                if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new Exception('TLS加密启动失败');
                }
                
                if (!$this->sendCmd('EHLO ' . $hostname, '250')) {
                    throw new Exception('EHLO命令失败(TLS)');
                }
            }
            
            if (!$this->sendCmd('AUTH LOGIN', '334')) {
                throw new Exception('AUTH LOGIN失败');
            }
            
            if (!$this->sendCmd(base64_encode($this->smtpUsername), '334')) {
                throw new Exception('用户名验证失败');
            }
            
            if (!$this->sendCmd(base64_encode($this->smtpPassword), '235')) {
                throw new Exception('密码验证失败，请检查SMTP用户名和密码/授权码');
            }
            
            if (!$this->sendCmd('MAIL FROM:<' . $fromEmail . '>', '250')) {
                throw new Exception('MAIL FROM失败');
            }
            
            if (!$this->sendCmd('RCPT TO:<' . $to . '>', ['250', '251'])) {
                throw new Exception('RCPT TO失败');
            }
            
            if (!$this->sendCmd('DATA', '354')) {
                throw new Exception('DATA命令失败');
            }
            
            $email = $this->buildEmail($to, $fromEmail, $fromName, $subject, $body, $isHtml);
            $this->write($email);
            
            if (!$this->sendCmd('.', '250')) {
                throw new Exception('邮件内容发送失败');
            }
            
            $this->sendCmd('QUIT', '221', false);
            $this->close();
            
            return ['success' => true, 'message' => '邮件发送成功'];
            
        } catch (Exception $e) {
            $this->close();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    private function buildEmail($to, $fromEmail, $fromName, $subject, $body, $isHtml) {
        $headers = [];
        $headers[] = 'Date: ' . date('r');
        $headers[] = 'To: <' . $to . '>';
        $headers[] = 'From: ' . $this->encodeHeader($fromName) . ' <' . $fromEmail . '>';
        $headers[] = 'Subject: ' . $this->encodeHeader($subject);
        $headers[] = 'Message-ID: <' . uniqid() . '@' . ($this->smtpHost ?: 'localhost') . '>';
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: ' . ($isHtml ? 'text/html' : 'text/plain') . '; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: base64';
        
        return implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($body)) . "\r\n.";
    }
    
    private function connect() {
        $protocol = '';
        if ($this->smtpEncryption === 'ssl') {
            $protocol = 'ssl://';
        }
        
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);
        
        $this->socket = @stream_socket_client(
            $protocol . $this->smtpHost . ':' . $this->smtpPort,
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT,
            $context
        );
        
        if (!$this->socket) {
            return false;
        }
        
        stream_set_timeout($this->socket, 30);
        return true;
    }
    
    private function close() {
        if ($this->socket) {
            @fclose($this->socket);
            $this->socket = null;
        }
    }
    
    private function write($data) {
        fwrite($this->socket, $data . "\r\n");
    }
    
    private function readResponse() {
        $response = '';
        while ($line = fgets($this->socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        return trim($response);
    }
    
    private function sendCmd($cmd, $expectedCode, $checkResponse = true) {
        $this->write($cmd);
        
        if (!$checkResponse) {
            return true;
        }
        
        $response = $this->readResponse();
        $code = substr($response, 0, 3);
        
        $expectedCodes = is_array($expectedCode) ? $expectedCode : [$expectedCode];
        
        return in_array($code, $expectedCodes);
    }
    
    private function encodeHeader($str) {
        if (preg_match('/[^\x20-\x7E]/', $str)) {
            return '=?UTF-8?B?' . base64_encode($str) . '?=';
        }
        return $str;
    }
    
    public function wrapEmailTemplate($content, $title = '') {
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
        
        return '<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>' . htmlspecialchars($title ?: $siteName) . '</title>
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
        .success-box { background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 4px; padding: 16px; margin: 0 0 24px 0; }
        .success-icon { color: #22c55e; font-size: 16px; margin-right: 8px; }
        .success-text { font-size: 13px; color: #166534; line-height: 1.6; margin: 0; }
        .footer { padding: 24px 40px; background-color: #fafafa; border-top: 1px solid #eee; text-align: center; }
        .footer-text { font-size: 12px; color: #999; line-height: 1.8; margin: 0; }
        .footer-link { color: #ff7a00; text-decoration: none; }
        .divider { height: 1px; background-color: #eee; margin: 16px 0; }
        .content-body { font-size: 14px; color: #333; line-height: 1.8; }
        .btn { display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #ff7a00 0%, #ff9a00 100%); color: #ffffff; text-decoration: none; border-radius: 6px; font-size: 14px; font-weight: 500; }
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
                ' . (!empty($title) ? '<p class="title">' . htmlspecialchars($title) . '</p>' : '') . '
                ' . $content . '
            </div>
            
            <div class="footer">
                <p class="footer-text">
                    <a href="' . htmlspecialchars($siteUrl) . '" class="footer-link">' . htmlspecialchars($siteName) . '</a>
                    <span style="margin: 0 8px;">|</span>
                    <a href="https://lintuyue.com" class="footer-link" target="_blank">技术支持</a>
                </p>
                <div class="divider"></div>
                <p class="footer-text">© 2026 徐州邻兔跃IT工作室 版权所有</p>
                <p class="footer-text">联系邮箱：lintuyue@qq.com | 技术交流QQ群：550133641</p>
            </div>
        </div>
    </div>
</body>
</html>';
    }
    
    public function sendTemplate($to, $templateName, $variables = []) {
        $templates = [
            'test' => [
                'subject' => '测试邮件',
                'body' => '<p class="desc">这是一封测试邮件，用于验证邮件服务配置是否正确。</p>
        
        <table class="info-table" cellpadding="0" cellspacing="0">
            <tr>
                <td class="label">发送时间</td>
                <td class="value">' . date('Y-m-d H:i:s') . '</td>
            </tr>
        </table>
        
        <div class="success-box">
            <p class="success-text"><span class="success-icon">✓</span>如果您收到这封邮件，说明邮件服务配置成功！</p>
        </div>'
            ],
            'contact_notification' => [
                'subject' => '新留言通知',
                'body' => '<p class="desc">您收到一条新留言，请及时查看处理。</p>
        
        <table class="info-table" cellpadding="0" cellspacing="0">
            <tr>
                <td class="label">姓名</td>
                <td class="value">{name}</td>
            </tr>
            <tr>
                <td class="label">邮箱</td>
                <td class="value">{email}</td>
            </tr>
            <tr>
                <td class="label">电话</td>
                <td class="value">{phone}</td>
            </tr>
            <tr>
                <td class="label">主题</td>
                <td class="value">{subject}</td>
            </tr>
            <tr>
                <td class="label">内容</td>
                <td class="value">{message}</td>
            </tr>
            <tr>
                <td class="label">时间</td>
                <td class="value">{time}</td>
            </tr>
        </table>'
            ],
            'contact_reply' => [
                'subject' => '感谢您的留言',
                'body' => '<p class="desc">亲爱的 <strong>{name}</strong>，您好！</p>
        
        <div class="content-body" style="margin: 24px 0; padding: 20px; background-color: #fafafa; border-radius: 8px; border-left: 4px solid #ff7a00;">
            <p style="margin: 0 0 12px 0;">我们已收到您的留言，感谢您对我们网站的关注和支持！</p>
            <p style="margin: 0;">我们会尽快处理您的留言，如有需要会通过邮件或电话与您联系。</p>
        </div>
        
        <div class="success-box">
            <p class="success-text"><span class="success-icon">✓</span>再次感谢您的支持！</p>
        </div>'
            ]
        ];
        
        if (!isset($templates[$templateName])) {
            return ['success' => false, 'message' => '邮件模板不存在'];
        }
        
        $template = $templates[$templateName];
        $subject = $template['subject'];
        $body = $template['body'];
        
        foreach ($variables as $key => $value) {
            $body = str_replace('{' . $key . '}', htmlspecialchars($value), $body);
        }
        
        $body = $this->wrapEmailTemplate($body, $subject);
        
        return $this->send($to, $subject, $body, true, true);
    }
    
    public function sendToRoles($roleIds, $subject, $body, $isHtml = true) {
        if (!$this->isEnabled()) {
            return ['success' => false, 'message' => '邮件服务未启用或配置不完整'];
        }
        
        if (empty($roleIds)) {
            return ['success' => false, 'message' => '未选择通知角色'];
        }
        
        $db = Database::getInstance();
        
        $roleIds = array_map('intval', (array)$roleIds);
        $roleIdsStr = implode(',', $roleIds);
        
        $users = $db->fetchAll(
            "SELECT email, name FROM `lty-admin_users` WHERE role_id IN ($roleIdsStr) AND status = 1 AND email IS NOT NULL AND email != ''"
        );
        
        if (empty($users)) {
            return ['success' => false, 'message' => '所选角色没有可通知的成员'];
        }
        
        $successCount = 0;
        $failedEmails = [];
        
        foreach ($users as $user) {
            $result = $this->send($user['email'], $subject, $body, $isHtml);
            if ($result['success']) {
                $successCount++;
            } else {
                $failedEmails[] = $user['email'];
            }
        }
        
        if ($successCount === count($users)) {
            return ['success' => true, 'message' => "成功发送 {$successCount} 封通知邮件"];
        } elseif ($successCount > 0) {
            return ['success' => true, 'message' => "成功发送 {$successCount} 封邮件，" . count($failedEmails) . " 封发送失败"];
        } else {
            return ['success' => false, 'message' => '所有邮件发送失败'];
        }
    }
    
    public function sendContactNotificationToRoles($messageData) {
        $emailEnabled = Settings::get('email_enabled', '0') === '1';
        if (!$emailEnabled) {
            return ['success' => false, 'message' => '邮件服务未启用'];
        }
        
        $db = Database::getInstance();
        
        $roles = $db->fetchAll(
            "SELECT id, name, email_notify_content, email_notify_types FROM `lty-roles` WHERE email_notify_enabled = 1 AND status = 1"
        );
        
        $roles = array_filter($roles, function($role) {
            $types = json_decode($role['email_notify_types'] ?? '[]', true);
            return in_array('contact', $types);
        });
        
        if (empty($roles)) {
            return ['success' => false, 'message' => '没有启用邮件通知的角色'];
        }
        
        $roleIds = array_column($roles, 'id');
        $roleContents = [];
        foreach ($roles as $role) {
            $roleContents[$role['id']] = $role['email_notify_content'];
        }
        
        $roleIdsStr = implode(',', $roleIds);
        
        $users = $db->fetchAll(
            "SELECT id, email, name, role_id FROM `lty-admin_users` WHERE role_id IN ($roleIdsStr) AND status = 1 AND email IS NOT NULL AND email != ''"
        );
        
        if (empty($users)) {
            return ['success' => false, 'message' => '所选角色没有可通知的成员'];
        }
        
        $successCount = 0;
        $failedEmails = [];
        
        foreach ($users as $user) {
            $customContent = $roleContents[$user['role_id']] ?? '';
            
            $siteName = Settings::get('site_name', '网站');
            $subject = "【{$siteName}】新留言通知";
            
            if (!empty($customContent)) {
                $body = $customContent;
                $body = str_replace('{name}', htmlspecialchars($messageData['name'] ?? ''), $body);
                $body = str_replace('{email}', htmlspecialchars($messageData['email'] ?? ''), $body);
                $body = str_replace('{phone}', htmlspecialchars($messageData['phone'] ?? ''), $body);
                $body = str_replace('{subject}', htmlspecialchars($messageData['subject'] ?? ''), $body);
                $body = str_replace('{message}', nl2br(htmlspecialchars($messageData['message'] ?? '')), $body);
                $body = str_replace('{time}', date('Y-m-d H:i:s'), $body);
            } else {
                $body = '<p class="desc">您收到一条新留言，请及时查看处理。</p>';
                $body .= '<table class="info-table" cellpadding="0" cellspacing="0">';
                $body .= '<tr><td class="label">姓名</td><td class="value">' . htmlspecialchars($messageData['name'] ?? '') . '</td></tr>';
                $body .= '<tr><td class="label">邮箱</td><td class="value">' . htmlspecialchars($messageData['email'] ?? '') . '</td></tr>';
                
                if (!empty($messageData['phone'])) {
                    $body .= '<tr><td class="label">电话</td><td class="value">' . htmlspecialchars($messageData['phone']) . '</td></tr>';
                }
                
                if (!empty($messageData['subject'])) {
                    $body .= '<tr><td class="label">主题</td><td class="value">' . htmlspecialchars($messageData['subject']) . '</td></tr>';
                }
                
                $body .= '<tr><td class="label">内容</td><td class="value">' . nl2br(htmlspecialchars($messageData['message'] ?? '')) . '</td></tr>';
                $body .= '<tr><td class="label">时间</td><td class="value">' . date('Y-m-d H:i:s') . '</td></tr>';
                $body .= '</table>';
                
                $siteUrl = Settings::get('site_url', '');
                if (!empty($siteUrl)) {
                    $body .= '<p style="margin: 24px 0 0 0; text-align: center;"><a href="' . htmlspecialchars($siteUrl) . '/lty-admin/messages.php" class="btn">查看留言详情</a></p>';
                }
            }
            
            $body = $this->wrapEmailTemplate($body, '新留言通知');
            
            $result = $this->send($user['email'], $subject, $body, true, true);
            if ($result['success']) {
                $successCount++;
            } else {
                $failedEmails[] = $user['email'];
            }
        }
        
        if ($successCount === count($users)) {
            return ['success' => true, 'message' => "成功发送 {$successCount} 封通知邮件"];
        } elseif ($successCount > 0) {
            return ['success' => true, 'message' => "成功发送 {$successCount} 封邮件，" . count($failedEmails) . " 封发送失败"];
        } else {
            return ['success' => false, 'message' => '所有邮件发送失败'];
        }
    }
}

switch ($action) {
    case 'test':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['success' => false, 'message' => 'Method not allowed'], 405);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $to = $data['to'] ?? '';
        
        if (empty($to)) {
            json_response(['success' => false, 'message' => '请输入收件人邮箱'], 400);
        }
        
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            json_response(['success' => false, 'message' => '邮箱地址格式不正确'], 400);
        }
        
        $mailer = new Mailer();
        
        if (!$mailer->isEnabled()) {
            json_response(['success' => false, 'message' => '邮件服务未启用或配置不完整，请先在设置中配置SMTP信息'], 400);
        }
        
        $result = $mailer->sendTemplate($to, 'test');
        
        APISecurity::logSecurityEvent('email_test', [
            'user_id' => $_SESSION['admin_id'],
            'to' => $to,
            'result' => $result['success'] ? 'success' : 'failed'
        ]);
        
        json_response($result);
        break;
        
    case 'send':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['success' => false, 'message' => 'Method not allowed'], 405);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $to = $data['to'] ?? '';
        $subject = $data['subject'] ?? '';
        $body = $data['body'] ?? '';
        $isHtml = $data['isHtml'] ?? true;
        
        if (empty($to) || empty($subject) || empty($body)) {
            json_response(['success' => false, 'message' => '收件人、主题和内容不能为空'], 400);
        }
        
        $mailer = new Mailer();
        
        if (!$mailer->isEnabled()) {
            json_response(['success' => false, 'message' => '邮件服务未启用或配置不完整'], 400);
        }
        
        $result = $mailer->send($to, $subject, $body, $isHtml);
        
        APISecurity::logSecurityEvent('email_sent', [
            'user_id' => $_SESSION['admin_id'],
            'to' => $to,
            'subject' => $subject,
            'result' => $result['success'] ? 'success' : 'failed'
        ]);
        
        json_response($result);
        break;
        
    case 'send_template':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['success' => false, 'message' => 'Method not allowed'], 405);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $to = $data['to'] ?? '';
        $template = $data['template'] ?? '';
        $variables = $data['variables'] ?? [];
        
        if (empty($to) || empty($template)) {
            json_response(['success' => false, 'message' => '收件人和模板不能为空'], 400);
        }
        
        $mailer = new Mailer();
        
        if (!$mailer->isEnabled()) {
            json_response(['success' => false, 'message' => '邮件服务未启用或配置不完整'], 400);
        }
        
        $result = $mailer->sendTemplate($to, $template, $variables);
        
        APISecurity::logSecurityEvent('email_template_sent', [
            'user_id' => $_SESSION['admin_id'],
            'to' => $to,
            'template' => $template,
            'result' => $result['success'] ? 'success' : 'failed'
        ]);
        
        json_response($result);
        break;
        
    case 'status':
        $mailer = new Mailer();
        json_response([
            'success' => true,
            'enabled' => $mailer->isEnabled(),
            'configured' => !empty(Settings::get('email_smtp_host')) && !empty(Settings::get('email_smtp_username'))
        ]);
        break;
        
    case 'notify_roles':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['success' => false, 'message' => 'Method not allowed'], 405);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $mailer = new Mailer();
        
        if (!$mailer->isEnabled()) {
            json_response(['success' => false, 'message' => '邮件服务未启用或配置不完整'], 400);
        }
        
        $result = $mailer->sendContactNotificationToRoles($data);
        
        APISecurity::logSecurityEvent('email_notify_roles', [
            'user_id' => $_SESSION['admin_id'] ?? null,
            'result' => $result['success'] ? 'success' : 'failed'
        ]);
        
        json_response($result);
        break;
        
    default:
        if (!defined('INCLUDE_MAILER_ONLY')) {
            json_response(['success' => false, 'message' => 'Invalid action'], 400);
        }
}

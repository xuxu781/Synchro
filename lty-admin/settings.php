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

Auth::requirePermission('settings_manage');

$db = Database::getInstance();

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$currentDomain = $_SERVER['HTTP_HOST'] ?? '';
$autoSiteUrl = $protocol . '://' . $currentDomain;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentTab = trim($_POST['current_tab'] ?? 'basic');
    $settings = [
        'site_name' => trim($_POST['site_name'] ?? ''),
        'site_url' => trim($_POST['site_url'] ?? $autoSiteUrl),
        'logo_type' => trim($_POST['logo_type'] ?? 'image'),
        'logo_image' => trim($_POST['logo_image'] ?? ''),
        'logo_text' => trim($_POST['logo_text'] ?? ''),
        'company_name' => trim($_POST['company_name'] ?? ''),
        'copyright' => trim($_POST['copyright'] ?? ''),
        'session_timeout' => intval($_POST['session_timeout'] ?? 30),
        'enable_ip_binding' => trim($_POST['enable_ip_binding'] ?? '1'),
        'api_rate_limit' => intval($_POST['api_rate_limit'] ?? 100),
        'csrf_token_expiry' => intval($_POST['csrf_token_expiry'] ?? 120),
        'require_password_on_delete' => trim($_POST['require_password_on_delete'] ?? '1'),
        'require_password_on_settings' => trim($_POST['require_password_on_settings'] ?? '1'),
        'force_https' => trim($_POST['force_https'] ?? '0'),
        'enable_security_log' => trim($_POST['enable_security_log'] ?? '1'),
        'security_alert_email' => trim($_POST['security_alert_email'] ?? '1'),
        'email_enabled' => trim($_POST['email_enabled'] ?? '0'),
        'email_smtp_host' => trim($_POST['email_smtp_host'] ?? ''),
        'email_smtp_port' => intval($_POST['email_smtp_port'] ?? 465),
        'email_smtp_username' => trim($_POST['email_smtp_username'] ?? ''),
        'email_smtp_password' => trim($_POST['email_smtp_password'] ?? ''),
        'email_smtp_encryption' => trim($_POST['email_smtp_encryption'] ?? 'ssl'),
        'email_from_name' => trim($_POST['email_from_name'] ?? ''),
        'email_subject_prefix' => trim($_POST['email_subject_prefix'] ?? ''),
        'email_footer_content1' => trim($_POST['email_footer_content1'] ?? ''),
        'email_footer_content2' => trim($_POST['email_footer_content2'] ?? ''),
        'custom_css' => trim($_POST['custom_css'] ?? ''),
        'custom_js' => trim($_POST['custom_js'] ?? ''),
        'custom_head_html' => trim($_POST['custom_head_html'] ?? ''),
        'custom_footer_html' => trim($_POST['custom_footer_html'] ?? ''),
        'analytics_html' => trim($_POST['analytics_html'] ?? '')
    ];
    
    if (isset($_FILES['logo_image_file']) && $_FILES['logo_image_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['logo_image_file'];
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 2 * 1024 * 1024;
        
        if ($file['size'] > $maxSize) {
            set_flash_message('error_' . $currentTab, '文件大小不能超过2MB');
            redirect('lty-admin/settings.php?tab=' . $currentTab);
        }
        
        if (!in_array($file['type'], $allowedTypes)) {
            set_flash_message('error_' . $currentTab, '不支持的文件类型，请上传 JPG、PNG、GIF 或 WebP 格式的图片');
            redirect('lty-admin/settings.php?tab=' . $currentTab);
        }
        
        $uploadDir = __DIR__ . '/../uploads/settings/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'logo_' . time() . '_' . uniqid() . '.' . $extension;
        $uploadPath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            $settings['logo_image'] = '/uploads/settings/' . $filename;
        }
    }
    
    if (empty($settings['site_name'])) {
        set_flash_message('error_' . $currentTab, '请填写网站名称');
        redirect('lty-admin/settings.php?tab=' . $currentTab);
    } else {
        $oldSiteUrl = Settings::get('site_url', '');
        $newSiteUrl = $settings['site_url'];
        
        foreach ($settings as $key => $value) {
            Settings::set($key, $value);
        }
        
        if ($oldSiteUrl !== $newSiteUrl && !empty($newSiteUrl)) {
            $updatedCount = 0;
            
            $mediaRecords = $db->fetchAll("SELECT id, file_url FROM `lty-media_library` WHERE status = 1");
            foreach ($mediaRecords as $record) {
                $oldUrl = $record['file_url'];
                if (!empty($oldUrl)) {
                    $newUrl = '';
                    if (!empty($oldSiteUrl) && strpos($oldUrl, $oldSiteUrl) !== false) {
                        $newUrl = str_replace($oldSiteUrl, $newSiteUrl, $oldUrl);
                    } elseif (strpos($oldUrl, '../../uploads/') === 0) {
                        $newUrl = rtrim($newSiteUrl, '/') . str_replace('../../uploads/', '/uploads/', $oldUrl);
                    } elseif (strpos($oldUrl, '/uploads/') === 0) {
                        $newUrl = rtrim($newSiteUrl, '/') . $oldUrl;
                    }
                    
                    if (!empty($newUrl) && $newUrl !== $oldUrl) {
                        $db->update('`lty-media_library`', ['file_url' => $newUrl], 'id = ?', [$record['id']]);
                        $updatedCount++;
                    }
                }
            }
            
            if (!empty($oldSiteUrl)) {
                $tables = ['`lty-company_culture`', '`lty-mission_slides`', '`lty-team_members`'];
                foreach ($tables as $table) {
                    $column = ($table === '`lty-team_members`') ? 'avatar' : 'image';
                    $records = $db->fetchAll("SELECT id, $column FROM $table");
                    
                    foreach ($records as $record) {
                        $oldImage = $record[$column];
                        if (!empty($oldImage) && strpos($oldImage, $oldSiteUrl) !== false) {
                            $newImage = str_replace($oldSiteUrl, $newSiteUrl, $oldImage);
                            $db->update($table, [$column => $newImage], 'id = ?', [$record['id']]);
                            $updatedCount++;
                        }
                    }
                }
                
                $productRecords = $db->fetchAll("SELECT id, image, images FROM `lty-products`");
                foreach ($productRecords as $record) {
                    $updateData = [];
                    
                    if (!empty($record['image']) && strpos($record['image'], $oldSiteUrl) !== false) {
                        $updateData['image'] = str_replace($oldSiteUrl, $newSiteUrl, $record['image']);
                    }
                    
                    if (!empty($record['images'])) {
                        $images = json_decode($record['images'], true);
                        if (is_array($images)) {
                            $updated = false;
                            foreach ($images as &$img) {
                                if (strpos($img, $oldSiteUrl) !== false) {
                                    $img = str_replace($oldSiteUrl, $newSiteUrl, $img);
                                    $updated = true;
                                }
                            }
                            if ($updated) {
                                $updateData['images'] = json_encode($images);
                            }
                        }
                    }
                    
                    if (!empty($updateData)) {
                        $db->update('`lty-products`', $updateData, 'id = ?', [$record['id']]);
                        $updatedCount++;
                    }
                }
                
                $oldLogoImage = Settings::get('logo_image', '');
                if (!empty($oldLogoImage) && strpos($oldLogoImage, $oldSiteUrl) !== false) {
                    $newLogoImage = str_replace($oldSiteUrl, $newSiteUrl, $oldLogoImage);
                    Settings::set('logo_image', $newLogoImage);
                    $updatedCount++;
                }
            }
            
            if ($updatedCount > 0) {
                set_flash_message('success_' . $currentTab, '设置保存成功，已自动更新 ' . $updatedCount . ' 个链接的域名');
            } else {
                set_flash_message('success_' . $currentTab, '设置保存成功');
            }
        } else {
            set_flash_message('success_' . $currentTab, '设置保存成功');
        }
        
        OperationLog::update('settings', '更新网站设置 - ' . ($currentTab === 'basic' ? '基本设置' : ($currentTab === 'custom' ? '自定义代码' : ($currentTab === 'email' ? '邮件设置' : ($currentTab === 'footer' ? '底部页脚' : '安全设置')))), 'settings', null, ['tab' => $currentTab]);
        
        redirect('lty-admin/settings.php?tab=' . $currentTab);
    }
}

$activeTab = $_GET['tab'] ?? 'basic';
$successBasic = get_flash_message('success_basic');
$errorBasic = get_flash_message('error_basic');
$successEmail = get_flash_message('success_email');
$errorEmail = get_flash_message('error_email');
$successSecurity = get_flash_message('success_security');
$errorSecurity = get_flash_message('error_security');

$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>网站设置 - <?php echo htmlspecialchars(Settings::get('site_name', '官网系统')); ?></title>
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        .settings-footer-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding: 24px;
            background: var(--bg-card);
            border-radius: 20px;
            border: 1px solid var(--border-color);
            margin-bottom: 20px;
        }
        .settings-footer-actions .btn {
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .settings-footer-actions .btn-primary {
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
            color: white;
            border: none;
        }
        .settings-footer-actions .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(245, 158, 11, 0.4);
        }
        .settings-footer-actions .btn-outline {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }
        .settings-footer-actions .btn-outline:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>网站设置</h1>
        </div>

        <div class="content-card">
            <div class="card-body">
                <div class="tabs-container">
                    <div class="tabs-nav">
                        <button class="tab-btn <?php echo $activeTab === 'basic' ? 'active' : ''; ?>" data-tab="basic">网站信息</button>
                        <button class="tab-btn <?php echo $activeTab === 'footer' ? 'active' : ''; ?>" data-tab="footer">底部页脚</button>
                        <button class="tab-btn <?php echo $activeTab === 'custom' ? 'active' : ''; ?>" data-tab="custom">自定义代码</button>
                        <button class="tab-btn <?php echo $activeTab === 'email' ? 'active' : ''; ?>" data-tab="email">邮件设置</button>
                        <button class="tab-btn <?php echo $activeTab === 'security' ? 'active' : ''; ?>" data-tab="security">安全设置</button>
                    </div>
                    
                    <form method="POST" id="settings-form">
                        <input type="hidden" name="current_tab" id="current_tab" value="<?php echo htmlspecialchars($activeTab); ?>">
                        <div class="tab-content <?php echo $activeTab === 'basic' ? 'active' : ''; ?>" id="tab-basic">
                            <?php if ($errorBasic): ?>
                                <div class="alert alert-error"><?php echo htmlspecialchars($errorBasic); ?></div>
                            <?php endif; ?>
                            <?php if ($successBasic): ?>
                                <div class="alert alert-success"><?php echo htmlspecialchars($successBasic); ?></div>
                            <?php endif; ?>
                            <div class="form-section">
                                <h3>网站信息</h3>
                                <div class="form-group">
                                    <label for="site_name">网站名称 *</label>
                                    <input type="text" id="site_name" name="site_name" value="<?php echo htmlspecialchars(Settings::get('site_name', '团队官网')); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="site_url">网站域名 *</label>
                                    <input type="text" id="site_url" name="site_url" value="<?php echo htmlspecialchars(Settings::get('site_url', $autoSiteUrl)); ?>" placeholder="例如：https://lintuyue.com">
                                    <small style="color: #666; font-size: 12px;">当前自动检测域名：<strong><?php echo htmlspecialchars($autoSiteUrl); ?></strong>，用于图片URL的域名，更换域名后请及时更新此项</small>
                                </div>
                            </div>
                            <div class="form-section">
                                <h3>Logo设置</h3>
                                <div class="form-group">
                                    <label for="logo_type">Logo类型</label>
                                    <select id="logo_type" name="logo_type" onchange="toggleLogoFields()">
                                        <option value="image" <?php echo Settings::get('logo_type', 'image') === 'image' ? 'selected' : ''; ?>>图片Logo</option>
                                        <option value="text" <?php echo Settings::get('logo_type', 'image') === 'text' ? 'selected' : ''; ?>>自定义文字</option>
                                    </select>
                                </div>
                                <div class="form-group" id="logo_image_field" style="display: <?php echo in_array(Settings::get('logo_type', 'image'), ['image', 'default']) ? 'block' : 'none'; ?>">
                                    <label for="logo_image">Logo图片</label>
                                    <div style="display: flex; gap: 10px;">
                                        <input type="text" id="logo_image" name="logo_image" value="<?php echo htmlspecialchars(Settings::get('logo_image', '')); ?>" placeholder="点击选择图片或手动输入图片URL" oninput="previewLogoUrlImage(this)" style="flex: 1;">
                                        <button type="button" class="btn btn-outline" onclick="openMediaLibrary('logo_image')">选择图片</button>
                                    </div>
                                    <div id="logo_preview" style="margin-top: 10px;">
                                        <?php if (Settings::get('logo_image', '')): ?>
                                            <img src="<?php echo htmlspecialchars(Settings::get('logo_image', '')); ?>" alt="Logo预览" style="max-width: 200px; max-height: 80px;">
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="form-group" id="logo_text_field" style="display: <?php echo Settings::get('logo_type', 'image') === 'text' ? 'block' : 'none'; ?>;">
                                    <label for="logo_text">Logo文字</label>
                                    <input type="text" id="logo_text" name="logo_text" value="<?php echo htmlspecialchars(Settings::get('logo_text', '')); ?>" placeholder="输入自定义Logo文字">
                                </div>
                            </div>
                            <div class="form-section">
                                <h3>公司信息</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="company_name">公司名称</label>
                                        <input type="text" id="company_name" name="company_name" value="<?php echo htmlspecialchars(Settings::get('company_name', 'XX科技有限公司')); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="copyright">版权信息</label>
                                        <input type="text" id="copyright" name="copyright" value="<?php echo htmlspecialchars(Settings::get('copyright', '2024 Synchro 版权所有')); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="tab-content <?php echo $activeTab === 'email' ? 'active' : ''; ?>" id="tab-email">
                            <?php if ($errorEmail): ?>
                                <div class="alert alert-error"><?php echo htmlspecialchars($errorEmail); ?></div>
                            <?php endif; ?>
                            <?php if ($successEmail): ?>
                                <div class="alert alert-success"><?php echo htmlspecialchars($successEmail); ?></div>
                            <?php endif; ?>
                            <div class="form-section">
                                <h3>邮件服务配置</h3>
                                <div class="settings-tip" style="background: #e3f2fd; border: 1px solid #bbdefb; border-radius: 8px; padding: 12px 16px; margin-bottom: 15px; color: #1565c0; font-size: 13px;">
                                    <strong>💡 提示：</strong>配置SMTP邮件服务后，网站可以发送通知邮件、留言提醒等。推荐使用企业邮箱或专业邮件服务。
                                </div>
                                <div class="form-group">
                                    <label for="email_enabled">启用邮件服务</label>
                                    <select id="email_enabled" name="email_enabled">
                                        <option value="1" <?php echo Settings::get('email_enabled', '0') === '1' ? 'selected' : ''; ?>>启用</option>
                                        <option value="0" <?php echo Settings::get('email_enabled', '0') === '0' ? 'selected' : ''; ?>>禁用</option>
                                    </select>
                                    <small style="color: #666; font-size: 12px;">启用后网站将可以发送邮件通知</small>
                                </div>
                            </div>
                            <div class="form-section">
                                <h3>SMTP服务器设置</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="email_smtp_host">SMTP服务器地址</label>
                                        <input type="text" id="email_smtp_host" name="email_smtp_host" value="<?php echo htmlspecialchars(Settings::get('email_smtp_host', '')); ?>" placeholder="例如：smtp.qq.com">
                                    </div>
                                    <div class="form-group">
                                        <label for="email_smtp_port">SMTP端口</label>
                                        <input type="number" id="email_smtp_port" name="email_smtp_port" value="<?php echo htmlspecialchars(Settings::get('email_smtp_port', '465')); ?>" placeholder="465">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="email_smtp_username">SMTP用户名</label>
                                        <input type="text" id="email_smtp_username" name="email_smtp_username" value="<?php echo htmlspecialchars(Settings::get('email_smtp_username', '')); ?>" placeholder="通常是邮箱地址">
                                    </div>
                                    <div class="form-group">
                                        <label for="email_smtp_password">SMTP密码/授权码</label>
                                        <input type="password" id="email_smtp_password" name="email_smtp_password" value="<?php echo htmlspecialchars(Settings::get('email_smtp_password', '')); ?>" placeholder="邮箱密码或授权码">
                                        <small style="color: #666; font-size: 12px;">QQ邮箱等需要使用授权码</small>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="email_smtp_encryption">加密方式</label>
                                    <select id="email_smtp_encryption" name="email_smtp_encryption">
                                        <option value="ssl" <?php echo Settings::get('email_smtp_encryption', 'ssl') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                        <option value="tls" <?php echo Settings::get('email_smtp_encryption', 'ssl') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                        <option value="none" <?php echo Settings::get('email_smtp_encryption', 'ssl') === 'none' ? 'selected' : ''; ?>>无加密</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-section">
                                <h3>自定义设置</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="email_from_name">自定义发件人</label>
                                        <input type="text" id="email_from_name" name="email_from_name" value="<?php echo htmlspecialchars(Settings::get('email_from_name', '')); ?>" placeholder="发件人显示名称">
                                        <small style="color: #666; font-size: 12px;">留空则使用网站名称</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="email_subject_prefix">自定义标题前缀</label>
                                        <input type="text" id="email_subject_prefix" name="email_subject_prefix" value="<?php echo htmlspecialchars(Settings::get('email_subject_prefix', '')); ?>" placeholder="例如：【官网】">
                                        <small style="color: #666; font-size: 12px;">将自动添加到邮件标题前</small>
                                    </div>
                                </div>
                            </div>
                            <div class="form-section">
                                <h3>邮件底部额外内容</h3>
                                <div class="form-group">
                                    <label for="email_footer_content1">额外内容一</label>
                                    <textarea id="email_footer_content1" name="email_footer_content1" rows="3" placeholder="支持HTML代码"><?php echo htmlspecialchars(Settings::get('email_footer_content1', '')); ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="email_footer_content2">额外内容二</label>
                                    <textarea id="email_footer_content2" name="email_footer_content2" rows="3" placeholder="支持HTML代码"><?php echo htmlspecialchars(Settings::get('email_footer_content2', '')); ?></textarea>
                                    <small style="color: #666; font-size: 12px;">以上内容将按顺序添加到邮件底部</small>
                                </div>
                            </div>
                            <div class="form-section">
                                <h3>邮件测试</h3>
                                <div class="form-group">
                                    <label for="test_email_address">测试邮箱地址</label>
                                    <div style="display: flex; gap: 10px;">
                                        <input type="email" id="test_email_address" placeholder="输入接收测试邮件的邮箱" style="flex: 1;">
                                        <button type="button" class="btn btn-outline" onclick="sendTestEmail()">发送测试邮件</button>
                                    </div>
                                    <div id="email-test-result" style="margin-top: 10px; display: none;"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="tab-content <?php echo $activeTab === 'security' ? 'active' : ''; ?>" id="tab-security">
                            <?php if ($errorSecurity): ?>
                                <div class="alert alert-error"><?php echo htmlspecialchars($errorSecurity); ?></div>
                            <?php endif; ?>
                            <?php if ($successSecurity): ?>
                                <div class="alert alert-success"><?php echo htmlspecialchars($successSecurity); ?></div>
                            <?php endif; ?>
                            <div class="form-section">
                                <h3>Session安全</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="session_timeout">Session超时时间（分钟）</label>
                                        <input type="number" id="session_timeout" name="session_timeout" min="5" max="1440" value="<?php echo htmlspecialchars(Settings::get('session_timeout', '30')); ?>">
                                        <small style="color: #666; font-size: 12px;">用户无操作自动登出的时间，建议30-60分钟</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="enable_ip_binding">启用IP绑定</label>
                                        <select id="enable_ip_binding" name="enable_ip_binding">
                                            <option value="1" <?php echo Settings::get('enable_ip_binding', '1') === '1' ? 'selected' : ''; ?>>启用</option>
                                            <option value="0" <?php echo Settings::get('enable_ip_binding', '1') === '0' ? 'selected' : ''; ?>>禁用</option>
                                        </select>
                                        <small style="color: #666; font-size: 12px;">启用后Session与IP绑定，IP变化会自动登出</small>
                                    </div>
                                </div>
                            </div>
                            <div class="form-section">
                                <h3>API安全</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="api_rate_limit">API请求频率限制（次/小时）</label>
                                        <input type="number" id="api_rate_limit" name="api_rate_limit" min="10" max="1000" value="<?php echo htmlspecialchars(Settings::get('api_rate_limit', '100')); ?>">
                                        <small style="color: #666; font-size: 12px;">每个用户每小时最多请求次数</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="csrf_token_expiry">CSRF Token过期时间（分钟）</label>
                                        <input type="number" id="csrf_token_expiry" name="csrf_token_expiry" min="5" max="1440" value="<?php echo htmlspecialchars(Settings::get('csrf_token_expiry', '120')); ?>">
                                        <small style="color: #666; font-size: 12px;">CSRF Token的有效期，建议60-120分钟</small>
                                    </div>
                                </div>
                            </div>
                            <div class="form-section">
                                <h3>敏感操作验证</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="require_password_on_delete">删除成员需要密码验证</label>
                                        <select id="require_password_on_delete" name="require_password_on_delete">
                                            <option value="1" <?php echo Settings::get('require_password_on_delete', '1') === '1' ? 'selected' : ''; ?>>启用</option>
                                            <option value="0" <?php echo Settings::get('require_password_on_delete', '1') === '0' ? 'selected' : ''; ?>>禁用</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="require_password_on_settings">修改设置需要密码验证</label>
                                        <select id="require_password_on_settings" name="require_password_on_settings">
                                            <option value="1" <?php echo Settings::get('require_password_on_settings', '1') === '1' ? 'selected' : ''; ?>>启用</option>
                                            <option value="0" <?php echo Settings::get('require_password_on_settings', '1') === '0' ? 'selected' : ''; ?>>禁用</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-section">
                                <h3>HTTPS设置</h3>
                                <div class="form-group">
                                    <label for="force_https">强制HTTPS</label>
                                    <select id="force_https" name="force_https">
                                        <option value="0" <?php echo Settings::get('force_https', '0') === '0' ? 'selected' : ''; ?>>禁用（仅HTTP）</option>
                                        <option value="1" <?php echo Settings::get('force_https', '0') === '1' ? 'selected' : ''; ?>>启用（强制HTTPS）</option>
                                    </select>
                                    <small style="color: #666; font-size: 12px;">启用后所有HTTP请求将重定向到HTTPS，需要配置SSL证书</small>
                                </div>
                            </div>
                            <div class="form-section">
                                <h3>安全日志</h3>
                                <div class="form-group">
                                    <label for="enable_security_log">启用安全事件日志</label>
                                    <select id="enable_security_log" name="enable_security_log">
                                        <option value="1" <?php echo Settings::get('enable_security_log', '1') === '1' ? 'selected' : ''; ?>>启用</option>
                                        <option value="0" <?php echo Settings::get('enable_security_log', '1') === '0' ? 'selected' : ''; ?>>禁用</option>
                                    </select>
                                    <small style="color: #666; font-size: 12px;">记录未授权访问、密码验证失败等安全事件</small>
                                </div>
                                <div class="form-group">
                                    <label for="security_alert_email">安全警报邮件通知</label>
                                    <select id="security_alert_email" name="security_alert_email">
                                        <option value="1" <?php echo Settings::get('security_alert_email', '1') === '1' ? 'selected' : ''; ?>>启用</option>
                                        <option value="0" <?php echo Settings::get('security_alert_email', '1') === '0' ? 'selected' : ''; ?>>禁用</option>
                                    </select>
                                    <small style="color: #666; font-size: 12px;">当发生严重或高危安全事件时，自动发送邮件通知给启用了邮件通知的角色成员</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="tab-content <?php echo $activeTab === 'footer' ? 'active' : ''; ?>" id="tab-footer">
                            <div class="form-section">
                                <h3>快速链接管理</h3>
                                <div class="settings-tip" style="background: #e8f5e9; border: 1px solid #c8e6c9; border-radius: 8px; padding: 12px 16px; margin-bottom: 15px; color: #2e7d32; font-size: 13px;">
                                    <strong>💡 提示：</strong>底部页脚链接设置成功后，将立即在前台页面底部显示。可拖拽调整链接顺序。
                                </div>
                                
                                <div id="footer-links-alert" style="display: none; margin-bottom: 15px; padding: 12px 15px; border-radius: 6px;"></div>
                                
                                <div class="footer-links-toolbar" style="margin-bottom: 15px;">
                                    <button type="button" class="btn btn-primary" onclick="openAddLinkModal()">添加链接</button>
                                </div>
                                
                                <div id="footer-links-list" class="sortable-list">
                                </div>
                            </div>
                        </div>
                        
                        <div class="tab-content <?php echo $activeTab === 'custom' ? 'active' : ''; ?>" id="tab-custom">
                            <?php $successCustom = get_flash_message('success_custom'); ?>
                            <?php $errorCustom = get_flash_message('error_custom'); ?>
                            <?php if ($errorCustom): ?>
                                <div class="alert alert-error"><?php echo htmlspecialchars($errorCustom); ?></div>
                            <?php endif; ?>
                            <?php if ($successCustom): ?>
                                <div class="alert alert-success"><?php echo htmlspecialchars($successCustom); ?></div>
                            <?php endif; ?>
                            
                            <div class="form-section">
                                <h3>自定义CSS样式</h3>
                                <div class="settings-tip" style="background: #e3f2fd; border: 1px solid #bbdefb; border-radius: 8px; padding: 12px 16px; margin-bottom: 15px; color: #1565c0; font-size: 13px;">
                                    <strong>💡 提示：</strong>位于 <code>&lt;/head&gt;</code> 之前，直接写样式代码，<strong>不需要</strong>添加 <code>&lt;style&gt;</code> 标签，系统会自动包裹。
                                </div>
                                <div class="form-group">
                                    <label for="custom_css">CSS代码</label>
                                    <textarea id="custom_css" name="custom_css" rows="8" style="font-family: 'Consolas', 'Monaco', monospace; font-size: 13px; line-height: 1.5;" placeholder="/* 例如 */
.my-custom-class {
    color: #f59e0b;
    font-size: 16px;
}"><?php echo htmlspecialchars(Settings::get('custom_css', '')); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3>自定义JavaScript代码</h3>
                                <div class="settings-tip" style="background: #e3f2fd; border: 1px solid #bbdefb; border-radius: 8px; padding: 12px 16px; margin-bottom: 15px; color: #1565c0; font-size: 13px;">
                                    <strong>💡 提示：</strong>位于页面底部，直接填写JS代码，<strong>不需要</strong>添加 <code>&lt;script&gt;</code> 标签，系统会自动包裹。
                                </div>
                                <div class="form-group">
                                    <label for="custom_js">JavaScript代码</label>
                                    <textarea id="custom_js" name="custom_js" rows="8" style="font-family: 'Consolas', 'Monaco', monospace; font-size: 13px; line-height: 1.5;" placeholder="// 例如
document.addEventListener('DOMContentLoaded', function() {
    console.log('自定义JS已加载');
});"><?php echo htmlspecialchars(Settings::get('custom_js', '')); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3>自定义头部HTML代码</h3>
                                <div class="settings-tip" style="background: #fff3e0; border: 1px solid #ffe0b2; border-radius: 8px; padding: 12px 16px; margin-bottom: 15px; color: #e65100; font-size: 13px;">
                                    <strong>💡 提示：</strong>位于 <code>&lt;/head&gt;</code> 之前，这部分代码在主要内容显示之前加载，通常是CSS样式、自定义的 <code>&lt;meta&gt;</code> 标签、全站头部JS等需要提前加载的代码，<strong>需要</strong>填写完整的HTML标签。
                                </div>
                                <div class="form-group">
                                    <label for="custom_head_html">头部HTML代码</label>
                                    <textarea id="custom_head_html" name="custom_head_html" rows="5" style="font-family: 'Consolas', 'Monaco', monospace; font-size: 13px; line-height: 1.5;" placeholder="&lt;!-- 例如 --&gt;
&lt;meta name=&quot;baidu_union_verify&quot; content=&quot;your_verify_code&quot;&gt;
&lt;link rel=&quot;stylesheet&quot; href=&quot;your-custom-style.css&quot;&gt;"><?php echo htmlspecialchars(Settings::get('custom_head_html', '')); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3>自定义底部HTML代码</h3>
                                <div class="settings-tip" style="background: #fff3e0; border: 1px solid #ffe0b2; border-radius: 8px; padding: 12px 16px; margin-bottom: 15px; color: #e65100; font-size: 13px;">
                                    <strong>💡 提示：</strong>位于 <code>&lt;/body&gt;</code> 之前，这部分代码在主要内容加载完毕后加载，通常是JS代码，<strong>需要</strong>填写完整的HTML标签。
                                </div>
                                <div class="form-group">
                                    <label for="custom_footer_html">底部HTML代码</label>
                                    <textarea id="custom_footer_html" name="custom_footer_html" rows="5" style="font-family: 'Consolas', 'Monaco', monospace; font-size: 13px; line-height: 1.5;" placeholder="<!-- 例如 -->
&lt;script src=&quot;your-custom-script.js&quot;&gt;&lt;/script&gt;
&lt;div id=&quot;my-custom-element&quot;&gt;&lt;/div&gt;"><?php echo htmlspecialchars(Settings::get('custom_footer_html', '')); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3>网站统计HTML代码</h3>
                                <div class="settings-tip" style="background: #e8f5e9; border: 1px solid #c8e6c9; border-radius: 8px; padding: 12px 16px; margin-bottom: 15px; color: #2e7d32; font-size: 13px;">
                                    <strong>💡 提示：</strong>位于页面底部，用于添加第三方流量数据统计代码，如：Google Analytics、百度统计、CNZZ、51la。<br>
                                    <strong>国内站点推荐使用百度统计，国外站点推荐使用Google Analytics。</strong><br>
                                    <strong>需要</strong>填写完整的HTML标签，如果是javascript代码，请保存在"自定义JavaScript代码"中。
                                </div>
                                <div class="form-group">
                                    <label for="analytics_html">统计代码</label>
                                    <textarea id="analytics_html" name="analytics_html" rows="5" style="font-family: 'Consolas', 'Monaco', monospace; font-size: 13px; line-height: 1.5;" placeholder="<!-- 百度统计代码示例 -->
&lt;script&gt;
var _hmt = _hmt || [];
(function() {
  var hm = document.createElement(&quot;script&quot;);
  hm.src = &quot;https://hm.baidu.com/hm.js?your_tracking_id&quot;;
  var s = document.getElementsByTagName(&quot;script&quot;)[0]; 
  s.parentNode.insertBefore(hm, s);
})();
&lt;/script&gt;"><?php echo htmlspecialchars(Settings::get('analytics_html', '')); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="settings-footer-actions">
            <button type="button" class="btn btn-outline" onclick="if(confirm('确定要重置所有设置吗？')) document.querySelector('form').reset()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                    <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
                    <path d="M3 3v5h5"></path>
                </svg>
                重置
            </button>
            <button type="submit" class="btn btn-primary" form="settings-form">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                保存设置
            </button>
        </div>

        <div class="main-footer">
            <div class="footer-content">
                <span class="footer-line">© 2026 <?php echo htmlspecialchars(Settings::get('site_name', '官网系统')); ?> 1.0</span>
                <span class="footer-line">徐州邻兔跃IT工作室提供技术支持版权所有</span>
            </div>
        </div>
    </div>
</body>
<script>
function validateForm() {
    const currentTab = document.getElementById('current_tab').value;
    let errors = [];
    
    clearValidationErrors();
    
    if (currentTab === 'basic') {
        const siteName = document.getElementById('site_name');
        const siteUrl = document.getElementById('site_url');
        
        if (!siteName.value.trim()) {
            errors.push({field: 'site_name', message: '网站名称'});
            markFieldError(siteName);
        }
        
        if (!siteUrl.value.trim()) {
            errors.push({field: 'site_url', message: '网站域名'});
            markFieldError(siteUrl);
        }
    } else if (currentTab === 'email') {
        const emailEnabled = document.getElementById('email_enabled').value;
        
        if (emailEnabled === '1') {
            const smtpHost = document.getElementById('email_smtp_host');
            const smtpPort = document.getElementById('email_smtp_port');
            const smtpUsername = document.getElementById('email_smtp_username');
            const smtpPassword = document.getElementById('email_smtp_password');
            
            if (!smtpHost.value.trim()) {
                errors.push({field: 'email_smtp_host', message: 'SMTP服务器地址'});
                markFieldError(smtpHost);
            }
            
            if (!smtpPort.value.trim()) {
                errors.push({field: 'email_smtp_port', message: 'SMTP端口'});
                markFieldError(smtpPort);
            }
            
            if (!smtpUsername.value.trim()) {
                errors.push({field: 'email_smtp_username', message: 'SMTP用户名'});
                markFieldError(smtpUsername);
            }
            
            if (!smtpPassword.value.trim()) {
                errors.push({field: 'email_smtp_password', message: 'SMTP密码/授权码'});
                markFieldError(smtpPassword);
            }
        }
    }
    
    if (errors.length > 0) {
        const fieldNames = errors.map(e => e.message).join('、');
        showValidationError('请填写必填字段：' + fieldNames);
        
        if (errors[0].field) {
            const firstErrorField = document.getElementById(errors[0].field);
            if (firstErrorField) {
                firstErrorField.focus();
                firstErrorField.scrollIntoView({behavior: 'smooth', block: 'center'});
            }
        }
        
        return false;
    }
    
    return true;
}

function markFieldError(field) {
    field.style.borderColor = '#e53935';
    field.style.boxShadow = '0 0 0 2px rgba(229, 57, 53, 0.2)';
    
    field.addEventListener('input', function() {
        field.style.borderColor = '';
        field.style.boxShadow = '';
    }, {once: true});
}

function clearValidationErrors() {
    const errorAlert = document.getElementById('validation-error-alert');
    if (errorAlert) {
        errorAlert.remove();
    }
    
    const form = document.querySelector('form');
    const inputs = form.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
        input.style.borderColor = '';
        input.style.boxShadow = '';
    });
}

function showValidationError(message) {
    clearValidationErrors();
    
    const currentTab = document.getElementById('current_tab').value;
    const tabContent = document.getElementById('tab-' + currentTab);
    
    const alertDiv = document.createElement('div');
    alertDiv.id = 'validation-error-alert';
    alertDiv.className = 'alert alert-error';
    alertDiv.innerHTML = '<svg style="width:20px;height:20px;flex-shrink:0;" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg><span>' + message + '</span>';
    
    const existingAlert = tabContent.querySelector('.alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    tabContent.insertBefore(alertDiv, tabContent.firstChild);
    
    setTimeout(() => {
        alertDiv.style.opacity = '0';
        alertDiv.style.transition = 'opacity 0.5s ease';
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 500);
    }, 3000);
}

function openMediaLibrary(inputId) {
    MediaPicker.open({
        multiple: false,
        onSelect: function(url) {
            const input = document.getElementById(inputId);
            if (input) {
                input.value = url;
                if (inputId === 'logo_image') {
                    previewLogoUrlImage(input);
                } else if (inputId === 'seo_default_image') {
                    previewSeoImage(input);
                }
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const urlInput = document.getElementById('logo_image');
            
            if (!validateForm()) {
                e.preventDefault();
                return false;
            }
        });
    }
    
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            this.classList.add('active');
            document.getElementById('tab-' + tabId).classList.add('active');
            
            const currentTabInput = document.getElementById('current_tab');
            if (currentTabInput) {
                currentTabInput.value = tabId;
            }
            
            const newUrl = new URL(window.location.href);
            newUrl.searchParams.set('tab', tabId);
            window.history.replaceState({}, '', newUrl);
            
            const saveBtn = document.querySelector('.settings-footer-actions .btn-primary');
            const resetBtn = document.querySelector('.settings-footer-actions .btn-outline');
            if (tabId === 'footer') {
                if (saveBtn) saveBtn.style.display = 'none';
                if (resetBtn) resetBtn.style.display = 'none';
            } else {
                if (saveBtn) saveBtn.style.display = '';
                if (resetBtn) resetBtn.style.display = '';
            }
        });
    });
    
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease';
            setTimeout(function() {
                alert.remove();
            }, 500);
        }, 3000);
    });
});

function toggleLogoFields() {
    const logoType = document.getElementById('logo_type').value;
    const logoImageField = document.getElementById('logo_image_field');
    const logoTextField = document.getElementById('logo_text_field');
    
    logoImageField.style.display = (logoType === 'image' || logoType === 'default') ? 'block' : 'none';
    logoTextField.style.display = logoType === 'text' ? 'block' : 'none';
}

let pendingLogoFile = null;

function handleLogoUpload(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        const maxSize = 2 * 1024 * 1024;
        if (file.size > maxSize) {
            alert('文件大小不能超过2MB');
            input.value = '';
            return;
        }
        
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            alert('不支持的文件类型，请上传 JPG、PNG、GIF 或 WebP 格式的图片');
            input.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('logo_preview');
            const urlInput = document.getElementById('logo_image');
            preview.innerHTML = '<img src="' + e.target.result + '" alt="Logo预览" style="max-width: 200px; max-height: 80px;">';
            
            const formData = new FormData();
            formData.append('logo_image', file);
            
            fetch('/api/settings.php?action=upload_logo', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.url) {
                    urlInput.value = data.url;
                    pendingLogoFile = null;
                    input.dataset.fileSelected = 'false';
                } else {
                    alert('上传失败：' + (data.message || '未知错误'));
                }
            })
            .catch(error => {
                console.error('上传错误:', error);
                alert('上传失败：' + error.message);
            });
        };
        reader.readAsDataURL(file);
    }
}

function previewLogoUrlImage(input) {
    const preview = document.getElementById('logo_preview');
    const fileInput = document.getElementById('logo_image_upload');
    const url = input.value.trim();
    
    if (url) {
        preview.innerHTML = '<img src="' + url + '" alt="Logo预览" style="max-width: 200px; max-height: 80px;" onerror="this.src=\'\'; this.parentElement.innerHTML=\'<span style=\'color: #999;\'>图片加载失败</span>\'">';
        fileInput.value = '';
        fileInput.dataset.fileSelected = 'false';
        pendingLogoFile = null;
    } else {
        preview.innerHTML = '';
    }
}

let footerLinks = [];
let editingLinkId = null;

function showFooterAlert(message, type) {
    const alertEl = document.getElementById('footer-links-alert');
    alertEl.textContent = message;
    alertEl.style.display = 'block';
    alertEl.style.background = type === 'success' ? '#e8f5e9' : '#ffebee';
    alertEl.style.color = type === 'success' ? '#2e7d32' : '#c62828';
    alertEl.style.border = type === 'success' ? '1px solid #c8e6c9' : '1px solid #ffcdd2';
    
    setTimeout(() => {
        alertEl.style.display = 'none';
    }, 3000);
}

function loadFooterLinks() {
    fetch('/api/footer_links.php?action=list', {credentials: 'same-origin'})
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                footerLinks = data.data;
                renderFooterLinks();
            }
        })
        .catch(error => {
            console.error('加载快速链接失败:', error);
        });
}

function renderFooterLinks() {
    const container = document.getElementById('footer-links-list');
    if (!container) return;
    
    if (footerLinks.length === 0) {
        container.innerHTML = '<div style="color: #999; padding: 40px; text-align: center; background: #f9f9f9; border-radius: 8px; border: 2px dashed #ddd;">暂无快速链接，请点击上方按钮添加</div>';
        return;
    }
    
    container.innerHTML = `<div style="display: flex; flex-wrap: wrap; gap: 16px; justify-content: center;" class="footer-links-grid">
        <style>
            .footer-links-grid > div { width: 280px; }
            @media (max-width: 480px) {
                .footer-links-grid > div { width: 100%; }
            }
        </style>
        ${footerLinks.map((link, index) => `
            <div class="sortable-item" data-id="${link.id}" style="background: linear-gradient(135deg, #fff 0%, #fafafa 100%); border: 1px solid #e8e8e8; border-radius: 10px; padding: 16px; cursor: move; transition: all 0.2s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.04);" 
                onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)';this.style.borderColor='#c8e6c9';this.style.transform='translateY(-2px)'" 
                onmouseout="this.style.boxShadow='0 2px 4px rgba(0,0,0,0.04)';this.style.borderColor='#e8e8e8';this.style.transform='translateY(0)'">
                <div style="display: flex; align-items: center; margin-bottom: 10px;">
                    <span class="drag-handle" style="color: #bbb; margin-right: 10px; cursor: move; font-size: 14px;">☰</span>
                    <div style="flex: 1; min-width: 0; overflow: hidden;">
                        <div style="font-weight: 600; font-size: 14px; color: #333; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${escapeHtml(link.title)}</div>
                    </div>
                    <span style="padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 500; ${link.status == 1 ? 'background: #e8f5e9; color: #2e7d32;' : 'background: #ffebee; color: #c62828;'}">${link.status == 1 ? '✓ 启用' : '✗ 禁用'}</span>
                </div>
                <div style="color: #888; font-size: 12px; margin-bottom: 12px; padding: 6px 10px; background: #f5f5f5; border-radius: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    <svg style="width:12px;height:12px;vertical-align:middle;margin-right:4px;" viewBox="0 0 1024 1024"><path d="M904 192h-56v-56c0-4.4-3.6-8-8-8h-56c-4.4 0-8 3.6-8 8v56h-56c-4.4 0-8 3.6-8 8v56c0 4.4 3.6 8 8 8h56v56c0 4.4 3.6 8 8 8h56c4.4 0 8-3.6 8-8v-56h56c4.4 0 8-3.6 8-8v-56c0-4.4-3.6-8-8-8zM816 384c-4.4 0-8 3.6-8 8v448c0 4.4-3.6 8-8 8H224c-4.4 0-8-3.6-8-8V264c0-4.4 3.6-8 8-8h448c4.4 0 8-3.6 8-8v-56c0-4.4-3.6-8-8-8H192c-17.7 0-32 14.3-32 32v640c0 17.7 14.3 32 32 32h640c17.7 0 32-14.3 32-32V392c0-4.4-3.6-8-8-8z" fill="currentColor"/></svg>${escapeHtml(link.url)} ${link.target === '_blank' ? '<span style="color: #999;">↗新窗口</span>' : ''}
                </div>
                <div style="display: flex; gap: 6px;">
                    <button type="button" class="btn btn-outline" style="flex: 1; padding: 6px 10px; font-size: 12px; border-radius: 6px; display: flex; align-items: center; justify-content: center; gap: 4px;" onclick="editLink(${link.id})"><svg style="width:14px;height:14px;" viewBox="0 0 1024 1024"><path d="M880 836H144c-17.7 0-32 14.3-32 32s14.3 32 32 32h736c17.7 0 32-14.3 32-32s-14.3-32-32-32zM610.3 476h123.4c1.3 0 2.3-3.6 2.3-8v-48c0-4.4-1-8-2.3-8H610.3c-1.3 0-2.3 3.6-2.3 8v48c0 4.4 1 8 2.3 8zm-220.8-8v-48c0-4.4 1-8 2.3-8h123.4c1.3 0 2.3 3.6 2.3 8v48c0 4.4-1 8-2.3 8H391.8c-1.3 0-2.3-3.6-2.3-8zm-196.2 0v-48c0-4.4 1-8 2.3-8h123.4c1.3 0 2.3 3.6 2.3 8v48c0 4.4-1 8-2.3 8H195.6c-1.3 0-2.3-3.6-2.3-8z" fill="currentColor"/><path d="M880 184H144c-17.7 0-32 14.3-32 32v560c0 17.7 14.3 32 32 32h736c17.7 0 32-14.3 32-32V216c0-17.7-14.3-32-32-32z m-40 552H184V256h656v480z" fill="currentColor"/></svg>编辑</button>
                    <button type="button" class="btn btn-outline" style="flex: 1; padding: 6px 10px; font-size: 12px; border-radius: 6px; display: flex; align-items: center; justify-content: center; gap: 4px; ${link.status == 1 ? 'color: #e53935; border-color: #ffcdd2;' : 'color: #2e7d32; border-color: #c8e6c9;'}" onclick="toggleLinkStatus(${link.id})">${link.status == 1 ? '<svg style="width:14px;height:14px;" viewBox="0 0 1024 1024"><path d="M832 464h-68V240c0-70.7-57.3-128-128-128H388c-70.7 0-128 57.3-128 128v224h-68c-17.7 0-32 14.3-32 32v288c0 17.7 14.3 32 32 32h640c17.7 0 32-14.3 32-32V496c0-17.7-14.3-32-32-32zM540 701v53c0 4.4-3.6 8-8 8h-40c-4.4 0-8-3.6-8-8v-53c-12.1-8.7-20-22.9-20-39 0-26.5 21.5-48 48-48s48 21.5 48 48c0 16.1-7.9 30.3-20 39z m152-237H332V240c0-35.3 28.7-64 64-64h232c35.3 0 64 28.7 64 64v224z" fill="currentColor"/></svg>禁用' : '<svg style="width:14px;height:14px;" viewBox="0 0 1024 1024"><path d="M832 464h-68V240c0-70.7-57.3-128-128-128H388c-70.7 0-128 57.3-128 128v224h-68c-17.7 0-32 14.3-32 32v288c0 17.7 14.3 32 32 32h640c17.7 0 32-14.3 32-32V496c0-17.7-14.3-32-32-32zM332 240c0-35.3 28.7-64 64-64h232c35.3 0 64 28.7 64 64v224H332V240z m428 544H264V528h496v256z" fill="currentColor"/><path d="M540 701v53c0 4.4-3.6 8-8 8h-40c-4.4 0-8-3.6-8-8v-53c-12.1-8.7-20-22.9-20-39 0-26.5 21.5-48 48-48s48 21.5 48 48c0 16.1-7.9 30.3-20 39z" fill="currentColor"/></svg>启用'}</button>
                    <button type="button" class="btn btn-danger" style="padding: 6px 10px; font-size: 12px; border-radius: 6px; display: flex; align-items: center; justify-content: center;" onclick="deleteLink(${link.id})"><svg style="width:14px;height:14px;" viewBox="0 0 1024 1024"><path d="M360 184h-8c4.4 0 8-3.6 8-8v8h304v-8c0 4.4 3.6 8 8 8h-8v72h72v-80c0-35.3-28.7-64-64-64H352c-35.3 0-64 28.7-64 64v80h72v-72z" fill="currentColor"/><path d="M864 256H160c-17.7 0-32 14.3-32 32v32c0 4.4 3.6 8 8 8h60.4l24.7 523c1.6 34.1 29.8 61 63.9 61h454c34.2 0 62.3-26.8 63.9-61l24.7-523H888c4.4 0 8-3.6 8-8v-32c0-17.7-14.3-32-32-32zM731.3 840H292.7l-24.2-512h487l-24.2 512z" fill="currentColor"/></svg></button>
                </div>
            </div>
        `).join('')}
    </div>`;
    
    initSortable();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function initSortable() {
    const container = document.getElementById('footer-links-list');
    if (!container) return;
    
    const flexContainer = container.querySelector('.footer-links-grid');
    if (!flexContainer) return;
    
    const items = flexContainer.querySelectorAll('.sortable-item');
    let draggedItem = null;
    
    items.forEach(item => {
        item.addEventListener('dragstart', function(e) {
            draggedItem = this;
            this.style.opacity = '0.5';
        });
        
        item.addEventListener('dragend', function(e) {
            this.style.opacity = '1';
            draggedItem = null;
            saveLinksOrder();
        });
        
        item.addEventListener('dragover', function(e) {
            e.preventDefault();
            if (draggedItem && draggedItem !== this) {
                const allItems = Array.from(flexContainer.querySelectorAll('.sortable-item'));
                const draggedIndex = allItems.indexOf(draggedItem);
                const targetIndex = allItems.indexOf(this);
                
                if (draggedIndex < targetIndex) {
                    this.parentNode.insertBefore(draggedItem, this.nextSibling);
                } else {
                    this.parentNode.insertBefore(draggedItem, this);
                }
            }
        });
        
        item.setAttribute('draggable', 'true');
    });
}

function saveLinksOrder() {
    const container = document.getElementById('footer-links-list');
    const flexContainer = container.querySelector('.footer-links-grid');
    if (!flexContainer) return;
    
    const items = flexContainer.querySelectorAll('.sortable-item');
    const ids = Array.from(items).map(item => parseInt(item.dataset.id));
    
    fetch('/api/footer_links.php?action=update_order', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        credentials: 'same-origin',
        body: JSON.stringify({ids: ids})
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            showFooterAlert('保存排序失败: ' + data.message, 'error');
        }
    });
}

function openAddLinkModal() {
    editingLinkId = null;
    document.getElementById('link-modal-title').textContent = '添加快速链接';
    document.getElementById('link-title').value = '';
    document.getElementById('link-url').value = '';
    document.getElementById('link-target').value = '_self';
    document.getElementById('link-status').value = '1';
    document.getElementById('link-modal').style.display = 'flex';
}

function editLink(id) {
    const link = footerLinks.find(l => l.id === id);
    if (!link) return;
    
    editingLinkId = id;
    document.getElementById('link-modal-title').textContent = '编辑快速链接';
    document.getElementById('link-title').value = link.title;
    document.getElementById('link-url').value = link.url;
    document.getElementById('link-target').value = link.target;
    document.getElementById('link-status').value = link.status;
    document.getElementById('link-modal').style.display = 'flex';
}

function closeLinkModal() {
    document.getElementById('link-modal').style.display = 'none';
    editingLinkId = null;
}

function saveLink() {
    const title = document.getElementById('link-title').value.trim();
    const url = document.getElementById('link-url').value.trim();
    const target = document.getElementById('link-target').value;
    const status = parseInt(document.getElementById('link-status').value);
    
    if (!title) {
        alert('请输入链接标题');
        return;
    }
    
    if (!url) {
        alert('请输入链接URL');
        return;
    }
    
    const data = {title, url, target, status};
    const action = editingLinkId ? 'update' : 'create';
    if (editingLinkId) {
        data.id = editingLinkId;
    }
    
    fetch('/api/footer_links.php?action=' + action, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        credentials: 'same-origin',
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.message || '请求失败');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            closeLinkModal();
            loadFooterLinks();
            showFooterAlert(editingLinkId ? '更新成功' : '添加成功', 'success');
        } else {
            showFooterAlert('保存失败: ' + (data.message || '未知错误'), 'error');
        }
    })
    .catch(error => {
        console.error('保存失败:', error);
        showFooterAlert('保存失败: ' + error.message, 'error');
    });
}

function deleteLink(id) {
    if (!confirm('确定要删除这个链接吗？')) return;
    
    fetch('/api/footer_links.php?action=delete', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        credentials: 'same-origin',
        body: JSON.stringify({id: id})
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.message || '请求失败');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            loadFooterLinks();
            showFooterAlert('删除成功', 'success');
        } else {
            showFooterAlert('删除失败: ' + (data.message || '未知错误'), 'error');
        }
    })
    .catch(error => {
        console.error('删除失败:', error);
        showFooterAlert('删除失败: ' + error.message, 'error');
    });
}

function toggleLinkStatus(id) {
    fetch('/api/footer_links.php?action=toggle_status', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        credentials: 'same-origin',
        body: JSON.stringify({id: id})
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.message || '请求失败');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            loadFooterLinks();
            showFooterAlert('状态更新成功', 'success');
        } else {
            showFooterAlert('更新状态失败: ' + (data.message || '未知错误'), 'error');
        }
    })
    .catch(error => {
        console.error('更新状态失败:', error);
        showFooterAlert('更新状态失败: ' + error.message, 'error');
    });
}

document.addEventListener('DOMContentLoaded', function() {
    loadFooterLinks();
});

function sendTestEmail() {
    const testEmail = document.getElementById('test_email_address').value.trim();
    const resultDiv = document.getElementById('email-test-result');
    
    if (!testEmail) {
        alert('请输入测试邮箱地址');
        return;
    }
    
    resultDiv.style.display = 'block';
    resultDiv.innerHTML = '<div style="padding: 10px; background: #fff3e0; border-radius: 6px; color: #e65100;">正在发送测试邮件...</div>';
    
    fetch('/api/email.php?action=test', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        credentials: 'same-origin',
        body: JSON.stringify({to: testEmail})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultDiv.innerHTML = '<div style="padding: 10px; background: #e8f5e9; border-radius: 6px; color: #2e7d32;">✓ 测试邮件发送成功，请检查收件箱</div>';
        } else {
            resultDiv.innerHTML = '<div style="padding: 10px; background: #ffebee; border-radius: 6px; color: #c62828;">✗ 发送失败: ' + (data.message || '未知错误') + '</div>';
        }
    })
    .catch(error => {
        resultDiv.innerHTML = '<div style="padding: 10px; background: #ffebee; border-radius: 6px; color: #c62828;">✗ 发送失败: ' + error.message + '</div>';
    });
}
</script>

<?php include 'components/media-picker.php'; ?>
<link rel="stylesheet" href="components/media-picker.css">
<script src="components/media-picker.js"></script>

<div id="link-modal" class="modal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; display: none; align-items: center; justify-content: center; padding: 16px; box-sizing: border-box;">
    <div class="modal-content" style="background: #fff; border-radius: 12px; padding: 24px; width: 100%; max-width: 420px; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 40px rgba(0,0,0,0.2); margin: auto; box-sizing: border-box;">
        <h3 id="link-modal-title" style="margin: 0 0 20px 0; font-size: 18px;">添加快速链接</h3>
        <div class="form-group">
            <label for="link-title">链接标题 *</label>
            <input type="text" id="link-title" placeholder="例如：首页" style="font-size: 16px;">
        </div>
        <div class="form-group">
            <label for="link-url">链接URL *</label>
            <input type="text" id="link-url" placeholder="例如：/ 或 /about" style="font-size: 16px;">
        </div>
        <div class="form-group">
            <label for="link-target">打开方式</label>
            <select id="link-target" style="font-size: 16px;">
                <option value="_self">当前窗口</option>
                <option value="_blank">新窗口</option>
            </select>
        </div>
        <div class="form-group">
            <label for="link-status">状态</label>
            <select id="link-status" style="font-size: 16px;">
                <option value="1">启用</option>
                <option value="0">禁用</option>
            </select>
        </div>
        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; flex-wrap: wrap;">
            <button type="button" class="btn btn-outline" onclick="closeLinkModal()" style="min-width: 80px;">取消</button>
            <button type="button" class="btn btn-primary" onclick="saveLink()" style="min-width: 80px;">保存</button>
        </div>
    </div>
</div>
</html>

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
require_once __DIR__ . '/includes/functions.php';

$external_link_redirect = Settings::get('external_link_redirect', '0');
$external_link_auth = Settings::get('external_link_auth', '0');
$exclude_domains = Settings::get('external_link_exclude_domains', '');

$url = $_GET['url'] ?? '';

if (empty($url)) {
    http_response_code(400);
    echo '缺少目标URL';
    exit;
}

if (!filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo '无效的URL';
    exit;
}

$parsedUrl = parse_url($url);
$targetDomain = $parsedUrl['host'] ?? '';

$excludeDomainsList = array_filter(array_map('trim', explode("\n", $exclude_domains)));
$isExcluded = false;
foreach ($excludeDomainsList as $excludeDomain) {
    if ($targetDomain === $excludeDomain || str_ends_with($targetDomain, '.' . $excludeDomain)) {
        $isExcluded = true;
        break;
    }
}

if ($external_link_redirect !== '1' || $isExcluded) {
    header('Location: ' . $url);
    exit;
}

$db = Database::getInstance();
$db->insert('`lty-external_link_logs`', [
    'link_url' => $url,
    'source_page' => $_SERVER['HTTP_REFERER'] ?? null,
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
    'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500)
]);

if ($external_link_auth === '1' && !isset($_GET['confirm'])) {
    $site_name = Settings::get('site_name', '团队官网');
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>即将离开 <?php echo htmlspecialchars($site_name); ?></title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .container {
                background: white;
                border-radius: 16px;
                padding: 40px;
                max-width: 500px;
                width: 100%;
                text-align: center;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            }
            .icon {
                width: 80px;
                height: 80px;
                background: linear-gradient(135deg, #f59e0b, #ef4444);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 24px;
            }
            .icon svg {
                width: 40px;
                height: 40px;
                color: white;
            }
            h1 {
                font-size: 24px;
                color: #1f2937;
                margin-bottom: 16px;
            }
            p {
                color: #6b7280;
                margin-bottom: 12px;
                line-height: 1.6;
            }
            .url-box {
                background: #f3f4f6;
                border-radius: 8px;
                padding: 16px;
                margin: 24px 0;
                word-break: break-all;
                font-size: 14px;
                color: #374151;
            }
            .buttons {
                display: flex;
                gap: 12px;
                justify-content: center;
                flex-wrap: wrap;
            }
            .btn {
                padding: 12px 32px;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                text-decoration: none;
                transition: all 0.2s;
            }
            .btn-primary {
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: white;
                border: none;
            }
            .btn-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
            }
            .btn-secondary {
                background: white;
                color: #374151;
                border: 1px solid #d1d5db;
            }
            .btn-secondary:hover {
                background: #f9fafb;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                </svg>
            </div>
            <h1>即将离开 <?php echo htmlspecialchars($site_name); ?></h1>
            <p>您即将访问外部链接，请注意信息安全。</p>
            <div class="url-box"><?php echo htmlspecialchars($url); ?></div>
            <div class="buttons">
                <a href="javascript:history.back()" class="btn btn-secondary">返回上页</a>
                <a href="?url=<?php echo urlencode($url); ?>&confirm=1" class="btn btn-primary">继续访问</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

header('Location: ' . $url);
exit;

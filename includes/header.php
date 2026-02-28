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
$site_name = isset($site_name) ? $site_name : Settings::get('site_name', '团队官网');
$page_title = isset($page_title) ? $page_title : $site_name;
$current_page = isset($current_page) ? $current_page : 'home';

$seo_separator = Settings::get('seo_separator', '-');
$seo_title = Settings::get('seo_title', '');
$seo_keywords = Settings::get('seo_keywords', '');
$seo_description = Settings::get('seo_description', '');
$seo_default_image = Settings::get('seo_default_image', '');
$baidu_auto_submit = Settings::get('baidu_auto_submit', '0');

$final_seo_title = $seo_title ?: $site_name;
if ($page_title && $page_title !== $site_name) {
    $final_seo_title = $page_title . ' ' . $seo_separator . ' ' . ($seo_title ?: $site_name);
}

$page_keywords = isset($page_keywords) ? $page_keywords : '';
$page_description = isset($page_description) ? $page_description : '';
$page_image = isset($page_image) ? $page_image : '';

$final_keywords = $page_keywords ?: $seo_keywords;
$final_description = $page_description ?: $seo_description;
$final_image = $page_image ?: $seo_default_image;

if ($final_image && strpos($final_image, '://') === false) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $final_image = $protocol . '://' . $_SERVER['HTTP_HOST'] . ($final_image[0] === '/' ? '' : '/') . $final_image;
}

$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($final_seo_title); ?></title>
    <?php if ($final_keywords): ?>
    <meta name="keywords" content="<?php echo htmlspecialchars($final_keywords); ?>">
    <?php endif; ?>
    <?php if ($final_description): ?>
    <meta name="description" content="<?php echo htmlspecialchars($final_description); ?>">
    <?php endif; ?>
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo htmlspecialchars($final_seo_title); ?>">
    <?php if ($final_description): ?>
    <meta property="og:description" content="<?php echo htmlspecialchars($final_description); ?>">
    <?php endif; ?>
    <?php if ($final_image): ?>
    <meta property="og:image" content="<?php echo htmlspecialchars($final_image); ?>">
    <?php endif; ?>
    <meta property="og:url" content="<?php echo htmlspecialchars($current_url); ?>">
    <meta property="og:site_name" content="<?php echo htmlspecialchars($site_name); ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($final_seo_title); ?>">
    <?php if ($final_description): ?>
    <meta name="twitter:description" content="<?php echo htmlspecialchars($final_description); ?>">
    <?php endif; ?>
    <?php if ($final_image): ?>
    <meta name="twitter:image" content="<?php echo htmlspecialchars($final_image); ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <?php if (isset($extra_css)): ?>
        <?php foreach ($extra_css as $css): ?>
            <link rel="stylesheet" href="<?php echo htmlspecialchars($css); ?>?v=<?php echo time(); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <?php if ($baidu_auto_submit === '1'):
        $baidu_token = Settings::get('baidu_token', '');
        $baidu_normal = Settings::get('baidu_normal_submit', '0');
        $baidu_fast = Settings::get('baidu_fast_submit', '0');
    ?>
    <script>
    (function(){
        var bp = document.createElement('script');
        var curProtocol = window.location.protocol.split(':')[0];
        <?php if ($baidu_normal === '1'): ?>
        bp.src = curProtocol + '://push.zhanzhang.baidu.com/push.js';
        <?php endif; ?>
        <?php if ($baidu_fast === '1' && $baidu_token): ?>
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'https://data.zz.baidu.com/urls?site=<?php echo htmlspecialchars($current_url); ?>&token=<?php echo htmlspecialchars($baidu_token); ?>', true);
        xhr.setRequestHeader('Content-Type', 'text/plain');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                fetch('/api/settings.php?action=increment_baidu_submit', {
                    method: 'POST',
                    credentials: 'same-origin'
                }).catch(function(){});
            }
        };
        xhr.send('<?php echo htmlspecialchars($current_url); ?>');
        <?php endif; ?>
        var s = document.getElementsByTagName("script")[0];
        s.parentNode.insertBefore(bp, s);
    })();
    </script>
    <?php endif; ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.addEventListener('error', function(e) {
            if (e.target.tagName === 'IMG') {
                if (!e.target.dataset.errorHandled) {
                    e.target.dataset.errorHandled = 'true';
                    e.target.src = 'assets/images/丢失/图片丢失-copy.png';
                }
            }
        }, true);
        
        (function() {
            var pageUrl = window.location.pathname + window.location.search;
            var pageTitle = document.title;
            var tracked = sessionStorage.getItem('pageview_' + pageUrl);
            if (!tracked) {
                fetch('/api/stats.php?action=track', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({page_url: pageUrl, page_title: pageTitle})
                }).catch(function() {});
                sessionStorage.setItem('pageview_' + pageUrl, '1');
            }
        })();
    });
    </script>
    <?php
    $custom_css = Settings::get('custom_css', '');
    $custom_head_html = Settings::get('custom_head_html', '');
    ?>
    <?php if ($custom_css): ?>
    <style><?php echo $custom_css; ?></style>
    <?php endif; ?>
    <?php if ($custom_head_html): ?>
    <?php echo $custom_head_html; ?>
    <?php endif; ?>
</head>
<body>
    <header>
        <div class="container">
            <nav class="navbar">
                <a href="/" class="logo"><?php echo get_logo_html(); ?></a>
                <button class="mobile-menu-btn" onclick="toggleMenu()">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <ul class="nav-links" id="navLinks">
                    <li><a href="/" class="<?php echo $current_page === 'home' ? 'active' : ''; ?>">首页</a></li>
                    <li><a href="/team" class="<?php echo $current_page === 'team' ? 'active' : ''; ?>">团队成员</a></li>
                    <li><a href="/contact" class="<?php echo $current_page === 'contact' ? 'active' : ''; ?>">联系我们</a></li>
                </ul>
            </nav>
        </div>
    </header>

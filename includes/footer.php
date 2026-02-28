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
function getFooterSocialIcon($platform) {
    $icons = [
        '微信' => '<svg viewBox="0 0 1024 1024"><path d="M664.250054 368.541681c10.015098 0 19.892049 0.732687 29.627708 2.038659-26.647917-122.810739-159.358451-214.077703-310.826188-214.077703-169.353083 0-308.085774 114.232694-308.085774 259.274068 0 83.70765 46.165436 152.460344 123.281791 205.78483l-30.838712 91.730191 107.688658-53.455469c38.560521 7.53665 69.466819 15.308511 107.954242 15.308511 9.661473 0 19.230033-0.467618 28.752825-1.216492-6.025089-20.36584-9.278883-41.723264-9.278883-63.862492C402.525721 476.487004 517.108796 368.541681 664.250054 368.541681zM501.760858 297.916c23.200395 0 38.557309 15.120868 38.557309 38.060875 0 22.811407-15.356914 38.232825-38.557309 38.232825-23.107276 0-46.339394-15.421418-46.339394-38.232825C455.421464 313.036868 478.653582 297.916 501.760858 297.916zM284.536595 374.2097c-23.107276 0-46.456607-15.421418-46.456607-38.232825 0-22.940007 23.349331-38.060875 46.456607-38.060875 23.076489 0 38.464059 15.120868 38.464059 38.060875C323.000654 358.788282 307.613084 374.2097 284.536595 374.2097z" fill="currentColor"/><path d="M945.448458 606.151333c0-121.888048-123.386635-221.236753-262.224529-221.236753-146.836891 0-262.376678 99.348705-262.376678 221.236753 0 122.017528 115.539787 221.236753 262.376678 221.236753 30.662556 0 61.619693-7.617744 92.118724-15.30442l84.513836 45.864058-23.226723-76.170797C899.442573 735.776599 945.448458 674.903947 945.448458 606.151333zM621.53249 570.544601c-15.356914 0-30.838712-15.046258-30.838712-30.563732 0-15.335378 15.481798-30.689082 30.838712-30.689082 23.200395 0 38.464059 15.353704 38.464059 30.689082C659.996549 555.498343 644.732885 570.544601 621.53249 570.544601zM744.699473 570.544601c-15.231008 0-30.682386-15.046258-30.682386-30.563732 0-15.335378 15.451378-30.689082 30.682386-30.689082 23.076489 0 38.557309 15.353704 38.557309 30.689082C783.256782 555.498343 767.775962 570.544601 744.699473 570.544601z" fill="currentColor"/></svg>',
        '微博' => '<svg viewBox="0 0 1024 1024"><path d="M877.7472 389.0432c-17.2032-5.9392-28.8768-9.8304-19.968-35.4816 19.3536-55.808 21.3504-103.8848 0.1024-138.24-39.8848-64.512-148.992-61.0304-273.7152-1.7408 0 0-39.2192 17.1008-29.184-13.8752 19.2-61.3376 16.384-112.64-13.568-142.1824-67.9424-67.0784-248.6272 2.4064-403.6096 155.2384C65.3312 318.0032 12.8 418.8672 12.8 505.6c0 166.0416 214.3232 266.9056 423.936 266.9056 274.7392 0 457.472-158.3104 457.472-284.0576 0-75.9808-64.7168-119.0912-116.4544-99.4048zM437.3504 707.6352c-161.792 15.8208-301.3632-56.7808-311.6544-162.2016-10.2912-105.3696 112.2304-203.5712 274.016-219.4432 161.8432-15.8208 301.3632 56.7808 311.6544 162.1504C721.6576 593.5104 599.1424 691.7632 437.3504 707.6352z" fill="currentColor"/><path d="M411.1872 381.5936c-78.4896 16.384-132.6592 76.8-121.0368 134.8608 11.5712 58.112 84.0704 92.8256 162.5088 76.4416 78.5408-16.384 132.6592-76.8 121.088-134.912C562.1248 399.9744 489.6768 365.2096 411.1872 381.5936zM369.7152 514.8672c-24.1152 5.0176-46.8992-7.2704-50.8928-27.4944-3.9424-20.1728 12.2368-40.6016 36.352-45.6704 24.1664-5.0176 46.9504 7.2704 50.8928 27.4944C410.0672 489.3696 393.9328 509.7984 369.7152 514.8672zM424.192 469.6064c-9.0112 1.8944-17.5616-3.1744-19.1488-11.264-1.536-8.0384 4.5056-16.1792 13.5168-18.0224 9.0624-1.8944 17.6128 3.1232 19.2 11.2128C439.3472 459.6224 433.2544 467.712 424.192 469.6064z" fill="currentColor"/><path d="M948.1728 322.56c-25.1392-27.1872-62.3104-34.1504-95.8464-21.7088-9.5744 3.4816-14.4896 14.0288-10.9568 23.6032 3.4816 9.5744 14.0288 14.4896 23.6032 10.9568 19.2-6.912 40.192-3.0208 54.4768 12.3904 14.2336 15.36 17.408 36.9152 9.0112 56.1152-4.0448 9.2672 0.3072 20.0192 9.5744 24.064 9.2672 4.0448 20.0192-0.3072 24.064-9.5744C978.5856 384.9216 973.312 349.7472 948.1728 322.56z" fill="currentColor"/></svg>',
        'LinkedIn' => '<svg viewBox="0 0 1024 1024"><path d="M880 112H144c-17.7 0-32 14.3-32 32v736c0 17.7 14.3 32 32 32h736c17.7 0 32-14.3 32-32V144c0-17.7-14.3-32-32-32zM349.3 793.7H230.6V411.9h118.7v381.8z m-59.3-434c-38 0-68.8-30.8-68.8-68.8s30.8-68.8 68.8-68.8c38 0 68.8 30.8 68.8 68.8s-30.8 68.8-68.8 68.8z m447.7 434H619.2V533.8c0-45.4-16.4-76.5-57.1-76.5-31.1 0-49.7 21-57.9 41.3-3 7.3-3.7 17.3-3.7 27.4v267.7H381.8s1.5-388.1 0-428.3h118.7v60.7c15.8-24.3 43.9-58.9 106.8-58.9 78 0 136.4 51 136.4 160.6v266z" fill="currentColor"/></svg>',
        'GitHub' => '<svg viewBox="0 0 1024 1024"><path d="M512 42.666667C232.533333 42.666667 6.4 268.8 6.4 548.266667c0 222.293333 144.213333 410.88 344.32 477.013333 25.173333 4.693333 34.346667-10.88 34.346667-24.213333 0-11.946667-0.426667-51.626667-0.64-93.653334-140.117333 30.506667-169.6-59.52-169.6-59.52-22.869333-58.133333-55.893333-73.6-55.893334-73.6-45.653333-31.146667 3.456-30.506667 3.456-30.506666 50.517333 3.584 77.141333 51.84 77.141334 51.84 44.885333 76.970667 117.76 54.698667 146.474666 41.813333 4.565333-32.512 17.578667-54.741333 31.957334-67.306667-111.829333-12.8-229.461333-55.893333-229.461334-248.746666 0-54.954667 19.626667-99.84 51.84-135.125334-5.162667-12.757333-22.442667-64.085333 4.906667-133.653333 0 0 42.325333-13.525333 138.624 51.626667 40.192-11.178667 83.285333-16.725333 126.122667-16.938667 42.794667 0.213333 85.930667 5.76 126.165333 16.938667 96.213333-65.152 138.453333-51.626667 138.453333-51.626667 27.434667 69.568 10.154667 120.896 4.992 133.653333 32.298667 35.285333 51.797333 80.170667 51.797334 135.125334 0 193.365333-117.845333 235.776-230.058667 248.277333 18.090667 15.658667 34.176 46.464 34.176 93.653333 0 67.669333-0.597333 122.112-0.597333 138.88 0 13.44 9.088 29.098667 34.602666 24.149334C873.344 958.976 1017.6 770.474667 1017.6 548.266667 1017.6 268.8 791.466667 42.666667 512 42.666667z" fill="currentColor"/></svg>',
        '抖音' => '<svg viewBox="0 0 1024 1024"><path d="M512 128c-212.1 0-384 171.9-384 384s171.9 384 384 384 384-171.9 384-384-171.9-384-384-384z m0 686c-166.6 0-302-135.4-302-302s135.4-302 302-302 302 135.4 302 302-135.4 302-302 302z" fill="currentColor"/><path d="M680.5 396.5c-34.6-6.9-62.5-34.8-69.4-69.4-1.1-5.5-1.7-11.2-1.7-17.1h-55.4v288.4c0 33.1-26.8 59.9-59.9 59.9s-59.9-26.8-59.9-59.9 26.8-59.9 59.9-59.9c6.2 0 12.2 1 17.8 2.7v-56.6c-5.8-0.8-11.7-1.3-17.8-1.3-63.5 0-115.1 51.6-115.1 115.1s51.6 115.1 115.1 115.1 115.1-51.6 115.1-115.1V463.8c21.8 15.5 48.4 24.6 77.1 24.6v-55.4c-1.9 0-3.8-0.1-5.8-0.2v-36.3z" fill="currentColor"/></svg>',
        '小红书' => '<svg viewBox="0 0 1024 1024"><path d="M512 128c-212.1 0-384 171.9-384 384s171.9 384 384 384 384-171.9 384-384-171.9-384-384-384z m0 686c-166.6 0-302-135.4-302-302s135.4-302 302-302 302 135.4 302 302-135.4 302-302 302z" fill="currentColor"/><path d="M640 384h-256c-17.7 0-32 14.3-32 32v192c0 17.7 14.3 32 32 32h256c17.7 0 32-14.3 32-32V416c0-17.7-14.3-32-32-32z m-128 192c-35.3 0-64-28.7-64-64s28.7-64 64-64 64 28.7 64 64-28.7 64-64 64z" fill="currentColor"/></svg>',
        'QQ' => '<svg viewBox="0 0 1024 1024"><path d="M512 128c-212.1 0-384 171.9-384 384s171.9 384 384 384 384-171.9 384-384-171.9-384-384-384z m0 686c-166.6 0-302-135.4-302-302s135.4-302 302-302 302 135.4 302 302-135.4 302-302 302z" fill="currentColor"/><path d="M704 544c0-106-86-192-192-192s-192 86-192 192c0 71.4 39 133.6 96.8 166.8-6.4 18.8-24.8 32.2-46.8 32.2-28 0-50.6-22.6-50.6-50.6 0-14.4 6-27.4 15.6-36.6-30.4-26.4-49.6-65-49.6-108 0-79.4 64.4-143.8 143.8-143.8s143.8 64.4 143.8 143.8c0 43-19.2 81.6-49.6 108 9.6 9.2 15.6 22.2 15.6 36.6 0 28-22.6 50.6-50.6 50.6-22 0-40.4-13.4-46.8-32.2C665 677.6 704 615.4 704 544z" fill="currentColor"/></svg>',
        '知乎' => '<svg viewBox="0 0 1024 1024"><path d="M512 128c-212.1 0-384 171.9-384 384s171.9 384 384 384 384-171.9 384-384-171.9-384-384-384z m0 686c-166.6 0-302-135.4-302-302s135.4-302 302-302 302 135.4 302 302-135.4 302-302 302z" fill="currentColor"/><path d="M640 384H384v64h96v192h64V448h96z" fill="currentColor"/></svg>',
        'B站' => '<svg viewBox="0 0 1024 1024"><path d="M777.8 384h-531.6c-35.3 0-64 28.7-64 64v192c0 35.3 28.7 64 64 64h531.6c35.3 0 64-28.7 64-64V448c0-35.3-28.7-64-64-64zM352 576c-17.7 0-32-14.3-32-32s14.3-32 32-32 32 14.3 32 32-14.3 32-32 32z m320 0c-17.7 0-32-14.3-32-32s14.3-32 32-32 32 14.3 32 32-14.3 32-32 32z" fill="currentColor"/><path d="M288 320l96-96h256l96 96" stroke="currentColor" stroke-width="48" fill="none"/></svg>',
        'Twitter' => '<svg viewBox="0 0 1024 1024"><path d="M928 254.4c-30.6 13.2-63.9 22.8-98.6 27 35.4-21.2 62.4-54.6 75.2-94.6-33.2 19.6-69.8 33.8-108.8 41.4C763.4 195 718.4 172 668 172c-94.6 0-171.4 76.8-171.4 171.4 0 13.4 1.4 26.6 4.4 39.2C356.6 375.6 226.4 305 139.4 196c-14.8 25.4-23.2 54.6-23.2 86 0 59.4 30.2 111.8 76.2 142.4-28-0.8-54.6-8.6-77.8-21.4v2c0 83 59 152.2 137.4 167.8-14.4 4-29.4 6-45 6-11 0-21.8-1-32.2-3 21.8 67.6 85 116.8 159.8 118-58.6 46-132.2 73.4-212.4 73.4-13.8 0-27.4-0.8-40.8-2.4 75.6 48.6 165.4 76.8 262 76.8 314.4 0 486.4-260.4 486.4-486.4 0-7.4-0.2-14.8-0.6-22 33.4-24.2 62.4-54.4 85.2-88.6z" fill="currentColor"/></svg>',
        'Facebook' => '<svg viewBox="0 0 1024 1024"><path d="M928 512c0-229.8-186.2-416-416-416S96 282.2 96 512c0 207.6 152.4 379.4 352 411.4V640h-96v-128h96v-96c0-96 57.6-149.6 145.2-149.6 42 0 86 7.6 86 7.6v94.4h-48.4c-47.8 0-62.8 29.8-62.8 60.2V512h106.8l-17 128H560v283.4c199.6-32 352-203.8 352-411.4z" fill="currentColor"/></svg>',
        'Instagram' => '<svg viewBox="0 0 1024 1024"><path d="M512 96c-114.7 0-129.1 0.5-174.2 2.5-45.1 2.1-75.9 9.2-102.9 19.7-27.9 10.8-51.5 25.3-75.1 48.9-23.6 23.6-38.1 47.2-48.9 75.1-10.5 27-17.6 57.8-19.7 102.9C89.5 390.9 89 405.3 89 520s0.5 129.1 2.5 174.2c2.1 45.1 9.2 75.9 19.7 102.9 10.8 27.9 25.3 51.5 48.9 75.1 23.6 23.6 47.2 38.1 75.1 48.9 27 10.5 57.8 17.6 102.9 19.7 45.1 2.1 59.5 2.5 174.2 2.5s129.1-0.5 174.2-2.5c45.1-2.1 75.9-9.2 102.9-19.7 27.9-10.8 51.5-25.3 75.1-48.9 23.6-23.6 38.1-47.2 48.9-75.1 10.5-27 17.6-57.8 19.7-102.9 2.1-45.1 2.5-59.5 2.5-174.2s-0.5-129.1-2.5-174.2c-2.1-45.1-9.2-75.9-19.7-102.9-10.8-27.9-25.3-51.5-48.9-75.1-23.6-23.6-47.2-38.1-75.1-48.9-27-10.5-57.8-17.6-102.9-19.7C641.1 96.5 626.7 96 512 96z m0 76.2c112.8 0 126.2 0.4 170.8 2.5 41.2 1.9 63.6 8.8 78.5 14.6 19.7 7.7 33.8 16.9 48.6 31.7 14.8 14.8 24 28.9 31.7 48.6 5.8 14.9 12.7 37.3 14.6 78.5 2.1 44.6 2.5 58 2.5 170.8s-0.4 126.2-2.5 170.8c-1.9 41.2-8.8 63.6-14.6 78.5-7.7 19.7-16.9 33.8-31.7 48.6-14.8 14.8-28.9 24-48.6 31.7-14.9 5.8-37.3 12.7-78.5 14.6-44.6 2.1-58 2.5-170.8 2.5s-126.2-0.4-170.8-2.5c-41.2-1.9-63.6-8.8-78.5-14.6-19.7-7.7-33.8-16.9-48.6-31.7-14.8-14.8-24-28.9-31.7-48.6-5.8-14.9-12.7-37.3-14.6-78.5-2.1-44.6-2.5-58-2.5-170.8s0.4-126.2 2.5-170.8c1.9-41.2 8.8-63.6 14.6-78.5 7.7-19.7 16.9-33.8 31.7-48.6 14.8-14.8 28.9-24 48.6-31.7 14.9-5.8 37.3-12.7 78.5-14.6 44.6-2.1 58-2.5 170.8-2.5z m0 129.8c-119.8 0-217 97.2-217 217s97.2 217 217 217 217-97.2 217-217-97.2-217-217-217z m0 357.8c-77.7 0-140.8-63.1-140.8-140.8s63.1-140.8 140.8-140.8 140.8 63.1 140.8 140.8-63.1 140.8-140.8 140.8z m276.2-366.2c0 28-22.7 50.7-50.7 50.7s-50.7-22.7-50.7-50.7 22.7-50.7 50.7-50.7 50.7 22.7 50.7 50.7z" fill="currentColor"/></svg>',
        'YouTube' => '<svg viewBox="0 0 1024 1024"><path d="M941.3 296.1c-10.3-38.6-40.6-69-79.2-79.2C792.2 200 512 200 512 200s-280.2 0-350.1 16.9c-38.6 10.3-69 40.6-79.2 79.2C66 366 66 512 66 512s0 146 16.9 215.9c10.3 38.6 40.6 69 79.2 79.2C231.8 824 512 824 512 824s280.2 0 350.1-16.9c38.6-10.3 69-40.6 79.2-79.2C958 658 958 512 958 512s0-146-16.7-215.9zM423 646V378l232 134-232 134z" fill="currentColor"/></svg>',
    ];
    
    foreach ($icons as $name => $icon) {
        if (stripos($platform, $name) !== false) {
            return $icon;
        }
    }
    
    return '<svg viewBox="0 0 1024 1024"><path d="M512 85.333333c-235.733333 0-426.666667 190.933333-426.666667 426.666667s190.933333 426.666667 426.666667 426.666667 426.666667-190.933333 426.666667-426.666667S747.733333 85.333333 512 85.333333z m0 768c-188.8 0-341.333333-152.533333-341.333333-341.333333s152.533333-341.333333 341.333333-341.333333 341.333333 152.533333 341.333333 341.333333-152.533333 341.333333-341.333333 341.333333z" fill="currentColor"/><path d="M682.666667 512h-128V341.333333c0-23.466667-19.2-42.666667-42.666667-42.666666s-42.666667 19.2-42.666667 42.666666v213.333334c0 23.466667 19.2 42.666667 42.666667 42.666666h170.666667c23.466667 0 42.666667-19.2 42.666666-42.666666s-19.2-42.666667-42.666666-42.666667z" fill="currentColor"/></svg>';
}

$db = Database::getInstance();

$external_link_redirect = Settings::get('external_link_redirect', '0');
$external_link_article = Settings::get('external_link_article', '0');
$exclude_domains = Settings::get('external_link_exclude_domains', '');
$excludeDomainsList = array_filter(array_map('trim', explode("\n", $exclude_domains)));

function processExternalLink($url, $external_link_redirect, $excludeDomainsList) {
    if ($external_link_redirect !== '1') {
        return $url;
    }
    
    if (empty($url) || $url === '#') {
        return $url;
    }
    
    if (strpos($url, '://') === false) {
        return $url;
    }
    
    $parsedUrl = parse_url($url);
    $targetDomain = $parsedUrl['host'] ?? '';
    $currentHost = $_SERVER['HTTP_HOST'] ?? '';
    
    if ($targetDomain === $currentHost) {
        return $url;
    }
    
    foreach ($excludeDomainsList as $excludeDomain) {
        if ($targetDomain === $excludeDomain || str_ends_with($targetDomain, '.' . $excludeDomain)) {
            return $url;
        }
    }
    
    return '/go.php?url=' . urlencode($url);
}

try {
    $footer_social_links = $db->fetchAll("SELECT * FROM `lty-social_links` WHERE status = 1 ORDER BY sort_order ASC, id ASC");
    if (!$footer_social_links) {
        $footer_social_links = [];
    }
} catch (Exception $e) {
    $footer_social_links = [];
}

try {
    $footer_quick_links = $db->fetchAll("SELECT * FROM `lty-footer_links` WHERE status = 1 ORDER BY sort_order ASC, id ASC");
    if (!$footer_quick_links) {
        $footer_quick_links = [];
    }
} catch (Exception $e) {
    $footer_quick_links = [];
}

if (!isset($site_name)) {
    $site_name = Settings::get('site_name', '团队官网');
}
?>
    <footer>
        <div class="footer-glow footer-glow-1"></div>
        <div class="footer-glow footer-glow-2"></div>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><?php echo htmlspecialchars($site_name); ?></h3>
                    <p><?php echo htmlspecialchars(Settings::get('site_description', '专业的团队官方网站')); ?></p>
                    <?php if (!empty($footer_social_links)): ?>
                    <div class="footer-social-links">
                        <?php foreach ($footer_social_links as $link): ?>
                            <?php 
                            $link_url = trim($link['url'] ?? '');
                            if (empty($link_url) || $link_url === '#') continue;
                            $processed_url = processExternalLink($link_url, $external_link_redirect, $excludeDomainsList);
                            ?>
                        <a href="<?php echo htmlspecialchars($processed_url); ?>" target="_blank" rel="noopener noreferrer" class="social-link-item" title="<?php echo htmlspecialchars($link['platform']); ?>">
                            <?php if ($link['icon_type'] === 'custom' && !empty($link['svg_code'])): ?>
                                <?php echo $link['svg_code']; ?>
                            <?php elseif ($link['icon_type'] === 'custom' && !empty($link['icon_url'])): ?>
                                <img src="<?php echo htmlspecialchars($link['icon_url']); ?>" alt="<?php echo htmlspecialchars($link['platform']); ?>">
                            <?php else: ?>
                                <?php echo getFooterSocialIcon($link['platform']); ?>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="footer-section">
                    <h3>快速链接</h3>
                    <ul class="footer-links">
                        <?php if (!empty($footer_quick_links)): ?>
                            <?php foreach ($footer_quick_links as $link): ?>
                                <li><a href="<?php echo htmlspecialchars($link['url']); ?>" target="<?php echo htmlspecialchars($link['target']); ?>"><?php echo htmlspecialchars($link['title']); ?></a></li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li><a href="/">首页</a></li>
                            <li><a href="/about">关于我们</a></li>
                            <li><a href="/team">团队成员</a></li>
                            <li><a href="/contact">联系我们</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="footer-section footer-contact">
                    <h3>联系方式</h3>
                    <div class="footer-contact-item">
                        <svg viewBox="0 0 1024 1024" class="footer-contact-icon"><path d="M928 160H96c-17.7 0-32 14.3-32 32v640c0 17.7 14.3 32 32 32h832c17.7 0 32-14.3 32-32V192c0-17.7-14.3-32-32-32z m-40 110.8V792H136V270.8l-27.6-21.5 39.4-50.6 50.4 39.3H826.2l50.4-39.3 39.4 50.6z" fill="currentColor"/><path d="M826.2 238H197.8L136 270.8V792h752V270.8z" fill="currentColor" opacity="0.3"/><path d="M512 534.8L826.2 270.8H197.8z" fill="currentColor"/></svg>
                        <span><?php echo htmlspecialchars(Settings::get('contact_email', 'contact@lintuyue.com')); ?></span>
                    </div>
                    <div class="footer-contact-item">
                        <svg viewBox="0 0 1024 1024" class="footer-contact-icon"><path d="M737.2 614.4c-31.6-31.6-68.8-56.8-110.4-75.2-10.4-4.8-21.6-8.8-32.8-12.4 46.4-36.8 76-93.2 76-156.8 0-110.4-89.6-200-200-200s-200 89.6-200 200c0 63.6 29.6 120 76 156.8-11.2 3.6-22.4 7.6-32.8 12.4-41.6 18.4-78.8 43.6-110.4 75.2-63.2 63.2-98 147.2-98 236.8h60c0-149.2 121.2-270.4 270.4-270.4h69.2c149.2 0 270.4 121.2 270.4 270.4h60c0-89.6-34.8-173.6-98-236.8zM512 230.4c77.2 0 140 62.8 140 140s-62.8 140-140 140-140-62.8-140-140 62.8-140 140-140z" fill="currentColor"/></svg>
                        <span><?php echo htmlspecialchars(Settings::get('contact_phone', '400-123-4567')); ?></span>
                    </div>
                    <div class="footer-contact-item footer-contact-address">
                        <svg viewBox="0 0 1024 1024" class="footer-contact-icon"><path d="M854.6 289.1a362.49 362.49 0 0 0-79.9-115.7 370.83 370.83 0 0 0-118.2-77.8C610.7 76.6 562.1 67 512 67c-50.1 0-98.7 9.6-144.5 28.5-44.3 18.3-84 44.5-118.2 77.8A362.49 362.49 0 0 0 169.4 289.1c-19.5 45-29.4 92.8-29.4 142 0 70.6 16.9 140.9 50.1 209.1 26.7 54.8 64 107.6 111 157 55.4 58.1 111.4 101.2 146.6 127.3 12.8 9.5 23.6 17.4 32.3 23.8 8.7-6.4 19.5-14.3 32.3-23.8 35.2-26.1 91.2-69.2 146.6-127.3 47-49.4 84.3-102.2 111-157 33.3-68.2 50.1-138.5 50.1-209.1 0.1-49.2-9.8-97-29.3-142zM512 615c-97.2 0-176-78.8-176-176s78.8-176 176-176 176 78.8 176 176-78.8 176-176 176z" fill="currentColor"/><path d="M512 319c-66.3 0-120 53.7-120 120s53.7 120 120 120 120-53.7 120-120-53.7-120-120-120z m0 180c-33.1 0-60-26.9-60-60s26.9-60 60-60 60 26.9 60 60-26.9 60-60 60z" fill="currentColor"/></svg>
                        <span><?php echo htmlspecialchars(Settings::get('contact_address', '北京市朝阳区XX路XX号')); ?></span>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <div class="footer-bottom-content">
                    <p><?php echo htmlspecialchars(Settings::get('copyright', '2024 版权所有')); ?></p>
                    <?php $custom_footer_html = Settings::get('custom_footer_html', ''); ?>
                    <?php if ($custom_footer_html): ?>
                    <div class="footer-custom-html"><?php echo $custom_footer_html; ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </footer>

    <button class="back-to-top" id="backToTop" title="返回顶部">
        <svg viewBox="0 0 1024 1024"><path d="M512 378.24l-289.92 289.92-45.248-45.248L512 287.744l335.168 335.168-45.248 45.248z" fill="currentColor"/></svg>
    </button>

    <div class="menu-overlay" id="menuOverlay" onclick="toggleMenu()"></div>

    <script>
    function toggleMenu() {
        const navLinks = document.getElementById('navLinks');
        const menuBtn = document.querySelector('.mobile-menu-btn');
        const menuOverlay = document.getElementById('menuOverlay');
        
        navLinks.classList.toggle('active');
        menuBtn.classList.toggle('active');
        menuOverlay.classList.toggle('active');
        
        document.body.style.overflow = navLinks.classList.contains('active') ? 'hidden' : '';
    }

    document.querySelectorAll('.nav-links a').forEach(link => {
        link.addEventListener('click', () => {
            const navLinks = document.getElementById('navLinks');
            const menuBtn = document.querySelector('.mobile-menu-btn');
            const menuOverlay = document.getElementById('menuOverlay');
            
            navLinks.classList.remove('active');
            menuBtn.classList.remove('active');
            menuOverlay.classList.remove('active');
            document.body.style.overflow = '';
        });
    });

    window.addEventListener('scroll', function() {
        const header = document.querySelector('header');
        const backToTop = document.getElementById('backToTop');
        
        if (window.scrollY > 100) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
        
        if (backToTop) {
            if (window.scrollY > 300) {
                backToTop.classList.add('visible');
            } else {
                backToTop.classList.remove('visible');
            }
        }
    });

    document.getElementById('backToTop')?.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });

    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, observerOptions);

    document.querySelectorAll('.fade-in, .scale-in, .slide-in-left, .slide-in-right, .info-card, .info-item, .info-card-item, .quick-link-item').forEach(el => {
        observer.observe(el);
    });

    <?php if ($external_link_redirect === '1' && $external_link_article === '1'): ?>
    (function() {
        var currentHost = window.location.host;
        var excludeDomains = <?php echo json_encode($excludeDomainsList); ?>;
        
        document.querySelectorAll('a[href]').forEach(function(link) {
            var href = link.getAttribute('href');
            if (!href || href.indexOf('://') === -1) return;
            
            try {
                var url = new URL(href);
                var targetDomain = url.host;
                
                if (targetDomain === currentHost) return;
                
                var isExcluded = excludeDomains.some(function(domain) {
                    return targetDomain === domain || targetDomain.endsWith('.' + domain);
                });
                
                if (!isExcluded) {
                    link.setAttribute('href', '/go.php?url=' + encodeURIComponent(href));
                }
            } catch(e) {}
        });
    })();
    <?php endif; ?>
    </script>
    <?php
    $custom_js = Settings::get('custom_js', '');
    $analytics_html = Settings::get('analytics_html', '');
    ?>
    <?php if ($custom_js): ?>
    <script><?php echo $custom_js; ?></script>
    <?php endif; ?>
    <?php if ($analytics_html): ?>
    <?php echo $analytics_html; ?>
    <?php endif; ?>
</body>
</html>

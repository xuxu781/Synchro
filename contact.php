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

$site_name = Settings::get('site_name', '团队官网');
$page_title = '联系我们 - ' . $site_name;
$contact_title = Settings::get('contact_title', '联系我们');
$contact_subtitle = Settings::get('contact_subtitle', '期待与您的每一次交流，让我们一起创造更多可能');
$page_description = $contact_subtitle . ' - ' . $site_name;
$page_keywords = '联系我们,在线留言,商务合作,' . $site_name;
$current_page = 'contact';
$extra_css = [];

require_once __DIR__ . '/includes/header.php';

function getDefaultSocialIcon($platform) {
    $icons = [
        '微信公众号' => '<svg class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" width="22" height="22"><path d="M664.250054 368.541681c10.015098 0 19.892049 0.732687 29.627708 2.038659-26.647917-122.810739-159.358451-214.077703-310.826188-214.077703-169.353083 0-308.085774 114.232694-308.085774 259.274068 0 83.70765 46.165436 152.460344 123.281791 205.78483l-30.838712 91.730191 107.688658-53.455469c38.560521 7.53665 69.466819 15.308511 107.954242 15.308511 9.661473 0 19.230033-0.467618 28.752825-1.216492-6.025089-20.36584-9.278883-41.723264-9.278883-63.862492C402.525721 476.487004 517.108796 368.541681 664.250054 368.541681zM501.760858 297.916c23.200395 0 38.557309 15.120868 38.557309 38.060875 0 22.811407-15.356914 38.232825-38.557309 38.232825-23.107276 0-46.339394-15.421418-46.339394-38.232825C455.421464 313.036868 478.653582 297.916 501.760858 297.916zM284.536595 374.2097c-23.107276 0-46.456607-15.421418-46.456607-38.232825 0-22.940007 23.349331-38.060875 46.456607-38.060875 23.076489 0 38.464059 15.120868 38.464059 38.060875C323.000654 358.788282 307.613084 374.2097 284.536595 374.2097z" fill="currentColor"/><path d="M945.448458 606.151333c0-121.888048-123.386635-221.236753-262.224529-221.236753-146.836891 0-262.376678 99.348705-262.376678 221.236753 0 122.017528 115.539787 221.236753 262.376678 221.236753 30.662556 0 61.619693-7.617744 92.118724-15.30442l84.513836 45.864058-23.226723-76.170797C899.442573 735.776599 945.448458 674.903947 945.448458 606.151333zM621.53249 570.544601c-15.356914 0-30.838712-15.046258-30.838712-30.563732 0-15.335378 15.481798-30.689082 30.838712-30.689082 23.200395 0 38.464059 15.353704 38.464059 30.689082C659.996549 555.498343 644.732885 570.544601 621.53249 570.544601zM744.699473 570.544601c-15.231008 0-30.682386-15.046258-30.682386-30.563732 0-15.335378 15.451378-30.689082 30.682386-30.689082 23.076489 0 38.557309 15.353704 38.557309 30.689082C783.256782 555.498343 767.775962 570.544601 744.699473 570.544601z" fill="currentColor"/></svg>',
        '微博' => '<svg class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" width="22" height="22"><path d="M877.7472 389.0432c-17.2032-5.9392-28.8768-9.8304-19.968-35.4816 19.3536-55.808 21.3504-103.8848 0.1024-138.24-39.8848-64.512-148.992-61.0304-273.7152-1.7408 0 0-39.2192 17.1008-29.184-13.8752 19.2-61.3376 16.384-112.64-13.568-142.1824-67.9424-67.0784-248.6272 2.4064-403.6096 155.2384C65.3312 318.0032 12.8 418.8672 12.8 505.6c0 166.0416 214.3232 266.9056 423.936 266.9056 274.7392 0 457.472-158.3104 457.472-284.0576 0-75.9808-64.7168-119.0912-116.4544-99.4048zM437.3504 707.6352c-161.792 15.8208-301.3632-56.7808-311.6544-162.2016-10.2912-105.3696 112.2304-203.5712 274.016-219.4432 161.8432-15.8208 301.3632 56.7808 311.6544 162.1504C721.6576 593.5104 599.1424 691.7632 437.3504 707.6352z" fill="currentColor"/><path d="M411.1872 381.5936c-78.4896 16.384-132.6592 76.8-121.0368 134.8608 11.5712 58.112 84.0704 92.8256 162.5088 76.4416 78.5408-16.384 132.6592-76.8 121.088-134.912C562.1248 399.9744 489.6768 365.2096 411.1872 381.5936zM369.7152 514.8672c-24.1152 5.0176-46.8992-7.2704-50.8928-27.4944-3.9424-20.1728 12.2368-40.6016 36.352-45.6704 24.1664-5.0176 46.9504 7.2704 50.8928 27.4944C410.0672 489.3696 393.9328 509.7984 369.7152 514.8672zM424.192 469.6064c-9.0112 1.8944-17.5616-3.1744-19.1488-11.264-1.536-8.0384 4.5056-16.1792 13.5168-18.0224 9.0624-1.8944 17.6128 3.1232 19.2 11.2128C439.3472 459.6224 433.2544 467.712 424.192 469.6064z" fill="currentColor"/><path d="M948.1728 322.56c-25.1392-27.1872-62.3104-34.1504-95.8464-21.7088-9.5744 3.4816-14.4896 14.0288-10.9568 23.6032 3.4816 9.5744 14.0288 14.4896 23.6032 10.9568 19.2-6.912 40.192-3.0208 54.4768 12.3904 14.2336 15.36 17.408 36.9152 9.0112 56.1152-4.0448 9.2672 0.3072 20.0192 9.5744 24.064 9.2672 4.0448 20.0192-0.3072 24.064-9.5744C978.5856 384.9216 973.312 349.7472 948.1728 322.56z" fill="currentColor"/></svg>',
        'LinkedIn' => '<svg class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" width="22" height="22"><path d="M880 112H144c-17.7 0-32 14.3-32 32v736c0 17.7 14.3 32 32 32h736c17.7 0 32-14.3 32-32V144c0-17.7-14.3-32-32-32zM349.3 793.7H230.6V411.9h118.7v381.8z m-59.3-434c-38 0-68.8-30.8-68.8-68.8s30.8-68.8 68.8-68.8c38 0 68.8 30.8 68.8 68.8s-30.8 68.8-68.8 68.8z m447.7 434H619.2V533.8c0-45.4-16.4-76.5-57.1-76.5-31.1 0-49.7 21-57.9 41.3-3 7.3-3.7 17.3-3.7 27.4v267.7H381.8s1.5-388.1 0-428.3h118.7v60.7c15.8-24.3 43.9-58.9 106.8-58.9 78 0 136.4 51 136.4 160.6v266z" fill="currentColor"/></svg>',
        'GitHub' => '<svg class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" width="22" height="22"><path d="M512 42.666667C232.533333 42.666667 6.4 268.8 6.4 548.266667c0 222.293333 144.213333 410.88 344.32 477.013333 25.173333 4.693333 34.346667-10.88 34.346667-24.213333 0-11.946667-0.426667-51.626667-0.64-93.653334-140.117333 30.506667-169.6-59.52-169.6-59.52-22.869333-58.133333-55.893333-73.6-55.893334-73.6-45.653333-31.146667 3.456-30.506667 3.456-30.506666 50.517333 3.584 77.141333 51.84 77.141334 51.84 44.885333 76.970667 117.76 54.698667 146.474666 41.813333 4.565333-32.512 17.578667-54.741333 31.957334-67.306667-111.829333-12.8-229.461333-55.893333-229.461334-248.746666 0-54.954667 19.626667-99.84 51.84-135.125334-5.162667-12.757333-22.442667-64.085333 4.906667-133.653333 0 0 42.325333-13.525333 138.624 51.626667 40.192-11.178667 83.285333-16.725333 126.122667-16.938667 42.794667 0.213333 85.930667 5.76 126.165333 16.938667 96.213333-65.152 138.453333-51.626667 138.453333-51.626667 27.434667 69.568 10.154667 120.896 4.992 133.653333 32.298667 35.285333 51.797333 80.170667 51.797334 135.125334 0 193.365333-117.845333 235.776-230.058667 248.277333 18.090667 15.658667 34.176 46.464 34.176 93.653333 0 67.669333-0.597333 122.112-0.597333 138.88 0 13.44 9.088 29.098667 34.602666 24.149334C873.344 958.976 1017.6 770.474667 1017.6 548.266667 1017.6 268.8 791.466667 42.666667 512 42.666667z" fill="currentColor"/></svg>',
    ];
    
    foreach ($icons as $name => $icon) {
        if (stripos($platform, $name) !== false) {
            return $icon;
        }
    }
    
    return '<svg class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" width="22" height="22"><path d="M512 85.333333c-235.733333 0-426.666667 190.933333-426.666667 426.666667s190.933333 426.666667 426.666667 426.666667 426.666667-190.933333 426.666667-426.666667S747.733333 85.333333 512 85.333333z m0 768c-188.8 0-341.333333-152.533333-341.333333-341.333333s152.533333-341.333333 341.333333-341.333333 341.333333 152.533333 341.333333 341.333333-152.533333 341.333333-341.333333 341.333333z" fill="currentColor"/><path d="M682.666667 512h-128V341.333333c0-23.466667-19.2-42.666667-42.666667-42.666666s-42.666667 19.2-42.666667 42.666666v213.333334c0 23.466667 19.2 42.666667 42.666667 42.666666h170.666667c23.466667 0 42.666667-19.2 42.666666-42.666666s-19.2-42.666667-42.666666-42.666667z" fill="currentColor"/></svg>';
}

$contact_email = Settings::get('contact_email', 'contact@lintuyue.com');
$contact_phone = Settings::get('contact_phone', '400-123-4567');
$contact_address = Settings::get('contact_address', '北京市朝阳区XX路XX号');
$business_email = Settings::get('business_email', 'business@lintuyue.com');
$hr_email = Settings::get('hr_email', 'hr@lintuyue.com');
$media_email = Settings::get('media_email', 'media@lintuyue.com');
$working_hours = Settings::get('working_hours', '周一至周五 09:00-18:00');
$qq_number = Settings::get('qq_number', '');
$contact_intro = Settings::get('contact_intro', '期待与您的每一次交流，让我们一起创造更多可能');
$contact_title = Settings::get('contact_title', '联系我们');
$contact_subtitle = Settings::get('contact_subtitle', '期待与您的每一次交流，让我们一起创造更多可能');

$stat_response = Settings::get('stat_response', '24h');
$stat_satisfaction = Settings::get('stat_satisfaction', '98%');
$stat_cases = Settings::get('stat_cases', '500+');

$success_message = '';
$error_message = '';

$db = Database::getInstance();
try {
    $social_links = $db->fetchAll("SELECT * FROM `lty-social_links` WHERE status = 1 ORDER BY sort_order ASC, id ASC");
    if (!$social_links) {
        $social_links = [];
    }
} catch (Exception $e) {
    $social_links = [];
}

try {
    $contact_types = $db->fetchAll("SELECT * FROM `lty-contact_types` WHERE status = 1 ORDER BY sort_order ASC, id ASC");
    if (!$contact_types) {
        $contact_types = [];
    }
} catch (Exception $e) {
    $contact_types = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'contact') {
    $type_id = isset($_POST['type_id']) && !empty($_POST['type_id']) ? (int)$_POST['type_id'] : null;
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($type_id)) {
        $error_message = '请选择咨询类型';
    } elseif (empty($name) || empty($email) || empty($message)) {
        $error_message = '请填写必填项';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = '请输入有效的邮箱地址';
    } else {
        try {
            $db = Database::getInstance();
            $db->insert('lty-contact_messages', [
                'type_id' => $type_id,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'message' => $message,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $success_message = '感谢您的留言，我们会尽快与您联系！';
        } catch (Exception $e) {
            $error_message = '提交失败，请稍后重试';
        }
    }
}
?>

<section class="contact-page-hero">
    <div class="hero-bg-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>
    <div class="container">
        <div class="hero-content-area fade-in">
            <h1><?php echo htmlspecialchars($contact_title); ?></h1>
            <p class="hero-desc"><?php echo htmlspecialchars($contact_subtitle); ?></p>
            <div class="hero-stats-row">
                <div class="stat-item">
                    <span class="stat-num"><?php echo htmlspecialchars($stat_response); ?></span>
                    <span class="stat-label">快速响应</span>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item">
                    <span class="stat-num"><?php echo htmlspecialchars($stat_satisfaction); ?></span>
                    <span class="stat-label">客户满意</span>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item">
                    <span class="stat-num"><?php echo htmlspecialchars($stat_cases); ?></span>
                    <span class="stat-label">成功案例</span>
                </div>
            </div>
        </div>
    </div>
    <div class="hero-wave">
        <svg viewBox="0 0 1440 100" preserveAspectRatio="none">
            <path d="M0,50 C360,100 720,0 1080,50 C1260,75 1350,60 1440,50 L1440,100 L0,100 Z" fill="#ffffff"/>
        </svg>
    </div>
</section>

<section class="contact-page-info">
    <div class="container">
        <div class="section-title-center fade-in">
            <span class="section-tag">联系方式</span>
            <h2>多种方式 与我们取得联系</h2>
        </div>
        <div class="info-cards-grid">
            <div class="info-card-item scale-in">
                <div class="card-glow"></div>
                <div class="card-icon-box">
                    <svg class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="24"><path d="M874.666667 375.466667L548.266667 622.933333c-20.266667 14.933333-46.933333 14.933333-66.133334 0L155.733333 375.466667C138.666667 362.666667 138.666667 337.066667 155.733333 324.266667c17.066667-12.8 40.533333-12.8 57.6 0L512 554.666667l298.666667-230.4c17.066667-12.8 40.533333-12.8 57.6 0 17.066667 12.8 17.066667 38.4 6.4 51.2z" fill="currentColor"/><path d="M853.333333 213.333333H170.666667c-46.933333 0-85.333333 38.4-85.333334 85.333334v426.666666c0 46.933333 38.4 85.333333 85.333334 85.333334h682.666666c46.933333 0 85.333333-38.4 85.333334-85.333334V298.666667c0-46.933333-38.4-85.333333-85.333334-85.333334z m0 512H170.666667V298.666667h682.666666v426.666666z" fill="currentColor"/></svg>
                </div>
                <h3>邮箱联系</h3>
                <p>发送邮件给我们，24小时内回复</p>
                <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>" class="card-link-btn">
                    <span><?php echo htmlspecialchars($contact_email); ?></span>
                </a>
            </div>
            
            <div class="info-card-item scale-in">
                <div class="card-glow"></div>
                <div class="card-icon-box">
                    <svg class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="24"><path d="M746.666667 640c-14.933333 0-32 6.4-44.8 19.2l-64 64c-91.733333-46.933333-168.533333-123.733333-215.466667-215.466667l64-64c12.8-12.8 19.2-29.866667 19.2-44.8 0-14.933333-6.4-32-19.2-44.8l-149.333333-149.333333c-12.8-12.8-29.866667-19.2-44.8-19.2-14.933333 0-32 6.4-44.8 19.2l-85.333334 85.333333c-46.933333 46.933333-46.933333 123.733333 0 170.666667l384 384c46.933333 46.933333 123.733333 46.933333 170.666667 0l85.333333-85.333333c12.8-12.8 19.2-29.866667 19.2-44.8 0-14.933333-6.4-32-19.2-44.8l-149.333333-149.333334c-12.8-12.8-29.866667-19.2-44.8-19.2z" fill="currentColor"/></svg>
                </div>
                <h3>电话咨询</h3>
                <p>工作日 9:00 - 18:00 随时为您服务</p>
                <a href="tel:<?php echo htmlspecialchars($contact_phone); ?>" class="card-link-btn">
                    <span><?php echo htmlspecialchars($contact_phone); ?></span>
                </a>
            </div>
            
            <div class="info-card-item scale-in">
                <div class="card-glow"></div>
                <div class="card-icon-box">
                    <svg class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="24"><path d="M512 85.333333c-164.266667 0-298.666667 134.4-298.666667 298.666667 0 213.333333 298.666667 554.666667 298.666667 554.666667s298.666667-341.333333 298.666667-554.666667c0-164.266667-134.4-298.666667-298.666667-298.666667z m0 426.666667c-70.4 0-128-57.6-128-128s57.6-128 128-128 128 57.6 128 128-57.6 128-128 128z" fill="currentColor"/></svg>
                </div>
                <h3>公司地址</h3>
                <p>欢迎来访，提前预约可安排接待</p>
                <div class="card-text-info">
                    <span><?php echo htmlspecialchars($contact_address); ?></span>
                </div>
            </div>
            
            <div class="info-card-item scale-in">
                <div class="card-glow"></div>
                <div class="card-icon-box">
                    <svg class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="24"><path d="M512 85.333333c-235.733333 0-426.666667 190.933333-426.666667 426.666667s190.933333 426.666667 426.666667 426.666667 426.666667-190.933333 426.666667-426.666667S747.733333 85.333333 512 85.333333z m0 768c-188.8 0-341.333333-152.533333-341.333333-341.333333s152.533333-341.333333 341.333333-341.333333 341.333333 152.533333 341.333333 341.333333-152.533333 341.333333-341.333333 341.333333z" fill="currentColor"/><path d="M682.666667 512h-128V341.333333c0-23.466667-19.2-42.666667-42.666667-42.666666s-42.666667 19.2-42.666667 42.666666v213.333334c0 23.466667 19.2 42.666667 42.666667 42.666666h170.666667c23.466667 0 42.666667-19.2 42.666666-42.666666s-19.2-42.666667-42.666666-42.666667z" fill="currentColor"/></svg>
                </div>
                <h3>工作时间</h3>
                <p>我们的服务时间安排</p>
                <div class="card-text-info">
                    <span><?php echo htmlspecialchars($working_hours); ?></span>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="contact-page-form">
    <div class="container">
        <div class="form-layout-wrap">
            <div class="form-left-panel slide-in-left">
                <div class="left-panel-content">
                    <span class="section-tag">在线留言</span>
                    <h2>给我们写封信</h2>
                    <p><?php echo htmlspecialchars($contact_intro); ?></p>
                    
                    <div class="quick-contacts">
                        <div class="quick-contact-row">
                            <div class="quick-icon"><svg class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="24"><path d="M853.333333 213.333333H170.666667c-46.933333 0-85.333333 38.4-85.333334 85.333334v426.666666c0 46.933333 38.4 85.333333 85.333334 85.333334h682.666666c46.933333 0 85.333333-38.4 85.333334-85.333334V298.666667c0-46.933333-38.4-85.333333-85.333334-85.333334z m0 512H170.666667V341.333333h682.666666v384z" fill="currentColor"/></svg></div>
                            <div class="quick-text">
                                <h4>商务合作</h4>
                                <p><?php echo htmlspecialchars($business_email); ?></p>
                            </div>
                        </div>
                        <div class="quick-contact-row">
                            <div class="quick-icon"><svg class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="24"><path d="M512 512c-117.333333 0-213.333333-96-213.333333-213.333333s96-213.333333 213.333333-213.333334 213.333333 96 213.333333 213.333334-96 213.333333-213.333333 213.333333z m0-341.333333c-70.4 0-128 57.6-128 128s57.6 128 128 128 128-57.6 128-128-57.6-128-128-128z" fill="currentColor"/><path d="M896 896H128c-23.466667 0-42.666667-19.2-42.666667-42.666667v-85.333333c0-117.333333 96-213.333333 213.333334-213.333333h426.666666c117.333333 0 213.333333 96 213.333334 213.333333v85.333333c0 23.466667-19.2 42.666667-42.666667 42.666667z m-725.333333-85.333333h682.666666v-42.666667c0-70.4-57.6-128-128-128H298.666667c-70.4 0-128 57.6-128 128v42.666667z" fill="currentColor"/></svg></div>
                            <div class="quick-text">
                                <h4>人才招聘</h4>
                                <p><?php echo htmlspecialchars($hr_email); ?></p>
                            </div>
                        </div>
                        <div class="quick-contact-row">
                            <div class="quick-icon"><svg class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="24"><path d="M853.333333 469.333333h-64v-128c0-23.466667-19.2-42.666667-42.666666-42.666666s-42.666667 19.2-42.666667 42.666666v128h-170.666667v-128c0-23.466667-19.2-42.666667-42.666666-42.666666s-42.666667 19.2-42.666667 42.666666v128h-170.666667v-128c0-23.466667-19.2-42.666667-42.666666-42.666666s-42.666667 19.2-42.666667 42.666666v128H170.666667c-46.933333 0-85.333333 38.4-85.333334 85.333334v256c0 46.933333 38.4 85.333333 85.333334 85.333333h682.666666c46.933333 0 85.333333-38.4 85.333334-85.333333v-256c0-46.933333-38.4-85.333333-85.333334-85.333334z m0 341.333334H170.666667v-256h682.666666v256z" fill="currentColor"/></svg></div>
                            <div class="quick-text">
                                <h4>媒体合作</h4>
                                <p><?php echo htmlspecialchars($media_email); ?></p>
                            </div>
                        </div>
                        <?php if (!empty($qq_number)): ?>
                        <div class="quick-contact-row">
                            <div class="quick-icon"><svg class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="24"><path d="M512 128c-212.1 0-384 171.9-384 384s171.9 384 384 384 384-171.9 384-384-171.9-384-384-384z m0 686c-166.6 0-302-135.4-302-302s135.4-302 302-302 302 135.4 302 302-135.4 302-302 302z" fill="currentColor"/><path d="M704 544c0-106-86-192-192-192s-192 86-192 192c0 71.4 39 133.6 96.8 166.8-6.4 18.8-24.8 32.2-46.8 32.2-28 0-50.6-22.6-50.6-50.6 0-14.4 6-27.4 15.6-36.6-30.4-26.4-49.6-65-49.6-108 0-79.4 64.4-143.8 143.8-143.8s143.8 64.4 143.8 143.8c0 43-19.2 81.6-49.6 108 9.6 9.2 15.6 22.2 15.6 36.6 0 28-22.6 50.6-50.6 50.6-22 0-40.4-13.4-46.8-32.2C665 677.6 704 615.4 704 544z" fill="currentColor"/></svg></div>
                            <div class="quick-text">
                                <h4>QQ咨询</h4>
                                <p><?php echo htmlspecialchars($qq_number); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="social-connect">
                        <span class="social-label">关注我们</span>
                        <div class="social-icons-row">
                            <?php if (!empty($social_links)): ?>
                                <?php foreach ($social_links as $link): ?>
                                    <?php 
                                    $link_url = trim($link['url'] ?? '');
                                    if (empty($link_url) || $link_url === '#') continue;
                                    ?>
                                    <a href="<?php echo htmlspecialchars($link_url); ?>" class="social-icon-btn" title="<?php echo htmlspecialchars($link['platform']); ?>" target="_blank" rel="noopener noreferrer">
                                        <?php if ($link['icon_type'] === 'custom' && !empty($link['svg_code'])): ?>
                                            <?php echo $link['svg_code']; ?>
                                        <?php elseif ($link['icon_type'] === 'custom' && !empty($link['icon_url'])): ?>
                                            <img src="<?php echo htmlspecialchars($link['icon_url']); ?>" alt="<?php echo htmlspecialchars($link['platform']); ?>" style="width: 22px; height: 22px; object-fit: contain;">
                                        <?php else: ?>
                                            <?php echo getDefaultSocialIcon($link['platform']); ?>
                                        <?php endif; ?>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-right-panel slide-in-right">
                <div class="form-card-wrap">
                            <div id="formMessage" class="form-message-box" style="display: none;"></div>
                            <form id="contactForm" method="POST">
                                <input type="hidden" name="action" value="contact">
                            
                            <div class="form-field-wrap">
                                <label for="type_id">
                                    <svg class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" width="16" height="16"><path d="M128 213.333333h768c23.466667 0 42.666667 19.2 42.666667 42.666667v512c0 23.466667-19.2 42.666667-42.666667 42.666667H128c-23.466667 0-42.666667-19.2-42.666667-42.666667V256c0-23.466667 19.2-42.666667 42.666667-42.666667z m42.666667 85.333334v426.666666h682.666666V298.666667H170.666667z" fill="currentColor"/></svg>
                                    咨询类型 <span class="required-mark">*</span>
                                </label>
                                <div class="type-selector">
                                    <select id="type_id" name="type_id" class="type-select" required>
                                        <option value="">请选择咨询类型</option>
                                        <?php foreach ($contact_types as $type): ?>
                                            <option value="<?php echo $type['id']; ?>">
                                                <?php echo htmlspecialchars($type['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row-grid">
                                <div class="form-field-wrap">
                                    <label for="name">
                                        <svg class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" width="16" height="16"><path d="M512 512c-117.333333 0-213.333333-96-213.333333-213.333333s96-213.333333 213.333333-213.333334 213.333333 96 213.333333 213.333334-96 213.333333-213.333333 213.333333z m0-341.333333c-70.4 0-128 57.6-128 128s57.6 128 128 128 128-57.6 128-128-57.6-128-128-128z" fill="currentColor"/><path d="M896 896H128c-23.466667 0-42.666667-19.2-42.666667-42.666667v-85.333333c0-117.333333 96-213.333333 213.333334-213.333333h426.666666c117.333333 0 213.333333 96 213.333334 213.333333v85.333333c0 23.466667-19.2 42.666667-42.666667 42.666667z m-725.333333-85.333333h682.666666v-42.666667c0-70.4-57.6-128-128-128H298.666667c-70.4 0-128 57.6-128 128v42.666667z" fill="currentColor"/></svg>
                                        姓名 <span class="required-mark">*</span>
                                    </label>
                                    <input type="text" id="name" name="name" required placeholder="请输入您的姓名">
                                </div>
                                <div class="form-field-wrap">
                                    <label for="email">
                                        <i class="ri-mail-line"></i>
                                        邮箱 <span class="required-mark">*</span>
                                    </label>
                                    <input type="email" id="email" name="email" required placeholder="请输入您的邮箱">
                                </div>
                            </div>
                            
                            <div class="form-field-wrap">
                                <label for="verification_code">
                                    <svg class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" width="16" height="16"><path d="M512 85.333333c-235.733333 0-426.666667 190.933333-426.666667 426.666667s190.933333 426.666667 426.666667 426.666667 426.666667-190.933333 426.666667-426.666667S747.733333 85.333333 512 85.333333z m0 768c-188.8 0-341.333333-152.533333-341.333333-341.333333s152.533333-341.333333 341.333333-341.333333 341.333333 152.533333 341.333333 341.333333-152.533333 341.333333-341.333333 341.333333z" fill="currentColor"/><path d="M512 298.666667c-70.4 0-128 57.6-128 128s57.6 128 128 128 128-57.6 128-128-57.6-128-128-128z m0 170.666666c-23.466667 0-42.666667-19.2-42.666667-42.666666s19.2-42.666667 42.666667-42.666667 42.666667 19.2 42.666667 42.666667-19.2 42.666667-42.666667 42.666666z" fill="currentColor"/></svg>
                                    邮箱验证码 <span class="required-mark">*</span>
                                </label>
                                <div class="verification-code-row">
                                    <input type="text" id="verification_code" name="verification_code" required placeholder="请输入6位验证码" maxlength="6" pattern="[0-9]{6}">
                                    <button type="button" id="sendCodeBtn" class="send-code-btn">发送验证码</button>
                                </div>
                            </div>
                            
                            <div class="form-field-wrap">
                                <label for="phone">
                                    <i class="ri-phone-line"></i>
                                    电话
                                </label>
                                <input type="tel" id="phone" name="phone" placeholder="请输入您的电话（选填）">
                            </div>
                            
                            <div class="form-field-wrap">
                                <label for="subject">
                                    <svg class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" width="16" height="16"><path d="M874.666667 362.666667H149.333333c-23.466667 0-42.666667 19.2-42.666666 42.666666v384c0 23.466667 19.2 42.666667 42.666666 42.666667h725.333334c23.466667 0 42.666667-19.2 42.666666-42.666667v-384c0-23.466667-19.2-42.666667-42.666666-42.666666z m0 426.666666H149.333333V405.333333h725.333334v384z" fill="currentColor"/><path d="M512 554.666667l341.333333-213.333334H170.666667z" fill="currentColor"/></svg>
                                    主题 <span class="required-mark">*</span>
                                </label>
                                <input type="text" id="subject" name="subject" required placeholder="请输入留言主题">
                            </div>
                            
                            <div class="form-field-wrap">
                                <label for="message">
                                    <svg class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" width="16" height="16"><path d="M853.333333 213.333333H170.666667c-46.933333 0-85.333333 38.4-85.333334 85.333334v426.666666c0 46.933333 38.4 85.333333 85.333334 85.333334h682.666666c46.933333 0 85.333333-38.4 85.333334-85.333333V298.666667c0-46.933333-38.4-85.333333-85.333334-85.333334z m0 512H170.666667V341.333333h682.666666v384z" fill="currentColor"/></svg>
                                    留言内容 <span class="required-mark">*</span>
                                </label>
                                <textarea id="message" name="message" required placeholder="请详细描述您的需求或问题..."></textarea>
                            </div>
                            
                            <button type="submit" class="submit-btn-main">
                                <span>发送留言</span>
                                <svg class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" width="16" height="16"><path d="M896 469.333333L512 85.333333 128 469.333333h213.333333v384h341.333334v-384h213.333333z" fill="currentColor" transform="rotate(90 512 512)"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        let countdown = 0;
        let countdownTimer = null;
        
        const sendCodeBtn = document.getElementById('sendCodeBtn');
        const emailInput = document.getElementById('email');
        const verificationCodeInput = document.getElementById('verification_code');
        
        function updateSendCodeBtn() {
            if (countdown > 0) {
                sendCodeBtn.textContent = countdown + '秒后重发';
                sendCodeBtn.disabled = true;
                sendCodeBtn.classList.add('disabled');
            } else {
                sendCodeBtn.textContent = '发送验证码';
                sendCodeBtn.disabled = false;
                sendCodeBtn.classList.remove('disabled');
            }
        }
        
        function startCountdown(seconds) {
            countdown = seconds;
            updateSendCodeBtn();
            countdownTimer = setInterval(function() {
                countdown--;
                if (countdown <= 0) {
                    clearInterval(countdownTimer);
                }
                updateSendCodeBtn();
            }, 1000);
        }
        
        sendCodeBtn.addEventListener('click', function() {
            const email = emailInput.value.trim();
            
            if (!email) {
                alert('请先输入邮箱地址');
                emailInput.focus();
                return;
            }
            
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                alert('请输入有效的邮箱地址');
                emailInput.focus();
                return;
            }
            
            if (countdown > 0) {
                return;
            }
            
            sendCodeBtn.textContent = '发送中...';
            sendCodeBtn.disabled = true;
            
            const formData = new FormData();
            formData.append('action', 'send_verification_code');
            formData.append('email', email);
            
            fetch('api/index.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Status:', response.status);
                return response.text().then(text => {
                    console.log('Response text:', text);
                    try {
                        return JSON.parse(text);
                    } catch(e) {
                        return { error: '服务器返回非JSON: ' + text.substring(0, 500) };
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    alert('验证码已发送到您的邮箱，请注意查收');
                    startCountdown(60);
                } else {
                    let errorMsg = data.error || '验证码发送失败';
                    if (data.debug) {
                        console.log('调试信息:', data.debug);
                    }
                    alert(errorMsg);
                    sendCodeBtn.textContent = '发送验证码';
                    sendCodeBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('发送验证码错误:', error);
                alert('发送失败：' + (error.message || '请稍后重试'));
                sendCodeBtn.textContent = '发送验证码';
                sendCodeBtn.disabled = false;
            });
        });
        
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const formData = new FormData(form);
            const formMessage = document.getElementById('formMessage');
            const submitBtn = form.querySelector('button[type="submit"]');
            
            const verificationCode = verificationCodeInput.value.trim();
            if (!verificationCode) {
                alert('请输入邮箱验证码');
                verificationCodeInput.focus();
                return;
            }
            
            if (!/^\d{6}$/.test(verificationCode)) {
                alert('请输入6位数字验证码');
                verificationCodeInput.focus();
                return;
            }
            
            const phoneInput = document.getElementById('phone');
            const phone = phoneInput.value.trim();
            if (phone && !/^1[3-9]\d{9}$/.test(phone)) {
                phoneInput.style.borderColor = '#ef4444';
                phoneInput.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.2)';
                setTimeout(function() {
                    phoneInput.style.borderColor = '';
                    phoneInput.style.boxShadow = '';
                }, 3000);
                formMessage.style.display = 'block';
                formMessage.style.background = '#ffebee';
                formMessage.style.color = '#c62828';
                formMessage.innerHTML = '<h3 style="margin-bottom: 10px;">手机号格式错误！</h3><p>请输入11位有效手机号</p>';
                formMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
                phoneInput.focus();
                return;
            }
            
            submitBtn.disabled = true;
            submitBtn.textContent = '提交中...';
            
            fetch('api/index.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                formMessage.style.display = 'block';
                if (data.success) {
                    formMessage.style.background = '#e8f5e9';
                    formMessage.style.color = '#2e7d32';
                    formMessage.innerHTML = '<h3 style="margin-bottom: 10px;">提交成功！</h3><p>感谢您的留言，我们会尽快与您联系。</p>';
                    form.reset();
                    formMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    if (countdownTimer) {
                        clearInterval(countdownTimer);
                    }
                    countdown = 0;
                    updateSendCodeBtn();
                    if (window.history.replaceState) {
                        window.history.replaceState(null, null, window.location.pathname);
                    }
                } else {
                    formMessage.style.background = '#ffebee';
                    formMessage.style.color = '#c62828';
                    formMessage.innerHTML = data.error || '提交失败，请稍后重试';
                }
            })
            .catch(error => {
                formMessage.style.display = 'block';
                formMessage.style.background = '#ffebee';
                formMessage.style.color = '#c62828';
                formMessage.innerHTML = '提交失败，请稍后重试';
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = '发送留言';
            });
        });
    </script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
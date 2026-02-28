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
$page_title = $site_name . ' - 首页';
$page_description = Settings::get('seo_description', '专业的团队官方网站');
$page_keywords = Settings::get('seo_keywords', '团队,官网,网站');
$current_page = 'home';
$extra_css = [];

require_once __DIR__ . '/includes/header.php';

$site_description = Settings::get('site_description', '专业的团队官方网站');

$db = Database::getInstance();

$cultures = $db->fetchAll("SELECT * FROM `lty-company_culture` WHERE status = 1 ORDER BY sort_order ASC, id ASC");
$behaviors = $db->fetchAll("SELECT * FROM `lty-company_behavior` WHERE status = 1 ORDER BY sort_order ASC, id ASC");
$timelines = $db->fetchAll("SELECT * FROM `lty-company_timeline` WHERE status = 1 ORDER BY sort_order ASC, id ASC");
$slides = $db->fetchAll("SELECT * FROM `lty-mission_slides` WHERE status = 1 ORDER BY sort_order ASC, id ASC");
$categories = $db->fetchAll("SELECT * FROM `lty-product_categories` WHERE status = 1 ORDER BY sort_order ASC, id ASC");

$culture_title = Settings::get('culture_title', '字节范');
$culture_subtitle = Settings::get('culture_subtitle', '字节范是我们共同认可的行为准则');
$behavior_title = Settings::get('behavior_title', '行为准则');
$behavior_subtitle = Settings::get('behavior_subtitle', '高道德标准和诚信文化发挥着关键作用');
$timeline_title = Settings::get('timeline_title', '大事记');
$timeline_subtitle = Settings::get('timeline_subtitle', '记录我们的成长历程');
$contact_title = Settings::get('contact_title', '联系我们');
$contact_subtitle = Settings::get('contact_subtitle', '期待与您的合作');
$products_title = Settings::get('products_title', '我们的产品');
$products_subtitle = Settings::get('products_subtitle', '以下展示的是工作室自主研发的程序项目');

$business_title = Settings::get('business_title', '业务领域');
$business_subtitle = Settings::get('business_subtitle', '我们专注的核心业务方向');
$business_areas = $db->fetchAll("SELECT * FROM `lty-business_areas` WHERE status = 1 ORDER BY sort_order ASC, id ASC");

$story_title = Settings::get('story_title', '公司简介');
$story_subtitle = Settings::get('story_subtitle', '记录我们的成长历程');
$story_content = Settings::get('story_content', '我们是一支充满激情和创造力的团队，致力于为客户提供最优质的服务。');
$story_content2 = Settings::get('story_content2', '自成立以来，我们始终坚持以技术创新为核心，以客户需求为导向，不断推出高质量的产品和服务。我们的团队由来自不同领域的专业人才组成，拥有丰富的行业经验和深厚的技术积累。');
$story_content3 = Settings::get('story_content3', '我们相信，只有不断创新和进步，才能在激烈的市场竞争中立于不败之地。因此，我们始终保持着对新技术、新理念的关注和学习，努力为客户提供最前沿的解决方案。');
$story_stats_years = Settings::get('story_stats_years', '10+');
$story_stats_cases = Settings::get('story_stats_cases', '500+');
$story_stats_team = Settings::get('story_stats_team', '100+');
$story_stats_satisfaction = Settings::get('story_stats_satisfaction', '98%');

$contact_email = Settings::get('contact_email', 'contact@lintuyue.com');
$contact_phone = Settings::get('contact_phone', '400-123-4567');
$contact_address = Settings::get('contact_address', '北京市朝阳区XX路XX号');
$business_email = Settings::get('business_email', 'business@lintuyue.com');
$hr_email = Settings::get('hr_email', 'hr@lintuyue.com');
$media_email = Settings::get('media_email', 'media@lintuyue.com');
$working_hours = Settings::get('working_hours', '周一至周五 09:00-18:00');
$qq_number = Settings::get('qq_number', '');
$contact_intro = Settings::get('contact_intro', '期待与您的每一次交流，让我们一起创造更多可能');
$stat_response = Settings::get('stat_response', '24h');
$stat_satisfaction = Settings::get('stat_satisfaction', '98%');
$stat_cases = Settings::get('stat_cases', '500+');

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
?>

    <section class="hero">
        <div class="hero-slider">
            <?php foreach ($slides as $index => $slide): ?>
                <div class="hero-slide <?php echo $index === 0 ? 'active' : ''; ?>" style="background-image: url('<?php echo htmlspecialchars($slide['image']); ?>');">
                    <div class="container hero-content">
                        <h1 class="mission-title"><?php echo htmlspecialchars($slide['title']); ?></h1>
                        <p class="mission-subtitle"><?php echo htmlspecialchars($slide['subtitle']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (count($slides) > 1): ?>
            <div class="slider-dots">
                <?php foreach ($slides as $index => $slide): ?>
                    <span class="dot <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>"></span>
                <?php endforeach; ?>
            </div>
            <button class="slider-arrow slider-arrow-prev">❮</button>
            <button class="slider-arrow slider-arrow-next">❯</button>
        <?php endif; ?>
        <div class="scroll-indicator">
            <div class="scroll-indicator-text">向下滑动</div>
            <div class="scroll-indicator-circle">
                <div class="scroll-indicator-arrow"></div>
            </div>
        </div>
    </section>

    <nav class="content-nav">
        <div class="container">
            <ul class="content-nav-list">
                <li><a href="#story" class="content-nav-link"><?php echo htmlspecialchars($story_title); ?></a></li>
                <li><a href="#culture" class="content-nav-link"><?php echo htmlspecialchars($culture_title); ?></a></li>
                <li><a href="#behavior" class="content-nav-link"><?php echo htmlspecialchars($behavior_title); ?></a></li>
                <li><a href="#products" class="content-nav-link"><?php echo htmlspecialchars($products_title); ?></a></li>
                <li><a href="#business" class="content-nav-link"><?php echo htmlspecialchars($business_title); ?></a></li>
                <li><a href="#timeline" class="content-nav-link"><?php echo htmlspecialchars($timeline_title); ?></a></li>
                <li><a href="#contact" class="content-nav-link"><?php echo htmlspecialchars($contact_title); ?></a></li>
            </ul>
        </div>
    </nav>

    <section class="story-section" id="story">
        <div class="container">
            <div class="story-layout">
                <div class="story-content fade-in">
                    <div class="section-title">
                        <h2><?php echo htmlspecialchars($story_title); ?></h2>
                        <p><?php echo htmlspecialchars($story_subtitle); ?></p>
                    </div>
                    <div class="story-text">
                        <p><?php echo nl2br(htmlspecialchars($story_content)); ?></p>
                        <p><?php echo nl2br(htmlspecialchars($story_content2)); ?></p>
                        <p><?php echo nl2br(htmlspecialchars($story_content3)); ?></p>
                    </div>
                </div>
                <div class="story-stats">
                    <div class="stat-item scale-in">
                        <div class="stat-number"><?php echo htmlspecialchars($story_stats_years); ?></div>
                        <div class="stat-label">年行业经验</div>
                    </div>
                    <div class="stat-item scale-in">
                        <div class="stat-number"><?php echo htmlspecialchars($story_stats_cases); ?></div>
                        <div class="stat-label">成功案例</div>
                    </div>
                    <div class="stat-item scale-in">
                        <div class="stat-number"><?php echo htmlspecialchars($story_stats_team); ?></div>
                        <div class="stat-label">专业团队</div>
                    </div>
                    <div class="stat-item scale-in">
                        <div class="stat-number"><?php echo htmlspecialchars($story_stats_satisfaction); ?></div>
                        <div class="stat-label">客户满意度</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="culture-section" id="culture">
        <div class="container">
            <div class="culture-layout">
                <div class="culture-left slide-in-left">
                    <div class="section-title">
                        <h2><?php echo htmlspecialchars($culture_title); ?></h2>
                        <p><?php echo htmlspecialchars($culture_subtitle); ?></p>
                    </div>
                    <div class="culture-description">
                        <p>我们的企业文化是团队凝聚力和创造力的源泉。通过共同的价值观和行为准则，我们打造了一个充满活力、积极向上的工作环境。</p>
                    </div>
                </div>
                <div class="culture-right slide-in-right">
                    <div class="culture-slider">
                        <?php foreach ($cultures as $index => $culture): ?>
                            <div class="culture-slide <?php echo $index === 0 ? 'active' : ''; ?>">
                                <div class="culture-slide-image">
                                    <?php if (!empty($culture['image'])): ?>
                                        <img src="<?php echo htmlspecialchars(get_image_url($culture['image'])); ?>" alt="<?php echo htmlspecialchars($culture['title']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="culture-slide-icon">💡</div>
                                    <?php endif; ?>
                                </div>
                                <div class="culture-slide-content">
                                    <h3><?php echo htmlspecialchars($culture['title']); ?></h3>
                                    <p><?php echo htmlspecialchars($culture['description']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($cultures)): ?>
                            <div class="culture-slide active">
                                <div class="culture-slide-image">
                                    <div class="culture-slide-icon">💡</div>
                                </div>
                                <div class="culture-slide-content">
                                    <h3>创新驱动</h3>
                                    <p>持续探索新技术、新方法，勇于突破传统思维，以创新引领行业发展</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if (count($cultures) > 1): ?>
                        <div class="culture-slider-controls">
                            <button class="culture-slider-btn culture-slider-prev">❮</button>
                            <div class="culture-slider-dots">
                                <?php foreach ($cultures as $index => $culture): ?>
                                    <span class="culture-dot <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>"></span>
                                <?php endforeach; ?>
                            </div>
                            <button class="culture-slider-btn culture-slider-next">❯</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="features" id="behavior">
        <div class="container">
            <div class="section-title fade-in">
                <h2><?php echo htmlspecialchars($behavior_title); ?></h2>
                <p><?php echo htmlspecialchars($behavior_subtitle); ?></p>
            </div>
            <div class="features-grid">
                <?php foreach ($behaviors as $behavior): ?>
                    <div class="feature-card scale-in">
                        <div class="feature-icon">
                            <?php if (!empty($behavior['icon_svg'])): ?>
                                <?php echo $behavior['icon_svg']; ?>
                            <?php else: ?>
                                <svg viewBox="0 0 1024 1024"><path d="M512 64C264.6 64 64 264.6 64 512s200.6 448 448 448 448-200.6 448-448S759.4 64 512 64zm0 820c-205.4 0-372-166.6-372-372s166.6-372 372-372 372 166.6 372 372-166.6 372-372 372z"/></svg>
                            <?php endif; ?>
                        </div>
                        <h3><?php echo htmlspecialchars($behavior['title']); ?></h3>
                        <p><?php echo htmlspecialchars($behavior['description']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="products-section" id="products">
        <div class="container">
            <div class="section-title fade-in">
                <h2><?php echo htmlspecialchars($products_title); ?></h2>
                <p><?php echo htmlspecialchars($products_subtitle); ?></p>
            </div>
            
            <div class="products-tabs">
                <button class="products-tab active" data-category="all">全部</button>
                <?php foreach ($categories as $cat): ?>
                    <button class="products-tab" data-category="<?php echo htmlspecialchars($cat['slug']); ?>">
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </button>
                <?php endforeach; ?>
            </div>
            
            <div class="products-info">
                <span class="products-count">共 <strong id="productsTotal">0</strong> 个产品</span>
            </div>
            
            <div class="products-grid" id="productsGrid">
                <div class="products-loading">
                    <div class="loading-spinner"></div>
                    <span>加载中...</span>
                </div>
            </div>
            
            <div class="products-pagination" id="productsPagination"></div>
        </div>
    </section>

    <section class="business-areas-section" id="business">
        <div class="container">
            <div class="section-title fade-in">
                <h2><?php echo htmlspecialchars($business_title); ?></h2>
                <p><?php echo htmlspecialchars($business_subtitle); ?></p>
            </div>
            <div class="business-grid">
                <?php foreach ($business_areas as $area): ?>
                    <div class="business-card scale-in">
                        <div class="business-icon">
                            <?php echo $area['icon_svg']; ?>
                        </div>
                        <h3><?php echo htmlspecialchars($area['title']); ?></h3>
                        <p><?php echo htmlspecialchars($area['description']); ?></p>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($business_areas)): ?>
                    <div class="business-card scale-in">
                        <div class="business-icon">🌐</div>
                        <h3>网站开发</h3>
                        <p>提供从策划、设计到开发、运维的全流程网站建设服务，打造优质的线上体验</p>
                    </div>
                    <div class="business-card scale-in">
                        <div class="business-icon">📱</div>
                        <h3>移动应用</h3>
                        <p>开发iOS和Android原生应用及跨平台应用，满足不同场景的移动端需求</p>
                    </div>
                    <div class="business-card scale-in">
                        <div class="business-icon">☁️</div>
                        <h3>云服务</h3>
                        <p>提供云计算、云存储、云安全等云服务解决方案，助力企业数字化转型</p>
                    </div>
                    <div class="business-card scale-in">
                        <div class="business-icon">🔧</div>
                        <h3>技术咨询</h3>
                        <p>为企业提供专业的技术咨询服务，帮助客户解决复杂的技术难题</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="timeline-section" id="timeline">
        <div class="container">
            <div class="section-title fade-in">
                <h2><?php echo htmlspecialchars($timeline_title); ?></h2>
                <p><?php echo htmlspecialchars($timeline_subtitle); ?></p>
            </div>
            <div class="timeline-wrapper">
                <div class="timeline">
                    <?php foreach ($timelines as $index => $item): ?>
                        <div class="timeline-item scale-in">
                            <div class="timeline-date"><?php echo htmlspecialchars($item['year']); ?></div>
                            <div class="timeline-content">
                                <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                                <p><?php echo htmlspecialchars($item['description']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="contact-page-form" id="contact">
        <div class="container">
            <div class="section-title-center fade-in">
                <span class="section-tag"><?php echo htmlspecialchars($contact_title); ?></span>
                <h2>多种方式 与我们取得联系</h2>
            </div>
            
            <div class="info-cards-grid-simple">
                <div class="info-card-simple scale-in">
                    <div class="card-icon-simple">
                        <svg class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" width="20" height="20"><path d="M874.666667 375.466667L548.266667 622.933333c-20.266667 14.933333-46.933333 14.933333-66.133334 0L155.733333 375.466667C138.666667 362.666667 138.666667 337.066667 155.733333 324.266667c17.066667-12.8 40.533333-12.8 57.6 0L512 554.666667l298.666667-230.4c17.066667-12.8 40.533333-12.8 57.6 0 17.066667 12.8 17.066667 38.4 6.4 51.2z" fill="currentColor"/><path d="M853.333333 213.333333H170.666667c-46.933333 0-85.333333 38.4-85.333334 85.333334v426.666666c0 46.933333 38.4 85.333333 85.333334 85.333334h682.666666c46.933333 0 85.333333-38.4 85.333334-85.333334V298.666667c0-46.933333-38.4-85.333333-85.333334-85.333334z m0 512H170.666667V298.666667h682.666666v426.666666z" fill="currentColor"/></svg>
                    </div>
                    <div class="card-content-simple">
                        <h4>邮箱</h4>
                        <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>"><?php echo htmlspecialchars($contact_email); ?></a>
                    </div>
                </div>
                <div class="info-card-simple scale-in">
                    <div class="card-icon-simple">
                        <svg class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" width="20" height="20"><path d="M746.666667 640c-14.933333 0-32 6.4-44.8 19.2l-64 64c-91.733333-46.933333-168.533333-123.733333-215.466667-215.466667l64-64c12.8-12.8 19.2-29.866667 19.2-44.8 0-14.933333-6.4-32-19.2-44.8l-149.333333-149.333333c-12.8-12.8-29.866667-19.2-44.8-19.2-14.933333 0-32 6.4-44.8 19.2l-85.333334 85.333333c-46.933333 46.933333-46.933333 123.733333 0 170.666667l384 384c46.933333 46.933333 123.733333 46.933333 170.666667 0l85.333333-85.333333c12.8-12.8 19.2-29.866667 19.2-44.8 0-14.933333-6.4-32-19.2-44.8l-149.333333-149.333334c-12.8-12.8-29.866667-19.2-44.8-19.2z" fill="currentColor"/></svg>
                    </div>
                    <div class="card-content-simple">
                        <h4>电话</h4>
                        <a href="tel:<?php echo htmlspecialchars($contact_phone); ?>"><?php echo htmlspecialchars($contact_phone); ?></a>
                    </div>
                </div>
                <div class="info-card-simple scale-in">
                    <div class="card-icon-simple">
                        <svg class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" width="20" height="20"><path d="M512 85.333333c-164.266667 0-298.666667 134.4-298.666667 298.666667 0 213.333333 298.666667 554.666667 298.666667 554.666667s298.666667-341.333333 298.666667-554.666667c0-164.266667-134.4-298.666667-298.666667-298.666667z m0 426.666667c-70.4 0-128-57.6-128-128s57.6-128 128-128 128 57.6 128 128-57.6 128-128 128z" fill="currentColor"/></svg>
                    </div>
                    <div class="card-content-simple">
                        <h4>地址</h4>
                        <span><?php echo htmlspecialchars($contact_address); ?></span>
                    </div>
                </div>
                <div class="info-card-simple scale-in">
                    <div class="card-icon-simple">
                        <svg class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" width="20" height="20"><path d="M512 85.333333c-235.733333 0-426.666667 190.933333-426.666667 426.666667s190.933333 426.666667 426.666667 426.666667 426.666667-190.933333 426.666667-426.666667S747.733333 85.333333 512 85.333333z m0 768c-188.8 0-341.333333-152.533333-341.333333-341.333333s152.533333-341.333333 341.333333-341.333333 341.333333 152.533333 341.333333 341.333333-152.533333 341.333333-341.333333 341.333333z" fill="currentColor"/><path d="M682.666667 512h-128V341.333333c0-23.466667-19.2-42.666667-42.666667-42.666666s-42.666667 19.2-42.666667 42.666666v213.333334c0 23.466667 19.2 42.666667 42.666667 42.666666h170.666667c23.466667 0 42.666667-19.2 42.666666-42.666666s-19.2-42.666667-42.666666-42.666667z" fill="currentColor"/></svg>
                    </div>
                    <div class="card-content-simple">
                        <h4>工作时间</h4>
                        <span><?php echo htmlspecialchars($working_hours); ?></span>
                    </div>
                </div>
            </div>
            
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
                    </div>
                </div>
                
                <div class="form-right-panel slide-in-right">
                    <div class="form-card-wrap">
                        <div id="formMessage" class="form-message-box" style="display: none;"></div>
                        <form id="contactForm" method="POST" action="api/index.php">
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
                                <svg class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" width="20" height="20"><path d="M896 512c0-212.074667-171.925333-384-384-384S128 299.925333 128 512s171.925333 384 384 384 384-171.925333 384-384z m-64 0c0 176.725333-143.274667 320-320 320S192 688.725333 192 512 335.274667 192 512 192s320 143.274667 320 320z" fill="currentColor"/><path d="M704 512l-192-128v256z" fill="currentColor"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const observerOptions = {
                root: null,
                rootMargin: '0px',
                threshold: 0.1
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                    }
                });
            }, observerOptions);

            document.querySelectorAll('.fade-in, .slide-in-left, .slide-in-right, .scale-in').forEach(el => {
                observer.observe(el);
            });

            if ('scrollRestoration' in history) {
                history.scrollRestoration = 'manual';
            }
            // 只有在没有锚点的情况下才强制滚动到页面顶部
            if (!window.location.hash) {
                window.scrollTo(0, 0);
            } else {
                // 如果有锚点，保存锚点信息并立即隐藏
                const targetId = window.location.hash.substring(1);
                const targetSection = document.getElementById(targetId);
                
                if (targetSection) {
                    // 立即隐藏锚点
                    history.replaceState(null, null, window.location.pathname + window.location.search);
                    
                    // 延迟跳转到锚点位置
                    setTimeout(function() {
                        const header = document.querySelector('header');
                        const contentNav = document.querySelector('.content-nav');
                        const headerHeight = header ? header.offsetHeight : 0;
                        const contentNavHeight = contentNav ? contentNav.offsetHeight : 0;
                        const offset = headerHeight + contentNavHeight + 10;
                        const targetPosition = targetSection.offsetTop - offset;
                        
                        window.scrollTo({
                            top: targetPosition,
                            behavior: 'smooth'
                        });
                    }, 100);
                }
            }

            const timelineItems = document.querySelectorAll('.timeline-item');
            
            timelineItems.forEach(item => {
                item.addEventListener('click', function() {
                    const timelineWrapper = document.querySelector('.timeline-wrapper');
                    const itemRect = this.getBoundingClientRect();
                    const wrapperRect = timelineWrapper.getBoundingClientRect();
                    
                    const itemCenter = itemRect.left + itemRect.width / 2;
                    const wrapperCenter = wrapperRect.left + wrapperRect.width / 2;
                    
                    const scrollAmount = itemCenter - wrapperCenter;
                    
                    timelineWrapper.scrollBy({
                        left: scrollAmount,
                        behavior: 'smooth'
                    });
                });
                
                item.style.cursor = 'pointer';
            });

            const slides = document.querySelectorAll('.hero-slide');
            const dots = document.querySelectorAll('.dot');
            const prevBtn = document.querySelector('.slider-arrow-prev');
            const nextBtn = document.querySelector('.slider-arrow-next');
            const heroSlider = document.querySelector('.hero-slider');
            let currentSlide = 0;
            let autoSlideInterval;
            let touchStartX = 0;
            let touchEndX = 0;

            function showSlide(index) {
                slides.forEach((slide, i) => {
                    slide.classList.remove('active');
                    if (dots[i]) dots[i].classList.remove('active');
                });
                
                currentSlide = (index + slides.length) % slides.length;
                slides[currentSlide].classList.add('active');
                if (dots[currentSlide]) dots[currentSlide].classList.add('active');
            }

            function nextSlide() {
                showSlide(currentSlide + 1);
            }

            function prevSlide() {
                showSlide(currentSlide - 1);
            }

            if (slides.length > 1) {
                dots.forEach((dot, index) => {
                    dot.addEventListener('click', () => {
                        showSlide(index);
                        resetAutoSlide();
                    });
                });

                if (prevBtn) {
                    prevBtn.addEventListener('click', () => {
                        prevSlide();
                        resetAutoSlide();
                    });
                }

                if (nextBtn) {
                    nextBtn.addEventListener('click', () => {
                        nextSlide();
                        resetAutoSlide();
                    });
                }

                heroSlider.addEventListener('touchstart', (e) => {
                    touchStartX = e.changedTouches[0].screenX;
                }, false);

                heroSlider.addEventListener('touchend', (e) => {
                    touchEndX = e.changedTouches[0].screenX;
                    handleSwipe();
                }, false);

                function handleSwipe() {
                    const swipeThreshold = 50;
                    const diff = touchStartX - touchEndX;
                    
                    if (Math.abs(diff) > swipeThreshold) {
                        if (diff > 0) {
                            nextSlide();
                        } else {
                            prevSlide();
                        }
                        resetAutoSlide();
                    }
                }

                function startAutoSlide() {
                    autoSlideInterval = setInterval(nextSlide, 5000);
                }

                function resetAutoSlide() {
                    clearInterval(autoSlideInterval);
                    startAutoSlide();
                }

                startAutoSlide();
            }

            const scrollIndicator = document.querySelector('.scroll-indicator');
            if (scrollIndicator) {
                let isScrolling = false;
                let scrollTimeout = null;

                scrollIndicator.addEventListener('click', function(e) {
                    if (isScrolling) {
                        return;
                    }
                    
                    isScrolling = true;
                    
                    if (e) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                    
                    const nextSection = document.querySelector('.story-section');
                    if (nextSection) {
                        var header = document.querySelector('header');
                        var contentNav = document.querySelector('.content-nav');
                        var headerHeight = header ? header.offsetHeight : 0;
                        var contentNavHeight = contentNav ? contentNav.offsetHeight : 0;
                        var offset = headerHeight + contentNavHeight + 20;
                        var targetY = nextSection.offsetTop - offset;
                        var currentScroll = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
                        var scrollDistance = Math.abs(targetY - currentScroll);
                        var scrollDuration = Math.min(600, Math.max(300, scrollDistance / 3));
                        var startTime = null;
                        
                        function animateScroll(currentTime) {
                            if (!startTime) startTime = currentTime;
                            var timeElapsed = currentTime - startTime;
                            var progress = Math.min(timeElapsed / scrollDuration, 1);
                            
                            var easeProgress = progress < 0.5 
                                ? 2 * progress * progress 
                                : 1 - Math.pow(-2 * progress + 2, 2) / 2;
                            
                            window.scrollTo(0, currentScroll + (targetY - currentScroll) * easeProgress);
                            
                            if (progress < 1) {
                                window.requestAnimationFrame(animateScroll);
                            } else {
                                if (scrollTimeout) {
                                    clearTimeout(scrollTimeout);
                                }
                                scrollTimeout = setTimeout(function() {
                                    isScrolling = false;
                                }, 500);
                            }
                        }
                        
                        window.requestAnimationFrame(animateScroll);
                    } else {
                        isScrolling = false;
                    }
                });
            }

            const cultureSlides = document.querySelectorAll('.culture-slide');
            const cultureDots = document.querySelectorAll('.culture-dot');
            const culturePrevBtn = document.querySelector('.culture-slider-prev');
            const cultureNextBtn = document.querySelector('.culture-slider-next');
            const cultureSlider = document.querySelector('.culture-slider');
            let currentCultureSlide = 0;
            let cultureAutoSlideInterval;
            let cultureTouchStartX = 0;
            let cultureTouchEndX = 0;

            function showCultureSlide(index) {
                cultureSlides.forEach((slide, i) => {
                    slide.classList.remove('active');
                    if (cultureDots[i]) cultureDots[i].classList.remove('active');
                });
                
                currentCultureSlide = (index + cultureSlides.length) % cultureSlides.length;
                cultureSlides[currentCultureSlide].classList.add('active');
                if (cultureDots[currentCultureSlide]) cultureDots[currentCultureSlide].classList.add('active');
            }

            function nextCultureSlide() {
                showCultureSlide(currentCultureSlide + 1);
            }

            function prevCultureSlide() {
                showCultureSlide(currentCultureSlide - 1);
            }

            if (cultureSlides.length > 1) {
                cultureDots.forEach((dot, index) => {
                    dot.addEventListener('click', () => {
                        showCultureSlide(index);
                        resetCultureAutoSlide();
                    });
                });

                if (culturePrevBtn) {
                    culturePrevBtn.addEventListener('click', () => {
                        prevCultureSlide();
                        resetCultureAutoSlide();
                    });
                }

                if (cultureNextBtn) {
                    cultureNextBtn.addEventListener('click', () => {
                        nextCultureSlide();
                        resetCultureAutoSlide();
                    });
                }

                if (cultureSlider) {
                    cultureSlider.addEventListener('touchstart', (e) => {
                        cultureTouchStartX = e.changedTouches[0].screenX;
                    }, false);

                    cultureSlider.addEventListener('touchend', (e) => {
                        cultureTouchEndX = e.changedTouches[0].screenX;
                        handleCultureSwipe();
                    }, false);

                    function handleCultureSwipe() {
                        const swipeThreshold = 50;
                        const diff = cultureTouchStartX - cultureTouchEndX;
                        
                        if (Math.abs(diff) > swipeThreshold) {
                            if (diff > 0) {
                                nextCultureSlide();
                            } else {
                                prevCultureSlide();
                            }
                            resetCultureAutoSlide();
                        }
                    }
                }

                function startCultureAutoSlide() {
                    cultureAutoSlideInterval = setInterval(nextCultureSlide, 6000);
                }

                function resetCultureAutoSlide() {
                    clearInterval(cultureAutoSlideInterval);
                    startCultureAutoSlide();
                }

                startCultureAutoSlide();
            }

            const contentNavLinks = document.querySelectorAll('.content-nav-link');
            const sections = document.querySelectorAll('#story, #culture, #behavior, #products, #business, #timeline, #contact');
            const contentNav = document.querySelector('.content-nav');
            const header = document.querySelector('header');

            // 重置导航栏激活状态到初始状态
            contentNavLinks.forEach(link => {
                link.classList.remove('active');
            });
            // 设置第一个导航链接（公司简介）为激活状态
            const firstNavLink = document.querySelector('.content-nav-link[href="#story"]');
            if (firstNavLink) {
                firstNavLink.classList.add('active');
            }

            function updateContentNavPosition() {
                if (window.innerWidth > 768) {
                    const headerHeight = header.offsetHeight;
                    contentNav.style.top = headerHeight + 'px';
                    contentNav.style.position = 'sticky';
                    contentNav.style.width = '100%';
                } else {
                    contentNav.style.top = '56px';
                    contentNav.style.position = 'sticky';
                    contentNav.style.width = '100%';
                }
            }

            updateContentNavPosition();
            
            window.addEventListener('resize', updateContentNavPosition);

            if (window.innerWidth <= 768) {
                let contentNavStuck = false;
                const contentNavHeight = 46;
                
                window.addEventListener('scroll', function() {
                    const headerHeight = 56;
                    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                    const navOffset = document.querySelector('.hero').offsetHeight;
                    
                    if (scrollTop >= navOffset - headerHeight && !contentNavStuck) {
                        contentNav.style.position = 'fixed';
                        contentNav.style.top = headerHeight + 'px';
                        contentNav.style.left = '0';
                        contentNav.style.right = '0';
                        contentNav.style.width = '100%';
                        contentNav.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.1)';
                        contentNavStuck = true;
                    } else if (scrollTop < navOffset - headerHeight && contentNavStuck) {
                        contentNav.style.position = 'sticky';
                        contentNav.style.top = '56px';
                        contentNav.style.boxShadow = '';
                        contentNavStuck = false;
                    }
                });
            }

            contentNavLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href');
                    const targetSection = document.querySelector(targetId);
                    
                    if (targetSection) {
                        const headerHeight = header.offsetHeight;
                        const contentNavHeight = contentNav.offsetHeight;
                        const offset = headerHeight + contentNavHeight + 10;
                        const targetPosition = targetSection.offsetTop - offset;
                        
                        window.scrollTo({
                            top: targetPosition,
                            behavior: 'smooth'
                        });
                    }
                });
            });

            window.addEventListener('scroll', function() {
                let current = 'story';
                const headerHeight = header.offsetHeight;
                const contentNavHeight = contentNav.offsetHeight;
                const scrollPosition = window.scrollY + headerHeight + contentNavHeight + 120;
                const windowHeight = window.innerHeight;
                const documentHeight = document.documentElement.scrollHeight;
                const isAtBottom = window.scrollY + windowHeight >= documentHeight - 50;

                sections.forEach(section => {
                    const sectionTop = section.offsetTop;
                    const sectionHeight = section.offsetHeight;
                    
                    if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                        current = section.getAttribute('id');
                    }
                });

                if (isAtBottom) {
                    const lastSection = sections[sections.length - 1];
                    if (lastSection) {
                        current = lastSection.getAttribute('id');
                    }
                }

                contentNavLinks.forEach(link => {
                    link.classList.remove('active');
                    if (link.getAttribute('href') === '#' + current) {
                        link.classList.add('active');
                        centerNavLink(link);
                    }
                });
            });

            function centerNavLink(activeLink) {
                const navList = document.querySelector('.content-nav-list');
                const navListRect = navList.getBoundingClientRect();
                const linkRect = activeLink.getBoundingClientRect();
                
                const linkCenter = linkRect.left + linkRect.width / 2;
                const navListCenter = navListRect.left + navListRect.width / 2;
                
                const scrollAmount = linkCenter - navListCenter;
                
                navList.scrollBy({
                    left: scrollAmount,
                    behavior: 'smooth'
                });
            }
        });
        
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
        
        if (sendCodeBtn) {
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
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('验证码已发送到您的邮箱，请注意查收');
                        startCountdown(60);
                    } else {
                        alert(data.error || '验证码发送失败');
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
        }

        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const formData = new FormData(form);
            const formMessage = document.getElementById('formMessage');
            const submitBtn = form.querySelector('button[type="submit"]');
            
            const verificationCode = verificationCodeInput ? verificationCodeInput.value.trim() : '';
            if (!verificationCode) {
                alert('请输入邮箱验证码');
                if (verificationCodeInput) verificationCodeInput.focus();
                return;
            }
            
            if (!/^\d{6}$/.test(verificationCode)) {
                alert('请输入6位数字验证码');
                if (verificationCodeInput) verificationCodeInput.focus();
                return;
            }
            
            const phoneInput = document.getElementById('phone');
            const phone = phoneInput ? phoneInput.value.trim() : '';
            if (phone && !/^1[3-9]\d{9}$/.test(phone)) {
                if (phoneInput) {
                    phoneInput.style.borderColor = '#ef4444';
                    phoneInput.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.2)';
                    setTimeout(function() {
                        phoneInput.style.borderColor = '';
                        phoneInput.style.boxShadow = '';
                    }, 3000);
                }
                formMessage.style.display = 'block';
                formMessage.style.background = '#ffebee';
                formMessage.style.color = '#c62828';
                formMessage.innerHTML = '<h3 style="margin-bottom: 10px;">手机号格式错误！</h3><p>请输入11位有效手机号</p>';
                formMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
                if (phoneInput) phoneInput.focus();
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

        let currentCategory = 'all';
        let currentPage = 1;
        let isLoading = false;

        function loadProducts(category, page) {
            if (isLoading) return;
            isLoading = true;
            
            const grid = document.getElementById('productsGrid');
            const pagination = document.getElementById('productsPagination');
            const totalEl = document.getElementById('productsTotal');
            
            grid.innerHTML = '<div class="products-loading"><div class="loading-spinner"></div><span>加载中...</span></div>';
            pagination.innerHTML = '';
            
            const params = new URLSearchParams({
                action: 'products_paginated',
                category: category,
                page: page
            });
            
            fetch('api/products.php?' + params.toString())
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        totalEl.textContent = data.pagination.total_products;
                        renderProducts(data.data);
                        renderPagination(data.pagination);
                        currentCategory = category;
                        currentPage = page;
                    } else {
                        grid.innerHTML = '<div class="products-empty"><div class="products-empty-icon"><i class="ri-error-warning-line"></i></div><div class="products-empty-text">加载失败</div></div>';
                    }
                })
                .catch(() => {
                    grid.innerHTML = '<div class="products-empty"><div class="products-empty-icon"><i class="ri-error-warning-line"></i></div><div class="products-empty-text">加载失败</div></div>';
                })
                .finally(() => {
                    isLoading = false;
                });
        }

        function renderProducts(products) {
            const grid = document.getElementById('productsGrid');
            
            if (products.length === 0) {
                grid.innerHTML = '<div class="products-empty"><div class="products-empty-icon"><i class="ri-inbox-line"></i></div><div class="products-empty-text">暂无产品</div></div>';
                return;
            }
            
            grid.innerHTML = products.map(product => `
                <div class="product-card scale-in visible" data-category="${product.category_slug || 'all'}" data-id="${product.id}">
                    <div class="product-image">
                        ${product.image ? 
                            `<img src="${escapeHtml(product.image)}" alt="${escapeHtml(product.title)}">` : 
                            '<div class="product-placeholder">📦</div>'
                        }
                        <div class="product-category">${escapeHtml(product.category_name || '未分类')}</div>
                        ${product.is_original == 1 ? '<div class="product-original">原创</div>' : ''}
                    </div>
                    <div class="product-content">
                        <h3>${escapeHtml(product.title)}</h3>
                        <p>${escapeHtml(product.description ? product.description.substring(0, 80) : '')}...</p>
                        <a href="/project?id=${product.id}" class="product-link">查看详情</a>
                    </div>
                </div>
            `).join('');
        }

        function renderPagination(pagination) {
            const container = document.getElementById('productsPagination');
            const { current_page, total_pages } = pagination;
            
            if (total_pages <= 1) {
                container.innerHTML = '';
                return;
            }
            
            let html = '';
            
            if (current_page > 1) {
                html += `<button class="pagination-btn pagination-prev" onclick="goToPage(${current_page - 1})">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                    <span>上一页</span>
                </button>`;
            } else {
                html += `<span class="pagination-btn pagination-prev pagination-disabled">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                    <span>上一页</span>
                </span>`;
            }
            
            html += '<div class="pagination-pages">';
            
            const startPage = Math.max(1, current_page - 2);
            const endPage = Math.min(total_pages, current_page + 2);
            
            if (startPage > 1) {
                html += `<button class="pagination-page" onclick="goToPage(1)">1</button>`;
                if (startPage > 2) {
                    html += '<span class="pagination-ellipsis">...</span>';
                }
            }
            
            for (let i = startPage; i <= endPage; i++) {
                if (i === current_page) {
                    html += `<span class="pagination-page pagination-current">${i}</span>`;
                } else {
                    html += `<button class="pagination-page" onclick="goToPage(${i})">${i}</button>`;
                }
            }
            
            if (endPage < total_pages) {
                if (endPage < total_pages - 1) {
                    html += '<span class="pagination-ellipsis">...</span>';
                }
                html += `<button class="pagination-page" onclick="goToPage(${total_pages})">${total_pages}</button>`;
            }
            
            html += '</div>';
            
            if (current_page < total_pages) {
                html += `<button class="pagination-btn pagination-next" onclick="goToPage(${current_page + 1})">
                    <span>下一页</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>`;
            } else {
                html += `<span class="pagination-btn pagination-next pagination-disabled">
                    <span>下一页</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </span>`;
            }
            
            container.innerHTML = html;
        }

        function goToPage(page) {
            const productsSection = document.getElementById('products');
            if (productsSection) {
                const header = document.querySelector('header');
                const contentNav = document.querySelector('.content-nav');
                const headerHeight = header ? header.offsetHeight : 0;
                const navHeight = contentNav ? contentNav.offsetHeight : 0;
                const offset = headerHeight + navHeight + 20;
                const targetPosition = productsSection.offsetTop - offset;
                
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
            loadProducts(currentCategory, page);
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        const productsTabs = document.querySelectorAll('.products-tab');

        productsTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const category = this.dataset.category;
                
                productsTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                loadProducts(category, 1);
            });
        });

        loadProducts('all', 1);
    </script>
    
    <style>
        .business-areas-section {
            padding: 80px 0 100px 0;
            background: #ffffff;
        }
        
        .business-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 28px;
        }
        
        .business-card {
            background: #ffffff;
            padding: 36px;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        
        .business-card:hover {
            border-color: #f59e0b;
            box-shadow: 0 8px 24px rgba(245, 158, 11, 0.1);
            transform: translateY(-4px);
        }
        
        .business-icon {
            font-size: 40px;
            margin-bottom: 20px;
            color: #f59e0b;
        }
        
        .business-icon svg {
            width: 40px;
            height: 40px;
            fill: currentColor;
        }
        
        .business-card h3 {
            font-size: 20px;
            margin-bottom: 12px;
            color: #1f2937;
            font-weight: 600;
        }
        
        .business-card p {
            font-size: 15px;
            line-height: 1.7;
            color: #6b7280;
        }
        
        @media (max-width: 768px) {
            .business-areas-section {
                padding: 50px 0 70px 0;
            }
            
            .business-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .business-card {
                padding: 28px;
            }
        }
        
        .section-title-center {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .section-title-center .section-tag {
            display: inline-block;
            background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
            color: #fff;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 16px;
        }
        
        .section-title-center h2 {
            font-size: 28px;
            color: #1f2937;
            font-weight: 600;
        }
        
        .info-cards-grid-simple {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 50px;
        }
        
        .info-card-simple {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        
        .info-card-simple:hover {
            border-color: #f59e0b;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.1);
        }
        
        .card-icon-simple {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f59e0b;
            flex-shrink: 0;
        }
        
        .card-icon-simple svg {
            width: 20px;
            height: 20px;
        }
        
        .card-content-simple h4 {
            font-size: 13px;
            color: #9ca3af;
            margin-bottom: 4px;
            font-weight: 500;
        }
        
        .card-content-simple a,
        .card-content-simple span {
            font-size: 14px;
            color: #1f2937;
            font-weight: 500;
        }
        
        .card-content-simple a:hover {
            color: #f59e0b;
        }
        
        @media (max-width: 992px) {
            .info-cards-grid-simple {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 576px) {
            .info-cards-grid-simple {
                grid-template-columns: 1fr;
            }
            
            .section-title-center h2 {
                font-size: 22px;
            }
        }
        
        .form-message-box {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .form-message-box h3 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .form-message-box p {
            font-size: 14px;
            margin: 0;
        }
    </style>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>
</html>
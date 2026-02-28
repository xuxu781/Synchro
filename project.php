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

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    redirect('index.php');
}

$db = Database::getInstance();

$product = $db->fetchOne("SELECT p.*, c.name as category_name, c.slug as category_slug FROM `lty-products` p LEFT JOIN `lty-product_categories` c ON p.category_id = c.id WHERE p.id = ? AND p.status = 1", [$id]);

if (!$product) {
    redirect('index.php');
}

$viewedProducts = $_SESSION['viewed_products'] ?? [];
if (!in_array($id, $viewedProducts)) {
    $db->query("UPDATE `lty-products` SET views = views + 1 WHERE id = ?", [$id]);
    $_SESSION['viewed_products'][] = $id;
}

$updates = $db->fetchAll("SELECT * FROM `lty-product_updates` WHERE product_id = ? AND status = 1 ORDER BY release_date DESC, sort_order ASC", [$id]);

$latestUpdate = !empty($updates) ? $updates[0] : null;
$latestVersion = $latestUpdate ? $latestUpdate['version'] : '';

$site_name = Settings::get('site_name', '团队官网');
$page_title = $product['title'] . ' - ' . $site_name;
$page_description = !empty($product['description']) ? mb_substr($product['description'], 0, 160, 'UTF-8') : ($product['title'] . ' - ' . $site_name);
$page_keywords = $product['title'] . ',' . ($product['category_name'] ?? '产品') . ',' . $site_name;
$page_image = $product['image'] ?? '';
$current_page = 'project';
$extra_css = ['assets/css/project.css'];

require_once __DIR__ . '/includes/header.php';

$features = !empty($product['features']) ? json_decode($product['features'], true) : [];
$features = is_array($features) ? $features : [];
$tech_stack = !empty($product['tech_stack']) ? json_decode($product['tech_stack'], true) : [];
$tech_stack = is_array($tech_stack) ? $tech_stack : [];
$images = !empty($product['images']) ? json_decode($product['images'], true) : [];
$images = is_array($images) ? $images : [];

$description = !empty($product['full_description']) ? $product['full_description'] : $product['description'];

$category_name = !empty($product['category_name']) ? $product['category_name'] : '未分类';
?>

    <div class="project-detail-wrapper">
        <div class="container">
            <div class="project-detail-grid">
                <div class="project-detail-main">
                    <div class="detail-card detail-hero-card">
                        <div class="detail-breadcrumb">
                            <a href="/">首页</a>
                            <span class="breadcrumb-arrow">→</span>
                            <a href="/#products">产品</a>
                            <span class="breadcrumb-arrow">→</span>
                            <span class="breadcrumb-current"><?php echo htmlspecialchars($product['title']); ?></span>
                        </div>
                        
                        <div class="detail-hero-content">
                            <div class="detail-image-wrapper">
                                <?php if (!empty($product['image'])): ?>
                                    <div class="detail-hero-image-container">
                                        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>" class="detail-hero-image">
                                        <div class="detail-image-overlay">
                                            <button class="detail-zoom-btn" onclick="viewImage('<?php echo htmlspecialchars($product['image']); ?>', '<?php echo htmlspecialchars($product['title']); ?>')">
                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <circle cx="11" cy="11" r="8"></circle>
                                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                                    <line x1="11" y1="8" x2="11" y2="14"></line>
                                                    <line x1="8" y1="11" x2="14" y2="11"></line>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="detail-hero-image-container">
                                        <div class="detail-image-placeholder">
                                            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                                <polyline points="21 15 16 10 5 21"></polyline>
                                            </svg>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                

                            </div>
                            
                            <div class="detail-hero-info">
                                <div class="detail-badges">
                                    <?php if (!empty($product['is_original']) && $product['is_original'] == 1): ?>
                                        <span class="detail-badge detail-badge-original">原创</span>
                                    <?php endif; ?>
                                    <span class="detail-badge detail-badge-category"><?php echo htmlspecialchars($category_name); ?></span>
                                    <?php if (!empty($product['status_text'])): ?>
                                        <span class="detail-badge detail-badge-status"><?php echo htmlspecialchars($product['status_text']); ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <h1 class="detail-title"><?php echo htmlspecialchars($product['title']); ?></h1>
                                
                                <p class="detail-description"><?php echo nl2br(htmlspecialchars($description)); ?></p>
                                
                                <div class="detail-stats">
                                    <?php if (!empty($latestVersion)): ?>
                                        <div class="stat-item">
                                            <div class="stat-icon">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                                                    <path d="M2 17l10 5 10-5"></path>
                                                    <path d="M2 12l10 5 10-5"></path>
                                                </svg>
                                            </div>
                                            <div class="stat-content">
                                                <div class="stat-value">v<?php echo htmlspecialchars($latestVersion); ?></div>
                                                <div class="stat-label">当前版本</div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <div class="stat-item">
                                        <div class="stat-icon">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                        </div>
                                        <div class="stat-content">
                                            <div class="stat-value"><?php echo number_format($product['views']); ?></div>
                                            <div class="stat-label">浏览量</div>
                                        </div>
                                    </div>
                                    <?php if (!empty($product['release_date'])): ?>
                                        <div class="stat-item">
                                            <div class="stat-icon">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                                </svg>
                                            </div>
                                            <div class="stat-content">
                                                <div class="stat-value"><?php echo date('Y-m-d', strtotime($product['release_date'])); ?></div>
                                                <div class="stat-label">发布日期</div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="detail-actions">
                                    <?php if (!empty($product['demo_url']) && $product['demo_url'] != '#'): ?>
                                        <a href="<?php echo htmlspecialchars($product['demo_url']); ?>" target="_blank" class="detail-btn detail-btn-primary">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polygon points="5 3 19 12 5 21 5 3"></polygon>
                                            </svg>
                                            在线演示
                                        </a>
                                    <?php else: ?>
                                        <span class="detail-btn detail-btn-primary detail-btn-disabled">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polygon points="5 3 19 12 5 21 5 3"></polygon>
                                            </svg>
                                            <span class="btn-text-wrapper">
                                                <span class="btn-main-text">在线演示</span>
                                                <span class="btn-sub-text">暂无</span>
                                            </span>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($product['download_url']) && $product['download_url'] != '#'): ?>
                                        <a href="<?php echo htmlspecialchars($product['download_url']); ?>" target="_blank" class="detail-btn detail-btn-secondary">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                <polyline points="7 10 12 15 17 10"></polyline>
                                                <line x1="12" y1="15" x2="12" y2="3"></line>
                                            </svg>
                                            立即下载
                                        </a>
                                    <?php else: ?>
                                        <span class="detail-btn detail-btn-secondary detail-btn-disabled">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                <polyline points="7 10 12 15 17 10"></polyline>
                                                <line x1="12" y1="15" x2="12" y2="3"></line>
                                            </svg>
                                            <span class="btn-text-wrapper">
                                                <span class="btn-main-text">立即下载</span>
                                                <span class="btn-sub-text">暂无</span>
                                            </span>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($product['link']) && $product['link'] != '#'): ?>
                                        <a href="<?php echo htmlspecialchars($product['link']); ?>" target="_blank" class="detail-btn detail-btn-outline">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                                                <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                                            </svg>
                                            访问官网
                                        </a>
                                    <?php else: ?>
                                        <span class="detail-btn detail-btn-outline detail-btn-disabled">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                                                <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                                            </svg>
                                            <span class="btn-text-wrapper">
                                                <span class="btn-main-text">访问官网</span>
                                                <span class="btn-sub-text">暂无</span>
                                            </span>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($features)): ?>
                        <div class="detail-card detail-features-card">
                            <h2 class="detail-section-title">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                                </svg>
                                功能特性
                            </h2>
                            <div class="detail-features-grid">
                                <?php foreach ($features as $feature): ?>
                                    <div class="detail-feature-item">
                                        <div class="feature-check">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                                <polyline points="20 6 9 17 4 12"></polyline>
                                            </svg>
                                        </div>
                                        <span><?php echo htmlspecialchars($feature); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($tech_stack)): ?>
                        <div class="detail-card detail-tech-card">
                            <h2 class="detail-section-title">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="16 18 22 12 16 6"></polyline>
                                    <polyline points="8 6 2 12 8 18"></polyline>
                                </svg>
                                技术栈
                            </h2>
                            <div class="detail-tech-tags">
                                <?php foreach ($tech_stack as $tech): ?>
                                    <span class="detail-tech-tag"><?php echo htmlspecialchars($tech); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($images)): ?>
                        <div class="detail-card detail-images-card">
                            <h2 class="detail-section-title">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                    <polyline points="21 15 16 10 5 21"></polyline>
                                </svg>
                                项目图片
                            </h2>
                            <div class="detail-images-grid">
                                <?php foreach ($images as $index => $img): ?>
                                    <div class="detail-image-item" onclick="viewImage('<?php echo htmlspecialchars($img); ?>', '图片 <?php echo $index + 1; ?>')">
                                        <img src="<?php echo htmlspecialchars($img); ?>" alt="图片 <?php echo $index + 1; ?>" loading="lazy">
                                        <div class="detail-image-item-overlay">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="11" cy="11" r="8"></circle>
                                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                                <line x1="11" y1="8" x2="11" y2="14"></line>
                                                <line x1="8" y1="11" x2="14" y2="11"></line>
                                            </svg>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($updates)): ?>
                        <div class="detail-card detail-updates-card">
                            <h2 class="detail-section-title">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                    <polyline points="10 9 9 9 8 9"></polyline>
                                </svg>
                                更新日志
                            </h2>
                            <div class="detail-updates-timeline">
                                <?php foreach ($updates as $index => $update): ?>
                                    <div class="detail-update-item">
                                        <div class="update-timeline-dot"></div>
                                        <div class="update-timeline-line <?php echo $index === count($updates) - 1 ? 'last' : ''; ?>"></div>
                                        <div class="update-content-wrapper">
                                            <div class="update-header">
                                                <span class="update-version">v<?php echo htmlspecialchars($update['version']); ?></span>
                                                <span class="update-date"><?php echo date('Y-m-d', strtotime($update['release_date'])); ?></span>
                                            </div>
                                            <div class="update-body">
                                                <?php echo nl2br(htmlspecialchars($update['content'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="detail-card detail-updates-card">
                            <h2 class="detail-section-title">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                    <polyline points="10 9 9 9 8 9"></polyline>
                                </svg>
                                更新日志
                            </h2>
                            <div class="detail-updates-empty">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="8" x2="12" y2="12"></line>
                                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                </svg>
                                <p>暂无更新日志</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="project-detail-sidebar">
                    <div class="detail-sidebar-card">
                        <h3 class="detail-sidebar-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="16" x2="12" y2="12"></line>
                                <line x1="12" y1="8" x2="12.01" y2="8"></line>
                            </svg>
                            项目信息
                        </h3>
                        <div class="detail-sidebar-info">
                            <?php if (!empty($product['price'])): ?>
                                <div class="sidebar-info-item">
                                    <span class="sidebar-info-label">价格</span>
                                    <span class="sidebar-info-value sidebar-price">¥<?php echo number_format($product['price'], 2); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="sidebar-info-item">
                                <span class="sidebar-info-label">类型</span>
                                <span class="sidebar-info-value"><?php echo htmlspecialchars($category_name); ?></span>
                            </div>
                            <?php if (!empty($product['status_text'])): ?>
                                <div class="sidebar-info-item">
                                    <span class="sidebar-info-label">状态</span>
                                    <span class="sidebar-info-value sidebar-status <?php echo $product['status_text'] == '预售中' ? 'status-pre' : 'status-active'; ?>">
                                        <?php echo htmlspecialchars($product['status_text']); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($product['release_date'])): ?>
                                <div class="sidebar-info-item">
                                    <span class="sidebar-info-label">发布日期</span>
                                    <span class="sidebar-info-value"><?php echo date('Y-m-d', strtotime($product['release_date'])); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="sidebar-info-item">
                                <span class="sidebar-info-label">浏览量</span>
                                <span class="sidebar-info-value"><?php echo number_format($product['views']); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="detail-sidebar-card">
                        <h3 class="detail-sidebar-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                                <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                            </svg>
                            快速链接
                        </h3>
                        <div class="detail-sidebar-links">
                            <?php if (!empty($product['demo_url']) && $product['demo_url'] != '#'): ?>
                                <a href="<?php echo htmlspecialchars($product['demo_url']); ?>" target="_blank" class="sidebar-link-item">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polygon points="5 3 19 12 5 21 5 3"></polygon>
                                    </svg>
                                    在线演示
                                </a>
                            <?php else: ?>
                                <span class="sidebar-link-item sidebar-link-disabled">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polygon points="5 3 19 12 5 21 5 3"></polygon>
                                    </svg>
                                    <span class="sidebar-text-wrapper">
                                        <span class="sidebar-main-text">在线演示</span>
                                        <span class="sidebar-sub-text">暂无</span>
                                    </span>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($product['download_url']) && $product['download_url'] != '#'): ?>
                                <a href="<?php echo htmlspecialchars($product['download_url']); ?>" target="_blank" class="sidebar-link-item">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="7 10 12 15 17 10"></polyline>
                                        <line x1="12" y1="15" x2="12" y2="3"></line>
                                    </svg>
                                    立即下载
                                </a>
                            <?php else: ?>
                                <span class="sidebar-link-item sidebar-link-disabled">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="7 10 12 15 17 10"></polyline>
                                        <line x1="12" y1="15" x2="12" y2="3"></line>
                                    </svg>
                                    <span class="sidebar-text-wrapper">
                                        <span class="sidebar-main-text">立即下载</span>
                                        <span class="sidebar-sub-text">暂无</span>
                                    </span>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($product['link']) && $product['link'] != '#'): ?>
                                <a href="<?php echo htmlspecialchars($product['link']); ?>" target="_blank" class="sidebar-link-item">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                                    </svg>
                                    访问官网
                                </a>
                            <?php else: ?>
                                <span class="sidebar-link-item sidebar-link-disabled">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                                    </svg>
                                    <span class="sidebar-text-wrapper">
                                        <span class="sidebar-main-text">访问官网</span>
                                        <span class="sidebar-sub-text">暂无</span>
                                    </span>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="imageModal" class="detail-image-modal">
        <div class="modal-content-wrapper">
            <button class="modal-close-btn" onclick="closeImageModal()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
            <img id="modalImage" src="" alt="">
            <div class="modal-caption" id="modalImageTitle"></div>
        </div>
    </div>

    <script>
    function toggleMenu() {
        const navLinks = document.getElementById('navLinks');
        navLinks.classList.toggle('active');
    }
    
    function viewImage(imageUrl, imageTitle) {
        const modal = document.getElementById('imageModal');
        const modalImage = document.getElementById('modalImage');
        const modalTitle = document.getElementById('modalImageTitle');
        
        modalImage.src = imageUrl;
        modalTitle.textContent = imageTitle;
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeImageModal() {
        const modal = document.getElementById('imageModal');
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    document.getElementById('imageModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeImageModal();
        }
    });
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeImageModal();
        }
    });
    </script>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>
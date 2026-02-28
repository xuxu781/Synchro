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

Auth::requirePermission('seo_manage');

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'seo_separator' => trim($_POST['seo_separator'] ?? '-'),
        'seo_title' => trim($_POST['seo_title'] ?? ''),
        'seo_keywords' => trim($_POST['seo_keywords'] ?? ''),
        'seo_description' => trim($_POST['seo_description'] ?? ''),
        'baidu_auto_submit' => trim($_POST['baidu_auto_submit'] ?? '0'),
        'baidu_normal_submit' => trim($_POST['baidu_normal_submit'] ?? '0'),
        'baidu_fast_submit' => trim($_POST['baidu_fast_submit'] ?? '0'),
        'baidu_token' => trim($_POST['baidu_token'] ?? ''),
        'seo_default_image' => trim($_POST['seo_default_image'] ?? ''),
        'external_link_redirect' => trim($_POST['external_link_redirect'] ?? '0'),
        'external_link_auth' => trim($_POST['external_link_auth'] ?? '0'),
        'external_link_article' => trim($_POST['external_link_article'] ?? '0'),
        'external_link_exclude_domains' => trim($_POST['external_link_exclude_domains'] ?? ''),
        'seo_auto_alt' => trim($_POST['seo_auto_alt'] ?? '0'),
        'remove_category_url' => trim($_POST['remove_category_url'] ?? '0')
    ];
    
    foreach ($settings as $key => $value) {
        Settings::set($key, $value);
    }
    
    OperationLog::update('settings', '更新SEO设置', 'settings', null, array_keys($settings));
    
    set_flash_message('success', 'SEO设置保存成功');
    redirect('lty-admin/seo.php');
}

$success = get_flash_message('success');
$error = get_flash_message('error');

$user = Auth::user();

$todayViews = 0;
$totalViews = 0;
$baiduSubmitCount = intval(Settings::get('baidu_submit_count', '0'));
$externalLinkCount = 0;

try {
    $result = $db->fetchOne("SELECT COALESCE(SUM(view_count), 0) as cnt FROM `lty-page_views` WHERE view_date = CURDATE()");
    $todayViews = $result ? intval($result['cnt']) : 0;
} catch (Exception $e) {}

try {
    $result = $db->fetchOne("SELECT COALESCE(SUM(view_count), 0) as cnt FROM `lty-page_views`");
    $totalViews = $result ? intval($result['cnt']) : 0;
} catch (Exception $e) {}

try {
    $result = $db->fetchOne("SELECT COUNT(*) as cnt FROM `lty-external_link_logs` WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $externalLinkCount = $result ? intval($result['cnt']) : 0;
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO优化 - <?php echo htmlspecialchars(Settings::get('site_name', '官网系统')); ?></title>
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        .seo-header {
            background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
            border-radius: 20px;
            padding: 32px;
            margin-bottom: 24px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        .seo-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 400px;
            height: 400px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }
        .seo-header::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: 10%;
            width: 200px;
            height: 200px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }
        .seo-header-content {
            position: relative;
            z-index: 1;
        }
        .seo-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .seo-header p {
            opacity: 0.9;
            font-size: 14px;
        }
        .seo-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        .seo-stat-card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 20px;
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.3s ease;
        }
        .seo-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        }
        .seo-stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .seo-stat-icon.purple { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); }
        .seo-stat-icon.blue { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); }
        .seo-stat-icon.green { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); }
        .seo-stat-icon.orange { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); }
        .seo-stat-icon svg {
            width: 24px;
            height: 24px;
            color: #b45309;
        }
        .seo-stat-info h3 {
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 500;
            margin-bottom: 4px;
        }
        .seo-stat-info p {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
        }
        .seo-section {
            background: var(--bg-card);
            border-radius: 20px;
            border: 1px solid var(--border-color);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .seo-section-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--bg-tertiary);
        }
        .seo-section-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .seo-section-icon svg {
            width: 20px;
            height: 20px;
        }
        .seo-section-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
        }
        .seo-section-desc {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 2px;
        }
        .seo-section-body {
            padding: 24px;
        }
        .seo-form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        .seo-form-grid.single {
            grid-template-columns: 1fr;
        }
        .seo-form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .seo-form-group.full-width {
            grid-column: 1 / -1;
        }
        .seo-form-label {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .seo-form-label svg {
            width: 16px;
            height: 16px;
            color: var(--text-muted);
        }
        .seo-form-input,
        .seo-form-select,
        .seo-form-textarea {
            padding: 12px 16px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            font-size: 14px;
            color: var(--text-primary);
            background: var(--bg-tertiary);
            transition: all 0.2s ease;
        }
        .seo-form-input:focus,
        .seo-form-select:focus,
        .seo-form-textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }
        .seo-form-textarea {
            resize: vertical;
            min-height: 100px;
        }
        .seo-form-hint {
            font-size: 12px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .seo-form-hint svg {
            width: 14px;
            height: 14px;
        }
        .seo-toggle-group {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px;
            background: var(--bg-tertiary);
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }
        .seo-toggle-info h4 {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        .seo-toggle-info p {
            font-size: 12px;
            color: var(--text-muted);
        }
        .seo-toggle {
            position: relative;
            width: 48px;
            height: 26px;
        }
        .seo-toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .seo-toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--border-color);
            transition: 0.3s;
            border-radius: 26px;
        }
        .seo-toggle-slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
        }
        .seo-toggle input:checked + .seo-toggle-slider {
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
        }
        .seo-toggle input:checked + .seo-toggle-slider:before {
            transform: translateX(22px);
        }
        .seo-image-picker {
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }
        .seo-image-input {
            flex: 1;
        }
        .seo-image-preview {
            width: 120px;
            height: 80px;
            border-radius: 10px;
            border: 2px dashed var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: var(--bg-tertiary);
        }
        .seo-image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .seo-image-preview svg {
            width: 32px;
            height: 32px;
            color: var(--text-muted);
        }
        .seo-btn {
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
        }
        .seo-btn-primary {
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
            color: white;
        }
        .seo-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(245, 158, 11, 0.4);
        }
        .seo-btn-outline {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }
        .seo-btn-outline:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        .seo-footer-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding: 24px;
            background: var(--bg-card);
            border-radius: 20px;
            border: 1px solid var(--border-color);
        }
        .char-counter {
            font-size: 12px;
            color: var(--text-muted);
            text-align: right;
            margin-top: 4px;
        }
        .char-counter.warning {
            color: #f59e0b;
        }
        .char-counter.error {
            color: #ef4444;
        }
        @media (max-width: 768px) {
            .seo-stats {
                grid-template-columns: repeat(2, 1fr);
            }
            .seo-form-grid {
                grid-template-columns: 1fr;
            }
            .seo-image-picker {
                flex-direction: column;
            }
            .seo-image-preview {
                width: 100%;
                height: 120px;
            }
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <?php if ($error): ?>
            <div class="alert alert-error" style="margin-bottom: 20px;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom: 20px;"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="seo-header">
            <div class="seo-header-content">
                <h1>SEO优化中心</h1>
                <p>优化网站搜索引擎表现，提升网站在搜索结果中的排名和展示效果</p>
            </div>
        </div>

        <div class="seo-stats">
            <div class="seo-stat-card">
                <div class="seo-stat-icon purple">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </div>
                <div class="seo-stat-info">
                    <h3>今日浏览次数</h3>
                    <p><?php echo number_format($todayViews); ?></p>
                </div>
            </div>
            <div class="seo-stat-card">
                <div class="seo-stat-icon blue">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                    </svg>
                </div>
                <div class="seo-stat-info">
                    <h3>总浏览次数</h3>
                    <p><?php echo number_format($totalViews); ?></p>
                </div>
            </div>
            <div class="seo-stat-card">
                <div class="seo-stat-icon green">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                        <path d="M2 17l10 5 10-5"></path>
                        <path d="M2 12l10 5 10-5"></path>
                    </svg>
                </div>
                <div class="seo-stat-info">
                    <h3>百度收录提交次数</h3>
                    <p><?php echo number_format($baiduSubmitCount); ?></p>
                </div>
            </div>
            <div class="seo-stat-card">
                <div class="seo-stat-icon orange">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                    </svg>
                </div>
                <div class="seo-stat-info">
                    <h3>外链处理数量</h3>
                    <p><?php echo number_format($externalLinkCount); ?> <small style="font-size: 12px; color: #888;">(近30天)</small></p>
                </div>
            </div>
        </div>

        <form method="POST">
            <div class="seo-section">
                <div class="seo-section-header">
                    <div class="seo-section-icon" style="background: linear-gradient(135deg, #f59e0b, #fbbf24);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="seo-section-title">基础SEO设置</div>
                        <div class="seo-section-desc">配置网站的核心SEO信息，影响搜索引擎收录和展示</div>
                    </div>
                </div>
                <div class="seo-section-body">
                    <div class="seo-form-grid">
                        <div class="seo-form-group">
                            <label class="seo-form-label">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 7V4h16v3"></path>
                                    <path d="M9 20h6"></path>
                                    <path d="M12 4v16"></path>
                                </svg>
                                SEO标题
                            </label>
                            <input type="text" class="seo-form-input" id="seo_title" name="seo_title" 
                                value="<?php echo htmlspecialchars(Settings::get('seo_title', '')); ?>" 
                                placeholder="网站SEO标题，留空则使用网站名称"
                                oninput="updateCharCount(this, 60, 'title_counter')">
                            <div class="seo-form-hint">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="M12 16v-4"></path>
                                    <path d="M12 8h.01"></path>
                                </svg>
                                建议长度：50-60个字符
                                <span id="title_counter" class="char-counter" style="margin-left: auto;"></span>
                            </div>
                        </div>
                        <div class="seo-form-group">
                            <label class="seo-form-label">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                SEO连接符
                            </label>
                            <select class="seo-form-select" id="seo_separator" name="seo_separator">
                                <option value="-" <?php echo Settings::get('seo_separator', '-') === '-' ? 'selected' : ''; ?>>- 短横线（推荐）</option>
                                <option value="|" <?php echo Settings::get('seo_separator', '-') === '|' ? 'selected' : ''; ?>>| 竖线</option>
                                <option value="_" <?php echo Settings::get('seo_separator', '-') === '_' ? 'selected' : ''; ?>>_ 下划线</option>
                                <option value="·" <?php echo Settings::get('seo_separator', '-') === '·' ? 'selected' : ''; ?>>· 间隔号</option>
                            </select>
                            <div class="seo-form-hint">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="M12 16v-4"></path>
                                    <path d="M12 8h.01"></path>
                                </svg>
                                用于分隔页面标题与网站名称
                            </div>
                        </div>
                        <div class="seo-form-group full-width">
                            <label class="seo-form-label">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polygon points="20 6 12 2 4 6"></polygon>
                                    <line x1="4" y1="6" x2="4" y2="18"></line>
                                    <line x1="20" y1="6" x2="20" y2="18"></line>
                                    <line x1="4" y1="12" x2="20" y2="12"></line>
                                </svg>
                                SEO关键词
                            </label>
                            <textarea class="seo-form-textarea" id="seo_keywords" name="seo_keywords" rows="3" 
                                placeholder="多个关键词用英文逗号分隔，例如：网站建设,软件开发,IT服务"
                                oninput="updateCharCount(this, 200, 'keywords_counter')"><?php echo htmlspecialchars(Settings::get('seo_keywords', '')); ?></textarea>
                            <div class="seo-form-hint">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="M12 16v-4"></path>
                                    <path d="M12 8h.01"></path>
                                </svg>
                                建议5-10个核心关键词
                                <span id="keywords_counter" class="char-counter" style="margin-left: auto;"></span>
                            </div>
                        </div>
                        <div class="seo-form-group full-width">
                            <label class="seo-form-label">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                </svg>
                                SEO描述
                            </label>
                            <textarea class="seo-form-textarea" id="seo_description" name="seo_description" rows="4" 
                                placeholder="网站描述，用于搜索引擎展示，建议包含核心关键词"
                                oninput="updateCharCount(this, 160, 'desc_counter')"><?php echo htmlspecialchars(Settings::get('seo_description', '')); ?></textarea>
                            <div class="seo-form-hint">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="M12 16v-4"></path>
                                    <path d="M12 8h.01"></path>
                                </svg>
                                建议长度：150-160个字符
                                <span id="desc_counter" class="char-counter" style="margin-left: auto;"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="seo-section">
                <div class="seo-section-header">
                    <div class="seo-section-icon" style="background: linear-gradient(135deg, #f59e0b, #fbbf24);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M2 12h20"></path>
                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="seo-section-title">百度搜索优化</div>
                        <div class="seo-section-desc">配置百度搜索引擎收录和提交设置</div>
                    </div>
                </div>
                <div class="seo-section-body">
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <div class="seo-toggle-group">
                            <div class="seo-toggle-info">
                                <h4>全站链接自动提交</h4>
                                <p>启用后页面访问时自动向百度提交链接，加快收录速度</p>
                            </div>
                            <label class="seo-toggle">
                                <input type="checkbox" name="baidu_auto_submit" value="1" <?php echo Settings::get('baidu_auto_submit', '0') === '1' ? 'checked' : ''; ?>>
                                <span class="seo-toggle-slider"></span>
                            </label>
                        </div>
                        <div class="seo-toggle-group">
                            <div class="seo-toggle-info">
                                <h4>百度普通收录</h4>
                                <p>开启百度普通收录推送，适合已稳定运营的网站</p>
                            </div>
                            <label class="seo-toggle">
                                <input type="checkbox" name="baidu_normal_submit" value="1" <?php echo Settings::get('baidu_normal_submit', '0') === '1' ? 'checked' : ''; ?>>
                                <span class="seo-toggle-slider"></span>
                            </label>
                        </div>
                        <div class="seo-toggle-group">
                            <div class="seo-toggle-info">
                                <h4>百度快速收录</h4>
                                <p>开启百度快速收录推送，适合新站或新内容</p>
                            </div>
                            <label class="seo-toggle">
                                <input type="checkbox" name="baidu_fast_submit" value="1" <?php echo Settings::get('baidu_fast_submit', '0') === '1' ? 'checked' : ''; ?>>
                                <span class="seo-toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                    <div style="margin-top: 20px;">
                        <div class="seo-form-group">
                            <label class="seo-form-label">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                                百度准入密钥Token
                            </label>
                            <input type="text" class="seo-form-input" id="baidu_token" name="baidu_token" 
                                value="<?php echo htmlspecialchars(Settings::get('baidu_token', '')); ?>" 
                                placeholder="在百度搜索资源平台获取">
                            <div class="seo-form-hint">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="M12 16v-4"></path>
                                    <path d="M12 8h.01"></path>
                                </svg>
                                登录 <a href="https://ziyuan.baidu.com/" target="_blank" style="color: var(--primary-color);">百度搜索资源平台</a>，在用户中心-准入密钥获取
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="seo-section">
                <div class="seo-section-header">
                    <div class="seo-section-icon" style="background: linear-gradient(135deg, #f59e0b, #fbbf24);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                            <polyline points="21 15 16 10 5 21"></polyline>
                        </svg>
                    </div>
                    <div>
                        <div class="seo-section-title">图片SEO优化</div>
                        <div class="seo-section-desc">优化搜索引擎图片展示效果</div>
                    </div>
                </div>
                <div class="seo-section-body">
                    <div class="seo-form-grid single">
                        <div class="seo-form-group">
                            <label class="seo-form-label">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                    <polyline points="21 15 16 10 5 21"></polyline>
                                </svg>
                                默认分享图片
                            </label>
                            <div class="seo-image-picker">
                                <div class="seo-image-input">
                                    <input type="text" class="seo-form-input" id="seo_default_image" name="seo_default_image" 
                                        value="<?php echo htmlspecialchars(Settings::get('seo_default_image', '')); ?>" 
                                        placeholder="选择或输入默认图片URL">
                                    <div class="seo-form-hint" style="margin-top: 8px;">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <path d="M12 16v-4"></path>
                                            <path d="M12 8h.01"></path>
                                        </svg>
                                        当页面无图片时，使用此图片作为搜索引擎展示图片
                                    </div>
                                </div>
                                <div class="seo-image-preview" id="seo_image_preview">
                                    <?php if (Settings::get('seo_default_image', '')): ?>
                                        <img src="<?php echo htmlspecialchars(Settings::get('seo_default_image', '')); ?>" alt="预览">
                                    <?php else: ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                            <polyline points="21 15 16 10 5 21"></polyline>
                                        </svg>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="seo-btn seo-btn-outline" onclick="openMediaLibrary('seo_default_image')">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="17 8 12 3 7 8"></polyline>
                                        <line x1="12" y1="3" x2="12" y2="15"></line>
                                    </svg>
                                    选择图片
                                </button>
                            </div>
                        </div>
                        <div class="seo-toggle-group" style="margin-top: 16px;">
                            <div class="seo-toggle-info">
                                <h4>文章图片自动添加alt</h4>
                                <p>自动为文章内容中的图片添加alt属性（使用文章标题）</p>
                            </div>
                            <label class="seo-toggle">
                                <input type="checkbox" name="seo_auto_alt" value="1" <?php echo Settings::get('seo_auto_alt', '0') === '1' ? 'checked' : ''; ?>>
                                <span class="seo-toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="seo-section">
                <div class="seo-section-header">
                    <div class="seo-section-icon" style="background: linear-gradient(135deg, #f59e0b, #fbbf24);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                            <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="seo-section-title">外链重定向</div>
                        <div class="seo-section-desc">管理外部链接跳转，提升网站权重</div>
                    </div>
                </div>
                <div class="seo-section-body">
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <div class="seo-toggle-group">
                            <div class="seo-toggle-info">
                                <h4>外链重定向</h4>
                                <p>将外链转为内部跳转链接，防止权重流失</p>
                            </div>
                            <label class="seo-toggle">
                                <input type="checkbox" name="external_link_redirect" value="1" <?php echo Settings::get('external_link_redirect', '0') === '1' ? 'checked' : ''; ?>>
                                <span class="seo-toggle-slider"></span>
                            </label>
                        </div>
                        <div class="seo-toggle-group">
                            <div class="seo-toggle-info">
                                <h4>外链重定向鉴权</h4>
                                <p>跳转前显示提示页面，提醒用户即将离开本站</p>
                            </div>
                            <label class="seo-toggle">
                                <input type="checkbox" name="external_link_auth" value="1" <?php echo Settings::get('external_link_auth', '0') === '1' ? 'checked' : ''; ?>>
                                <span class="seo-toggle-slider"></span>
                            </label>
                        </div>
                        <div class="seo-toggle-group">
                            <div class="seo-toggle-info">
                                <h4>文章内容外链处理</h4>
                                <p>对文章内容中的外链启用重定向</p>
                            </div>
                            <label class="seo-toggle">
                                <input type="checkbox" name="external_link_article" value="1" <?php echo Settings::get('external_link_article', '0') === '1' ? 'checked' : ''; ?>>
                                <span class="seo-toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                    <div style="margin-top: 20px;">
                        <div class="seo-form-group">
                            <label class="seo-form-label">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                                排除域名
                            </label>
                            <textarea class="seo-form-textarea" id="external_link_exclude_domains" name="external_link_exclude_domains" rows="4" 
                                placeholder="每行一个域名，这些域名的外链不会被重定向&#10;例如：&#10;example.com&#10;www.test.com"><?php echo htmlspecialchars(Settings::get('external_link_exclude_domains', '')); ?></textarea>
                            <div class="seo-form-hint">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="M12 16v-4"></path>
                                    <path d="M12 8h.01"></path>
                                </svg>
                                这些域名的外链不会被重定向，每行一个域名
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="seo-section">
                <div class="seo-section-header">
                    <div class="seo-section-icon" style="background: linear-gradient(135deg, #f59e0b, #fbbf24);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                            <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="seo-section-title">URL优化</div>
                        <div class="seo-section-desc">优化URL结构，提升搜索引擎友好度</div>
                    </div>
                </div>
                <div class="seo-section-body">
                    <div class="seo-toggle-group">
                        <div class="seo-toggle-info">
                            <h4>分类URL去除category</h4>
                            <p>移除分类URL中的/category/前缀，使URL更简洁</p>
                        </div>
                        <label class="seo-toggle">
                            <input type="checkbox" name="remove_category_url" value="1" <?php echo Settings::get('remove_category_url', '0') === '1' ? 'checked' : ''; ?>>
                            <span class="seo-toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="seo-footer-actions">
                <button type="button" class="seo-btn seo-btn-outline" onclick="resetForm()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                        <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
                        <path d="M3 3v5h5"></path>
                    </svg>
                    重置
                </button>
                <button type="submit" class="seo-btn seo-btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    保存设置
                </button>
            </div>
        </form>

        <div class="main-footer">
            <div class="footer-content">
                <span class="footer-line">© 2026 <?php echo htmlspecialchars(Settings::get('site_name', '官网系统')); ?> 1.0</span>
                <span class="footer-line">徐州邻兔跃IT工作室提供技术支持版权所有</span>
            </div>
        </div>
    </div>
</body>
<script>
function openMediaLibrary(inputId) {
    MediaPicker.open({
        multiple: false,
        onSelect: function(url) {
            const input = document.getElementById(inputId);
            if (input) {
                input.value = url;
                updateImagePreview(url);
            }
        }
    });
}

function updateImagePreview(url) {
    const preview = document.getElementById('seo_image_preview');
    if (url) {
        preview.innerHTML = '<img src="' + url + '" alt="预览" onerror="this.parentElement.innerHTML=\'<svg viewBox=\\\'0 0 24 24\\\' fill=\\\'none\\\' stroke=\\\'currentColor\\\' stroke-width=\\\'1.5\\\'><circle cx=\\\'12\\\' cy=\\\'12\\\' r=\\\'10\\\'></circle><path d=\\\'M12 8v4\\\'></path><path d=\\\'M12 16h.01\\\'></path></svg>\'">';
    } else {
        preview.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>';
    }
}

function updateCharCount(input, maxLength, counterId) {
    const counter = document.getElementById(counterId);
    const length = input.value.length;
    counter.textContent = length + '/' + maxLength;
    
    counter.classList.remove('warning', 'error');
    if (length > maxLength) {
        counter.classList.add('error');
    } else if (length > maxLength * 0.8) {
        counter.classList.add('warning');
    }
}

function resetForm() {
    if (confirm('确定要重置所有设置吗？')) {
        document.querySelector('form').reset();
    }
}

document.addEventListener('DOMContentLoaded', function() {
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
    
    updateCharCount(document.getElementById('seo_title'), 60, 'title_counter');
    updateCharCount(document.getElementById('seo_keywords'), 200, 'keywords_counter');
    updateCharCount(document.getElementById('seo_description'), 160, 'desc_counter');
});
</script>

<?php include 'components/media-picker.php'; ?>
<link rel="stylesheet" href="components/media-picker.css">
<script src="components/media-picker.js"></script>
</html>

<?php
require_once __DIR__ . '/../includes/functions.php';

Auth::requirePermission('about_view');

$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>关于程序 - <?php echo htmlspecialchars(Settings::get('site_name', '官网系统')); ?></title>
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        .about-hero {
            background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 30px;
            color: white;
        }
        .about-hero-logo {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            flex-shrink: 0;
            overflow: hidden;
        }
        .about-hero-logo img {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }
        .about-hero-content {
            flex: 1;
        }
        .about-hero-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .about-hero-version {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 16px;
        }
        .about-hero-desc {
            font-size: 14px;
            opacity: 0.85;
            line-height: 1.6;
        }
        .about-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }
        @media (max-width: 900px) {
            .about-grid {
                grid-template-columns: 1fr;
            }
            .about-hero {
                flex-direction: column;
                text-align: center;
            }
        }
        .about-card {
            background: var(--bg-card);
            border-radius: 16px;
            border: 1px solid var(--border-color);
            overflow: hidden;
        }
        .about-card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .about-card-header svg {
            width: 22px;
            height: 22px;
            color: #f59e0b;
        }
        .about-card-header h3 {
            font-size: 16px;
            font-weight: 600;
            margin: 0;
            color: var(--text-primary);
        }
        .about-card-body {
            padding: 20px 24px;
        }
        .about-info-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .about-info-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .about-info-icon {
            width: 40px;
            height: 40px;
            background: #fffbeb;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .about-info-icon svg {
            width: 20px;
            height: 20px;
            color: #f59e0b;
        }
        .about-info-content {
            flex: 1;
            min-width: 0;
        }
        .about-info-label {
            font-size: 12px;
            color: var(--text-secondary);
            margin-bottom: 2px;
        }
        .about-info-value {
            font-size: 14px;
            font-weight: 500;
            color: var(--text-primary);
        }
        .about-info-value a {
            color: #f59e0b;
            text-decoration: none;
        }
        .about-info-value a:hover {
            text-decoration: underline;
        }
        .tech-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .tech-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background: #fffbeb;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-primary);
            transition: all 0.2s ease;
        }
        .tech-tag:hover {
            background: #f59e0b;
            color: white;
        }
        .tech-tag svg {
            width: 18px;
            height: 18px;
        }
        .feature-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .feature-list li {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px;
            background: #fffbeb;
            border-radius: 10px;
        }
        .feature-list li svg {
            width: 20px;
            height: 20px;
            color: #f59e0b;
            flex-shrink: 0;
            margin-top: 2px;
        }
        .feature-list li span {
            font-size: 14px;
            color: var(--text-primary);
            line-height: 1.5;
        }
        .copyright-card {
            background: linear-gradient(135deg, #fff9e6 0%, #fff3cd 100%);
            border: 1px solid #ffc107;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
        }
        .copyright-card h4 {
            color: #856404;
            font-size: 16px;
            font-weight: 600;
            margin: 0 0 12px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .copyright-card h4 svg {
            width: 22px;
            height: 22px;
        }
        .copyright-card p {
            color: #856404;
            font-size: 14px;
            line-height: 1.7;
            margin: 0;
        }
        .contact-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 16px;
        }
        .contact-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .contact-btn svg {
            width: 16px;
            height: 16px;
        }
        .contact-btn-primary {
            background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
            color: white;
        }
        .contact-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
        }
        .contact-btn-outline {
            background: white;
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }
        .contact-btn-outline:hover {
            border-color: #f59e0b;
            color: #f59e0b;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="about-hero">
            <div class="about-hero-logo">
                <img src="../assets/images/logo/logo.png" alt="Logo">
            </div>
            <div class="about-hero-content">
                <div class="about-hero-title"><?php echo htmlspecialchars(Settings::get('site_name', '官网系统')); ?></div>
                <div class="about-hero-version">版本 1.0.0</div>
                <div class="about-hero-desc">一款现代化的企业官网管理系统，提供完善的团队展示、内容管理和权限控制功能。</div>
            </div>
        </div>

        <div class="about-grid">
            <div class="about-card">
                <div class="about-card-header">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    <h3>开发者信息</h3>
                </div>
                <div class="about-card-body">
                    <div class="about-info-list">
                        <div class="about-info-item">
                            <div class="about-info-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                            </div>
                            <div class="about-info-content">
                                <div class="about-info-label">开发团队</div>
                                <div class="about-info-value">徐州邻兔跃IT工作室</div>
                            </div>
                        </div>
                        <div class="about-info-item">
                            <div class="about-info-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="2" y1="12" x2="22" y2="12"/>
                                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                                </svg>
                            </div>
                            <div class="about-info-content">
                                <div class="about-info-label">官方网站</div>
                                <div class="about-info-value"><a href="https://lintuyue.com" target="_blank">lintuyue.com</a></div>
                            </div>
                        </div>
                        <div class="about-info-item">
                            <div class="about-info-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                    <polyline points="22,6 12,13 2,6"/>
                                </svg>
                            </div>
                            <div class="about-info-content">
                                <div class="about-info-label">联系邮箱</div>
                                <div class="about-info-value"><a href="mailto:lintuyue@qq.com">lintuyue@qq.com</a></div>
                            </div>
                        </div>
                        <div class="about-info-item">
                            <div class="about-info-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
                                </svg>
                            </div>
                            <div class="about-info-content">
                                <div class="about-info-label">技术交流QQ群</div>
                                <div class="about-info-value">550133641</div>
                            </div>
                        </div>
                    </div>
                    <div class="contact-buttons">
                        <a href="https://lintuyue.com" target="_blank" class="contact-btn contact-btn-primary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="2" y1="12" x2="22" y2="12"/>
                                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                            </svg>
                            访问官网
                        </a>
                        <a href="https://github.com/xuxu781/Synchro" target="_blank" class="contact-btn contact-btn-outline">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                            </svg>
                            GitHub
                        </a>
                        <a href="https://scno5e3k7t6i.feishu.cn/wiki/ZpuPwZYkzi3tyEkApEKccTR0nzg?from=from_copylink" target="_blank" class="contact-btn contact-btn-primary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                            </svg>
                            使用教程
                        </a>
                        <a href="mailto:lintuyue@qq.com" class="contact-btn contact-btn-outline">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                            联系我们
                        </a>
                    </div>
                </div>
            </div>

            <div class="about-card">
                <div class="about-card-header">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="16 18 22 12 16 6"/>
                        <polyline points="8 6 2 12 8 18"/>
                    </svg>
                    <h3>技术栈</h3>
                </div>
                <div class="about-card-body">
                    <div class="tech-tags">
                        <span class="tech-tag">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M7.01 10.207h-.944l-.515 2.648h.838c.556 0 .97-.105 1.242-.314.272-.21.455-.559.55-1.049.092-.47.05-.802-.124-.995-.175-.193-.523-.29-1.047-.29zM12 5.688C5.373 5.688 0 8.514 0 12s5.373 6.313 12 6.313S24 15.486 24 12c0-3.486-5.373-6.312-12-6.312zm-3.26 7.451c-.261.25-.575.438-.917.551-.336.108-.765.164-1.285.164H5.357l-.327 1.681H3.652l1.23-6.326h2.65c.797 0 1.378.209 1.744.628.366.418.476 1.002.33 1.752a2.836 2.836 0 0 1-.305.847c-.143.255-.33.49-.561.703zm4.024.715l.543-2.799c.063-.318.038-.536-.074-.653-.113-.117-.35-.176-.712-.176h-1.27l-.703 3.628h-1.38l1.23-6.326h1.38l-.327 1.682h1.404c.884 0 1.493.15 1.828.45.335.3.435.788.3 1.465l-.571 2.93h-1.398zm6.486-2.715c-.092.47-.05.802.124.995.175.193.523.29 1.047.29h.944l.515-2.648h-.838c-.556 0-.97.105-1.242.314-.272.21-.455.559-.55 1.049zm-1.381.715a2.836 2.836 0 0 0 .305-.847c.146-.75.036-1.334-.33-1.752-.366-.419-.947-.628-1.744-.628h-2.65l-1.23 6.326h1.378l.327-1.681h1.181c.52 0 .949-.056 1.285-.164.342-.113.656-.301.917-.551.23-.213.418-.448.561-.703z"/>
                            </svg>
                            PHP 8+
                        </span>
                        <span class="tech-tag">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12.001 0C5.326 0 0 5.326 0 12.001c0 6.674 5.326 12 12.001 12 6.674 0 12-5.326 12-12C24.001 5.326 18.675 0 12.001 0zm-.672 3.566c.169-.006.37.015.57.048 1.286.21 2.396.846 3.178 1.754.122.141.232.29.34.443l-1.837 1.657a3.853 3.853 0 0 0-.498-.59c-.527-.507-1.16-.76-1.902-.76-.613 0-1.104.165-1.474.495-.37.33-.556.758-.556 1.284 0 .386.1.72.3.998.2.28.49.506.87.68.38.173.95.358 1.71.554.99.255 1.78.55 2.37.882.59.333 1.04.77 1.35 1.31.31.54.47 1.19.47 1.95 0 1.12-.36 2.03-1.08 2.72-.72.69-1.7 1.1-2.94 1.22v2.17h-1.71v-2.17c-1.34-.14-2.42-.6-3.24-1.38-.82-.78-1.32-1.79-1.5-3.03l2.39-.6c.12.82.43 1.48.93 1.97.5.49 1.13.74 1.88.74.68 0 1.22-.17 1.62-.51.4-.34.6-.79.6-1.35 0-.42-.12-.78-.36-1.07-.24-.29-.56-.52-.96-.7-.4-.18-.98-.37-1.74-.57-.96-.26-1.73-.54-2.31-.85-.58-.31-1.04-.73-1.38-1.27-.34-.54-.51-1.21-.51-2.02 0-.84.24-1.58.71-2.21.47-.63 1.13-1.09 1.97-1.36.31-.1.65-.17 1-.21v-2.17h1.71v2.17c.15.01.3.03.45.05z"/>
                            </svg>
                            MySQL
                        </span>
                        <span class="tech-tag">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M1.5 0h21l-1.91 21.563L11.977 24l-8.564-2.438L1.5 0zm7.031 9.75l-.232-2.718 10.059.003.23-2.622L5.412 4.41l.698 8.01h9.126l-.326 3.426-2.91.804-2.955-.81-.188-2.11H6.248l.33 4.171L12 19.351l5.379-1.443.744-8.157H8.531z"/>
                            </svg>
                            HTML5
                        </span>
                        <span class="tech-tag">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M1.5 0h21l-1.91 21.563L11.977 24l-8.565-2.438L1.5 0zm17.09 4.413L5.41 4.41l.213 2.622 10.125.002-.255 2.716h-6.64l.24 2.573h6.182l-.366 3.523-2.91.804-2.956-.81-.188-2.11h-2.61l.29 3.855L12 19.288l5.373-1.53L18.59 4.414z"/>
                            </svg>
                            CSS3
                        </span>
                        <span class="tech-tag">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M0 0h24v24H0V0zm22.034 18.276c-.175-1.095-.888-2.015-3.003-2.873-.736-.345-1.554-.585-1.797-1.14-.091-.33-.105-.51-.046-.705.15-.646.915-.84 1.515-.66.39.12.75.42.976.9 1.034-.676 1.034-.676 1.755-1.125-.27-.42-.404-.601-.586-.78-.63-.705-1.469-1.065-2.834-1.034l-.705.089c-.676.165-1.32.525-1.71 1.005-1.14 1.291-.811 3.541.569 4.471 1.365 1.02 3.361 1.244 3.616 2.205.24 1.17-.87 1.545-1.966 1.41-.811-.18-1.26-.586-1.755-1.336l-1.83 1.051c.21.48.45.689.81 1.109 1.74 1.756 6.09 1.666 6.871-1.004.029-.09.24-.705.074-1.65l.046.067zm-8.983-7.245h-2.248c0 1.938-.009 3.864-.009 5.805 0 1.232.063 2.363-.138 2.711-.33.689-1.18.601-1.566.48-.396-.196-.597-.466-.83-.855-.063-.105-.11-.196-.127-.196l-1.825 1.125c.305.63.75 1.172 1.324 1.517.855.51 2.004.675 3.207.405.783-.226 1.458-.691 1.811-1.411.51-.93.402-2.07.397-3.346.012-2.054 0-4.109 0-6.179l.004-.056z"/>
                            </svg>
                            JavaScript
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="about-card" style="margin-bottom: 24px;">
            <div class="about-card-header">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                <h3>系统特性</h3>
            </div>
            <div class="about-card-body">
                <ul class="feature-list">
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                        <span>现代化响应式设计，支持多端适配，完美兼容PC、平板和手机设备</span>
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                        <span>完善的权限管理系统，支持多角色、多权限精细化控制</span>
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                        <span>媒体库管理功能，支持图片上传、管理和在线选择</span>
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                        <span>SEO优化设置，自定义网站标题、关键词和描述</span>
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                        <span>安全日志记录，实时监控后台操作行为</span>
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                        <span>邮件通知系统，支持SMTP配置和测试</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="copyright-card">
            <h4>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="16" x2="12" y2="12"/>
                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                </svg>
                版权声明
            </h4>
            <p>版权所有，禁止未经授权的复制、传播、修改或商业使用。未经开发者书面授权，任何单位或个人不得以任何形式复制、传播、展示、修改本软件或制作衍生作品。</p>
        </div>

        <div class="main-footer">
            <div class="footer-content">
                <span class="footer-line">© 2026 <?php echo htmlspecialchars(Settings::get('site_name', '官网系统')); ?> 1.0</span>
                <span class="footer-line">徐州邻兔跃IT工作室提供技术支持版权所有</span>
            </div>
        </div>
    </div>
</body>
</html>

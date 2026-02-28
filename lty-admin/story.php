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

Auth::requirePermission('story_manage');

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_settings'])) {
        $story_title = trim($_POST['story_title'] ?? '');
        $story_subtitle = trim($_POST['story_subtitle'] ?? '');
        
        Settings::set('story_title', $story_title);
        Settings::set('story_subtitle', $story_subtitle);
        
        OperationLog::update('story', '更新公司简介页面标题设置', 'settings', null, ['title' => $story_title, 'subtitle' => $story_subtitle]);
        
        set_flash_message('success', '设置保存成功');
        redirect('lty-admin/story.php');
    } else {
        $settings = [
            'story_content' => trim($_POST['story_content'] ?? ''),
            'story_content2' => trim($_POST['story_content2'] ?? ''),
            'story_content3' => trim($_POST['story_content3'] ?? ''),
            'story_stats_years' => trim($_POST['story_stats_years'] ?? ''),
            'story_stats_cases' => trim($_POST['story_stats_cases'] ?? ''),
            'story_stats_team' => trim($_POST['story_stats_team'] ?? ''),
            'story_stats_satisfaction' => trim($_POST['story_stats_satisfaction'] ?? '')
        ];
        
        foreach ($settings as $key => $value) {
            Settings::set($key, $value);
        }
        OperationLog::update('story', '更新公司简介内容', 'settings', null, array_keys($settings));
        set_flash_message('success', '保存成功');
        redirect('lty-admin/story.php');
    }
}

$success = get_flash_message('success');
$error = get_flash_message('error');

$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>我们的故事 - <?php echo htmlspecialchars(Settings::get('site_name', '官网系统')); ?></title>
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        .form-section {
            background: white;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 0;
            padding-bottom: 16px;
            border-bottom: none;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .form-section h3 {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #f59e0b;
        }
        
        .form-group {
            margin-bottom: 12px;
        }
        
        .form-group textarea {
            min-height: 80px;
            resize: vertical;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .form-actions {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            display: flex;
            justify-content: flex-end;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>公司简介</h1>
            <button onclick="openSettingsModal()" class="btn btn-outline">页面标题设置</button>
        </div>

        <div class="content-card">
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <form method="POST" id="storyForm">
                    <div class="form-section">
                        <h3>内容段落</h3>
                        <div class="form-group">
                            <label for="story_content">内容段落1</label>
                            <textarea id="story_content" name="story_content" rows="4"><?php echo htmlspecialchars(Settings::get('story_content', '我们是一支充满激情和创造力的团队，致力于为客户提供最优质的服务。')); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="story_content2">内容段落2</label>
                            <textarea id="story_content2" name="story_content2" rows="4"><?php echo htmlspecialchars(Settings::get('story_content2', '自成立以来，我们始终坚持以技术创新为核心，以客户需求为导向，不断推出高质量的产品和服务。我们的团队由来自不同领域的专业人才组成，拥有丰富的行业经验和深厚的技术积累。')); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="story_content3">内容段落3</label>
                            <textarea id="story_content3" name="story_content3" rows="4"><?php echo htmlspecialchars(Settings::get('story_content3', '我们相信，只有不断创新和进步，才能在激烈的市场竞争中立于不败之地。因此，我们始终保持着对新技术、新理念的关注和学习，努力为客户提供最前沿的解决方案。')); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3>统计数据</h3>
                        <div class="stats-grid">
                            <div class="form-group">
                                <label for="story_stats_years">年行业经验</label>
                                <input type="text" id="story_stats_years" name="story_stats_years" 
                                       value="<?php echo htmlspecialchars(Settings::get('story_stats_years', '10+')); ?>">
                            </div>
                            <div class="form-group">
                                <label for="story_stats_cases">成功案例</label>
                                <input type="text" id="story_stats_cases" name="story_stats_cases" 
                                       value="<?php echo htmlspecialchars(Settings::get('story_stats_cases', '500+')); ?>">
                            </div>
                            <div class="form-group">
                                <label for="story_stats_team">专业团队</label>
                                <input type="text" id="story_stats_team" name="story_stats_team" 
                                       value="<?php echo htmlspecialchars(Settings::get('story_stats_team', '100+')); ?>">
                            </div>
                            <div class="form-group">
                                <label for="story_stats_satisfaction">客户满意度</label>
                                <input type="text" id="story_stats_satisfaction" name="story_stats_satisfaction" 
                                       value="<?php echo htmlspecialchars(Settings::get('story_stats_satisfaction', '98%')); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">保存设置</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="main-footer">
            <div class="footer-content">
                <span class="footer-line">© 2026 <?php echo htmlspecialchars(Settings::get('site_name', '官网系统')); ?> 1.0</span>
                <span class="footer-line">徐州邻兔跃IT工作室提供技术支持版权所有</span>
            </div>
        </div>
    </div>

    <div id="settingsModal" class="modal <?php echo isset($_GET['open_settings']) ? 'active' : ''; ?>">
        <div class="modal-content">
            <div class="modal-header">
                <h3>页面标题设置</h3>
                <button onclick="closeSettingsModal()" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="save_settings" value="1">
                    <div class="form-group">
                        <label for="story_title">公司简介标题</label>
                        <input type="text" id="story_title" name="story_title" value="<?php echo htmlspecialchars(Settings::get('story_title', '我们的故事')); ?>">
                    </div>
                    <div class="form-group">
                        <label for="story_subtitle">公司简介副标题</label>
                        <input type="text" id="story_subtitle" name="story_subtitle" value="<?php echo htmlspecialchars(Settings::get('story_subtitle', '记录我们的成长历程')); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">保存设置</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openSettingsModal() {
            document.getElementById('settingsModal').classList.add('active');
        }
        
        function closeSettingsModal() {
            document.getElementById('settingsModal').classList.remove('active');
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
        });
    </script>
</body>
</html>

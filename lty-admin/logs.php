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

Auth::requirePermission('logs_view');

$db = Database::getInstance();

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$filters = [
    'action' => $_GET['action'] ?? '',
    'module' => $_GET['module'] ?? '',
    'user_id' => $_GET['user_id'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'keyword' => $_GET['keyword'] ?? ''
];

$where = '1=1';
$params = [];

if (!empty($filters['action'])) {
    $where .= ' AND action = ?';
    $params[] = $filters['action'];
}

if (!empty($filters['module'])) {
    $where .= ' AND module = ?';
    $params[] = $filters['module'];
}

if (!empty($filters['user_id'])) {
    $where .= ' AND user_id = ?';
    $params[] = $filters['user_id'];
}

if (!empty($filters['date_from'])) {
    $where .= ' AND DATE(created_at) >= ?';
    $params[] = $filters['date_from'];
}

if (!empty($filters['date_to'])) {
    $where .= ' AND DATE(created_at) <= ?';
    $params[] = $filters['date_to'];
}

if (!empty($filters['keyword'])) {
    $where .= ' AND (description LIKE ? OR username LIKE ? OR module LIKE ?)';
    $keyword = '%' . $filters['keyword'] . '%';
    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
}

$totalCount = $db->fetchOne("SELECT COUNT(*) as count FROM `lty-operation_logs` WHERE {$where}", $params)['count'];
$totalPages = ceil($totalCount / $perPage);

$logs = $db->fetchAll(
    "SELECT * FROM `lty-operation_logs` WHERE {$where} ORDER BY created_at DESC LIMIT {$offset}, {$perPage}",
    $params
);

$actions = $db->fetchAll("SELECT DISTINCT action FROM `lty-operation_logs` ORDER BY action");
$modules = $db->fetchAll("SELECT DISTINCT module FROM `lty-operation_logs` WHERE module IS NOT NULL ORDER BY module");
$users = $db->fetchAll("SELECT DISTINCT user_id, username FROM `lty-operation_logs` WHERE user_id IS NOT NULL ORDER BY username");

$actionLabels = [
    'create' => ['label' => '创建', 'class' => 'success'],
    'update' => ['label' => '更新', 'class' => 'warning'],
    'delete' => ['label' => '删除', 'class' => 'danger'],
    'login' => ['label' => '登录', 'class' => 'info'],
    'logout' => ['label' => '登出', 'class' => 'secondary'],
    'export' => ['label' => '导出', 'class' => 'primary'],
    'import' => ['label' => '导入', 'class' => 'primary'],
    'upload' => ['label' => '上传', 'class' => 'info'],
    'download' => ['label' => '下载', 'class' => 'info']
];

$moduleLabels = [
    'roles' => '团队角色',
    'admin_users' => '后台用户',
    'permissions' => '权限管理',
    'menus' => '菜单管理',
    'settings' => '网站设置',
    'media' => '媒体库',
    'products' => '产品管理',
    'members' => '团队成员',
    'messages' => '留言管理',
    'contact' => '联系我们',
    'timeline' => '大事记',
    'culture' => '企业文化',
    'behavior' => '行为准则',
    'business' => '业务领域',
    'mission' => '轮播图',
    'story' => '公司简介',
    'auth' => '认证授权',
    'seo' => 'SEO设置',
    'logs' => '操作日志',
    'security_logs' => '安全日志',
    'company_timeline' => '大事记',
    'role' => '角色',
    'product' => '产品',
    'product_category' => '产品分类',
    'product_update' => '产品更新',
    'mission_slide' => '轮播图',
    'contact_message' => '留言',
    'team_member' => '团队成员',
    'company_culture' => '企业文化',
    'contact_type' => '联系类型',
    'social_link' => '社交媒体链接',
    'business_area' => '业务领域',
    'company_behavior' => '行为准则',
    'admin_user' => '后台用户',
    'operation_logs' => '操作日志'
];

$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>操作日志 - <?php echo htmlspecialchars(Settings::get('site_name', '官网系统')); ?></title>
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
            padding: 16px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        .filter-bar .form-group {
            margin-bottom: 0;
            min-width: 150px;
        }
        .filter-bar .form-group label {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 4px;
        }
        .filter-bar .form-control {
            padding: 8px 12px;
            font-size: 13px;
        }
        .filter-actions {
            display: flex;
            align-items: flex-end;
            gap: 8px;
            margin-left: auto;
        }
        .log-action-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .log-action-badge.success { background: #dcfce7; color: #16a34a; }
        .log-action-badge.warning { background: #fef3c7; color: #d97706; }
        .log-action-badge.danger { background: #fee2e2; color: #dc2626; }
        .log-action-badge.info { background: #dbeafe; color: #2563eb; }
        .log-action-badge.secondary { background: #f3f4f6; color: #6b7280; }
        .log-action-badge.primary { background: #e0e7ff; color: #4f46e5; }
        .log-details {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .log-details:hover {
            white-space: normal;
            word-break: break-all;
        }
        .log-ip {
            font-family: monospace;
            font-size: 12px;
            color: #6b7280;
        }
        .log-time {
            font-size: 12px;
            color: #9ca3af;
        }
        .detail-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .detail-modal.active {
            display: flex;
        }
        .detail-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 700px;
            max-height: 80vh;
            overflow: hidden;
        }
        .detail-header {
            padding: 16px 20px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .detail-header h3 {
            margin: 0;
            font-size: 16px;
        }
        .detail-body {
            padding: 20px;
            overflow-y: auto;
            max-height: calc(80vh - 60px);
        }
        .detail-item {
            margin-bottom: 16px;
        }
        .detail-item label {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 4px;
            display: block;
        }
        .detail-item pre {
            background: #f8fafc;
            padding: 12px;
            border-radius: 6px;
            font-size: 12px;
            overflow-x: auto;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
        }
        .stat-card .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
        }
        .stat-card .stat-label {
            font-size: 13px;
            color: #6b7280;
            margin-top: 4px;
        }
        .stat-card.today { border-left: 3px solid #10b981; }
        .stat-card.week { border-left: 3px solid #3b82f6; }
        .stat-card.month { border-left: 3px solid #8b5cf6; }
        .stat-card.total { border-left: 3px solid #f59e0b; }
        .clean-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1001;
            align-items: center;
            justify-content: center;
        }
        .clean-modal.active {
            display: flex;
        }
        .clean-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
            padding: 24px;
        }
        .clean-content h3 {
            margin: 0 0 16px 0;
            font-size: 18px;
        }
        .clean-content p {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 16px;
        }
        .clean-content .form-group {
            margin-bottom: 16px;
        }
        .clean-content .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }
        .clean-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }
        .btn-danger {
            background: #dc2626;
            color: white;
        }
        .btn-danger:hover {
            background: #b91c1c;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>操作日志</h1>
        </div>

        <?php
        $todayCount = $db->fetchOne("SELECT COUNT(*) as count FROM `lty-operation_logs` WHERE DATE(created_at) = CURDATE()")['count'];
        $weekCount = $db->fetchOne("SELECT COUNT(*) as count FROM `lty-operation_logs` WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['count'];
        $monthCount = $db->fetchOne("SELECT COUNT(*) as count FROM `lty-operation_logs` WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['count'];
        ?>
        
        <div class="stats-cards">
            <div class="stat-card today">
                <div class="stat-value"><?php echo number_format($todayCount); ?></div>
                <div class="stat-label">今日操作</div>
            </div>
            <div class="stat-card week">
                <div class="stat-value"><?php echo number_format($weekCount); ?></div>
                <div class="stat-label">近7天操作</div>
            </div>
            <div class="stat-card month">
                <div class="stat-value"><?php echo number_format($monthCount); ?></div>
                <div class="stat-label">近30天操作</div>
            </div>
            <div class="stat-card total">
                <div class="stat-value"><?php echo number_format($totalCount); ?></div>
                <div class="stat-label">总记录数</div>
            </div>
        </div>

        <div class="content-card">
            <div class="card-body">
                <form method="GET" class="filter-bar">
                    <div class="form-group">
                        <label>操作类型</label>
                        <select name="action" class="form-control">
                            <option value="">全部类型</option>
                            <?php foreach ($actions as $action): ?>
                                <option value="<?php echo htmlspecialchars($action['action']); ?>" <?php echo $filters['action'] === $action['action'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($actionLabels[$action['action']]['label'] ?? $action['action']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>操作模块</label>
                        <select name="module" class="form-control">
                            <option value="">全部模块</option>
                            <?php foreach ($modules as $module): ?>
                                <option value="<?php echo htmlspecialchars($module['module']); ?>" <?php echo $filters['module'] === $module['module'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($moduleLabels[$module['module']] ?? $module['module']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>操作用户</label>
                        <select name="user_id" class="form-control">
                            <option value="">全部用户</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?php echo $u['user_id']; ?>" <?php echo $filters['user_id'] === $u['user_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($u['username']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>开始日期</label>
                        <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($filters['date_from']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>结束日期</label>
                        <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($filters['date_to']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>关键词</label>
                        <input type="text" name="keyword" class="form-control" placeholder="搜索描述..." value="<?php echo htmlspecialchars($filters['keyword']); ?>">
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">筛选</button>
                        <a href="logs.php" class="btn btn-outline">重置</a>
                        <?php if (Auth::isSuperAdmin()): ?>
                        <button type="button" class="btn btn-danger" onclick="showCleanModal()">清理日志</button>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th width="60">ID</th>
                                <th width="100">操作用户</th>
                                <th width="80">操作类型</th>
                                <th width="120">操作模块</th>
                                <th>操作描述</th>
                                <th width="120">IP地址</th>
                                <th width="150">操作时间</th>
                                <th width="80">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 40px; color: #9ca3af;">
                                        暂无操作日志记录
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?php echo $log['id']; ?></td>
                                        <td>
                                            <?php if ($log['username']): ?>
                                                <?php echo htmlspecialchars($log['username']); ?>
                                            <?php else: ?>
                                                <span style="color: #9ca3af;">系统</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $actionInfo = $actionLabels[$log['action']] ?? ['label' => $log['action'], 'class' => 'secondary'];
                                            ?>
                                            <span class="log-action-badge <?php echo $actionInfo['class']; ?>">
                                                <?php echo htmlspecialchars($actionInfo['label']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            echo htmlspecialchars($moduleLabels[$log['module']] ?? $log['module'] ?? '-'); 
                                            ?>
                                        </td>
                                        <td class="log-details" title="<?php echo htmlspecialchars($log['description'] ?? ''); ?>">
                                            <?php echo htmlspecialchars($log['description'] ?? '-'); ?>
                                        </td>
                                        <td class="log-ip"><?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?></td>
                                        <td class="log-time"><?php echo $log['created_at']; ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline" onclick="showDetail(<?php echo $log['id']; ?>)">详情</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($filters, ['page' => $page - 1])); ?>" class="btn btn-outline">上一页</a>
                        <?php endif; ?>
                        
                        <span class="page-info">第 <?php echo $page; ?> / <?php echo $totalPages; ?> 页，共 <?php echo number_format($totalCount); ?> 条</span>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?<?php echo http_build_query(array_merge($filters, ['page' => $page + 1])); ?>" class="btn btn-outline">下一页</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="main-footer">
            <div class="footer-content">
                <span class="footer-line">© 2026 <?php echo htmlspecialchars(Settings::get('site_name', '官网系统')); ?> 1.0</span>
                <span class="footer-line">徐州邻兔跃IT工作室提供技术支持版权所有</span>
            </div>
        </div>
    </div>

    <div id="detailModal" class="detail-modal">
        <div class="detail-content">
            <div class="detail-header">
                <h3>日志详情</h3>
                <button onclick="closeDetail()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #6b7280;">&times;</button>
            </div>
            <div class="detail-body" id="detailBody">
            </div>
        </div>
    </div>

    <?php if (Auth::isSuperAdmin()): ?>
    <div id="cleanModal" class="clean-modal">
        <div class="clean-content">
            <h3>清理操作日志</h3>
            <p>将删除指定天数之前的操作日志记录，此操作不可恢复。</p>
            <div class="form-group">
                <label>保留最近天数</label>
                <input type="number" id="cleanDays" class="form-control" value="30" min="0" max="365">
                <small style="color: #6b7280; font-size: 12px;">0表示清空所有日志，最多365天</small>
            </div>
            <div class="clean-actions">
                <button type="button" class="btn btn-outline" onclick="closeCleanModal()">取消</button>
                <button type="button" class="btn btn-danger" onclick="cleanLogs()">确认清理</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        const logsData = <?php echo json_encode(array_column($logs, null, 'id')); ?>;
        const actionLabels = <?php echo json_encode($actionLabels); ?>;
        const moduleLabels = <?php echo json_encode($moduleLabels); ?>;
        
        function showDetail(id) {
            const log = logsData[id];
            if (!log) return;
            
            const actionInfo = actionLabels[log.action] || { label: log.action, class: 'secondary' };
            const moduleName = moduleLabels[log.module] || log.module || '-';
            
            let detailsHtml = '';
            if (log.details) {
                try {
                    const details = JSON.parse(log.details);
                    detailsHtml = '<pre>' + JSON.stringify(details, null, 2) + '</pre>';
                } catch (e) {
                    detailsHtml = '<pre>' + log.details + '</pre>';
                }
            }
            
            document.getElementById('detailBody').innerHTML = `
                <div class="detail-item">
                    <label>日志ID</label>
                    <div>${log.id}</div>
                </div>
                <div class="detail-item">
                    <label>操作用户</label>
                    <div>${log.username || '系统'}</div>
                </div>
                <div class="detail-item">
                    <label>操作类型</label>
                    <div><span class="log-action-badge ${actionInfo.class}">${actionInfo.label}</span></div>
                </div>
                <div class="detail-item">
                    <label>操作模块</label>
                    <div>${moduleName}</div>
                </div>
                <div class="detail-item">
                    <label>操作描述</label>
                    <div>${log.description || '-'}</div>
                </div>
                <div class="detail-item">
                    <label>目标类型</label>
                    <div>${log.target_type || '-'}</div>
                </div>
                <div class="detail-item">
                    <label>目标ID</label>
                    <div>${log.target_id || '-'}</div>
                </div>
                <div class="detail-item">
                    <label>IP地址</label>
                    <div>${log.ip_address || '-'}</div>
                </div>
                <div class="detail-item">
                    <label>用户代理</label>
                    <div style="word-break: break-all; font-size: 12px; color: #6b7280;">${log.user_agent || '-'}</div>
                </div>
                <div class="detail-item">
                    <label>操作时间</label>
                    <div>${log.created_at}</div>
                </div>
                ${detailsHtml ? `
                <div class="detail-item">
                    <label>详细信息</label>
                    ${detailsHtml}
                </div>
                ` : ''}
            `;
            
            document.getElementById('detailModal').classList.add('active');
        }
        
        function closeDetail() {
            document.getElementById('detailModal').classList.remove('active');
        }
        
        document.getElementById('detailModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDetail();
            }
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDetail();
                closeCleanModal();
            }
        });
        
        function showCleanModal() {
            document.getElementById('cleanModal').classList.add('active');
        }
        
        function closeCleanModal() {
            document.getElementById('cleanModal').classList.remove('active');
        }
        
        function cleanLogs() {
            const days = parseInt(document.getElementById('cleanDays').value);
            if (days < 7 || days > 365) {
                alert('保留天数必须在7-365天之间');
                return;
            }
            
            if (!confirm('确定要清理' + days + '天前的操作日志吗？此操作不可恢复！')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'clean');
            formData.append('days', days);
            
            fetch('api/logs.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('清理完成，共删除 ' + data.deleted + ' 条记录');
                    location.reload();
                } else {
                    alert('清理失败：' + (data.error || '未知错误'));
                }
            })
            .catch(error => {
                alert('请求失败：' + error.message);
            });
        }
        
        document.getElementById('cleanModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeCleanModal();
            }
        });
    </script>
</body>
</html>

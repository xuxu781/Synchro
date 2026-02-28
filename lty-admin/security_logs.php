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

Auth::requirePermission('security_logs_view');

$db = Database::getInstance();

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$filters = [
    'event_type' => $_GET['event_type'] ?? '',
    'severity' => $_GET['severity'] ?? '',
    'ip_address' => $_GET['ip_address'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'keyword' => $_GET['keyword'] ?? ''
];

$where = '1=1';
$params = [];

if (!empty($filters['event_type'])) {
    $where .= ' AND event_type = ?';
    $params[] = $filters['event_type'];
}

if (!empty($filters['severity'])) {
    $where .= ' AND severity = ?';
    $params[] = $filters['severity'];
}

if (!empty($filters['ip_address'])) {
    $where .= ' AND ip_address LIKE ?';
    $params[] = '%' . $filters['ip_address'] . '%';
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
    $where .= ' AND (description LIKE ? OR username LIKE ? OR request_url LIKE ?)';
    $keyword = '%' . $filters['keyword'] . '%';
    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
}

$totalCount = $db->fetchOne("SELECT COUNT(*) as count FROM `lty-security_logs` WHERE {$where}", $params)['count'];
$totalPages = ceil($totalCount / $perPage);

$logs = $db->fetchAll(
    "SELECT * FROM `lty-security_logs` WHERE {$where} ORDER BY created_at DESC LIMIT {$offset}, {$perPage}",
    $params
);

$eventTypes = SecurityLog::getEventTypes();
$severityLevels = SecurityLog::getSeverityLevels();

$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安全日志 - <?php echo htmlspecialchars(Settings::get('site_name', '官网系统')); ?></title>
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
        .severity-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .severity-badge.badge-info { background: #dbeafe; color: #2563eb; }
        .severity-badge.badge-warning { background: #fef3c7; color: #d97706; }
        .severity-badge.badge-orange { background: #ffedd5; color: #ea580c; }
        .severity-badge.badge-danger { background: #fee2e2; color: #dc2626; }
        .event-type-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            background: #f3f4f6;
            color: #4b5563;
        }
        .log-description {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .log-description:hover {
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
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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
        .stat-card.today { border-left: 3px solid #ef4444; }
        .stat-card.week { border-left: 3px solid #f97316; }
        .stat-card.month { border-left: 3px solid #eab308; }
        .stat-card.total { border-left: 3px solid #6b7280; }
        .stat-card.critical { border-left: 3px solid #dc2626; }
        .stat-card.critical .stat-value { color: #dc2626; }
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
            <h1>安全日志</h1>
        </div>

        <?php
        $todayCount = $db->fetchOne("SELECT COUNT(*) as count FROM `lty-security_logs` WHERE DATE(created_at) = CURDATE()")['count'];
        $weekCount = $db->fetchOne("SELECT COUNT(*) as count FROM `lty-security_logs` WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['count'];
        $monthCount = $db->fetchOne("SELECT COUNT(*) as count FROM `lty-security_logs` WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['count'];
        $criticalCount = $db->fetchOne("SELECT COUNT(*) as count FROM `lty-security_logs` WHERE severity = 'critical' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['count'];
        ?>
        
        <div class="stats-cards">
            <div class="stat-card today">
                <div class="stat-value"><?php echo number_format($todayCount); ?></div>
                <div class="stat-label">今日安全事件</div>
            </div>
            <div class="stat-card week">
                <div class="stat-value"><?php echo number_format($weekCount); ?></div>
                <div class="stat-label">近7天事件</div>
            </div>
            <div class="stat-card month">
                <div class="stat-value"><?php echo number_format($monthCount); ?></div>
                <div class="stat-label">近30天事件</div>
            </div>
            <div class="stat-card critical">
                <div class="stat-value"><?php echo number_format($criticalCount); ?></div>
                <div class="stat-label">近7天严重事件</div>
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
                        <label>事件类型</label>
                        <select name="event_type" class="form-control">
                            <option value="">全部类型</option>
                            <?php foreach ($eventTypes as $type => $label): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $filters['event_type'] === $type ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>严重程度</label>
                        <select name="severity" class="form-control">
                            <option value="">全部程度</option>
                            <?php foreach ($severityLevels as $level => $label): ?>
                                <option value="<?php echo htmlspecialchars($level); ?>" <?php echo $filters['severity'] === $level ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>IP地址</label>
                        <input type="text" name="ip_address" class="form-control" placeholder="输入IP地址" value="<?php echo htmlspecialchars($filters['ip_address']); ?>">
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
                        <a href="security_logs.php" class="btn btn-outline">重置</a>
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
                                <th width="100">事件类型</th>
                                <th width="70">严重程度</th>
                                <th width="90">用户名</th>
                                <th>事件描述</th>
                                <th width="120">IP地址</th>
                                <th width="150">发生时间</th>
                                <th width="80">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 40px; color: #9ca3af;">
                                        暂无安全日志记录
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?php echo $log['id']; ?></td>
                                        <td>
                                            <span class="event-type-badge">
                                                <?php echo htmlspecialchars($eventTypes[$log['event_type']] ?? $log['event_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            $severityBadgeClass = SecurityLog::getSeverityBadgeClass($log['severity']);
                                            ?>
                                            <span class="severity-badge <?php echo $severityBadgeClass; ?>">
                                                <?php echo htmlspecialchars($severityLevels[$log['severity']] ?? $log['severity']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($log['username']): ?>
                                                <?php echo htmlspecialchars($log['username']); ?>
                                            <?php else: ?>
                                                <span style="color: #9ca3af;">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="log-description" title="<?php echo htmlspecialchars($log['description'] ?? ''); ?>">
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
                <h3>安全事件详情</h3>
                <button onclick="closeDetail()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #6b7280;">&times;</button>
            </div>
            <div class="detail-body" id="detailBody">
            </div>
        </div>
    </div>

    <?php if (Auth::isSuperAdmin()): ?>
    <div id="cleanModal" class="clean-modal">
        <div class="clean-content">
            <h3>清理安全日志</h3>
            <p>将删除指定天数之前的安全日志记录，此操作不可恢复。</p>
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
        const eventTypes = <?php echo json_encode($eventTypes); ?>;
        const severityLevels = <?php echo json_encode($severityLevels); ?>;
        const severityBadgeClasses = {
            low: 'badge-info',
            medium: 'badge-warning',
            high: 'badge-orange',
            critical: 'badge-danger'
        };
        
        function showDetail(id) {
            const log = logsData[id];
            if (!log) return;
            
            const eventTypeName = eventTypes[log.event_type] || log.event_type;
            const severityName = severityLevels[log.severity] || log.severity;
            const severityBadgeClass = severityBadgeClasses[log.severity] || 'badge-secondary';
            
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
                    <label>事件类型</label>
                    <div><span class="event-type-badge">${eventTypeName}</span></div>
                </div>
                <div class="detail-item">
                    <label>严重程度</label>
                    <div><span class="severity-badge ${severityBadgeClass}">${severityName}</span></div>
                </div>
                <div class="detail-item">
                    <label>用户ID</label>
                    <div>${log.user_id || '-'}</div>
                </div>
                <div class="detail-item">
                    <label>用户名</label>
                    <div>${log.username || '-'}</div>
                </div>
                <div class="detail-item">
                    <label>事件描述</label>
                    <div>${log.description || '-'}</div>
                </div>
                <div class="detail-item">
                    <label>IP地址</label>
                    <div>${log.ip_address || '-'}</div>
                </div>
                <div class="detail-item">
                    <label>请求URL</label>
                    <div style="word-break: break-all;">${log.request_url || '-'}</div>
                </div>
                <div class="detail-item">
                    <label>请求方法</label>
                    <div>${log.request_method || '-'}</div>
                </div>
                <div class="detail-item">
                    <label>用户代理</label>
                    <div style="word-break: break-all; font-size: 12px; color: #6b7280;">${log.user_agent || '-'}</div>
                </div>
                <div class="detail-item">
                    <label>发生时间</label>
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
            
            if (!confirm('确定要清理' + days + '天前的安全日志吗？此操作不可恢复！')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'clean');
            formData.append('days', days);
            
            fetch('api/security_logs.php', {
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

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

Auth::requirePermission('messages_manage');

$db = Database::getInstance();

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

if ($action === 'view' && $id) {
    header('Content-Type: application/json');
    $message = $db->fetchOne("SELECT m.*, t.name as type_name FROM `lty-contact_messages` m LEFT JOIN `lty-contact_types` t ON m.type_id = t.id WHERE m.id = ?", [$id]);
    if ($message) {
        $wasUnread = ($message['status'] == 0);
        if ($wasUnread) {
            $db->update('`lty-contact_messages`', ['status' => 1], 'id = ?', [$id]);
            $message['status'] = 1;
        }
        $message['was_unread'] = $wasUnread;
        echo json_encode(['success' => true, 'data' => $message]);
    } else {
        echo json_encode(['success' => false, 'message' => '留言不存在']);
    }
    exit;
}

if ($action === 'read' && $id) {
    $message = $db->fetchOne("SELECT name FROM `lty-contact_messages` WHERE id = ?", [$id]);
    $db->update('`lty-contact_messages`', ['status' => 1], 'id = ?', [$id]);
    OperationLog::update('messages', '标记留言已读：' . ($message['name'] ?? $id), 'contact_message', $id);
    set_flash_message('success', '标记已读成功');
    redirect('lty-admin/messages.php');
}

if ($action === 'reply' && $id) {
    header('Content-Type: application/json');
    $db->update('`lty-contact_messages`', ['replied' => 1], 'id = ?', [$id]);
    OperationLog::update('messages', '回复留言 ID: ' . $id, 'contact_message', $id);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'delete' && $id) {
    $message = $db->fetchOne("SELECT name FROM `lty-contact_messages` WHERE id = ?", [$id]);
    $db->delete('`lty-contact_messages`', 'id = ?', [$id]);
    OperationLog::delete('messages', '删除留言：' . ($message['name'] ?? $id), 'contact_message', $id);
    set_flash_message('success', '删除成功');
    redirect('lty-admin/messages.php');
}

$success = get_flash_message('success');
$error = get_flash_message('error');

$filter = $_GET['filter'] ?? 'all';
if (!in_array($filter, ['all', 'unread', 'read', 'replied', 'unreplied'])) {
    $filter = 'all';
}

$page = (int)($_GET['page'] ?? 1);
$per_page = 20;
$offset = ($page - 1) * $per_page;

$whereClause = '';
$params = [];
if ($filter === 'unread') {
    $whereClause = 'WHERE m.status = 0';
} elseif ($filter === 'read') {
    $whereClause = 'WHERE m.status = 1';
} elseif ($filter === 'replied') {
    $whereClause = 'WHERE m.replied = 1';
} elseif ($filter === 'unreplied') {
    $whereClause = 'WHERE m.replied = 0';
}

$total = $db->fetchOne("SELECT COUNT(*) as count FROM `lty-contact_messages` m $whereClause")['count'];
$total_pages = ceil($total / $per_page);

$unread_count = $db->fetchOne("SELECT COUNT(*) as count FROM `lty-contact_messages` WHERE status = 0")['count'];
$read_count = $db->fetchOne("SELECT COUNT(*) as count FROM `lty-contact_messages` WHERE status = 1")['count'];
$replied_count = $db->fetchOne("SELECT COUNT(*) as count FROM `lty-contact_messages` WHERE replied = 1")['count'];
$unreplied_count = $db->fetchOne("SELECT COUNT(*) as count FROM `lty-contact_messages` WHERE replied = 0")['count'];

$messages = $db->fetchAll("SELECT m.*, t.name as type_name FROM `lty-contact_messages` m LEFT JOIN `lty-contact_types` t ON m.type_id = t.id $whereClause ORDER BY m.status ASC, m.replied ASC, m.created_at DESC LIMIT $per_page OFFSET $offset", $params);

$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>留言管理 - <?php echo htmlspecialchars(Settings::get('site_name', '官网系统')); ?></title>
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        .status-badges {
            display: flex;
            flex-direction: column;
            gap: 6px;
            align-items: flex-start;
        }
        .status-badges .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 500;
            border-radius: 16px;
            transition: all 0.2s ease;
        }
        .status-badges .badge svg {
            flex-shrink: 0;
        }
        .status-badges .badge-warning {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.15) 0%, rgba(217, 119, 6, 0.2) 100%);
            color: #d97706;
            border: 1px solid rgba(245, 158, 11, 0.4);
        }
        .status-badges .badge-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(5, 150, 105, 0.2) 100%);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.4);
        }
        .status-badges .badge-info {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(37, 99, 235, 0.2) 100%);
            color: #2563eb;
            border: 1px solid rgba(59, 130, 246, 0.4);
        }
        .status-badges .badge-secondary {
            background: linear-gradient(135deg, rgba(107, 114, 128, 0.12) 0%, rgba(75, 85, 99, 0.18) 100%);
            color: #6b7280;
            border: 1px solid rgba(107, 114, 128, 0.35);
        }
        .table tr:hover .status-badges .badge {
            transform: translateX(2px);
        }
        .filter-tabs {
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>留言管理</h1>
            <span style="color: var(--text-secondary); font-weight: 500;">共 <?php echo $total; ?> 条留言</span>
        </div>

        <div class="filter-tabs">
            <a href="?filter=all" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                全部
                <span class="tab-count"><?php echo $unread_count + $read_count; ?></span>
            </a>
            <a href="?filter=unread" class="filter-tab <?php echo $filter === 'unread' ? 'active' : ''; ?>">
                未读
                <span class="tab-count"><?php echo $unread_count; ?></span>
            </a>
            <a href="?filter=read" class="filter-tab <?php echo $filter === 'read' ? 'active' : ''; ?>">
                已读
                <span class="tab-count"><?php echo $read_count; ?></span>
            </a>
            <a href="?filter=unreplied" class="filter-tab <?php echo $filter === 'unreplied' ? 'active' : ''; ?>">
                未回复
                <span class="tab-count"><?php echo $unreplied_count; ?></span>
            </a>
            <a href="?filter=replied" class="filter-tab <?php echo $filter === 'replied' ? 'active' : ''; ?>">
                已回复
                <span class="tab-count"><?php echo $replied_count; ?></span>
            </a>
        </div>

        <div class="content-card">
            <div class="card-body" style="padding: 0;">
                <?php if ($success): ?>
                    <div class="alert alert-success" style="margin: 16px;"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <?php if (empty($messages)): ?>
                    <div class="empty-state">暂无留言</div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>类型</th>
                                    <th>姓名</th>
                                    <th>邮箱</th>
                                    <th>电话</th>
                                    <th>留言内容</th>
                                    <th>状态</th>
                                    <th>时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($messages as $message): ?>
                                    <tr class="<?php echo $message['status'] == 0 ? 'unread' : ''; ?>" data-id="<?php echo $message['id']; ?>">
                                        <td>
                                            <?php if ($message['type_name']): ?>
                                                <span style="display: inline-flex; align-items: center; gap: 6px;">
                                                    <?php 
                                                    $icon_type = $message['type_icon_type'] ?? 'default';
                                                    if ($icon_type === 'svg' && !empty($message['type_svg_code'])) {
                                                        echo $message['type_svg_code'];
                                                    } elseif ($icon_type === 'custom' && !empty($message['type_icon_url'])) {
                                                        echo '<img src="' . htmlspecialchars($message['type_icon_url']) . '" alt="' . htmlspecialchars($message['type_name']) . '" style="width: 16px; height: 16px; object-fit: contain;">';
                                                    }
                                                    ?>
                                                    <?php echo htmlspecialchars($message['type_name']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span style="color: #9ca3af;">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($message['name']); ?></td>
                                        <td><?php echo htmlspecialchars($message['email']); ?></td>
                                        <td><?php echo htmlspecialchars($message['phone'] ?: '-'); ?></td>
                                        <td class="message-preview" title="<?php echo htmlspecialchars($message['message']); ?>">
                                            <?php echo htmlspecialchars($message['message']); ?>
                                        </td>
                                        <td>
                                            <div class="status-badges">
                                                <?php if ($message['status'] == 0): ?>
                                                    <span class="badge badge-warning">
                                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                                                        未读
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-success">
                                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                                        已读
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (isset($message['replied']) && $message['replied'] == 1): ?>
                                                    <span class="badge badge-info">
                                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                                        已回复
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">
                                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                                                        待回复
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($message['created_at'])); ?></td>
                                        <td>
                                            <div class="actions">
                                                <button class="btn btn-sm btn-primary" onclick="viewMessage(<?php echo $message['id']; ?>)">查看</button>
                                                <button class="btn btn-sm btn-success" onclick="openReplyModal(<?php echo $message['id']; ?>, '<?php echo htmlspecialchars($message['email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($message['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($message['subject'] ?? '', ENT_QUOTES); ?>')">回复</button>
                                                <?php if ($message['status'] == 0): ?>
                                                    <a href="messages.php?action=read&id=<?php echo $message['id']; ?>" class="btn btn-sm btn-outline">标记已读</a>
                                                <?php endif; ?>
                                                <a href="messages.php?action=delete&id=<?php echo $message['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('确定要删除吗？');">删除</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?filter=<?php echo $filter; ?>&page=<?php echo $page - 1; ?>">上一页</a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="active"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?filter=<?php echo $filter; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?filter=<?php echo $filter; ?>&page=<?php echo $page + 1; ?>">下一页</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
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
    
    <div class="modal-overlay" id="viewModal" style="display: none;">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3>留言详情</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalContent">
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeModal()">关闭</button>
            </div>
        </div>
    </div>
    
    <div class="modal-overlay" id="replyModal" style="display: none;">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h3>回复留言</h3>
                <button class="modal-close" onclick="closeReplyModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>收件人</label>
                    <div id="replyToEmail" style="padding: 8px 0; color: var(--text-primary);"></div>
                </div>
                <div class="form-group">
                    <label>收件人姓名</label>
                    <div id="replyToName" style="padding: 8px 0; color: var(--text-primary);"></div>
                </div>
                <div class="form-group">
                    <label>原主题</label>
                    <div id="replyOriginalSubject" style="padding: 8px 0; color: var(--text-primary);"></div>
                </div>
                <div class="form-group">
                    <label>邮件主题 <span class="required">*</span></label>
                    <input type="text" id="replySubject" placeholder="请输入回复邮件主题">
                </div>
                <div class="form-group">
                    <label>邮件内容 <span class="required">*</span></label>
                    <textarea id="replyContent" rows="8" placeholder="请输入回复内容"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeReplyModal()">取消</button>
                <button class="btn btn-primary" id="sendReplyBtn" onclick="sendReply()">发送回复</button>
            </div>
        </div>
    </div>
    
    <script>
        let needReload = false;
        
        function viewMessage(id) {
            fetch('messages.php?action=view&id=' + id)
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        const data = result.data;
                        const status = parseInt(data.status);
                        const replied = parseInt(data.replied || 0);
                        const statusBadge = status === 0 
                            ? '<span class="badge badge-warning" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 16px;"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>未读</span>' 
                            : '<span class="badge badge-success" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 16px;"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>已读</span>';
                        const repliedBadge = replied === 1 
                            ? '<span class="badge badge-info" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 16px;"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>已回复</span>' 
                            : '<span class="badge badge-secondary" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 16px;"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>待回复</span>';
                        
                        document.getElementById('modalContent').innerHTML = `
                            <div class="detail-row">
                                <label>咨询类型</label>
                                <div>${data.type_name || '-'}</div>
                            </div>
                            <div class="detail-row">
                                <label>姓名</label>
                                <div>${escapeHtml(data.name)}</div>
                            </div>
                            <div class="detail-row">
                                <label>邮箱</label>
                                <div><a href="mailto:${escapeHtml(data.email)}">${escapeHtml(data.email)}</a></div>
                            </div>
                            <div class="detail-row">
                                <label>电话</label>
                                <div>${data.phone ? escapeHtml(data.phone) : '-'}</div>
                            </div>
                            <div class="detail-row">
                                <label>主题</label>
                                <div>${data.subject ? escapeHtml(data.subject) : '-'}</div>
                            </div>
                            <div class="detail-row">
                                <label>留言内容</label>
                                <div style="white-space: pre-wrap; word-break: break-all;">${escapeHtml(data.message)}</div>
                            </div>
                            <div class="detail-row">
                                <label>IP地址</label>
                                <div>${data.ip_address || '-'}</div>
                            </div>
                            <div class="detail-row">
                                <label>提交时间</label>
                                <div>${data.created_at}</div>
                            </div>
                            <div class="detail-row">
                                <label>阅读状态</label>
                                <div>${statusBadge}</div>
                            </div>
                            <div class="detail-row">
                                <label>回复状态</label>
                                <div>${repliedBadge}</div>
                            </div>
                        `;
                        
                        if (data.was_unread) {
                            needReload = true;
                        }
                        
                        document.getElementById('viewModal').style.display = 'flex';
                    } else {
                        alert(result.message || '获取留言详情失败');
                    }
                })
                .catch(() => {
                    alert('网络错误，请重试');
                });
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function closeModal() {
            document.getElementById('viewModal').style.display = 'none';
            if (needReload) {
                location.reload();
            }
        }
        
        let currentReplyData = {
            id: null,
            email: '',
            name: ''
        };
        
        function openReplyModal(id, email, name, originalSubject) {
            currentReplyData = { id, email, name };
            
            document.getElementById('replyToEmail').textContent = email;
            document.getElementById('replyToName').textContent = name;
            document.getElementById('replyOriginalSubject').textContent = originalSubject || '-';
            
            const siteName = '<?php echo addslashes(Settings::get("site_name", "网站")); ?>';
            let replySubject = 'Re: ';
            if (originalSubject) {
                replySubject += originalSubject;
            } else {
                replySubject += '您在' + siteName + '的留言回复';
            }
            document.getElementById('replySubject').value = replySubject;
            
            let replyContent = '亲爱的 ' + name + '，\n\n';
            replyContent += '感谢您在我们网站的留言，我们已收到您的反馈。\n\n';
            replyContent += '以下是我们的回复：\n\n\n';
            replyContent += '此致\n' + siteName + ' 团队';
            document.getElementById('replyContent').value = replyContent;
            
            document.getElementById('replyModal').style.display = 'flex';
        }
        
        function closeReplyModal() {
            document.getElementById('replyModal').style.display = 'none';
            currentReplyData = { id: null, email: '', name: '' };
        }
        
        function sendReply() {
            const subject = document.getElementById('replySubject').value.trim();
            const content = document.getElementById('replyContent').value.trim();
            
            if (!subject) {
                alert('请输入邮件主题');
                return;
            }
            
            if (!content) {
                alert('请输入邮件内容');
                return;
            }
            
            const btn = document.getElementById('sendReplyBtn');
            const originalText = btn.textContent;
            btn.textContent = '发送中...';
            btn.disabled = true;
            
            const formData = new FormData();
            formData.append('action', 'reply_message');
            formData.append('email', currentReplyData.email);
            formData.append('name', currentReplyData.name);
            formData.append('subject', subject);
            formData.append('content', content);
            
            fetch('../api/index.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                btn.textContent = originalText;
                btn.disabled = false;
                
                if (result.success) {
                    return fetch('messages.php?action=reply&id=' + currentReplyData.id)
                        .then(res => res.json())
                        .then(() => {
                            alert('回复邮件发送成功！');
                            closeReplyModal();
                            location.reload();
                        });
                } else {
                    alert('发送失败：' + (result.error || '未知错误'));
                }
            })
            .catch(() => {
                btn.textContent = originalText;
                btn.disabled = false;
                alert('网络错误，请重试');
            });
        }
        
        document.getElementById('replyModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeReplyModal();
            }
        });
        
        document.getElementById('viewModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
                closeReplyModal();
            }
        });
        
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

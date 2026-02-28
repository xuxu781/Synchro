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
$page_title = '团队成员 - ' . $site_name;
$members_title = Settings::get('members_title', '团队成员');
$members_subtitle = Settings::get('members_subtitle', '认识我们的专业团队');
$page_description = $members_subtitle . ' - ' . $site_name;
$page_keywords = '团队成员,团队介绍,专业团队,' . $site_name;
$current_page = 'team';
$extra_css = [];

require_once __DIR__ . '/includes/header.php';

$db = Database::getInstance();
$members = $db->fetchAll("SELECT * FROM `lty-team_members` WHERE status = 1 ORDER BY sort_order ASC, id ASC");
?>

    <section class="hero team-hero">
        <div class="container">
            <h1 class="mission-title fade-in"><?php echo htmlspecialchars($members_title); ?></h1>
            <p class="mission-subtitle fade-in"><?php echo htmlspecialchars($members_subtitle); ?></p>
        </div>
    </section>

    <section class="team-section">
        <div class="container">
            <?php if (empty($members)): ?>
                <div class="team-empty-layout">
                    <div class="team-empty-left">
                        <div class="team-empty">
                            <div class="team-empty-icon">
                                <i class="ri-team-line"></i>
                            </div>
                            <p>暂无团队成员信息</p>
                        </div>
                    </div>
                    <div class="team-empty-right">
                        <div class="verify-box">
                            <div class="verify-header">
                                <h2 class="verify-title">团队成员防伪查询</h2>
                                <p class="verify-desc">请输入姓名、工号、手机号或邮箱进行查询</p>
                            </div>
                            
                            <form class="verify-form" id="verifyForm">
                                <div class="form-group">
                                    <label class="form-label">查询信息</label>
                                    <input type="text" class="form-input" id="queryValue" name="query_value" placeholder="请输入姓名、工号、手机号或邮箱" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">验证码</label>
                                    <div class="captcha-row">
                                        <input type="text" class="form-input captcha-input" id="captchaCode" name="captcha_code" placeholder="请输入验证码" required maxlength="4" autocomplete="off">
                                        <img src="lty-admin/captcha.php" alt="验证码" class="captcha-img" id="captchaImg" onclick="refreshCaptcha()" title="点击刷新验证码">
                                    </div>
                                </div>
                                
                                <button type="submit" class="verify-btn">
                                    <span class="btn-text">立即查询</span>
                                    <span class="btn-loading" style="display: none;">查询中...</span>
                                </button>
                            </form>
                            
                            <div class="verify-result" id="verifyResult" style="display: none;">
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="team-layout">
                    <div class="team-layout-left">
                        <div class="member-list">
                            <?php foreach ($members as $index => $member): ?>
                                <div class="member-item scale-in" style="animation-delay: <?php echo $index * 0.05; ?>s;">
                                    <div class="member-avatar">
                                        <?php 
                                        $memberAvatar = get_avatar_url($member['avatar'], $member['email'], 100);
                                        if ($memberAvatar): ?>
                                            <img src="<?php echo htmlspecialchars($memberAvatar); ?>" alt="<?php echo htmlspecialchars($member['name']); ?>" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                            <div class="member-avatar-placeholder" style="display:none;">
                                                <i class="ri-user-line"></i>
                                            </div>
                                        <?php else: ?>
                                            <div class="member-avatar-placeholder">
                                                <i class="ri-user-line"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="member-info">
                                        <div class="member-main">
                                            <h3 class="member-name"><?php echo htmlspecialchars($member['name']); ?></h3>
                                            <span class="member-position"><?php echo htmlspecialchars($member['position']); ?></span>
                                        </div>
                                        <div class="member-details">
                                            <?php if (!empty($member['employee_id'])): ?>
                                                <div class="member-detail-item">
                                                    <i class="ri-id-card-line"></i>
                                                    <span><?php echo htmlspecialchars($member['employee_id']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($member['phone'])): ?>
                                                <div class="member-detail-item">
                                                    <i class="ri-phone-line"></i>
                                                    <a href="tel:<?php echo htmlspecialchars($member['phone']); ?>"><?php echo htmlspecialchars($member['phone']); ?></a>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($member['email'])): ?>
                                                <div class="member-detail-item">
                                                    <i class="ri-mail-line"></i>
                                                    <a href="mailto:<?php echo htmlspecialchars($member['email']); ?>"><?php echo htmlspecialchars($member['email']); ?></a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($member['bio'])): ?>
                                            <p class="member-bio"><?php echo htmlspecialchars($member['bio']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="team-layout-right">
                        <div class="verify-box">
                            <div class="verify-header">
                                <h2 class="verify-title">团队成员防伪查询</h2>
                                <p class="verify-desc">请输入姓名、工号、手机号或邮箱进行查询</p>
                            </div>
                            
                            <form class="verify-form" id="verifyForm">
                                <div class="form-group">
                                    <label class="form-label">查询信息</label>
                                    <input type="text" class="form-input" id="queryValue" name="query_value" placeholder="请输入姓名、工号、手机号或邮箱" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">验证码</label>
                                    <div class="captcha-row">
                                        <input type="text" class="form-input captcha-input" id="captchaCode" name="captcha_code" placeholder="请输入验证码" required maxlength="4" autocomplete="off">
                                        <img src="lty-admin/captcha.php" alt="验证码" class="captcha-img" id="captchaImg" onclick="refreshCaptcha()" title="点击刷新验证码">
                                    </div>
                                </div>
                                
                                <button type="submit" class="verify-btn">
                                    <span class="btn-text">立即查询</span>
                                    <span class="btn-loading" style="display: none;">查询中...</span>
                                </button>
                            </form>
                            
                            <div class="verify-result" id="verifyResult" style="display: none;">
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <div class="verify-modal" id="verifyModal">
        <div class="verify-modal-overlay" onclick="closeVerifyModal()"></div>
        <div class="verify-modal-content">
            <button class="verify-modal-close" onclick="closeVerifyModal()">&times;</button>
            <div class="verify-modal-body" id="verifyModalBody">
            </div>
        </div>
    </div>

    <script>
    function refreshCaptcha() {
        var captchaImg = document.getElementById('captchaImg');
        if (captchaImg) {
            captchaImg.src = 'lty-admin/captcha.php?t=' + new Date().getTime();
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        const scaleInElements = document.querySelectorAll('.scale-in');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });
        
        scaleInElements.forEach(el => {
            observer.observe(el);
        });
        
        const fadeInElements = document.querySelectorAll('.fade-in');
        fadeInElements.forEach(el => {
            el.classList.add('visible');
        });
        
        const queryValueInput = document.getElementById('queryValue');
        const captchaCodeInput = document.getElementById('captchaCode');
        const verifyForm = document.getElementById('verifyForm');
        const verifyModal = document.getElementById('verifyModal');
        const verifyModalBody = document.getElementById('verifyModalBody');
        const verifyBtn = document.querySelector('.verify-btn');
        const btnText = verifyBtn.querySelector('.btn-text');
        const btnLoading = verifyBtn.querySelector('.btn-loading');
        
        verifyForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const queryValue = queryValueInput.value.trim();
            const captchaCode = captchaCodeInput ? captchaCodeInput.value.trim() : '';
            
            if (!queryValue) {
                return;
            }
            
            if (!captchaCode) {
                showCaptchaError('请输入验证码');
                return;
            }
            
            btnText.style.display = 'none';
            btnLoading.style.display = 'inline';
            verifyBtn.disabled = true;
            
            const formData = new FormData();
            formData.append('action', 'verify');
            formData.append('query_value', queryValue);
            formData.append('captcha_code', captchaCode);
            
            fetch('api/verify.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btnText.style.display = 'inline';
                btnLoading.style.display = 'none';
                verifyBtn.disabled = false;
                
                if (data.captcha_error) {
                    showCaptchaError(data.message);
                    refreshCaptcha();
                    if (captchaCodeInput) {
                        captchaCodeInput.value = '';
                        captchaCodeInput.focus();
                    }
                    return;
                }
                
                hideCaptchaError();
                
                if (data.success) {
                    showResult(data.data);
                } else {
                    showError(data.message, queryValue);
                }
                refreshCaptcha();
                if (captchaCodeInput) {
                    captchaCodeInput.value = '';
                }
            })
            .catch(error => {
                btnText.style.display = 'inline';
                btnLoading.style.display = 'none';
                verifyBtn.disabled = false;
                showError('查询失败，请检查网络连接', queryValue);
                refreshCaptcha();
            });
        });
        
        function showCaptchaError(message) {
            let errorEl = document.getElementById('captchaError');
            if (!errorEl) {
                errorEl = document.createElement('div');
                errorEl.id = 'captchaError';
                errorEl.className = 'captcha-error';
                var captchaRow = document.querySelector('.captcha-row');
                if (captchaRow) {
                    captchaRow.parentNode.insertBefore(errorEl, captchaRow.nextSibling);
                }
            }
            errorEl.textContent = message;
            errorEl.style.display = 'block';
        }
        
        function hideCaptchaError() {
            var errorEl = document.getElementById('captchaError');
            if (errorEl) {
                errorEl.style.display = 'none';
            }
        }
        
        function showResult(member) {
            const avatarHtml = member.avatar 
                ? `<img src="${escapeHtml(member.avatar)}" alt="${escapeHtml(member.name)}" class="result-avatar" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"><div class="result-avatar result-avatar-placeholder" style="display:none;"><i class="ri-user-line"></i></div>`
                : `<div class="result-avatar result-avatar-placeholder"><i class="ri-user-line"></i></div>`;
            
            let statusBadgeHtml = '';
            let resultHeaderHtml = '';
            
            if (member.status == 2) {
                statusBadgeHtml = `<span class="status-badge status-resigned">已离职</span>`;
                resultHeaderHtml = `
                    <div class="result-warning">
                        <div class="result-icon">!</div>
                        <h3 class="result-title">查询成功</h3>
                        <p class="result-message">该成员已从公司离职</p>
                    </div>`;
            } else if (member.status == 0) {
                statusBadgeHtml = `<span class="status-badge status-hidden">已隐藏</span>`;
                resultHeaderHtml = `
                    <div class="result-warning">
                        <div class="result-icon">!</div>
                        <h3 class="result-title">查询成功</h3>
                        <p class="result-message">该成员信息已被隐藏</p>
                    </div>`;
            } else {
                statusBadgeHtml = `<span class="status-badge status-active">在职</span>`;
                resultHeaderHtml = `
                    <div class="result-success">
                        <div class="result-icon">✓</div>
                        <h3 class="result-title">查询成功</h3>
                        <p class="result-message">该成员信息已验证，确认为本公司正式成员</p>
                    </div>`;
            }
            
            let infoHtml = `
                <div class="result-info-item">
                    <span class="result-info-label">姓名：</span>
                    <span class="result-info-value">${escapeHtml(member.name)}</span>
                </div>
                <div class="result-info-item">
                    <span class="result-info-label">职位：</span>
                    <span class="result-info-value">${escapeHtml(member.position)}</span>
                </div>
                <div class="result-info-item">
                    <span class="result-info-label">状态：</span>
                    <span class="result-info-value">${statusBadgeHtml}</span>
                </div>`;
            
            if (member.employee_id) {
                infoHtml += `
                <div class="result-info-item">
                    <span class="result-info-label">工号：</span>
                    <span class="result-info-value">${escapeHtml(member.employee_id)}</span>
                </div>`;
            }
            
            if (member.email) {
                infoHtml += `
                <div class="result-info-item">
                    <span class="result-info-label">邮箱：</span>
                    <span class="result-info-value">${escapeHtml(member.email)}</span>
                </div>`;
            }
            
            if (member.phone) {
                infoHtml += `
                <div class="result-info-item">
                    <span class="result-info-label">手机号：</span>
                    <span class="result-info-value">${escapeHtml(member.phone)}</span>
                </div>`;
            }
            
            if (member.bio) {
                infoHtml += `
                <div class="result-info-item">
                    <span class="result-info-label">简介：</span>
                    <span class="result-info-value">${escapeHtml(member.bio)}</span>
                </div>`;
            }
            
            infoHtml += `
                <div class="result-info-item">
                    <span class="result-info-label">加入时间：</span>
                    <span class="result-info-value">${formatDate(member.created_at)}</span>
                </div>`;
            
            verifyModalBody.innerHTML = `
                ${resultHeaderHtml}
                <div class="result-content">
                    <div class="result-avatar-wrapper">
                        ${avatarHtml}
                    </div>
                    <div class="result-details">
                        ${infoHtml}
                    </div>
                </div>
            `;
            verifyModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function showError(message, queryValue) {
            verifyModalBody.innerHTML = `
                <div class="result-error">
                    <div class="result-icon">✕</div>
                    <h3 class="result-title">查询失败</h3>
                    <p class="result-message">您输入的"${escapeHtml(queryValue)}"不是我们的官方工作人员所有!</p>
                    <p class="result-tip">温馨提示:请检查后重试，谨防被骗!!!</p>
                </div>
            `;
            verifyModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeVerifyModal() {
            verifyModal.classList.remove('active');
            document.body.style.overflow = '';
        }
        
        function formatDate(dateString) {
            const date = new Date(dateString);
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    });
    
    function closeVerifyModal() {
        document.getElementById('verifyModal').classList.remove('active');
        document.body.style.overflow = '';
    }
    </script>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>

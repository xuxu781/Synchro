<?php
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $captcha = strtoupper(trim($_POST['captcha'] ?? ''));
    $error = '';
    $refreshCaptcha = false;
    
    if (empty($username) || empty($password)) {
        $error = '请输入用户名/邮箱和密码';
    } elseif (empty($captcha)) {
        $error = '请输入验证码';
    } elseif (!isset($_SESSION['captcha_code']) || $captcha !== $_SESSION['captcha_code']) {
        $error = '验证码错误';
        unset($_SESSION['captcha_code']);
        $refreshCaptcha = true;
    } elseif (time() - ($_SESSION['captcha_time'] ?? 0) > 300) {
        $error = '验证码已过期，请重新输入';
        unset($_SESSION['captcha_code'], $_SESSION['captcha_time']);
        $refreshCaptcha = true;
    } else {
        $result = Auth::login($username, $password);
        if ($result['success']) {
            unset($_SESSION['captcha_code'], $_SESSION['captcha_time']);
            redirect('lty-admin/');
        } elseif ($result['error'] === 'account_disabled') {
            $error = '您的账户已被禁用，请联系管理员';
            unset($_SESSION['captcha_code']);
            SecurityLog::loginFailed($username, '账户已禁用');
        } else {
            $error = '用户名/邮箱或密码错误';
            unset($_SESSION['captcha_code']);
            SecurityLog::loginFailed($username, '密码错误');
        }
    }
    
    if ($error) {
        $loginError = $error;
        $refreshCaptchaLogin = $refreshCaptcha;
    }
}

if (!Auth::check()) {
    $loginError = $loginError ?? '';
    $refreshCaptchaLogin = $refreshCaptchaLogin ?? false;
    $noPermission = null;
    
    if (isset($_SESSION['no_permission'])) {
        $noPermission = $_SESSION['no_permission'];
        unset($_SESSION['no_permission']);
    }
    ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台登录 - <?php echo htmlspecialchars(Settings::get('site_name', '官网系统')); ?></title>
    <link rel="stylesheet" href="../assets/css/common.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif; min-height: 100vh; background: #0F0F0F; padding: 24px; }
        .login-container { width: 100%; max-width: 900px; display: grid; grid-template-columns: 1fr 1fr; background: #1A1A1A; border-radius: 20px; overflow: hidden; box-shadow: 0 25px 80px rgba(0, 0, 0, 0.5); margin: auto; }
        .login-visual { background: linear-gradient(135deg, #F59E0B 0%, #D97706 50%, #B45309 100%); padding: 40px 36px; display: flex; flex-direction: column; justify-content: space-between; position: relative; overflow: hidden; }
        .login-visual::before { content: ''; position: absolute; top: -50%; right: -50%; width: 100%; height: 100%; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%); }
        .visual-header { position: relative; z-index: 1; }
        .brand { display: flex; align-items: center; gap: 14px; }
        .brand-logo { height: 36px; width: auto; }
        .brand-name { font-size: 22px; font-weight: 800; color: white; letter-spacing: -0.5px; }
        .visual-decor { position: relative; z-index: 1; flex: 1; display: flex; align-items: center; justify-content: center; }
        .decor-circle { width: 200px; height: 200px; border: 2px solid rgba(255,255,255,0.2); border-radius: 50%; position: relative; animation: pulse 3s ease-in-out infinite; }
        .decor-circle::before { content: ''; position: absolute; inset: 30px; border: 2px solid rgba(255,255,255,0.15); border-radius: 50%; }
        .decor-circle::after { content: ''; position: absolute; inset: 60px; border: 2px solid rgba(255,255,255,0.1); border-radius: 50%; }
        .decor-icon { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 60px; height: 60px; background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); border-radius: 18px; display: flex; align-items: center; justify-content: center; }
        .decor-icon svg { width: 30px; height: 30px; color: white; }
        .decor-dots { position: absolute; width: 100%; height: 100%; }
        .decor-dot { position: absolute; width: 8px; height: 8px; background: rgba(255,255,255,0.4); border-radius: 50%; }
        .decor-dot:nth-child(1) { top: 0; left: 50%; transform: translateX(-50%); }
        .decor-dot:nth-child(2) { bottom: 0; left: 50%; transform: translateX(-50%); }
        .decor-dot:nth-child(3) { left: 0; top: 50%; transform: translateY(-50%); }
        .decor-dot:nth-child(4) { right: 0; top: 50%; transform: translateY(-50%); }
        @keyframes pulse { 0%, 100% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.05); opacity: 0.8; } }
        .visual-footer { position: relative; z-index: 1; }
        .footer-text { font-size: 14px; color: rgba(255,255,255,0.7); text-align: center; }
        .footer-sub { font-size: 12px; color: rgba(255,255,255,0.5); text-align: center; margin-top: 8px; }
        .login-form-section { padding: 40px 36px; display: flex; flex-direction: column; justify-content: center; }
        .form-header { margin-bottom: 28px; }
        .form-header h1 { font-size: 24px; font-weight: 700; color: #FFFFFF; margin-bottom: 6px; }
        .form-header p { font-size: 14px; color: #71717A; }
        .error-box { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 10px; padding: 10px 14px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .error-box svg { width: 18px; height: 18px; color: #EF4444; flex-shrink: 0; }
        .error-box span { font-size: 13px; color: #FCA5A5; }
        .no-permission-box { background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); padding: 14px 16px; align-items: flex-start; }
        .no-permission-box svg { width: 22px; height: 22px; color: #F59E0B; margin-top: 2px; }
        .no-permission-content strong { display: block; font-size: 14px; color: #FCD34D; margin-bottom: 4px; }
        .no-permission-content p { font-size: 13px; color: #FBBF24; line-height: 1.5; }
        .no-permission-content span { color: #FCD34D; font-weight: 600; }
        .field { margin-bottom: 16px; }
        .field label { display: block; font-size: 12px; font-weight: 600; color: #A1A1AA; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
        .field-input { position: relative; }
        .field-input > svg:first-of-type { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; color: #52525B; pointer-events: none; }
        .field-input input { width: 100%; height: 44px; padding: 0 14px 0 44px; font-size: 14px; font-family: inherit; color: #FFFFFF; background: #262626; border: 1px solid #3F3F46; border-radius: 10px; transition: all 0.2s; }
        .field-input input:focus { outline: none; border-color: #F59E0B; background: #2A2A2A; box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15); }
        .field-input input:focus ~ svg:first-of-type { color: #F59E0B; }
        .field-input input::placeholder { color: #52525B; }
        .toggle-pw { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #52525B; cursor: pointer; padding: 6px; border-radius: 6px; display: flex; align-items: center; justify-content: center; transition: color 0.2s; }
        .toggle-pw:hover { color: #F59E0B; }
        .toggle-pw svg { position: static; transform: none; width: 20px; height: 20px; }
        .captcha-row { display: flex; gap: 12px; align-items: center; }
        .captcha-input { flex: 1; }
        .captcha-image { flex-shrink: 0; }
        .captcha-image img { height: 44px; border-radius: 10px; cursor: pointer; transition: opacity 0.2s; border: 1px solid #3F3F46; }
        .captcha-image img:hover { opacity: 0.8; }
        .options { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .remember { display: flex; align-items: center; gap: 8px; cursor: pointer; }
        .remember input { width: 16px; height: 16px; accent-color: #F59E0B; cursor: pointer; }
        .remember span { font-size: 13px; color: #A1A1AA; }
        .btn-login { width: 100%; height: 44px; font-size: 14px; font-weight: 700; font-family: inherit; color: #0F0F0F; background: linear-gradient(135deg, #FBBF24 0%, #F59E0B 100%); border: none; border-radius: 10px; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-login:hover { background: linear-gradient(135deg, #FCD34D 0%, #FBBF24 100%); transform: translateY(-2px); box-shadow: 0 10px 30px rgba(245, 158, 11, 0.3); }
        .btn-login:active { transform: translateY(0); }
        .btn-login:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        .btn-login svg { width: 18px; height: 18px; }
        .divider { display: flex; align-items: center; margin: 24px 0; gap: 12px; }
        .divider-line { flex: 1; height: 1px; background: #3F3F46; }
        .divider-text { font-size: 11px; color: #52525B; font-weight: 500; }
        .back-link { text-align: center; }
        .back-link a { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 500; color: #71717A; text-decoration: none; transition: color 0.2s; }
        .back-link a:hover { color: #F59E0B; }
        .back-link a svg { width: 14px; height: 14px; }
        .form-footer { margin-top: auto; padding-top: 24px; text-align: center; }
        .form-footer p { font-size: 12px; color: #52525B; }
        .form-footer a { color: #F59E0B; text-decoration: none; font-weight: 500; }
        @media (max-width: 900px) { .login-container { grid-template-columns: 1fr; max-width: 380px; } .login-visual { display: none; } }
        @media (max-width: 480px) { body { padding: 16px; } .login-form-section { padding: 32px 24px; } .form-header h1 { font-size: 20px; } }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-visual">
            <div class="visual-header">
                <div class="brand">
                    <img src="../assets/images/logo/logo.png" alt="Synchro" class="brand-logo">
                </div>
            </div>
            <div class="visual-decor">
                <div class="decor-circle">
                    <div class="decor-dots">
                        <div class="decor-dot"></div>
                        <div class="decor-dot"></div>
                        <div class="decor-dot"></div>
                        <div class="decor-dot"></div>
                    </div>
                    <div class="decor-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="visual-footer">
                <p class="footer-text">安全 · 高效 · 可靠</p>
                <p class="footer-sub">徐州邻兔跃IT团队提供技术支持</p>
            </div>
        </div>
        <div class="login-form-section">
            <div class="form-header">
                <h1>欢迎回来</h1>
                <p>请登录您的账户以继续使用</p>
            </div>
            <?php if ($noPermission): ?>
                <div class="error-box no-permission-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <circle cx="12" cy="16" r="1"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <div class="no-permission-content">
                        <strong>暂无访问权限</strong>
                        <p>账户 <span><?php echo htmlspecialchars($noPermission['name']); ?></span> 尚未分配角色，请联系管理员</p>
                    </div>
                </div>
            <?php elseif ($loginError): ?>
                <div class="error-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span><?php echo htmlspecialchars($loginError); ?></span>
                </div>
            <?php endif; ?>
            <form method="POST" id="loginForm">
                <div class="field">
                    <label>用户名/邮箱</label>
                    <div class="field-input">
                        <input type="text" id="username" name="username" required autofocus placeholder="请输入用户名或邮箱">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                </div>
                <div class="field">
                    <label>密码</label>
                    <div class="field-input">
                        <input type="password" id="password" name="password" required placeholder="请输入密码" style="padding-right: 44px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <button type="button" class="toggle-pw" onclick="togglePassword()">
                            <svg id="eyeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="field">
                    <label>验证码</label>
                    <div class="captcha-row">
                        <div class="field-input captcha-input">
                            <input type="text" id="captcha" name="captcha" required placeholder="请输入验证码" maxlength="4" autocomplete="off">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            </svg>
                        </div>
                        <div class="captcha-image">
                            <img src="captcha.php" id="captchaImg" alt="验证码" title="点击刷新">
                        </div>
                    </div>
                </div>
                <div class="options">
                    <label class="remember">
                        <input type="checkbox" name="remember">
                        <span>记住登录状态</span>
                    </label>
                </div>
                <button type="submit" class="btn-login" id="submitBtn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                        <polyline points="10 17 15 12 10 7"></polyline>
                        <line x1="15" y1="12" x2="3" y2="12"></line>
                    </svg>
                    登录系统
                </button>
            </form>
            <div class="divider">
                <div class="divider-line"></div>
                <span class="divider-text">其他选项</span>
                <div class="divider-line"></div>
            </div>
            <div class="back-link">
                <a href="../">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    返回网站首页
                </a>
            </div>
            <div class="form-footer">
                <p>© 2026 <a href="https://lintuyue.com" target="_blank"><?php echo htmlspecialchars(Settings::get('site_name', '官网系统')); ?></a> · 徐州邻兔跃IT工作室</p>
            </div>
        </div>
    </div>
    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('eyeIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
            } else {
                input.type = 'password';
                icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
            }
        }
        function refreshCaptcha() {
            const img = document.getElementById('captchaImg');
            img.src = 'captcha.php?t=' + Date.now();
        }
        document.getElementById('captchaImg').addEventListener('click', refreshCaptcha);
        <?php if (!empty($refreshCaptchaLogin)): ?>
        refreshCaptcha();
        <?php endif; ?>
        (function() {
            const savedUsername = localStorage.getItem('remembered_username');
            const savedPassword = localStorage.getItem('remembered_password');
            if (savedUsername) {
                document.getElementById('username').value = savedUsername;
                document.querySelector('input[name="remember"]').checked = true;
            }
            if (savedPassword) {
                document.getElementById('password').value = savedPassword;
            }
        })();
        document.getElementById('loginForm').addEventListener('submit', function() {
            const rememberCheckbox = document.querySelector('input[name="remember"]');
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            if (rememberCheckbox.checked) {
                localStorage.setItem('remembered_username', username);
                localStorage.setItem('remembered_password', password);
            } else {
                localStorage.removeItem('remembered_username');
                localStorage.removeItem('remembered_password');
            }
            const btn = document.getElementById('submitBtn');
            btn.innerHTML = '<svg style="animation: spin 1s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10" stroke-dasharray="32" stroke-dashoffset="32"></circle></svg> 登录中...';
            btn.disabled = true;
        });
        const style = document.createElement('style');
        style.textContent = '@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }';
        document.head.appendChild(style);
    </script>
</body>
</html>
<?php
    exit;
}

$db = Database::getInstance();
$user = Auth::user();
$hasRole = Auth::hasRole();

if (!$hasRole) {
    $permissionName = $user['name'] ?? $user['username'] ?? '未知用户';
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_username']);
    unset($_SESSION['login_ip']);
    unset($_SESSION['login_time']);
    unset($_SESSION['last_activity']);
    unset($_SESSION['user_agent']);
    unset($_SESSION['csrf_token']);
    unset($_SESSION['csrf_token_time']);
    $_SESSION['no_permission'] = [
        'name' => $permissionName,
        'time' => date('H:i')
    ];
    redirect('lty-admin/');
}

$canViewDashboard = Auth::isSuperAdmin() || Auth::hasPermission('dashboard_view');
$canViewMessages = Auth::isSuperAdmin() || Auth::hasPermission('messages_manage');

if ($canViewDashboard) {
    $stats = [
        'members' => $db->fetchOne("SELECT COUNT(*) as count FROM `lty-team_members` WHERE status = 1")['count'],
        'messages' => $db->fetchOne("SELECT COUNT(*) as count FROM `lty-contact_messages` WHERE status = 0")['count'],
        'total_messages' => $db->fetchOne("SELECT COUNT(*) as count FROM `lty-contact_messages`")['count'],
        'media' => $db->fetchOne("SELECT COUNT(*) as count FROM `lty-media_library` WHERE status = 1")['count']
    ];

    $recent_messages = $db->fetchAll("SELECT * FROM `lty-contact_messages` ORDER BY status ASC, created_at DESC LIMIT 5");

    $systemInfo = [
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'mysql_version' => $db->fetchOne("SELECT VERSION() as version")['version'],
        'upload_max_size' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time') . '秒',
        'server_time' => date('Y-m-d H:i:s'),
        'timezone' => date_default_timezone_get(),
        'disk_free_space' => function_exists('disk_free_space') ? round(disk_free_space(__DIR__) / 1024 / 1024 / 1024, 2) . ' GB' : 'Unknown',
        'system_version' => '1.0'
    ];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>仪表盘 - <?php echo htmlspecialchars(Settings::get('site_name', '官网系统')); ?></title>
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <?php 
        $success = get_flash_message('success');
        $error = get_flash_message('error');
        if ($success || $error): 
            $flash = $success ?: $error;
            $flashType = $success ? 'success' : 'error';
        ?>
        <div class="alert alert-<?php echo $flashType; ?>" style="margin: 20px;">
            <?php echo htmlspecialchars($flash); ?>
        </div>
        <?php endif; ?>
        <div class="dash-header">
            <div class="dash-header-left">
                <div class="dash-greeting">
                    <?php
                    $hour = date('H');
                    if ($hour >= 5 && $hour < 12) {
                        $greet = '早上好';
                    } elseif ($hour >= 12 && $hour < 18) {
                        $greet = '下午好';
                    } else {
                        $greet = '晚上好';
                    }
                    ?>
                    <div class="greet-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <?php if ($hour >= 5 && $hour < 12): ?>
                                <circle cx="12" cy="12" r="5"/>
                                <line x1="12" y1="1" x2="12" y2="3"/>
                                <line x1="12" y1="21" x2="12" y2="23"/>
                                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                                <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                                <line x1="1" y1="12" x2="3" y2="12"/>
                                <line x1="21" y1="12" x2="23" y2="12"/>
                                <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                                <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                            <?php elseif ($hour >= 12 && $hour < 18): ?>
                                <circle cx="12" cy="12" r="5"/>
                                <line x1="12" y1="1" x2="12" y2="3"/>
                                <line x1="12" y1="21" x2="12" y2="23"/>
                                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                                <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                                <line x1="1" y1="12" x2="3" y2="12"/>
                                <line x1="21" y1="12" x2="23" y2="12"/>
                                <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                                <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                                <path d="M17 6l2 2-2 2"/>
                            <?php else: ?>
                                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                            <?php endif; ?>
                        </svg>
                    </div>
                    <div class="greet-text">
                        <span class="greet-word"><?php echo $greet; ?></span>
                        <span class="greet-name"><?php echo htmlspecialchars($user['name'] ?? $user['username'] ?? '管理员'); ?></span>
                    </div>
                </div>
                <div class="dash-date">
                    <span class="date-day"><?php echo date('d'); ?></span>
                    <div class="date-info">
                        <span class="date-month"><?php echo date('M'); ?></span>
                        <span class="date-week">
                            <?php
                            $weekdays = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'];
                            echo $weekdays[date('w')];
                            ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="dash-header-right">
                <div class="header-time-display">
                    <div class="htd-time" id="realtime-clock"><?php echo date('H:i'); ?></div>
                    <div class="htd-date" id="realtime-date"><?php echo date('Y年m月d日'); ?></div>
                </div>
            </div>
        </div>

        <?php if (!$canViewDashboard): ?>
        <div class="dash-body" style="display: flex; align-items: center; justify-content: center; min-height: 400px;">
            <div style="text-align: center; padding: 40px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 80px; height: 80px; color: var(--text-muted); margin-bottom: 20px;">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                    <line x1="3" y1="9" x2="21" y2="9"/>
                    <line x1="9" y1="21" x2="9" y2="9"/>
                </svg>
                <h3 style="color: var(--text-muted); margin-bottom: 10px;">暂无数据查看权限</h3>
                <p style="color: var(--text-muted); font-size: 14px;">您没有仪表盘查看权限，无法查看统计数据。</p>
                <p style="color: var(--text-muted); font-size: 14px;">请联系管理员为您分配相应权限。</p>
            </div>
        </div>
        <?php else: ?>
        <div class="dash-body">
            <div class="dash-left">
                <div class="stats-overview">
                    <div class="stats-header">
                        <h2>数据概览</h2>
                        <button class="refresh-btn" onclick="location.reload()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="23 4 23 10 17 10"/>
                                <polyline points="1 20 1 14 7 14"/>
                                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                            </svg>
                            刷新
                        </button>
                    </div>
                    <div class="stats-grid-new">
                        <div class="stat-block">
                            <div class="stat-block-icon members">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                </svg>
                            </div>
                            <div class="stat-block-content">
                                <span class="stat-block-num"><?php echo $stats['members']; ?></span>
                                <span class="stat-block-title">团队成员</span>
                            </div>
                            <div class="stat-block-bar"></div>
                        </div>
                        <div class="stat-block">
                            <div class="stat-block-icon messages">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                </svg>
                            </div>
                            <div class="stat-block-content">
                                <span class="stat-block-num"><?php echo $stats['messages']; ?></span>
                                <span class="stat-block-title">未读留言</span>
                            </div>
                            <div class="stat-block-bar orange"></div>
                        </div>
                        <div class="stat-block">
                            <div class="stat-block-icon media">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                    <circle cx="8.5" cy="8.5" r="1.5"/>
                                    <polyline points="21 15 16 10 5 21"/>
                                </svg>
                            </div>
                            <div class="stat-block-content">
                                <span class="stat-block-num"><?php echo $stats['media']; ?></span>
                                <span class="stat-block-title">媒体文件</span>
                            </div>
                            <div class="stat-block-bar green"></div>
                        </div>
                        <div class="stat-block">
                            <div class="stat-block-icon total">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                    <polyline points="22,6 12,13 2,6"/>
                                </svg>
                            </div>
                            <div class="stat-block-content">
                                <span class="stat-block-num"><?php echo $stats['total_messages']; ?></span>
                                <span class="stat-block-title">总留言</span>
                            </div>
                            <div class="stat-block-bar purple"></div>
                        </div>
                    </div>
                </div>

                <div class="system-info-card">
                    <div class="card-title-bar">
                        <h3>系统信息</h3>
                    </div>
                    <div class="sys-info-wrap">
                        <div class="sys-row">
                            <div class="sys-label">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="12" y1="16" x2="12" y2="12"/>
                                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                                </svg>
                                系统版本
                            </div>
                            <div class="sys-value">v<?php echo htmlspecialchars($systemInfo['system_version']); ?></div>
                        </div>
                        <div class="sys-row">
                            <div class="sys-label">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="16 18 22 12 16 6"/>
                                    <polyline points="8 6 2 12 8 18"/>
                                </svg>
                                PHP版本
                            </div>
                            <div class="sys-value"><?php echo htmlspecialchars($systemInfo['php_version']); ?></div>
                        </div>
                        <div class="sys-row">
                            <div class="sys-label">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <ellipse cx="12" cy="5" rx="9" ry="3"/>
                                    <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/>
                                    <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
                                </svg>
                                MySQL
                            </div>
                            <div class="sys-value"><?php echo htmlspecialchars($systemInfo['mysql_version']); ?></div>
                        </div>
                        <div class="sys-row">
                            <div class="sys-label">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="2" y="2" width="20" height="8" rx="2" ry="2"/>
                                    <rect x="2" y="14" width="20" height="8" rx="2" ry="2"/>
                                    <line x1="6" y1="6" x2="6.01" y2="6"/>
                                    <line x1="6" y1="18" x2="6.01" y2="18"/>
                                </svg>
                                服务器
                            </div>
                            <div class="sys-value truncate"><?php echo htmlspecialchars($systemInfo['server_software']); ?></div>
                        </div>
                        <div class="sys-row">
                            <div class="sys-label">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                                </svg>
                                上传限制
                            </div>
                            <div class="sys-value"><?php echo htmlspecialchars($systemInfo['upload_max_size']); ?></div>
                        </div>
                        <div class="sys-row">
                            <div class="sys-label">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <polyline points="12 6 12 12 16 14"/>
                                </svg>
                                服务器时间
                            </div>
                            <div class="sys-value"><?php echo htmlspecialchars($systemInfo['server_time']); ?></div>
                        </div>
                        <div class="sys-row">
                            <div class="sys-label">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21.21 15.89A10 10 0 1 1 8 2.83"/>
                                    <path d="M22 12A10 10 0 0 0 12 2v10z"/>
                                </svg>
                                磁盘剩余
                            </div>
                            <div class="sys-value"><?php echo htmlspecialchars($systemInfo['disk_free_space']); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dash-right">
                <div class="messages-card">
                    <div class="card-title-bar">
                        <h3>最新留言</h3>
                        <?php if ($canViewMessages): ?>
                        <a href="messages.php" class="view-all-link">
                            查看全部
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"/>
                            </svg>
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="messages-wrap">
                        <?php if (!$canViewMessages): ?>
                            <div class="no-messages">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                    <circle cx="12" cy="16" r="1"/>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                </svg>
                                <p>没有授权</p>
                            </div>
                        <?php elseif (empty($recent_messages)): ?>
                            <div class="no-messages">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                </svg>
                                <p>暂无留言</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_messages as $msg): ?>
                                <div class="msg-item" onclick="viewMessage(<?php echo $msg['id']; ?>)">
                                    <div class="msg-avatar">
                                        <?php echo mb_substr($msg['name'], 0, 1); ?>
                                    </div>
                                    <div class="msg-main">
                                        <div class="msg-top">
                                            <span class="msg-name"><?php echo htmlspecialchars($msg['name']); ?></span>
                                            <span class="msg-time"><?php echo date('m-d H:i', strtotime($msg['created_at'])); ?></span>
                                        </div>
                                        <p class="msg-preview"><?php echo htmlspecialchars($msg['subject'] ?: $msg['email']); ?></p>
                                    </div>
                                    <div class="msg-tag <?php echo $msg['status'] == 0 ? 'unread' : 'read'; ?>">
                                        <?php echo $msg['status'] == 0 ? '未读' : '已读'; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="quick-actions-card">
                    <div class="card-title-bar">
                        <h3>快捷操作</h3>
                    </div>
                    <div class="actions-grid">
                        <a href="members.php" class="action-btn">
                            <div class="action-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                    <circle cx="8.5" cy="7" r="4"/>
                                    <line x1="20" y1="8" x2="20" y2="14"/>
                                    <line x1="23" y1="11" x2="17" y2="11"/>
                                </svg>
                            </div>
                            <span>添加成员</span>
                        </a>
                        <a href="media.php" class="action-btn">
                            <div class="action-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                    <circle cx="8.5" cy="8.5" r="1.5"/>
                                    <polyline points="21 15 16 10 5 21"/>
                                </svg>
                            </div>
                            <span>上传媒体</span>
                        </a>
                        <a href="messages.php" class="action-btn">
                            <div class="action-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                    <polyline points="22,6 12,13 2,6"/>
                                </svg>
                            </div>
                            <span>查看留言</span>
                        </a>
                        <a href="settings.php" class="action-btn">
                            <div class="action-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="3"/>
                                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                                </svg>
                            </div>
                            <span>系统设置</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

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

    <script>
        let currentMessageId = null;
        
        function updateRealtimeClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            
            const clockEl = document.getElementById('realtime-clock');
            const dateEl = document.getElementById('realtime-date');
            
            if (clockEl) {
                clockEl.textContent = hours + ':' + minutes;
            }
            if (dateEl) {
                dateEl.textContent = year + '年' + month + '月' + day + '日';
            }
        }
        
        setInterval(updateRealtimeClock, 1000);
        
        function viewMessage(id) {
            currentMessageId = id;
            fetch('messages.php?action=view&id=' + id)
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        const data = result.data;
                        const status = parseInt(data.status);
                        const statusBadge = status === 0 
                            ? '<span class="badge badge-warning">未读</span>' 
                            : '<span class="badge badge-success">已读</span>';
                        
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
                                <label>状态</label>
                                <div>${statusBadge}</div>
                            </div>
                        `;
                        
                        if (data.was_unread) {
                            updateMessageStatus(id);
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
            currentMessageId = null;
        }
        
        function updateMessageStatus(id) {
            updateUnreadCount();
            
            const msgItems = document.querySelectorAll('.msg-item');
            let targetItem = null;
            
            msgItems.forEach(function(item) {
                if (item.getAttribute('onclick').includes('viewMessage(' + id + ')')) {
                    const tag = item.querySelector('.msg-tag');
                    if (tag && tag.classList.contains('unread')) {
                        tag.classList.remove('unread');
                        tag.classList.add('read');
                        tag.textContent = '已读';
                        targetItem = item;
                    }
                }
            });
            
            // 如果找到了目标项且它是从"未读"变为"已读"，则重新排序
            if (targetItem) {
                const messagesWrap = document.querySelector('.messages-wrap');
                if (messagesWrap) {
                    // 移除并重新添加到末尾（已读留言在后面）
                    targetItem.remove();
                    messagesWrap.appendChild(targetItem);
                }
            }
        }
        
        function updateUnreadCount() {
            const statBlock = document.querySelector('.stat-block-icon.messages').closest('.stat-block');
            const numEl = statBlock.querySelector('.stat-block-num');
            let count = parseInt(numEl.textContent);
            if (count > 0) {
                const newCount = count - 1;
                numEl.textContent = newCount;
                
                const navBadges = document.querySelectorAll('.nav-badge');
                navBadges.forEach(function(badge) {
                    badge.textContent = newCount;
                    if (newCount <= 0) {
                        badge.style.display = 'none';
                    }
                });
                
                const actionBadges = document.querySelectorAll('.action-badge');
                actionBadges.forEach(function(badge) {
                    badge.textContent = newCount;
                    if (newCount <= 0) {
                        badge.style.display = 'none';
                    }
                });
            }
        }
        
        document.getElementById('viewModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>

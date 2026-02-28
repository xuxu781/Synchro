<?php
require_once __DIR__ . '/../includes/functions.php';

if (Auth::check()) {
    redirect('lty-admin/');
}

$error = '';
$noPermission = null;
$refreshCaptcha = false;

if (isset($_SESSION['no_permission'])) {
    $noPermission = $_SESSION['no_permission'];
    unset($_SESSION['no_permission']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $captcha = strtoupper(trim($_POST['captcha'] ?? ''));
    
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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            background: #0F0F0F;
            padding: 24px;
        }

        .login-container {
            width: 100%;
            max-width: 900px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: #1A1A1A;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.5);
            margin: auto;
        }

        .login-visual {
            background: linear-gradient(135deg, #F59E0B 0%, #D97706 50%, #B45309 100%);
            padding: 40px 36px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .login-visual::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
        }

        .visual-header {
            position: relative;
            z-index: 1;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-logo {
            height: 36px;
            width: auto;
        }

        .brand-name {
            font-size: 22px;
            font-weight: 800;
            color: white;
            letter-spacing: -0.5px;
        }

        .visual-decor {
            position: relative;
            z-index: 1;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .decor-circle {
            width: 200px;
            height: 200px;
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 50%;
            position: relative;
            animation: pulse 3s ease-in-out infinite;
        }

        .decor-circle::before {
            content: '';
            position: absolute;
            inset: 30px;
            border: 2px solid rgba(255,255,255,0.15);
            border-radius: 50%;
        }

        .decor-circle::after {
            content: '';
            position: absolute;
            inset: 60px;
            border: 2px solid rgba(255,255,255,0.1);
            border-radius: 50%;
        }

        .decor-icon {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .decor-icon svg {
            width: 30px;
            height: 30px;
            color: white;
        }

        .decor-dots {
            position: absolute;
            width: 100%;
            height: 100%;
        }

        .decor-dot {
            position: absolute;
            width: 8px;
            height: 8px;
            background: rgba(255,255,255,0.4);
            border-radius: 50%;
        }

        .decor-dot:nth-child(1) { top: 0; left: 50%; transform: translateX(-50%); }
        .decor-dot:nth-child(2) { bottom: 0; left: 50%; transform: translateX(-50%); }
        .decor-dot:nth-child(3) { left: 0; top: 50%; transform: translateY(-50%); }
        .decor-dot:nth-child(4) { right: 0; top: 50%; transform: translateY(-50%); }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
        }

        .visual-footer {
            position: relative;
            z-index: 1;
        }

        .footer-text {
            font-size: 14px;
            color: rgba(255,255,255,0.7);
            text-align: center;
        }

        .footer-sub {
            font-size: 12px;
            color: rgba(255,255,255,0.5);
            text-align: center;
            margin-top: 8px;
        }

        .login-form-section {
            padding: 40px 36px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-header {
            margin-bottom: 28px;
        }

        .form-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: #FFFFFF;
            margin-bottom: 6px;
        }

        .form-header p {
            font-size: 14px;
            color: #71717A;
        }

        .error-box {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 10px;
            padding: 10px 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .error-box svg {
            width: 18px;
            height: 18px;
            color: #EF4444;
            flex-shrink: 0;
        }

        .error-box span {
            font-size: 13px;
            color: #FCA5A5;
        }
        
        .no-permission-box {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.3);
            padding: 14px 16px;
            align-items: flex-start;
        }
        
        .no-permission-box svg {
            width: 22px;
            height: 22px;
            color: #F59E0B;
            margin-top: 2px;
        }
        
        .no-permission-content strong {
            display: block;
            font-size: 14px;
            color: #FCD34D;
            margin-bottom: 4px;
        }
        
        .no-permission-content p {
            font-size: 13px;
            color: #FBBF24;
            line-height: 1.5;
        }
        
        .no-permission-content span {
            color: #FCD34D;
            font-weight: 600;
        }

        .field {
            margin-bottom: 16px;
        }

        .field label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #A1A1AA;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .field-input {
            position: relative;
        }

        .field-input > svg:first-of-type {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            color: #52525B;
            pointer-events: none;
        }

        .field-input input {
            width: 100%;
            height: 44px;
            padding: 0 14px 0 44px;
            font-size: 14px;
            font-family: inherit;
            color: #FFFFFF;
            background: #262626;
            border: 1px solid #3F3F46;
            border-radius: 10px;
            transition: all 0.2s;
        }

        .field-input input:focus {
            outline: none;
            border-color: #F59E0B;
            background: #2A2A2A;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15);
        }

        .field-input input:focus ~ svg:first-of-type {
            color: #F59E0B;
        }

        .field-input input::placeholder {
            color: #52525B;
        }

        .toggle-pw {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #52525B;
            cursor: pointer;
            padding: 6px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s;
        }

        .toggle-pw:hover {
            color: #F59E0B;
        }

        .toggle-pw svg {
            position: static;
            transform: none;
            width: 20px;
            height: 20px;
        }

        .captcha-row {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .captcha-input {
            flex: 1;
        }

        .captcha-image {
            flex-shrink: 0;
        }

        .captcha-image img {
            height: 44px;
            border-radius: 10px;
            cursor: pointer;
            transition: opacity 0.2s;
            border: 1px solid #3F3F46;
        }

        .captcha-image img:hover {
            opacity: 0.8;
        }

        .options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .remember input {
            width: 16px;
            height: 16px;
            accent-color: #F59E0B;
            cursor: pointer;
        }

        .remember span {
            font-size: 13px;
            color: #A1A1AA;
        }

        .btn-login {
            width: 100%;
            height: 44px;
            font-size: 14px;
            font-weight: 700;
            font-family: inherit;
            color: #0F0F0F;
            background: linear-gradient(135deg, #FBBF24 0%, #F59E0B 100%);
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #FCD34D 0%, #FBBF24 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(245, 158, 11, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .btn-login svg {
            width: 18px;
            height: 18px;
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 24px 0;
            gap: 12px;
        }

        .divider-line {
            flex: 1;
            height: 1px;
            background: #3F3F46;
        }

        .divider-text {
            font-size: 11px;
            color: #52525B;
            font-weight: 500;
        }

        .back-link {
            text-align: center;
        }

        .back-link a {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 500;
            color: #71717A;
            text-decoration: none;
            transition: color 0.2s;
        }

        .back-link a:hover {
            color: #F59E0B;
        }

        .back-link a svg {
            width: 14px;
            height: 14px;
        }

        .form-footer {
            margin-top: auto;
            padding-top: 24px;
            text-align: center;
        }

        .form-footer p {
            font-size: 12px;
            color: #52525B;
        }

        .form-footer a {
            color: #F59E0B;
            text-decoration: none;
            font-weight: 500;
        }

        @media (max-width: 900px) {
            .login-container {
                grid-template-columns: 1fr;
                max-width: 380px;
            }

            .login-visual {
                display: none;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 16px;
            }

            .login-form-section {
                padding: 32px 24px;
            }

            .form-header h1 {
                font-size: 20px;
            }
        }
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
            <?php elseif ($error): ?>
                <div class="error-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span><?php echo htmlspecialchars($error); ?></span>
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

        <?php if ($refreshCaptcha): ?>
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

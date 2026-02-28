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
session_start();

$error = '';
$success = '';
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'install';
$dbCheckResult = null;

function getRequiredTablesFromSql($sqlFile) {
    $tables = [];
    if (file_exists($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        if (preg_match_all('/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+`([^`]+)`/i', $sql, $matches)) {
            foreach ($matches[1] as $table) {
                $tables[$table] = $table;
            }
        }
    }
    return $tables;
}

function getTableFieldsFromSql($sqlFile) {
    $tableFields = [];
    if (!file_exists($sqlFile)) {
        return $tableFields;
    }
    
    $sql = file_get_contents($sqlFile);
    
    if (preg_match_all('/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+`([^`]+)`\s*\((.*?)\)\s*ENGINE/si', $sql, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $tableName = $match[1];
            $columnsStr = $match[2];
            
            $fields = [];
            $prevField = null;
            
            $lines = explode("\n", $columnsStr);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || preg_match('/^(PRIMARY|UNIQUE|KEY|INDEX|CONSTRAINT|FOREIGN)/i', $line)) {
                    continue;
                }
                
                if (preg_match('/^`([^`]+)`\s+(.+?)(?:,\s*)?$/i', $line, $colMatch)) {
                    $fieldName = $colMatch[1];
                    $fieldDef = rtrim($colMatch[2], ',');
                    
                    $fields[] = [$fieldName, $fieldDef, $prevField];
                    $prevField = $fieldName;
                }
            }
            
            if (!empty($fields)) {
                $tableFields[$tableName] = $fields;
            }
        }
    }
    
    return $tableFields;
}

$sqlFile = __DIR__ . '/database.sql';
$requiredTables = getRequiredTablesFromSql($sqlFile);

function checkDatabase($pdo, $requiredTables) {
    $result = [
        'has_database' => true,
        'existing_tables' => [],
        'missing_tables' => [],
        'table_details' => [],
        'has_any_table' => false,
        'is_complete' => false
    ];
    
    try {
        $stmt = $pdo->query("SHOW TABLES");
        $rows = $stmt->fetchAll(PDO::FETCH_NUM);
        $stmt->closeCursor();
        
        foreach ($rows as $row) {
            $result['existing_tables'][] = $row[0];
        }
        
        $result['has_any_table'] = count($result['existing_tables']) > 0;
        
        foreach ($requiredTables as $table => $desc) {
            if (in_array($table, $result['existing_tables'])) {
                $colStmt = $pdo->query("SHOW COLUMNS FROM `{$table}`");
                $colRows = $colStmt->fetchAll(PDO::FETCH_ASSOC);
                $colStmt->closeCursor();
                
                $columns = [];
                foreach ($colRows as $col) {
                    $columns[] = $col['Field'];
                }
                $result['table_details'][$table] = [
                    'exists' => true,
                    'columns' => $columns,
                    'description' => $desc
                ];
            } else {
                $result['missing_tables'][] = $table;
                $result['table_details'][$table] = [
                    'exists' => false,
                    'columns' => [],
                    'description' => $desc
                ];
            }
        }
        
        $result['is_complete'] = empty($result['missing_tables']);
        
    } catch (PDOException $e) {
        $result['has_database'] = false;
        $result['error'] = $e->getMessage();
    }
    
    return $result;
}

function columnExists($pdo, $table, $column) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
        $count = $stmt->rowCount();
        $stmt->closeCursor();
        return $count > 0;
    } catch (PDOException $e) {
        return false;
    }
}

function addColumn($pdo, $table, $column, $definition, $after = null) {
    if (!columnExists($pdo, $table, $column)) {
        $afterSql = $after ? " AFTER `{$after}`" : '';
        $sql = "ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}{$afterSql}";
        try {
            $pdo->exec($sql);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    return false;
}

function updateDatabaseFields($pdo) {
    global $sqlFile;
    
    $updates = getTableFieldsFromSql($sqlFile);
    
    foreach ($updates as $table => $columns) {
        try {
            $pdo->query("SELECT 1 FROM `{$table}` LIMIT 1");
            foreach ($columns as $col) {
                addColumn($pdo, $table, $col[0], $col[1], $col[2]);
            }
        } catch (PDOException $e) {
        }
    }
}

function executeSqlFile($pdo, $sql) {
    $sql = str_replace("\r\n", "\n", $sql);
    $sql = str_replace("\r", "\n", $sql);
    
    $statements = [];
    $current = '';
    $lines = explode("\n", $sql);
    
    foreach ($lines as $line) {
        $trimmed = trim($line);
        
        if (empty($trimmed) || strpos($trimmed, '--') === 0) {
            continue;
        }
        
        if (preg_match('/^(START\s+TRANSACTION|COMMIT|DELIMITER)/i', $trimmed)) {
            continue;
        }
        
        $current .= ' ' . $trimmed;
        
        if (substr($trimmed, -1) === ';') {
            $parts = explode(';', $current);
            foreach ($parts as $part) {
                $part = trim($part);
                if (!empty($part)) {
                    $statements[] = $part;
                }
            }
            $current = '';
        }
    }
    
    if (!empty(trim($current))) {
        $parts = explode(';', $current);
        foreach ($parts as $part) {
            $part = trim($part);
            if (!empty($part)) {
                $statements[] = $part;
            }
        }
    }
    
    $pdo->exec("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");
    $pdo->exec("SET time_zone = '+08:00'");
    $pdo->exec("SET NAMES utf8mb4");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    foreach ($statements as $statement) {
        if (empty($statement)) {
            continue;
        }
        
        if (preg_match('/^(START\s+TRANSACTION|COMMIT|DELIMITER)/i', $statement)) {
            continue;
        }
        
        try {
            $pdo->exec($statement);
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            if (strpos($errorMsg, 'already exists') === false && 
                strpos($errorMsg, 'Duplicate') === false &&
                strpos($errorMsg, 'Unknown column') === false &&
                strpos($errorMsg, "doesn't exist") === false) {
            }
        }
    }
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
}

function executeSqlFileWithMysqli($host, $user, $pass, $dbname, $sqlFile) {
    $mysqli = new mysqli($host, $user, $pass, $dbname);
    
    if ($mysqli->connect_error) {
        throw new Exception('数据库连接失败: ' . $mysqli->connect_error);
    }
    
    $mysqli->set_charset('utf8mb4');
    $mysqli->query("SET FOREIGN_KEY_CHECKS = 0");
    $mysqli->query("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");
    
    $sql = file_get_contents($sqlFile);
    
    if ($mysqli->multi_query($sql)) {
        do {
            if ($result = $mysqli->store_result()) {
                $result->free();
            }
        } while ($mysqli->next_result());
    }
    
    if ($mysqli->error) {
        error_log('MySQLi Error: ' . $mysqli->error);
    }
    
    $mysqli->query("SET FOREIGN_KEY_CHECKS = 1");
    $mysqli->close();
    
    return true;
}

if (file_exists('lty-admin/config/database.php') && !isset($_SESSION['install_complete'])) {
    require_once 'lty-admin/config/database.php';
    
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        
        $dbCheckResult = checkDatabase($pdo, $requiredTables);
        
        if ($dbCheckResult['is_complete']) {
            header('Location: /');
            exit;
        } elseif ($dbCheckResult['has_any_table']) {
            $mode = 'update';
            $step = 4;
        }
    } catch (PDOException $e) {
        $error = '数据库连接失败：' . $e->getMessage();
    }
}

if (isset($_SESSION['install_complete'])) {
    unset($_SESSION['install_complete']);
}

$uploadsDir = __DIR__ . '/uploads';
if (!is_dir($uploadsDir)) {
    @mkdir($uploadsDir, 0755, true);
}

$envCheck = [
    'php_version' => version_compare(PHP_VERSION, '7.4.0', '>='),
    'php_version_recommend' => version_compare(PHP_VERSION, '8.0.0', '>='),
    'pdo_mysql' => extension_loaded('pdo_mysql') || (class_exists('PDO') && in_array('mysql', PDO::getAvailableDrivers())),
    'openssl' => extension_loaded('openssl'),
    'mbstring' => extension_loaded('mbstring'),
    'gd' => extension_loaded('gd') && function_exists('gd_info'),
    'uploads_writable' => is_dir($uploadsDir) && is_writable($uploadsDir),
    'config_writable' => is_writable(__DIR__ . '/lty-admin/config') || is_writable(__DIR__ . '/lty-admin'),
];

$envCheckRequired = ['php_version', 'pdo_mysql', 'openssl', 'mbstring', 'gd', 'uploads_writable', 'config_writable'];

$envCheckAllPass = true;
foreach ($envCheckRequired as $key) {
    if (!$envCheck[$key]) {
        $envCheckAllPass = false;
        break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_database'])) {
        try {
            require_once 'lty-admin/config/database.php';
            
            executeSqlFileWithMysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, __DIR__ . '/database.sql');
            
            header('Location: install.php?step=7&mode=update');
            exit;
        } catch (Exception $e) {
            $error = '更新失败：' . $e->getMessage();
        }
    }
    
    if ($step === 3) {
        $db_host = $_POST['db_host'] ?? 'localhost';
        $db_name = $_POST['db_name'] ?? '';
        $db_user = $_POST['db_user'] ?? '';
        $db_pass = $_POST['db_pass'] ?? '';
        
        try {
            $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            
            $stmt = $pdo->query("SHOW DATABASES LIKE '$db_name'");
            $dbExists = $stmt->rowCount() > 0;
            $stmt->closeCursor();
            
            if ($dbExists) {
                $pdo->exec("USE `$db_name`");
                $stmt = $pdo->query("SHOW TABLES");
                $rows = $stmt->fetchAll(PDO::FETCH_NUM);
                $stmt->closeCursor();
                
                $existingTables = [];
                foreach ($rows as $row) {
                    $existingTables[] = $row[0];
                }
                
                if (count($existingTables) > 0) {
                    $_SESSION['install_db'] = [
                        'host' => $db_host,
                        'name' => $db_name,
                        'user' => $db_user,
                        'pass' => $db_pass,
                        'existing_tables' => $existingTables,
                        'has_existing_data' => true
                    ];
                    header('Location: install.php?step=3&check=exists');
                    exit;
                }
            }
            
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            $_SESSION['install_db'] = [
                'host' => $db_host,
                'name' => $db_name,
                'user' => $db_user,
                'pass' => $db_pass,
                'has_existing_data' => false
            ];
            
            header('Location: install.php?step=4');
            exit;
        } catch (PDOException $e) {
            $error = '数据库连接失败：' . $e->getMessage();
        }
    }
    
    if (isset($_POST['confirm_overwrite']) && $step === 3) {
        $_SESSION['install_db']['overwrite'] = true;
        header('Location: install.php?step=4');
        exit;
    }
    
    if (isset($_POST['confirm_update']) && $step === 3) {
        $_SESSION['install_db']['update_mode'] = true;
        header('Location: install.php?step=4');
        exit;
    } elseif ($step === 4) {
        $admin_username = $_POST['admin_username'] ?? 'admin';
        $admin_password = $_POST['admin_password'] ?? '';
        $admin_password_confirm = $_POST['admin_password_confirm'] ?? '';
        $admin_email = $_POST['admin_email'] ?? '';
        
        if (empty($admin_password)) {
            $error = '请输入管理员密码';
        } elseif (strlen($admin_password) < 6) {
            $error = '密码长度至少6位';
        } elseif ($admin_password !== $admin_password_confirm) {
            $error = '两次输入的密码不一致';
        } else {
            $_SESSION['install_admin'] = [
                'username' => $admin_username,
                'password' => password_hash($admin_password, PASSWORD_DEFAULT),
                'email' => $admin_email
            ];
            header('Location: install.php?step=5');
            exit;
        }
    } elseif ($step === 5) {
        $site_name = $_POST['site_name'] ?? 'Synchro';
        $site_url = $_POST['site_url'] ?? '';
        
        if (empty($site_url)) {
            $error = '请输入网站域名';
        } else {
            $_SESSION['install_site'] = [
                'name' => $site_name,
                'url' => $site_url
            ];
            header('Location: install.php?step=6');
            exit;
        }
    } elseif ($step === 6) {
        $db_config = $_SESSION['install_db'] ?? null;
        $admin_config = $_SESSION['install_admin'] ?? null;
        $site_config = $_SESSION['install_site'] ?? null;
        
        if (!$db_config || !$admin_config || !$site_config) {
            $error = '安装信息不完整，请重新开始安装';
            header('Location: install.php?step=1');
            exit;
        }
        
        try {
            $pdo = new PDO("mysql:host={$db_config['host']};dbname={$db_config['name']}", $db_config['user'], $db_config['pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            $pdo->exec("SET NAMES utf8mb4");
            
            if (isset($db_config['overwrite']) && $db_config['overwrite']) {
                $stmt = $pdo->query("SHOW TABLES");
                $rows = $stmt->fetchAll(PDO::FETCH_NUM);
                $stmt->closeCursor();
                
                $tables = [];
                foreach ($rows as $row) {
                    $tables[] = $row[0];
                }
                
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                foreach ($tables as $table) {
                    $pdo->exec("DROP TABLE IF EXISTS `$table`");
                }
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            }
            
            executeSqlFileWithMysqli($db_config['host'], $db_config['user'], $db_config['pass'], $db_config['name'], __DIR__ . '/database.sql');
            
            $pdo = new PDO("mysql:host={$db_config['host']};dbname={$db_config['name']}", $db_config['user'], $db_config['pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            $pdo->exec("SET NAMES utf8mb4");
            
            $stmt = $pdo->prepare("INSERT INTO `lty-admin_users` (username, password, email, role_id, created_at) VALUES (?, ?, ?, 1, NOW()) ON DUPLICATE KEY UPDATE role_id = 1, password = VALUES(password), email = VALUES(email)");
            $stmt->execute([$admin_config['username'], $admin_config['password'], $admin_config['email']]);
            
            $stmt = $pdo->prepare("INSERT INTO `lty-site_settings` (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute(['site_url', $site_config['url'], $site_config['url']]);
            $stmt->execute(['site_name', $site_config['name'], $site_config['name']]);
            $stmt->execute(['enable_security_log', '1', '1']);
            $stmt->execute(['security_alert_email', '1', '1']);
            
            $config_content = "<?php\n";
            $config_content .= "define('DB_HOST', '{$db_config['host']}');\n";
            $config_content .= "define('DB_NAME', '{$db_config['name']}');\n";
            $config_content .= "define('DB_USER', '{$db_config['user']}');\n";
            $config_content .= "define('DB_PASS', '{$db_config['pass']}');\n";
            $config_content .= "define('DB_CHARSET', 'utf8mb4');\n";
            
            if (!is_dir(__DIR__ . '/lty-admin/config')) {
                mkdir(__DIR__ . '/lty-admin/config', 0755, true);
            }
            file_put_contents(__DIR__ . '/lty-admin/config/database.php', $config_content);
            
            unset($_SESSION['install_db']);
            unset($_SESSION['install_admin']);
            unset($_SESSION['install_site']);
            $_SESSION['install_complete'] = true;
            
            header('Location: install.php?step=7');
            exit;
        } catch (PDOException $e) {
            $error = '安装失败：' . $e->getMessage();
        } catch (Exception $e) {
            $error = '安装失败：' . $e->getMessage();
        }
    }
}

$steps = [
    1 => ['title' => '许可协议'],
    2 => ['title' => '环境检测'],
    3 => ['title' => '数据库'],
    4 => ['title' => '管理员'],
    5 => ['title' => '网站配置'],
    6 => ['title' => '执行安装'],
    7 => ['title' => '完成']
];

if ($mode === 'update') {
    $steps = [
        4 => ['title' => '数据库更新'],
        7 => ['title' => '完成']
    ];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $mode === 'update' ? '系统更新' : '安装向导'; ?> - <?php echo htmlspecialchars($site_name ?? '官网系统'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Noto Sans SC', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #fafafa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        
        .container {
            width: 100%;
            max-width: 480px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .logo img {
            height: 32px;
            width: auto;
        }
        
        .logo-text {
            font-size: 20px;
            font-weight: 700;
            color: #1a1a1a;
        }
        
        .step-info {
            font-size: 13px;
            color: #888;
        }
        
        .progress {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-bottom: 24px;
        }
        
        .progress-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #ddd;
            transition: all 0.3s ease;
        }
        
        .progress-dot.active {
            background: #f59e0b;
            transform: scale(1.25);
        }
        
        .progress-dot.done {
            background: #f59e0b;
        }
        
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .card-body {
            padding: 32px;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            background: #fef2f2;
            color: #dc2626;
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #444;
            margin-bottom: 6px;
        }
        
        .form-label .req {
            color: #dc2626;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.2s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #f59e0b;
        }
        
        .form-hint {
            font-size: 12px;
            color: #999;
            margin-top: 4px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-main {
            background: #f59e0b;
            color: white;
        }
        
        .btn-main:hover {
            background: #d97706;
        }
        
        .btn-outline {
            background: white;
            color: #666;
            border: 1px solid #e5e5e5;
        }
        
        .btn-outline:hover {
            border-color: #f59e0b;
            color: #f59e0b;
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 24px;
        }
        
        .btn-group .btn {
            flex: 1;
        }
        
        .license-box {
            background: #f9f9f9;
            border-radius: 8px;
            padding: 16px;
            max-height: 200px;
            overflow-y: auto;
            font-size: 13px;
            line-height: 1.7;
            color: #666;
        }
        
        .license-box h4 {
            font-size: 13px;
            font-weight: 600;
            color: #333;
            margin: 12px 0 6px;
        }
        
        .license-box h4:first-child {
            margin-top: 0;
        }
        
        .license-box ul {
            padding-left: 16px;
        }
        
        .checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px;
            background: #fffbeb;
            border-radius: 8px;
            margin-top: 16px;
            cursor: pointer;
        }
        
        .checkbox input {
            width: 18px;
            height: 18px;
            accent-color: #f59e0b;
        }
        
        .checkbox span {
            font-size: 13px;
            color: #666;
        }
        
        .checkbox strong {
            color: #d97706;
        }
        
        .env-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        
        .env-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 16px;
            background: #f8fafc;
            border-radius: 10px;
            font-size: 13px;
            border: 1px solid transparent;
            transition: all 0.2s ease;
        }
        
        .env-item:hover {
            background: #f1f5f9;
        }
        
        .env-item.ok {
            background: #f0fdf4;
            border-color: #bbf7d0;
        }
        
        .env-item.no {
            background: #fef2f2;
            border-color: #fecaca;
        }
        
        .env-item.warn {
            background: #fffbeb;
            border-color: #fde68a;
        }
        
        .env-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .env-icon {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border-radius: 8px;
            color: #64748b;
            flex-shrink: 0;
        }
        
        .env-item.ok .env-icon {
            color: #16a34a;
        }
        
        .env-item.no .env-icon {
            color: #dc2626;
        }
        
        .env-item.warn .env-icon {
            color: #d97706;
        }
        
        .env-name {
            color: #1e293b;
            font-weight: 600;
            font-size: 14px;
        }
        
        .env-desc {
            color: #94a3b8;
            font-size: 12px;
            margin-top: 2px;
        }
        
        .env-status {
            font-size: 12px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 6px;
            flex-shrink: 0;
        }
        
        .env-item.ok .env-status {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .env-item.no .env-status {
            background: #fecaca;
            color: #dc2626;
        }
        
        .env-item.warn .env-status {
            background: #fef3c7;
            color: #d97706;
        }
        
        .env-summary {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px;
            border-radius: 10px;
            margin-top: 16px;
            text-align: center;
            font-size: 14px;
            font-weight: 500;
        }
        
        .env-summary.success {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }
        
        .env-summary.error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        
        .env-section-title {
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 20px 0 10px 0;
            padding-bottom: 8px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .env-section-title:first-child {
            margin-top: 0;
        }
        
        .env-section-hint {
            font-weight: 400;
            color: #94a3b8;
            text-transform: none;
            letter-spacing: normal;
        }
        
        .env-tip {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 12px 14px;
            font-size: 13px;
            color: #991b1b;
            margin-top: 4px;
            display: none;
        }
        
        .env-tip strong {
            color: #dc2626;
        }
        
        .env-tip code {
            background: #fee2e2;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
        }
        
        .env-item.no {
            cursor: pointer;
        }
        
        .success {
            text-align: center;
            padding: 16px 0;
        }
        
        .success-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 20px;
            background: #dcfce7;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .success-icon svg {
            width: 32px;
            height: 32px;
            color: #16a34a;
        }
        
        .success h2 {
            font-size: 22px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 8px;
        }
        
        .success p {
            font-size: 13px;
            color: #888;
            margin-bottom: 24px;
        }
        
        .info-box {
            background: #f9f9f9;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
        }
        
        .info-box h4 {
            font-size: 13px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        
        .info-list {
            list-style: none;
        }
        
        .info-list li {
            font-size: 13px;
            color: #666;
            padding: 6px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .info-list li::before {
            content: '';
            width: 5px;
            height: 5px;
            background: #f59e0b;
            border-radius: 50%;
        }
        
        .footer {
            text-align: center;
            margin-top: 24px;
            font-size: 12px;
            color: #bbb;
        }
        
        @media (max-width: 480px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .btn-group {
                flex-direction: column;
            }
        }

        .password-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-input-wrapper .password-input {
            padding-right: 44px;
        }

        .password-toggle-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            padding: 4px;
            cursor: pointer;
            color: #999;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s;
        }

        .password-toggle-btn:hover {
            color: #f59e0b;
        }

        .password-toggle-btn svg {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <img src="assets/images/logo/logo.png" alt="Synchro">
            </div>
            <div class="step-info">
                <?php 
                $currentStepNum = 0;
                $totalSteps = count($steps);
                foreach ($steps as $num => $s) { $currentStepNum++; if ($num === $step) break; }
                echo "步骤 {$currentStepNum} / {$totalSteps}";
                ?>
            </div>
        </div>
        
        <div class="progress">
            <?php 
            $i = 0;
            foreach ($steps as $num => $s): 
                $i++;
            ?>
                <div class="progress-dot <?php echo $step === $num ? 'active' : ($step > $num ? 'done' : ''); ?>"></div>
            <?php endforeach; ?>
        </div>
        
        <div class="card">
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($step === 1 && $mode === 'install'): ?>
                    <div class="license-box" id="licenseBox">
                        <h4>版权声明</h4>
                        <p>本软件由徐州邻兔跃IT工作室开发并拥有完整版权。未经开发者书面授权，任何单位或个人不得以任何形式复制、传播、展示、修改本软件或制作衍生作品。</p>
                        <p>联系邮箱：lintuyue@qq.com</p>
                        <p>官方网站：lintuyue.com</p>
                        <p>技术交流QQ群：550133641</p>
                        
                        <h4>开源声明</h4>
                        <p>本软件部分组件基于以下开源项目构建，感谢开源社区的贡献：</p>
                        <ul>
                            <li>PHP - PHP License</li>
                            <li>MySQL - GPL License</li>
                            <li>jQuery - MIT License</li>
                            <li>Bootstrap - MIT License</li>
                            <li>Font Awesome - SIL OFL License</li>
                            <li>阿里巴巴矢量图标库 - MIT License</li>
                        </ul>
                        <p>上述开源项目均保留其原始许可证，使用者应遵守相应的开源协议条款。</p>
                        
                        <h4>Synchro 开源许可</h4>
                        <p>本软件遵循以下开源使用条例：</p>
                        <ul>
                            <li>允许个人及企业免费使用本软件</li>
                            <li>允许对源代码进行修改和定制</li>
                            <li>允许将本软件用于商业项目</li>
                            <li>允许分发本软件的副本</li>
                            <li>修改后的代码必须保留原始版权声明</li>
                            <li>禁止删除或隐藏开发者信息</li>
                            <li>二次分发时必须注明原始来源</li>
                            <li>禁止将本软件单独进行售卖</li>
                            <li>不对软件做任何形式的担保</li>
                        </ul>
                        <p>如您对本开源许可有任何疑问，请通过 lintuyue@qq.com 联系我们。</p>
                        
                        <h4>授权许可</h4>
                        <p>在您同意遵守本协议各项条款的前提下，开发者授予您有限的、非独占的、不可转让的许可，允许您：</p>
                        <ul>
                            <li>在本协议规定的范围内使用本软件</li>
                            <li>为备份目的制作本软件的副本</li>
                            <li>根据实际需求对软件进行合法的二次开发</li>
                        </ul>
                        
                        <h4>使用限制</h4>
                        <p>您不得进行以下行为：</p>
                        <ul>
                            <li>反向工程、反编译或反汇编本软件</li>
                            <li>出租、租赁、出借或转授权本软件</li>
                            <li>删除或修改软件中的任何版权声明</li>
                            <li>将软件用于任何非法目的</li>
                            <li>未经授权将软件用于商业分发</li>
                        </ul>
                        
                        <h4>免责声明</h4>
                        <p>本软件按"现状"提供，开发者不提供任何明示或暗示的保证，包括但不限于对适销性和特定用途适用性的保证。在任何情况下，开发者均不对任何直接、间接、偶然、特殊或后果性损害承担责任。</p>
                        
                        <h4>技术支持</h4>
                        <p>开发者提供以下技术支持服务：</p>
                        <ul>
                            <li>软件安装指导与问题排查</li>
                            <li>功能使用咨询</li>
                            <li>Bug修复与安全更新</li>
                            <li>版本升级服务</li>
                        </ul>
                        
                        <h4>协议变更</h4>
                        <p>开发者保留随时修改本协议的权利。修改后的协议将在官方网站公布，继续使用本软件即视为您接受修改后的协议。</p>
                        
                        <h4>法律适用</h4>
                        <p>本协议受中华人民共和国法律管辖，因本协议引起的任何争议应提交开发者所在地人民法院诉讼解决。</p>
                        
                        <div class="license-end" id="licenseEnd" style="text-align:center;padding:12px 0;color:#999;font-size:12px;">— 已阅读完毕 —</div>
                    </div>
                    
                    <label class="checkbox">
                        <input type="checkbox" id="agree" disabled>
                        <span id="agreeText" style="color:#999;">请先阅读完整协议</span>
                    </label>
                    
                    <div class="btn-group">
                        <a href="index.php" class="btn btn-outline">返回</a>
                        <button class="btn btn-main" id="nextBtn" disabled onclick="goNext()">同意并继续</button>
                    </div>
                    
                    <script>
                        var licenseBox = document.getElementById('licenseBox');
                        var checkbox = document.getElementById('agree');
                        var agreeText = document.getElementById('agreeText');
                        var btn = document.getElementById('nextBtn');
                        var scrolled = false;
                        
                        licenseBox.addEventListener('scroll', function() {
                            var scrollTop = this.scrollTop;
                            var scrollHeight = this.scrollHeight;
                            var clientHeight = this.clientHeight;
                            
                            if (scrollTop + clientHeight >= scrollHeight - 10 && !scrolled) {
                                scrolled = true;
                                checkbox.disabled = false;
                                agreeText.innerHTML = '我已阅读并同意<strong>《软件许可协议》</strong>';
                                agreeText.style.color = '#666';
                            }
                        });
                        
                        checkbox.addEventListener('change', function() {
                            btn.disabled = !this.checked;
                        });
                        
                        function goNext() {
                            if (checkbox.checked) window.location.href = 'install.php?step=2';
                        }
                    </script>
                
                <?php elseif ($step === 2 && $mode === 'install'): ?>
                    <div class="env-section-title">核心环境</div>
                    <div class="env-list">
                        <div class="env-item <?php echo $envCheck['php_version'] ? ($envCheck['php_version_recommend'] ? 'ok' : 'warn') : 'no'; ?>" <?php if (!$envCheck['php_version']): ?>onclick="toggleTip('php_tip')"<?php endif; ?>>
                            <div class="env-info">
                                <div class="env-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                                        <path d="M2 17l10 5 10-5"></path>
                                        <path d="M2 12l10 5 10-5"></path>
                                    </svg>
                                </div>
                                <div>
                                    <div class="env-name">PHP 运行环境</div>
                                    <div class="env-desc">当前版本 <?php echo PHP_VERSION; ?>，推荐 8.0+</div>
                                </div>
                            </div>
                            <span class="env-status"><?php 
                                if (!$envCheck['php_version']) {
                                    echo '不兼容';
                                } elseif ($envCheck['php_version_recommend']) {
                                    echo '理想';
                                } else {
                                    echo '可用';
                                }
                            ?></span>
                        </div>
                        <?php if (!$envCheck['php_version']): ?>
                        <div class="env-tip" id="php_tip">
                            <strong>修复方法：</strong>升级 PHP 版本至 7.4 或更高版本。联系服务器管理员或更换 PHP 环境。
                        </div>
                        <?php endif; ?>
                        
                        <div class="env-item <?php echo $envCheck['pdo_mysql'] ? 'ok' : 'no'; ?>" <?php if (!$envCheck['pdo_mysql']): ?>onclick="toggleTip('pdo_tip')"<?php endif; ?>>
                            <div class="env-info">
                                <div class="env-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                                        <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path>
                                        <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
                                    </svg>
                                </div>
                                <div>
                                    <div class="env-name">MySQL 数据库</div>
                                    <div class="env-desc">PDO_MySQL 扩展驱动</div>
                                </div>
                            </div>
                            <span class="env-status"><?php echo $envCheck['pdo_mysql'] ? '就绪' : '缺失'; ?></span>
                        </div>
                        <?php if (!$envCheck['pdo_mysql']): ?>
                        <div class="env-tip" id="pdo_tip">
                            <strong>修复方法：</strong>在 php.ini 中启用 extension=pdo_mysql，然后重启 Web 服务器。
                        </div>
                        <?php endif; ?>
                        
                        <div class="env-item <?php echo $envCheck['openssl'] ? 'ok' : 'no'; ?>" <?php if (!$envCheck['openssl']): ?>onclick="toggleTip('openssl_tip')"<?php endif; ?>>
                            <div class="env-info">
                                <div class="env-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                    </svg>
                                </div>
                                <div>
                                    <div class="env-name">OpenSSL 加密</div>
                                    <div class="env-desc">安全通信与数据加密</div>
                                </div>
                            </div>
                            <span class="env-status"><?php echo $envCheck['openssl'] ? '就绪' : '缺失'; ?></span>
                        </div>
                        <?php if (!$envCheck['openssl']): ?>
                        <div class="env-tip" id="openssl_tip">
                            <strong>修复方法：</strong>在 php.ini 中启用 extension=openssl，然后重启 Web 服务器。
                        </div>
                        <?php endif; ?>
                        
                        <div class="env-item <?php echo $envCheck['mbstring'] ? 'ok' : 'no'; ?>" <?php if (!$envCheck['mbstring']): ?>onclick="toggleTip('mbstring_tip')"<?php endif; ?>>
                            <div class="env-info">
                                <div class="env-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="4 7 4 4 20 4 20 7"></polyline>
                                        <line x1="9" y1="20" x2="15" y2="20"></line>
                                        <line x1="12" y1="4" x2="12" y2="20"></line>
                                    </svg>
                                </div>
                                <div>
                                    <div class="env-name">Mbstring 扩展</div>
                                    <div class="env-desc">多字节字符处理</div>
                                </div>
                            </div>
                            <span class="env-status"><?php echo $envCheck['mbstring'] ? '就绪' : '缺失'; ?></span>
                        </div>
                        <?php if (!$envCheck['mbstring']): ?>
                        <div class="env-tip" id="mbstring_tip">
                            <strong>修复方法：</strong>在 php.ini 中启用 extension=mbstring，然后重启 Web 服务器。
                        </div>
                        <?php endif; ?>
                        
                        <div class="env-item <?php echo $envCheck['gd'] ? 'ok' : 'no'; ?>" <?php if (!$envCheck['gd']): ?>onclick="toggleTip('gd_tip')"<?php endif; ?>>
                            <div class="env-info">
                                <div class="env-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                        <polyline points="21 15 16 10 5 21"></polyline>
                                    </svg>
                                </div>
                                <div>
                                    <div class="env-name">GD 图像库</div>
                                    <div class="env-desc">验证码生成与图片处理</div>
                                </div>
                            </div>
                            <span class="env-status"><?php echo $envCheck['gd'] ? '就绪' : '缺失'; ?></span>
                        </div>
                        <?php if (!$envCheck['gd']): ?>
                        <div class="env-tip" id="gd_tip">
                            <strong>修复方法：</strong>在 php.ini 中启用 extension=gd，然后重启 Web 服务器。
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="env-section-title">目录权限</div>
                    <div class="env-list">
                        <div class="env-item <?php echo $envCheck['uploads_writable'] ? 'ok' : 'no'; ?>" <?php if (!$envCheck['uploads_writable']): ?>onclick="toggleTip('uploads_tip')"<?php endif; ?>>
                            <div class="env-info">
                                <div class="env-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="17 8 12 3 7 8"></polyline>
                                        <line x1="12" y1="3" x2="12" y2="15"></line>
                                    </svg>
                                </div>
                                <div>
                                    <div class="env-name">uploads 目录</div>
                                    <div class="env-desc">文件上传存储目录</div>
                                </div>
                            </div>
                            <span class="env-status"><?php echo $envCheck['uploads_writable'] ? '可写' : '不可写'; ?></span>
                        </div>
                        <?php if (!$envCheck['uploads_writable']): ?>
                        <div class="env-tip" id="uploads_tip">
                            <strong>修复方法：</strong>执行命令 <code>chmod -R 755 uploads</code> 或 <code>chmod -R 777 uploads</code>（Windows 环境请右键目录设置写入权限）。
                        </div>
                        <?php endif; ?>
                        
                        <div class="env-item <?php echo $envCheck['config_writable'] ? 'ok' : 'no'; ?>" <?php if (!$envCheck['config_writable']): ?>onclick="toggleTip('config_tip')"<?php endif; ?>>
                            <div class="env-info">
                                <div class="env-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="3"></circle>
                                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <div class="env-name">config 目录</div>
                                    <div class="env-desc">系统配置文件目录</div>
                                </div>
                            </div>
                            <span class="env-status"><?php echo $envCheck['config_writable'] ? '可写' : '不可写'; ?></span>
                        </div>
                        <?php if (!$envCheck['config_writable']): ?>
                        <div class="env-tip" id="config_tip">
                            <strong>修复方法：</strong>执行命令 <code>chmod -R 755 lty-admin/config</code> 或 <code>chmod -R 777 lty-admin/config</code>（Windows 环境请右键目录设置写入权限）。
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="env-summary <?php echo $envCheckAllPass ? 'success' : 'error'; ?>">
                        <?php if ($envCheckAllPass): ?>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                            环境检测通过，可以开始安装
                        <?php else: ?>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                <line x1="9" y1="9" x2="15" y2="15"></line>
                            </svg>
                            环境检测未通过，点击红色项查看修复方法
                        <?php endif; ?>
                    </div>
                    
                    <div class="btn-group">
                        <a href="install.php?step=1" class="btn btn-outline">上一步</a>
                        <?php if ($envCheckAllPass): ?>
                            <a href="install.php?step=3" class="btn btn-main">下一步</a>
                        <?php else: ?>
                            <button class="btn btn-main" disabled>环境不满足</button>
                        <?php endif; ?>
                    </div>
                    
                    <script>
                    function toggleTip(id) {
                        var tip = document.getElementById(id);
                        if (tip) {
                            tip.style.display = tip.style.display === 'none' ? 'block' : 'none';
                        }
                    }
                    </script>
                
                <?php elseif ($step === 3 && $mode === 'install'): ?>
                    <?php if (isset($_GET['check']) && $_GET['check'] === 'exists' && isset($_SESSION['install_db']['existing_tables'])): ?>
                        <?php 
                        $existingCount = count($_SESSION['install_db']['existing_tables']);
                        $missingTables = array_diff(array_keys($requiredTables), $_SESSION['install_db']['existing_tables']);
                        $missingCount = count($missingTables);
                        $totalCount = count($requiredTables);
                        ?>
                        <div class="alert" style="background:#fef3c7;color:#92400e;">
                            <strong>检测到数据库中已存在数据表！</strong><br>
                            数据库 "<?php echo htmlspecialchars($_SESSION['install_db']['name']); ?>" 中已有 <?php echo $existingCount; ?> 个表，共需 <?php echo $totalCount; ?> 个表，还有 <?php echo $missingCount; ?> 个表未安装。
                        </div>
                        
                        <div class="info-box">
                            <h4>已存在的表</h4>
                            <div style="max-height:120px;overflow-y:auto;font-size:12px;color:#666;">
                                <?php foreach ($_SESSION['install_db']['existing_tables'] as $table): ?>
                                    <span style="display:inline-block;background:#f3f4f6;padding:2px 8px;margin:2px;border-radius:4px;"><?php echo htmlspecialchars($table); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="info-box" style="background:#fef2f2;">
                            <h4 style="color:#dc2626;">请选择安装方式</h4>
                            <ul class="info-list">
                                <li><strong>覆盖安装</strong>：删除所有旧数据，全新安装（原有数据将丢失）</li>
                                <li><strong>更新安装</strong>：保留现有数据，仅添加缺失的表和字段</li>
                            </ul>
                        </div>
                        
                        <form method="POST" style="margin-bottom:12px;">
                            <input type="hidden" name="confirm_overwrite" value="1">
                            <button type="submit" class="btn btn-main" style="width:100%;background:#dc2626;" onclick="return confirm('确定要覆盖安装吗？所有原有数据将被删除！')">覆盖安装（清空数据）</button>
                        </form>
                        
                        <form method="POST">
                            <input type="hidden" name="confirm_update" value="1">
                            <button type="submit" class="btn btn-main" style="width:100%;">更新安装（保留数据）</button>
                        </form>
                        
                        <div style="margin-top:16px;">
                            <a href="install.php?step=3" class="btn btn-outline" style="width:100%;">重新选择数据库</a>
                        </div>
                    <?php else: ?>
                        <form method="POST">
                            <div class="form-group">
                                <label class="form-label">数据库主机 <span class="req">*</span></label>
                                <input type="text" class="form-input" name="db_host" value="localhost" required>
                                <div class="form-hint">通常为 localhost</div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">数据库名称 <span class="req">*</span></label>
                                    <input type="text" class="form-input" name="db_name" placeholder="synchro_db" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">数据库用户名 <span class="req">*</span></label>
                                    <input type="text" class="form-input" name="db_user" placeholder="root" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">数据库密码</label>
                                <input type="password" class="form-input" name="db_pass" placeholder="请输入数据库密码">
                                <div class="form-hint">没有密码请留空</div>
                            </div>
                            
                            <div class="btn-group">
                                <a href="install.php?step=2" class="btn btn-outline">上一步</a>
                                <button type="submit" class="btn btn-main">测试连接</button>
                            </div>
                        </form>
                    <?php endif; ?>
                
                <?php elseif ($step === 4 && $mode === 'install'): ?>
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">管理员用户名 <span class="req">*</span></label>
                                <input type="text" class="form-input" name="admin_username" value="admin" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">管理员邮箱 <span class="req">*</span></label>
                                <input type="email" class="form-input" name="admin_email" placeholder="lintuyue@qq.com" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">管理员密码 <span class="req">*</span></label>
                            <div class="password-input-wrapper">
                                <input type="password" class="form-input password-input" name="admin_password" id="admin_password" placeholder="请设置密码" required>
                                <button type="button" class="password-toggle-btn" onclick="togglePassword('admin_password', this)">
                                    <svg class="eye-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                    <svg class="eye-off-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                        <line x1="1" y1="1" x2="23" y2="23"></line>
                                    </svg>
                                </button>
                            </div>
                            <div class="form-hint">建议8位以上，包含字母和数字</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">确认密码 <span class="req">*</span></label>
                            <div class="password-input-wrapper">
                                <input type="password" class="form-input password-input" name="admin_password_confirm" id="admin_password_confirm" placeholder="请再次输入密码" required>
                                <button type="button" class="password-toggle-btn" onclick="togglePassword('admin_password_confirm', this)">
                                    <svg class="eye-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                    <svg class="eye-off-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                        <line x1="1" y1="1" x2="23" y2="23"></line>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <div class="btn-group">
                            <a href="install.php?step=3" class="btn btn-outline">上一步</a>
                            <button type="submit" class="btn btn-main">下一步</button>
                        </div>
                    </form>
                
                <?php elseif ($step === 5 && $mode === 'install'): ?>
                    <?php 
                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                    $autoDomain = $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
                    ?>
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">网站名称 <span class="req">*</span></label>
                            <input type="text" class="form-input" name="site_name" value="Synchro" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">网站域名 <span class="req">*</span></label>
                            <input type="text" class="form-input" name="site_url" value="<?php echo htmlspecialchars($autoDomain); ?>" required>
                            <div class="form-hint">系统已自动检测当前域名</div>
                        </div>
                        
                        <div class="btn-group">
                            <a href="install.php?step=4" class="btn btn-outline">上一步</a>
                            <button type="submit" class="btn btn-main">下一步</button>
                        </div>
                    </form>
                
                <?php elseif ($step === 6 && $mode === 'install'): ?>
                    <div class="info-box">
                        <h4>安装将执行以下操作</h4>
                        <ul class="info-list">
                            <li>创建数据库表结构</li>
                            <li>初始化系统配置</li>
                            <li>创建管理员账号</li>
                            <li>生成配置文件</li>
                        </ul>
                    </div>
                    
                    <form method="POST">
                        <div class="btn-group">
                            <a href="install.php?step=5" class="btn btn-outline">上一步</a>
                            <button type="submit" class="btn btn-main">开始安装</button>
                        </div>
                    </form>
                
                <?php elseif ($step === 4 && $mode === 'update'): ?>
                    <?php 
                    require_once 'lty-admin/config/database.php';
                    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
                    $currentDbCheck = checkDatabase($pdo, $requiredTables);
                    ?>
                    
                    <div class="info-box">
                        <h4>数据库检查结果</h4>
                        <p style="font-size:13px;color:#666;margin-bottom:12px;">
                            已有 <?php echo count($currentDbCheck['existing_tables']); ?> 个表，
                            缺失 <?php echo count($currentDbCheck['missing_tables']); ?> 个表
                        </p>
                    </div>
                    
                    <div class="db-check-list" style="max-height:280px;overflow-y:auto;margin-bottom:16px;">
                        <?php foreach ($currentDbCheck['table_details'] as $tableName => $tableInfo): ?>
                            <div class="env-item <?php echo $tableInfo['exists'] ? 'ok' : 'no'; ?>" style="margin-bottom:6px;">
                                <div>
                                    <div class="env-name"><?php echo $tableName; ?></div>
                                    <div class="env-desc"><?php echo $tableInfo['description']; ?></div>
                                </div>
                                <span class="env-status"><?php echo $tableInfo['exists'] ? '已存在' : '缺失'; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="info-box" style="background:#fffbeb;">
                        <h4>更新将执行以下操作</h4>
                        <ul class="info-list">
                            <li>创建缺失的数据表</li>
                            <li>补充缺失的字段</li>
                            <li>优化数据库索引结构</li>
                            <li>更新系统基础数据</li>
                        </ul>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="update_database" value="1">
                        <div class="btn-group">
                            <button type="submit" class="btn btn-main">开始更新</button>
                        </div>
                    </form>
                
                <?php elseif ($step === 7): ?>
                    <div class="success">
                        <div class="success-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </div>
                        <h2><?php echo $mode === 'update' ? '更新完成' : '安装成功'; ?></h2>
                        <p><?php echo $mode === 'update' ? '系统数据库已成功更新，您可以：' : 'Synchro 已成功安装，您可以：'; ?></p>
                        <div class="btn-group">
                            <a href="index.php" class="btn btn-outline">进入前台</a>
                            <a href="lty-admin/" class="btn btn-main">进入后台</a>
                        </div>
                        <div style="margin-top:24px;padding:16px;background:#f9f9f9;border-radius:8px;font-size:12px;color:#888;line-height:1.8;">
                            <strong style="color:#666;">温馨提示：</strong><br>
                            如需重新安装，请删除 <code style="background:#fff;padding:2px 6px;border-radius:4px;">lty-admin/config/database.php</code> 文件后重新访问本页面。
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="footer">
            © 2026 <?php echo htmlspecialchars($site_name ?? '官网系统'); ?> · 徐州邻兔跃IT工作室
        </div>
    </div>
    
    <script>
        function togglePassword(inputId, btn) {
            var input = document.getElementById(inputId);
            var eyeIcon = btn.querySelector('.eye-icon');
            var eyeOffIcon = btn.querySelector('.eye-off-icon');
            
            if (input.type === 'password') {
                input.type = 'text';
                eyeIcon.style.display = 'none';
                eyeOffIcon.style.display = 'block';
            } else {
                input.type = 'password';
                eyeIcon.style.display = 'block';
                eyeOffIcon.style.display = 'none';
            }
        }
    </script>
</body>
</html>

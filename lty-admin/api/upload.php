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
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (!Auth::check()) {
        echo json_encode(['success' => false, 'message' => '请先登录']);
        exit;
    }

    $canManage = Auth::isSuperAdmin() || Auth::hasPermission('media_manage');
    if (!$canManage) {
        echo json_encode(['success' => false, 'message' => '无权限上传文件']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => '请求方法错误']);
        exit;
    }

    if (!isset($_FILES['files']) || empty($_FILES['files']['name'])) {
        echo json_encode(['success' => false, 'message' => '没有选择文件']);
        exit;
    }
    
    if (!is_array($_FILES['files']['name'])) {
        $_FILES['files']['name'] = [$_FILES['files']['name']];
        $_FILES['files']['type'] = [$_FILES['files']['type']];
        $_FILES['files']['tmp_name'] = [$_FILES['files']['tmp_name']];
        $_FILES['files']['error'] = [$_FILES['files']['error']];
        $_FILES['files']['size'] = [$_FILES['files']['size']];
    }
    
    if (empty($_FILES['files']['name'][0])) {
        echo json_encode(['success' => false, 'message' => '没有选择文件']);
        exit;
    }

    $db = Database::getInstance();
    $user = Auth::user();

    $uploadDir = __DIR__ . '/../../uploads/media/';
    $maxFileSize = 10 * 1024 * 1024;
    $allowedTypes = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml', 'image/bmp', 'image/tiff',
        'video/mp4', 'video/webm', 'video/ogg', 'video/quicktime', 'video/x-msvideo', 'video/x-ms-wmv',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'text/csv',
        'application/json',
        'application/xml',
        'text/xml',
        'text/html',
        'text/css',
        'application/javascript',
        'text/markdown',
        'application/rtf'
    ];

    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $year = date('Y');
    $month = date('m');
    $day = date('d');
    $uploadPath = $uploadDir . $year . '/' . $month . '/' . $day . '/';

    if (!file_exists($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }

    $uploadedFiles = [];
    $errors = [];

    foreach ($_FILES['files']['name'] as $key => $name) {
        $tmpName = $_FILES['files']['tmp_name'][$key];
        $size = $_FILES['files']['size'][$key];
        $error = $_FILES['files']['error'][$key];
        $type = $_FILES['files']['type'][$key];

        if ($error !== UPLOAD_ERR_OK) {
            $errors[] = $name . ': 上传错误 (错误代码: ' . $error . ')';
            continue;
        }

        if ($size > $maxFileSize) {
            $errors[] = $name . ': 文件大小超过限制（最大10MB）';
            continue;
        }

        $detectedType = $type;
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detectedType = finfo_file($finfo, $tmpName);
            finfo_close($finfo);
        }
        
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        
        $extensionMimeTypeMap = [
            'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif',
            'webp' => 'image/webp', 'svg' => 'image/svg+xml', 'bmp' => 'image/bmp', 'tiff' => 'image/tiff',
            'mp4' => 'video/mp4', 'webm' => 'video/webm', 'ogg' => 'video/ogg', 'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo', 'wmv' => 'video/x-ms-wmv',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint', 'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'txt' => 'text/plain', 'md' => 'text/markdown', 'rtf' => 'application/rtf',
            'csv' => 'text/csv', 'json' => 'application/json',
            'xml' => 'application/xml', 'html' => 'text/html', 'css' => 'text/css', 'js' => 'application/javascript'
        ];
        
        if ($detectedType === 'application/octet-stream' || $detectedType === 'application/zip') {
            if (isset($extensionMimeTypeMap[$extension])) {
                $detectedType = $extensionMimeTypeMap[$extension];
            }
        }

        if (!in_array($detectedType, $allowedTypes)) {
            if (!isset($extensionMimeTypeMap[$extension]) || !in_array($extensionMimeTypeMap[$extension], $allowedTypes)) {
                $errors[] = $name . ': 不支持的文件类型 (' . $detectedType . ')';
                continue;
            }
            $detectedType = $extensionMimeTypeMap[$extension];
        }
        $filename = 'media_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $filepath = $uploadPath . $filename;
        $relativeUrl = '/uploads/media/' . $year . '/' . $month . '/' . $day . '/' . $filename;
        
        $siteUrl = Settings::get('site_url', '');
        if (!empty($siteUrl)) {
            $fileUrl = rtrim($siteUrl, '/') . $relativeUrl;
        } else {
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $fileUrl = $protocol . '://' . $host . $relativeUrl;
        }

        if (move_uploaded_file($tmpName, $filepath)) {
            $fileType = 'other';
            if (strpos($detectedType, 'image/') === 0) {
                $fileType = 'image';
            } elseif (strpos($detectedType, 'video/') === 0) {
                $fileType = 'video';
            } elseif (isset($extensionMimeTypeMap[$extension])) {
                $fileType = 'document';
            } elseif (in_array($detectedType, [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'text/plain',
                'text/csv',
                'application/json',
                'application/xml',
                'text/xml',
                'text/html',
                'text/css',
                'application/javascript',
                'text/markdown',
                'application/rtf'
            ])) {
                $fileType = 'document';
            }

            $width = null;
            $height = null;

            if ($fileType === 'image') {
                $imageInfo = getimagesize($filepath);
                if ($imageInfo) {
                    $width = $imageInfo[0];
                    $height = $imageInfo[1];
                }
            }

            $data = [
                'filename' => $filename,
                'original_name' => $name,
                'file_path' => $filepath,
                'file_url' => $fileUrl,
                'file_type' => $fileType,
                'mime_type' => $detectedType,
                'file_size' => $size,
                'width' => $width,
                'height' => $height,
                'uploaded_by' => $user['id']
            ];

            $mediaId = $db->insert('lty-media_library', $data);

            if ($mediaId) {
                OperationLog::create('media', '上传媒体文件：' . $name, 'media', $mediaId);
                $uploadedFiles[] = [
                    'id' => $mediaId,
                    'name' => $name,
                    'url' => $fileUrl,
                    'type' => $fileType,
                    'size' => $size
                ];
            } else {
                $errors[] = $name . ': 数据库保存失败';
                @unlink($filepath);
            }
        } else {
            $errors[] = $name . ': 文件保存失败';
        }
    }

    if (!empty($uploadedFiles)) {
        $response = [
            'success' => true,
            'message' => '成功上传 ' . count($uploadedFiles) . ' 个文件',
            'uploaded' => $uploadedFiles,
            'errors' => $errors
        ];
        $json = json_encode($response, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            echo json_encode(['success' => false, 'message' => 'JSON编码失败']);
        } else {
            echo $json;
        }
    } else {
        $response = [
            'success' => false,
            'message' => '上传失败',
            'errors' => $errors
        ];
        $json = json_encode($response, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            echo json_encode(['success' => false, 'message' => 'JSON编码失败']);
        } else {
            echo $json;
        }
    }
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => '上传失败: ' . $e->getMessage()
    ];
    $json = json_encode($response, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        echo json_encode(['success' => false, 'message' => 'JSON编码失败']);
    } else {
        echo $json;
    }
}

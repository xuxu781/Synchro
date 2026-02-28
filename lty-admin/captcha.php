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
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$code = '';
$chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
for ($i = 0; $i < 4; $i++) {
    $code .= $chars[random_int(0, strlen($chars) - 1)];
}

$_SESSION['captcha_code'] = $code;
$_SESSION['captcha_time'] = time();

$width = 120;
$height = 40;
$image = imagecreatetruecolor($width, $height);

$bgColor = imagecolorallocate($image, 30, 30, 30);
imagefill($image, 0, 0, $bgColor);

$textColor = imagecolorallocate($image, 245, 158, 11);

$font = __DIR__ . '/assets/fonts/captcha.ttf';
if (file_exists($font)) {
    for ($i = 0; $i < strlen($code); $i++) {
        $x = 15 + $i * 25;
        $y = 28;
        $angle = random_int(-15, 15);
        imagettftext($image, 20, $angle, $x, $y, $textColor, $font, $code[$i]);
    }
} else {
    for ($i = 0; $i < strlen($code); $i++) {
        $x = 20 + $i * 25;
        $y = 28;
        imagestring($image, 5, $x, $y - 10, $code[$i], $textColor);
    }
}

$lineColor = imagecolorallocate($image, 60, 60, 60);
for ($i = 0; $i < 3; $i++) {
    imageline($image, random_int(0, $width), random_int(0, $height), random_int(0, $width), random_int(0, $height), $lineColor);
}

$dotColor = imagecolorallocate($image, 80, 80, 80);
for ($i = 0; $i < 30; $i++) {
    imagefilledellipse($image, random_int(0, $width), random_int(0, $height), 2, 2, $dotColor);
}

header('Content-Type: image/png');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

imagepng($image);
imagedestroy($image);

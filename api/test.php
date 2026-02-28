<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

echo json_encode([
    'success' => true,
    'message' => 'API测试成功',
    'time' => date('Y-m-d H:i:s'),
    'post' => $_POST,
    'get' => $_GET
]);

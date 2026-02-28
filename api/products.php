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
require_once __DIR__ . '/../includes/api_security.php';

APISecurity::setSecurityHeaders();
APISecurity::checkRateLimit();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

$publicActions = ['product', 'products', 'categories', 'updates', 'get_updates', 'get_update', 'products_paginated'];
$authRequiredActions = ['update_sort', 'update_category_sort', 'add_update', 'edit_update', 'delete_update', 'reorder_updates'];

if (in_array($action, $authRequiredActions)) {
    require_auth();
}

switch ($action) {
    case 'product':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($id <= 0) {
            json_response(['error' => 'Invalid product ID'], 400);
        }
        
        try {
            $db = Database::getInstance();
            $product = $db->fetchOne("SELECT * FROM `lty-products` WHERE id = ? AND status = 1", [$id]);
            
            if (!$product) {
                json_response(['error' => 'Product not found'], 404);
            }
            
            $updates = $db->fetchAll("SELECT * FROM `lty-product_updates` WHERE product_id = ? AND status = 1 ORDER BY release_date DESC, sort_order ASC", [$id]);
            
            $features = !empty($product['features']) ? json_decode($product['features'], true) : [];
            $tech_stack = !empty($product['tech_stack']) ? json_decode($product['tech_stack'], true) : [];
            
            $product['features'] = $features;
            $product['tech_stack'] = $tech_stack;
            $product['updates'] = $updates;
            
            json_response(['success' => true, 'data' => $product]);
        } catch (Exception $e) {
            json_response(['error' => 'Failed to fetch product'], 500);
        }
        break;
        
    case 'products':
        try {
            $db = Database::getInstance();
            $category = $_GET['category'] ?? '';
            
            if (!empty($category) && $category !== 'all') {
                $products = $db->fetchAll("SELECT p.*, c.name as category_name, c.slug as category_slug FROM `lty-products` p LEFT JOIN `lty-product_categories` c ON p.category_id = c.id WHERE c.slug = ? AND p.status = 1 ORDER BY p.sort_order ASC, p.id ASC", [$category]);
            } else {
                $products = $db->fetchAll("SELECT p.*, c.name as category_name, c.slug as category_slug FROM `lty-products` p LEFT JOIN `lty-product_categories` c ON p.category_id = c.id WHERE p.status = 1 ORDER BY c.sort_order ASC, p.sort_order ASC, p.id ASC");
            }
            
            foreach ($products as &$product) {
                $product['features'] = !empty($product['features']) ? json_decode($product['features'], true) : [];
                $product['tech_stack'] = !empty($product['tech_stack']) ? json_decode($product['tech_stack'], true) : [];
            }
            
            json_response(['success' => true, 'data' => $products]);
        } catch (Exception $e) {
            json_response(['error' => 'Failed to fetch products'], 500);
        }
        break;
        
    case 'products_paginated':
        try {
            $db = Database::getInstance();
            $category = $_GET['category'] ?? '';
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $perPage = 6;
            $offset = ($page - 1) * $perPage;
            
            $whereClause = "p.status = 1";
            $params = [];
            
            if (!empty($category) && $category !== 'all') {
                $whereClause .= " AND c.slug = ?";
                $params[] = $category;
            }
            
            $totalProducts = $db->fetchOne("SELECT COUNT(*) as count FROM `lty-products` p LEFT JOIN `lty-product_categories` c ON p.category_id = c.id WHERE {$whereClause}", $params)['count'];
            $totalPages = ceil($totalProducts / $perPage);
            
            $products = $db->fetchAll("SELECT p.*, c.name as category_name, c.slug as category_slug FROM `lty-products` p LEFT JOIN `lty-product_categories` c ON p.category_id = c.id WHERE {$whereClause} ORDER BY c.sort_order ASC, p.sort_order ASC, p.id ASC LIMIT {$offset}, {$perPage}", $params);
            
            foreach ($products as &$product) {
                $product['features'] = !empty($product['features']) ? json_decode($product['features'], true) : [];
                $product['tech_stack'] = !empty($product['tech_stack']) ? json_decode($product['tech_stack'], true) : [];
            }
            
            json_response([
                'success' => true, 
                'data' => $products,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_products' => $totalProducts,
                    'per_page' => $perPage
                ]
            ]);
        } catch (Exception $e) {
            json_response(['error' => 'Failed to fetch products'], 500);
        }
        break;
        
    case 'categories':
        try {
            $db = Database::getInstance();
            $categories = $db->fetchAll("SELECT * FROM `lty-product_categories` WHERE status = 1 ORDER BY sort_order ASC, id ASC");
            
            json_response(['success' => true, 'data' => $categories]);
        } catch (Exception $e) {
            json_response(['error' => 'Failed to fetch categories'], 500);
        }
        break;
        
    case 'update_sort':
        $input = json_decode(file_get_contents('php://input'), true);
        $data = $input['data'] ?? [];
        
        if (empty($data)) {
            json_response(['error' => 'Invalid parameters'], 400);
        }
        
        try {
            $db = Database::getInstance();
            $db->beginTransaction();
            
            foreach ($data as $item) {
                $db->update('`lty-products`', ['sort_order' => $item['sort_order']], 'id = ?', [$item['id']]);
            }
            
            $db->commit();
            json_response(['success' => true]);
        } catch (Exception $e) {
            $db->rollback();
            json_response(['error' => 'Failed to reorder products'], 500);
        }
        break;
        
    case 'update_category_sort':
        $input = json_decode(file_get_contents('php://input'), true);
        $data = $input['data'] ?? [];
        
        if (empty($data)) {
            json_response(['error' => 'Invalid parameters'], 400);
        }
        
        try {
            $db = Database::getInstance();
            $db->beginTransaction();
            
            foreach ($data as $item) {
                $db->update('`lty-product_categories`', ['sort_order' => $item['sort_order']], 'id = ?', [$item['id']]);
            }
            
            $db->commit();
            json_response(['success' => true]);
        } catch (Exception $e) {
            $db->rollback();
            json_response(['error' => 'Failed to reorder categories'], 500);
        }
        break;
        
    case 'updates':
        $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
        
        if ($product_id <= 0) {
            json_response(['error' => 'Invalid product ID'], 400);
        }
        
        try {
            $db = Database::getInstance();
            $updates = $db->fetchAll("SELECT * FROM `lty-product_updates` WHERE product_id = ? AND status = 1 ORDER BY release_date DESC, sort_order ASC", [$product_id]);
            
            json_response(['success' => true, 'data' => $updates]);
        } catch (Exception $e) {
            json_response(['error' => 'Failed to fetch updates'], 500);
        }
        break;
        
    case 'get_updates':
        $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
        
        if ($product_id <= 0) {
            json_response(['error' => 'Invalid product ID'], 400);
        }
        
        try {
            $db = Database::getInstance();
            $updates = $db->fetchAll("SELECT * FROM `lty-product_updates` WHERE product_id = ? ORDER BY release_date DESC, sort_order ASC", [$product_id]);
            
            json_response(['success' => true, 'updates' => $updates]);
        } catch (Exception $e) {
            json_response(['error' => 'Failed to fetch updates'], 500);
        }
        break;
        
    case 'get_update':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($id <= 0) {
            json_response(['error' => 'Invalid update ID'], 400);
        }
        
        try {
            $db = Database::getInstance();
            $update = $db->fetchOne("SELECT * FROM `lty-product_updates` WHERE id = ?", [$id]);
            
            if (!$update) {
                json_response(['error' => 'Update not found'], 404);
            }
            
            json_response(['success' => true, 'update' => $update]);
        } catch (Exception $e) {
            json_response(['error' => 'Failed to fetch update'], 500);
        }
        break;
        
    case 'add_update':
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $version = trim($_POST['version'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $release_date = trim($_POST['release_date'] ?? '');
        $status = isset($_POST['status']) ? 1 : 0;
        
        if (empty($product_id) || empty($version) || empty($content) || empty($release_date)) {
            json_response(['error' => 'Missing required fields'], 400);
        }
        
        try {
            $db = Database::getInstance();
            
            $maxSortOrder = $db->fetchOne("SELECT MAX(sort_order) as max_sort FROM `lty-product_updates` WHERE product_id = ?", [$product_id]);
            $sort_order = ($maxSortOrder['max_sort'] ?? 0) + 1;
            
            $data = [
                'product_id' => $product_id,
                'version' => $version,
                'content' => $content,
                'release_date' => $release_date,
                'sort_order' => $sort_order,
                'status' => $status
            ];
            
            $db->insert('`lty-product_updates`', $data);
            
            json_response(['success' => true, 'message' => '添加成功']);
        } catch (Exception $e) {
            json_response(['error' => 'Failed to add update'], 500);
        }
        break;
        
    case 'edit_update':
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $version = trim($_POST['version'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $release_date = trim($_POST['release_date'] ?? '');
        $status = isset($_POST['status']) ? 1 : 0;
        
        if (empty($id) || empty($version) || empty($content) || empty($release_date)) {
            json_response(['error' => 'Missing required fields'], 400);
        }
        
        try {
            $db = Database::getInstance();
            
            $existingItem = $db->fetchOne("SELECT sort_order FROM `lty-product_updates` WHERE id = ?", [$id]);
            $sort_order = $existingItem['sort_order'] ?? 0;
            
            $data = [
                'product_id' => $product_id,
                'version' => $version,
                'content' => $content,
                'release_date' => $release_date,
                'sort_order' => $sort_order,
                'status' => $status
            ];
            
            $db->update('`lty-product_updates`', $data, 'id = ?', [$id]);
            
            json_response(['success' => true, 'message' => '更新成功']);
        } catch (Exception $e) {
            json_response(['error' => 'Failed to update update'], 500);
        }
        break;
        
    case 'delete_update':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($id <= 0) {
            json_response(['error' => 'Invalid update ID'], 400);
        }
        
        try {
            $db = Database::getInstance();
            $db->delete('`lty-product_updates`', 'id = ?', [$id]);
            
            json_response(['success' => true, 'message' => '删除成功']);
        } catch (Exception $e) {
            json_response(['error' => 'Failed to delete update'], 500);
        }
        break;
        
    case 'reorder_updates':
        $input = json_decode(file_get_contents('php://input'), true);
        $product_id = intval($input['product_id'] ?? 0);
        $order = $input['order'] ?? [];
        
        if ($product_id <= 0 || empty($order)) {
            json_response(['error' => 'Invalid parameters'], 400);
        }
        
        try {
            $db = Database::getInstance();
            $db->beginTransaction();
            
            foreach ($order as $index => $id) {
                $db->update('`lty-product_updates`', ['sort_order' => $index + 1], 'id = ? AND product_id = ?', [$id, $product_id]);
            }
            
            $db->commit();
            json_response(['success' => true]);
        } catch (Exception $e) {
            $db->rollback();
            json_response(['error' => 'Failed to reorder updates'], 500);
        }
        break;
        
    default:
        json_response(['error' => 'Invalid action'], 400);
}

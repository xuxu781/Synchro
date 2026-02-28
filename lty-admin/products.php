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

Auth::requirePermission('products_manage');

$db = Database::getInstance();

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

if ($action === 'delete_product' && $id) {
    $product = $db->fetchOne("SELECT title FROM `lty-products` WHERE id = ?", [$id]);
    $db->delete('`lty-products`', 'id = ?', [$id]);
    OperationLog::delete('products', '删除产品：' . ($product['title'] ?? $id), 'product', $id);
    set_flash_message('success', '删除成功');
    $filter_category = $_GET['filter_category'] ?? '';
    redirect('lty-admin/products.php' . ($filter_category ? '?filter_category=' . $filter_category : ''));
}

if ($action === 'delete_category' && $id) {
    $category = $db->fetchOne("SELECT name FROM `lty-product_categories` WHERE id = ?", [$id]);
    $db->delete('`lty-product_categories`', 'id = ?', [$id]);
    OperationLog::delete('products', '删除产品分类：' . ($category['name'] ?? $id), 'product_category', $id);
    set_flash_message('success', '删除成功');
    redirect('lty-admin/products.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $products_title = trim($_POST['products_title'] ?? '');
    $products_subtitle = trim($_POST['products_subtitle'] ?? '');
    
    Settings::set('products_title', $products_title);
    Settings::set('products_subtitle', $products_subtitle);
    
    OperationLog::update('products', '更新产品页面设置', 'settings', null, ['title' => $products_title, 'subtitle' => $products_subtitle]);
    
    set_flash_message('success', '设置保存成功');
    redirect('lty-admin/products.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add_product' || $action === 'edit_product') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $imageUrl = trim($_POST['image_url'] ?? '');
        $imagesInput = trim($_POST['images'] ?? '');
        $link = trim($_POST['link'] ?? '');
        $status = isset($_POST['status']) ? 1 : 0;
        $isOriginal = isset($_POST['is_original']) ? 1 : 0;
        $statusText = trim($_POST['status_text'] ?? '');
        $releaseDate = trim($_POST['release_date'] ?? '');
        $features = trim($_POST['features'] ?? '');
        $techStack = trim($_POST['tech_stack'] ?? '');
        $demoUrl = trim($_POST['demo_url'] ?? '');
        $downloadUrl = trim($_POST['download_url'] ?? '');
        $price = trim($_POST['price'] ?? '');
        $fullDescription = trim($_POST['full_description'] ?? '');
        
        $featuresArray = [];
        if (!empty($features)) {
            $featuresArray = array_filter(array_map('trim', explode("\n", $features)));
        }
        $featuresJson = !empty($featuresArray) ? json_encode($featuresArray) : null;
        
        $techStackArray = [];
        if (!empty($techStack)) {
            $techStackArray = array_filter(array_map('trim', explode("\n", $techStack)));
        }
        $techStackJson = !empty($techStackArray) ? json_encode($techStackArray) : null;
        
        $imagesArray = [];
        if (!empty($imagesInput)) {
            $imagesArray = array_filter(array_map('trim', explode("\n", $imagesInput)));
        }
        $images = !empty($imagesArray) ? json_encode($imagesArray) : null;
        
        if ($action === 'add_product') {
            $maxSortOrder = $db->fetchOne("SELECT MAX(sort_order) as max_sort FROM `lty-products` WHERE category_id = ?", [$categoryId]);
            $sort_order = ($maxSortOrder['max_sort'] ?? 0) + 1;
        } else {
            $existingItem = $db->fetchOne("SELECT sort_order FROM `lty-products` WHERE id = ?", [$id]);
            $sort_order = $existingItem['sort_order'] ?? 0;
        }
        
        if (empty($title) || empty($description) || empty($categoryId)) {
            set_flash_message('error', '请填写标题、描述和分类');
        } else {
            $data = [
                'title' => $title,
                'description' => $description,
                'category_id' => $categoryId,
                'image' => $imageUrl,
                'images' => $images ?: null,
                'link' => $link,
                'is_original' => $isOriginal,
                'sort_order' => $sort_order,
                'status' => $status,
                'status_text' => $statusText,
                'release_date' => $releaseDate ?: null,
                'features' => $featuresJson,
                'tech_stack' => $techStackJson,
                'demo_url' => $demoUrl ?: null,
                'download_url' => $downloadUrl ?: null,
                'price' => $price ?: null,
                'full_description' => $fullDescription ?: null
            ];
            
            if ($action === 'edit_product' && $id) {
                $db->update('`lty-products`', $data, 'id = ?', [$id]);
                OperationLog::update('products', '更新产品：' . $title, 'product', $id, ['title' => $title]);
                set_flash_message('success', '更新成功');
                $filter_category = $_GET['filter_category'] ?? '';
                redirect('lty-admin/products.php' . ($filter_category ? '?filter_category=' . $filter_category : ''));
            } else {
                $newId = $db->insert('`lty-products`', $data);
                OperationLog::create('products', '创建产品：' . $title, 'product', $newId, ['title' => $title]);
                set_flash_message('success', '添加成功');
                $filter_category = $_GET['filter_category'] ?? '';
                redirect('lty-admin/products.php' . ($filter_category ? '?filter_category=' . $filter_category : ''));
            }
        }
    }
    
    if ($action === 'add_category' || $action === 'edit_category') {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $status = isset($_POST['status']) ? 1 : 0;
        
        if (empty($name) || empty($slug)) {
            set_flash_message('error', '请填写分类名称和标识');
        } else {
            $data = [
                'name' => $name,
                'slug' => $slug,
                'description' => $description ?: null,
                'sort_order' => $sortOrder,
                'status' => $status
            ];
            
            if ($action === 'edit_category' && $id) {
                $db->update('`lty-product_categories`', $data, 'id = ?', [$id]);
                OperationLog::update('products', '更新产品分类：' . $name, 'product_category', $id, ['name' => $name, 'slug' => $slug]);
                set_flash_message('success', '更新成功');
                redirect('lty-admin/products.php');
            } else {
                $newId = $db->insert('`lty-product_categories`', $data);
                OperationLog::create('products', '创建产品分类：' . $name, 'product_category', $newId, ['name' => $name, 'slug' => $slug]);
                set_flash_message('success', '添加成功');
                redirect('lty-admin/products.php');
            }
        }
    }
}

$success = get_flash_message('success');
$error = get_flash_message('error');

$showProductModal = false;
$showCategoryModal = false;
if ($error) {
    if ($action === 'add_product' || ($action === 'edit_product' && $id)) {
        $showProductModal = true;
        if ($action === 'edit_product' && $id) {
            $edit_product = $db->fetchOne("SELECT * FROM `lty-products` WHERE id = ?", [$id]);
        }
    }
    if ($action === 'add_category' || ($action === 'edit_category' && $id)) {
        $showCategoryModal = true;
        if ($action === 'edit_category' && $id) {
            $edit_category = $db->fetchOne("SELECT * FROM `lty-product_categories` WHERE id = ?", [$id]);
        }
    }
}

$categories = $db->fetchAll("SELECT * FROM `lty-product_categories` WHERE status = 1 ORDER BY sort_order ASC, id ASC");

$filter_category = $_GET['filter_category'] ?? '';

if ($filter_category) {
    $products = $db->fetchAll("SELECT p.*, c.name as category_name FROM `lty-products` p LEFT JOIN `lty-product_categories` c ON p.category_id = c.id WHERE p.category_id = ? ORDER BY c.sort_order ASC, p.sort_order ASC, p.id ASC", [$filter_category]);
} else {
    $products = $db->fetchAll("SELECT p.*, c.name as category_name FROM `lty-products` p LEFT JOIN `lty-product_categories` c ON p.category_id = c.id ORDER BY c.sort_order ASC, p.sort_order ASC, p.id ASC");
}

$edit_product = null;
$edit_category = null;
if (($action === 'edit_product' && $id) || $showProductModal) {
    if (!$edit_product) {
        $edit_product = $db->fetchOne("SELECT * FROM `lty-products` WHERE id = ?", [$id]);
    }
}

if (($action === 'edit_category' && $id) || $showCategoryModal) {
    if (!$edit_category) {
        $edit_category = $db->fetchOne("SELECT * FROM `lty-product_categories` WHERE id = ?", [$id]);
    }
}

$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>产品管理 - <?php echo htmlspecialchars(Settings::get('site_name', '官网系统')); ?></title>
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>产品管理</h1>
            <div style="display: flex; gap: 10px;">
                <button onclick="openSettingsModal()" class="btn btn-outline">页面标题设置</button>
                <button id="addCategoryBtn" onclick="openCategoryModal()" class="btn btn-primary" style="display: none;">添加分类</button>
                <button id="addProductBtn" onclick="openProductModal()" class="btn btn-primary">添加产品</button>
            </div>
        </div>

        <div class="tabs-container">
            <div class="tabs-nav">
                <button class="tab-btn active" data-tab="products">
                    <span class="tab-text">产品列表</span>
                    <span class="tab-count"><?php echo count($products); ?></span>
                </button>
                <button class="tab-btn" data-tab="categories">
                    <span class="tab-text">分类管理</span>
                    <span class="tab-count"><?php echo count($categories); ?></span>
                </button>
            </div>
        </div>

        <div id="productsTab" class="tab-content active tab-content-no-padding">
            <div class="content-card">
                <div class="card-body" style="padding: 0;">
                    <?php if ($success): ?>
                        <div class="alert alert-success" style="margin: 16px;"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    
                    <div class="filter-bar" style="padding: 16px; border-bottom: 1px solid var(--border-color);">
                        <form method="GET" style="display: flex; align-items: center; gap: 12px;">
                            <label style="font-weight: 600; color: var(--text-primary);">分类筛选：</label>
                            <select name="filter_category" id="filterCategory" onchange="this.form.submit()" style="padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--bg-card); color: var(--text-primary); font-size: 14px; cursor: pointer;">
                                <option value="">全部产品</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo ($filter_category == $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span style="color: var(--text-muted); font-size: 13px;">共 <?php echo count($products); ?> 个产品</span>
                        </form>
                    </div>
                    
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th style="width: 80px;">图片</th>
                                    <th style="width: 300px;">产品信息</th>
                                    <th style="width: 150px;">分类</th>
                                    <th style="width: 80px;">排序</th>
                                    <th style="width: 100px;">状态</th>
                                    <th style="width: 200px;">操作</th>
                                </tr>
                            </thead>
                            <tbody class="draggable-tbody">
                                <?php if (empty($products)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 60px 20px; color: var(--text-muted);">
                                            <div style="font-size: 48px; margin-bottom: 16px;">📦</div>
                                            <div style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">暂无产品</div>
                                            <div style="font-size: 14px;">该分类下还没有添加产品</div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($products as $product): ?>
                                        <tr data-id="<?php echo $product['id']; ?>" class="draggable-row" draggable="true">
                                        <td>
                                            <?php if (!empty($product['image'])): ?>
                                                <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                            <?php else: ?>
                                                <span style="color: var(--text-muted);">无图片</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 4px;"><?php echo htmlspecialchars($product['title']); ?></div>
                                            <div style="font-size: 12px; color: var(--text-muted);"><?php echo htmlspecialchars(mb_substr($product['description'], 0, 40)); ?>...</div>
                                        </td>
                                        <td>
                                            <?php if (!empty($product['category_name'])): ?>
                                                <span class="badge badge-info"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                            <?php else: ?>
                                                <span style="color: var(--text-muted);">未分类</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="sort-badge"><?php echo $product['sort_order']; ?></span>
                                        </td>
                                        <td>
                                            <?php if ($product['status'] == 1): ?>
                                                <span class="badge badge-success">启用</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">禁用</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="actions">
                                                <button onclick="openUpdatesModal(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['title']); ?>')" class="btn btn-sm btn-outline" draggable="false">更新日志</button>
                                                <a href="products.php?action=edit_product&id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline" draggable="false">编辑</a>
                                                <a href="products.php?action=delete_product&id=<?php echo $product['id']; ?>" class="btn btn-sm btn-danger" draggable="false" onclick="return confirm('确定要删除吗？');">删除</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div id="categoriesTab" class="tab-content tab-content-no-padding">
            <div class="content-card">
                <div class="card-body" style="padding: 0;">
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th style="width: 200px;">分类名称</th>
                                    <th style="width: 150px;">标识</th>
                                    <th style="width: 300px;">描述</th>
                                    <th style="width: 80px;">排序</th>
                                    <th style="width: 100px;">状态</th>
                                    <th style="width: 150px;">操作</th>
                                </tr>
                            </thead>
                            <tbody class="draggable-tbody">
                                <?php foreach ($categories as $category): ?>
                                    <tr data-id="<?php echo $category['id']; ?>" class="draggable-row" draggable="true">
                                        <td>
                                            <div style="font-weight: 600; color: var(--text-primary);"><?php echo htmlspecialchars($category['name']); ?></div>
                                        </td>
                                        <td>
                                            <code style="background: var(--bg-secondary); padding: 4px 8px; border-radius: 4px; font-size: 12px; color: var(--primary-color);"><?php echo htmlspecialchars($category['slug']); ?></code>
                                        </td>
                                        <td>
                                            <div style="color: var(--text-muted); font-size: 13px;"><?php echo htmlspecialchars(mb_substr($category['description'] ?? '', 0, 50)); ?>...</div>
                                        </td>
                                        <td>
                                            <span class="sort-badge"><?php echo $category['sort_order']; ?></span>
                                        </td>
                                        <td>
                                            <?php if ($category['status'] == 1): ?>
                                                <span class="badge badge-success">启用</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">禁用</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="actions">
                                                <a href="products.php?action=edit_category&id=<?php echo $category['id']; ?>" class="btn btn-sm btn-outline" draggable="false">编辑</a>
                                                <a href="products.php?action=delete_category&id=<?php echo $category['id']; ?>" class="btn btn-sm btn-danger" draggable="false" onclick="return confirm('确定要删除吗？');">删除</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="main-footer">
            <div class="footer-content">
                <span class="footer-line">© 2026 <?php echo htmlspecialchars(Settings::get('site_name', '官网系统')); ?> 1.0</span>
                <span class="footer-line">徐州邻兔跃IT工作室提供技术支持版权所有</span>
            </div>
        </div>
    </div>

    <div id="productModal" class="modal <?php echo ($action === 'add_product' || ($action === 'edit_product' && $edit_product)) ? 'active' : ''; ?>">
        <div class="modal-content">
            <div class="modal-header">
                <h3><?php echo $action === 'edit_product' && $edit_product ? '编辑产品' : '添加产品'; ?></h3>
                <button onclick="closeProductModal()" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form id="productForm" method="POST" autocomplete="off">
                    <input type="hidden" name="action" value="<?php echo $action; ?>">
                    
                    <div class="form-group">
                        <label for="title">标题 *</label>
                        <input type="text" id="title" name="title" required value="<?php echo ($action === 'edit_product' && $edit_product) ? htmlspecialchars($edit_product['title']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">描述 *</label>
                        <textarea id="description" name="description" rows="4" required><?php echo ($action === 'edit_product' && $edit_product) ? htmlspecialchars($edit_product['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id">分类 *</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">请选择分类</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo ($action === 'edit_product' && $edit_product && $edit_product['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="link">链接</label>
                        <input type="text" id="link" name="link" value="<?php echo ($action === 'edit_product' && $edit_product) ? htmlspecialchars($edit_product['link']) : ''; ?>" placeholder="产品链接地址">
                    </div>
                    
                    <div class="form-group">
                        <label>排序序号</label>
                        <input type="text" value="<?php echo $action === 'edit_product' && $edit_product ? ($edit_product['sort_order'] ?? '') : '自动排序'; ?>" readonly style="background-color: #f5f5f5; cursor: not-allowed;">
                    </div>
                    
                    <h4 style="margin: 20px 0 15px; padding-bottom: 10px; border-bottom: 1px solid #e2e8f0; color: #0f172a; font-size: 16px;">图片设置</h4>
                    
                    <h5 style="margin: 0 0 10px; color: #64748b; font-size: 14px; font-weight: 500;">产品主图</h5>
                    
                    <div class="form-group">
                        <label for="image_url">图片链接 *</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="text" id="image_url" name="image_url" required value="<?php echo ($action === 'edit_product' && $edit_product) ? htmlspecialchars($edit_product['image']) : ''; ?>" placeholder="点击选择图片或手动输入图片URL" oninput="previewUrlImage(this)" style="flex: 1;">
                            <button type="button" class="btn btn-outline" onclick="openMediaLibrary('image_url')">选择图片</button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>图片预览</label>
                        <div id="image_preview" style="margin-top: 10px;">
                            <?php if ($action === 'edit_product' && $edit_product && !empty($edit_product['image'])): ?>
                                <div style="position: relative; display: inline-block;">
                                    <img src="<?php echo htmlspecialchars($edit_product['image']); ?>" alt="预览" style="max-width: 200px; max-height: 200px; object-fit: cover; border-radius: 4px;">
                                    <button type="button" onclick="removeMainImage()" style="position: absolute; top: -8px; right: -8px; width: 24px; height: 24px; border: none; border-radius: 50%; background: #ef4444; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 16px; line-height: 1; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">×</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <h5 style="margin: 20px 0 10px; color: #64748b; font-size: 14px; font-weight: 500;">项目图片</h5>
                    
                    <div class="form-group">
                        <label for="images">图片列表</label>
                        <div style="display: flex; gap: 10px;">
                            <textarea id="images" name="images" rows="6" placeholder="每行一个图片URL" style="flex: 1;"><?php 
                                if ($action === 'edit_product' && $edit_product && !empty($edit_product['images'])) {
                                    $imagesArr = json_decode($edit_product['images'], true);
                                    if (is_array($imagesArr)) {
                                        echo htmlspecialchars(implode("\n", $imagesArr));
                                    }
                                }
                            ?></textarea>
                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                <button type="button" class="btn btn-outline" onclick="openMediaLibraryForImages()">选择图片</button>
                                <button type="button" class="btn btn-outline" onclick="clearImages()">清空图片</button>
                            </div>
                        </div>
                        <small style="color: #666; font-size: 12px;">每行输入一个图片URL，也可以点击选择图片按钮从媒体库选择。</small>
                    </div>
                    
                    <div class="form-group" id="images_preview" style="display: none;">
                        <label>图片预览</label>
                        <div id="images_preview_grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; margin-top: 10px;"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="status_text">状态文本</label>
                        <input type="text" id="status_text" name="status_text" value="<?php echo ($action === 'edit_product' && $edit_product) ? htmlspecialchars($edit_product['status_text'] ?? '') : ''; ?>" placeholder="如：预售中、运营中">
                    </div>
                    
                    <div class="form-group">
                        <label for="release_date">发布日期</label>
                        <input type="date" id="release_date" name="release_date" value="<?php echo ($action === 'edit_product' && $edit_product && $edit_product['release_date']) ? date('Y-m-d', strtotime($edit_product['release_date'])) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="demo_url">演示链接</label>
                        <input type="text" id="demo_url" name="demo_url" value="<?php echo ($action === 'edit_product' && $edit_product) ? htmlspecialchars($edit_product['demo_url'] ?? '') : ''; ?>" placeholder="在线演示地址">
                    </div>
                    
                    <div class="form-group">
                        <label for="download_url">下载链接</label>
                        <input type="text" id="download_url" name="download_url" value="<?php echo ($action === 'edit_product' && $edit_product) ? htmlspecialchars($edit_product['download_url'] ?? '') : ''; ?>" placeholder="下载地址">
                    </div>
                    
                    <div class="form-group">
                        <label for="price">价格</label>
                        <input type="number" id="price" name="price" step="0.01" value="<?php echo ($action === 'edit_product' && $edit_product) ? htmlspecialchars($edit_product['price'] ?? '') : ''; ?>" placeholder="0.00">
                    </div>
                    
                    <div class="form-group">
                        <label for="features">功能特性（每行一个）</label>
                        <textarea id="features" name="features" rows="5" placeholder="多域名管理&#10;智能解析&#10;快速稳定"><?php 
                            if ($action === 'edit_product' && $edit_product && $edit_product['features']) {
                                $decodedFeatures = json_decode($edit_product['features'], true);
                                if (is_array($decodedFeatures)) {
                                    echo htmlspecialchars(implode("\n", $decodedFeatures));
                                } else {
                                    echo htmlspecialchars($edit_product['features']);
                                }
                            }
                        ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="tech_stack">技术栈（每行一个）</label>
                        <textarea id="tech_stack" name="tech_stack" rows="5" placeholder="PHP&#10;MySQL&#10;Redis"><?php 
                            if ($action === 'edit_product' && $edit_product && $edit_product['tech_stack']) {
                                $decodedTechStack = json_decode($edit_product['tech_stack'], true);
                                if (is_array($decodedTechStack)) {
                                    echo htmlspecialchars(implode("\n", $decodedTechStack));
                                } else {
                                    echo htmlspecialchars($edit_product['tech_stack']);
                                }
                            }
                        ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="full_description">完整描述</label>
                        <textarea id="full_description" name="full_description" rows="6"><?php echo ($action === 'edit_product' && $edit_product) ? htmlspecialchars($edit_product['full_description'] ?? '') : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="status" value="1" <?php echo ($action === 'edit_product' && $edit_product && $edit_product['status'] != 1) ? '' : 'checked'; ?>>
                            启用
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_original" value="1" <?php echo ($action === 'edit_product' && $edit_product && $edit_product['is_original'] == 1) ? 'checked' : 'checked'; ?>>
                            标记为原创
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary"><?php echo $action === 'edit_product' && $edit_product ? '更新' : '添加'; ?></button>
                </form>
            </div>
        </div>
    </div>

    <div id="categoryModal" class="modal <?php echo ($action === 'add_category' || ($action === 'edit_category' && $edit_category)) ? 'active' : ''; ?>">
        <div class="modal-content">
            <div class="modal-header">
                <h3><?php echo $action === 'edit_category' && $edit_category ? '编辑分类' : '添加分类'; ?></h3>
                <button onclick="closeCategoryModal()" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form method="POST" autocomplete="off">
                    <input type="hidden" name="action" value="<?php echo $action; ?>">
                    
                    <div class="form-group">
                        <label for="name">分类名称 *</label>
                        <input type="text" id="name" name="name" required value="<?php echo ($action === 'edit_category' && $edit_category) ? htmlspecialchars($edit_category['name']) : ''; ?>" placeholder="如：网站项目">
                    </div>
                    
                    <div class="form-group">
                        <label for="slug">分类标识 *</label>
                        <input type="text" id="slug" name="slug" required value="<?php echo ($action === 'edit_category' && $edit_category) ? htmlspecialchars($edit_category['slug']) : ''; ?>" placeholder="如：website" pattern="[a-z0-9_-]+" title="只能包含小写字母、数字、下划线和连字符">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">分类描述</label>
                        <textarea id="description" name="description" rows="3" placeholder="分类的详细描述"><?php echo ($action === 'edit_category' && $edit_category) ? htmlspecialchars($edit_category['description'] ?? '') : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="sort_order">排序序号</label>
                        <input type="number" id="sort_order" name="sort_order" value="<?php echo ($action === 'edit_category' && $edit_category) ? ($edit_category['sort_order'] ?? 0) : 0; ?>" min="0">
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="status" value="1" <?php echo ($action === 'edit_category' && $edit_category && $edit_category['status'] != 1) ? '' : 'checked'; ?>>
                            启用
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary"><?php echo $action === 'edit_category' && $edit_category ? '更新' : '添加'; ?></button>
                </form>
            </div>
        </div>
    </div>

    <div id="settingsModal" class="modal <?php echo isset($_GET['open_settings']) ? 'active' : ''; ?>">
        <div class="modal-content">
            <div class="modal-header">
                <h3>页面标题设置</h3>
                <button onclick="closeSettingsModal()" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="save_settings" value="1">
                    <div class="form-group">
                        <label for="products_title">产品展示标题</label>
                        <input type="text" id="products_title" name="products_title" value="<?php echo htmlspecialchars(Settings::get('products_title', '我们的产品')); ?>">
                    </div>
                    <div class="form-group">
                        <label for="products_subtitle">产品展示副标题</label>
                        <input type="text" id="products_subtitle" name="products_subtitle" value="<?php echo htmlspecialchars(Settings::get('products_subtitle', '以下展示的是工作室自主研发的程序项目')); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">保存设置</button>
                </form>
            </div>
        </div>
    </div>

    <div id="updatesModal" class="modal">
        <div class="modal-content" style="max-width: 900px;">
            <div class="modal-header">
                <h3>更新日志管理 - <span id="updatesProductName"></span></h3>
                <button onclick="closeUpdatesModal()" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div style="margin-bottom: 20px;">
                    <button onclick="openAddUpdateModal()" class="btn btn-primary">添加更新</button>
                </div>
                
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>版本</th>
                                <th>发布日期</th>
                                <th>内容</th>
                                <th>状态</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody id="updatesTableBody">
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);">加载中...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="updateFormModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="updateFormTitle">添加更新</h3>
                <button onclick="closeUpdateFormModal()" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <form id="updateForm" autocomplete="off">
                    <input type="hidden" id="updateId" name="id">
                    <input type="hidden" id="updateProductId" name="product_id">
                    
                    <div class="form-group">
                        <label for="updateVersion">版本号 *</label>
                        <input type="text" id="updateVersion" name="version" required placeholder="如：v1.0.0">
                    </div>
                    
                    <div class="form-group">
                        <label for="updateReleaseDate">发布日期 *</label>
                        <input type="date" id="updateReleaseDate" name="release_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="updateContent">更新内容 *</label>
                        <textarea id="updateContent" name="content" rows="6" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="updateStatus" name="status" value="1" checked>
                            启用
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">保存</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        let pendingImagesFiles = [];
        
        function openMediaLibrary(inputId) {
            MediaPicker.open({
                multiple: false,
                onSelect: function(url) {
                    const input = document.getElementById(inputId);
                    if (input) {
                        input.value = url;
                        previewUrlImage(input);
                    }
                }
            });
        }
        
        function openMediaLibraryForImages() {
            MediaPicker.open({
                multiple: true,
                onSelect: function(urls) {
                    const textarea = document.getElementById('images');
                    urls.forEach(url => {
                        const currentValue = textarea.value.trim();
                        if (currentValue) {
                            textarea.value = currentValue + '\n' + url;
                        } else {
                            textarea.value = url;
                        }
                    });
                    updateImagesPreview();
                }
            });
        }
        
        function openProductModal() {
            window.location.href = 'products.php?action=add_product';
        }
        
        function closeProductModal() {
            document.getElementById('productModal').classList.remove('active');
            window.location.href = 'products.php';
        }
        
        function openCategoryModal() {
            window.location.href = 'products.php?action=add_category';
        }
        
        function closeCategoryModal() {
            document.getElementById('categoryModal').classList.remove('active');
            window.location.href = 'products.php';
        }
        
        function openSettingsModal() {
            document.getElementById('settingsModal').classList.add('active');
        }
        
        function closeSettingsModal() {
            document.getElementById('settingsModal').classList.remove('active');
        }
        
        function openUpdatesModal(productId, productName) {
            document.getElementById('updatesProductName').textContent = productName;
            document.getElementById('updatesModal').classList.add('active');
            window.currentProductId = productId;
            loadUpdates(productId);
        }
        
        function closeUpdatesModal() {
            document.getElementById('updatesModal').classList.remove('active');
        }
        
        function loadUpdates(productId) {
            const tbody = document.getElementById('updatesTableBody');
            tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);">加载中...</td></tr>';
            
            fetch(`../api/products.php?action=get_updates&product_id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.updates.length > 0) {
                        tbody.innerHTML = data.updates.map(update => `
                            <tr>
                                <td>${escapeHtml(update.version)}</td>
                                <td>${escapeHtml(update.release_date)}</td>
                                <td>${escapeHtml(update.content.substring(0, 50))}...</td>
                                <td>
                                    ${update.status == 1 
                                        ? '<span class="badge badge-success">启用</span>' 
                                        : '<span class="badge badge-warning">禁用</span>'}
                                </td>
                                <td>
                                    <div class="actions">
                                        <button onclick="openEditUpdateModal(${update.id}, ${productId})" class="btn btn-sm btn-outline">编辑</button>
                                        <button onclick="deleteUpdate(${update.id}, ${productId})" class="btn btn-sm btn-danger">删除</button>
                                    </div>
                                </td>
                            </tr>
                        `).join('');
                    } else {
                        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);">暂无更新日志</td></tr>';
                    }
                })
                .catch(error => {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);">加载失败</td></tr>';
                });
        }
        
        function openAddUpdateModal() {
            const productId = window.currentProductId;
            
            document.getElementById('updateFormTitle').textContent = '添加更新';
            document.getElementById('updateId').value = '';
            document.getElementById('updateProductId').value = productId;
            document.getElementById('updateVersion').value = '';
            document.getElementById('updateReleaseDate').value = new Date().toISOString().split('T')[0];
            document.getElementById('updateContent').value = '';
            document.getElementById('updateStatus').checked = true;
            document.getElementById('updateFormModal').classList.add('active');
        }
        
        function openEditUpdateModal(updateId, productId) {
            fetch(`../api/products.php?action=get_update&id=${updateId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.update) {
                        const update = data.update;
                        document.getElementById('updateFormTitle').textContent = '编辑更新';
                        document.getElementById('updateId').value = update.id;
                        document.getElementById('updateProductId').value = productId;
                        document.getElementById('updateVersion').value = update.version;
                        document.getElementById('updateReleaseDate').value = update.release_date;
                        document.getElementById('updateContent').value = update.content;
                        document.getElementById('updateStatus').checked = update.status == 1;
                        document.getElementById('updateFormModal').classList.add('active');
                    }
                });
        }
        
        function closeUpdateFormModal() {
            document.getElementById('updateFormModal').classList.remove('active');
        }
        
        function saveUpdate() {
            const form = document.getElementById('updateForm');
            const formData = new FormData(form);
            
            const updateId = document.getElementById('updateId').value;
            const action = updateId ? 'edit_update' : 'add_update';
            
            formData.append('action', action);
            
            fetch('../api/products.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeUpdateFormModal();
                    loadUpdates(document.getElementById('updateProductId').value);
                } else {
                    alert(data.message || '保存失败');
                }
            })
            .catch(error => {
                alert('保存失败：' + error.message);
            });
        }
        
        function deleteUpdate(updateId, productId) {
            if (confirm('确定要删除吗？')) {
                fetch(`../api/products.php?action=delete_update&id=${updateId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            loadUpdates(productId);
                        } else {
                            alert(data.message || '删除失败');
                        }
                    })
                    .catch(error => {
                        alert('删除失败：' + error.message);
                    });
            }
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        document.getElementById('updateForm').addEventListener('submit', function(e) {
            e.preventDefault();
            saveUpdate();
        });
        
        function clearForm() {
            document.querySelector('form').reset();
            document.getElementById('image_preview').innerHTML = '';
        }
        
        function previewImage(input) {
            const preview = document.getElementById('image_preview');
            const urlInput = document.getElementById('image_url');
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="预览" style="max-width: 200px; max-height: 200px; object-fit: cover; border-radius: 4px;">';
                    
                    const formData = new FormData();
                    formData.append('product_image', file);
                    
                    fetch('../api/settings.php?action=upload_product', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.url) {
                            urlInput.value = data.url;
                            pendingMainImageFile = null;
                            input.dataset.fileSelected = 'false';
                        } else {
                            alert('上传失败：' + (data.message || '未知错误'));
                        }
                    })
                    .catch(error => {
                        console.error('上传错误:', error);
                        alert('上传失败：' + error.message);
                    });
                };
                reader.readAsDataURL(file);
            }
        }
        
        function previewUrlImage(input) {
            const preview = document.getElementById('image_preview');
            const fileInput = document.getElementById('image');
            const url = input.value.trim();
            if (url) {
                preview.innerHTML = '<div style="position: relative; display: inline-block;"><img src="' + url + '" alt="预览" style="max-width: 200px; max-height: 200px; object-fit: cover; border-radius: 4px;" onerror="this.style.display=\'none\'"><button type="button" onclick="removeMainImage()" style="position: absolute; top: -8px; right: -8px; width: 24px; height: 24px; border: none; border-radius: 50%; background: #ef4444; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 16px; line-height: 1; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">×</button></div>';
                fileInput.value = '';
                fileInput.dataset.fileSelected = 'false';
                pendingMainImageFile = null;
            } else {
                preview.innerHTML = '';
            }
        }
        
        function removeMainImage() {
            if (!confirm('确定要移除图片吗？')) return;
            const input = document.getElementById('image_url');
            const preview = document.getElementById('image_preview');
            const fileInput = document.getElementById('image');
            input.value = '';
            preview.innerHTML = '';
            fileInput.value = '';
            pendingMainImageFile = null;
        }
        
        function handleMultipleImagesUpload(input) {
            const files = input.files;
            if (files && files.length > 0) {
                const imagesPreview = document.getElementById('images_preview');
                const imagesPreviewGrid = document.getElementById('images_preview_grid');
                const imagesTextarea = document.getElementById('images');
                
                imagesPreview.style.display = 'block';
                
                Array.from(files).forEach((file) => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const previewItem = document.createElement('div');
                        previewItem.className = 'image-preview-item uploading';
                        previewItem.style.cssText = 'position: relative; aspect-ratio: 1; overflow: hidden; border-radius: 8px; cursor: move; transition: transform 0.2s ease, box-shadow 0.2s ease;';
                        previewItem.innerHTML = `
                            <img src="${e.target.result}" alt="预览" style="width: 100%; height: 100%; object-fit: cover;">
                            <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); display: flex; align-items: center; justify-content: center; color: white; font-size: 12px;">上传中...</div>
                        `;
                        imagesPreviewGrid.appendChild(previewItem);
                        
                        const formData = new FormData();
                        formData.append('product_image', file);
                        
                        fetch('../api/settings.php?action=upload_product', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.url) {
                                const existingUrls = imagesTextarea.value.trim().split('\n').filter(url => url.trim());
                                existingUrls.push(data.url);
                                imagesTextarea.value = existingUrls.join('\n');
                                
                                previewItem.classList.remove('uploading');
                                previewItem.innerHTML = `
                                    <img src="${data.url}" alt="预览" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.parentElement.style.display=\'none\'">
                                    <button type="button" onclick="removeImage(${existingUrls.length - 1}, '${data.url.replace(/'/g, "\\'")}')" style="position: absolute; top: 4px; right: 4px; width: 24px; height: 24px; border: none; border-radius: 50%; background: rgba(239, 68, 68, 0.9); color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 16px; line-height: 1; z-index: 10;">×</button>
                                `;
                                previewItem.draggable = true;
                                previewItem.dataset.index = existingUrls.length - 1;
                                
                                updateImagesPreview();
                            } else {
                                previewItem.innerHTML = `
                                    <img src="${e.target.result}" alt="预览" style="width: 100%; height: 100%; object-fit: cover;">
                                    <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(239, 68, 68, 0.8); display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; padding: 8px; text-align: center;">上传失败</div>
                                `;
                            }
                        })
                        .catch(error => {
                            previewItem.innerHTML = `
                                <img src="${e.target.result}" alt="预览" style="width: 100%; height: 100%; object-fit: cover;">
                                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(239, 68, 68, 0.8); display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; padding: 8px; text-align: center;">上传失败</div>
                            `;
                        });
                    };
                    reader.readAsDataURL(file);
                });
                
                input.value = '';
            }
        }
        
        function removeImage(index, imageUrl, isNew = false) {
            if (!confirm('确定要移除这张图片吗？')) return;
            
            const imagesTextarea = document.getElementById('images');
            const imagesPreviewGrid = document.getElementById('images_preview_grid');
            
            const urls = imagesTextarea.value.trim().split('\n').filter(url => url.trim());
            
            if (isNew) {
                pendingImagesFiles.splice(index, 1);
            }
            
            urls.splice(index, 1);
            imagesTextarea.value = urls.join('\n');
            
            updateImagesPreview();
        }
        
        function clearImages() {
            const imagesTextarea = document.getElementById('images');
            const imagesPreview = document.getElementById('images_preview');
            const imagesPreviewGrid = document.getElementById('images_preview_grid');
            
            imagesTextarea.value = '';
            imagesPreviewGrid.innerHTML = '';
            imagesPreview.style.display = 'none';
            pendingImagesFiles = [];
        }
        
        function updateImagesPreview() {
            const imagesTextarea = document.getElementById('images');
            const imagesPreview = document.getElementById('images_preview');
            const imagesPreviewGrid = document.getElementById('images_preview_grid');
            
            const urls = imagesTextarea.value.trim().split('\n').filter(url => url.trim());
            
            imagesPreviewGrid.innerHTML = '';
            
            urls.forEach((url, index) => {
                const previewItem = document.createElement('div');
                previewItem.className = 'image-preview-item';
                previewItem.style.cssText = 'position: relative; aspect-ratio: 1; overflow: hidden; border-radius: 8px; cursor: move; transition: transform 0.2s ease, box-shadow 0.2s ease;';
                previewItem.draggable = true;
                previewItem.dataset.index = index;
                previewItem.innerHTML = `
                    <img src="${url}" alt="预览" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.parentElement.style.display=\'none\'">
                    <button type="button" onclick="removeImage(${index}, '${url.replace(/'/g, "\\'")}')" style="position: absolute; top: 4px; right: 4px; width: 24px; height: 24px; border: none; border-radius: 50%; background: rgba(239, 68, 68, 0.9); color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 16px; line-height: 1; z-index: 10;">×</button>
                `;
                imagesPreviewGrid.appendChild(previewItem);
            });
            
            pendingImagesFiles.forEach((pending, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewItem = document.createElement('div');
                    previewItem.className = 'image-preview-item';
                    previewItem.style.cssText = 'position: relative; aspect-ratio: 1; overflow: hidden; border-radius: 8px; cursor: move; transition: transform 0.2s ease, box-shadow 0.2s ease;';
                    previewItem.innerHTML = `
                        <img src="${e.target.result}" alt="预览" style="width: 100%; height: 100%; object-fit: cover;">
                        <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); display: flex; align-items: center; justify-content: center; color: white; font-size: 12px;">待上传</div>
                        <button type="button" onclick="removePendingImage(${index})" style="position: absolute; top: 4px; right: 4px; width: 24px; height: 24px; border: none; border-radius: 50%; background: rgba(239, 68, 68, 0.9); color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 16px; line-height: 1; z-index: 10;">×</button>
                    `;
                    imagesPreviewGrid.appendChild(previewItem);
                };
                reader.readAsDataURL(pending.file);
            });
            
            if (urls.length > 0 || pendingImagesFiles.length > 0) {
                imagesPreview.style.display = 'block';
                initImageDragSort(imagesPreviewGrid);
            } else {
                imagesPreview.style.display = 'none';
            }
        }
        
        function removePendingImage(index) {
            pendingImagesFiles.splice(index, 1);
            updateImagesPreview();
        }
        
        let imageDragSortInitialized = false;
        
        function initImageDragSort(container) {
            if (imageDragSortInitialized) return;
            imageDragSortInitialized = true;
            
            let draggedItem = null;
            let placeholder = null;
            
            const placeholderEl = document.createElement('div');
            placeholderEl.className = 'drag-placeholder';
            placeholderEl.style.cssText = 'position: relative; aspect-ratio: 1; overflow: hidden; border-radius: 8px; border: 2px dashed #f59e0b; background: rgba(245, 158, 11, 0.15); display: flex; align-items: center; justify-content: center;';
            placeholderEl.innerHTML = '<span style="color: #f59e0b; font-size: 14px; font-weight: 500;">放置图片</span>';
            
            container.addEventListener('dragstart', function(e) {
                draggedItem = e.target.closest('.image-preview-item');
                if (draggedItem) {
                    e.dataTransfer.effectAllowed = 'move';
                    draggedItem.style.opacity = '0.4';
                    draggedItem.style.transform = 'scale(0.95)';
                    draggedItem.style.boxShadow = '0 10px 25px rgba(0, 0, 0, 0.2)';
                }
            });
            
            container.addEventListener('dragend', function(e) {
                if (draggedItem) {
                    draggedItem.style.opacity = '1';
                    draggedItem.style.transform = 'scale(1)';
                    draggedItem.style.boxShadow = 'none';
                    if (placeholder && placeholder.parentNode) {
                        placeholder.parentNode.removeChild(placeholder);
                    }
                    placeholder = null;
                    draggedItem = null;
                }
            });
            
            container.addEventListener('dragover', function(e) {
                e.preventDefault();
                const target = e.target.closest('.image-preview-item');
                if (target && target !== draggedItem) {
                    const rect = target.getBoundingClientRect();
                    const midX = rect.left + rect.width / 2;
                    if (e.clientX < midX) {
                        container.insertBefore(placeholderEl, target);
                    } else {
                        container.insertBefore(placeholderEl, target.nextSibling);
                    }
                    placeholder = placeholderEl;
                }
            });
            
            container.addEventListener('drop', function(e) {
                e.preventDefault();
                if (placeholder && placeholder.parentNode) {
                    container.insertBefore(draggedItem, placeholder);
                    placeholder.parentNode.removeChild(placeholder);
                    placeholder = null;
                }
                updateImagesOrder();
            });
        }
        
        function updateImagesOrder() {
            const imagesPreviewGrid = document.getElementById('images_preview_grid');
            const imagesTextarea = document.getElementById('images');
            const items = imagesPreviewGrid.querySelectorAll('.image-preview-item');
            const urls = [];
            
            items.forEach(item => {
                const img = item.querySelector('img');
                if (img && img.src) {
                    urls.push(img.src);
                }
            });
            
            imagesTextarea.value = urls.join('\n');
        }
        
        var imagesTextarea = document.getElementById('images');
        if (imagesTextarea) {
            imagesTextarea.addEventListener('input', updateImagesPreview);
        }
        
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
            
            var imagesTextarea = document.getElementById('images');
            if (imagesTextarea && imagesTextarea.value.trim()) {
                updateImagesPreview();
            }
            
            const tabs = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');
            const addProductBtn = document.getElementById('addProductBtn');
            const addCategoryBtn = document.getElementById('addCategoryBtn');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const targetTab = this.dataset.tab;
                    
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    this.classList.add('active');
                    document.getElementById(targetTab + 'Tab').classList.add('active');
                    
                    if (targetTab === 'products') {
                        addProductBtn.style.display = 'inline-block';
                        addCategoryBtn.style.display = 'none';
                    } else {
                        addProductBtn.style.display = 'none';
                        addCategoryBtn.style.display = 'inline-block';
                    }
                });
            });
            
            document.getElementById('productForm').addEventListener('submit', function(e) {
                const fileInput = document.getElementById('image');
                const urlInput = document.getElementById('image_url');
                const imagesInput = document.getElementById('images_upload');
                
                const uploadingItems = document.querySelectorAll('.image-preview-item.uploading');
                if (uploadingItems.length > 0) {
                    e.preventDefault();
                    alert('请等待图片上传完成后再提交');
                }
            });
            
            function filterProducts() {
                const categoryId = document.getElementById('filterCategory').value;
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('filter_category', categoryId);
                window.location.href = currentUrl.toString();
            }
            
            const tbodies = document.querySelectorAll('.draggable-tbody');
            
            tbodies.forEach(tbody => {
                let draggedRow = null;
                let placeholder = null;
                
                tbody.addEventListener('dragstart', function(e) {
                    draggedRow = e.target.closest('.draggable-row');
                    e.dataTransfer.effectAllowed = 'move';
                    draggedRow.classList.add('dragging');
                    e.dataTransfer.setData('text/html', draggedRow.innerHTML);
                });
                
                tbody.addEventListener('dragend', function(e) {
                    if (draggedRow) {
                        draggedRow.classList.remove('dragging');
                        if (placeholder) {
                            placeholder.remove();
                            placeholder = null;
                        }
                        draggedRow = null;
                    }
                });
                
                tbody.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                    
                    const targetRow = e.target.closest('.draggable-row');
                    if (targetRow && targetRow !== draggedRow) {
                        const rect = targetRow.getBoundingClientRect();
                        const midY = rect.top + rect.height / 2;
                        
                        if (!placeholder) {
                            placeholder = document.createElement('tr');
                            placeholder.className = 'drag-placeholder';
                            placeholder.innerHTML = '<td colspan="6" style="padding: 10px; background: #fff9c4; border: 2px dashed #fbc02d; text-align: center; color: #f9a825;">拖放到此处</td>';
                        }
                        
                        if (e.clientY < midY) {
                            tbody.insertBefore(placeholder, targetRow);
                        } else {
                            tbody.insertBefore(placeholder, targetRow.nextSibling);
                        }
                    }
                });
                
                tbody.addEventListener('drop', function(e) {
                    e.preventDefault();
                    
                    if (draggedRow && placeholder) {
                        tbody.insertBefore(draggedRow, placeholder);
                        placeholder.remove();
                        placeholder = null;
                        
                        updateSortOrder(tbody);
                    }
                });
                
                function updateSortOrder(tbody) {
                    const rows = tbody.querySelectorAll('.draggable-row');
                    const ids = [];
                    
                    rows.forEach((row, index) => {
                        const id = row.dataset.id;
                        ids.push(id);
                        
                        const sortCell = row.querySelector('td:nth-child(4)');
                        if (sortCell) {
                            sortCell.textContent = index + 1;
                        }
                    });
                    
                    const activeTab = document.querySelector('.tab-content.active');
                    const isProducts = activeTab && activeTab.id === 'productsTab';
                    
                    fetch('../api/products.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: isProducts ? 'update_sort' : 'update_category_sort',
                            data: rows.map((row, index) => ({
                                id: row.dataset.id,
                                sort_order: index + 1
                            }))
                        })
                    }).then(response => response.json())
                      .then(result => {
                          if (result.success) {
                              location.reload();
                          }
                      });
                }
            });
        });
    </script>
    <style>
        .image-preview-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .image-preview-item:active {
            cursor: grabbing;
        }
        
        .drag-placeholder {
            animation: pulse 1.5s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
        }
    </style>
    
    <?php include 'components/media-picker.php'; ?>
    <link rel="stylesheet" href="components/media-picker.css">
    <script src="components/media-picker.js"></script>
</body>
</html>
<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/api_security.php';

APISecurity::setSecurityHeaders();
APISecurity::checkRateLimit();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

$publicActions = ['list', 'get'];
$authRequiredActions = ['update_sort'];

if (in_array($action, $authRequiredActions)) {
    require_auth();
}

switch ($action) {
    case 'list':
        try {
            $db = Database::getInstance();
            $areas = $db->fetchAll("SELECT * FROM `lty-business_areas` WHERE status = 1 ORDER BY sort_order ASC, id ASC");
            
            json_response(['success' => true, 'data' => $areas]);
        } catch (Exception $e) {
            json_response(['error' => 'Failed to fetch business areas'], 500);
        }
        break;
        
    case 'get':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($id <= 0) {
            json_response(['error' => 'Invalid business area ID'], 400);
        }
        
        try {
            $db = Database::getInstance();
            $area = $db->fetchOne("SELECT * FROM `lty-business_areas` WHERE id = ?", [$id]);
            
            if (!$area) {
                json_response(['error' => 'Business area not found'], 404);
            }
            
            json_response(['success' => true, 'data' => $area]);
        } catch (Exception $e) {
            json_response(['error' => 'Failed to fetch business area'], 500);
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
                $db->update('`lty-business_areas`', ['sort_order' => $item['sort_order']], 'id = ?', [$item['id']]);
            }
            
            $db->commit();
            json_response(['success' => true]);
        } catch (Exception $e) {
            $db->rollback();
            json_response(['error' => 'Failed to reorder business areas'], 500);
        }
        break;
        
    default:
        json_response(['error' => 'Invalid action'], 400);
}

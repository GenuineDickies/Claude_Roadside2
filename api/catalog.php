<?php
/**
 * Catalog API — Services & Parts CRUD
 * POST actions: create_service, update_service, delete_service, toggle_service_status
 *               create_part, update_part, delete_part, toggle_part_status
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/api_bootstrap.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        
        // ═══ SERVICES ════════════════════════════════════════════════════════
        
        case 'get_services':
            $stmt = $pdo->query("
                SELECT st.*, sc.name as category_name, sc.slug as category_slug 
                FROM service_types st 
                JOIN service_categories sc ON st.category_id = sc.id 
                ORDER BY sc.sort_order, st.name
            ");
            json_response(['success' => true, 'data' => $stmt->fetchAll()]);
            break;
            
        case 'create_service':
            $name = trim($_POST['name'] ?? '');
            $slug = trim($_POST['slug'] ?? '');
            $category_id = intval($_POST['category_id'] ?? 0);
            $base_rate = floatval($_POST['base_rate'] ?? 0);
            $after_hours_rate = floatval($_POST['after_hours_rate'] ?? $base_rate * 1.5);
            $description = trim($_POST['description'] ?? '');
            $active = intval($_POST['active'] ?? 1);
            
            if (!$name || !$slug || !$category_id) {
                json_response(['success' => false, 'error' => 'Name, slug, and category are required']);
            }
            
            // Check for duplicate slug
            $check = $pdo->prepare("SELECT id FROM service_types WHERE slug = ?");
            $check->execute([$slug]);
            if ($check->fetch()) {
                json_response(['success' => false, 'error' => 'A service with this slug already exists']);
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO service_types (name, slug, category_id, base_rate, after_hours_rate, description, active)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $slug, $category_id, $base_rate, $after_hours_rate, $description, $active]);
            
            json_response(['success' => true, 'data' => ['id' => $pdo->lastInsertId()]]);
            break;
            
        case 'update_service':
            $id = intval($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $slug = trim($_POST['slug'] ?? '');
            $category_id = intval($_POST['category_id'] ?? 0);
            $base_rate = floatval($_POST['base_rate'] ?? 0);
            $after_hours_rate = floatval($_POST['after_hours_rate'] ?? $base_rate * 1.5);
            $description = trim($_POST['description'] ?? '');
            $active = intval($_POST['active'] ?? 1);
            
            if (!$id || !$name || !$slug || !$category_id) {
                json_response(['success' => false, 'error' => 'ID, name, slug, and category are required']);
            }
            
            // Check for duplicate slug (excluding self)
            $check = $pdo->prepare("SELECT id FROM service_types WHERE slug = ? AND id != ?");
            $check->execute([$slug, $id]);
            if ($check->fetch()) {
                json_response(['success' => false, 'error' => 'A service with this slug already exists']);
            }
            
            $stmt = $pdo->prepare("
                UPDATE service_types 
                SET name = ?, slug = ?, category_id = ?, base_rate = ?, after_hours_rate = ?, description = ?, active = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $slug, $category_id, $base_rate, $after_hours_rate, $description, $active, $id]);
            
            json_response(['success' => true]);
            break;
            
        case 'delete_service':
            $id = intval($_POST['id'] ?? 0);
            if (!$id) {
                json_response(['success' => false, 'error' => 'ID is required']);
            }
            
            $stmt = $pdo->prepare("DELETE FROM service_types WHERE id = ?");
            $stmt->execute([$id]);
            
            json_response(['success' => true]);
            break;
            
        case 'toggle_service_status':
            $id = intval($_POST['id'] ?? 0);
            if (!$id) {
                json_response(['success' => false, 'error' => 'ID is required']);
            }
            
            $stmt = $pdo->prepare("UPDATE service_types SET active = NOT active WHERE id = ?");
            $stmt->execute([$id]);
            
            // Get new status
            $check = $pdo->prepare("SELECT active FROM service_types WHERE id = ?");
            $check->execute([$id]);
            $row = $check->fetch();
            
            json_response(['success' => true, 'data' => ['active' => (bool)$row['active']]]);
            break;
            
        // ═══ PARTS ═══════════════════════════════════════════════════════════
        
        case 'get_parts':
            $stmt = $pdo->query("SELECT * FROM parts_inventory ORDER BY category, name");
            json_response(['success' => true, 'data' => $stmt->fetchAll()]);
            break;
            
        case 'create_part':
            $part_number = trim($_POST['part_number'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $unit_cost = floatval($_POST['unit_cost'] ?? 0);
            $markup_pct = floatval($_POST['markup_pct'] ?? 50);
            $quantity_on_hand = intval($_POST['quantity_on_hand'] ?? 0);
            $reorder_point = intval($_POST['reorder_point'] ?? 5);
            $supplier = trim($_POST['supplier'] ?? '');
            $active = intval($_POST['active'] ?? 1);
            
            if (!$part_number || !$name) {
                json_response(['success' => false, 'error' => 'Part number and name are required']);
            }
            
            // Check for duplicate part number
            $check = $pdo->prepare("SELECT id FROM parts_inventory WHERE part_number = ?");
            $check->execute([$part_number]);
            if ($check->fetch()) {
                json_response(['success' => false, 'error' => 'A part with this number already exists']);
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO parts_inventory 
                (part_number, name, category, description, unit_cost, markup_pct, quantity_on_hand, reorder_point, supplier, active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $part_number, $name, $category ?: null, $description, 
                $unit_cost, $markup_pct, $quantity_on_hand, $reorder_point, 
                $supplier ?: null, $active
            ]);
            
            json_response(['success' => true, 'data' => ['id' => $pdo->lastInsertId()]]);
            break;
            
        case 'update_part':
            $id = intval($_POST['id'] ?? 0);
            $part_number = trim($_POST['part_number'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $unit_cost = floatval($_POST['unit_cost'] ?? 0);
            $markup_pct = floatval($_POST['markup_pct'] ?? 50);
            $quantity_on_hand = intval($_POST['quantity_on_hand'] ?? 0);
            $reorder_point = intval($_POST['reorder_point'] ?? 5);
            $supplier = trim($_POST['supplier'] ?? '');
            $active = intval($_POST['active'] ?? 1);
            
            if (!$id || !$part_number || !$name) {
                json_response(['success' => false, 'error' => 'ID, part number, and name are required']);
            }
            
            // Check for duplicate part number (excluding self)
            $check = $pdo->prepare("SELECT id FROM parts_inventory WHERE part_number = ? AND id != ?");
            $check->execute([$part_number, $id]);
            if ($check->fetch()) {
                json_response(['success' => false, 'error' => 'A part with this number already exists']);
            }
            
            $stmt = $pdo->prepare("
                UPDATE parts_inventory 
                SET part_number = ?, name = ?, category = ?, description = ?, 
                    unit_cost = ?, markup_pct = ?, quantity_on_hand = ?, 
                    reorder_point = ?, supplier = ?, active = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $part_number, $name, $category ?: null, $description, 
                $unit_cost, $markup_pct, $quantity_on_hand, $reorder_point, 
                $supplier ?: null, $active, $id
            ]);
            
            json_response(['success' => true]);
            break;
            
        case 'delete_part':
            $id = intval($_POST['id'] ?? 0);
            if (!$id) {
                json_response(['success' => false, 'error' => 'ID is required']);
            }
            
            $stmt = $pdo->prepare("DELETE FROM parts_inventory WHERE id = ?");
            $stmt->execute([$id]);
            
            json_response(['success' => true]);
            break;
            
        case 'toggle_part_status':
            $id = intval($_POST['id'] ?? 0);
            if (!$id) {
                json_response(['success' => false, 'error' => 'ID is required']);
            }
            
            $stmt = $pdo->prepare("UPDATE parts_inventory SET active = NOT active WHERE id = ?");
            $stmt->execute([$id]);
            
            // Get new status
            $check = $pdo->prepare("SELECT active FROM parts_inventory WHERE id = ?");
            $check->execute([$id]);
            $row = $check->fetch();
            
            json_response(['success' => true, 'data' => ['active' => (bool)$row['active']]]);
            break;
            
        // ═══ CATEGORIES ══════════════════════════════════════════════════════
        
        case 'get_categories':
            $stmt = $pdo->query("SELECT * FROM service_categories WHERE active = 1 ORDER BY sort_order");
            json_response(['success' => true, 'data' => $stmt->fetchAll()]);
            break;
            
        default:
            json_response(['success' => false, 'error' => 'Unknown action: ' . $action]);
    }
    
} catch (PDOException $e) {
    error_log('Catalog API Error: ' . $e->getMessage());
    json_response(['success' => false, 'error' => 'Database error']);
}

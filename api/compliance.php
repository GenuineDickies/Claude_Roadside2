<?php
/**
 * Compliance API - Simple document/permit/license tracker
 * User records what they need, whether they have it, and when it expires.
 * Non-blocking: just gentle reminders, never prevents work.
 */
require_once __DIR__ . '/../includes/api_bootstrap.php';

$pdo->exec("CREATE TABLE IF NOT EXISTS compliance_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    category ENUM('license','permit','certification','insurance','registration','inspection','other') DEFAULT 'other',
    document_number VARCHAR(100) DEFAULT NULL,
    issuing_authority VARCHAR(200) DEFAULT NULL,
    have_it TINYINT(1) DEFAULT 0,
    issue_date DATE DEFAULT NULL,
    expiry_date DATE DEFAULT NULL,
    remind_days_before INT DEFAULT 30,
    cost DECIMAL(10,2) DEFAULT 0.00,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_expiry (expiry_date),
    KEY idx_have (have_it)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

try { $pdo->exec("DROP TABLE IF EXISTS compliance_audit_log"); } catch(Exception $e) {}
try { $pdo->exec("DROP TABLE IF EXISTS compliance_records"); } catch(Exception $e) {}
try { $pdo->exec("DROP TABLE IF EXISTS compliance_types"); } catch(Exception $e) {}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($action) {

        case 'list':
            $where = ['1=1'];
            $params = [];
            if (!empty($_GET['category'])) { $where[] = 'category = ?'; $params[] = $_GET['category']; }
            if (isset($_GET['have_it']) && $_GET['have_it'] !== '') { $where[] = 'have_it = ?'; $params[] = (int)$_GET['have_it']; }
            if (!empty($_GET['search'])) {
                $where[] = '(name LIKE ? OR document_number LIKE ? OR issuing_authority LIKE ?)';
                $s = '%' . $_GET['search'] . '%';
                $params[] = $s; $params[] = $s; $params[] = $s;
            }
            $whereClause = implode(' AND ', $where);
            $stmt = $pdo->prepare("
                SELECT *,
                    CASE WHEN have_it = 0 THEN 'missing' WHEN expiry_date IS NOT NULL AND expiry_date < CURDATE() THEN 'expired' WHEN expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL remind_days_before DAY) THEN 'expiring' WHEN have_it = 1 THEN 'good' ELSE 'unknown' END as computed_status,
                    DATEDIFF(expiry_date, CURDATE()) as days_until_expiry
                FROM compliance_items WHERE $whereClause
                ORDER BY CASE WHEN have_it = 0 THEN 1 WHEN expiry_date IS NOT NULL AND expiry_date < CURDATE() THEN 2 WHEN expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL remind_days_before DAY) THEN 3 ELSE 4 END, expiry_date ASC
            ");
            $stmt->execute($params);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'get':
            if (empty($_GET['id'])) throw new Exception('Missing: id');
            $stmt = $pdo->prepare("SELECT *, CASE WHEN have_it = 0 THEN 'missing' WHEN expiry_date IS NOT NULL AND expiry_date < CURDATE() THEN 'expired' WHEN expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL remind_days_before DAY) THEN 'expiring' WHEN have_it = 1 THEN 'good' ELSE 'unknown' END as computed_status, DATEDIFF(expiry_date, CURDATE()) as days_until_expiry FROM compliance_items WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $row = $stmt->fetch();
            if (!$row) throw new Exception('Not found');
            echo json_encode(['success' => true, 'data' => $row]);
            break;

        case 'create':
            if ($method !== 'POST') throw new Exception('POST required');
            if (empty($_POST['name'])) throw new Exception('Missing: name');
            $stmt = $pdo->prepare("INSERT INTO compliance_items (name, category, document_number, issuing_authority, have_it, issue_date, expiry_date, remind_days_before, cost, notes) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([trim($_POST['name']), $_POST['category'] ?? 'other', trim($_POST['document_number'] ?? ''), trim($_POST['issuing_authority'] ?? ''), !empty($_POST['have_it']) ? 1 : 0, $_POST['issue_date'] ?: null, $_POST['expiry_date'] ?: null, intval($_POST['remind_days_before'] ?? 30), floatval($_POST['cost'] ?? 0), trim($_POST['notes'] ?? '')]);
            echo json_encode(['success' => true, 'data' => ['id' => $pdo->lastInsertId()]]);
            break;

        case 'update':
            if ($method !== 'POST') throw new Exception('POST required');
            if (empty($_POST['id'])) throw new Exception('Missing: id');
            $stmt = $pdo->prepare("UPDATE compliance_items SET name=?, category=?, document_number=?, issuing_authority=?, have_it=?, issue_date=?, expiry_date=?, remind_days_before=?, cost=?, notes=? WHERE id=?");
            $stmt->execute([trim($_POST['name']), $_POST['category'] ?? 'other', trim($_POST['document_number'] ?? ''), trim($_POST['issuing_authority'] ?? ''), !empty($_POST['have_it']) ? 1 : 0, $_POST['issue_date'] ?: null, $_POST['expiry_date'] ?: null, intval($_POST['remind_days_before'] ?? 30), floatval($_POST['cost'] ?? 0), trim($_POST['notes'] ?? ''), $_POST['id']]);
            echo json_encode(['success' => true]);
            break;

        case 'delete':
            if ($method !== 'POST') throw new Exception('POST required');
            if (empty($_POST['id'])) throw new Exception('Missing: id');
            $pdo->prepare("DELETE FROM compliance_items WHERE id = ?")->execute([$_POST['id']]);
            echo json_encode(['success' => true]);
            break;

        case 'reminders':
            $missing = $pdo->query("SELECT COUNT(*) FROM compliance_items WHERE have_it = 0")->fetchColumn();
            $expired = $pdo->query("SELECT COUNT(*) FROM compliance_items WHERE have_it = 1 AND expiry_date IS NOT NULL AND expiry_date < CURDATE()")->fetchColumn();
            $expiring = $pdo->query("SELECT COUNT(*) FROM compliance_items WHERE have_it = 1 AND expiry_date IS NOT NULL AND expiry_date >= CURDATE() AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL remind_days_before DAY)")->fetchColumn();
            $total = $pdo->query("SELECT COUNT(*) FROM compliance_items")->fetchColumn();
            $good = $total - $missing - $expired - $expiring;
            $alerts = $pdo->query("SELECT name, category, expiry_date, have_it, DATEDIFF(expiry_date, CURDATE()) as days_left, CASE WHEN have_it = 0 THEN 'missing' WHEN expiry_date < CURDATE() THEN 'expired' ELSE 'expiring' END as alert_type FROM compliance_items WHERE have_it = 0 OR (have_it = 1 AND expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL remind_days_before DAY)) ORDER BY CASE WHEN have_it = 0 THEN 1 WHEN expiry_date < CURDATE() THEN 2 ELSE 3 END, expiry_date ASC LIMIT 5")->fetchAll();
            echo json_encode(['success' => true, 'data' => ['total' => (int)$total, 'missing' => (int)$missing, 'expired' => (int)$expired, 'expiring' => (int)$expiring, 'good' => max(0, (int)$good), 'needs_attention' => (int)$missing + (int)$expired + (int)$expiring, 'alerts' => $alerts]]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $action]);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

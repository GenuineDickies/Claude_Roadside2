<?php
// Expense CRUD handlers — included by api/expenses.php
switch ($action) {

    case 'categories':
        $rows = $pdo->query("SELECT * FROM expense_categories WHERE is_active=1 ORDER BY sort_order")->fetchAll();
        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    case 'list':
        $where = ['1=1'];
        $params = [];

        if (!empty($_GET['category_id'])) { $where[] = 'e.category_id = ?'; $params[] = $_GET['category_id']; }
        if (!empty($_GET['vendor'])) { $where[] = 'e.vendor LIKE ?'; $params[] = '%' . $_GET['vendor'] . '%'; }
        if (!empty($_GET['date_from'])) { $where[] = 'e.expense_date >= ?'; $params[] = $_GET['date_from']; }
        if (!empty($_GET['date_to'])) { $where[] = 'e.expense_date <= ?'; $params[] = $_GET['date_to']; }
        if (!empty($_GET['status'])) { $where[] = 'e.status = ?'; $params[] = $_GET['status']; }
        if (!empty($_GET['payment_method'])) { $where[] = 'e.payment_method = ?'; $params[] = $_GET['payment_method']; }

        $whereClause = implode(' AND ', $where);
        $limit = min((int)($_GET['limit'] ?? 50), 200);
        $offset = max((int)($_GET['offset'] ?? 0), 0);

        $stmt = $pdo->prepare("
            SELECT e.*, ec.name as category_name, ec.icon as category_icon, ec.color as category_color
            FROM expenses e
            LEFT JOIN expense_categories ec ON e.category_id = ec.id
            WHERE $whereClause
            ORDER BY e.expense_date DESC, e.id DESC
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $stmtTotal = $pdo->prepare("
            SELECT COUNT(*) as count, COALESCE(SUM(e.total_amount),0) as total
            FROM expenses e WHERE $whereClause
        ");
        $stmtTotal->execute($params);
        $totals = $stmtTotal->fetch();

        echo json_encode(['success' => true, 'data' => $rows, 'totals' => $totals]);
        break;

    case 'create':
        if ($method !== 'POST') { throw new Exception('POST required'); }

        $required = ['category_id', 'vendor', 'amount', 'expense_date'];
        foreach ($required as $f) {
            if (empty($_POST[$f])) throw new Exception("Missing: $f");
        }

        $amount = floatval($_POST['amount']);
        $tax = floatval($_POST['tax_amount'] ?? 0);
        $total = $amount + $tax;

        $stmt = $pdo->prepare("INSERT INTO expenses
            (category_id, vendor, description, amount, tax_amount, total_amount, expense_date,
             payment_method, reference_number, is_recurring, recurring_frequency, recurring_next_date,
             service_request_id, work_order_id, technician_id, vehicle_tag, notes, is_tax_deductible, status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");
        $stmt->execute([
            $_POST['category_id'], trim($_POST['vendor']), trim($_POST['description'] ?? ''),
            $amount, $tax, $total, $_POST['expense_date'],
            $_POST['payment_method'] ?? 'card', $_POST['reference_number'] ?? null,
            !empty($_POST['is_recurring']) ? 1 : 0, $_POST['recurring_frequency'] ?? null, $_POST['recurring_next_date'] ?? null,
            $_POST['service_request_id'] ?? null, $_POST['work_order_id'] ?? null,
            $_POST['technician_id'] ?? null, $_POST['vehicle_tag'] ?? null,
            trim($_POST['notes'] ?? ''), isset($_POST['is_tax_deductible']) ? (int)$_POST['is_tax_deductible'] : 1,
            $_POST['status'] ?? 'paid',
        ]);

        echo json_encode(['success' => true, 'data' => ['id' => $pdo->lastInsertId()]]);
        break;

    case 'update':
        if ($method !== 'POST') { throw new Exception('POST required'); }
        if (empty($_POST['id'])) { throw new Exception('Missing: id'); }

        $amount = floatval($_POST['amount']);
        $tax = floatval($_POST['tax_amount'] ?? 0);
        $total = $amount + $tax;

        $stmt = $pdo->prepare("UPDATE expenses SET
            category_id=?, vendor=?, description=?, amount=?, tax_amount=?, total_amount=?,
            expense_date=?, payment_method=?, reference_number=?, is_recurring=?,
            recurring_frequency=?, recurring_next_date=?, service_request_id=?,
            work_order_id=?, technician_id=?, vehicle_tag=?, notes=?, is_tax_deductible=?, status=?
            WHERE id=?
        ");
        $stmt->execute([
            $_POST['category_id'], trim($_POST['vendor']), trim($_POST['description'] ?? ''),
            $amount, $tax, $total, $_POST['expense_date'],
            $_POST['payment_method'] ?? 'card', $_POST['reference_number'] ?? null,
            !empty($_POST['is_recurring']) ? 1 : 0, $_POST['recurring_frequency'] ?? null, $_POST['recurring_next_date'] ?? null,
            $_POST['service_request_id'] ?? null, $_POST['work_order_id'] ?? null,
            $_POST['technician_id'] ?? null, $_POST['vehicle_tag'] ?? null,
            trim($_POST['notes'] ?? ''), isset($_POST['is_tax_deductible']) ? (int)$_POST['is_tax_deductible'] : 1,
            $_POST['status'] ?? 'paid', $_POST['id'],
        ]);

        echo json_encode(['success' => true]);
        break;

    case 'delete':
        if ($method !== 'POST') { throw new Exception('POST required'); }
        if (empty($_POST['id'])) { throw new Exception('Missing: id'); }

        $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        echo json_encode(['success' => true]);
        break;

    case 'check_recurring':
        $due = $pdo->query("
            SELECT e.*, ec.name as category_name
            FROM expenses e
            LEFT JOIN expense_categories ec ON e.category_id = ec.id
            WHERE e.is_recurring = 1 AND e.recurring_next_date <= CURDATE()
              AND e.status != 'voided'
        ")->fetchAll();
        echo json_encode(['success' => true, 'data' => $due]);
        break;

}

<?php
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_customer'])) {
        try {
            $phone = preg_replace('/\D/', '', $_POST['phone']);
            if (strlen($phone) === 10) {
                $phone = '(' . substr($phone, 0, 3) . ') ' . substr($phone, 3, 3) . '-' . substr($phone, 6, 4);
            } else {
                $phone = $_POST['phone'];
            }

            $stmt = $pdo->prepare("INSERT INTO customers (first_name, last_name, phone, email, address) VALUES (?, ?, ?, ?, ?)");
            $result = $stmt->execute([
                sanitize_input($_POST['first_name']),
                sanitize_input($_POST['last_name']),
                $phone,
                sanitize_input($_POST['email']),
                sanitize_input($_POST['address'])
            ]);

            if ($result) {
                show_alert('Customer added successfully!', 'success');
                $action = 'list';
            } else {
                show_alert('Error adding customer.', 'danger');
            }
        } catch (PDOException $e) {
            show_alert('Database error: ' . $e->getMessage(), 'danger');
        }
    } elseif (isset($_POST['edit_customer'])) {
        try {
            $phone = preg_replace('/\D/', '', $_POST['phone']);
            if (strlen($phone) === 10) {
                $phone = '(' . substr($phone, 0, 3) . ') ' . substr($phone, 3, 3) . '-' . substr($phone, 6, 4);
            } else {
                $phone = $_POST['phone'];
            }

            $stmt = $pdo->prepare("UPDATE customers SET first_name=?, last_name=?, phone=?, email=?, address=? WHERE id=?");
            $result = $stmt->execute([
                sanitize_input($_POST['first_name']),
                sanitize_input($_POST['last_name']),
                $phone,
                sanitize_input($_POST['email']),
                sanitize_input($_POST['address']),
                $id
            ]);

            if ($result) {
                show_alert('Customer updated successfully!', 'success');
                $action = 'list';
            } else {
                show_alert('Error updating customer.', 'danger');
            }
        } catch (PDOException $e) {
            show_alert('Database error: ' . $e->getMessage(), 'danger');
        }
    }
}

// Handle delete
if ($action === 'delete' && $id) {
    $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
    $result = $stmt->execute([$id]);
    show_alert($result ? 'Customer deleted successfully!' : 'Error deleting customer.', $result ? 'success' : 'danger');
    $action = 'list';
}

// Get customer data for edit
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$id]);
    $customer = $stmt->fetch();
    if (!$customer) {
        show_alert('Customer not found.', 'danger');
        $action = 'list';
    }
}

// Get all customers for list view
if ($action === 'list') {
    try {
        $customers = $pdo->query("SELECT * FROM customers ORDER BY created_at DESC")->fetchAll();
    } catch (PDOException $e) {
        show_alert('Error retrieving customers: ' . $e->getMessage(), 'danger');
        $customers = [];
    }
}
?>

<div class="rr-page-header" style="margin: -28px -28px 24px -28px;">
    <div class="header-left">
        <i class="fas fa-users header-icon"></i>
        <div>
            <h1>Customers</h1>
            <div class="header-subtitle">Manage your customer database</div>
        </div>
    </div>
    <div class="header-actions">
        <?php if ($action === 'list'): ?>
            <a href="?page=customers&action=add" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Customer
            </a>
        <?php else: ?>
            <a href="?page=customers" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($action === 'list'): ?>
    <?php include __DIR__ . '/customers/list.php'; ?>
<?php elseif ($action === 'add' || $action === 'edit'): ?>
    <?php include __DIR__ . '/customers/form.php'; ?>
<?php endif; ?>

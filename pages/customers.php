<?php
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_customer'])) {
        try {
            // Clean phone number - remove formatting, keep only digits
            $phone = preg_replace('/\D/', '', $_POST['phone']);
            if (strlen($phone) === 10) {
                $phone = '(' . substr($phone, 0, 3) . ') ' . substr($phone, 3, 3) . '-' . substr($phone, 6, 4);
            } else {
                $phone = $_POST['phone']; // Keep as-is if not 10 digits
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
                $action = 'list'; // Redirect to list view
            } else {
                show_alert('Error adding customer.', 'danger');
            }
        } catch (PDOException $e) {
            show_alert('Database error: ' . $e->getMessage(), 'danger');
        }
    } elseif (isset($_POST['edit_customer'])) {
        try {
            // Clean phone number - remove formatting, keep only digits
            $phone = preg_replace('/\D/', '', $_POST['phone']);
            if (strlen($phone) === 10) {
                $phone = '(' . substr($phone, 0, 3) . ') ' . substr($phone, 3, 3) . '-' . substr($phone, 6, 4);
            } else {
                $phone = $_POST['phone']; // Keep as-is if not 10 digits
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
    if ($stmt->execute([$id])) {
        show_alert('Customer deleted successfully!', 'success');
    } else {
        show_alert('Error deleting customer.', 'danger');
    }
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
    <!-- Customer List -->
    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="card-title mb-0">Customer Directory</h5>
                </div>
                <div class="col-md-6">
                    <input type="text" class="form-control search-input" placeholder="Search customers...">
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($customers)): ?>
                <p class="text-muted">No customers found. <a href="?page=customers&action=add">Add the first customer</a>.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Address</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $customer): ?>
                                <tr class="searchable-row">
                                    <td>#<?php echo $customer['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars(format_phone($customer['phone'])); ?></td>
                                    <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['address']); ?></td>
                                    <td><?php echo format_date($customer['created_at']); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="?page=customers&action=edit&id=<?php echo $customer['id']; ?>" 
                                               class="btn btn-outline-primary">Edit</a>
                                            <a href="?page=service-requests&action=add&customer_id=<?php echo $customer['id']; ?>" 
                                               class="btn btn-outline-success">New Request</a>
                                            <a href="?page=customers&action=delete&id=<?php echo $customer['id']; ?>" 
                                               class="btn btn-outline-danger delete-btn">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php elseif ($action === 'add' || $action === 'edit'): ?>
    <!-- Add/Edit Form -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title"><?php echo $action === 'add' ? 'Add New Customer' : 'Edit Customer'; ?></h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   value="<?php echo htmlspecialchars($customer['first_name'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   value="<?php echo htmlspecialchars($customer['last_name'] ?? ''); ?>" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone *</label>
                            <?php 
                            $phone_value = '';
                            if (isset($customer['phone']) && !empty($customer['phone'])) {
                                $phone_value = $customer['phone'];
                            }
                            ?>
                            <input type="tel" class="form-control phone-masked" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($phone_value); ?>" 
                                   required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($customer['address'] ?? ''); ?></textarea>
                </div>
                <div class="d-flex justify-content-end">
                    <a href="?page=customers" class="btn btn-secondary me-2">Cancel</a>
                    <button type="submit" name="<?php echo $action === 'add' ? 'add_customer' : 'edit_customer'; ?>" 
                            class="btn btn-primary">
                        <?php echo $action === 'add' ? 'Add Customer' : 'Update Customer'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Phone mask handled globally by app.js
});
</script>

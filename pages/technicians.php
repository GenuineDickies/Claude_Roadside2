<?php
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_technician'])) {
        // Clean phone number - remove formatting, keep only digits
        $phone = preg_replace('/\D/', '', $_POST['phone']);
        if (strlen($phone) === 10) {
            $phone = '(' . substr($phone, 0, 3) . ') ' . substr($phone, 3, 3) . '-' . substr($phone, 6, 4);
        } else {
            $phone = $_POST['phone']; // Keep as-is if not 10 digits
        }
        
        $stmt = $pdo->prepare("INSERT INTO technicians (first_name, last_name, phone, email, specialization, hourly_rate) VALUES (?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([
            sanitize_input($_POST['first_name']),
            sanitize_input($_POST['last_name']),
            $phone,
            sanitize_input($_POST['email']),
            sanitize_input($_POST['specialization']),
            floatval($_POST['hourly_rate'])
        ]);
        
        if ($result) {
            show_alert('Technician added successfully!', 'success');
        } else {
            show_alert('Error adding technician.', 'danger');
        }
    } elseif (isset($_POST['edit_technician'])) {
        // Clean phone number - remove formatting, keep only digits
        $phone = preg_replace('/\D/', '', $_POST['phone']);
        if (strlen($phone) === 10) {
            $phone = '(' . substr($phone, 0, 3) . ') ' . substr($phone, 3, 3) . '-' . substr($phone, 6, 4);
        } else {
            $phone = $_POST['phone']; // Keep as-is if not 10 digits
        }
        
        $stmt = $pdo->prepare("UPDATE technicians SET first_name=?, last_name=?, phone=?, email=?, specialization=?, status=?, hourly_rate=? WHERE id=?");
        $result = $stmt->execute([
            sanitize_input($_POST['first_name']),
            sanitize_input($_POST['last_name']),
            $phone,
            sanitize_input($_POST['email']),
            sanitize_input($_POST['specialization']),
            $_POST['status'],
            floatval($_POST['hourly_rate']),
            $id
        ]);
        
        if ($result) {
            show_alert('Technician updated successfully!', 'success');
            $action = 'list';
        } else {
            show_alert('Error updating technician.', 'danger');
        }
    }
}

// Handle delete
if ($action === 'delete' && $id) {
    $stmt = $pdo->prepare("DELETE FROM technicians WHERE id = ?");
    if ($stmt->execute([$id])) {
        show_alert('Technician deleted successfully!', 'success');
    } else {
        show_alert('Error deleting technician.', 'danger');
    }
    $action = 'list';
}

// Get technician data for edit
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM technicians WHERE id = ?");
    $stmt->execute([$id]);
    $technician = $stmt->fetch();
    
    if (!$technician) {
        show_alert('Technician not found.', 'danger');
        $action = 'list';
    }
}

// Get all technicians for list view
if ($action === 'list') {
    $technicians = $pdo->query("
        SELECT t.*, 
               COUNT(sr.id) as active_requests,
               AVG(sr.actual_cost) as avg_job_cost
        FROM technicians t
        LEFT JOIN service_requests sr ON t.id = sr.technician_id AND sr.status IN ('assigned', 'in_progress')
        GROUP BY t.id
        ORDER BY t.created_at DESC
    ")->fetchAll();
}
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1><i class="fas fa-user-cog"></i> Technicians</h1>
            <?php if ($action === 'list'): ?>
                <a href="?page=technicians&action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Technician
                </a>
            <?php else: ?>
                <a href="?page=technicians" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($action === 'list'): ?>
    <!-- Technician List -->
    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="card-title mb-0">Technician Directory</h5>
                </div>
                <div class="col-md-6">
                    <input type="text" class="form-control search-input" placeholder="Search technicians...">
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($technicians)): ?>
                <p class="text-muted">No technicians found. <a href="?page=technicians&action=add">Add the first technician</a>.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Specialization</th>
                                <th>Status</th>
                                <th>Hourly Rate</th>
                                <th>Active Jobs</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($technicians as $tech): ?>
                                <tr class="searchable-row">
                                    <td>#<?php echo $tech['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($tech['first_name'] . ' ' . $tech['last_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($tech['email']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($tech['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($tech['specialization']); ?></td>
                                    <td><?php echo get_status_badge($tech['status']); ?></td>
                                    <td><?php echo format_currency($tech['hourly_rate']); ?></td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $tech['active_requests']; ?></span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="?page=technicians&action=edit&id=<?php echo $tech['id']; ?>" 
                                               class="btn btn-outline-primary">Edit</a>
                                            <a href="?page=technicians&action=delete&id=<?php echo $tech['id']; ?>" 
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
            <h5 class="card-title"><?php echo $action === 'add' ? 'Add New Technician' : 'Edit Technician'; ?></h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   value="<?php echo htmlspecialchars($technician['first_name'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   value="<?php echo htmlspecialchars($technician['last_name'] ?? ''); ?>" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone *</label>
                            <?php 
                            $phone_value = '';
                            if (isset($technician['phone']) && !empty($technician['phone'])) {
                                $phone_value = $technician['phone'];
                            }
                            ?>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($phone_value); ?>" 
                                   placeholder="(555) 123-4567" 
                                   maxlength="14" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($technician['email'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="specialization" class="form-label">Specialization</label>
                            <input type="text" class="form-control" id="specialization" name="specialization" 
                                   value="<?php echo htmlspecialchars($technician['specialization'] ?? ''); ?>" 
                                   placeholder="e.g., Automotive, Locksmith, Towing">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="hourly_rate" class="form-label">Hourly Rate</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="hourly_rate" name="hourly_rate" 
                                       value="<?php echo $technician['hourly_rate'] ?? '0'; ?>" step="0.01" min="0">
                            </div>
                        </div>
                    </div>
                </div>
                <?php if ($action === 'edit'): ?>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="available" <?php echo ($technician['status'] ?? '') === 'available' ? 'selected' : ''; ?>>Available</option>
                            <option value="busy" <?php echo ($technician['status'] ?? '') === 'busy' ? 'selected' : ''; ?>>Busy</option>
                            <option value="offline" <?php echo ($technician['status'] ?? '') === 'offline' ? 'selected' : ''; ?>>Offline</option>
                        </select>
                    </div>
                <?php endif; ?>
                <div class="d-flex justify-content-end">
                    <a href="?page=technicians" class="btn btn-secondary me-2">Cancel</a>
                    <button type="submit" name="<?php echo $action === 'add' ? 'add_technician' : 'edit_technician'; ?>" 
                            class="btn btn-primary">
                        <?php echo $action === 'add' ? 'Add Technician' : 'Update Technician'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const phoneInput = document.getElementById('phone');
    
    if (phoneInput) {
        phoneInput.addEventListener('input', function() {
            let value = this.value.replace(/\D/g, ''); // Remove non-digits
            let formatted = '';
            
            if (value.length > 0) {
                if (value.length <= 3) {
                    formatted = '(' + value;
                } else if (value.length <= 6) {
                    formatted = '(' + value.substring(0, 3) + ') ' + value.substring(3);
                } else {
                    formatted = '(' + value.substring(0, 3) + ') ' + value.substring(3, 6) + '-' + value.substring(6, 10);
                }
            }
            
            this.value = formatted;
        });
    }
});
</script>

<?php
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$customer_id = $_GET['customer_id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_request'])) {
        $stmt = $pdo->prepare("INSERT INTO service_requests (customer_id, service_type, description, location, priority, estimated_cost) VALUES (?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([
            $_POST['customer_id'],
            $_POST['service_type'],
            sanitize_input($_POST['description']),
            sanitize_input($_POST['location']),
            $_POST['priority'],
            floatval($_POST['estimated_cost'])
        ]);
        
        if ($result) {
            show_alert('Service request created successfully!', 'success');
            $action = 'list';
        } else {
            show_alert('Error creating service request.', 'danger');
        }
    } elseif (isset($_POST['assign_technician'])) {
        $stmt = $pdo->prepare("UPDATE service_requests SET technician_id=?, status='assigned' WHERE id=?");
        $result = $stmt->execute([$_POST['technician_id'], $id]);
        
        if ($result) {
            // Update technician status to busy
            $pdo->prepare("UPDATE technicians SET status='busy' WHERE id=?")->execute([$_POST['technician_id']]);
            show_alert('Technician assigned successfully!', 'success');
        } else {
            show_alert('Error assigning technician.', 'danger');
        }
    } elseif (isset($_POST['update_status'])) {
        $stmt = $pdo->prepare("UPDATE service_requests SET status=?, actual_cost=?, completed_at=? WHERE id=?");
        $completed_at = $_POST['status'] === 'completed' ? date('Y-m-d H:i:s') : null;
        $result = $stmt->execute([
            $_POST['status'],
            floatval($_POST['actual_cost']),
            $completed_at,
            $id
        ]);
        
        if ($result && $_POST['status'] === 'completed') {
            // Update technician status back to available
            $pdo->prepare("UPDATE technicians SET status='available' WHERE id = (SELECT technician_id FROM service_requests WHERE id=?)")->execute([$id]);
        }
        
        if ($result) {
            show_alert('Service request updated successfully!', 'success');
        } else {
            show_alert('Error updating service request.', 'danger');
        }
    }
}

// Get service request data for view/edit
if (($action === 'view' || $action === 'assign') && $id) {
    $stmt = $pdo->prepare("
        SELECT sr.*, c.first_name, c.last_name, c.phone, c.email, c.address,
               t.first_name as tech_first_name, t.last_name as tech_last_name, t.phone as tech_phone
        FROM service_requests sr
        LEFT JOIN customers c ON sr.customer_id = c.id
        LEFT JOIN technicians t ON sr.technician_id = t.id
        WHERE sr.id = ?
    ");
    $stmt->execute([$id]);
    $request = $stmt->fetch();
    
    if (!$request) {
        show_alert('Service request not found.', 'danger');
        $action = 'list';
    }
}

// Get customers for dropdown
$customers = $pdo->query("SELECT id, first_name, last_name, phone FROM customers ORDER BY first_name, last_name")->fetchAll();

// Get available technicians for assignment
$availableTechnicians = $pdo->query("SELECT id, first_name, last_name, specialization FROM technicians WHERE status = 'available' ORDER BY first_name, last_name")->fetchAll();

// Get all service requests for list view
if ($action === 'list') {
    $requests = $pdo->query("
        SELECT sr.*, c.first_name, c.last_name, c.phone,
               t.first_name as tech_first_name, t.last_name as tech_last_name
        FROM service_requests sr
        LEFT JOIN customers c ON sr.customer_id = c.id
        LEFT JOIN technicians t ON sr.technician_id = t.id
        ORDER BY sr.created_at DESC
    ")->fetchAll();
}
?>

<div class="rr-page-header" style="margin: -28px -28px 24px -28px;">
    <div class="header-left">
        <i class="fas fa-clipboard-list header-icon"></i>
        <div>
            <h1>Service Requests</h1>
            <div class="header-subtitle">Track and manage service operations</div>
        </div>
    </div>
    <div class="header-actions">
        <?php if ($action === 'list'): ?>
            <a href="?page=service-requests&action=add" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Request
            </a>
        <?php else: ?>
            <a href="?page=service-requests" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($action === 'list'): ?>
    <!-- Service Requests List -->
    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="card-title mb-0">All Service Requests</h5>
                </div>
                <div class="col-md-6">
                    <input type="text" class="form-control search-input" placeholder="Search requests...">
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($requests)): ?>
                <p class="text-muted">No service requests found. <a href="?page=service-requests&action=add">Create the first request</a>.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Service Type</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Technician</th>
                                <th>Cost</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $req): ?>
                                <tr class="searchable-row priority-<?php echo $req['priority']; ?>">
                                    <td>#<?php echo $req['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars(format_phone($req['phone'])); ?></small>
                                    </td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $req['service_type'])); ?></td>
                                    <td><?php echo get_status_badge($req['status']); ?></td>
                                    <td><?php echo get_priority_badge($req['priority']); ?></td>
                                    <td>
                                        <?php if ($req['tech_first_name']): ?>
                                            <?php echo htmlspecialchars($req['tech_first_name'] . ' ' . $req['tech_last_name']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($req['actual_cost'] > 0): ?>
                                            <?php echo format_currency($req['actual_cost']); ?>
                                        <?php else: ?>
                                            <span class="text-muted"><?php echo format_currency($req['estimated_cost']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo format_datetime($req['created_at']); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="?page=service-requests&action=view&id=<?php echo $req['id']; ?>" 
                                               class="btn btn-outline-primary">View</a>
                                            <?php if ($req['status'] === 'completed' && !$req['technician_id']): ?>
                                                <a href="?page=invoices&action=create&request_id=<?php echo $req['id']; ?>" 
                                                   class="btn btn-outline-success">Invoice</a>
                                            <?php endif; ?>
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

<?php elseif ($action === 'add'): ?>
    <!-- Add New Request Form -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">Create New Service Request</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="customer_id" class="form-label">Customer *</label>
                            <select class="form-select" id="customer_id" name="customer_id" required>
                                <option value="">Select Customer</option>
                                <?php foreach ($customers as $customer): ?>
                                    <option value="<?php echo $customer['id']; ?>" 
                                            <?php echo $customer_id == $customer['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name'] . ' - ' . format_phone($customer['phone'])); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="service_type" class="form-label">Service Type *</label>
                            <select class="form-select" id="service_type" name="service_type" required>
                                <option value="">Select Service</option>
                                <option value="battery_jump">Battery Jump Start</option>
                                <option value="tire_change">Tire Change</option>
                                <option value="lockout">Vehicle Lockout</option>
                                <option value="towing">Towing Service</option>
                                <option value="fuel_delivery">Fuel Delivery</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3" 
                              placeholder="Describe the issue and any specific requirements..."></textarea>
                </div>
                <div class="mb-3">
                    <label for="location" class="form-label">Location *</label>
                    <textarea class="form-control" id="location" name="location" rows="2" 
                              placeholder="Exact address or location details..." required></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="priority" class="form-label">Priority</label>
                            <select class="form-select" id="priority" name="priority">
                                <option value="medium">Medium</option>
                                <option value="low">Low</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="estimated_cost" class="form-label">Estimated Cost</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="estimated_cost" name="estimated_cost" 
                                       value="0" step="0.01" min="0">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end">
                    <a href="?page=service-requests" class="btn btn-secondary me-2">Cancel</a>
                    <button type="submit" name="add_request" class="btn btn-primary">Create Request</button>
                </div>
            </form>
        </div>
    </div>

<?php elseif ($action === 'view' && $request): ?>
    <!-- View Request Details -->
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Service Request #<?php echo $request['id']; ?></h5>
                    <div class="mt-2">
                        <?php echo get_status_badge($request['status']); ?>
                        <?php echo get_priority_badge($request['priority']); ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Customer Information</h6>
                            <p><strong><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></strong></p>
                            <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars(format_phone($request['phone'])); ?></p>
                            <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($request['email']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Service Details</h6>
                            <p><strong>Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $request['service_type'])); ?></p>
                            <p><strong>Created:</strong> <?php echo format_datetime($request['created_at']); ?></p>
                            <?php if ($request['completed_at']): ?>
                                <p><strong>Completed:</strong> <?php echo format_datetime($request['completed_at']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <h6>Location</h6>
                    <p><?php echo nl2br(htmlspecialchars($request['location'])); ?></p>
                    
                    <?php if ($request['description']): ?>
                        <h6>Description</h6>
                        <p><?php echo nl2br(htmlspecialchars($request['description'])); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($request['tech_first_name']): ?>
                        <h6>Assigned Technician</h6>
                        <p>
                            <strong><?php echo htmlspecialchars($request['tech_first_name'] . ' ' . $request['tech_last_name']); ?></strong><br>
                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($request['tech_phone']); ?>
                        </p>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Cost Information</h6>
                            <p><strong>Estimated:</strong> <?php echo format_currency($request['estimated_cost']); ?></p>
                            <?php if ($request['actual_cost'] > 0): ?>
                                <p><strong>Actual:</strong> <?php echo format_currency($request['actual_cost']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Actions Panel -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Actions</h5>
                </div>
                <div class="card-body">
                    <?php if ($request['status'] === 'pending' && !$request['technician_id']): ?>
                        <!-- Assign Technician -->
                        <form method="POST" class="mb-3">
                            <label for="technician_id" class="form-label">Assign Technician</label>
                            <select class="form-select mb-2" name="technician_id" required>
                                <option value="">Select Technician</option>
                                <?php foreach ($availableTechnicians as $tech): ?>
                                    <option value="<?php echo $tech['id']; ?>">
                                        <?php echo htmlspecialchars($tech['first_name'] . ' ' . $tech['last_name']); ?>
                                        <?php if ($tech['specialization']): ?>
                                            (<?php echo htmlspecialchars($tech['specialization']); ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="assign_technician" class="btn btn-success btn-sm w-100">
                                Assign Technician
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <?php if ($request['technician_id'] && in_array($request['status'], ['assigned', 'in_progress'])): ?>
                        <!-- Update Status -->
                        <form method="POST">
                            <label for="status" class="form-label">Update Status</label>
                            <select class="form-select mb-2" name="status">
                                <option value="assigned" <?php echo $request['status'] === 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                                <option value="in_progress" <?php echo $request['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            
                            <label for="actual_cost" class="form-label">Actual Cost</label>
                            <div class="input-group mb-2">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" name="actual_cost" 
                                       value="<?php echo $request['actual_cost']; ?>" step="0.01" min="0">
                            </div>
                            
                            <button type="submit" name="update_status" class="btn btn-primary btn-sm w-100">
                                Update Request
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <?php if ($request['status'] === 'completed' && $request['actual_cost'] > 0): ?>
                        <a href="?page=invoices&action=create&request_id=<?php echo $request['id']; ?>" 
                           class="btn btn-success w-100">
                            <i class="fas fa-file-invoice"></i> Create Invoice
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$customer_id = $_GET['customer_id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_request'])) {
        // Generate ticket number
        $dateStr = date('Ymd');
        $lastNum = $pdo->query("SELECT ticket_number FROM service_tickets WHERE ticket_number LIKE 'RR-{$dateStr}-%' ORDER BY id DESC LIMIT 1")->fetchColumn();
        if ($lastNum) {
            $seq = intval(substr($lastNum, -4)) + 1;
        } else {
            $seq = 1;
        }
        $ticketNum = 'RR-' . $dateStr . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);

        // Get customer info for snapshot fields
        $custStmt = $pdo->prepare("SELECT first_name, last_name, phone, email FROM customers WHERE id = ?");
        $custStmt->execute([$_POST['customer_id']]);
        $cust = $custStmt->fetch();

        $stmt = $pdo->prepare("INSERT INTO service_tickets (ticket_number, customer_id, customer_name, customer_phone, customer_email, service_category, issue_description, service_address, priority, estimated_cost, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'created', ?)");
        $result = $stmt->execute([
            $ticketNum,
            $_POST['customer_id'],
            $cust ? $cust['first_name'] . ' ' . $cust['last_name'] : '',
            $cust ? $cust['phone'] : '',
            $cust ? $cust['email'] : '',
            $_POST['service_category'],
            sanitize_input($_POST['issue_description']),
            sanitize_input($_POST['service_address']),
            $_POST['priority'],
            floatval($_POST['estimated_cost'])
            ,
            $_SESSION['user_id'] ?? null
        ]);
        
        if ($result) {
            show_alert('Service ticket created successfully!', 'success');
            $action = 'list';
        } else {
            show_alert('Error creating service ticket.', 'danger');
        }
    } elseif (isset($_POST['assign_technician'])) {
        // Assign technician only — does NOT dispatch. Dispatch is a separate action.
        $stmt = $pdo->prepare("UPDATE service_tickets SET technician_id=? WHERE id=?");
        $result = $stmt->execute([$_POST['technician_id'], $id]);
        
        if ($result) {
            show_alert('Technician assigned successfully!', 'success');
        } else {
            show_alert('Error assigning technician.', 'danger');
        }
    } elseif (isset($_POST['dispatch_ticket'])) {
        // Dispatch — explicitly sets status to dispatched. Requires a technician already assigned.
        $stmt = $pdo->prepare("SELECT technician_id FROM service_tickets WHERE id = ?");
        $stmt->execute([$id]);
        $ticket = $stmt->fetch();
        
        if (!$ticket || !$ticket['technician_id']) {
            show_alert('Cannot dispatch: no technician assigned.', 'danger');
        } else {
            $stmt = $pdo->prepare("UPDATE service_tickets SET status='dispatched', dispatched_at=NOW() WHERE id=?");
            $result = $stmt->execute([$id]);
            if ($result) {
                $pdo->prepare("UPDATE technicians SET status='busy' WHERE id=?")->execute([$ticket['technician_id']]);
                show_alert('Ticket dispatched!', 'success');
            } else {
                show_alert('Error dispatching ticket.', 'danger');
            }
        }
    } elseif (isset($_POST['update_status'])) {
        $stmt = $pdo->prepare("UPDATE service_tickets SET status=?, price_quoted=?, completed_at=? WHERE id=?");
        $completed_at = $_POST['status'] === 'completed' ? date('Y-m-d H:i:s') : null;
        $result = $stmt->execute([
            $_POST['status'],
            floatval($_POST['actual_cost']),
            $completed_at,
            $id
        ]);
        
        if ($result && $_POST['status'] === 'completed') {
            // Update technician status back to available
            $pdo->prepare("UPDATE technicians SET status='available' WHERE id = (SELECT technician_id FROM service_tickets WHERE id=?)")->execute([$id]);
        }
        
        if ($result) {
            show_alert('Service ticket updated successfully!', 'success');
        } else {
            show_alert('Error updating service ticket.', 'danger');
        }
    }
}

// Get service ticket data for view/edit
if (($action === 'view' || $action === 'assign') && $id) {
    $stmt = $pdo->prepare("
        SELECT st.*, c.first_name, c.last_name, c.phone, c.email, c.address,
               t.first_name as tech_first_name, t.last_name as tech_last_name, t.phone as tech_phone
        FROM service_tickets st
        LEFT JOIN customers c ON st.customer_id = c.id
        LEFT JOIN technicians t ON st.technician_id = t.id
        WHERE st.id = ?
    ");
    $stmt->execute([$id]);
    $request = $stmt->fetch();
    
    if (!$request) {
        show_alert('Service ticket not found.', 'danger');
        $action = 'list';
    }
}

// Get customers for dropdown
$customers = $pdo->query("SELECT id, first_name, last_name, phone FROM customers ORDER BY first_name, last_name")->fetchAll();

// Get available technicians for assignment
$availableTechnicians = $pdo->query("SELECT id, first_name, last_name, specialization FROM technicians WHERE status = 'available' ORDER BY first_name, last_name")->fetchAll();

// Get all service tickets for list view
if ($action === 'list') {
    $requests = $pdo->query("
        SELECT st.*, c.first_name, c.last_name, c.phone,
               t.first_name as tech_first_name, t.last_name as tech_last_name
        FROM service_tickets st
        LEFT JOIN customers c ON st.customer_id = c.id
        LEFT JOIN technicians t ON st.technician_id = t.id
        ORDER BY st.created_at DESC
    ")->fetchAll();
}
?>

<div class="rr-page-header" style="margin: -28px -28px 24px -28px;">
    <div class="header-left">
        <i class="fas fa-clipboard-list header-icon"></i>
        <div>
            <h1>Service Tickets</h1>
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
                    <h5 class="card-title mb-0">All Service Tickets</h5>
                </div>
                <div class="col-md-6">
                    <input type="text" class="form-control search-input" placeholder="Search requests...">
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($requests)): ?>
                <p class="text-muted">No service tickets found. <a href="?page=service-intake">Create the first ticket</a>.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Ticket #</th>
                                <th>Customer</th>
                                <th>Service</th>
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
                                    <td style="font-family:'JetBrains Mono',monospace;font-size:12px"><?php echo htmlspecialchars($req['ticket_number']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars(format_phone($req['phone'])); ?></small>
                                    </td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $req['service_category'])); ?></td>
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
                                        <?php if ($req['price_quoted'] > 0): ?>
                                            <?php echo format_currency($req['price_quoted']); ?>
                                        <?php else: ?>
                                            <span class="text-muted"><?php echo format_currency($req['estimated_cost']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo format_datetime($req['created_at']); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="?page=service-requests&action=view&id=<?php echo $req['id']; ?>" 
                                               class="btn btn-outline-primary">View</a>
                                            <?php if (in_array($req['status'], ['created', 'dispatched', 'acknowledged', 'en_route', 'on_scene', 'in_progress'])): ?>
                                                <a href="?page=estimates&action=create&sr_id=<?php echo $req['id']; ?>" 
                                                   class="btn btn-outline-info" title="Create Estimate">Estimate</a>
                                            <?php endif; ?>
                                            <?php if ($req['status'] === 'completed'): ?>
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
    <!-- Add New Service Ticket Form -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">Create New Service Ticket</h5>
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
                            <label for="service_category" class="form-label">Service Category *</label>
                            <select class="form-select" id="service_category" name="service_category" required>
                                <option value="">Select Service</option>
                                <option value="towing">Towing</option>
                                <option value="lockout">Vehicle Lockout</option>
                                <option value="jump_start">Jump Start</option>
                                <option value="tire_service">Tire Service</option>
                                <option value="fuel_delivery">Fuel Delivery</option>
                                <option value="mobile_repair">Mobile Repair</option>
                                <option value="winch_recovery">Winch Recovery</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="issue_description" class="form-label">Description</label>
                    <textarea class="form-control" id="issue_description" name="issue_description" rows="3" 
                              placeholder="Describe the issue and any specific requirements..."></textarea>
                </div>
                <div class="mb-3">
                    <label for="service_address" class="form-label">Location *</label>
                    <textarea class="form-control" id="service_address" name="service_address" rows="2" 
                              placeholder="Exact address or location details..." required></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="priority" class="form-label">Priority</label>
                            <select class="form-select" id="priority" name="priority">
                                <option value="P4">Low (P4)</option>
                                <option value="P3" selected>Normal (P3)</option>
                                <option value="P2">High (P2)</option>
                                <option value="P1">Urgent (P1)</option>
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
                    <button type="submit" name="add_request" class="btn btn-primary">Create Ticket</button>
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
                    <h5 class="card-title">Service Ticket <?php echo htmlspecialchars($request['ticket_number']); ?></h5>
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
                            <p><strong>Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $request['service_category'])); ?></p>
                            <p><strong>Created:</strong> <?php echo format_datetime($request['created_at']); ?></p>
                            <?php if ($request['completed_at']): ?>
                                <p><strong>Completed:</strong> <?php echo format_datetime($request['completed_at']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <h6>Location</h6>
                    <p><?php echo nl2br(htmlspecialchars($request['service_address'])); ?></p>
                    
                    <?php if ($request['issue_description']): ?>
                        <h6>Description</h6>
                        <p><?php echo nl2br(htmlspecialchars($request['issue_description'])); ?></p>
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
                            <?php if ($request['price_quoted'] > 0): ?>
                                <p><strong>Quoted:</strong> <?php echo format_currency($request['price_quoted']); ?></p>
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
                    <?php if (!$request['technician_id']): ?>
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
                            <button type="submit" name="assign_technician" class="btn btn-outline-primary btn-sm w-100">
                                <i class="fas fa-user-plus"></i> Assign Technician
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <?php if ($request['technician_id'] && $request['status'] === 'created'): ?>
                        <!-- Dispatch — separate explicit action -->
                        <form method="POST" class="mb-3">
                            <p class="text-muted" style="font-size:12px;margin-bottom:8px">
                                <i class="fas fa-user"></i> Assigned: <?php echo htmlspecialchars($request['tech_first_name'] . ' ' . $request['tech_last_name']); ?>
                            </p>
                            <button type="submit" name="dispatch_ticket" class="btn btn-success btn-sm w-100">
                                <i class="fas fa-paper-plane"></i> Dispatch
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <?php if ($request['technician_id'] && in_array($request['status'], ['dispatched', 'acknowledged', 'en_route', 'on_scene', 'in_progress'])): ?>
                        <!-- Update Status -->
                        <form method="POST">
                            <label for="status" class="form-label">Update Status</label>
                            <select class="form-select mb-2" name="status">
                                <option value="dispatched" <?php echo $request['status'] === 'dispatched' ? 'selected' : ''; ?>>Dispatched</option>
                                <option value="en_route" <?php echo $request['status'] === 'en_route' ? 'selected' : ''; ?>>En Route</option>
                                <option value="on_scene" <?php echo $request['status'] === 'on_scene' ? 'selected' : ''; ?>>On Scene</option>
                                <option value="in_progress" <?php echo $request['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            
                            <label for="actual_cost" class="form-label">Quoted Price</label>
                            <div class="input-group mb-2">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" name="actual_cost" 
                                       value="<?php echo $request['price_quoted']; ?>" step="0.01" min="0">
                            </div>
                            
                            <button type="submit" name="update_status" class="btn btn-primary btn-sm w-100">
                                Update Ticket
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <?php if ($request['status'] === 'completed' && $request['price_quoted'] > 0): ?>
                        <a href="?page=invoices&action=create&request_id=<?php echo $request['id']; ?>" 
                           class="btn btn-success w-100">
                            <i class="fas fa-file-invoice"></i> Create Invoice
                        </a>
                    <?php endif; ?>
                    
                    <!-- Create Estimate from this Service Request -->
                    <?php if (in_array($request['status'], ['created', 'dispatched', 'acknowledged', 'en_route', 'on_scene', 'in_progress'])): ?>
                        <a href="?page=estimates&action=create&sr_id=<?php echo $request['id']; ?>" 
                           class="btn btn-outline-primary w-100 <?php echo ($request['status'] === 'completed') ? 'mt-2' : ''; ?>">
                            <i class="fas fa-file-invoice"></i> Create Estimate
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

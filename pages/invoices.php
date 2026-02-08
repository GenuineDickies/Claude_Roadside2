<?php
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$request_id = $_GET['request_id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_invoice'])) {
        $invoice_number = generate_invoice_number();
        $subtotal = floatval($_POST['subtotal']);
        $tax_rate = floatval($_POST['tax_rate']);
        $tax_amount = $subtotal * ($tax_rate / 100);
        $total_amount = $subtotal + $tax_amount;
        $due_date = date('Y-m-d', strtotime('+30 days'));
        
        $stmt = $pdo->prepare("INSERT INTO invoices (service_request_id, customer_id, technician_id, invoice_number, subtotal, tax_rate, tax_amount, total_amount, due_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([
            $_POST['service_request_id'],
            $_POST['customer_id'],
            $_POST['technician_id'],
            $invoice_number,
            $subtotal,
            $tax_rate,
            $tax_amount,
            $total_amount,
            $due_date
        ]);
        
        if ($result) {
            show_alert('Invoice created successfully!', 'success');
            $action = 'list';
        } else {
            show_alert('Error creating invoice.', 'danger');
        }
    } elseif (isset($_POST['update_invoice_status'])) {
        $status = $_POST['status'];
        $paid_at = $status === 'paid' ? date('Y-m-d H:i:s') : null;
        
        $stmt = $pdo->prepare("UPDATE invoices SET status=?, paid_at=? WHERE id=?");
        $result = $stmt->execute([$status, $paid_at, $id]);
        
        if ($result) {
            show_alert('Invoice status updated successfully!', 'success');
        } else {
            show_alert('Error updating invoice status.', 'danger');
        }
    }
}

// Get service request data for invoice creation
if ($action === 'create' && $request_id) {
    $stmt = $pdo->prepare("
        SELECT sr.*, c.first_name, c.last_name, c.phone, c.email, c.address,
               t.first_name as tech_first_name, t.last_name as tech_last_name, t.hourly_rate
        FROM service_requests sr
        LEFT JOIN customers c ON sr.customer_id = c.id
        LEFT JOIN technicians t ON sr.technician_id = t.id
        WHERE sr.id = ? AND sr.status = 'completed'
    ");
    $stmt->execute([$request_id]);
    $serviceRequest = $stmt->fetch();
    
    if (!$serviceRequest) {
        show_alert('Service request not found or not completed.', 'danger');
        $action = 'list';
    }
}

// Get invoice data for view
if ($action === 'view' && $id) {
    $stmt = $pdo->prepare("
        SELECT i.*, sr.service_type, sr.description, sr.location,
               c.first_name, c.last_name, c.phone, c.email, c.address,
               t.first_name as tech_first_name, t.last_name as tech_last_name
        FROM invoices i
        LEFT JOIN service_requests sr ON i.service_request_id = sr.id
        LEFT JOIN customers c ON i.customer_id = c.id
        LEFT JOIN technicians t ON i.technician_id = t.id
        WHERE i.id = ?
    ");
    $stmt->execute([$id]);
    $invoice = $stmt->fetch();
    
    if (!$invoice) {
        show_alert('Invoice not found.', 'danger');
        $action = 'list';
    }
}

// Get all invoices for list view
if ($action === 'list') {
    $invoices = $pdo->query("
        SELECT i.*, c.first_name, c.last_name, c.phone,
               t.first_name as tech_first_name, t.last_name as tech_last_name
        FROM invoices i
        LEFT JOIN customers c ON i.customer_id = c.id
        LEFT JOIN technicians t ON i.technician_id = t.id
        ORDER BY i.created_at DESC
    ")->fetchAll();
}
?>

<div class="rr-page-header" style="margin: -28px -28px 24px -28px;">
    <div class="header-left">
        <i class="fas fa-file-invoice-dollar header-icon"></i>
        <div>
            <h1>Invoices</h1>
            <div class="header-subtitle">Billing and payment tracking</div>
        </div>
    </div>
    <div class="header-actions">
        <?php if ($action === 'list'): ?>
            <a href="?page=service-requests&status=completed" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create from Completed Request
            </a>
        <?php else: ?>
            <a href="?page=invoices" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($action === 'list'): ?>
    <!-- Invoices List -->
    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="card-title mb-0">All Invoices</h5>
                </div>
                <div class="col-md-6">
                    <input type="text" class="form-control search-input" placeholder="Search invoices...">
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($invoices)): ?>
                <p class="text-muted">No invoices found. Complete some service requests to create invoices.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Customer</th>
                                <th>Technician</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Due Date</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoices as $inv): ?>
                                <tr class="searchable-row">
                                    <td><strong><?php echo htmlspecialchars($inv['invoice_number']); ?></strong></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($inv['first_name'] . ' ' . $inv['last_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars(format_phone($inv['phone'])); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($inv['tech_first_name'] . ' ' . $inv['tech_last_name']); ?></td>
                                    <td><?php echo format_currency($inv['total_amount']); ?></td>
                                    <td><?php echo get_status_badge($inv['status']); ?></td>
                                    <td>
                                        <?php 
                                        $due_date = strtotime($inv['due_date']);
                                        $today = strtotime('today');
                                        $class = $due_date < $today && $inv['status'] !== 'paid' ? 'text-danger' : '';
                                        ?>
                                        <span class="<?php echo $class; ?>"><?php echo format_date($inv['due_date']); ?></span>
                                    </td>
                                    <td><?php echo format_date($inv['created_at']); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="?page=invoices&action=view&id=<?php echo $inv['id']; ?>" 
                                               class="btn btn-outline-primary">View</a>
                                            <?php if ($inv['status'] !== 'paid'): ?>
                                                <button type="button" class="btn btn-outline-success" 
                                                        onclick="updateInvoiceStatus(<?php echo $inv['id']; ?>, 'paid')">
                                                    Mark Paid
                                                </button>
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

<?php elseif ($action === 'create' && $serviceRequest): ?>
    <!-- Create Invoice Form -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">Create Invoice for Service Request #<?php echo $serviceRequest['id']; ?></h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="service_request_id" value="<?php echo $serviceRequest['id']; ?>">
                <input type="hidden" name="customer_id" value="<?php echo $serviceRequest['customer_id']; ?>">
                <input type="hidden" name="technician_id" value="<?php echo $serviceRequest['technician_id']; ?>">
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>Customer Information</h6>
                        <p>
                            <strong><?php echo htmlspecialchars($serviceRequest['first_name'] . ' ' . $serviceRequest['last_name']); ?></strong><br>
                            <?php echo htmlspecialchars(format_phone($serviceRequest['phone'])); ?><br>
                            <?php echo htmlspecialchars($serviceRequest['email']); ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6>Service Information</h6>
                        <p>
                            <strong>Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $serviceRequest['service_type'])); ?><br>
                            <strong>Technician:</strong> <?php echo htmlspecialchars($serviceRequest['tech_first_name'] . ' ' . $serviceRequest['tech_last_name']); ?><br>
                            <strong>Completed:</strong> <?php echo format_datetime($serviceRequest['completed_at']); ?>
                        </p>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="subtotal" class="form-label">Subtotal *</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="subtotal" name="subtotal" 
                                       value="<?php echo $serviceRequest['actual_cost']; ?>" step="0.01" min="0" required>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="tax_rate" class="form-label">Tax Rate (%)</label>
                            <input type="number" class="form-control" id="tax_rate" name="tax_rate" 
                                   value="8.25" step="0.01" min="0" max="100">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div id="invoice-preview" class="border p-3 mb-3" style="background: #f8f9fa;">
                            <h6>Invoice Preview</h6>
                            <div class="row">
                                <div class="col-md-8">
                                    <p><strong>Service:</strong> <?php echo ucfirst(str_replace('_', ' ', $serviceRequest['service_type'])); ?></p>
                                    <p><strong>Description:</strong> <?php echo htmlspecialchars($serviceRequest['description']); ?></p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <p>Subtotal: <span id="preview-subtotal"><?php echo format_currency($serviceRequest['actual_cost']); ?></span></p>
                                    <p>Tax: <span id="preview-tax">$0.00</span></p>
                                    <p><strong>Total: <span id="preview-total"><?php echo format_currency($serviceRequest['actual_cost']); ?></span></strong></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-end">
                    <a href="?page=invoices" class="btn btn-secondary me-2">Cancel</a>
                    <button type="submit" name="create_invoice" class="btn btn-primary">Create Invoice</button>
                </div>
            </form>
        </div>
    </div>

<?php elseif ($action === 'view' && $invoice): ?>
    <!-- View Invoice -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title">Invoice <?php echo htmlspecialchars($invoice['invoice_number']); ?></h5>
            <div>
                <?php echo get_status_badge($invoice['status']); ?>
                <?php if ($invoice['status'] !== 'paid'): ?>
                    <form method="POST" class="d-inline ms-2">
                        <select name="status" class="form-select form-select-sm d-inline-block w-auto">
                            <option value="draft" <?php echo $invoice['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="sent" <?php echo $invoice['status'] === 'sent' ? 'selected' : ''; ?>>Sent</option>
                            <option value="paid" <?php echo $invoice['status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="overdue" <?php echo $invoice['status'] === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                        </select>
                        <button type="submit" name="update_invoice_status" class="btn btn-sm btn-outline-primary">Update</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <!-- Invoice Header -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h4>Bill To:</h4>
                    <p>
                        <strong><?php echo htmlspecialchars($invoice['first_name'] . ' ' . $invoice['last_name']); ?></strong><br>
                        <?php echo nl2br(htmlspecialchars($invoice['address'])); ?><br>
                        Phone: <?php echo htmlspecialchars(format_phone($invoice['phone'])); ?><br>
                        Email: <?php echo htmlspecialchars($invoice['email']); ?>
                    </p>
                </div>
                <div class="col-md-6 text-end">
                    <h4>Invoice Details</h4>
                    <p>
                        <strong>Invoice #:</strong> <?php echo htmlspecialchars($invoice['invoice_number']); ?><br>
                        <strong>Date:</strong> <?php echo format_date($invoice['created_at']); ?><br>
                        <strong>Due Date:</strong> <?php echo format_date($invoice['due_date']); ?><br>
                        <strong>Technician:</strong> <?php echo htmlspecialchars($invoice['tech_first_name'] . ' ' . $invoice['tech_last_name']); ?>
                    </p>
                </div>
            </div>
            
            <!-- Invoice Items -->
            <div class="table-responsive mb-4">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>Description</th>
                            <th>Location</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo ucfirst(str_replace('_', ' ', $invoice['service_type'])); ?></td>
                            <td><?php echo htmlspecialchars($invoice['description']); ?></td>
                            <td><?php echo htmlspecialchars($invoice['location']); ?></td>
                            <td class="text-end"><?php echo format_currency($invoice['subtotal']); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Invoice Totals -->
            <div class="row">
                <div class="col-md-8"></div>
                <div class="col-md-4">
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Subtotal:</strong></td>
                            <td class="text-end"><?php echo format_currency($invoice['subtotal']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Tax (<?php echo $invoice['tax_rate']; ?>%):</strong></td>
                            <td class="text-end"><?php echo format_currency($invoice['tax_amount']); ?></td>
                        </tr>
                        <tr style="border-top: 2px solid var(--navy-500);">
                            <td><strong>Total:</strong></td>
                            <td class="text-end"><strong style="color: var(--navy-300); font-size: 16px;"><?php echo format_currency($invoice['total_amount']); ?></strong></td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <?php if ($invoice['paid_at']): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> This invoice was paid on <?php echo format_datetime($invoice['paid_at']); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<script>
// Real-time invoice calculation
document.addEventListener('DOMContentLoaded', function() {
    const subtotalInput = document.getElementById('subtotal');
    const taxRateInput = document.getElementById('tax_rate');
    
    if (subtotalInput && taxRateInput) {
        function updatePreview() {
            const subtotal = parseFloat(subtotalInput.value) || 0;
            const taxRate = parseFloat(taxRateInput.value) || 0;
            const taxAmount = subtotal * (taxRate / 100);
            const total = subtotal + taxAmount;
            
            document.getElementById('preview-subtotal').textContent = '$' + subtotal.toFixed(2);
            document.getElementById('preview-tax').textContent = '$' + taxAmount.toFixed(2);
            document.getElementById('preview-total').textContent = '$' + total.toFixed(2);
        }
        
        subtotalInput.addEventListener('input', updatePreview);
        taxRateInput.addEventListener('input', updatePreview);
        updatePreview(); // Initial calculation
    }
});

function updateInvoiceStatus(invoiceId, status) {
    if (confirm('Are you sure you want to mark this invoice as ' + status + '?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '?page=invoices&action=view&id=' + invoiceId;
        
        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'status';
        statusInput.value = status;
        
        const submitInput = document.createElement('input');
        submitInput.type = 'hidden';
        submitInput.name = 'update_invoice_status';
        submitInput.value = '1';
        
        form.appendChild(statusInput);
        form.appendChild(submitInput);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

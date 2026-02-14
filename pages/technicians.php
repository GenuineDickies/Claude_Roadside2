<?php
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_technician'])) {
        $phone = preg_replace('/\D/', '', $_POST['phone']);
        if (strlen($phone) === 10) {
            $phone = '(' . substr($phone, 0, 3) . ') ' . substr($phone, 3, 3) . '-' . substr($phone, 6, 4);
        } else {
            $phone = $_POST['phone'];
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

        show_alert($result ? 'Technician added successfully!' : 'Error adding technician.', $result ? 'success' : 'danger');
    } elseif (isset($_POST['edit_technician'])) {
        $phone = preg_replace('/\D/', '', $_POST['phone']);
        if (strlen($phone) === 10) {
            $phone = '(' . substr($phone, 0, 3) . ') ' . substr($phone, 3, 3) . '-' . substr($phone, 6, 4);
        } else {
            $phone = $_POST['phone'];
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
    $result = $stmt->execute([$id]);
    show_alert($result ? 'Technician deleted successfully!' : 'Error deleting technician.', $result ? 'success' : 'danger');
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

<div class="rr-page-header" style="margin: -28px -28px 24px -28px;">
    <div class="header-left">
        <i class="fas fa-user-cog header-icon"></i>
        <div>
            <h1>Technicians</h1>
            <div class="header-subtitle">Your field service team</div>
        </div>
    </div>
    <div class="header-actions">
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

<?php if ($action === 'list'): ?>
    <?php include __DIR__ . '/technicians/list.php'; ?>
<?php elseif ($action === 'add' || $action === 'edit'): ?>
    <?php include __DIR__ . '/technicians/form.php'; ?>
<?php endif; ?>

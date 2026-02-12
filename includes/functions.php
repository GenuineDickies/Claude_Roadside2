<?php
// Common functions for the application

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generate_invoice_number() {
    return 'INV-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

function format_currency($amount) {
    return '$' . number_format($amount, 2);
}

function format_phone($phone) {
    $digits = preg_replace('/\D/', '', $phone);
    if (strlen($digits) === 10) {
        return '(' . substr($digits, 0, 3) . ') ' . substr($digits, 3, 3) . '-' . substr($digits, 6, 4);
    } elseif (strlen($digits) === 11 && $digits[0] === '1') {
        return '(' . substr($digits, 1, 3) . ') ' . substr($digits, 4, 3) . '-' . substr($digits, 7, 4);
    }
    return $phone; // Return as-is if not standard US format
}

function format_date($date) {
    return date('M d, Y', strtotime($date));
}

function format_datetime($datetime) {
    return date('M d, Y g:i A', strtotime($datetime));
}

function get_status_badge($status) {
    $badges = [
        'pending' => 'warning',
        'assigned' => 'info',
        'in_progress' => 'primary',
        'completed' => 'success',
        'cancelled' => 'danger',
        'available' => 'success',
        'busy' => 'warning',
        'offline' => 'secondary',
        'draft' => 'secondary',
        'sent' => 'info',
        'paid' => 'success',
        'overdue' => 'danger',
        // Workflow statuses
        'presented' => 'info',
        'approved' => 'success',
        'declined' => 'danger',
        'revised' => 'warning',
        'expired' => 'secondary',
        'created' => 'info',
        'paused' => 'warning',
        'generated' => 'secondary',
        'viewed' => 'info',
        'partial' => 'warning',
        'disputed' => 'danger',
        'written_off' => 'secondary',
        'proposed' => 'warning',
        'voided' => 'secondary',
        // Service ticket statuses
        'dispatched' => 'info',
        'acknowledged' => 'info',
        'en_route' => 'primary',
        'on_scene' => 'primary',
        'closed' => 'success',
        'escalated' => 'danger',
        'on_hold' => 'warning',
    ];
    
    $class = $badges[$status] ?? 'secondary';
    return "<span class='badge bg-{$class}'>" . ucfirst(str_replace('_', ' ', $status)) . "</span>";
}

function get_priority_badge($priority) {
    $badges = [
        'low' => 'secondary',
        'medium' => 'info',
        'high' => 'warning',
        'urgent' => 'danger',
        'P1' => 'danger',
        'P2' => 'warning',
        'P3' => 'info',
        'P4' => 'secondary'
    ];
    $labels = [
        'P1' => 'Urgent',
        'P2' => 'High',
        'P3' => 'Normal',
        'P4' => 'Low'
    ];
    
    $class = $badges[$priority] ?? 'secondary';
    $label = $labels[$priority] ?? ucfirst($priority);
    return "<span class='badge bg-{$class}'>{$label}</span>";
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function show_alert($message, $type = 'info') {
    echo "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
            {$message}
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
          </div>";
}
?>

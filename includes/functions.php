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
        'overdue' => 'danger'
    ];
    
    $class = $badges[$status] ?? 'secondary';
    return "<span class='badge bg-{$class}'>" . ucfirst(str_replace('_', ' ', $status)) . "</span>";
}

function get_priority_badge($priority) {
    $badges = [
        'low' => 'secondary',
        'medium' => 'info',
        'high' => 'warning',
        'urgent' => 'danger'
    ];
    
    $class = $badges[$priority] ?? 'secondary';
    return "<span class='badge bg-{$class}'>" . ucfirst($priority) . "</span>";
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

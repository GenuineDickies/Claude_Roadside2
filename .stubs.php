<?php
/**
 * Intelephense helper stubs — declares functions loaded at runtime
 * via includes/functions.php through the main index.php bootstrap.
 * This file is never executed; it exists solely for static analysis.
 * @phpstan-ignore-next-line
 */

if (false) {
    /**
     * @param string $datetime
    /**
     * @param string $ticketNumber
     * @param string $version
     * @return string
     */
    function bump_ticket_number_version($ticketNumber, $version) { return ''; }
     * @return string
     */
    function format_datetime($datetime) { return ''; }

    /**
     * @param string $date
     * @return string
     */
    function format_date($date) { return ''; }

    /**
     * @param string $status
     * @return string
     */
    function get_status_badge($status) { return ''; }

    /**
     * @param string|null $ticketNumber
     * @return string
     */
    function format_ticket_number($ticketNumber) { return ''; }

    /**
     * @param string $priority
     * @return string
     */
    function get_priority_badge($priority) { return ''; }

    /**
     * @param string $input
     * @return string
     */
    function sanitize_input($input) { return ''; }

    /**
     * @param PDO $pdo
     * @param int $id
     * @return array|false
     */
    function get_service_ticket($pdo, $id) { return []; }

    /**
     * @param PDO $pdo
     * @param string $prefix
     * @param string $table
     * @param string $column
     * @return string
     */
    function generate_doc_id($pdo, $prefix, $table, $column, $version = 1, $serviceRequestId = null) { return ''; }

    /**
     * @param PDO $pdo
     * @param string $docType
     * @param int $docId
     * @return array
     */
    function get_line_items($pdo, $docType, $docId) { return []; }

    /**
     * @param array $items
     * @return array
     */
    function calculate_totals($items) { return []; }

    /**
     * @param PDO $pdo
     * @param string $fromType
     * @param int $fromId
     * @param string $toType
     * @param int $toId
     * @return void
     */
    function copy_line_items($pdo, $fromType, $fromId, $toType, $toId) {}

    /**
     * @param PDO $pdo
     * @param string $entityType
     * @param int $entityId
     * @param string $action
     * @param mixed $oldValue
     * @param mixed $newValue
     * @param string|null $details
     * @return void
     */
    function audit_log($pdo, $entityType, $entityId, $action, $oldValue = null, $newValue = null, $details = null) {}

    /** @var PDO $pdo */
    $pdo = new PDO('', '', '');
}

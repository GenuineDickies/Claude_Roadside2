<?php
/**
 * Workflow Document Chain — Table definitions
 * Tables: rate_schedules, parts_inventory, estimates, work_orders, change_orders,
 *         invoices_v2, receipts, document_line_items, document_attachments,
 *         document_audit_log, work_order_progress_log, payment_transactions
 */

function create_workflow_tables(PDO $pdo): void {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    $tables = [
        "CREATE TABLE IF NOT EXISTS rate_schedules (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            type ENUM('standard','after_hours','weekend','holiday','emergency') NOT NULL DEFAULT 'standard',
            hourly_rate DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            multiplier DECIMAL(4,2) NOT NULL DEFAULT 1.00,
            effective_from DATE NOT NULL,
            effective_to DATE DEFAULT NULL,
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS parts_inventory (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            part_number VARCHAR(50) NOT NULL,
            name VARCHAR(200) NOT NULL,
            description TEXT DEFAULT NULL,
            category VARCHAR(100) DEFAULT NULL,
            unit_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            markup_pct DECIMAL(5,2) NOT NULL DEFAULT 50.00,
            quantity_on_hand INT NOT NULL DEFAULT 0,
            reorder_point INT NOT NULL DEFAULT 5,
            supplier VARCHAR(200) DEFAULT NULL,
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_part_number (part_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS estimates (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            estimate_id VARCHAR(20) NOT NULL,
            service_request_id BIGINT UNSIGNED NOT NULL,
            version INT NOT NULL DEFAULT 1,
            status ENUM('draft','presented','approved','declined','revised','expired') NOT NULL DEFAULT 'draft',
            technician_id BIGINT UNSIGNED NOT NULL,
            diagnosis_summary TEXT DEFAULT NULL,
            diagnostic_codes JSON DEFAULT NULL,
            subtotal_labor DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            subtotal_parts DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            subtotal_services DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            subtotal_tow DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            tax_rate DECIMAL(5,4) NOT NULL DEFAULT 0.0825,
            tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            valid_until TIMESTAMP NULL DEFAULT NULL,
            approved_at TIMESTAMP NULL DEFAULT NULL,
            approval_method ENUM('verbal','signature','digital') DEFAULT NULL,
            approver_name VARCHAR(200) DEFAULT NULL,
            decline_reason VARCHAR(500) DEFAULT NULL,
            previous_version_id BIGINT UNSIGNED DEFAULT NULL,
            internal_notes TEXT DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_estimate_id (estimate_id),
            KEY idx_service_request (service_request_id),
            KEY idx_status (status),
            KEY idx_technician (technician_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS work_orders (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            work_order_id VARCHAR(20) NOT NULL,
            service_request_id BIGINT UNSIGNED NOT NULL,
            estimate_id BIGINT UNSIGNED DEFAULT NULL,
            status ENUM('created','in_progress','paused','completed','cancelled') NOT NULL DEFAULT 'created',
            technician_id BIGINT UNSIGNED NOT NULL,
            authorized_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            actual_labor_hours DECIMAL(5,2) DEFAULT NULL,
            actual_parts_cost DECIMAL(10,2) DEFAULT NULL,
            actual_total DECIMAL(10,2) DEFAULT NULL,
            variance_pct DECIMAL(5,2) DEFAULT NULL,
            work_started_at TIMESTAMP NULL DEFAULT NULL,
            work_completed_at TIMESTAMP NULL DEFAULT NULL,
            customer_signoff TINYINT(1) NOT NULL DEFAULT 0,
            signoff_at TIMESTAMP NULL DEFAULT NULL,
            quality_verified TINYINT(1) NOT NULL DEFAULT 0,
            notes TEXT DEFAULT NULL,
            diagnosis_summary TEXT DEFAULT NULL,
            diagnostic_codes JSON DEFAULT NULL,
            checklist_data JSON DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_wo_id (work_order_id),
            KEY idx_service_request (service_request_id),
            KEY idx_estimate (estimate_id),
            KEY idx_status (status),
            KEY idx_technician (technician_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS change_orders (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            change_order_id VARCHAR(20) NOT NULL,
            work_order_id BIGINT UNSIGNED NOT NULL,
            sequence_num INT NOT NULL DEFAULT 1,
            status ENUM('proposed','presented','approved','declined','voided') NOT NULL DEFAULT 'proposed',
            change_reason ENUM('discovery','customer_request','safety','parts_unavailable','other') NOT NULL DEFAULT 'discovery',
            reason_detail TEXT NOT NULL,
            original_scope_ref TEXT DEFAULT NULL,
            net_cost_impact DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            revised_wo_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            must_pause_work TINYINT(1) NOT NULL DEFAULT 1,
            approved_at TIMESTAMP NULL DEFAULT NULL,
            approval_method ENUM('verbal','signature','digital') DEFAULT NULL,
            declined_at TIMESTAMP NULL DEFAULT NULL,
            decline_reason VARCHAR(500) DEFAULT NULL,
            technician_id BIGINT UNSIGNED NOT NULL,
            technician_justification TEXT DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_co_id (change_order_id),
            KEY idx_work_order (work_order_id),
            KEY idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS invoices_v2 (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            invoice_id VARCHAR(20) NOT NULL,
            work_order_id BIGINT UNSIGNED NOT NULL,
            service_request_id BIGINT UNSIGNED NOT NULL,
            status ENUM('generated','sent','viewed','paid','partial','overdue','disputed','written_off') NOT NULL DEFAULT 'generated',
            invoice_date DATE NOT NULL,
            due_date DATE NOT NULL,
            payment_terms ENUM('due_on_receipt','net_15','net_30','net_60') NOT NULL DEFAULT 'due_on_receipt',
            subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            discount_reason VARCHAR(300) DEFAULT NULL,
            tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            grand_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            amount_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            balance_due DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            sent_email_at TIMESTAMP NULL DEFAULT NULL,
            sent_sms_at TIMESTAMP NULL DEFAULT NULL,
            viewed_at TIMESTAMP NULL DEFAULT NULL,
            paid_at TIMESTAMP NULL DEFAULT NULL,
            late_fee_applied DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            payment_instructions TEXT DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_invoice_id (invoice_id),
            KEY idx_work_order (work_order_id),
            KEY idx_service_request (service_request_id),
            KEY idx_status (status),
            KEY idx_due_date (due_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS receipts (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            receipt_id VARCHAR(20) NOT NULL,
            invoice_id BIGINT UNSIGNED NOT NULL,
            service_request_id BIGINT UNSIGNED NOT NULL,
            payment_method_used ENUM('card','cash','check','insurance_claim','fleet_po','split') NOT NULL,
            payment_reference VARCHAR(200) NOT NULL,
            processor_txn_id VARCHAR(100) DEFAULT NULL,
            amount_paid DECIMAL(10,2) NOT NULL,
            payment_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            service_summary TEXT NOT NULL,
            warranty_terms TEXT DEFAULT NULL,
            survey_sent_at TIMESTAMP NULL DEFAULT NULL,
            delivered_at TIMESTAMP NULL DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_receipt_id (receipt_id),
            KEY idx_invoice (invoice_id),
            KEY idx_service_request (service_request_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS document_line_items (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            document_type ENUM('estimate','work_order','change_order','invoice') NOT NULL,
            document_id BIGINT UNSIGNED NOT NULL,
            line_number INT NOT NULL DEFAULT 1,
            item_type ENUM('labor','parts','service_fee','tow_mileage','discount','tax') NOT NULL,
            catalog_service_id BIGINT UNSIGNED DEFAULT NULL,
            catalog_part_id BIGINT UNSIGNED DEFAULT NULL,
            description VARCHAR(500) NOT NULL,
            quantity DECIMAL(8,2) NOT NULL DEFAULT 1.00,
            unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            markup_pct DECIMAL(5,2) DEFAULT NULL,
            extended_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            taxable TINYINT(1) NOT NULL DEFAULT 1,
            is_actual TINYINT(1) NOT NULL DEFAULT 0,
            source_line_item_id BIGINT UNSIGNED DEFAULT NULL,
            notes VARCHAR(500) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_document (document_type, document_id, line_number),
            KEY idx_source (source_line_item_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS document_attachments (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            document_type ENUM('service_request','estimate','work_order','change_order') NOT NULL,
            document_id BIGINT UNSIGNED NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_type ENUM('photo','pdf','signature','other') NOT NULL DEFAULT 'photo',
            label VARCHAR(200) DEFAULT NULL,
            uploaded_by BIGINT UNSIGNED NOT NULL,
            uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_document (document_type, document_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS document_audit_log (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            document_type ENUM('service_request','estimate','work_order','change_order','invoice','receipt') NOT NULL,
            document_id BIGINT UNSIGNED NOT NULL,
            action VARCHAR(100) NOT NULL,
            old_value TEXT DEFAULT NULL,
            new_value TEXT DEFAULT NULL,
            performed_by BIGINT UNSIGNED NOT NULL,
            performed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(45) DEFAULT NULL,
            notes VARCHAR(500) DEFAULT NULL,
            KEY idx_document (document_type, document_id),
            KEY idx_performed_at (performed_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS work_order_progress_log (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            work_order_id BIGINT UNSIGNED NOT NULL,
            entry_type ENUM('note','clock_start','clock_stop','photo','checklist_item','status_change') NOT NULL,
            content TEXT DEFAULT NULL,
            attachment_id BIGINT UNSIGNED DEFAULT NULL,
            technician_id BIGINT UNSIGNED NOT NULL,
            logged_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_work_order (work_order_id),
            KEY idx_logged (logged_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS payment_transactions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            invoice_id BIGINT UNSIGNED NOT NULL,
            receipt_id BIGINT UNSIGNED DEFAULT NULL,
            payment_method ENUM('card','cash','check','insurance_claim','fleet_po') NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            processor VARCHAR(100) DEFAULT NULL,
            processor_txn_id VARCHAR(200) DEFAULT NULL,
            status ENUM('pending','completed','failed','refunded','disputed') NOT NULL DEFAULT 'pending',
            processed_at TIMESTAMP NULL DEFAULT NULL,
            recorded_by BIGINT UNSIGNED NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_invoice (invoice_id),
            KEY idx_receipt (receipt_id),
            KEY idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];

    foreach ($tables as $sql) {
        try { $pdo->exec($sql); } catch (PDOException $e) { /* exists */ }
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
}

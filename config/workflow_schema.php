<?php
/**
 * RoadRunner Workflow Document Chain — Schema Orchestrator
 *
 * Depends on: customers, customer_vehicles, technicians, users,
 *             service_categories, service_types (from intake_schema.php)
 *
 * Auto-bootstraps on first require — safe to call repeatedly.
 */

require_once __DIR__ . '/schema/workflow_tables.php';
require_once __DIR__ . '/schema/workflow_seeds.php';

function bootstrap_workflow_schema(PDO $pdo): void {
    static $done = false;
    if ($done) return;
    $done = true;

    create_workflow_tables($pdo);
    seed_workflow_data($pdo);
}

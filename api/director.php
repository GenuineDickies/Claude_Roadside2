<?php
/**
 * Production Director API — Router
 * Dispatches to handler files in api/director/
 */
require_once __DIR__ . '/../includes/api_bootstrap.php';
require_once __DIR__ . '/../config/director_schema.php';

bootstrap_director_schema($pdo);

function director_audit_log($pdo, $type, $id, $action, $details = null) {
    $stmt = $pdo->prepare("INSERT INTO director_audit_log (entity_type, entity_id, action, details, user_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$type, $id, $action, $details, $_SESSION['user_id'] ?? null]);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

$artifactActions = ['get_artifacts', 'update_artifact_status', 'save_artifact_content'];
$taskActions     = ['get_tasks', 'create_task', 'update_task_state', 'delete_task'];
$gateActions     = ['get_gates', 'toggle_gate', 'create_gate'];
$decisionActions = ['get_decisions', 'create_decision', 'update_decision_status'];
$releaseActions  = ['get_releases', 'create_release', 'update_release_gate'];
$statActions     = ['get_audit_log', 'get_stats'];

try {
    if (in_array($action, $artifactActions, true)) {
        require __DIR__ . '/director/artifacts.php';
    } elseif (in_array($action, $taskActions, true)) {
        require __DIR__ . '/director/tasks.php';
    } elseif (in_array($action, $gateActions, true)) {
        require __DIR__ . '/director/gates.php';
    } elseif (in_array($action, $decisionActions, true)) {
        require __DIR__ . '/director/decisions.php';
    } elseif (in_array($action, $releaseActions, true)) {
        require __DIR__ . '/director/releases.php';
    } elseif (in_array($action, $statActions, true)) {
        require __DIR__ . '/director/stats.php';
    } else {
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

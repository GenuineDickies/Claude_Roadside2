<?php
/**
 * Director API — Quality Gate handlers
 * Actions: get_gates, toggle_gate, create_gate
 */

switch ($action) {
    case 'get_gates':
        $taskId = intval($_GET['task_id'] ?? 0);
        if (!$taskId) { echo json_encode(['success' => false]); break; }
        $stmt = $pdo->prepare("SELECT * FROM director_quality_gates WHERE task_id = ? ORDER BY id");
        $stmt->execute([$taskId]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'toggle_gate':
        $id = intval($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false]); break; }
        $stmt = $pdo->prepare("UPDATE director_quality_gates SET passed = NOT passed, checked_at = CASE WHEN passed = 0 THEN NOW() ELSE NULL END WHERE id = ?");
        $stmt->execute([$id]);
        director_audit_log($pdo, 'gate', $id, 'toggled');
        echo json_encode(['success' => true]);
        break;

    case 'create_gate':
        $taskId = intval($_POST['task_id'] ?? 0);
        $name = trim($_POST['gate_name'] ?? '');
        if (!$taskId || !$name) { echo json_encode(['success' => false]); break; }
        $stmt = $pdo->prepare("INSERT INTO director_quality_gates (task_id, gate_name) VALUES (?, ?)");
        $stmt->execute([$taskId, $name]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        break;
}

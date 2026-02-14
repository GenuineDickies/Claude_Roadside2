<?php
/**
 * Director API — Artifact handlers
 * Actions: get_artifacts, update_artifact_status, save_artifact_content
 */

switch ($action) {
    case 'get_artifacts':
        $rows = $pdo->query("SELECT * FROM director_artifacts ORDER BY phase, sort_order")->fetchAll();
        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    case 'update_artifact_status':
        $id = intval($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $allowed = ['missing','draft','review','approved','outdated'];
        if (!$id || !in_array($status, $allowed)) {
            echo json_encode(['success' => false, 'error' => 'Invalid input']);
            break;
        }
        $stmt = $pdo->prepare("UPDATE director_artifacts SET status = ?, version = version + 1 WHERE id = ?");
        $stmt->execute([$status, $id]);
        director_audit_log($pdo, 'artifact', $id, 'status_change', "Status → $status");
        echo json_encode(['success' => true]);
        break;

    case 'save_artifact_content':
        $id = intval($_POST['id'] ?? 0);
        $content = $_POST['content'] ?? '';
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'Invalid ID']);
            break;
        }
        $stmt = $pdo->prepare("UPDATE director_artifacts SET content = ?, status = CASE WHEN status = 'missing' THEN 'draft' ELSE status END, version = version + 1 WHERE id = ?");
        $stmt->execute([$content, $id]);
        director_audit_log($pdo, 'artifact', $id, 'content_saved', substr($content, 0, 100));
        echo json_encode(['success' => true]);
        break;
}

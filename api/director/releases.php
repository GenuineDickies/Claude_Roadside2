<?php
/**
 * Director API — Release handlers
 * Actions: get_releases, create_release, update_release_gate
 */

switch ($action) {
    case 'get_releases':
        $rows = $pdo->query("SELECT * FROM director_releases ORDER BY created_at DESC")->fetchAll();
        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    case 'create_release':
        $version = trim($_POST['version'] ?? '');
        $scope = trim($_POST['scope'] ?? '');
        $rollbackRef = trim($_POST['rollback_ref'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        if (!$version) { echo json_encode(['success' => false, 'error' => 'Version required']); break; }
        $stmt = $pdo->prepare("INSERT INTO director_releases (version, scope, rollback_ref, notes) VALUES (?, ?, ?, ?)");
        $stmt->execute([$version, $scope, $rollbackRef, $notes]);
        $relId = $pdo->lastInsertId();
        director_audit_log($pdo, 'release', $relId, 'created', "v$version");
        echo json_encode(['success' => true, 'id' => $relId]);
        break;

    case 'update_release_gate':
        $id = intval($_POST['id'] ?? 0);
        $gateStatus = $_POST['gate_status'] ?? '';
        $allowed = ['pending','passing','failing','released'];
        if (!$id || !in_array($gateStatus, $allowed)) { echo json_encode(['success' => false]); break; }
        $released = $gateStatus === 'released' ? ", released_at = NOW()" : "";
        $stmt = $pdo->prepare("UPDATE director_releases SET gate_status = ? $released WHERE id = ?");
        $stmt->execute([$gateStatus, $id]);
        director_audit_log($pdo, 'release', $id, 'gate_update', "Gate → $gateStatus");
        echo json_encode(['success' => true]);
        break;
}

<?php
/**
 * Director API — Decision (ADR) handlers
 * Actions: get_decisions, create_decision, update_decision_status
 */

switch ($action) {
    case 'get_decisions':
        $rows = $pdo->query("SELECT * FROM director_decisions ORDER BY adr_number DESC")->fetchAll();
        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    case 'create_decision':
        $title = trim($_POST['title'] ?? '');
        $context = trim($_POST['context'] ?? '');
        $decision = trim($_POST['decision'] ?? '');
        $consequences = trim($_POST['consequences'] ?? '');
        $impact = $_POST['impact'] ?? 'medium';
        if (!$title) { echo json_encode(['success' => false, 'error' => 'Title required']); break; }
        $maxAdr = $pdo->query("SELECT COALESCE(MAX(adr_number), 0) FROM director_decisions")->fetchColumn();
        $stmt = $pdo->prepare("INSERT INTO director_decisions (adr_number, title, context, decision, consequences, impact) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$maxAdr + 1, $title, $context, $decision, $consequences, $impact]);
        $decId = $pdo->lastInsertId();
        director_audit_log($pdo, 'decision', $decId, 'created', $title);
        echo json_encode(['success' => true, 'id' => $decId]);
        break;

    case 'update_decision_status':
        $id = intval($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $allowed = ['proposed','accepted','deprecated','superseded'];
        if (!$id || !in_array($status, $allowed)) { echo json_encode(['success' => false]); break; }
        $stmt = $pdo->prepare("UPDATE director_decisions SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        director_audit_log($pdo, 'decision', $id, 'status_change', "Status → $status");
        echo json_encode(['success' => true]);
        break;
}

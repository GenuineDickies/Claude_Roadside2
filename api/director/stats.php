<?php
/**
 * Director API — Stats & Audit Log handlers
 * Actions: get_audit_log, get_stats
 */

switch ($action) {
    case 'get_audit_log':
        $limit = intval($_GET['limit'] ?? 50);
        $stmt = $pdo->prepare("SELECT al.*, u.username FROM director_audit_log al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'get_stats':
        $stats = [];
        $stats['artifacts'] = $pdo->query("
            SELECT status, COUNT(*) as count FROM director_artifacts GROUP BY status
        ")->fetchAll();
        $stats['artifact_total'] = $pdo->query("SELECT COUNT(*) FROM director_artifacts")->fetchColumn();
        $stats['artifacts_approved'] = $pdo->query("SELECT COUNT(*) FROM director_artifacts WHERE status = 'approved'")->fetchColumn();
        $stats['tasks'] = $pdo->query("
            SELECT state, COUNT(*) as count FROM director_tasks GROUP BY state
        ")->fetchAll();
        $stats['task_total'] = $pdo->query("SELECT COUNT(*) FROM director_tasks")->fetchColumn();
        $stats['tasks_done'] = $pdo->query("SELECT COUNT(*) FROM director_tasks WHERE state = 'done'")->fetchColumn();
        $stats['phases'] = $pdo->query("
            SELECT phase,
                   COUNT(*) as total,
                   SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                   SUM(CASE WHEN status = 'missing' THEN 1 ELSE 0 END) as missing
            FROM director_artifacts GROUP BY phase ORDER BY phase
        ")->fetchAll();
        $stats['recent_activity'] = $pdo->query("
            SELECT al.*, u.username
            FROM director_audit_log al
            LEFT JOIN users u ON al.user_id = u.id
            ORDER BY al.created_at DESC LIMIT 10
        ")->fetchAll();
        $stats['decision_count'] = $pdo->query("SELECT COUNT(*) FROM director_decisions")->fetchColumn();
        $stats['release_count'] = $pdo->query("SELECT COUNT(*) FROM director_releases")->fetchColumn();

        echo json_encode(['success' => true, 'data' => $stats]);
        break;
}

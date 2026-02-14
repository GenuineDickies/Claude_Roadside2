<?php
/**
 * Director API — Task handlers
 * Actions: get_tasks, create_task, update_task_state, delete_task
 */

switch ($action) {
    case 'get_tasks':
        $state = $_GET['state'] ?? '';
        $sql = "SELECT t.*, a.title as artifact_title, a.path as artifact_path
                FROM director_tasks t
                LEFT JOIN director_artifacts a ON t.artifact_id = a.id";
        if ($state && in_array($state, ['todo','doing','blocked','done','cancelled'])) {
            $sql .= " WHERE t.state = " . $pdo->quote($state);
        }
        $sql .= " ORDER BY FIELD(t.priority, 'critical','high','medium','low'), t.created_at DESC";
        $rows = $pdo->query($sql)->fetchAll();
        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    case 'create_task':
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $priority = $_POST['priority'] ?? 'medium';
        $artifactId = intval($_POST['artifact_id'] ?? 0) ?: null;
        $assignee = trim($_POST['assignee'] ?? '') ?: null;
        $dueDate = trim($_POST['due_date'] ?? '') ?: null;
        if (!$title) {
            echo json_encode(['success' => false, 'error' => 'Title required']);
            break;
        }
        $stmt = $pdo->prepare("INSERT INTO director_tasks (artifact_id, title, description, priority, assignee, due_date) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$artifactId, $title, $description, $priority, $assignee, $dueDate]);
        $taskId = $pdo->lastInsertId();
        director_audit_log($pdo, 'task', $taskId, 'created', $title);
        echo json_encode(['success' => true, 'id' => $taskId]);
        break;

    case 'update_task_state':
        $id = intval($_POST['id'] ?? 0);
        $state = $_POST['state'] ?? '';
        $allowed = ['todo','doing','blocked','done','cancelled'];
        if (!$id || !in_array($state, $allowed)) {
            echo json_encode(['success' => false, 'error' => 'Invalid input']);
            break;
        }
        $stmt = $pdo->prepare("UPDATE director_tasks SET state = ? WHERE id = ?");
        $stmt->execute([$state, $id]);
        director_audit_log($pdo, 'task', $id, 'state_change', "State → $state");
        echo json_encode(['success' => true]);
        break;

    case 'delete_task':
        $id = intval($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false]); break; }
        $pdo->prepare("DELETE FROM director_quality_gates WHERE task_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM director_tasks WHERE id = ?")->execute([$id]);
        director_audit_log($pdo, 'task', $id, 'deleted');
        echo json_encode(['success' => true]);
        break;
}

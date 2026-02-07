<?php
/**
 * Production Director API
 * Handles all CRUD for artifacts, tasks, quality gates, decisions, releases
 */
$sessionDir = __DIR__ . '/../sessions';
if (!is_dir($sessionDir)) { mkdir($sessionDir, 0755, true); }
session_save_path($sessionDir);
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Auth check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// ─── Bootstrap director tables ───────────────────────────────────────
$directorTables = [
    "CREATE TABLE IF NOT EXISTS director_artifacts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type ENUM('direction','product','architecture','delivery','qa','ops','agent') NOT NULL,
        title VARCHAR(200) NOT NULL,
        path VARCHAR(300) NOT NULL,
        status ENUM('missing','draft','review','approved','outdated') NOT NULL DEFAULT 'missing',
        version INT NOT NULL DEFAULT 1,
        owner VARCHAR(100) DEFAULT NULL,
        phase INT NOT NULL DEFAULT 0,
        sort_order INT NOT NULL DEFAULT 0,
        content LONGTEXT DEFAULT NULL,
        last_updated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_path (path)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS director_dependencies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        artifact_id INT NOT NULL,
        depends_on_id INT NOT NULL,
        KEY idx_artifact (artifact_id),
        KEY idx_depends (depends_on_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS director_tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        artifact_id INT DEFAULT NULL,
        title VARCHAR(200) NOT NULL,
        description TEXT DEFAULT NULL,
        priority ENUM('critical','high','medium','low') NOT NULL DEFAULT 'medium',
        state ENUM('todo','doing','blocked','done','cancelled') NOT NULL DEFAULT 'todo',
        assignee VARCHAR(100) DEFAULT NULL,
        due_date DATE DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_artifact (artifact_id),
        KEY idx_state (state)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS director_quality_gates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        task_id INT NOT NULL,
        gate_name VARCHAR(100) NOT NULL,
        passed TINYINT(1) NOT NULL DEFAULT 0,
        evidence_link VARCHAR(300) DEFAULT NULL,
        checked_at TIMESTAMP NULL DEFAULT NULL,
        KEY idx_task (task_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS director_decisions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        adr_number INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        status ENUM('proposed','accepted','deprecated','superseded') NOT NULL DEFAULT 'proposed',
        context TEXT DEFAULT NULL,
        decision TEXT DEFAULT NULL,
        consequences TEXT DEFAULT NULL,
        impact ENUM('high','medium','low') NOT NULL DEFAULT 'medium',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_adr (adr_number)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS director_releases (
        id INT AUTO_INCREMENT PRIMARY KEY,
        version VARCHAR(20) NOT NULL,
        scope TEXT DEFAULT NULL,
        gate_status ENUM('pending','passing','failing','released') NOT NULL DEFAULT 'pending',
        rollback_ref VARCHAR(100) DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        released_at TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_version (version)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS director_audit_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        entity_type VARCHAR(50) NOT NULL,
        entity_id INT NOT NULL,
        action VARCHAR(50) NOT NULL,
        details TEXT DEFAULT NULL,
        user_id INT DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_entity (entity_type, entity_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

foreach ($directorTables as $sql) {
    try { $pdo->exec($sql); } catch (PDOException $e) { /* table exists */ }
}

// ─── Seed default artifact registry ──────────────────────────────────
$defaultArtifacts = [
    // Phase 0 — Direction
    ['direction', 'Master Roadmap',                     '/docs/00-director/roadmap.md',              0, 1],
    ['direction', 'Milestones & Release Plan',          '/docs/00-director/release-plan.md',         0, 2],
    ['direction', 'Risk Register',                      '/docs/00-director/risk-register.md',        0, 3],
    ['direction', 'Decision Log (ADR Index)',            '/docs/00-director/decision-log.md',         0, 4],
    // Phase 1 — Product
    ['product',   'Project Brief',                      '/docs/10-product/project-brief.md',         1, 1],
    ['product',   'Requirements (MVP + v2)',             '/docs/10-product/requirements.md',          1, 2],
    ['product',   'Business Rules Master',              '/docs/10-product/business-rules-master.md', 1, 3],
    ['product',   'Workflow: Service Lifecycle',         '/docs/10-product/workflow-service-lifecycle.md', 1, 4],
    ['product',   'Glossary',                           '/docs/10-product/glossary.md',              1, 5],
    // Phase 2 — Architecture
    ['architecture', 'Architecture Document',           '/docs/20-architecture/architecture.md',     2, 1],
    ['architecture', 'Data Model + ERD',                '/docs/20-architecture/data-model.md',       2, 2],
    ['architecture', 'API Contracts & Integrations',    '/docs/20-architecture/integrations.md',     2, 3],
    ['architecture', 'Naming Conventions',              '/docs/20-architecture/naming-conventions.md', 2, 4],
    ['architecture', 'Style Guide (UI/System)',         '/docs/20-architecture/style-guide.md',      2, 5],
    // Phase 3 — Delivery
    ['delivery',  'AGENTS Constitution',                '/AGENTS.md',                                3, 1],
    ['delivery',  'Agent Rules Pack',                   '/agent-rules/',                             3, 2],
    ['delivery',  'Backlog (Prioritized)',              '/docs/30-delivery/backlog.md',              3, 3],
    ['delivery',  'Sprint Board (Current)',             '/docs/30-delivery/sprint-current.md',       3, 4],
    ['delivery',  'Definition of Done',                 '/docs/30-delivery/definition-of-done.md',   3, 5],
    ['delivery',  'Traceability Matrix',                '/docs/30-delivery/traceability-matrix.md',  3, 6],
    // Phase 4 — QA + Ops
    ['qa',        'Test Strategy',                      '/docs/40-qa/test-strategy.md',              4, 1],
    ['qa',        'Regression Checklist',               '/docs/40-qa/regression-checklist.md',       4, 2],
    ['ops',       'Runbook (Local + Server)',            '/docs/50-ops/runbook.md',                   4, 3],
    ['ops',       'Release Checklist',                  '/docs/50-ops/release-checklist.md',         4, 4],
    ['ops',       'Rollback Plan',                      '/docs/50-ops/rollback-plan.md',             4, 5],
];

// Only seed if table is empty
$artifactCount = $pdo->query("SELECT COUNT(*) FROM director_artifacts")->fetchColumn();
if ($artifactCount == 0) {
    $stmt = $pdo->prepare("INSERT INTO director_artifacts (type, title, path, phase, sort_order, status) VALUES (?, ?, ?, ?, ?, 'missing')");
    foreach ($defaultArtifacts as $a) {
        try { $stmt->execute($a); } catch (PDOException $e) { /* duplicate */ }
    }
    // Seed dependencies (phase ordering: phase 1 depends on phase 0, etc.)
    // Get artifact IDs for cross-phase dependencies
    $artifacts = $pdo->query("SELECT id, phase, sort_order FROM director_artifacts ORDER BY phase, sort_order")->fetchAll();
    $depStmt = $pdo->prepare("INSERT INTO director_dependencies (artifact_id, depends_on_id) VALUES (?, ?)");
    $byPhase = [];
    foreach ($artifacts as $art) { $byPhase[$art['phase']][] = $art['id']; }
    // Each phase's first artifact depends on all previous phase artifacts
    for ($p = 1; $p <= 4; $p++) {
        if (isset($byPhase[$p]) && isset($byPhase[$p - 1])) {
            foreach ($byPhase[$p] as $childId) {
                // Depend on last artifact of previous phase (simplified)
                $parentId = end($byPhase[$p - 1]);
                try { $depStmt->execute([$childId, $parentId]); } catch (PDOException $e) {}
            }
        }
    }
}

// ─── Route API requests ──────────────────────────────────────────────
$action = $_GET['action'] ?? $_POST['action'] ?? '';

function audit_log($pdo, $type, $id, $action, $details = null) {
    $stmt = $pdo->prepare("INSERT INTO director_audit_log (entity_type, entity_id, action, details, user_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$type, $id, $action, $details, $_SESSION['user_id'] ?? null]);
}

switch ($action) {
    // ── Artifacts ────────────────────────────
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
        audit_log($pdo, 'artifact', $id, 'status_change', "Status → $status");
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
        audit_log($pdo, 'artifact', $id, 'content_saved', substr($content, 0, 100));
        echo json_encode(['success' => true]);
        break;

    // ── Tasks ────────────────────────────────
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
        audit_log($pdo, 'task', $taskId, 'created', $title);
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
        audit_log($pdo, 'task', $id, 'state_change', "State → $state");
        echo json_encode(['success' => true]);
        break;

    case 'delete_task':
        $id = intval($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false]); break; }
        $pdo->prepare("DELETE FROM director_quality_gates WHERE task_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM director_tasks WHERE id = ?")->execute([$id]);
        audit_log($pdo, 'task', $id, 'deleted');
        echo json_encode(['success' => true]);
        break;

    // ── Quality Gates ────────────────────────
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
        audit_log($pdo, 'gate', $id, 'toggled');
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

    // ── Decisions (ADR) ──────────────────────
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
        audit_log($pdo, 'decision', $decId, 'created', $title);
        echo json_encode(['success' => true, 'id' => $decId]);
        break;

    case 'update_decision_status':
        $id = intval($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $allowed = ['proposed','accepted','deprecated','superseded'];
        if (!$id || !in_array($status, $allowed)) { echo json_encode(['success' => false]); break; }
        $stmt = $pdo->prepare("UPDATE director_decisions SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        audit_log($pdo, 'decision', $id, 'status_change', "Status → $status");
        echo json_encode(['success' => true]);
        break;

    // ── Releases ─────────────────────────────
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
        audit_log($pdo, 'release', $relId, 'created', "v$version");
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
        audit_log($pdo, 'release', $id, 'gate_update', "Gate → $gateStatus");
        echo json_encode(['success' => true]);
        break;

    // ── Audit Log ────────────────────────────
    case 'get_audit_log':
        $limit = intval($_GET['limit'] ?? 50);
        $stmt = $pdo->prepare("SELECT al.*, u.username FROM director_audit_log al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    // ── Dashboard Stats ──────────────────────
    case 'get_stats':
        $stats = [];
        // Artifact breakdown
        $stats['artifacts'] = $pdo->query("
            SELECT status, COUNT(*) as count FROM director_artifacts GROUP BY status
        ")->fetchAll();
        $stats['artifact_total'] = $pdo->query("SELECT COUNT(*) FROM director_artifacts")->fetchColumn();
        $stats['artifacts_approved'] = $pdo->query("SELECT COUNT(*) FROM director_artifacts WHERE status = 'approved'")->fetchColumn();
        // Task breakdown
        $stats['tasks'] = $pdo->query("
            SELECT state, COUNT(*) as count FROM director_tasks GROUP BY state
        ")->fetchAll();
        $stats['task_total'] = $pdo->query("SELECT COUNT(*) FROM director_tasks")->fetchColumn();
        $stats['tasks_done'] = $pdo->query("SELECT COUNT(*) FROM director_tasks WHERE state = 'done'")->fetchColumn();
        // Phases
        $stats['phases'] = $pdo->query("
            SELECT phase, 
                   COUNT(*) as total,
                   SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                   SUM(CASE WHEN status = 'missing' THEN 1 ELSE 0 END) as missing
            FROM director_artifacts GROUP BY phase ORDER BY phase
        ")->fetchAll();
        // Recent activity
        $stats['recent_activity'] = $pdo->query("
            SELECT al.*, u.username 
            FROM director_audit_log al 
            LEFT JOIN users u ON al.user_id = u.id 
            ORDER BY al.created_at DESC LIMIT 10
        ")->fetchAll();
        // Decisions
        $stats['decision_count'] = $pdo->query("SELECT COUNT(*) FROM director_decisions")->fetchColumn();
        // Releases
        $stats['release_count'] = $pdo->query("SELECT COUNT(*) FROM director_releases")->fetchColumn();
        
        echo json_encode(['success' => true, 'data' => $stats]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
}

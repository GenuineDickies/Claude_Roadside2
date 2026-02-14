<?php
/**
 * Director Module Schema — tables and seed data
 * Auto-bootstraps on first require — safe to call repeatedly.
 */

function bootstrap_director_schema(PDO $pdo): void {
    static $done = false;
    if ($done) return;
    $done = true;

    $tables = [
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];

    foreach ($tables as $sql) {
        try { $pdo->exec($sql); } catch (PDOException $e) { /* table exists */ }
    }

    // Seed default artifact registry
    $artifactCount = $pdo->query("SELECT COUNT(*) FROM director_artifacts")->fetchColumn();
    if ($artifactCount == 0) {
        $defaults = [
            ['direction', 'Master Roadmap',                     '/docs/00-director/roadmap.md',              0, 1],
            ['direction', 'Milestones & Release Plan',          '/docs/00-director/release-plan.md',         0, 2],
            ['direction', 'Risk Register',                      '/docs/00-director/risk-register.md',        0, 3],
            ['direction', 'Decision Log (ADR Index)',            '/docs/00-director/decision-log.md',         0, 4],
            ['product',   'Project Brief',                      '/docs/10-product/project-brief.md',         1, 1],
            ['product',   'Requirements (MVP + v2)',             '/docs/10-product/requirements.md',          1, 2],
            ['product',   'Business Rules Master',              '/docs/10-product/business-rules-master.md', 1, 3],
            ['product',   'Workflow: Service Lifecycle',         '/docs/10-product/workflow-service-lifecycle.md', 1, 4],
            ['product',   'Glossary',                           '/docs/10-product/glossary.md',              1, 5],
            ['architecture', 'Architecture Document',           '/docs/20-architecture/architecture.md',     2, 1],
            ['architecture', 'Data Model + ERD',                '/docs/20-architecture/data-model.md',       2, 2],
            ['architecture', 'API Contracts & Integrations',    '/docs/20-architecture/integrations.md',     2, 3],
            ['architecture', 'Naming Conventions',              '/docs/20-architecture/naming-conventions.md', 2, 4],
            ['architecture', 'Style Guide (UI/System)',         '/docs/20-architecture/style-guide.md',      2, 5],
            ['delivery',  'AGENTS Constitution',                '/AGENTS.md',                                3, 1],
            ['delivery',  'Agent Rules Pack',                   '/agent-rules/',                             3, 2],
            ['delivery',  'Backlog (Prioritized)',              '/docs/30-delivery/backlog.md',              3, 3],
            ['delivery',  'Sprint Board (Current)',             '/docs/30-delivery/sprint-current.md',       3, 4],
            ['delivery',  'Definition of Done',                 '/docs/30-delivery/definition-of-done.md',   3, 5],
            ['delivery',  'Traceability Matrix',                '/docs/30-delivery/traceability-matrix.md',  3, 6],
            ['qa',        'Test Strategy',                      '/docs/40-qa/test-strategy.md',              4, 1],
            ['qa',        'Regression Checklist',               '/docs/40-qa/regression-checklist.md',       4, 2],
            ['ops',       'Runbook (Local + Server)',            '/docs/50-ops/runbook.md',                   4, 3],
            ['ops',       'Release Checklist',                  '/docs/50-ops/release-checklist.md',         4, 4],
            ['ops',       'Rollback Plan',                      '/docs/50-ops/rollback-plan.md',             4, 5],
        ];
        $stmt = $pdo->prepare("INSERT INTO director_artifacts (type, title, path, phase, sort_order, status) VALUES (?, ?, ?, ?, ?, 'missing')");
        foreach ($defaults as $a) {
            try { $stmt->execute($a); } catch (PDOException $e) { /* duplicate */ }
        }
        // Seed phase dependencies
        $artifacts = $pdo->query("SELECT id, phase, sort_order FROM director_artifacts ORDER BY phase, sort_order")->fetchAll();
        $depStmt = $pdo->prepare("INSERT INTO director_dependencies (artifact_id, depends_on_id) VALUES (?, ?)");
        $byPhase = [];
        foreach ($artifacts as $art) { $byPhase[$art['phase']][] = $art['id']; }
        for ($p = 1; $p <= 4; $p++) {
            if (isset($byPhase[$p]) && isset($byPhase[$p - 1])) {
                foreach ($byPhase[$p] as $childId) {
                    $parentId = end($byPhase[$p - 1]);
                    try { $depStmt->execute([$childId, $parentId]); } catch (PDOException $e) {}
                }
            }
        }
    }
}

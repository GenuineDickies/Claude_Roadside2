<?php
/**
 * Production Director — Control Tower
 * Self-managing documentation & delivery pipeline dashboard
 * Tables auto-bootstrap on first API call.
 */
?>

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/pages/director-layout.css">
<link rel="stylesheet" href="assets/css/pages/director-ui.css">

<!-- ═══════════════ HEADER ═══════════════ -->
<div class="director-header">
    <div style="display:flex;align-items:center;gap:14px">
        <i class="fas fa-satellite-dish" style="font-size:26px;color:var(--navy-300)"></i>
        <div>
            <h1 style="font-size:24px;font-weight:700;color:var(--navy-300);letter-spacing:-0.5px;margin:0">Production Director</h1>
            <p style="font-size:13px;color:var(--text-secondary);margin:2px 0 0">Control tower — plan, create, verify, archive</p>
        </div>
        <div style="margin-left:auto;display:flex;gap:8px">
            <button class="director-btn secondary" onclick="Director.refresh()"><i class="fas fa-sync-alt"></i> Refresh</button>
        </div>
    </div>
</div>

<!-- ═══════════════ TAB NAV ═══════════════ -->
<div class="director-tab-nav" id="directorTabs">
    <button class="director-tab-btn active" data-tab="overview"><i class="fas fa-th-large"></i> Overview</button>
    <button class="director-tab-btn" data-tab="artifacts"><i class="fas fa-archive"></i> Artifact Registry <span class="tab-count" id="tabArtifactCount">0</span></button>
    <button class="director-tab-btn" data-tab="queue"><i class="fas fa-tasks"></i> Build Queue <span class="tab-count" id="tabTaskCount">0</span></button>
    <button class="director-tab-btn" data-tab="decisions"><i class="fas fa-gavel"></i> Decisions <span class="tab-count" id="tabDecisionCount">0</span></button>
    <button class="director-tab-btn" data-tab="releases"><i class="fas fa-rocket"></i> Releases <span class="tab-count" id="tabReleaseCount">0</span></button>
    <button class="director-tab-btn" data-tab="audit"><i class="fas fa-history"></i> Audit Trail</button>
</div>

<!-- ═══════════════ PANELS ═══════════════ -->
<div class="director-body">
    <!-- ── OVERVIEW PANEL ─────────────────────── -->
    <div class="director-panel active" id="panel-overview">
        <div class="director-stats" id="overviewStats"></div>
        <div class="row">
            <div class="col-lg-7">
                <div class="director-section-title"><i class="fas fa-layer-group"></i> Phase Progress</div>
                <div id="phaseProgress"></div>
            </div>
            <div class="col-lg-5">
                <div class="director-section-title"><i class="fas fa-bolt"></i> Recent Activity</div>
                <div style="background:var(--bg-surface);border:1px solid var(--border-medium);border-radius:10px;max-height:400px;overflow-y:auto">
                    <ul class="director-activity" id="activityFeed">
                        <li class="director-empty"><i class="fas fa-ghost"></i>No activity yet</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- ── ARTIFACT REGISTRY PANEL ────────────── -->
    <div class="director-panel" id="panel-artifacts">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
            <div class="director-section-title" style="margin-bottom:0"><i class="fas fa-archive"></i> Artifact Registry</div>
            <div style="display:flex;gap:8px;align-items:center">
                <select class="director-status-select" id="artifactPhaseFilter" onchange="Director.renderArtifacts()">
                    <option value="">All Phases</option>
                    <option value="0">Phase 0 — Direction</option>
                    <option value="1">Phase 1 — Product</option>
                    <option value="2">Phase 2 — Architecture</option>
                    <option value="3">Phase 3 — Delivery</option>
                    <option value="4">Phase 4 — QA & Ops</option>
                </select>
                <select class="director-status-select" id="artifactStatusFilter" onchange="Director.renderArtifacts()">
                    <option value="">All Statuses</option>
                    <option value="missing">Missing</option>
                    <option value="draft">Draft</option>
                    <option value="review">Review</option>
                    <option value="approved">Approved</option>
                    <option value="outdated">Outdated</option>
                </select>
            </div>
        </div>
        <div style="background:var(--bg-surface);border:1px solid var(--border-medium);border-radius:10px;overflow:hidden">
            <table class="director-table" id="artifactTable">
                <thead><tr><th style="width:50px">#</th><th style="width:90px">Phase</th><th>Artifact</th><th style="width:260px">Path</th><th style="width:90px">Status</th><th style="width:60px">Ver</th><th style="width:140px">Actions</th></tr></thead>
                <tbody id="artifactTableBody"></tbody>
            </table>
        </div>
    </div>

    <!-- ── BUILD QUEUE PANEL ──────────────────── -->
    <div class="director-panel" id="panel-queue">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
            <div class="director-section-title" style="margin-bottom:0"><i class="fas fa-tasks"></i> Build Queue</div>
            <button class="director-btn primary" onclick="Director.showCreateTask()"><i class="fas fa-plus"></i> New Task</button>
        </div>
        <div class="director-kanban" id="kanbanBoard">
            <div class="director-kanban-col"><div class="director-kanban-header">Todo <span class="count" id="kanbanTodoCount">0</span></div><div class="director-kanban-body" id="kanbanTodo"></div></div>
            <div class="director-kanban-col"><div class="director-kanban-header">Doing <span class="count" id="kanbanDoingCount">0</span></div><div class="director-kanban-body" id="kanbanDoing"></div></div>
            <div class="director-kanban-col"><div class="director-kanban-header">Blocked <span class="count" id="kanbanBlockedCount">0</span></div><div class="director-kanban-body" id="kanbanBlocked"></div></div>
            <div class="director-kanban-col"><div class="director-kanban-header">Done <span class="count" id="kanbanDoneCount">0</span></div><div class="director-kanban-body" id="kanbanDone"></div></div>
        </div>
    </div>

    <!-- ── DECISIONS PANEL ────────────────────── -->
    <div class="director-panel" id="panel-decisions">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
            <div class="director-section-title" style="margin-bottom:0"><i class="fas fa-gavel"></i> Architecture Decision Records</div>
            <button class="director-btn primary" onclick="Director.showCreateDecision()"><i class="fas fa-plus"></i> New ADR</button>
        </div>
        <div style="background:var(--bg-surface);border:1px solid var(--border-medium);border-radius:10px;overflow:hidden">
            <table class="director-table" id="decisionTable"><thead><tr><th style="width:70px">ADR #</th><th>Title</th><th style="width:90px">Status</th><th style="width:80px">Impact</th><th style="width:140px">Date</th><th style="width:100px">Actions</th></tr></thead><tbody id="decisionTableBody"></tbody></table>
        </div>
    </div>

    <!-- ── RELEASES PANEL ─────────────────────── -->
    <div class="director-panel" id="panel-releases">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
            <div class="director-section-title" style="margin-bottom:0"><i class="fas fa-rocket"></i> Release Control</div>
            <button class="director-btn primary" onclick="Director.showCreateRelease()"><i class="fas fa-plus"></i> New Release</button>
        </div>
        <div style="background:var(--bg-surface);border:1px solid var(--border-medium);border-radius:10px;overflow:hidden">
            <table class="director-table" id="releaseTable"><thead><tr><th style="width:100px">Version</th><th>Scope</th><th style="width:100px">Gate</th><th style="width:120px">Rollback Ref</th><th style="width:140px">Released</th><th style="width:100px">Actions</th></tr></thead><tbody id="releaseTableBody"></tbody></table>
        </div>
    </div>

    <!-- ── AUDIT TRAIL PANEL ──────────────────── -->
    <div class="director-panel" id="panel-audit">
        <div class="director-section-title"><i class="fas fa-history"></i> Full Audit Trail</div>
        <div style="background:var(--bg-surface);border:1px solid var(--border-medium);border-radius:10px;overflow:hidden;max-height:600px;overflow-y:auto">
            <table class="director-table" id="auditTable"><thead><tr><th style="width:140px">Timestamp</th><th style="width:80px">User</th><th style="width:80px">Entity</th><th style="width:60px">ID</th><th style="width:100px">Action</th><th>Details</th></tr></thead><tbody id="auditTableBody"></tbody></table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/director/modals.php'; ?>

<script src="assets/js/pages/director-core.js"></script>
<script src="assets/js/pages/director-crud.js"></script>

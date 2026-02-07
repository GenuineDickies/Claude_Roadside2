<?php
/**
 * Production Director — Control Tower
 * Self-managing documentation & delivery pipeline dashboard
 * Tables auto-bootstrap on first API call.
 */
?>

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

<style>
/* ── Director Scoped Styles ─────────────────────────────────────── */
:root {
    --bg-primary: #0C0E12;
    --bg-secondary: #12151B;
    --bg-surface: #181C24;
    --bg-surface-hover: #1E2330;
    --border-subtle: rgba(255,255,255,0.06);
    --border-medium: rgba(255,255,255,0.10);
    --border-strong: rgba(255,255,255,0.16);
    --text-primary: #E8ECF4;
    --text-secondary: #8A92A6;
    --text-tertiary: #5C6478;
    --navy-500: #2B5EA7;
    --navy-400: #3B7DD8;
    --navy-300: #5A9AE6;
    --amber-500: #F59E0B;
    --green-500: #22C55E;
    --red-500: #EF4444;
    --blue-500: #3B82F6;
    --purple-500: #A855F7;
}

.director-header {
    background: linear-gradient(135deg, var(--bg-surface) 0%, var(--bg-secondary) 100%);
    border-bottom: 2px solid var(--navy-500);
    padding: 24px 28px;
    margin: -1rem -1rem 0 -1rem;
}

.director-tab-nav {
    display: flex;
    background: var(--bg-secondary);
    border-bottom: 1px solid var(--border-subtle);
    margin: 0 -1rem;
    padding: 0 12px;
    overflow-x: auto;
    gap: 2px;
}

.director-tab-btn {
    background: none;
    border: none;
    color: var(--text-secondary);
    padding: 13px 18px;
    font-size: 13px;
    font-weight: 600;
    font-family: 'DM Sans', sans-serif;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all 0.15s ease;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 7px;
}
.director-tab-btn:hover { color: var(--text-primary); background: var(--bg-surface-hover); }
.director-tab-btn.active { color: var(--navy-300); border-bottom-color: var(--navy-500); background: var(--bg-surface); }
.director-tab-btn .tab-count {
    background: rgba(255,255,255,0.08);
    color: var(--text-tertiary);
    font-size: 11px;
    padding: 1px 7px;
    border-radius: 10px;
    font-family: 'JetBrains Mono', monospace;
}
.director-tab-btn.active .tab-count { background: rgba(43,94,167,0.2); color: var(--navy-300); }

.director-body {
    padding: 24px 0;
    margin: 0;
}

.director-panel { display: none; }
.director-panel.active { display: block; }

/* Stat cards row */
.director-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.director-stat {
    background: var(--bg-surface);
    border: 1px solid var(--border-medium);
    border-radius: 10px;
    padding: 20px;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.director-stat:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.3); }
.director-stat-label {
    font-size: 11px; font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.8px; color: var(--text-tertiary); margin-bottom: 6px;
}
.director-stat-value {
    font-size: 32px; font-weight: 700; color: var(--text-primary);
    font-family: 'JetBrains Mono', monospace; line-height: 1;
}
.director-stat-sub {
    font-size: 12px; color: var(--text-secondary); margin-top: 4px;
}
.director-stat.navy    { border-left: 4px solid var(--navy-500); }
.director-stat.amber   { border-left: 4px solid var(--amber-500); }
.director-stat.green   { border-left: 4px solid var(--green-500); }
.director-stat.blue    { border-left: 4px solid var(--blue-500); }
.director-stat.red     { border-left: 4px solid var(--red-500); }
.director-stat.purple  { border-left: 4px solid var(--purple-500); }

/* Phase progress bars */
.director-phase-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-medium);
    border-radius: 10px;
    padding: 18px 20px;
    margin-bottom: 12px;
}
.director-phase-title {
    font-size: 14px; font-weight: 600; color: var(--text-primary);
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 10px;
}
.director-phase-bar {
    height: 6px; background: rgba(255,255,255,0.06); border-radius: 3px; overflow: hidden;
}
.director-phase-fill {
    height: 100%; border-radius: 3px; transition: width 0.4s ease;
}

/* Artifact table */
.director-table {
    width: 100%; border-collapse: separate; border-spacing: 0;
}
.director-table thead th {
    background: var(--bg-secondary);
    font-size: 11px; font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.5px; color: var(--text-tertiary);
    padding: 10px 14px; border-bottom: 1px solid var(--border-subtle);
    position: sticky; top: 0; z-index: 1;
    font-family: 'DM Sans', sans-serif;
}
.director-table tbody td {
    padding: 12px 14px; border-bottom: 1px solid var(--border-subtle);
    font-size: 13px; color: var(--text-primary); vertical-align: middle;
}
.director-table tbody tr:hover { background: var(--bg-surface-hover); }
.director-table .mono {
    font-family: 'JetBrains Mono', monospace; font-size: 12px; color: var(--text-tertiary);
}

/* Badges */
.director-badge {
    display: inline-block; padding: 3px 10px; border-radius: 4px;
    font-size: 11px; font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.3px;
}
.director-badge.missing   { background: rgba(239,68,68,0.12); color: #EF4444; border: 1px solid rgba(239,68,68,0.3); }
.director-badge.draft     { background: rgba(245,158,11,0.12); color: #FBBF24; border: 1px solid rgba(245,158,11,0.3); }
.director-badge.review    { background: rgba(59,130,246,0.12); color: #3B82F6; border: 1px solid rgba(59,130,246,0.3); }
.director-badge.approved  { background: rgba(34,197,94,0.12); color: #22C55E; border: 1px solid rgba(34,197,94,0.3); }
.director-badge.outdated  { background: rgba(168,85,247,0.12); color: #A855F7; border: 1px solid rgba(168,85,247,0.3); }

.director-badge.todo      { background: rgba(255,255,255,0.06); color: var(--text-secondary); border: 1px solid var(--border-medium); }
.director-badge.doing     { background: rgba(59,130,246,0.12); color: #3B82F6; border: 1px solid rgba(59,130,246,0.3); }
.director-badge.blocked   { background: rgba(239,68,68,0.12); color: #EF4444; border: 1px solid rgba(239,68,68,0.3); }
.director-badge.done      { background: rgba(34,197,94,0.12); color: #22C55E; border: 1px solid rgba(34,197,94,0.3); }
.director-badge.cancelled { background: rgba(255,255,255,0.06); color: var(--text-tertiary); border: 1px solid var(--border-subtle); text-decoration: line-through; }

.director-badge.proposed  { background: rgba(245,158,11,0.12); color: #FBBF24; border: 1px solid rgba(245,158,11,0.3); }
.director-badge.accepted  { background: rgba(34,197,94,0.12); color: #22C55E; border: 1px solid rgba(34,197,94,0.3); }
.director-badge.deprecated { background: rgba(255,255,255,0.06); color: var(--text-tertiary); border: 1px solid var(--border-subtle); }
.director-badge.superseded { background: rgba(168,85,247,0.12); color: #A855F7; border: 1px solid rgba(168,85,247,0.3); }

.director-badge.pending   { background: rgba(245,158,11,0.12); color: #FBBF24; border: 1px solid rgba(245,158,11,0.3); }
.director-badge.passing   { background: rgba(34,197,94,0.12); color: #22C55E; border: 1px solid rgba(34,197,94,0.3); }
.director-badge.failing   { background: rgba(239,68,68,0.12); color: #EF4444; border: 1px solid rgba(239,68,68,0.3); }
.director-badge.released  { background: rgba(59,130,246,0.12); color: #3B82F6; border: 1px solid rgba(59,130,246,0.3); }

.director-badge.critical  { background: rgba(239,68,68,0.12); color: #EF4444; border: 1px solid rgba(239,68,68,0.3); }
.director-badge.high      { background: rgba(245,158,11,0.12); color: #FBBF24; border: 1px solid rgba(245,158,11,0.3); }
.director-badge.medium    { background: rgba(59,130,246,0.12); color: #3B82F6; border: 1px solid rgba(59,130,246,0.3); }
.director-badge.low       { background: rgba(255,255,255,0.06); color: var(--text-secondary); border: 1px solid var(--border-medium); }

/* Buttons */
.director-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 14px; border-radius: 6px; font-size: 13px; font-weight: 600;
    border: none; cursor: pointer; transition: all 0.15s ease;
    font-family: 'DM Sans', sans-serif;
}
.director-btn.primary {
    background: linear-gradient(135deg, #234B78, #2B5EA7); color: #fff;
}
.director-btn.primary:hover { background: linear-gradient(135deg, #2B5EA7, #3B7DD8); transform: translateY(-1px); }
.director-btn.secondary {
    background: var(--bg-surface); color: var(--text-secondary); border: 1px solid var(--border-medium);
}
.director-btn.secondary:hover { color: var(--text-primary); border-color: var(--border-strong); }
.director-btn.sm { padding: 4px 10px; font-size: 12px; }
.director-btn.danger { background: rgba(239,68,68,0.15); color: #EF4444; border: 1px solid rgba(239,68,68,0.3); }
.director-btn.danger:hover { background: rgba(239,68,68,0.25); }

/* Action dropdown for status changes */
.director-status-select {
    background: var(--bg-surface);
    color: var(--text-primary);
    border: 1px solid var(--border-medium);
    border-radius: 4px;
    padding: 4px 8px;
    font-size: 12px;
    font-family: 'DM Sans', sans-serif;
    cursor: pointer;
}
.director-status-select:focus { outline: none; border-color: var(--navy-400); }

/* Build Queue kanban-style columns */
.director-kanban {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    min-height: 300px;
}
.director-kanban-col {
    background: var(--bg-secondary);
    border: 1px solid var(--border-subtle);
    border-radius: 10px;
    padding: 0;
    display: flex;
    flex-direction: column;
}
.director-kanban-header {
    padding: 12px 14px;
    font-size: 12px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.5px; color: var(--text-secondary);
    border-bottom: 1px solid var(--border-subtle);
    display: flex; justify-content: space-between; align-items: center;
}
.director-kanban-header .count {
    font-family: 'JetBrains Mono', monospace;
    font-size: 11px;
    background: rgba(255,255,255,0.06);
    padding: 2px 8px;
    border-radius: 10px;
}
.director-kanban-body {
    padding: 10px;
    flex: 1;
    overflow-y: auto;
    max-height: 500px;
}
.director-task-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-medium);
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 8px;
    cursor: default;
    transition: border-color 0.15s ease;
}
.director-task-card:hover { border-color: var(--border-strong); }
.director-task-card .task-title {
    font-size: 13px; font-weight: 600; color: var(--text-primary); margin-bottom: 6px;
}
.director-task-card .task-meta {
    display: flex; justify-content: space-between; align-items: center;
    font-size: 11px; color: var(--text-tertiary);
}

/* Activity feed */
.director-activity {
    list-style: none; padding: 0; margin: 0;
}
.director-activity li {
    padding: 10px 14px;
    border-bottom: 1px solid var(--border-subtle);
    display: flex; gap: 12px; align-items: flex-start;
    font-size: 13px;
}
.director-activity li:hover { background: var(--bg-surface-hover); }
.director-activity .act-icon {
    width: 28px; height: 28px; border-radius: 6px;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; flex-shrink: 0;
}
.director-activity .act-icon.artifact { background: rgba(43,94,167,0.15); color: var(--navy-300); }
.director-activity .act-icon.task     { background: rgba(59,130,246,0.15); color: #3B82F6; }
.director-activity .act-icon.decision { background: rgba(168,85,247,0.15); color: #A855F7; }
.director-activity .act-icon.release  { background: rgba(34,197,94,0.15); color: #22C55E; }
.director-activity .act-icon.gate     { background: rgba(245,158,11,0.15); color: #FBBF24; }
.director-activity .act-time {
    font-size: 11px; color: var(--text-tertiary); font-family: 'JetBrains Mono', monospace;
    white-space: nowrap;
}
.director-activity .act-text { flex: 1; color: var(--text-secondary); }
.director-activity .act-text strong { color: var(--text-primary); font-weight: 600; }

/* Section headings */
.director-section-title {
    font-size: 16px; font-weight: 700; color: var(--text-primary);
    margin-bottom: 16px; display: flex; align-items: center; gap: 10px;
}
.director-section-title i { color: var(--navy-300); font-size: 16px; }

/* Phase labels */
.director-phase-label {
    font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.6px; padding: 2px 8px; border-radius: 4px;
}
.director-phase-label.p0 { background: rgba(239,68,68,0.12); color: #EF4444; }
.director-phase-label.p1 { background: rgba(245,158,11,0.12); color: #FBBF24; }
.director-phase-label.p2 { background: rgba(59,130,246,0.12); color: #3B82F6; }
.director-phase-label.p3 { background: rgba(34,197,94,0.12); color: #22C55E; }
.director-phase-label.p4 { background: rgba(168,85,247,0.12); color: #A855F7; }

/* Modal overrides for dark theme */
.director-modal .modal-content {
    background: var(--bg-surface);
    border: 1px solid var(--border-medium);
    border-radius: 14px;
    color: var(--text-primary);
}
.director-modal .modal-header {
    border-bottom: 1px solid var(--border-subtle);
    padding: 18px 20px;
}
.director-modal .modal-header .modal-title {
    font-size: 16px; font-weight: 700; color: var(--text-primary);
}
.director-modal .modal-header .btn-close { filter: invert(1); opacity: 0.5; }
.director-modal .modal-body { padding: 20px; }
.director-modal .modal-footer { border-top: 1px solid var(--border-subtle); padding: 14px 20px; }
.director-modal label {
    font-size: 12px; font-weight: 600; color: var(--text-secondary);
    text-transform: uppercase; letter-spacing: 0.3px; margin-bottom: 4px;
}
.director-modal .form-control, .director-modal .form-select {
    background: var(--bg-secondary);
    border: 1px solid var(--border-medium);
    color: var(--text-primary);
    border-radius: 6px;
    font-size: 13px;
}
.director-modal .form-control:focus, .director-modal .form-select:focus {
    border-color: var(--navy-400);
    box-shadow: 0 0 0 2px rgba(43,94,167,0.25);
    background: var(--bg-secondary);
    color: var(--text-primary);
}
.director-modal textarea.form-control { min-height: 80px; }

/* Responsive */
@media (max-width: 992px) {
    .director-kanban { grid-template-columns: repeat(2, 1fr); }
    .director-stats { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 576px) {
    .director-kanban { grid-template-columns: 1fr; }
    .director-stats { grid-template-columns: 1fr; }
}

/* Empty state */
.director-empty {
    text-align: center; padding: 40px 20px;
    color: var(--text-tertiary); font-size: 14px;
}
.director-empty i { font-size: 36px; margin-bottom: 12px; display: block; opacity: 0.3; }
</style>

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
        <div class="director-stats" id="overviewStats">
            <!-- Filled by JS -->
        </div>

        <div class="row">
            <div class="col-lg-7">
                <div class="director-section-title"><i class="fas fa-layer-group"></i> Phase Progress</div>
                <div id="phaseProgress"><!-- Filled by JS --></div>
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
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th style="width:90px">Phase</th>
                        <th>Artifact</th>
                        <th style="width:260px">Path</th>
                        <th style="width:90px">Status</th>
                        <th style="width:60px">Ver</th>
                        <th style="width:140px">Actions</th>
                    </tr>
                </thead>
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
            <div class="director-kanban-col">
                <div class="director-kanban-header">Todo <span class="count" id="kanbanTodoCount">0</span></div>
                <div class="director-kanban-body" id="kanbanTodo"></div>
            </div>
            <div class="director-kanban-col">
                <div class="director-kanban-header">Doing <span class="count" id="kanbanDoingCount">0</span></div>
                <div class="director-kanban-body" id="kanbanDoing"></div>
            </div>
            <div class="director-kanban-col">
                <div class="director-kanban-header">Blocked <span class="count" id="kanbanBlockedCount">0</span></div>
                <div class="director-kanban-body" id="kanbanBlocked"></div>
            </div>
            <div class="director-kanban-col">
                <div class="director-kanban-header">Done <span class="count" id="kanbanDoneCount">0</span></div>
                <div class="director-kanban-body" id="kanbanDone"></div>
            </div>
        </div>
    </div>

    <!-- ── DECISIONS PANEL ────────────────────── -->
    <div class="director-panel" id="panel-decisions">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
            <div class="director-section-title" style="margin-bottom:0"><i class="fas fa-gavel"></i> Architecture Decision Records</div>
            <button class="director-btn primary" onclick="Director.showCreateDecision()"><i class="fas fa-plus"></i> New ADR</button>
        </div>
        <div style="background:var(--bg-surface);border:1px solid var(--border-medium);border-radius:10px;overflow:hidden">
            <table class="director-table" id="decisionTable">
                <thead>
                    <tr>
                        <th style="width:70px">ADR #</th>
                        <th>Title</th>
                        <th style="width:90px">Status</th>
                        <th style="width:80px">Impact</th>
                        <th style="width:140px">Date</th>
                        <th style="width:100px">Actions</th>
                    </tr>
                </thead>
                <tbody id="decisionTableBody"></tbody>
            </table>
        </div>
    </div>

    <!-- ── RELEASES PANEL ─────────────────────── -->
    <div class="director-panel" id="panel-releases">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
            <div class="director-section-title" style="margin-bottom:0"><i class="fas fa-rocket"></i> Release Control</div>
            <button class="director-btn primary" onclick="Director.showCreateRelease()"><i class="fas fa-plus"></i> New Release</button>
        </div>
        <div style="background:var(--bg-surface);border:1px solid var(--border-medium);border-radius:10px;overflow:hidden">
            <table class="director-table" id="releaseTable">
                <thead>
                    <tr>
                        <th style="width:100px">Version</th>
                        <th>Scope</th>
                        <th style="width:100px">Gate</th>
                        <th style="width:120px">Rollback Ref</th>
                        <th style="width:140px">Released</th>
                        <th style="width:100px">Actions</th>
                    </tr>
                </thead>
                <tbody id="releaseTableBody"></tbody>
            </table>
        </div>
    </div>

    <!-- ── AUDIT TRAIL PANEL ──────────────────── -->
    <div class="director-panel" id="panel-audit">
        <div class="director-section-title"><i class="fas fa-history"></i> Full Audit Trail</div>
        <div style="background:var(--bg-surface);border:1px solid var(--border-medium);border-radius:10px;overflow:hidden;max-height:600px;overflow-y:auto">
            <table class="director-table" id="auditTable">
                <thead>
                    <tr>
                        <th style="width:140px">Timestamp</th>
                        <th style="width:80px">User</th>
                        <th style="width:80px">Entity</th>
                        <th style="width:60px">ID</th>
                        <th style="width:100px">Action</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody id="auditTableBody"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- ═══════════════ MODALS ═══════════════ -->

<!-- Create Task Modal -->
<div class="modal fade director-modal" id="createTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus" style="color:var(--navy-300);margin-right:8px"></i> New Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label>Title</label>
                    <input type="text" class="form-control" id="taskTitle" placeholder="Task title...">
                </div>
                <div class="mb-3">
                    <label>Description</label>
                    <textarea class="form-control" id="taskDescription" placeholder="Details..."></textarea>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label>Priority</label>
                        <select class="form-select" id="taskPriority">
                            <option value="medium">Medium</option>
                            <option value="low">Low</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                    <div class="col-6 mb-3">
                        <label>Linked Artifact</label>
                        <select class="form-select" id="taskArtifact">
                            <option value="">None</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label>Assignee</label>
                        <input type="text" class="form-control" id="taskAssignee" placeholder="Who?">
                    </div>
                    <div class="col-6 mb-3">
                        <label>Due Date</label>
                        <input type="date" class="form-control" id="taskDueDate">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="director-btn secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="director-btn primary" onclick="Director.createTask()"><i class="fas fa-check"></i> Create</button>
            </div>
        </div>
    </div>
</div>

<!-- Create Decision Modal -->
<div class="modal fade director-modal" id="createDecisionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-gavel" style="color:var(--navy-300);margin-right:8px"></i> New Architecture Decision Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label>Title</label>
                    <input type="text" class="form-control" id="decisionTitle" placeholder="ADR title...">
                </div>
                <div class="mb-3">
                    <label>Context</label>
                    <textarea class="form-control" id="decisionContext" placeholder="What is the issue?"></textarea>
                </div>
                <div class="mb-3">
                    <label>Decision</label>
                    <textarea class="form-control" id="decisionDecision" placeholder="What was decided?"></textarea>
                </div>
                <div class="mb-3">
                    <label>Consequences</label>
                    <textarea class="form-control" id="decisionConsequences" placeholder="What are the outcomes?"></textarea>
                </div>
                <div class="mb-3">
                    <label>Impact</label>
                    <select class="form-select" id="decisionImpact">
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                        <option value="high">High</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="director-btn secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="director-btn primary" onclick="Director.createDecision()"><i class="fas fa-check"></i> Create ADR</button>
            </div>
        </div>
    </div>
</div>

<!-- Create Release Modal -->
<div class="modal fade director-modal" id="createReleaseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-rocket" style="color:var(--navy-300);margin-right:8px"></i> New Release</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label>Version</label>
                    <input type="text" class="form-control" id="releaseVersion" placeholder="e.g. 1.0.0">
                </div>
                <div class="mb-3">
                    <label>Scope</label>
                    <textarea class="form-control" id="releaseScope" placeholder="What's included?"></textarea>
                </div>
                <div class="mb-3">
                    <label>Rollback Reference</label>
                    <input type="text" class="form-control" id="releaseRollback" placeholder="e.g. commit hash, tag">
                </div>
                <div class="mb-3">
                    <label>Notes</label>
                    <textarea class="form-control" id="releaseNotes" placeholder="Release notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="director-btn secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="director-btn primary" onclick="Director.createRelease()"><i class="fas fa-check"></i> Create Release</button>
            </div>
        </div>
    </div>
</div>

<!-- View/Edit Artifact Content Modal -->
<div class="modal fade director-modal" id="artifactContentModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="artifactContentTitle">Artifact</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div style="margin-bottom:10px;display:flex;justify-content:space-between;align-items:center">
                    <span class="mono" id="artifactContentPath" style="font-size:12px;color:var(--text-tertiary)"></span>
                    <span class="director-badge" id="artifactContentStatus"></span>
                </div>
                <textarea class="form-control" id="artifactContentEditor" style="min-height:400px;font-family:'JetBrains Mono',monospace;font-size:13px;line-height:1.6"></textarea>
                <input type="hidden" id="artifactContentId">
            </div>
            <div class="modal-footer">
                <button type="button" class="director-btn secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="director-btn primary" onclick="Director.saveArtifactContent()"><i class="fas fa-save"></i> Save</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════ JAVASCRIPT ═══════════════ -->
<script>
const Director = {
    API: 'api/director.php',
    data: { artifacts: [], tasks: [], decisions: [], releases: [], stats: null },

    // ── Init ─────────────────────────────
    async init() {
        this.bindTabs();
        await this.refresh();
    },

    bindTabs() {
        document.querySelectorAll('.director-tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.director-tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.director-panel').forEach(p => p.classList.remove('active'));
                btn.classList.add('active');
                document.getElementById('panel-' + btn.dataset.tab).classList.add('active');
            });
        });
    },

    async refresh() {
        await Promise.all([
            this.loadStats(),
            this.loadArtifacts(),
            this.loadTasks(),
            this.loadDecisions(),
            this.loadReleases(),
            this.loadAuditLog()
        ]);
    },

    async api(action, params = {}, method = 'GET') {
        try {
            if (method === 'GET') {
                const qs = new URLSearchParams({ action, ...params }).toString();
                const res = await fetch(`${this.API}?${qs}`);
                return await res.json();
            } else {
                const form = new FormData();
                form.append('action', action);
                for (const [k, v] of Object.entries(params)) form.append(k, v);
                const res = await fetch(this.API, { method: 'POST', body: form });
                return await res.json();
            }
        } catch (e) {
            console.error('Director API error:', e);
            return { success: false, error: e.message };
        }
    },

    // ── Stats / Overview ─────────────────
    async loadStats() {
        const res = await this.api('get_stats');
        if (!res.success) return;
        this.data.stats = res.data;
        this.renderOverview(res.data);
    },

    renderOverview(s) {
        const statusMap = {};
        (s.artifacts || []).forEach(r => statusMap[r.status] = parseInt(r.count));
        const taskMap = {};
        (s.tasks || []).forEach(r => taskMap[r.state] = parseInt(r.count));

        const pct = s.artifact_total > 0 ? Math.round((s.artifacts_approved / s.artifact_total) * 100) : 0;
        const taskPct = s.task_total > 0 ? Math.round((s.tasks_done / s.task_total) * 100) : 0;

        document.getElementById('overviewStats').innerHTML = `
            <div class="director-stat navy">
                <div class="director-stat-label">Total Artifacts</div>
                <div class="director-stat-value">${s.artifact_total}</div>
                <div class="director-stat-sub">${pct}% approved</div>
            </div>
            <div class="director-stat amber">
                <div class="director-stat-label">Missing</div>
                <div class="director-stat-value">${statusMap.missing || 0}</div>
                <div class="director-stat-sub">Need creation</div>
            </div>
            <div class="director-stat green">
                <div class="director-stat-label">Approved</div>
                <div class="director-stat-value">${statusMap.approved || 0}</div>
                <div class="director-stat-sub">Ready for use</div>
            </div>
            <div class="director-stat blue">
                <div class="director-stat-label">Tasks Active</div>
                <div class="director-stat-value">${(taskMap.todo || 0) + (taskMap.doing || 0)}</div>
                <div class="director-stat-sub">${taskPct}% done</div>
            </div>
            <div class="director-stat purple">
                <div class="director-stat-label">Decisions</div>
                <div class="director-stat-value">${s.decision_count}</div>
                <div class="director-stat-sub">ADR records</div>
            </div>
            <div class="director-stat red">
                <div class="director-stat-label">Releases</div>
                <div class="director-stat-value">${s.release_count}</div>
                <div class="director-stat-sub">Version history</div>
            </div>
        `;

        // Phase progress bars
        const phaseNames = ['Direction Lock', 'Product Truth', 'Technical Blueprint', 'Delivery Control', 'QA & Ops'];
        const phaseColors = ['#EF4444', '#FBBF24', '#3B82F6', '#22C55E', '#A855F7'];
        let phaseHtml = '';
        (s.phases || []).forEach(p => {
            const total = parseInt(p.total);
            const approved = parseInt(p.approved);
            const missing = parseInt(p.missing);
            const phasePct = total > 0 ? Math.round((approved / total) * 100) : 0;
            phaseHtml += `
                <div class="director-phase-card">
                    <div class="director-phase-title">
                        <span><span class="director-phase-label p${p.phase}">Phase ${p.phase}</span> ${phaseNames[p.phase] || 'Unknown'}</span>
                        <span style="font-family:'JetBrains Mono',monospace;font-size:13px;color:var(--text-secondary)">${approved}/${total} <span style="color:var(--text-tertiary)">(${phasePct}%)</span></span>
                    </div>
                    <div class="director-phase-bar">
                        <div class="director-phase-fill" style="width:${phasePct}%;background:${phaseColors[p.phase] || '#3B82F6'}"></div>
                    </div>
                </div>
            `;
        });
        document.getElementById('phaseProgress').innerHTML = phaseHtml || '<div class="director-empty"><i class="fas fa-spinner"></i>Loading phases...</div>';

        // Activity feed
        const feed = document.getElementById('activityFeed');
        if (s.recent_activity && s.recent_activity.length > 0) {
            feed.innerHTML = s.recent_activity.map(a => {
                const iconClass = a.entity_type === 'artifact' ? 'artifact' : a.entity_type === 'task' ? 'task' :
                    a.entity_type === 'decision' ? 'decision' : a.entity_type === 'release' ? 'release' : 'gate';
                const iconMap = { artifact: 'fa-archive', task: 'fa-tasks', decision: 'fa-gavel', release: 'fa-rocket', gate: 'fa-shield-alt' };
                return `<li>
                    <div class="act-icon ${iconClass}"><i class="fas ${iconMap[a.entity_type] || 'fa-circle'}"></i></div>
                    <div class="act-text"><strong>${a.username || 'system'}</strong> ${a.action} ${a.entity_type} #${a.entity_id}${a.details ? ' — ' + this.esc(a.details) : ''}</div>
                    <div class="act-time">${this.timeAgo(a.created_at)}</div>
                </li>`;
            }).join('');
        } else {
            feed.innerHTML = '<li class="director-empty"><i class="fas fa-ghost"></i>No activity yet. Start by updating artifact statuses.</li>';
        }
    },

    // ── Artifacts ────────────────────────
    async loadArtifacts() {
        const res = await this.api('get_artifacts');
        if (!res.success) return;
        this.data.artifacts = res.data;
        document.getElementById('tabArtifactCount').textContent = res.data.length;
        this.renderArtifacts();
        this.populateArtifactSelect();
    },

    renderArtifacts() {
        const phaseFilter = document.getElementById('artifactPhaseFilter').value;
        const statusFilter = document.getElementById('artifactStatusFilter').value;
        let filtered = this.data.artifacts;
        if (phaseFilter !== '') filtered = filtered.filter(a => a.phase == phaseFilter);
        if (statusFilter) filtered = filtered.filter(a => a.status === statusFilter);

        const phaseNames = { 0: 'Direction', 1: 'Product', 2: 'Architecture', 3: 'Delivery', 4: 'QA & Ops' };
        const tbody = document.getElementById('artifactTableBody');
        if (filtered.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="director-empty"><i class="fas fa-filter"></i>No artifacts match filters</td></tr>';
            return;
        }
        tbody.innerHTML = filtered.map(a => `
            <tr>
                <td class="mono">${a.id}</td>
                <td><span class="director-phase-label p${a.phase}">${phaseNames[a.phase] || ''}</span></td>
                <td style="font-weight:600">${this.esc(a.title)}</td>
                <td class="mono" style="font-size:11px">${this.esc(a.path)}</td>
                <td><span class="director-badge ${a.status}">${a.status}</span></td>
                <td class="mono" style="text-align:center">v${a.version}</td>
                <td>
                    <select class="director-status-select" onchange="Director.updateArtifactStatus(${a.id}, this.value)" style="width:90px">
                        ${['missing','draft','review','approved','outdated'].map(s =>
                            `<option value="${s}" ${s === a.status ? 'selected' : ''}>${s}</option>`
                        ).join('')}
                    </select>
                    <button class="director-btn sm secondary" onclick="Director.editArtifact(${a.id})" title="Edit content"><i class="fas fa-edit"></i></button>
                </td>
            </tr>
        `).join('');
    },

    async updateArtifactStatus(id, status) {
        await this.api('update_artifact_status', { id, status }, 'POST');
        await this.refresh();
    },

    editArtifact(id) {
        const a = this.data.artifacts.find(x => x.id == id);
        if (!a) return;
        document.getElementById('artifactContentTitle').textContent = a.title;
        document.getElementById('artifactContentPath').textContent = a.path;
        document.getElementById('artifactContentStatus').className = 'director-badge ' + a.status;
        document.getElementById('artifactContentStatus').textContent = a.status;
        document.getElementById('artifactContentEditor').value = a.content || '';
        document.getElementById('artifactContentId').value = a.id;
        new bootstrap.Modal(document.getElementById('artifactContentModal')).show();
    },

    async saveArtifactContent() {
        const id = document.getElementById('artifactContentId').value;
        const content = document.getElementById('artifactContentEditor').value;
        await this.api('save_artifact_content', { id, content }, 'POST');
        bootstrap.Modal.getInstance(document.getElementById('artifactContentModal')).hide();
        await this.refresh();
    },

    populateArtifactSelect() {
        const sel = document.getElementById('taskArtifact');
        sel.innerHTML = '<option value="">None</option>' + this.data.artifacts.map(a =>
            `<option value="${a.id}">${this.esc(a.title)}</option>`
        ).join('');
    },

    // ── Tasks / Build Queue ─────────────
    async loadTasks() {
        const res = await this.api('get_tasks');
        if (!res.success) return;
        this.data.tasks = res.data;
        document.getElementById('tabTaskCount').textContent = res.data.length;
        this.renderKanban();
    },

    renderKanban() {
        const cols = { todo: [], doing: [], blocked: [], done: [] };
        this.data.tasks.forEach(t => {
            if (cols[t.state]) cols[t.state].push(t);
        });

        for (const [state, tasks] of Object.entries(cols)) {
            const container = document.getElementById('kanban' + state.charAt(0).toUpperCase() + state.slice(1));
            const counter = document.getElementById('kanban' + state.charAt(0).toUpperCase() + state.slice(1) + 'Count');
            counter.textContent = tasks.length;
            if (tasks.length === 0) {
                container.innerHTML = '<div class="director-empty" style="padding:20px"><i class="fas fa-inbox"></i>Empty</div>';
                continue;
            }
            container.innerHTML = tasks.map(t => `
                <div class="director-task-card">
                    <div class="task-title">${this.esc(t.title)}</div>
                    ${t.artifact_title ? `<div style="font-size:11px;color:var(--text-tertiary);margin-bottom:6px"><i class="fas fa-link" style="margin-right:4px"></i>${this.esc(t.artifact_title)}</div>` : ''}
                    <div class="task-meta">
                        <span class="director-badge ${t.priority}">${t.priority}</span>
                        <div style="display:flex;gap:4px">
                            ${state !== 'doing' ? `<button class="director-btn sm secondary" onclick="Director.moveTask(${t.id},'doing')" title="Start"><i class="fas fa-play"></i></button>` : ''}
                            ${state !== 'done' ? `<button class="director-btn sm secondary" onclick="Director.moveTask(${t.id},'done')" title="Done"><i class="fas fa-check"></i></button>` : ''}
                            ${state !== 'blocked' && state !== 'done' ? `<button class="director-btn sm secondary" onclick="Director.moveTask(${t.id},'blocked')" title="Block"><i class="fas fa-ban"></i></button>` : ''}
                            ${state === 'blocked' ? `<button class="director-btn sm secondary" onclick="Director.moveTask(${t.id},'todo')" title="Unblock"><i class="fas fa-undo"></i></button>` : ''}
                            <button class="director-btn sm danger" onclick="Director.deleteTask(${t.id})" title="Delete"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                    ${t.assignee ? `<div style="font-size:11px;color:var(--text-tertiary);margin-top:6px"><i class="fas fa-user" style="margin-right:4px"></i>${this.esc(t.assignee)}</div>` : ''}
                </div>
            `).join('');
        }
    },

    async moveTask(id, state) {
        await this.api('update_task_state', { id, state }, 'POST');
        await this.loadTasks();
        await this.loadStats();
    },

    async deleteTask(id) {
        if (!confirm('Delete this task?')) return;
        await this.api('delete_task', { id }, 'POST');
        await this.loadTasks();
        await this.loadStats();
    },

    showCreateTask() {
        document.getElementById('taskTitle').value = '';
        document.getElementById('taskDescription').value = '';
        document.getElementById('taskPriority').value = 'medium';
        document.getElementById('taskArtifact').value = '';
        document.getElementById('taskAssignee').value = '';
        document.getElementById('taskDueDate').value = '';
        new bootstrap.Modal(document.getElementById('createTaskModal')).show();
    },

    async createTask() {
        const title = document.getElementById('taskTitle').value.trim();
        if (!title) return alert('Title required');
        await this.api('create_task', {
            title,
            description: document.getElementById('taskDescription').value,
            priority: document.getElementById('taskPriority').value,
            artifact_id: document.getElementById('taskArtifact').value,
            assignee: document.getElementById('taskAssignee').value,
            due_date: document.getElementById('taskDueDate').value,
        }, 'POST');
        bootstrap.Modal.getInstance(document.getElementById('createTaskModal')).hide();
        await this.loadTasks();
        await this.loadStats();
    },

    // ── Decisions ────────────────────────
    async loadDecisions() {
        const res = await this.api('get_decisions');
        if (!res.success) return;
        this.data.decisions = res.data;
        document.getElementById('tabDecisionCount').textContent = res.data.length;
        this.renderDecisions();
    },

    renderDecisions() {
        const tbody = document.getElementById('decisionTableBody');
        if (this.data.decisions.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="director-empty"><i class="fas fa-gavel"></i>No decisions recorded yet</td></tr>';
            return;
        }
        tbody.innerHTML = this.data.decisions.map(d => `
            <tr>
                <td class="mono" style="font-weight:700">ADR-${String(d.adr_number).padStart(3, '0')}</td>
                <td style="font-weight:600">${this.esc(d.title)}</td>
                <td><span class="director-badge ${d.status}">${d.status}</span></td>
                <td><span class="director-badge ${d.impact}">${d.impact}</span></td>
                <td class="mono" style="font-size:11px">${this.formatDate(d.created_at)}</td>
                <td>
                    <select class="director-status-select" onchange="Director.updateDecisionStatus(${d.id}, this.value)" style="width:110px">
                        ${['proposed','accepted','deprecated','superseded'].map(s =>
                            `<option value="${s}" ${s === d.status ? 'selected' : ''}>${s}</option>`
                        ).join('')}
                    </select>
                </td>
            </tr>
        `).join('');
    },

    async updateDecisionStatus(id, status) {
        await this.api('update_decision_status', { id, status }, 'POST');
        await this.loadDecisions();
        await this.loadStats();
    },

    showCreateDecision() {
        document.getElementById('decisionTitle').value = '';
        document.getElementById('decisionContext').value = '';
        document.getElementById('decisionDecision').value = '';
        document.getElementById('decisionConsequences').value = '';
        document.getElementById('decisionImpact').value = 'medium';
        new bootstrap.Modal(document.getElementById('createDecisionModal')).show();
    },

    async createDecision() {
        const title = document.getElementById('decisionTitle').value.trim();
        if (!title) return alert('Title required');
        await this.api('create_decision', {
            title,
            context: document.getElementById('decisionContext').value,
            decision: document.getElementById('decisionDecision').value,
            consequences: document.getElementById('decisionConsequences').value,
            impact: document.getElementById('decisionImpact').value,
        }, 'POST');
        bootstrap.Modal.getInstance(document.getElementById('createDecisionModal')).hide();
        await this.loadDecisions();
        await this.loadStats();
    },

    // ── Releases ─────────────────────────
    async loadReleases() {
        const res = await this.api('get_releases');
        if (!res.success) return;
        this.data.releases = res.data;
        document.getElementById('tabReleaseCount').textContent = res.data.length;
        this.renderReleases();
    },

    renderReleases() {
        const tbody = document.getElementById('releaseTableBody');
        if (this.data.releases.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="director-empty"><i class="fas fa-rocket"></i>No releases yet</td></tr>';
            return;
        }
        tbody.innerHTML = this.data.releases.map(r => `
            <tr>
                <td class="mono" style="font-weight:700">v${this.esc(r.version)}</td>
                <td>${this.esc(r.scope || '—')}</td>
                <td><span class="director-badge ${r.gate_status}">${r.gate_status}</span></td>
                <td class="mono" style="font-size:11px">${this.esc(r.rollback_ref || '—')}</td>
                <td class="mono" style="font-size:11px">${r.released_at ? this.formatDate(r.released_at) : '—'}</td>
                <td>
                    <select class="director-status-select" onchange="Director.updateReleaseGate(${r.id}, this.value)" style="width:100px">
                        ${['pending','passing','failing','released'].map(s =>
                            `<option value="${s}" ${s === r.gate_status ? 'selected' : ''}>${s}</option>`
                        ).join('')}
                    </select>
                </td>
            </tr>
        `).join('');
    },

    async updateReleaseGate(id, status) {
        await this.api('update_release_gate', { id, gate_status: status }, 'POST');
        await this.loadReleases();
        await this.loadStats();
    },

    showCreateRelease() {
        document.getElementById('releaseVersion').value = '';
        document.getElementById('releaseScope').value = '';
        document.getElementById('releaseRollback').value = '';
        document.getElementById('releaseNotes').value = '';
        new bootstrap.Modal(document.getElementById('createReleaseModal')).show();
    },

    async createRelease() {
        const version = document.getElementById('releaseVersion').value.trim();
        if (!version) return alert('Version required');
        await this.api('create_release', {
            version,
            scope: document.getElementById('releaseScope').value,
            rollback_ref: document.getElementById('releaseRollback').value,
            notes: document.getElementById('releaseNotes').value,
        }, 'POST');
        bootstrap.Modal.getInstance(document.getElementById('createReleaseModal')).hide();
        await this.loadReleases();
        await this.loadStats();
    },

    // ── Audit Log ────────────────────────
    async loadAuditLog() {
        const res = await this.api('get_audit_log', { limit: 100 });
        if (!res.success) return;
        const tbody = document.getElementById('auditTableBody');
        if (res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="director-empty"><i class="fas fa-clipboard-list"></i>No audit entries</td></tr>';
            return;
        }
        tbody.innerHTML = res.data.map(a => `
            <tr>
                <td class="mono" style="font-size:11px">${this.formatDate(a.created_at)}</td>
                <td style="font-weight:600">${this.esc(a.username || 'system')}</td>
                <td><span class="director-badge ${a.entity_type === 'artifact' ? 'review' : a.entity_type === 'task' ? 'doing' : 'proposed'}">${a.entity_type}</span></td>
                <td class="mono">#${a.entity_id}</td>
                <td style="font-weight:600">${this.esc(a.action)}</td>
                <td style="color:var(--text-secondary);font-size:12px">${this.esc(a.details || '—')}</td>
            </tr>
        `).join('');
    },

    // ── Helpers ──────────────────────────
    esc(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    },

    formatDate(d) {
        if (!d) return '—';
        const dt = new Date(d);
        return dt.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) +
            ' ' + dt.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    },

    timeAgo(d) {
        if (!d) return '';
        const diff = Math.floor((Date.now() - new Date(d).getTime()) / 1000);
        if (diff < 60) return diff + 's ago';
        if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
        return Math.floor(diff / 86400) + 'd ago';
    }
};

// Boot
document.addEventListener('DOMContentLoaded', () => Director.init());
// Also init immediately if DOM is already ready (included via router)
if (document.readyState !== 'loading') Director.init();
</script>

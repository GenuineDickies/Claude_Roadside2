// Director — CRUD: artifacts, tasks, decisions, releases, audit log

// ── Artifacts ────────────────────────
Director.loadArtifacts = async function() {
    var res = await this.api('get_artifacts');
    if (!res.success) return;
    this.data.artifacts = res.data;
    document.getElementById('tabArtifactCount').textContent = res.data.length;
    this.renderArtifacts();
    this.populateArtifactSelect();
};

Director.renderArtifacts = function() {
    var phaseFilter = document.getElementById('artifactPhaseFilter').value;
    var statusFilter = document.getElementById('artifactStatusFilter').value;
    var filtered = this.data.artifacts;
    if (phaseFilter !== '') filtered = filtered.filter(function(a) { return a.phase == phaseFilter; });
    if (statusFilter) filtered = filtered.filter(function(a) { return a.status === statusFilter; });
    var phaseNames = { 0: 'Direction', 1: 'Product', 2: 'Architecture', 3: 'Delivery', 4: 'QA & Ops' };
    var tbody = document.getElementById('artifactTableBody');
    var self = this;
    if (filtered.length === 0) { tbody.innerHTML = '<tr><td colspan="7" class="director-empty"><i class="fas fa-filter"></i>No artifacts match filters</td></tr>'; return; }
    tbody.innerHTML = filtered.map(function(a) {
        return '<tr><td class="mono">' + a.id + '</td><td><span class="director-phase-label p' + a.phase + '">' + (phaseNames[a.phase] || '') + '</span></td><td style="font-weight:600">' + self.esc(a.title) + '</td><td class="mono" style="font-size:11px">' + self.esc(a.path) + '</td><td><span class="director-badge ' + a.status + '">' + a.status + '</span></td><td class="mono" style="text-align:center">v' + a.version + '</td><td><select class="director-status-select" onchange="Director.updateArtifactStatus(' + a.id + ', this.value)" style="width:90px">' + ['missing','draft','review','approved','outdated'].map(function(s) { return '<option value="' + s + '"' + (s === a.status ? ' selected' : '') + '>' + s + '</option>'; }).join('') + '</select> <button class="director-btn sm secondary" onclick="Director.editArtifact(' + a.id + ')" title="Edit content"><i class="fas fa-edit"></i></button></td></tr>';
    }).join('');
};

Director.updateArtifactStatus = async function(id, status) { await this.api('update_artifact_status', { id: id, status: status }, 'POST'); await this.refresh(); };

Director.editArtifact = function(id) {
    var a = this.data.artifacts.find(function(x) { return x.id == id; });
    if (!a) return;
    document.getElementById('artifactContentTitle').textContent = a.title;
    document.getElementById('artifactContentPath').textContent = a.path;
    document.getElementById('artifactContentStatus').className = 'director-badge ' + a.status;
    document.getElementById('artifactContentStatus').textContent = a.status;
    document.getElementById('artifactContentEditor').value = a.content || '';
    document.getElementById('artifactContentId').value = a.id;
    new bootstrap.Modal(document.getElementById('artifactContentModal')).show();
};

Director.saveArtifactContent = async function() {
    var id = document.getElementById('artifactContentId').value;
    var content = document.getElementById('artifactContentEditor').value;
    await this.api('save_artifact_content', { id: id, content: content }, 'POST');
    bootstrap.Modal.getInstance(document.getElementById('artifactContentModal')).hide();
    await this.refresh();
};

Director.populateArtifactSelect = function() {
    var sel = document.getElementById('taskArtifact');
    var self = this;
    sel.innerHTML = '<option value="">None</option>' + this.data.artifacts.map(function(a) { return '<option value="' + a.id + '">' + self.esc(a.title) + '</option>'; }).join('');
};

// ── Tasks / Build Queue ─────────────
Director.loadTasks = async function() {
    var res = await this.api('get_tasks');
    if (!res.success) return;
    this.data.tasks = res.data;
    document.getElementById('tabTaskCount').textContent = res.data.length;
    this.renderKanban();
};

Director.renderKanban = function() {
    var cols = { todo: [], doing: [], blocked: [], done: [] };
    var self = this;
    this.data.tasks.forEach(function(t) { if (cols[t.state]) cols[t.state].push(t); });
    Object.keys(cols).forEach(function(state) {
        var tasks = cols[state];
        var container = document.getElementById('kanban' + state.charAt(0).toUpperCase() + state.slice(1));
        var counter = document.getElementById('kanban' + state.charAt(0).toUpperCase() + state.slice(1) + 'Count');
        counter.textContent = tasks.length;
        if (tasks.length === 0) { container.innerHTML = '<div class="director-empty" style="padding:20px"><i class="fas fa-inbox"></i>Empty</div>'; return; }
        container.innerHTML = tasks.map(function(t) {
            return '<div class="director-task-card"><div class="task-title">' + self.esc(t.title) + '</div>' + (t.artifact_title ? '<div style="font-size:11px;color:var(--text-tertiary);margin-bottom:6px"><i class="fas fa-link" style="margin-right:4px"></i>' + self.esc(t.artifact_title) + '</div>' : '') + '<div class="task-meta"><span class="director-badge ' + t.priority + '">' + t.priority + '</span><div style="display:flex;gap:4px">' + (state !== 'doing' ? '<button class="director-btn sm secondary" onclick="Director.moveTask(' + t.id + ',\'doing\')" title="Start"><i class="fas fa-play"></i></button>' : '') + (state !== 'done' ? '<button class="director-btn sm secondary" onclick="Director.moveTask(' + t.id + ',\'done\')" title="Done"><i class="fas fa-check"></i></button>' : '') + (state !== 'blocked' && state !== 'done' ? '<button class="director-btn sm secondary" onclick="Director.moveTask(' + t.id + ',\'blocked\')" title="Block"><i class="fas fa-ban"></i></button>' : '') + (state === 'blocked' ? '<button class="director-btn sm secondary" onclick="Director.moveTask(' + t.id + ',\'todo\')" title="Unblock"><i class="fas fa-undo"></i></button>' : '') + '<button class="director-btn sm danger" onclick="Director.deleteTask(' + t.id + ')" title="Delete"><i class="fas fa-trash"></i></button></div></div>' + (t.assignee ? '<div style="font-size:11px;color:var(--text-tertiary);margin-top:6px"><i class="fas fa-user" style="margin-right:4px"></i>' + self.esc(t.assignee) + '</div>' : '') + '</div>';
        }).join('');
    });
};

Director.moveTask = async function(id, state) { await this.api('update_task_state', { id: id, state: state }, 'POST'); await this.loadTasks(); await this.loadStats(); };
Director.deleteTask = async function(id) { if (!confirm('Delete this task?')) return; await this.api('delete_task', { id: id }, 'POST'); await this.loadTasks(); await this.loadStats(); };

Director.showCreateTask = function() {
    document.getElementById('taskTitle').value = '';
    document.getElementById('taskDescription').value = '';
    document.getElementById('taskPriority').value = 'medium';
    document.getElementById('taskArtifact').value = '';
    document.getElementById('taskAssignee').value = '';
    document.getElementById('taskDueDate').value = '';
    new bootstrap.Modal(document.getElementById('createTaskModal')).show();
};

Director.createTask = async function() {
    var title = document.getElementById('taskTitle').value.trim();
    if (!title) return alert('Title required');
    await this.api('create_task', { title: title, description: document.getElementById('taskDescription').value, priority: document.getElementById('taskPriority').value, artifact_id: document.getElementById('taskArtifact').value, assignee: document.getElementById('taskAssignee').value, due_date: document.getElementById('taskDueDate').value }, 'POST');
    bootstrap.Modal.getInstance(document.getElementById('createTaskModal')).hide();
    await this.loadTasks(); await this.loadStats();
};

// ── Decisions ────────────────────────
Director.loadDecisions = async function() { var res = await this.api('get_decisions'); if (!res.success) return; this.data.decisions = res.data; document.getElementById('tabDecisionCount').textContent = res.data.length; this.renderDecisions(); };

Director.renderDecisions = function() {
    var tbody = document.getElementById('decisionTableBody');
    var self = this;
    if (this.data.decisions.length === 0) { tbody.innerHTML = '<tr><td colspan="6" class="director-empty"><i class="fas fa-gavel"></i>No decisions recorded yet</td></tr>'; return; }
    tbody.innerHTML = this.data.decisions.map(function(d) {
        return '<tr><td class="mono" style="font-weight:700">ADR-' + String(d.adr_number).padStart(3, '0') + '</td><td style="font-weight:600">' + self.esc(d.title) + '</td><td><span class="director-badge ' + d.status + '">' + d.status + '</span></td><td><span class="director-badge ' + d.impact + '">' + d.impact + '</span></td><td class="mono" style="font-size:11px">' + self.formatDate(d.created_at) + '</td><td><select class="director-status-select" onchange="Director.updateDecisionStatus(' + d.id + ', this.value)" style="width:110px">' + ['proposed','accepted','deprecated','superseded'].map(function(s) { return '<option value="' + s + '"' + (s === d.status ? ' selected' : '') + '>' + s + '</option>'; }).join('') + '</select></td></tr>';
    }).join('');
};

Director.updateDecisionStatus = async function(id, status) { await this.api('update_decision_status', { id: id, status: status }, 'POST'); await this.loadDecisions(); await this.loadStats(); };
Director.showCreateDecision = function() { document.getElementById('decisionTitle').value = ''; document.getElementById('decisionContext').value = ''; document.getElementById('decisionDecision').value = ''; document.getElementById('decisionConsequences').value = ''; document.getElementById('decisionImpact').value = 'medium'; new bootstrap.Modal(document.getElementById('createDecisionModal')).show(); };
Director.createDecision = async function() { var title = document.getElementById('decisionTitle').value.trim(); if (!title) return alert('Title required'); await this.api('create_decision', { title: title, context: document.getElementById('decisionContext').value, decision: document.getElementById('decisionDecision').value, consequences: document.getElementById('decisionConsequences').value, impact: document.getElementById('decisionImpact').value }, 'POST'); bootstrap.Modal.getInstance(document.getElementById('createDecisionModal')).hide(); await this.loadDecisions(); await this.loadStats(); };

// ── Releases ─────────────────────────
Director.loadReleases = async function() { var res = await this.api('get_releases'); if (!res.success) return; this.data.releases = res.data; document.getElementById('tabReleaseCount').textContent = res.data.length; this.renderReleases(); };

Director.renderReleases = function() {
    var tbody = document.getElementById('releaseTableBody');
    var self = this;
    if (this.data.releases.length === 0) { tbody.innerHTML = '<tr><td colspan="6" class="director-empty"><i class="fas fa-rocket"></i>No releases yet</td></tr>'; return; }
    tbody.innerHTML = this.data.releases.map(function(r) {
        return '<tr><td class="mono" style="font-weight:700">v' + self.esc(r.version) + '</td><td>' + self.esc(r.scope || '\u2014') + '</td><td><span class="director-badge ' + r.gate_status + '">' + r.gate_status + '</span></td><td class="mono" style="font-size:11px">' + self.esc(r.rollback_ref || '\u2014') + '</td><td class="mono" style="font-size:11px">' + (r.released_at ? self.formatDate(r.released_at) : '\u2014') + '</td><td><select class="director-status-select" onchange="Director.updateReleaseGate(' + r.id + ', this.value)" style="width:100px">' + ['pending','passing','failing','released'].map(function(s) { return '<option value="' + s + '"' + (s === r.gate_status ? ' selected' : '') + '>' + s + '</option>'; }).join('') + '</select></td></tr>';
    }).join('');
};

Director.updateReleaseGate = async function(id, status) { await this.api('update_release_gate', { id: id, gate_status: status }, 'POST'); await this.loadReleases(); await this.loadStats(); };
Director.showCreateRelease = function() { document.getElementById('releaseVersion').value = ''; document.getElementById('releaseScope').value = ''; document.getElementById('releaseRollback').value = ''; document.getElementById('releaseNotes').value = ''; new bootstrap.Modal(document.getElementById('createReleaseModal')).show(); };
Director.createRelease = async function() { var version = document.getElementById('releaseVersion').value.trim(); if (!version) return alert('Version required'); await this.api('create_release', { version: version, scope: document.getElementById('releaseScope').value, rollback_ref: document.getElementById('releaseRollback').value, notes: document.getElementById('releaseNotes').value }, 'POST'); bootstrap.Modal.getInstance(document.getElementById('createReleaseModal')).hide(); await this.loadReleases(); await this.loadStats(); };

// ── Audit Log ────────────────────────
Director.loadAuditLog = async function() {
    var res = await this.api('get_audit_log', { limit: 100 });
    if (!res.success) return;
    var tbody = document.getElementById('auditTableBody');
    var self = this;
    if (res.data.length === 0) { tbody.innerHTML = '<tr><td colspan="6" class="director-empty"><i class="fas fa-clipboard-list"></i>No audit entries</td></tr>'; return; }
    tbody.innerHTML = res.data.map(function(a) {
        return '<tr><td class="mono" style="font-size:11px">' + self.formatDate(a.created_at) + '</td><td style="font-weight:600">' + self.esc(a.username || 'system') + '</td><td><span class="director-badge ' + (a.entity_type === 'artifact' ? 'review' : a.entity_type === 'task' ? 'doing' : 'proposed') + '">' + a.entity_type + '</span></td><td class="mono">#' + a.entity_id + '</td><td style="font-weight:600">' + self.esc(a.action) + '</td><td style="color:var(--text-secondary);font-size:12px">' + self.esc(a.details || '\u2014') + '</td></tr>';
    }).join('');
};

// Boot
document.addEventListener('DOMContentLoaded', function() { Director.init(); });
if (document.readyState !== 'loading') Director.init();

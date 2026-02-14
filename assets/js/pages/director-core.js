// Director — Core: init, API, stats, overview rendering, helpers
var Director = {
    API: 'api/director.php',
    data: { artifacts: [], tasks: [], decisions: [], releases: [], stats: null },

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

    async api(action, params, method) {
        method = method || 'GET';
        try {
            if (method === 'GET') {
                var qs = new URLSearchParams(Object.assign({ action: action }, params || {})).toString();
                var res = await fetch(this.API + '?' + qs);
                return await res.json();
            } else {
                var form = new FormData();
                form.append('action', action);
                for (var k in (params || {})) form.append(k, params[k]);
                var res = await fetch(this.API, { method: 'POST', body: form });
                return await res.json();
            }
        } catch (e) {
            console.error('Director API error:', e);
            return { success: false, error: e.message };
        }
    },

    async loadStats() {
        var res = await this.api('get_stats');
        if (!res.success) return;
        this.data.stats = res.data;
        this.renderOverview(res.data);
    },

    renderOverview(s) {
        var statusMap = {};
        (s.artifacts || []).forEach(function(r) { statusMap[r.status] = parseInt(r.count); });
        var taskMap = {};
        (s.tasks || []).forEach(function(r) { taskMap[r.state] = parseInt(r.count); });

        var pct = s.artifact_total > 0 ? Math.round((s.artifacts_approved / s.artifact_total) * 100) : 0;
        var taskPct = s.task_total > 0 ? Math.round((s.tasks_done / s.task_total) * 100) : 0;

        document.getElementById('overviewStats').innerHTML =
            '<div class="director-stat navy"><div class="director-stat-label">Total Artifacts</div><div class="director-stat-value">' + s.artifact_total + '</div><div class="director-stat-sub">' + pct + '% approved</div></div>' +
            '<div class="director-stat amber"><div class="director-stat-label">Missing</div><div class="director-stat-value">' + (statusMap.missing || 0) + '</div><div class="director-stat-sub">Need creation</div></div>' +
            '<div class="director-stat green"><div class="director-stat-label">Approved</div><div class="director-stat-value">' + (statusMap.approved || 0) + '</div><div class="director-stat-sub">Ready for use</div></div>' +
            '<div class="director-stat blue"><div class="director-stat-label">Tasks Active</div><div class="director-stat-value">' + ((taskMap.todo || 0) + (taskMap.doing || 0)) + '</div><div class="director-stat-sub">' + taskPct + '% done</div></div>' +
            '<div class="director-stat purple"><div class="director-stat-label">Decisions</div><div class="director-stat-value">' + s.decision_count + '</div><div class="director-stat-sub">ADR records</div></div>' +
            '<div class="director-stat red"><div class="director-stat-label">Releases</div><div class="director-stat-value">' + s.release_count + '</div><div class="director-stat-sub">Version history</div></div>';

        var phaseNames = ['Direction Lock', 'Product Truth', 'Technical Blueprint', 'Delivery Control', 'QA & Ops'];
        var phaseColors = ['#EF4444', '#FBBF24', '#3B82F6', '#22C55E', '#A855F7'];
        var phaseHtml = '';
        (s.phases || []).forEach(function(p) {
            var total = parseInt(p.total), approved = parseInt(p.approved);
            var phasePct = total > 0 ? Math.round((approved / total) * 100) : 0;
            phaseHtml += '<div class="director-phase-card"><div class="director-phase-title"><span><span class="director-phase-label p' + p.phase + '">Phase ' + p.phase + '</span> ' + (phaseNames[p.phase] || 'Unknown') + '</span><span style="font-family:\'JetBrains Mono\',monospace;font-size:13px;color:var(--text-secondary)">' + approved + '/' + total + ' <span style="color:var(--text-tertiary)">(' + phasePct + '%)</span></span></div><div class="director-phase-bar"><div class="director-phase-fill" style="width:' + phasePct + '%;background:' + (phaseColors[p.phase] || '#3B82F6') + '"></div></div></div>';
        });
        document.getElementById('phaseProgress').innerHTML = phaseHtml || '<div class="director-empty"><i class="fas fa-spinner"></i>Loading phases...</div>';

        var feed = document.getElementById('activityFeed');
        if (s.recent_activity && s.recent_activity.length > 0) {
            var self = this;
            feed.innerHTML = s.recent_activity.map(function(a) {
                var iconClass = a.entity_type === 'artifact' ? 'artifact' : a.entity_type === 'task' ? 'task' : a.entity_type === 'decision' ? 'decision' : a.entity_type === 'release' ? 'release' : 'gate';
                var iconMap = { artifact: 'fa-archive', task: 'fa-tasks', decision: 'fa-gavel', release: 'fa-rocket', gate: 'fa-shield-alt' };
                return '<li><div class="act-icon ' + iconClass + '"><i class="fas ' + (iconMap[a.entity_type] || 'fa-circle') + '"></i></div><div class="act-text"><strong>' + (a.username || 'system') + '</strong> ' + a.action + ' ' + a.entity_type + ' #' + a.entity_id + (a.details ? ' — ' + self.esc(a.details) : '') + '</div><div class="act-time">' + self.timeAgo(a.created_at) + '</div></li>';
            }).join('');
        } else {
            feed.innerHTML = '<li class="director-empty"><i class="fas fa-ghost"></i>No activity yet. Start by updating artifact statuses.</li>';
        }
    },

    esc: function(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    },

    formatDate: function(d) {
        if (!d) return '\u2014';
        var dt = new Date(d);
        return dt.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) + ' ' + dt.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    },

    timeAgo: function(d) {
        if (!d) return '';
        var diff = Math.floor((Date.now() - new Date(d).getTime()) / 1000);
        if (diff < 60) return diff + 's ago';
        if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
        return Math.floor(diff / 86400) + 'd ago';
    }
};

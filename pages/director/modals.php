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
                <div class="mb-3"><label>Title</label><input type="text" class="form-control" id="decisionTitle" placeholder="ADR title..."></div>
                <div class="mb-3"><label>Context</label><textarea class="form-control" id="decisionContext" placeholder="What is the issue?"></textarea></div>
                <div class="mb-3"><label>Decision</label><textarea class="form-control" id="decisionDecision" placeholder="What was decided?"></textarea></div>
                <div class="mb-3"><label>Consequences</label><textarea class="form-control" id="decisionConsequences" placeholder="What are the outcomes?"></textarea></div>
                <div class="mb-3"><label>Impact</label><select class="form-select" id="decisionImpact"><option value="medium">Medium</option><option value="low">Low</option><option value="high">High</option></select></div>
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
                <div class="mb-3"><label>Version</label><input type="text" class="form-control" id="releaseVersion" placeholder="e.g. 1.0.0"></div>
                <div class="mb-3"><label>Scope</label><textarea class="form-control" id="releaseScope" placeholder="What's included?"></textarea></div>
                <div class="mb-3"><label>Rollback Reference</label><input type="text" class="form-control" id="releaseRollback" placeholder="e.g. commit hash, tag"></div>
                <div class="mb-3"><label>Notes</label><textarea class="form-control" id="releaseNotes" placeholder="Release notes..."></textarea></div>
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

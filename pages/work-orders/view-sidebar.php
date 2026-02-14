<!-- Timer / Status controls -->
<div class="card mb-3">
    <div class="card-body" style="text-align:center">
        <div class="wo-timer <?= $wo['status'] === 'paused' ? 'paused' : '' ?>" style="margin-bottom:12px;display:inline-flex">
            <div>
                <div class="wo-timer-label">Elapsed</div>
                <div class="clock" id="elapsedClock" <?php if ($wo['work_started_at'] && !$wo['work_completed_at']): ?>data-start="<?= $wo['work_started_at'] ?>"<?php endif; ?>>--:--:--</div>
            </div>
        </div>
        <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;margin-top:8px">
            <?php if ($wo['status'] === 'created'): ?>
                <button class="btn btn-success btn-sm" onclick="updateWoStatus(<?= $wo['id'] ?>,'in_progress')"><i class="fas fa-play"></i> Start Work</button>
            <?php elseif ($wo['status'] === 'in_progress'): ?>
                <button class="btn btn-warning btn-sm" onclick="updateWoStatus(<?= $wo['id'] ?>,'paused')"><i class="fas fa-pause"></i> Pause</button>
                <button class="btn btn-success btn-sm" onclick="completeWork(<?= $wo['id'] ?>)"><i class="fas fa-check"></i> Complete</button>
            <?php elseif ($wo['status'] === 'paused'): ?>
                <button class="btn btn-success btn-sm" onclick="updateWoStatus(<?= $wo['id'] ?>,'in_progress')"><i class="fas fa-play"></i> Resume</button>
            <?php elseif ($wo['status'] === 'completed'): ?>
                <span style="color:var(--green-500);font-weight:600"><i class="fas fa-check-circle"></i> Completed</span>
            <?php endif; ?>
        </div>
        <?php if ($wo['work_started_at']): ?>
            <div style="font-size:11px;color:var(--text-tertiary);margin-top:8px">Started: <?= format_datetime($wo['work_started_at']) ?></div>
        <?php endif; ?>
        <?php if ($wo['work_completed_at']): ?>
            <div style="font-size:11px;color:var(--text-tertiary)">Completed: <?= format_datetime($wo['work_completed_at']) ?></div>
        <?php endif; ?>
    </div>
</div>

<!-- Customer signoff -->
<div class="card mb-3">
    <div class="card-body">
        <h5 style="font-size:13px;font-weight:600;margin-bottom:8px"><i class="fas fa-user-check" style="color:var(--navy-300)"></i> Customer Signoff</h5>
        <?php if ($wo['customer_signoff']): ?>
            <div style="color:var(--green-500);font-size:13px"><i class="fas fa-check-circle"></i> Signed <?= $wo['signoff_at'] ? format_datetime($wo['signoff_at']) : '' ?></div>
        <?php else: ?>
            <button class="btn btn-sm btn-outline-primary" onclick="customerSignoff(<?= $wo['id'] ?>)"><i class="fas fa-signature"></i> Record Signoff</button>
        <?php endif; ?>
    </div>
</div>

<!-- Progress Log -->
<div class="card mb-3">
    <div class="card-body">
        <h5 style="font-size:13px;font-weight:600;margin-bottom:8px"><i class="fas fa-stream" style="color:var(--navy-300)"></i> Progress Log</h5>
        <?php if (in_array($wo['status'], ['in_progress', 'paused'])): ?>
            <div style="display:flex;gap:6px;margin-bottom:12px">
                <input type="text" id="logNoteInput" placeholder="Add note..." style="flex:1;background:var(--bg-primary);border:1px solid var(--border-medium);border-radius:4px;padding:6px 8px;color:var(--text-primary);font-size:12px">
                <button class="btn btn-sm btn-primary" onclick="addProgressNote(<?= $wo['id'] ?>, <?= $wo['technician_id'] ?>)"><i class="fas fa-plus"></i></button>
            </div>
        <?php endif; ?>
        <div class="wo-log">
            <?php if (empty($progressLog)): ?>
                <p style="text-align:center;padding:20px;color:var(--text-tertiary);font-size:12px">No log entries yet.</p>
            <?php else: foreach ($progressLog as $log):
                $icons = ['clock_start' => 'fa-play', 'clock_stop' => 'fa-stop', 'note' => 'fa-sticky-note', 'photo' => 'fa-camera', 'status_change' => 'fa-exchange-alt', 'checklist_item' => 'fa-check-square'];
            ?>
                <div class="wo-log-entry">
                    <div class="wo-log-icon <?= $log['entry_type'] ?>"><i class="fas <?= $icons[$log['entry_type']] ?? 'fa-circle' ?>"></i></div>
                    <div>
                        <div style="color:var(--text-primary)"><?= htmlspecialchars($log['content'] ?? ucfirst(str_replace('_', ' ', $log['entry_type']))) ?></div>
                        <div class="wo-log-meta"><?= htmlspecialchars(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '')) ?> — <?= format_datetime($log['logged_at']) ?></div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

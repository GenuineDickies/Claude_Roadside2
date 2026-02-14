<!-- Header -->
<div class="dash-header">
    <div style="display:flex;align-items:center;gap:14px">
        <i class="fas fa-tachometer-alt" style="font-size:26px;color:var(--navy-300)"></i>
        <div>
            <h1>Command Center</h1>
            <p class="subtitle">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?> — here's your operation at a glance</p>
        </div>
    </div>
    <div class="timestamp"><?php echo date('l, M j, Y — g:i A'); ?></div>
</div>

<!-- Compliance Reminder Strip -->
<?php
$stripClass = 'ok';
$stripIcon = 'fa-check-circle';
$stripColor = '#22C55E';
$stripText = 'All documents & licenses look good';
if ($complianceNeedsAttention > 0) {
    $stripClass = 'warn'; $stripIcon = 'fa-bell'; $stripColor = '#FBBF24';
    $parts = [];
    if ($complianceMissing > 0) $parts[] = $complianceMissing . ' missing';
    if ($complianceExpired > 0) $parts[] = $complianceExpired . ' expired';
    if ($complianceExpiring > 0) $parts[] = $complianceExpiring . ' expiring soon';
    $stripText = 'Heads up: ' . implode(', ', $parts) . ' — take a look when you get a chance';
}
?>
<div class="dash-compliance-strip <?= $stripClass ?>">
    <div class="strip-left">
        <i class="fas <?= $stripIcon ?>" style="font-size:18px;color:<?= $stripColor ?>"></i>
        <div>
            <div class="strip-text"><strong>Documents:</strong> <?= $stripText ?></div>
        </div>
    </div>
    <div class="strip-items">
        <?php foreach (array_slice($complianceAlerts, 0, 3) as $alert): ?>
            <?php
            $catIcons = ['license' => 'fa-id-badge', 'permit' => 'fa-file-alt', 'certification' => 'fa-certificate', 'insurance' => 'fa-shield-alt', 'registration' => 'fa-clipboard-list', 'inspection' => 'fa-clipboard-check', 'other' => 'fa-file'];
            $aIcon = $catIcons[$alert['category']] ?? 'fa-file';
            $aColor = $alert['alert_type'] === 'missing' ? 'rgba(148,163,184,0.1);color:#94A3B8' :
                      ($alert['alert_type'] === 'expired' ? 'rgba(239,68,68,0.1);color:#EF4444' : 'rgba(245,158,11,0.1);color:#FBBF24');
            ?>
            <span class="strip-item" style="background:<?= $aColor ?>">
                <i class="fas <?= $aIcon ?>" style="font-size:10px"></i>
                <?= htmlspecialchars($alert['name']) ?>
                <?php if ($alert['alert_type'] === 'missing'): ?>
                    (need)
                <?php elseif ($alert['days_left'] !== null): ?>
                    (<?= $alert['days_left'] < 0 ? abs($alert['days_left']) . 'd ago' : $alert['days_left'] . 'd left' ?>)
                <?php endif; ?>
            </span>
        <?php endforeach; ?>
        <a href="?page=compliance" class="btn btn-sm btn-outline-primary" style="font-size:11px">View All</a>
    </div>
</div>

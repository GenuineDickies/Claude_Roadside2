<!-- LEFT: Recent Requests -->
<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><i class="fas fa-clock"></i> Recent Service Requests</h5>
            <a href="?page=service-requests" class="btn btn-sm btn-primary">View All</a>
        </div>
        <div class="card-body">
            <?php if (empty($recentRequests)): ?>
                <div class="rr-empty">
                    <i class="fas fa-clipboard-list"></i>
                    No service requests found.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Service</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentRequests as $request): ?>
                                <tr>
                                    <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--text-tertiary)"><?= htmlspecialchars(format_ticket_number($request['ticket_number'])) ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($request['first_name'] . ' ' . $request['last_name']) ?></strong><br>
                                        <small style="font-family:'JetBrains Mono',monospace;color:var(--text-tertiary)"><?= htmlspecialchars(format_phone($request['phone'])) ?></small>
                                    </td>
                                    <td><?= ucfirst(str_replace('_', ' ', $request['service_category'])) ?></td>
                                    <td><?= get_status_badge($request['status']) ?></td>
                                    <td><?= get_priority_badge($request['priority']) ?></td>
                                    <td style="font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--text-tertiary)"><?= format_datetime($request['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

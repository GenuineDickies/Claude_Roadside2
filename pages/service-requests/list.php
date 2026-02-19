<!-- Service Tickets List -->
<style>
/* Zebra striping for service request rows */
.sr-list-table tbody tr:nth-child(odd) {
    background: var(--bg-surface);
}
.sr-list-table tbody tr:nth-child(even) {
    background: var(--bg-secondary);
}
.sr-list-table tbody tr:hover {
    background: var(--bg-surface-hover) !important;
}
/* Compact single-line styling */
.sr-list-table td {
    vertical-align: middle;
    white-space: nowrap;
    padding: 10px 12px;
}
.sr-list-table .sr-customer-cell {
    white-space: nowrap;
}
.sr-list-table .sr-customer-phone {
    color: var(--text-tertiary);
    font-family: 'JetBrains Mono', monospace;
    font-size: 11px;
    margin-left: 8px;
}
</style>

<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col-md-6">
                <h5 class="card-title mb-0">All Service Tickets</h5>
            </div>
            <div class="col-md-6">
                <input type="text" class="form-control search-input" placeholder="Search requests...">
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (empty($requests)): ?>
            <p class="text-muted p-3">No service tickets found. <a href="?page=service-intake">Create the first ticket</a>.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover sr-list-table mb-0">
                    <thead>
                        <tr>
                            <th>Ticket #</th>
                            <th>Customer</th>
                            <th>Service</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Technician</th>
                            <th>Cost</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                            <tr class="searchable-row priority-<?php echo $req['priority']; ?>">
                                <?php $displayTicket = format_ticket_number($req['ticket_number']); ?>
                                <td style="font-family:'JetBrains Mono',monospace;font-size:12px"><?php echo htmlspecialchars($displayTicket); ?></td>
                                <td class="sr-customer-cell">
                                    <strong><?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></strong>
                                    <span class="sr-customer-phone"><?php echo htmlspecialchars(format_phone($req['phone'])); ?></span>
                                </td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $req['service_category'])); ?></td>
                                <td><?php echo get_status_badge($req['status']); ?></td>
                                <td><?php echo get_priority_badge($req['priority']); ?></td>
                                <td>
                                    <?php if ($req['tech_first_name']): ?>
                                        <?php echo htmlspecialchars($req['tech_first_name'] . ' ' . $req['tech_last_name']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-family:'JetBrains Mono',monospace;font-size:12px">
                                    <?php if ($req['price_quoted'] > 0): ?>
                                        <?php echo format_currency($req['price_quoted']); ?>
                                    <?php else: ?>
                                        <span class="text-muted"><?php echo format_currency($req['estimated_cost']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size:12px"><?php echo date('M d, g:i A', strtotime($req['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm" style="white-space:nowrap">
                                        <a href="?page=service-requests&action=view&id=<?php echo $req['id']; ?>" class="btn btn-outline-primary btn-sm">View</a>
                                        <?php if (in_array($req['status'], ['created', 'dispatched', 'acknowledged', 'en_route', 'on_scene', 'in_progress'])): ?>
                                            <a href="?page=estimates&action=create&sr_id=<?php echo $req['id']; ?>" class="btn btn-outline-info btn-sm">Est</a>
                                        <?php endif; ?>
                                        <?php if ($req['status'] === 'completed'): ?>
                                            <a href="?page=invoices&action=create&request_id=<?php echo $req['id']; ?>" class="btn btn-outline-success btn-sm">Inv</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

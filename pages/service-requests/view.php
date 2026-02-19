<!-- View Request Details -->
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body" style="padding: 24px;">
                <!-- Ticket Header -->
                <div class="sr-ticket-header">
                    <div>
                        <div class="sr-ticket-id"><?php echo htmlspecialchars(format_ticket_number($request['ticket_number'])); ?></div>
                        <div class="sr-ticket-badges">
                            <?php echo get_status_badge($request['status']); ?>
                            <?php echo get_priority_badge($request['priority']); ?>
                        </div>
                    </div>
                    <div class="sr-ticket-meta">
                        <div>Created <?php echo format_datetime($request['created_at']); ?></div>
                        <?php if ($request['dispatched_at']): ?>
                            <div style="color:var(--blue-500)">Dispatched <?php echo format_datetime($request['dispatched_at']); ?></div>
                        <?php endif; ?>
                        <?php if ($request['completed_at']): ?>
                            <div style="color:var(--green-500)">Completed <?php echo format_datetime($request['completed_at']); ?></div>
                        <?php endif; ?>
                        <div style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--text-tertiary)">Version <?php echo str_pad((string)($request['version'] ?? 1), 2, '0', STR_PAD_LEFT); ?></div>
                    </div>
                </div>

                <!-- Customer + Service Info Grid -->
                <div class="sr-info-grid">
                    <div class="sr-info-card">
                        <h6><i class="fas fa-user"></i> Customer</h6>
                        <div class="sr-info-row">
                            <span class="sr-name"><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></span>
                        </div>
                        <div class="sr-info-row">
                            <i class="fas fa-phone"></i>
                            <span class="sr-value-mono"><?php echo htmlspecialchars(format_phone($request['phone'])); ?></span>
                        </div>
                        <?php if (!empty($request['email'])): ?>
                        <div class="sr-info-row">
                            <i class="fas fa-envelope"></i>
                            <span class="sr-value"><?php echo htmlspecialchars($request['email']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="sr-info-card">
                        <h6><i class="fas fa-car"></i> Vehicle</h6>
                        <?php if (!empty($request['vehicle_year']) || !empty($request['vehicle_make']) || !empty($request['vehicle_model'])): ?>
                        <div class="sr-info-row">
                            <span class="sr-value" style="font-weight:600">
                                <?php 
                                $vehicle_parts = array_filter([
                                    $request['vehicle_year'] ?? '',
                                    $request['vehicle_make'] ?? '',
                                    $request['vehicle_model'] ?? ''
                                ]);
                                echo htmlspecialchars(implode(' ', $vehicle_parts));
                                ?>
                            </span>
                        </div>
                        <?php if (!empty($request['vehicle_color'])): ?>
                        <div class="sr-info-row">
                            <span class="sr-label">Color</span>
                            <span class="sr-value"><?php echo htmlspecialchars($request['vehicle_color']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($request['vehicle_plate'])): ?>
                        <div class="sr-info-row">
                            <span class="sr-label">Plate</span>
                            <span class="sr-value-mono"><?php echo htmlspecialchars(strtoupper($request['vehicle_plate'])); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php else: ?>
                        <div class="sr-info-row">
                            <span class="sr-value text-muted">No vehicle info</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Service Info -->
                <div class="sr-section">
                    <h6><i class="fas fa-wrench"></i> Service</h6>
                    <div class="sr-info-row">
                        <span class="sr-label">Type</span>
                        <span class="sr-value"><?php echo ucfirst(str_replace('_', ' ', $request['service_category'])); ?></span>
                    </div>
                    <?php if ($request['tech_first_name']): ?>
                    <div class="sr-info-row">
                        <span class="sr-label">Tech</span>
                        <span class="sr-value"><?php echo htmlspecialchars($request['tech_first_name'] . ' ' . $request['tech_last_name']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Location -->
                <div class="sr-section">
                    <h6><i class="fas fa-map-marker-alt"></i> Location</h6>
                    <p><?php echo nl2br(htmlspecialchars($request['service_address'])); ?></p>
                </div>

                <!-- Description -->
                <?php if ($request['issue_description']): ?>
                <div class="sr-section">
                    <h6><i class="fas fa-align-left"></i> Description</h6>
                    <p><?php echo nl2br(htmlspecialchars($request['issue_description'])); ?></p>
                </div>
                <?php endif; ?>

                <!-- Assigned Technician -->
                <?php if ($request['tech_first_name']): ?>
                <div class="sr-section">
                    <h6><i class="fas fa-user-cog"></i> Assigned Technician</h6>
                    <div class="sr-tech-badge">
                        <div class="avatar"><?php echo strtoupper(substr($request['tech_first_name'], 0, 1) . substr($request['tech_last_name'], 0, 1)); ?></div>
                        <div>
                            <div class="name"><?php echo htmlspecialchars($request['tech_first_name'] . ' ' . $request['tech_last_name']); ?></div>
                            <div class="phone"><?php echo htmlspecialchars(format_phone($request['tech_phone'])); ?></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Cost Information -->
                <div class="sr-section">
                    <h6><i class="fas fa-dollar-sign"></i> Cost Summary</h6>
                    <div class="sr-cost-grid">
                        <div class="sr-cost-item">
                            <div class="label">Estimated</div>
                            <div class="amount"><?php echo format_currency($request['estimated_cost']); ?></div>
                        </div>
                        <div class="sr-cost-item">
                            <div class="label">Quoted</div>
                            <div class="amount"><?php echo $request['price_quoted'] > 0 ? format_currency($request['price_quoted']) : '—'; ?></div>
                        </div>
                    </div>
                </div>

                <div class="sr-section">
                    <h6><i class="fas fa-history"></i> Version History</h6>
                    <p class="text-secondary" style="margin-bottom:12px;font-size:12px">Past drafts are stored as read-only snapshots. Saving creates a new version instead of modifying them.</p>
                    <?php if (!empty($ticketHistory)): ?>
                        <?php foreach ($ticketHistory as $history):
                            $snapshot = json_decode($history['data'], true) ?? [];
                            $archivedBy = $history['username'] ?? 'System';
                        ?>
                            <div style="background:var(--bg-surface);border:1px solid var(--border-medium);border-radius:10px;padding:12px;margin-bottom:12px">
                                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px">
                                    <div>
                                        <div style="font-size:13px;font-weight:600;letter-spacing:0.5px;text-transform:uppercase;color:var(--text-tertiary)">Version <?php echo str_pad((string)$history['version'], 2, '0', STR_PAD_LEFT); ?></div>
                                        <div style="font-size:12px;color:var(--text-secondary)">Archived <?php echo format_datetime($history['archived_at']); ?> by <?php echo htmlspecialchars($archivedBy); ?></div>
                                    </div>
                                    <span class="sr-ticket-id" style="font-size:12px"><?php echo htmlspecialchars($history['ticket_number']); ?></span>
                                </div>
                                <?php if (!empty($snapshot['service_category']) || !empty($snapshot['issue_description'])): ?>
                                    <hr style="border-color:rgba(255,255,255,0.08);margin:10px 0">
                                    <div class="sr-info-row" style="gap:8px;flex-wrap:wrap">
                                        <?php if (!empty($snapshot['service_category'])): ?>
                                            <span class="sr-label">Category</span>
                                            <span class="sr-value"><?php echo htmlspecialchars(ucfirst(str_replace('_',' ',$snapshot['service_category']))); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($snapshot['priority'])): ?>
                                            <span class="sr-label">Priority</span>
                                            <span class="sr-value"><?php echo htmlspecialchars($snapshot['priority']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($snapshot['issue_description'])): ?>
                                        <p style="margin:8px 0 0;font-size:13px;color:var(--text-secondary)"><?php echo nl2br(htmlspecialchars($snapshot['issue_description'])); ?></p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-secondary" style="font-size:12px">No prior drafts yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/view-sidebar.php'; ?>
</div>

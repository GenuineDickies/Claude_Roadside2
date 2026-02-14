<div class="col-md-4">
    <!-- Actions Panel -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="fas fa-bolt" style="color:var(--accent-purple-light, #A78BFA);margin-right:6px"></i> Actions</h5>
        </div>
        <div class="card-body">
            <?php if (!$request['technician_id']): ?>
                <!-- Assign Technician -->
                <div class="sr-action-group">
                    <div class="sr-action-label">Assign Technician</div>
                    <form method="POST">
                        <select class="form-select mb-2" name="technician_id" required>
                            <option value="">Select Technician</option>
                            <?php foreach ($availableTechnicians as $tech): ?>
                                <option value="<?php echo $tech['id']; ?>">
                                    <?php echo htmlspecialchars($tech['first_name'] . ' ' . $tech['last_name']); ?>
                                    <?php if ($tech['specialization']): ?>
                                        (<?php echo htmlspecialchars($tech['specialization']); ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="assign_technician" class="btn btn-primary btn-sm w-100">
                            <i class="fas fa-user-plus"></i> Assign
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if ($request['technician_id'] && $request['status'] === 'created'): ?>
                <?php
                $estCheckView = $pdo->prepare("SELECT COUNT(*) FROM estimates WHERE service_request_id = ? AND status = 'approved'");
                $estCheckView->execute([$request['id']]);
                $hasApprovedEst = $estCheckView->fetchColumn() > 0;
                ?>
                <div class="sr-action-group">
                    <div class="sr-action-label">Dispatch</div>
                    <div class="sr-tech-badge" style="margin-bottom:10px">
                        <div class="avatar" style="width:28px;height:28px;font-size:11px"><?php echo strtoupper(substr($request['tech_first_name'], 0, 1) . substr($request['tech_last_name'], 0, 1)); ?></div>
                        <div>
                            <div class="name" style="font-size:12px"><?php echo htmlspecialchars($request['tech_first_name'] . ' ' . $request['tech_last_name']); ?></div>
                        </div>
                    </div>
                    <?php if ($hasApprovedEst): ?>
                        <form method="POST">
                            <button type="submit" name="dispatch_ticket" class="btn btn-success btn-sm w-100">
                                <i class="fas fa-paper-plane"></i> Dispatch Now
                            </button>
                        </form>
                    <?php else: ?>
                        <div style="font-size:11px;padding:10px 12px;background:rgba(245,158,11,0.06);border:1px solid rgba(245,158,11,0.2);border-radius:8px;text-align:center;margin-bottom:10px">
                            <i class="fas fa-exclamation-triangle" style="color:var(--amber-500);margin-right:4px"></i>
                            <span style="color:var(--amber-400)">Approved estimate required</span>
                        </div>
                        <a href="?page=estimates&action=create&sr_id=<?php echo $request['id']; ?>" class="btn btn-info btn-sm w-100">
                            <i class="fas fa-file-invoice"></i> Create Estimate
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($request['technician_id'] && in_array($request['status'], ['dispatched', 'acknowledged', 'en_route', 'on_scene', 'in_progress'])): ?>
                <div class="sr-action-group">
                    <div class="sr-action-label">Update Status</div>
                    <form method="POST">
                        <select class="form-select mb-2" name="status">
                            <option value="dispatched" <?php echo $request['status'] === 'dispatched' ? 'selected' : ''; ?>>Dispatched</option>
                            <option value="en_route" <?php echo $request['status'] === 'en_route' ? 'selected' : ''; ?>>En Route</option>
                            <option value="on_scene" <?php echo $request['status'] === 'on_scene' ? 'selected' : ''; ?>>On Scene</option>
                            <option value="in_progress" <?php echo $request['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <label for="actual_cost" class="form-label">Quoted Price</label>
                        <div class="input-group mb-2">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" name="actual_cost"
                                   value="<?php echo $request['price_quoted']; ?>" step="0.01" min="0">
                        </div>
                        <button type="submit" name="update_status" class="btn btn-primary btn-sm w-100">
                            <i class="fas fa-sync-alt"></i> Update Ticket
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if ($request['status'] === 'completed' && $request['price_quoted'] > 0): ?>
                <div class="sr-action-group">
                    <div class="sr-action-label">Billing</div>
                    <a href="?page=invoices&action=create&request_id=<?php echo $request['id']; ?>"
                       class="btn btn-success btn-sm w-100">
                        <i class="fas fa-file-invoice-dollar"></i> Create Invoice
                    </a>
                </div>
            <?php endif; ?>

            <!-- Workflow Links -->
            <div class="sr-action-group">
                <div class="sr-action-label">Workflow</div>
                <?php if (in_array($request['status'], ['created', 'dispatched', 'acknowledged', 'en_route', 'on_scene', 'in_progress'])): ?>
                    <a href="?page=estimates&action=create&sr_id=<?php echo $request['id']; ?>"
                       class="btn btn-outline-primary btn-sm w-100 mb-2">
                        <i class="fas fa-file-invoice"></i> Create Estimate
                    </a>
                <?php endif; ?>
                <a href="?page=service-requests" class="btn btn-outline-secondary btn-sm w-100">
                    <i class="fas fa-list"></i> All Tickets
                </a>
                <a href="?page=service-requests&action=edit&id=<?php echo $request['id']; ?>" class="btn btn-outline-info btn-sm w-100 mt-2">
                    <i class="fas fa-edit"></i> Edit Ticket
                </a>
            </div>
        </div>
    </div>
</div>

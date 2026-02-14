<!-- Technician List -->
<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col-md-6">
                <h5 class="card-title mb-0">Technician Directory</h5>
            </div>
            <div class="col-md-6">
                <input type="text" class="form-control search-input" placeholder="Search technicians...">
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($technicians)): ?>
            <p class="text-muted">No technicians found. <a href="?page=technicians&action=add">Add the first technician</a>.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Specialization</th>
                            <th>Status</th>
                            <th>Hourly Rate</th>
                            <th>Active Jobs</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($technicians as $tech): ?>
                            <tr class="searchable-row">
                                <td>#<?php echo $tech['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($tech['first_name'] . ' ' . $tech['last_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($tech['email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars(format_phone($tech['phone'])); ?></td>
                                <td><?php echo htmlspecialchars($tech['specialization']); ?></td>
                                <td><?php echo get_status_badge($tech['status']); ?></td>
                                <td><?php echo format_currency($tech['hourly_rate']); ?></td>
                                <td>
                                    <span class="badge bg-info"><?php echo $tech['active_requests']; ?></span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="?page=technicians&action=edit&id=<?php echo $tech['id']; ?>"
                                           class="btn btn-outline-primary">Edit</a>
                                        <a href="?page=technicians&action=delete&id=<?php echo $tech['id']; ?>"
                                           class="btn btn-outline-danger delete-btn">Delete</a>
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

<!-- Customer List -->
<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col-md-6">
                <h5 class="card-title mb-0">Customer Directory</h5>
            </div>
            <div class="col-md-6">
                <input type="text" class="form-control search-input" placeholder="Search customers...">
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($customers)): ?>
            <p class="text-muted">No customers found. <a href="?page=customers&action=add">Add the first customer</a>.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Address</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $customer): ?>
                            <tr class="searchable-row">
                                <td>#<?php echo $customer['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars(format_phone($customer['phone'])); ?></td>
                                <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                <td><?php echo htmlspecialchars($customer['address']); ?></td>
                                <td><?php echo format_date($customer['created_at']); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="?page=customers&action=edit&id=<?php echo $customer['id']; ?>"
                                           class="btn btn-outline-primary">Edit</a>
                                        <a href="?page=service-requests&action=add&customer_id=<?php echo $customer['id']; ?>"
                                           class="btn btn-outline-success">New Request</a>
                                        <a href="?page=customers&action=delete&id=<?php echo $customer['id']; ?>"
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

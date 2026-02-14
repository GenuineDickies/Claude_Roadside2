<!-- Add/Edit Technician Form -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title"><?php echo $action === 'add' ? 'Add New Technician' : 'Edit Technician'; ?></h5>
    </div>
    <div class="card-body">
        <form method="POST">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="first_name" class="form-label">First Name *</label>
                        <input type="text" class="form-control" id="first_name" name="first_name"
                               value="<?php echo htmlspecialchars($technician['first_name'] ?? ''); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="last_name" class="form-label">Last Name *</label>
                        <input type="text" class="form-control" id="last_name" name="last_name"
                               value="<?php echo htmlspecialchars($technician['last_name'] ?? ''); ?>" required>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone *</label>
                        <?php
                        $phone_value = '';
                        if (isset($technician['phone']) && !empty($technician['phone'])) {
                            $phone_value = $technician['phone'];
                        }
                        ?>
                        <input type="tel" class="form-control phone-masked" id="phone" name="phone"
                               value="<?php echo htmlspecialchars($phone_value); ?>"
                               required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="<?php echo htmlspecialchars($technician['email'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="specialization" class="form-label">Specialization</label>
                        <input type="text" class="form-control" id="specialization" name="specialization"
                               value="<?php echo htmlspecialchars($technician['specialization'] ?? ''); ?>"
                               placeholder="e.g., Automotive, Locksmith, Towing">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="hourly_rate" class="form-label">Hourly Rate</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="hourly_rate" name="hourly_rate"
                                   value="<?php echo $technician['hourly_rate'] ?? '0'; ?>" step="0.01" min="0">
                        </div>
                    </div>
                </div>
            </div>
            <?php if ($action === 'edit'): ?>
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="available" <?php echo ($technician['status'] ?? '') === 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="busy" <?php echo ($technician['status'] ?? '') === 'busy' ? 'selected' : ''; ?>>Busy</option>
                        <option value="offline" <?php echo ($technician['status'] ?? '') === 'offline' ? 'selected' : ''; ?>>Offline</option>
                    </select>
                </div>
            <?php endif; ?>
            <div class="d-flex justify-content-end">
                <a href="?page=technicians" class="btn btn-secondary me-2">Cancel</a>
                <button type="submit" name="<?php echo $action === 'add' ? 'add_technician' : 'edit_technician'; ?>"
                        class="btn btn-primary">
                    <?php echo $action === 'add' ? 'Add Technician' : 'Update Technician'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Add/Edit Customer Form -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title"><?php echo $action === 'add' ? 'Add New Customer' : 'Edit Customer'; ?></h5>
    </div>
    <div class="card-body">
        <form method="POST">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="first_name" class="form-label">First Name *</label>
                        <input type="text" class="form-control" id="first_name" name="first_name"
                               value="<?php echo htmlspecialchars($customer['first_name'] ?? ''); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="last_name" class="form-label">Last Name *</label>
                        <input type="text" class="form-control" id="last_name" name="last_name"
                               value="<?php echo htmlspecialchars($customer['last_name'] ?? ''); ?>" required>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone *</label>
                        <?php
                        $phone_value = '';
                        if (isset($customer['phone']) && !empty($customer['phone'])) {
                            $phone_value = $customer['phone'];
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
                               value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($customer['address'] ?? ''); ?></textarea>
            </div>
            <div class="d-flex justify-content-end">
                <a href="?page=customers" class="btn btn-secondary me-2">Cancel</a>
                <button type="submit" name="<?php echo $action === 'add' ? 'add_customer' : 'edit_customer'; ?>"
                        class="btn btn-primary">
                    <?php echo $action === 'add' ? 'Add Customer' : 'Update Customer'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

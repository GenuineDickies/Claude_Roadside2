<!-- Add New Service Ticket Form -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title">Create New Service Ticket</h5>
    </div>
    <div class="card-body">
        <form method="POST">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="customer_id" class="form-label">Customer *</label>
                        <select class="form-select" id="customer_id" name="customer_id" required>
                            <option value="">Select Customer</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?php echo $customer['id']; ?>"
                                        <?php echo $customer_id == $customer['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name'] . ' - ' . format_phone($customer['phone'])); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="service_category" class="form-label">Service Category *</label>
                        <select class="form-select" id="service_category" name="service_category" required>
                            <option value="">Select Service</option>
                            <option value="towing">Towing</option>
                            <option value="lockout">Vehicle Lockout</option>
                            <option value="jump_start">Jump Start</option>
                            <option value="tire_service">Tire Service</option>
                            <option value="fuel_delivery">Fuel Delivery</option>
                            <option value="mobile_repair">Mobile Repair</option>
                            <option value="winch_recovery">Winch Recovery</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label for="issue_description" class="form-label">Description</label>
                <textarea class="form-control" id="issue_description" name="issue_description" rows="3"
                          placeholder="Describe the issue and any specific requirements..."></textarea>
            </div>
            <div class="mb-3">
                <label for="service_address" class="form-label">Location *</label>
                <textarea class="form-control" id="service_address" name="service_address" rows="2"
                          placeholder="Exact address or location details..." required></textarea>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="priority" class="form-label">Priority</label>
                        <select class="form-select" id="priority" name="priority">
                            <option value="P4">Low (P4)</option>
                            <option value="P3" selected>Normal (P3)</option>
                            <option value="P2">High (P2)</option>
                            <option value="P1">Urgent (P1)</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="estimated_cost" class="form-label">Estimated Cost</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="estimated_cost" name="estimated_cost"
                                   value="0" step="0.01" min="0">
                        </div>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-end">
                <a href="?page=service-requests" class="btn btn-secondary me-2">Cancel</a>
                <button type="submit" name="add_request" class="btn btn-primary">Create Ticket</button>
            </div>
        </form>
    </div>
</div>

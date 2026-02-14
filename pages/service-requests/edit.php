<!-- Edit Service Ticket -->
<div class="card">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
        <h5 class="card-title mb-0"><i class="fas fa-edit" style="color:var(--navy-300);margin-right:8px"></i> Edit Service Ticket</h5>
        <span class="sr-ticket-id" style="font-family:'JetBrains Mono',monospace;font-size:13px;color:var(--navy-300)"><?= htmlspecialchars(format_ticket_number($request['ticket_number'])) ?></span>
    </div>
    <div class="card-body" style="padding:24px">
        <form method="POST">
            <input type="hidden" name="id" value="<?= htmlspecialchars($request['id']) ?>">
            <!-- Customer & Service Category -->
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="edit_customer_id" class="form-label">Customer *</label>
                    <select class="form-select" id="edit_customer_id" name="customer_id" required>
                        <option value="">Select Customer</option>
                        <?php foreach ($customers as $customer): ?>
                            <option value="<?= $customer['id'] ?>"
                                    <?= $request['customer_id'] == $customer['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name'] . ' - ' . format_phone($customer['phone'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="edit_service_category" class="form-label">Service Category *</label>
                    <select class="form-select" id="edit_service_category" name="service_category" required>
                        <?php
                        $categories = [
                            'towing' => 'Towing',
                            'lockout' => 'Vehicle Lockout',
                            'jump_start' => 'Jump Start',
                            'tire_service' => 'Tire Service',
                            'fuel_delivery' => 'Fuel Delivery',
                            'mobile_repair' => 'Mobile Repair',
                            'winch_recovery' => 'Winch Recovery',
                            'other' => 'Other',
                        ];
                        foreach ($categories as $val => $label): ?>
                            <option value="<?= $val ?>" <?= $request['service_category'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Vehicle Info -->
            <div style="margin-top:20px">
                <h6 style="font-size:13px;font-weight:600;color:var(--text-secondary);margin-bottom:12px"><i class="fas fa-car" style="margin-right:6px"></i> Vehicle Information</h6>
                <div class="row g-3">
                    <div class="col-md-2">
                        <label for="edit_vehicle_year" class="form-label">Year</label>
                        <input type="number" class="form-control" id="edit_vehicle_year" name="vehicle_year"
                               value="<?= htmlspecialchars($request['vehicle_year'] ?? '') ?>" min="1900" max="2100">
                    </div>
                    <div class="col-md-3">
                        <label for="edit_vehicle_make" class="form-label">Make</label>
                        <input type="text" class="form-control" id="edit_vehicle_make" name="vehicle_make"
                               value="<?= htmlspecialchars($request['vehicle_make'] ?? '') ?>" placeholder="e.g. Toyota">
                    </div>
                    <div class="col-md-3">
                        <label for="edit_vehicle_model" class="form-label">Model</label>
                        <input type="text" class="form-control" id="edit_vehicle_model" name="vehicle_model"
                               value="<?= htmlspecialchars($request['vehicle_model'] ?? '') ?>" placeholder="e.g. Camry">
                    </div>
                    <div class="col-md-2">
                        <label for="edit_vehicle_color" class="form-label">Color</label>
                        <input type="text" class="form-control" id="edit_vehicle_color" name="vehicle_color"
                               value="<?= htmlspecialchars($request['vehicle_color'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="edit_vehicle_plate" class="form-label">Plate</label>
                        <input type="text" class="form-control" id="edit_vehicle_plate" name="vehicle_plate"
                               value="<?= htmlspecialchars($request['vehicle_plate'] ?? '') ?>">
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-md-4">
                        <label for="edit_vehicle_vin" class="form-label">VIN</label>
                        <input type="text" class="form-control" id="edit_vehicle_vin" name="vehicle_vin"
                               value="<?= htmlspecialchars($request['vehicle_vin'] ?? '') ?>" maxlength="17" style="font-family:'JetBrains Mono',monospace;font-size:12px">
                    </div>
                    <div class="col-md-4">
                        <label for="edit_vehicle_condition" class="form-label">Vehicle Condition</label>
                        <select class="form-select" id="edit_vehicle_condition" name="vehicle_condition">
                            <?php
                            $conditions = [
                                'unknown' => 'Unknown',
                                'runs_drives' => 'Runs & Drives',
                                'runs_no_drive' => 'Runs, No Drive',
                                'no_start' => 'No Start',
                                'accident' => 'Accident',
                                'immobile' => 'Immobile',
                            ];
                            foreach ($conditions as $val => $label): ?>
                                <option value="<?= $val ?>" <?= ($request['vehicle_condition'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="edit_vehicle_drive_type" class="form-label">Drive Type</label>
                        <select class="form-select" id="edit_vehicle_drive_type" name="vehicle_drive_type">
                            <?php foreach (['Unknown','FWD','RWD','AWD','4WD'] as $dt): ?>
                                <option value="<?= $dt ?>" <?= ($request['vehicle_drive_type'] ?? '') === $dt ? 'selected' : '' ?>><?= $dt ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Location -->
            <div style="margin-top:20px">
                <h6 style="font-size:13px;font-weight:600;color:var(--text-secondary);margin-bottom:12px"><i class="fas fa-map-marker-alt" style="margin-right:6px"></i> Location</h6>
                <div class="row g-3">
                    <div class="col-md-8">
                        <label for="edit_service_address" class="form-label">Service Address *</label>
                        <textarea class="form-control" id="edit_service_address" name="service_address" rows="2" required><?= htmlspecialchars($request['service_address']) ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label for="edit_location_type" class="form-label">Location Type</label>
                        <select class="form-select" id="edit_location_type" name="location_type">
                            <?php
                            $locationTypes = [
                                'roadside' => 'Roadside',
                                'parking_lot' => 'Parking Lot',
                                'residence' => 'Residence',
                                'business' => 'Business',
                                'highway' => 'Highway',
                                'other' => 'Other',
                            ];
                            foreach ($locationTypes as $val => $label): ?>
                                <option value="<?= $val ?>" <?= ($request['location_type'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-md-8">
                        <label for="edit_tow_destination" class="form-label">Tow Destination</label>
                        <textarea class="form-control" id="edit_tow_destination" name="tow_destination" rows="2" placeholder="Where the vehicle needs to go..."><?= htmlspecialchars($request['tow_destination'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label for="edit_tow_distance_miles" class="form-label">Tow Distance (miles)</label>
                        <input type="number" class="form-control" id="edit_tow_distance_miles" name="tow_distance_miles"
                               value="<?= htmlspecialchars($request['tow_distance_miles'] ?? '') ?>" step="0.1" min="0">
                    </div>
                </div>
            </div>

            <!-- Issue Description -->
            <div style="margin-top:20px">
                <h6 style="font-size:13px;font-weight:600;color:var(--text-secondary);margin-bottom:12px"><i class="fas fa-align-left" style="margin-right:6px"></i> Issue Details</h6>
                <div class="mb-3">
                    <label for="edit_issue_description" class="form-label">Description *</label>
                    <textarea class="form-control" id="edit_issue_description" name="issue_description" rows="3" required><?= htmlspecialchars($request['issue_description']) ?></textarea>
                </div>
            </div>

            <!-- Priority & Cost -->
            <div style="margin-top:20px">
                <h6 style="font-size:13px;font-weight:600;color:var(--text-secondary);margin-bottom:12px"><i class="fas fa-sliders-h" style="margin-right:6px"></i> Priority & Cost</h6>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="edit_priority" class="form-label">Priority</label>
                        <select class="form-select" id="edit_priority" name="priority">
                            <?php
                            $priorities = ['P4' => 'Low (P4)', 'P3' => 'Normal (P3)', 'P2' => 'High (P2)', 'P1' => 'Urgent (P1)'];
                            foreach ($priorities as $val => $label): ?>
                                <option value="<?= $val ?>" <?= $request['priority'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="edit_estimated_cost" class="form-label">Estimated Cost</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="edit_estimated_cost" name="estimated_cost"
                                   value="<?= $request['estimated_cost'] ?>" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="edit_payment_method" class="form-label">Payment Method</label>
                        <select class="form-select" id="edit_payment_method" name="payment_method">
                            <?php
                            $methods = ['card' => 'Card', 'cash' => 'Cash', 'invoice' => 'Invoice', 'insurance' => 'Insurance', 'motor_club' => 'Motor Club'];
                            foreach ($methods as $val => $label): ?>
                                <option value="<?= $val ?>" <?= ($request['payment_method'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="edit_requested_eta" class="form-label">Requested ETA</label>
                        <input type="text" class="form-control" id="edit_requested_eta" name="requested_eta"
                               value="<?= htmlspecialchars($request['requested_eta'] ?? 'ASAP') ?>" placeholder="ASAP">
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div style="margin-top:20px">
                <h6 style="font-size:13px;font-weight:600;color:var(--text-secondary);margin-bottom:12px"><i class="fas fa-sticky-note" style="margin-right:6px"></i> Notes</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="edit_internal_notes" class="form-label">Internal Notes</label>
                        <textarea class="form-control" id="edit_internal_notes" name="internal_notes" rows="3" placeholder="Staff-only notes..."><?= htmlspecialchars($request['internal_notes'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label for="edit_customer_notes" class="form-label">Customer Notes</label>
                        <textarea class="form-control" id="edit_customer_notes" name="customer_notes" rows="3" placeholder="Notes from or for the customer..."><?= htmlspecialchars($request['customer_notes'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:28px;padding-top:20px;border-top:1px solid var(--border-medium)">
                <a href="?page=service-requests&action=view&id=<?= $request['id'] ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" name="edit_request" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

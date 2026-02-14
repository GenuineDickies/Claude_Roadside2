        <!-- ─── Section 4: Service Type ───────────────────────── -->
        <div class="intake-section" data-section="service" id="sec-service">
            <div class="intake-section-head" onclick="toggleSection(this)">
                <div class="left">
                    <span class="num">4</span>
                    <h2><i class="fas fa-wrench" style="color:var(--navy-300);margin-right:6px"></i> Service Classification</h2>
                </div>
                <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="intake-section-body">
                <label class="intake-label"><span class="req">*</span> Service Category</label>
                <div class="intake-cat-grid" id="categoryGrid">
                    <div class="intake-cat-card" data-val="towing" onclick="selectCategory(this)"><i class="fas fa-truck-pickup"></i><span class="name">Towing</span></div>
                    <div class="intake-cat-card" data-val="lockout" onclick="selectCategory(this)"><i class="fas fa-key"></i><span class="name">Lockout</span></div>
                    <div class="intake-cat-card" data-val="jump_start" onclick="selectCategory(this)"><i class="fas fa-car-battery"></i><span class="name">Jump Start</span></div>
                    <div class="intake-cat-card" data-val="tire_service" onclick="selectCategory(this)"><i class="fas fa-circle-notch"></i><span class="name">Tire Service</span></div>
                    <div class="intake-cat-card" data-val="fuel_delivery" onclick="selectCategory(this)"><i class="fas fa-gas-pump"></i><span class="name">Fuel Delivery</span></div>
                    <div class="intake-cat-card" data-val="mobile_repair" onclick="selectCategory(this)"><i class="fas fa-wrench"></i><span class="name">Mobile Repair</span></div>
                    <div class="intake-cat-card" data-val="winch_recovery" onclick="selectCategory(this)"><i class="fas fa-truck-monster"></i><span class="name">Winch / Recovery</span></div>
                    <div class="intake-cat-card" data-val="other" onclick="selectCategory(this)"><i class="fas fa-ellipsis-h"></i><span class="name">Other</span></div>
                </div>
                <input type="hidden" name="service_category" id="serviceCategoryInput" required>

                <!-- Specific services (loads dynamically) -->
                <div id="specificServicesPanel" style="margin-top:16px;display:none">
                    <label class="intake-label">Specific Service</label>
                    <select class="intake-select" name="specific_services" id="specificServiceSelect">
                        <option value="">Select specific service...</option>
                    </select>
                </div>

                <div class="row g-3" style="margin-top:8px">
                    <div class="col-md-6">
                        <label class="intake-label">Vehicle Condition</label>
                        <select class="intake-select" name="vehicle_condition">
                            <option value="unknown">Unknown</option>
                            <option value="runs_drives">Runs & Drives</option>
                            <option value="runs_no_drive">Runs, Won't Drive</option>
                            <option value="no_start">No Start</option>
                            <option value="accident">Accident</option>
                            <option value="immobile">Immobile</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="intake-label">Vehicle Accessible?</label>
                        <select class="intake-select" name="vehicle_accessible">
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="intake-label">Keys Available?</label>
                        <select class="intake-select" name="keys_available">
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="intake-label">Passengers</label>
                        <input type="number" class="intake-input mono" name="passengers" value="0" min="0" max="20">
                    </div>
                    <div class="col-12">
                        <label class="intake-label"><span class="req">*</span> Issue Description</label>
                        <textarea class="intake-textarea" name="issue_description" id="issueDescription" rows="3" placeholder="Describe the issue... supports shorthand: js, ft lr, tow 15mi, lo, fuel, diag" required></textarea>
                        <div class="intake-shorthand-hint">
                            Shorthand: <code>js</code> jump start · <code>ft lr</code> flat tire left rear · <code>tow 15mi</code> · <code>lo</code> lockout · <code>fuel</code> · <code>diag</code> diagnostic
                        </div>
                        <div id="shorthandSuggestions"></div>
                    </div>
                </div>
            </div>
        </div>

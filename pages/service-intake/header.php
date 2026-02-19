<!-- Service Intake Header + Quick Entry Card — included by service-intake.php -->
<form id="intakeForm" autocomplete="off">

<input type="hidden" name="customer_id" id="selectedCustomerId" value="">

<!-- Header -->
<div class="intake-header">
    <div class="intake-header-left">
        <i class="fas fa-headset" style="font-size:26px;color:var(--navy-300)"></i>
        <div>
            <h1>New Service Request</h1>
            <p class="subtitle">Dispatch Intake Form — <span id="intakeTicketPreview" style="font-family:'JetBrains Mono',monospace;color:var(--navy-300)">RR-<?php echo date('ymd'); ?>-###-01</span></p>
        </div>
    </div>
    <div class="intake-header-actions">
        <label class="rapid-toggle" id="rapidToggle" title="Emergency mode — only phone, location, category & priority required">
            <input type="checkbox" name="rapid_dispatch" value="1" id="rapidDispatchCheck">
            <span class="dot"></span>
            <span class="label"><i class="fas fa-bolt"></i> Rapid Dispatch</span>
        </label>
        <a href="?page=service-requests" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
</div>

<div class="intake-layout">

    <!-- ═══ LEFT: Form Sections ═══ -->
    <div class="intake-form-center">

        <!-- ─── Quick Entry Card: All Required Fields ─────────── -->
        <div class="intake-quick-entry" id="quickEntryCard">
            <div class="intake-quick-entry-head">
                <div class="qe-icon"><i class="fas fa-bolt"></i></div>
                <div>
                    <h2>Quick Entry</h2>
                    <div class="qe-sub">All required fields — fill here or in detailed sections below</div>
                </div>
            </div>
            <div class="intake-quick-entry-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="intake-label"><span class="req">*</span> Phone Number</label>
                        <input type="tel" class="intake-input mono phone-masked" id="qePhone" value="(   )    -    ">
                    </div>
                    <div class="col-md-4">
                        <label class="intake-label"><span class="req">*</span> First Name</label>
                        <input type="text" class="intake-input" id="qeFirstName" placeholder="John">
                    </div>
                    <div class="col-md-4">
                        <label class="intake-label"><span class="req">*</span> Last Name</label>
                        <input type="text" class="intake-input" id="qeLastName" placeholder="Doe">
                    </div>
                </div>
                <div class="row g-3" style="margin-top:0">
                    <div class="col-md-6">
                        <label class="intake-label"><span class="req">*</span> Street Address</label>
                        <input type="text" class="intake-input" id="qeStreet1" placeholder="123 Main St">
                    </div>
                    <div class="col-md-6">
                        <label class="intake-label">Street Address 2</label>
                        <input type="text" class="intake-input" id="qeStreet2" placeholder="Apt, Suite, etc.">
                    </div>
                    <div class="col-md-6">
                        <label class="intake-label"><span class="req">*</span> City</label>
                        <input type="text" class="intake-input" id="qeCity" placeholder="Miami">
                    </div>
                    <div class="col-md-6">
                        <label class="intake-label"><span class="req">*</span> State</label>
                        <select class="intake-select" id="qeState">
                            <option value="">Select State</option>
                            <option value="AL">Alabama</option>
                            <option value="AK">Alaska</option>
                            <option value="AZ">Arizona</option>
                            <option value="AR">Arkansas</option>
                            <option value="CA">California</option>
                            <option value="CO">Colorado</option>
                            <option value="CT">Connecticut</option>
                            <option value="DE">Delaware</option>
                            <option value="FL">Florida</option>
                            <option value="GA">Georgia</option>
                            <option value="HI">Hawaii</option>
                            <option value="ID">Idaho</option>
                            <option value="IL">Illinois</option>
                            <option value="IN">Indiana</option>
                            <option value="IA">Iowa</option>
                            <option value="KS">Kansas</option>
                            <option value="KY">Kentucky</option>
                            <option value="LA">Louisiana</option>
                            <option value="ME">Maine</option>
                            <option value="MD">Maryland</option>
                            <option value="MA">Massachusetts</option>
                            <option value="MI">Michigan</option>
                            <option value="MN">Minnesota</option>
                            <option value="MS">Mississippi</option>
                            <option value="MO">Missouri</option>
                            <option value="MT">Montana</option>
                            <option value="NE">Nebraska</option>
                            <option value="NV">Nevada</option>
                            <option value="NH">New Hampshire</option>
                            <option value="NJ">New Jersey</option>
                            <option value="NM">New Mexico</option>
                            <option value="NY">New York</option>
                            <option value="NC">North Carolina</option>
                            <option value="ND">North Dakota</option>
                            <option value="OH">Ohio</option>
                            <option value="OK">Oklahoma</option>
                            <option value="OR">Oregon</option>
                            <option value="PA">Pennsylvania</option>
                            <option value="RI">Rhode Island</option>
                            <option value="SC">South Carolina</option>
                            <option value="SD">South Dakota</option>
                            <option value="TN">Tennessee</option>
                            <option value="TX">Texas</option>
                            <option value="UT">Utah</option>
                            <option value="VT">Vermont</option>
                            <option value="VA">Virginia</option>
                            <option value="WA">Washington</option>
                            <option value="WV">West Virginia</option>
                            <option value="WI">Wisconsin</option>
                            <option value="WY">Wyoming</option>
                            <option value="DC">District of Columbia</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="intake-label"><span class="req">*</span> Service Category</label>
                        <div class="qe-cat-grid" id="qeCatGrid">
                            <div class="qe-cat-pill" data-val="towing" onclick="qeSelectCategory(this)"><i class="fas fa-truck-pickup"></i> Towing</div>
                            <div class="qe-cat-pill" data-val="lockout" onclick="qeSelectCategory(this)"><i class="fas fa-key"></i> Lockout</div>
                            <div class="qe-cat-pill" data-val="jump_start" onclick="qeSelectCategory(this)"><i class="fas fa-car-battery"></i> Jump Start</div>
                            <div class="qe-cat-pill" data-val="tire_service" onclick="qeSelectCategory(this)"><i class="fas fa-circle-notch"></i> Tire</div>
                            <div class="qe-cat-pill" data-val="fuel_delivery" onclick="qeSelectCategory(this)"><i class="fas fa-gas-pump"></i> Fuel</div>
                            <div class="qe-cat-pill" data-val="mobile_repair" onclick="qeSelectCategory(this)"><i class="fas fa-wrench"></i> Repair</div>
                            <div class="qe-cat-pill" data-val="winch_recovery" onclick="qeSelectCategory(this)"><i class="fas fa-truck-monster"></i> Winch</div>
                            <div class="qe-cat-pill" data-val="other" onclick="qeSelectCategory(this)"><i class="fas fa-ellipsis-h"></i> Other</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="intake-label"><span class="req">*</span> Issue Description</label>
                        <textarea class="intake-textarea" id="qeDescription" rows="2" placeholder="Describe the issue..."></textarea>
                    </div>
                    <div class="col-md-3 col-6">
                        <label class="intake-label"><span class="req">*</span> Year</label>
                        <input type="text" class="intake-input mono" id="qeVehicleYear" name="vehicle_year" placeholder="2024" maxlength="4" pattern="[0-9]{4}" required>
                    </div>
                    <div class="col-md-3 col-6">
                        <label class="intake-label"><span class="req">*</span> Make</label>
                        <input type="text" class="intake-input" id="qeVehicleMake" name="vehicle_make" placeholder="Toyota" required>
                    </div>
                    <div class="col-md-3 col-6">
                        <label class="intake-label"><span class="req">*</span> Model</label>
                        <input type="text" class="intake-input" id="qeVehicleModel" name="vehicle_model" placeholder="Camry" required>
                    </div>
                    <div class="col-md-3 col-6">
                        <label class="intake-label"><span class="req">*</span> Color</label>
                        <input type="text" class="intake-input" id="qeVehicleColor" name="vehicle_color" placeholder="Silver" required>
                    </div>
                </div>
            </div>
        </div>

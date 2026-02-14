<!-- Sections 1-3: Customer, Vehicle, Location — included by service-intake.php -->

        <!-- ─── Section 1: Customer Info ──────────────────────── -->
        <div class="intake-section open" data-section="customer" id="sec-customer">
            <div class="intake-section-head" onclick="toggleSection(this)">
                <div class="left">
                    <span class="num">1</span>
                    <h2><i class="fas fa-user" style="color:var(--navy-300);margin-right:6px"></i> Customer Information</h2>
                </div>
                <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="intake-section-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="intake-label"><span class="req">*</span> Phone Number</label>
                        <input type="tel" class="intake-input mono phone-masked" name="customer_phone" id="customerPhone" value="(   )    -    " required>
                        <div class="intake-error-msg">Phone number is required</div>
                    </div>
                    <div class="col-md-6">
                        <label class="intake-label">Alt Phone</label>
                        <input type="tel" class="intake-input mono phone-masked" name="alt_phone" value="(   )    -    ">
                    </div>
                    <div class="col-md-6">
                        <label class="intake-label"><span class="req">*</span> Full Name</label>
                        <input type="text" class="intake-input" name="customer_name" id="customerName" placeholder="First Last" required>
                        <div class="intake-error-msg">Customer name is required</div>
                    </div>
                    <div class="col-md-6">
                        <label class="intake-label">Email</label>
                        <input type="email" class="intake-input" name="customer_email" placeholder="email@example.com">
                    </div>
                    <div class="col-md-6">
                        <label class="intake-label">Customer Type</label>
                        <select class="intake-select" name="customer_type">
                            <option value="individual">Individual</option>
                            <option value="fleet">Fleet</option>
                            <option value="insurance">Insurance</option>
                            <option value="motor_club">Motor Club</option>
                            <option value="commercial">Commercial</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="intake-label">Account / Policy #</label>
                        <input type="text" class="intake-input mono" name="account_number" placeholder="Policy or account number">
                    </div>
                    <div class="col-md-6">
                        <label class="intake-label">Caller Relation</label>
                        <div class="intake-radio-group">
                            <label class="intake-radio selected"><input type="radio" name="caller_relation" value="owner" checked> Owner</label>
                            <label class="intake-radio"><input type="radio" name="caller_relation" value="driver"> Driver</label>
                            <label class="intake-radio"><input type="radio" name="caller_relation" value="passenger"> Passenger</label>
                            <label class="intake-radio"><input type="radio" name="caller_relation" value="third_party"> 3rd Party</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── Section 2: Vehicle Info ───────────────────────── -->
        <div class="intake-section intake-conditional" data-section="vehicle" id="sec-vehicle">
            <div class="intake-section-head" onclick="toggleSection(this)">
                <div class="left">
                    <span class="num">2</span>
                    <h2><i class="fas fa-car" style="color:var(--navy-300);margin-right:6px"></i> Vehicle Information</h2>
                </div>
                <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="intake-section-body">
                <div id="vehicleSelectPanel" style="display:none;margin-bottom:12px">
                    <label class="intake-label">Select Customer Vehicle</label>
                    <select class="intake-select" id="vehicleSelect"><option value="">+ Enter new vehicle</option></select>
                </div>
                <div class="row g-3" id="vehicleFields">
                    <div class="col-md-3">
                        <label class="intake-label">Year</label>
                        <input type="number" class="intake-input mono" name="vehicle_year" min="1900" max="2030" placeholder="2024">
                    </div>
                    <div class="col-md-4">
                        <label class="intake-label">Make</label>
                        <input type="text" class="intake-input" name="vehicle_make" placeholder="Toyota, Ford...">
                    </div>
                    <div class="col-md-5">
                        <label class="intake-label">Model</label>
                        <input type="text" class="intake-input" name="vehicle_model" placeholder="Camry, F-150...">
                    </div>
                    <div class="col-md-3">
                        <label class="intake-label">Color</label>
                        <input type="text" class="intake-input" name="vehicle_color" placeholder="White">
                    </div>
                    <div class="col-md-4">
                        <label class="intake-label">License Plate</label>
                        <input type="text" class="intake-input mono" name="vehicle_plate" placeholder="ABC-1234" style="text-transform:uppercase">
                    </div>
                    <div class="col-md-5">
                        <label class="intake-label">VIN</label>
                        <input type="text" class="intake-input mono" name="vehicle_vin" maxlength="17" placeholder="17-char VIN" style="text-transform:uppercase">
                    </div>
                    <div class="col-md-4">
                        <label class="intake-label">Mileage</label>
                        <input type="number" class="intake-input mono" name="vehicle_mileage" placeholder="85,000">
                    </div>
                    <div class="col-md-4">
                        <label class="intake-label">Drive Type</label>
                        <select class="intake-select" name="vehicle_drive_type">
                            <option value="Unknown">Unknown</option>
                            <option value="FWD">FWD</option>
                            <option value="RWD">RWD</option>
                            <option value="AWD">AWD</option>
                            <option value="4WD">4WD</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── Section 3: Service Location ───────────────────── -->
        <div class="intake-section" data-section="location" id="sec-location">
            <div class="intake-section-head" onclick="toggleSection(this)">
                <div class="left">
                    <span class="num">3</span>
                    <h2><i class="fas fa-map-marker-alt" style="color:var(--navy-300);margin-right:6px"></i> Service Location</h2>
                </div>
                <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="intake-section-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="intake-label"><span class="req">*</span> Service Address</label>
                        <input type="text" class="intake-input" name="service_address" id="serviceAddress" placeholder="123 Main St, City, State or describe location" required>
                        <div class="intake-error-msg">Service address is required</div>
                        <input type="hidden" name="service_lat" id="serviceLat">
                        <input type="hidden" name="service_lng" id="serviceLng">
                    </div>
                    <div class="col-md-4">
                        <label class="intake-label">Location Type</label>
                        <select class="intake-select" name="location_type" id="locationType">
                            <option value="roadside">Roadside</option>
                            <option value="parking_lot">Parking Lot</option>
                            <option value="residence">Residence</option>
                            <option value="business">Business</option>
                            <option value="highway">Highway</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-4 intake-conditional" id="highwayFields">
                        <label class="intake-label">Highway / Road</label>
                        <input type="text" class="intake-input" name="highway_name" placeholder="I-95, US-1">
                    </div>
                    <div class="col-md-4 intake-conditional" id="directionField">
                        <label class="intake-label">Direction of Travel</label>
                        <select class="intake-select" name="direction_travel">
                            <option value="">—</option>
                            <option value="NB">Northbound</option>
                            <option value="SB">Southbound</option>
                            <option value="EB">Eastbound</option>
                            <option value="WB">Westbound</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="intake-label">Location Details</label>
                        <textarea class="intake-textarea" name="location_details" rows="2" placeholder="Near mile marker 42, under the overpass, etc."></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="intake-label">Safe Location?</label>
                        <div class="intake-radio-group">
                            <label class="intake-radio selected"><input type="radio" name="safe_location" value="1" checked> Yes — Safe</label>
                            <label class="intake-radio"><input type="radio" name="safe_location" value="0"> No — Unsafe</label>
                        </div>
                        <div class="intake-safety-warn" id="safetyWarning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>Unsafe location — priority will be auto-escalated to P1 Emergency</span>
                        </div>
                    </div>
                </div>

                <!-- Tow Destination (conditional) -->
                <div class="intake-conditional" id="towDestFields">
                    <hr style="border-color:var(--border-subtle);margin:16px 0">
                    <label class="intake-label" style="font-size:13px;color:var(--navy-300)"><i class="fas fa-truck-pickup"></i> Tow Destination</label>
                    <div class="row g-3" style="margin-top:4px">
                        <div class="col-md-8">
                            <label class="intake-label">Destination Address</label>
                            <input type="text" class="intake-input" name="tow_destination" placeholder="Shop or drop-off address">
                            <input type="hidden" name="tow_dest_lat">
                            <input type="hidden" name="tow_dest_lng">
                        </div>
                        <div class="col-md-4">
                            <label class="intake-label">Est. Distance (mi)</label>
                            <input type="number" class="intake-input mono" name="tow_distance" step="0.1" placeholder="15.0" id="towDistance">
                        </div>
                    </div>
                </div>
            </div>
        </div>

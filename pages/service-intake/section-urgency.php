        <!-- ─── Section 5: Urgency & Priority ─────────────────── -->
        <div class="intake-section" data-section="urgency" id="sec-urgency">
            <div class="intake-section-head" onclick="toggleSection(this)">
                <div class="left">
                    <span class="num">5</span>
                    <h2><i class="fas fa-bolt" style="color:var(--navy-300);margin-right:6px"></i> Urgency & Priority</h2>
                </div>
                <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="intake-section-body">
                <label class="intake-label"><span class="req">*</span> Priority Level</label>
                <div class="intake-priority-grid">
                    <div class="intake-priority-card" data-val="P1" onclick="selectPriority(this)"><div class="level">P1</div><div class="desc">Emergency</div></div>
                    <div class="intake-priority-card" data-val="P2" onclick="selectPriority(this)"><div class="level">P2</div><div class="desc">Urgent</div></div>
                    <div class="intake-priority-card selected" data-val="P3" onclick="selectPriority(this)"><div class="level">P3</div><div class="desc">Normal</div></div>
                    <div class="intake-priority-card" data-val="P4" onclick="selectPriority(this)"><div class="level">P4</div><div class="desc">Scheduled</div></div>
                </div>
                <input type="hidden" name="priority" id="priorityInput" value="P3">

                <div class="row g-3" style="margin-top:12px">
                    <div class="col-md-4">
                        <label class="intake-label">Requested ETA</label>
                        <select class="intake-select" name="requested_eta">
                            <option value="ASAP">ASAP</option>
                            <option value="30min">Within 30 min</option>
                            <option value="1hr">Within 1 hour</option>
                            <option value="2hr">Within 2 hours</option>
                            <option value="scheduled">Scheduled</option>
                        </select>
                    </div>
                    <div class="col-md-4 intake-conditional" id="scheduledDateField">
                        <label class="intake-label">Scheduled Date/Time</label>
                        <input type="datetime-local" class="intake-input mono" name="scheduled_datetime">
                    </div>
                    <div class="col-md-4">
                        <label class="intake-label">Time Sensitivity</label>
                        <input type="text" class="intake-input" name="time_sensitivity" placeholder="e.g. flight to catch at 6pm">
                    </div>
                </div>

                <!-- Hazard Conditions -->
                <div style="margin-top:16px">
                    <label class="intake-label">Hazard Conditions</label>
                    <div class="intake-hazard-grid">
                        <label class="intake-check"><input type="checkbox" name="hazard[]" value="accident_scene"> Accident Scene</label>
                        <label class="intake-check"><input type="checkbox" name="hazard[]" value="fire_risk"> Fire Risk</label>
                        <label class="intake-check"><input type="checkbox" name="hazard[]" value="fluid_leak"> Fluid Leak</label>
                        <label class="intake-check"><input type="checkbox" name="hazard[]" value="traffic_hazard"> Traffic Hazard</label>
                        <label class="intake-check"><input type="checkbox" name="hazard[]" value="weather"> Severe Weather</label>
                        <label class="intake-check"><input type="checkbox" name="hazard[]" value="low_visibility"> Low Visibility</label>
                    </div>
                </div>
            </div>
        </div>

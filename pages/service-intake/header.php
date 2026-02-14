<!-- Service Intake Header + Quick Entry Card — included by service-intake.php -->
<form id="intakeForm" autocomplete="off">

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
                        <label class="intake-label"><span class="req">*</span> Full Name</label>
                        <input type="text" class="intake-input" id="qeName" placeholder="First Last">
                    </div>
                    <div class="col-md-4">
                        <label class="intake-label"><span class="req">*</span> Service Address</label>
                        <input type="text" class="intake-input" id="qeAddress" placeholder="123 Main St, City, State">
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
                </div>
            </div>
        </div>

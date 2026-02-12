<?php
/**
 * Service Request Intake Form — Full Dispatch Interface
 * 47 fields across 7 collapsible sections, Rapid Dispatch mode,
 * customer phone lookup, shorthand parser, auto-cost estimation
 */

// Fetch data for dropdowns
$customersForDropdown = $pdo->query("SELECT id, first_name, last_name, phone FROM customers ORDER BY first_name")->fetchAll();
$techsForDropdown = $pdo->query("SELECT id, first_name, last_name, specialization, status FROM technicians ORDER BY first_name")->fetchAll();
?>

<style>
/* ── Service Intake — Scoped Styles ──────────────────────────────── */

/* Textured page background — subtle dot grid overlay */
.rr-content:has(.intake-header) {
    background:
        radial-gradient(circle, rgba(255,255,255,0.02) 1px, transparent 1px),
        linear-gradient(180deg, var(--bg-primary) 0%, #0A0C10 100%);
    background-size: 20px 20px, 100% 100%;
}

.intake-header {
    background:
        linear-gradient(135deg, rgba(43,94,167,0.08) 0%, rgba(18,21,27,0.95) 50%, rgba(43,94,167,0.04) 100%),
        linear-gradient(180deg, var(--bg-surface) 0%, var(--bg-secondary) 100%);
    border-bottom: 2px solid var(--navy-500);
    padding: 20px 28px;
    margin: -28px -28px 24px -28px;
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;
    position: relative;
    overflow: hidden;
}
.intake-header::before {
    content: '';
    position: absolute; inset: 0;
    background:
        repeating-linear-gradient(90deg, rgba(43,94,167,0.03) 0px, rgba(43,94,167,0.03) 1px, transparent 1px, transparent 60px),
        repeating-linear-gradient(0deg, rgba(43,94,167,0.03) 0px, rgba(43,94,167,0.03) 1px, transparent 1px, transparent 60px);
    pointer-events: none;
}
.intake-header-left { display: flex; align-items: center; gap: 14px; position: relative; z-index: 1; }
.intake-header h1 { font-size: 24px; font-weight: 700; color: var(--navy-300); letter-spacing: -0.5px; margin: 0; }
.intake-header .subtitle { font-size: 13px; color: #A8B0C4; margin: 2px 0 0; }
.intake-header-actions { display: flex; gap: 10px; align-items: center; position: relative; z-index: 1; }

/* Rapid Dispatch toggle */
.rapid-toggle { display: flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 6px; background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.25); cursor: pointer; transition: all 0.2s; }
.rapid-toggle:hover { background: rgba(239,68,68,0.12); }
.rapid-toggle.active { background: rgba(239,68,68,0.18); border-color: var(--red-500); }
.rapid-toggle .label { font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--red-500); }
.rapid-toggle input { display: none; }
.rapid-toggle .dot { width: 10px; height: 10px; border-radius: 50%; background: var(--text-tertiary); transition: all 0.2s; }
.rapid-toggle.active .dot { background: var(--red-500); box-shadow: 0 0 8px rgba(239,68,68,0.5); }

/* Layout */
.intake-layout { display: grid; grid-template-columns: 1fr 300px; gap: 20px; }
@media (max-width: 992px) { .intake-layout { grid-template-columns: 1fr; } .intake-sidebar-right { display: none; } }

/* Sidebar panels */
.intake-sidebar-left, .intake-sidebar-right { display: flex; flex-direction: column; gap: 16px; }
.intake-panel {
    background: linear-gradient(160deg, var(--bg-surface) 0%, rgba(18,21,27,0.9) 100%);
    border: 1px solid var(--border-medium);
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2), inset 0 1px 0 rgba(255,255,255,0.03);
}
.intake-panel-head {
    padding: 14px 16px;
    border-bottom: 1px solid var(--border-subtle);
    display: flex; align-items: center; gap: 8px;
    background: rgba(0,0,0,0.15);
}
.intake-panel-head h3 { font-size: 13px; font-weight: 600; margin: 0; color: #BCC3D4; text-transform: uppercase; letter-spacing: 0.5px; }
.intake-panel-body { padding: 12px 16px; }

/* Customer lookup */
.intake-search { position: relative; }
.intake-search input { width: 100%; background: var(--bg-primary); border: 1px solid var(--border-medium); border-radius: 6px; padding: 8px 12px 8px 32px; color: var(--text-primary); font-size: 13px; transition: all 0.2s; }
.intake-search input:focus { border-color: var(--navy-400); outline: none; box-shadow: 0 0 0 3px rgba(43,94,167,0.15), 0 0 12px rgba(43,94,167,0.08); }
.intake-search .icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-secondary); font-size: 12px; }
.intake-search-results { margin-top: 8px; max-height: 300px; overflow-y: auto; }
.intake-cust-card { padding: 10px 12px; border: 1px solid var(--border-subtle); border-radius: 6px; margin-bottom: 6px; cursor: pointer; transition: all 0.2s; background: rgba(0,0,0,0.1); }
.intake-cust-card:hover { border-color: var(--navy-500); background: rgba(43,94,167,0.06); box-shadow: 0 0 10px rgba(43,94,167,0.1); }
.intake-cust-card .name { font-size: 13px; font-weight: 600; color: var(--text-primary); }
.intake-cust-card .phone { font-size: 12px; font-family: 'JetBrains Mono', monospace; color: #A8B0C4; }
.intake-cust-card .meta { font-size: 11px; color: var(--text-secondary); margin-top: 2px; }

/* Accordion sections */
.intake-section {
    background: linear-gradient(160deg, var(--bg-surface) 0%, rgba(18,21,27,0.85) 100%);
    border: 1px solid var(--border-medium);
    border-radius: 10px;
    margin-bottom: 12px;
    overflow: hidden;
    transition: border-color 0.2s, box-shadow 0.2s;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
.intake-section:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.25); }
.intake-section.error { border-color: var(--red-500); box-shadow: 0 0 12px rgba(239,68,68,0.1); }
.intake-section.complete { border-color: rgba(34,197,94,0.3); box-shadow: 0 0 12px rgba(34,197,94,0.06); }
.intake-section.open { border-color: rgba(43,94,167,0.3); }
.intake-section-head {
    padding: 14px 20px;
    cursor: pointer;
    display: flex; align-items: center; justify-content: space-between;
    user-select: none;
    transition: background 0.15s;
}
.intake-section-head:hover { background: rgba(255,255,255,0.02); }
.intake-section-head .left { display: flex; align-items: center; gap: 12px; }
.intake-section-head .num {
    width: 28px; height: 28px;
    border-radius: 50%;
    background: rgba(43,94,167,0.15);
    color: var(--navy-300);
    font-size: 12px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    font-family: 'JetBrains Mono', monospace;
    box-shadow: 0 0 8px rgba(43,94,167,0.12);
    transition: all 0.2s;
}
.intake-section.open .num { background: rgba(43,94,167,0.25); box-shadow: 0 0 12px rgba(43,94,167,0.2); }
.intake-section.complete .num { background: rgba(34,197,94,0.15); color: var(--green-500); box-shadow: 0 0 8px rgba(34,197,94,0.15); }
.intake-section.error .num { background: rgba(239,68,68,0.15); color: var(--red-500); box-shadow: 0 0 8px rgba(239,68,68,0.15); }
.intake-section-head h2 { font-size: 15px; font-weight: 600; margin: 0; color: #F0F2F8; }
.intake-section-head .chevron { color: var(--text-secondary); transition: transform 0.2s; }
.intake-section.open .chevron { transform: rotate(180deg); color: var(--navy-300); }
.intake-section-body { padding: 0 20px 20px; display: none; }
.intake-section.open .intake-section-body { display: block; }
.intake-section-body .row { margin-top: 12px; }

/* Form elements */
.intake-label { font-size: 12px; font-weight: 600; color: #BCC3D4; margin-bottom: 4px; display: flex; align-items: center; gap: 4px; }
.intake-label .req { color: var(--red-500); }
.intake-input, .intake-select, .intake-textarea {
    width: 100%;
    background: rgba(8,10,14,0.6);
    border: 1px solid var(--border-medium);
    border-radius: 6px; padding: 8px 12px;
    color: #F0F2F8; font-size: 13px;
    font-family: 'DM Sans', sans-serif;
    transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
}
.intake-input:focus, .intake-select:focus, .intake-textarea:focus {
    border-color: var(--navy-400);
    outline: none;
    box-shadow: 0 0 0 3px rgba(43,94,167,0.15), 0 0 12px rgba(43,94,167,0.06);
    background: rgba(8,10,14,0.8);
}
.intake-input.mono, .intake-input[type="tel"] { font-family: 'JetBrains Mono', monospace; font-size: 13px; }
.intake-input.invalid { border-color: var(--red-500); box-shadow: 0 0 0 3px rgba(239,68,68,0.1); }
.intake-error-msg { font-size: 11px; color: var(--red-500); margin-top: 2px; display: none; }
.intake-input.invalid + .intake-error-msg { display: block; }
.intake-textarea { resize: vertical; min-height: 60px; }
.intake-select { cursor: pointer; }

/* Radio / checkbox groups */
.intake-radio-group, .intake-check-group { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 4px; }
.intake-radio, .intake-check { display: flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 6px; border: 1px solid var(--border-medium); cursor: pointer; transition: all 0.2s; font-size: 12px; color: #BCC3D4; background: rgba(0,0,0,0.1); }
.intake-radio:hover, .intake-check:hover { border-color: var(--navy-500); background: rgba(43,94,167,0.06); }
.intake-radio.selected, .intake-check.selected { border-color: var(--navy-500); background: rgba(43,94,167,0.12); color: var(--navy-300); box-shadow: 0 0 8px rgba(43,94,167,0.1); }
.intake-radio input, .intake-check input { display: none; }

/* Category grid */
.intake-cat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-top: 8px; }
@media (max-width: 768px) { .intake-cat-grid { grid-template-columns: repeat(2, 1fr); } }
.intake-cat-card {
    padding: 14px 10px; border: 1px solid var(--border-medium); border-radius: 8px;
    text-align: center; cursor: pointer; transition: all 0.2s;
    background: rgba(0,0,0,0.1);
}
.intake-cat-card:hover { border-color: var(--navy-500); background: rgba(43,94,167,0.06); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.3); }
.intake-cat-card.selected { border-color: var(--navy-500); background: rgba(43,94,167,0.12); box-shadow: 0 0 16px rgba(43,94,167,0.12); }
.intake-cat-card i { font-size: 22px; color: var(--navy-300); margin-bottom: 6px; display: block; }
.intake-cat-card .name { font-size: 12px; font-weight: 600; color: var(--text-primary); }

/* Priority selector */
.intake-priority-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-top: 8px; }
.intake-priority-card { padding: 10px 8px; border-radius: 6px; text-align: center; cursor: pointer; border: 1px solid var(--border-medium); transition: all 0.2s; background: rgba(0,0,0,0.1); }
.intake-priority-card .level { font-size: 16px; font-weight: 700; font-family: 'JetBrains Mono', monospace; }
.intake-priority-card .desc { font-size: 10px; text-transform: uppercase; letter-spacing: 0.3px; margin-top: 2px; }
.intake-priority-card[data-val="P1"] { color: var(--red-500); }
.intake-priority-card[data-val="P1"].selected { background: rgba(239,68,68,0.12); border-color: var(--red-500); box-shadow: 0 0 12px rgba(239,68,68,0.15); }
.intake-priority-card[data-val="P2"] { color: var(--amber-500); }
.intake-priority-card[data-val="P2"].selected { background: rgba(245,158,11,0.12); border-color: var(--amber-500); box-shadow: 0 0 12px rgba(245,158,11,0.12); }
.intake-priority-card[data-val="P3"] { color: var(--blue-500); }
.intake-priority-card[data-val="P3"].selected { background: rgba(59,130,246,0.12); border-color: var(--blue-500); box-shadow: 0 0 12px rgba(59,130,246,0.12); }
.intake-priority-card[data-val="P4"] { color: var(--text-secondary); }
.intake-priority-card[data-val="P4"].selected { background: rgba(138,146,166,0.12); border-color: var(--text-secondary); }

/* Shorthand hint */
.intake-shorthand-hint { font-size: 11px; color: var(--text-secondary); margin-top: 4px; }
.intake-shorthand-hint code { font-family: 'JetBrains Mono', monospace; font-size: 11px; background: rgba(43,94,167,0.1); padding: 1px 5px; border-radius: 3px; color: var(--navy-300); }
.intake-shorthand-suggestion { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.25); border-radius: 4px; font-size: 12px; color: var(--green-500); margin: 4px 4px 0 0; cursor: pointer; }

/* Safety indicator */
.intake-safety-warn { padding: 10px 14px; background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.25); border-radius: 6px; margin-top: 8px; display: none; }
.intake-safety-warn.visible { display: flex; align-items: center; gap: 10px; }
.intake-safety-warn i { color: var(--red-500); font-size: 16px; }
.intake-safety-warn span { font-size: 12px; color: var(--red-500); font-weight: 500; }

/* Summary panel */
.intake-summary-item { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid var(--border-subtle); font-size: 12px; }
.intake-summary-item .lbl { color: #A8B0C4; }
.intake-summary-item .val { color: #F0F2F8; font-weight: 600; font-family: 'JetBrains Mono', monospace; }
.intake-summary-total { display: flex; justify-content: space-between; padding: 10px 0; font-size: 14px; font-weight: 700; }
.intake-summary-total .val { color: var(--green-500); font-family: 'JetBrains Mono', monospace; text-shadow: 0 0 12px rgba(34,197,94,0.3); }

/* Submit bar */
.intake-submit-bar {
    position: sticky; bottom: 0;
    background: linear-gradient(180deg, var(--bg-surface) 0%, rgba(18,21,27,0.98) 100%);
    border-top: 2px solid var(--navy-500);
    padding: 16px 20px;
    margin: 24px -28px -28px -28px;
    display: flex; justify-content: space-between; align-items: center; gap: 12px;
    z-index: 10;
    box-shadow: 0 -4px 20px rgba(0,0,0,0.4);
    backdrop-filter: blur(8px);
}
.intake-submit-bar .status-text { font-size: 12px; color: #BCC3D4; }
.intake-submit-bar .actions { display: flex; gap: 10px; }

/* Hazard checkboxes */
.intake-hazard-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; margin-top: 6px; }
@media (max-width: 768px) { .intake-hazard-grid { grid-template-columns: repeat(2, 1fr); } }

/* Conditional sections hidden by default */
.intake-conditional { display: none; }
.intake-conditional.visible { display: block; }

/* Vehicle subfields */
.intake-vehicle-display { padding: 10px 14px; background: var(--bg-primary); border: 1px solid var(--border-medium); border-radius: 6px; margin-top: 8px; }
.intake-vehicle-display .make-model { font-size: 14px; font-weight: 600; color: var(--text-primary); }
.intake-vehicle-display .details { font-size: 12px; color: var(--text-secondary); margin-top: 2px; }

/* ── Quick Entry Card ─────────────────────────────────────────────── */
.intake-quick-entry {
    background:
        linear-gradient(135deg, rgba(43,94,167,0.10) 0%, rgba(18,21,27,0.95) 60%, rgba(43,94,167,0.06) 100%),
        linear-gradient(160deg, var(--bg-surface) 0%, rgba(18,21,27,0.9) 100%);
    border: 2px solid rgba(43,94,167,0.35);
    border-radius: 12px;
    margin-bottom: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3), 0 0 20px rgba(43,94,167,0.08);
    position: relative;
}
.intake-quick-entry::before {
    content: '';
    position: absolute; top: 0; left: 0; right: 0; height: 3px;
    background: linear-gradient(90deg, transparent, var(--navy-500), transparent);
}
.intake-quick-entry-head {
    padding: 14px 20px;
    display: flex; align-items: center; gap: 12px;
    border-bottom: 1px solid rgba(43,94,167,0.15);
    background: rgba(0,0,0,0.15);
}
.intake-quick-entry-head .qe-icon {
    width: 34px; height: 34px; border-radius: 8px;
    background: rgba(43,94,167,0.2);
    display: flex; align-items: center; justify-content: center;
    color: var(--navy-300); font-size: 15px;
    box-shadow: 0 0 12px rgba(43,94,167,0.15);
}
.intake-quick-entry-head h2 {
    font-size: 15px; font-weight: 700; margin: 0;
    color: var(--navy-300); letter-spacing: -0.3px;
}
.intake-quick-entry-head .qe-sub {
    font-size: 11px; color: var(--text-secondary); margin-top: 1px;
}
.intake-quick-entry-body {
    padding: 16px 20px 20px;
}
.intake-quick-entry-body .row { margin-bottom: 0; }
.intake-quick-entry-body .intake-label { font-size: 11px; }

/* Quick Entry category mini-grid */
.qe-cat-grid {
    display: flex; flex-wrap: wrap; gap: 6px; margin-top: 4px;
}
.qe-cat-pill {
    padding: 5px 12px; border-radius: 20px;
    border: 1px solid var(--border-medium);
    font-size: 11px; font-weight: 600;
    color: var(--text-secondary); cursor: pointer;
    transition: all 0.2s;
    background: rgba(0,0,0,0.15);
    display: flex; align-items: center; gap: 5px;
}
.qe-cat-pill:hover { border-color: var(--navy-500); color: var(--navy-300); background: rgba(43,94,167,0.08); }
.qe-cat-pill.selected { border-color: var(--navy-500); color: var(--navy-300); background: rgba(43,94,167,0.15); box-shadow: 0 0 8px rgba(43,94,167,0.12); }
.qe-cat-pill i { font-size: 10px; }

/* Mirror sync indicator */
.intake-input.synced, .intake-textarea.synced {
    border-color: rgba(43,94,167,0.4) !important;
    animation: qeSyncFlash 0.4s ease;
}
@keyframes qeSyncFlash {
    0% { box-shadow: 0 0 0 3px rgba(43,94,167,0.25); }
    100% { box-shadow: none; }
}
</style>

<!-- ═══════════════════════════════════════════════════════════════════
     INTAKE FORM HTML
     ═══════════════════════════════════════════════════════════════════ -->

<form id="intakeForm" autocomplete="off">

<!-- Header -->
<div class="intake-header">
    <div class="intake-header-left">
        <i class="fas fa-headset" style="font-size:26px;color:var(--navy-300)"></i>
        <div>
            <h1>New Service Request</h1>
            <p class="subtitle">Dispatch Intake Form — <span id="intakeTicketPreview" style="font-family:'JetBrains Mono',monospace;color:var(--navy-300)">RR-<?php echo date('Ymd'); ?>-####</span></p>
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

        <!-- ─── Section 6: Payment ────────────────────────────── -->
        <div class="intake-section intake-conditional" data-section="payment" id="sec-payment">
            <div class="intake-section-head" onclick="toggleSection(this)">
                <div class="left">
                    <span class="num">6</span>
                    <h2><i class="fas fa-credit-card" style="color:var(--navy-300);margin-right:6px"></i> Payment Method</h2>
                </div>
                <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="intake-section-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="intake-label">Payment Method</label>
                        <select class="intake-select" name="payment_method">
                            <option value="card">Credit/Debit Card</option>
                            <option value="cash">Cash</option>
                            <option value="invoice">Invoice (Bill Later)</option>
                            <option value="insurance">Insurance</option>
                            <option value="motor_club">Motor Club</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="intake-label">Estimated Cost</label>
                        <div style="position:relative">
                            <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-tertiary);font-family:'JetBrains Mono',monospace">$</span>
                            <input type="number" class="intake-input mono" name="estimated_cost" id="estimatedCost" step="0.01" value="0.00" style="padding-left:24px">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="intake-label">Price Quoted</label>
                        <div style="position:relative">
                            <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-tertiary);font-family:'JetBrains Mono',monospace">$</span>
                            <input type="number" class="intake-input mono" name="price_quoted" step="0.01" value="0.00" style="padding-left:24px">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="intake-label">Customer Approved?</label>
                        <div class="intake-radio-group">
                            <label class="intake-radio"><input type="radio" name="customer_approved" value="1"> Yes</label>
                            <label class="intake-radio selected"><input type="radio" name="customer_approved" value="0" checked> Pending</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="intake-label">Authorization Code</label>
                        <input type="text" class="intake-input mono" name="authorization_code" placeholder="Auth #">
                    </div>
                    <div class="col-md-12">
                        <label class="intake-label">Billing Notes</label>
                        <textarea class="intake-textarea" name="billing_notes" rows="2" placeholder="Insurance claim #, fleet PO, special billing instructions..."></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── Section 7: Special & Assignment ───────────────── -->
        <div class="intake-section intake-conditional" data-section="special" id="sec-special">
            <div class="intake-section-head" onclick="toggleSection(this)">
                <div class="left">
                    <span class="num">7</span>
                    <h2><i class="fas fa-cog" style="color:var(--navy-300);margin-right:6px"></i> Special Needs & Assignment</h2>
                </div>
                <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="intake-section-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="intake-label">Special Equipment Needed</label>
                        <textarea class="intake-textarea" name="special_equipment" rows="2" placeholder="Flatbed, extra chains, specific tools..."></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="intake-label">Accessibility Needs</label>
                        <textarea class="intake-textarea" name="accessibility_needs" rows="2" placeholder="Wheelchair access, hearing impaired..."></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="intake-label">Preferred Language</label>
                        <select class="intake-select" name="preferred_language">
                            <option value="English">English</option>
                            <option value="Spanish">Spanish</option>
                            <option value="French">French</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="intake-label">SMS Consent</label>
                        <div class="intake-radio-group">
                            <label class="intake-radio"><input type="radio" name="sms_consent" value="1"> Yes, opt-in</label>
                            <label class="intake-radio selected"><input type="radio" name="sms_consent" value="0" checked> No</label>
                        </div>
                    </div>
                    <div class="col-12"><hr style="border-color:var(--border-subtle)"></div>
                    <div class="col-md-6">
                        <label class="intake-label">Internal Notes (dispatcher only)</label>
                        <textarea class="intake-textarea" name="internal_notes" rows="2" placeholder="Notes visible only to dispatchers..."></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="intake-label">Customer Notes</label>
                        <textarea class="intake-textarea" name="customer_notes" rows="2" placeholder="Notes from customer..."></textarea>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /intake-form-center -->

    <!-- ═══ RIGHT SIDEBAR ═══ -->
    <div class="intake-sidebar-right">
        <div class="intake-panel">
            <div class="intake-panel-head">
                <i class="fas fa-search" style="color:var(--navy-300);font-size:12px"></i>
                <h3>Customer Lookup</h3>
            </div>
            <div class="intake-panel-body">
                <div class="intake-search">
                    <i class="fas fa-phone icon"></i>
                    <input type="tel" id="custLookupPhone" class="phone-masked" value="(   )    -    " maxlength="14">
                </div>
                <div id="custLookupResults" class="intake-search-results"></div>
            </div>
        </div>
        <div class="intake-panel">
            <div class="intake-panel-head">
                <i class="fas fa-clock" style="color:var(--text-tertiary);font-size:12px"></i>
                <h3>Recent Customers</h3>
            </div>
            <div class="intake-panel-body" id="recentCustomersPanel">
                <p style="font-size:12px;color:var(--text-tertiary)">Loading...</p>
            </div>
        </div>
        <div class="intake-panel">
            <div class="intake-panel-head">
                <i class="fas fa-file-alt" style="color:var(--navy-300);font-size:12px"></i>
                <h3>Ticket Summary</h3>
            </div>
            <div class="intake-panel-body" id="ticketSummaryPanel">
                <div class="intake-summary-item"><span class="lbl">Customer</span><span class="val" id="sumCustomer">—</span></div>
                <div class="intake-summary-item"><span class="lbl">Phone</span><span class="val" id="sumPhone">—</span></div>
                <div class="intake-summary-item"><span class="lbl">Vehicle</span><span class="val" id="sumVehicle">—</span></div>
                <div class="intake-summary-item"><span class="lbl">Location</span><span class="val" id="sumLocation" style="font-family:'DM Sans',sans-serif;font-size:11px;max-width:160px;text-align:right">—</span></div>
                <div class="intake-summary-item"><span class="lbl">Service</span><span class="val" id="sumService">—</span></div>
                <div class="intake-summary-item"><span class="lbl">Priority</span><span class="val" id="sumPriority">P2</span></div>
                <div class="intake-summary-item"><span class="lbl">Payment</span><span class="val" id="sumPayment">Card</span></div>
                <hr style="border-color:var(--border-subtle);margin:8px 0">
                <div class="intake-summary-total"><span>Est. Cost</span><span class="val" id="sumCost">$0.00</span></div>
            </div>
        </div>
        <div class="intake-panel">
            <div class="intake-panel-head">
                <i class="fas fa-user-cog" style="color:var(--navy-300);font-size:12px"></i>
                <h3>Assign Technician</h3>
            </div>
            <div class="intake-panel-body">
                <select class="intake-select" name="technician_id" id="techSelect">
                    <option value="">Auto-assign (nearest)</option>
                    <?php foreach ($techsForDropdown as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= $t['status'] !== 'available' ? 'disabled' : '' ?>>
                            <?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?>
                            <?= $t['specialization'] ? '(' . htmlspecialchars($t['specialization']) . ')' : '' ?>
                            <?= $t['status'] !== 'available' ? ' [' . $t['status'] . ']' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div style="font-size:11px;color:var(--text-tertiary);margin-top:6px">
                    <span id="techAvailCount"><?= count(array_filter($techsForDropdown, fn($t) => $t['status'] === 'available')) ?></span> technicians available
                </div>
            </div>
        </div>
    </div>

</div><!-- /intake-layout -->

<!-- Submit Bar -->
<div class="intake-submit-bar">
    <div class="status-text">
        <span id="sectionProgress">0 of 7 sections complete</span>
    </div>
    <div class="actions">
        <button type="button" class="btn btn-secondary" onclick="saveDraft()">
            <i class="fas fa-save"></i> Save Draft
        </button>
        <button type="button" class="btn btn-primary" onclick="submitTicket(false)" id="btnCreateTicket">
            <i class="fas fa-ticket-alt"></i> Create Ticket
        </button>
        <button type="button" class="btn btn-danger" onclick="submitTicket(true)" id="btnDispatch" style="display:none">
            <i class="fas fa-paper-plane"></i> Create & Dispatch
        </button>
    </div>
</div>

</form>

<script>
// ═════════════════════════════════════════════════════════════════════
// Intake Form Controller
// ═════════════════════════════════════════════════════════════════════
const API_TICKETS = 'api/service-tickets.php';
const API_WORKFLOW = 'api/workflow.php';
let selectedCategory = '';
let isRapidMode = false;
let customerData = null;

// ─── Phone mask handled globally by app.js ──────────────────────────
// phoneMaskApply() and phoneGetRawDigits() are available globally

// ─── Section Accordion ──────────────────────────────────────────────
function toggleSection(head) {
    const sec = head.closest('.intake-section');
    sec.classList.toggle('open');
}

// ─── Rapid Dispatch Toggle ──────────────────────────────────────────
document.getElementById('rapidToggle').addEventListener('click', function() {
    isRapidMode = !isRapidMode;
    this.classList.toggle('active', isRapidMode);
    const conditionals = document.querySelectorAll('.intake-conditional');
    conditionals.forEach(el => {
        if (isRapidMode) {
            el.classList.remove('visible');
            el.style.display = 'none';
        } else {
            el.classList.add('visible');
            el.style.display = '';
        }
    });
    updateSectionProgress();
});

// ─── Show non-rapid sections on load ────────────────────────────────
document.querySelectorAll('.intake-conditional').forEach(el => {
    el.classList.add('visible');
    el.style.display = '';
});

// ─── Category Selection ─────────────────────────────────────────────
function selectCategory(card) {
    document.querySelectorAll('.intake-cat-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    selectedCategory = card.dataset.val;
    document.getElementById('serviceCategoryInput').value = selectedCategory;

    // Show tow destination if towing
    const towFields = document.getElementById('towDestFields');
    if (towFields) towFields.classList.toggle('visible', selectedCategory === 'towing');

    // Load specific services
    loadSpecificServices(selectedCategory);
    updateSummary();
    updateEstimate();
}

async function loadSpecificServices(category) {
    const panel = document.getElementById('specificServicesPanel');
    const select = document.getElementById('specificServiceSelect');
    try {
        const res = await fetch(`${API_TICKETS}?action=get_services&category=${category}`);
        const json = await res.json();
        if (json.success && json.data.length) {
            select.innerHTML = '<option value="">Select specific service...</option>';
            json.data.forEach(s => {
                const rate = parseFloat(s.base_rate);
                select.innerHTML += `<option value="${s.id}" data-rate="${rate}">${s.name} — $${rate.toFixed(2)}</option>`;
            });
            panel.style.display = 'block';
        } else {
            panel.style.display = 'none';
        }
    } catch(e) { panel.style.display = 'none'; }
}

document.getElementById('specificServiceSelect')?.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (opt && opt.dataset.rate) {
        document.getElementById('estimatedCost').value = parseFloat(opt.dataset.rate).toFixed(2);
        updateSummary();
    }
});

// ─── Priority Selection ─────────────────────────────────────────────
function selectPriority(card) {
    document.querySelectorAll('.intake-priority-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    document.getElementById('priorityInput').value = card.dataset.val;
    updateSummary();
}

// ─── Safety Toggle ──────────────────────────────────────────────────
document.querySelectorAll('input[name="safe_location"]').forEach(r => {
    r.addEventListener('change', function() {
        const warn = document.getElementById('safetyWarning');
        if (this.value === '0') {
            warn.classList.add('visible');
            // Auto-escalate to P1
            selectPriority(document.querySelector('.intake-priority-card[data-val="P1"]'));
        } else {
            warn.classList.remove('visible');
        }
    });
});

// ─── Location Type → show highway fields ────────────────────────────
document.getElementById('locationType')?.addEventListener('change', function() {
    const isHwy = this.value === 'highway' || this.value === 'roadside';
    document.getElementById('highwayFields')?.classList.toggle('visible', isHwy);
    document.getElementById('directionField')?.classList.toggle('visible', isHwy);
});

// ─── ETA → show scheduled field ─────────────────────────────────────
document.querySelector('select[name="requested_eta"]')?.addEventListener('change', function() {
    document.getElementById('scheduledDateField')?.classList.toggle('visible', this.value === 'scheduled');
});

// ─── Radio / Checkbox styling ───────────────────────────────────────
document.querySelectorAll('.intake-radio input').forEach(r => {
    r.addEventListener('change', function() {
        this.closest('.intake-radio-group,.intake-section-body').querySelectorAll('.intake-radio').forEach(l => l.classList.remove('selected'));
        this.closest('.intake-radio').classList.add('selected');
    });
});
document.querySelectorAll('.intake-check input').forEach(c => {
    c.addEventListener('change', function() {
        this.closest('.intake-check').classList.toggle('selected', this.checked);
    });
});

// ─── Customer Phone Lookup ──────────────────────────────────────────
let lookupTimer = null;
document.getElementById('custLookupPhone')?.addEventListener('input', function() {
    clearTimeout(lookupTimer);
    const phone = phoneGetRawDigits(this);
    if (phone.length < 4) { document.getElementById('custLookupResults').innerHTML = ''; return; }
    lookupTimer = setTimeout(() => lookupCustomer(phone), 300);
});

// Also trigger lookup from main phone field
document.getElementById('customerPhone')?.addEventListener('input', function() {
    clearTimeout(lookupTimer);
    const phone = this.value.replace(/\D/g, '');
    if (phone.length >= 6) {
        lookupTimer = setTimeout(() => lookupCustomer(phone), 400);
    }
    updateSummary();
});

async function lookupCustomer(phone) {
    try {
        const res = await fetch(`${API_TICKETS}?action=customer_lookup&phone=${phone}`);
        const json = await res.json();
        const panel = document.getElementById('custLookupResults');
        if (json.success && json.data.length) {
            panel.innerHTML = json.data.map(c => `
                <div class="intake-cust-card" onclick="fillCustomer(${JSON.stringify(c).replace(/"/g, '&quot;')})">
                    <div class="name">${c.first_name} ${c.last_name}</div>
                    <div class="phone">${c.phone}</div>
                    <div class="meta">${c.email || ''} ${c.last_service ? '· Last: ' + c.last_service.substring(0,10) : ''}</div>
                </div>
            `).join('');
        } else {
            panel.innerHTML = '<p style="font-size:12px;color:var(--text-tertiary);padding:4px">No customers found</p>';
        }
    } catch(e) {}
}

function fillCustomer(cust) {
    customerData = cust;
    // Format phone into mask
    const phoneInput = document.querySelector('input[name="customer_phone"]');
    const phoneDigits = (cust.phone || '').replace(/\D/g, '').slice(0, 10);
    phoneMaskApply(phoneInput, phoneDigits);
    document.querySelector('input[name="customer_name"]').value = cust.first_name + ' ' + cust.last_name;
    if (cust.email) document.querySelector('input[name="customer_email"]').value = cust.email;

    // Fill vehicles dropdown
    if (cust.vehicles && cust.vehicles.length) {
        const vSelect = document.getElementById('vehicleSelect');
        const vPanel = document.getElementById('vehicleSelectPanel');
        vSelect.innerHTML = '<option value="">+ Enter new vehicle</option>';
        cust.vehicles.forEach(v => {
            vSelect.innerHTML += `<option value="${v.id}" data-vehicle='${JSON.stringify(v)}'>${v.year || ''} ${v.make || ''} ${v.model || ''} ${v.color ? '(' + v.color + ')' : ''}</option>`;
        });
        vPanel.style.display = 'block';
    }

    updateSummary();
    // Open vehicle section
    document.getElementById('sec-vehicle')?.classList.add('open');
}

// Vehicle select → fill fields
document.getElementById('vehicleSelect')?.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (opt.value && opt.dataset.vehicle) {
        const v = JSON.parse(opt.dataset.vehicle);
        const fields = document.getElementById('vehicleFields');
        if (v.year) fields.querySelector('[name="vehicle_year"]').value = v.year;
        if (v.make) fields.querySelector('[name="vehicle_make"]').value = v.make;
        if (v.model) fields.querySelector('[name="vehicle_model"]').value = v.model;
        if (v.color) fields.querySelector('[name="vehicle_color"]').value = v.color;
        if (v.license_plate) fields.querySelector('[name="vehicle_plate"]').value = v.license_plate;
        if (v.vin) fields.querySelector('[name="vehicle_vin"]').value = v.vin;
        if (v.mileage) fields.querySelector('[name="vehicle_mileage"]').value = v.mileage;
        if (v.drive_type) fields.querySelector('[name="vehicle_drive_type"]').value = v.drive_type;
        updateSummary();
    }
});

// ─── Load Recent Customers ──────────────────────────────────────────
async function loadRecentCustomers() {
    try {
        const res = await fetch(`${API_TICKETS}?action=recent_customers`);
        const json = await res.json();
        const panel = document.getElementById('recentCustomersPanel');
        if (json.success && json.data.length) {
            panel.innerHTML = json.data.slice(0, 8).map(c => `
                <div class="intake-cust-card" onclick="lookupAndFill('${c.phone}')">
                    <div class="name">${c.first_name} ${c.last_name}</div>
                    <div class="phone">${c.phone}</div>
                    <div class="meta">${c.last_service_type ? c.last_service_type.replace(/_/g,' ') : ''} ${c.last_service_date ? '· ' + c.last_service_date.substring(0,10) : ''}</div>
                </div>
            `).join('');
        } else {
            panel.innerHTML = '<p style="font-size:12px;color:var(--text-tertiary)">No recent customers</p>';
        }
    } catch(e) {}
}

async function lookupAndFill(phone) {
    const res = await fetch(`${API_TICKETS}?action=customer_lookup&phone=${phone.replace(/\D/g,'')}`);
    const json = await res.json();
    if (json.success && json.data.length) fillCustomer(json.data[0]);
}

// ─── Shorthand Parser ───────────────────────────────────────────────
let shorthandTimer = null;
document.getElementById('issueDescription')?.addEventListener('input', function() {
    clearTimeout(shorthandTimer);
    const text = this.value;
    if (text.length < 2) { document.getElementById('shorthandSuggestions').innerHTML = ''; return; }
    shorthandTimer = setTimeout(async () => {
        try {
            const fd = new FormData();
            fd.append('action', 'parse_shorthand');
            fd.append('text', text);
            const res = await fetch(API_TICKETS, { method: 'POST', body: fd });
            const json = await res.json();
            if (json.success && json.data.suggestions.length) {
                const panel = document.getElementById('shorthandSuggestions');
                panel.innerHTML = json.data.suggestions.map(s =>
                    `<span class="intake-shorthand-suggestion" onclick="applyShorthand('${s.category}','${s.service}')"><i class="fas fa-check"></i> ${s.label}</span>`
                ).join('');
                if (json.data.tow_distance) {
                    const td = document.getElementById('towDistance');
                    if (td) td.value = json.data.tow_distance;
                }
            } else {
                document.getElementById('shorthandSuggestions').innerHTML = '';
            }
        } catch(e) {}
    }, 500);
});

function applyShorthand(category, service) {
    const card = document.querySelector(`.intake-cat-card[data-val="${category}"]`);
    if (card) selectCategory(card);
    // Open service section
    document.getElementById('sec-service')?.classList.add('open');
}

// ─── Auto Cost Estimation ───────────────────────────────────────────
async function updateEstimate() {
    const serviceSelect = document.getElementById('specificServiceSelect');
    const serviceId = serviceSelect?.value;
    if (!serviceId) return;

    const towMiles = document.getElementById('towDistance')?.value || 0;
    const isAfterHours = new Date().getHours() < 7 || new Date().getHours() >= 19;

    const fd = new FormData();
    fd.append('action', 'estimate_cost');
    fd.append('service_ids', JSON.stringify([parseInt(serviceId)]));
    fd.append('tow_miles', towMiles);
    fd.append('after_hours', isAfterHours ? '1' : '0');

    try {
        const res = await fetch(API_TICKETS, { method: 'POST', body: fd });
        const json = await res.json();
        if (json.success) {
            document.getElementById('estimatedCost').value = json.data.estimated_cost.toFixed(2);
            updateSummary();
        }
    } catch(e) {}
}

// ─── Summary Panel Updates ──────────────────────────────────────────
function updateSummary() {
    const name = document.querySelector('input[name="customer_name"]')?.value;
    const phoneInput = document.querySelector('input[name="customer_phone"]');
    const phoneDigits = phoneInput ? phoneGetRawDigits(phoneInput) : '';
    const phone = phoneDigits.length > 0 ? phoneInput.value : '';
    const year = document.querySelector('input[name="vehicle_year"]')?.value;
    const make = document.querySelector('input[name="vehicle_make"]')?.value;
    const model = document.querySelector('input[name="vehicle_model"]')?.value;
    const addr = document.getElementById('serviceAddress')?.value;
    const cost = document.getElementById('estimatedCost')?.value;
    const priority = document.getElementById('priorityInput')?.value;
    const payment = document.querySelector('select[name="payment_method"]')?.selectedOptions?.[0]?.text;

    document.getElementById('sumCustomer').textContent = name || '—';
    document.getElementById('sumPhone').textContent = phone || '—';
    document.getElementById('sumVehicle').textContent = (year || make || model) ? `${year || ''} ${make || ''} ${model || ''}`.trim() : '—';
    document.getElementById('sumLocation').textContent = addr ? (addr.length > 30 ? addr.substring(0,30) + '…' : addr) : '—';
    document.getElementById('sumService').textContent = selectedCategory ? selectedCategory.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) : '—';
    document.getElementById('sumPriority').textContent = priority || 'P3';
    document.getElementById('sumPayment').textContent = payment || 'Card';
    document.getElementById('sumCost').textContent = cost ? '$' + parseFloat(cost).toFixed(2) : '$0.00';
}

// Debounced summary for text inputs
['customer_name','service_address'].forEach(n => {
    document.querySelector(`input[name="${n}"]`)?.addEventListener('input', () => updateSummary());
});
document.querySelector('input[name="customer_phone"]')?.addEventListener('input', () => updateSummary());
document.querySelector('select[name="payment_method"]')?.addEventListener('change', updateSummary);
document.getElementById('estimatedCost')?.addEventListener('input', updateSummary);

// ─── Section Progress ───────────────────────────────────────────────
function updateSectionProgress() {
    const sections = isRapidMode ? 3 : 7; // Rapid = customer, location, service
    let complete = 0;
    // Simple completion check
    if (phoneGetRawDigits(document.querySelector('input[name="customer_phone"]')).length >= 7) complete++;
    if (!isRapidMode && document.querySelector('input[name="vehicle_make"]')?.value) complete++;
    if (document.getElementById('serviceAddress')?.value) complete++;
    if (selectedCategory) complete++;
    if (document.getElementById('priorityInput')?.value) complete++;
    if (!isRapidMode) complete += 2; // payment & special have defaults

    document.getElementById('sectionProgress').textContent = `${Math.min(complete, sections)} of ${sections} sections complete`;
}

// ─── Show dispatch button if tech selected ──────────────────────────
document.getElementById('techSelect')?.addEventListener('change', function() {
    document.getElementById('btnDispatch').style.display = this.value ? 'inline-flex' : 'none';
});

// ─── Form Submission ────────────────────────────────────────────────
async function submitTicket(dispatch = false) {
    const form = document.getElementById('intakeForm');
    const fd = new FormData(form);
    fd.append('action', 'create_ticket');
    if (isRapidMode) fd.set('rapid_dispatch', '1');

    // Collect hazard conditions
    const hazards = [];
    document.querySelectorAll('input[name="hazard[]"]:checked').forEach(h => hazards.push(h.value));
    fd.set('hazard_conditions', hazards.join(','));

    // Strip mask before sending — send raw digits
    const phoneInput = document.querySelector('input[name="customer_phone"]');
    const phoneDigits = phoneGetRawDigits(phoneInput);
    fd.set('customer_phone', phoneDigits);
    // Also strip alt phone
    const altPhoneInput = document.querySelector('input[name="alt_phone"]');
    if (altPhoneInput) fd.set('alt_phone', phoneGetRawDigits(altPhoneInput));

    // Validation
    const errors = [];
    if (phoneDigits.length < 7) errors.push('Phone number is required (min 7 digits)');
    if (!fd.get('customer_name')?.trim()) errors.push('Customer name is required');
    if (!fd.get('service_address')?.trim()) errors.push('Service address is required');
    if (!fd.get('service_category')?.trim()) errors.push('Service category is required');
    if (!fd.get('issue_description')?.trim() && !isRapidMode) errors.push('Issue description is required');
    if (isRapidMode && !fd.get('issue_description')?.trim()) fd.set('issue_description', selectedCategory.replace(/_/g, ' '));

    if (errors.length) {
        alert('Please fix:\n\n' + errors.join('\n'));
        return;
    }

    try {
        const res = await fetch(API_TICKETS, { method: 'POST', body: fd });
        const json = await res.json();
        if (json.success) {
            if (dispatch && fd.get('technician_id')) {
                // Also dispatch
                const dfd = new FormData();
                dfd.append('action', 'dispatch');
                dfd.append('ticket_id', json.data.id);
                dfd.append('technician_id', fd.get('technician_id'));
                await fetch(API_TICKETS, { method: 'POST', body: dfd });
            }
            // Redirect to service requests list with success
            window.location.href = `?page=service-requests&created=${json.data.ticket_number}`;
        } else {
            alert('Error: ' + (json.error || 'Unknown error') + (json.fields ? '\nMissing: ' + json.fields.join(', ') : ''));
        }
    } catch(e) {
        alert('Network error. Please try again.');
    }
}

function saveDraft() {
    alert('Draft saving coming soon. For now, use Create Ticket to save.');
}

// ─── Quick Entry ↔ Detail Section Mirroring ─────────────────────────
(function() {
    // Define mirror pairs: [quickEntryId, detailFieldSelector]
    const mirrorPairs = [
        ['qeName',        'input[name="customer_name"]'],
        ['qeAddress',     '#serviceAddress'],
        ['qeDescription', '#issueDescription'],
    ];

    // Text/textarea bidirectional sync
    mirrorPairs.forEach(([qeId, detailSel]) => {
        const qe = document.getElementById(qeId);
        const detail = document.querySelector(detailSel);
        if (!qe || !detail) return;

        let syncing = false;
        qe.addEventListener('input', () => {
            if (syncing) return;
            syncing = true;
            detail.value = qe.value;
            detail.classList.add('synced');
            setTimeout(() => detail.classList.remove('synced'), 400);
            detail.dispatchEvent(new Event('input', { bubbles: true }));
            syncing = false;
        });
        detail.addEventListener('input', () => {
            if (syncing) return;
            syncing = true;
            qe.value = detail.value;
            qe.classList.add('synced');
            setTimeout(() => qe.classList.remove('synced'), 400);
            syncing = false;
        });
    });

    // Phone field — special handling (masked)
    const qePhone = document.getElementById('qePhone');
    const detailPhone = document.getElementById('customerPhone');
    if (qePhone && detailPhone) {
        let syncing = false;
        qePhone.addEventListener('input', () => {
            if (syncing) return;
            syncing = true;
            const digits = phoneGetRawDigits(qePhone);
            phoneMaskApply(detailPhone, digits);
            detailPhone.classList.add('synced');
            setTimeout(() => detailPhone.classList.remove('synced'), 400);
            detailPhone.dispatchEvent(new Event('input', { bubbles: true }));
            syncing = false;
        });
        detailPhone.addEventListener('input', () => {
            if (syncing) return;
            syncing = true;
            const digits = phoneGetRawDigits(detailPhone);
            phoneMaskApply(qePhone, digits);
            qePhone.classList.add('synced');
            setTimeout(() => qePhone.classList.remove('synced'), 400);
            syncing = false;
        });
    }

    // Category pills ↔ category cards — bidirectional
    window.qeSelectCategory = function(pill) {
        // Update QE pills
        document.querySelectorAll('.qe-cat-pill').forEach(p => p.classList.remove('selected'));
        pill.classList.add('selected');
        // Mirror to detail card
        const val = pill.dataset.val;
        const detailCard = document.querySelector(`.intake-cat-card[data-val="${val}"]`);
        if (detailCard) selectCategory(detailCard);
        // Open service section
        document.getElementById('sec-service')?.classList.add('open');
    };

    // Intercept detail selectCategory to mirror back to QE pills
    const origSelectCategory = window.selectCategory;
    window.selectCategory = function(card) {
        origSelectCategory(card);
        const val = card.dataset.val;
        document.querySelectorAll('.qe-cat-pill').forEach(p => {
            p.classList.toggle('selected', p.dataset.val === val);
        });
    };

    // Also mirror fillCustomer data into QE fields
    const origFillCustomer = window.fillCustomer;
    window.fillCustomer = function(cust) {
        origFillCustomer(cust);
        // Sync to QE
        const qeN = document.getElementById('qeName');
        const qeP = document.getElementById('qePhone');
        if (qeN) qeN.value = (cust.first_name + ' ' + cust.last_name).trim();
        if (qeP) {
            const digits = (cust.phone || '').replace(/\D/g, '').slice(0, 10);
            phoneMaskApply(qeP, digits);
        }
    };
})();

// ─── Init ───────────────────────────────────────────────────────────
loadRecentCustomers();
updateSummary();
updateSectionProgress();

// Show highway fields by default for roadside
document.getElementById('highwayFields')?.classList.add('visible');
document.getElementById('directionField')?.classList.add('visible');
</script>

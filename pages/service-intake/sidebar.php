<!-- Right Sidebar + Submit Bar — included by service-intake.php -->

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
        <button type="button" class="btn btn-primary" onclick="showSmsConsentModal()" id="btnCreateTicket">
            <i class="fas fa-ticket-alt"></i> Create Ticket
        </button>
    </div>
</div>

</form>

<!-- SMS Consent Modal -->
<div class="modal fade" id="smsConsentModal" tabindex="-1" aria-labelledby="smsConsentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--bg-surface);border:1px solid var(--border-medium)">
            <div class="modal-header" style="border-bottom:1px solid var(--border-subtle)">
                <h5 class="modal-title" id="smsConsentModalLabel">
                    <i class="fas fa-sms" style="color:var(--navy-300);margin-right:8px"></i>
                    SMS Verbal Consent
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding:24px">
                <div class="sms-consent-text" style="font-size:14px;line-height:1.7;color:var(--text-secondary)">
                    <p style="margin-bottom:10px">
                        Read this to the customer and confirm they agree:
                    </p>
                    <div style="background:var(--bg-surface-2);border:1px solid var(--border-medium);border-left:3px solid var(--navy-500);border-radius:12px;padding:14px 14px;margin:0 0 14px 0;color:var(--text-primary)">
                        <div style="font-family:'JetBrains Mono',monospace;font-size:12.5px;line-height:1.7">
                            “By providing your phone number, you agree to receive Customer Care SMS from <strong style="color:var(--navy-300)"><?= htmlspecialchars($smsBrandName) ?></strong>. Message frequency may vary. Standard Message and Data Rates may apply. Reply <strong>STOP</strong> to opt out. Reply <strong>HELP</strong> for help. We will not share mobile information with third parties for promotional or marketing purposes.”
                        </div>
                        <span id="smsConsentPhone" class="d-none"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--border-subtle);gap:10px">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-secondary" onclick="confirmNoSmsConsentAndSubmit()">
                    <i class="fas fa-ban"></i> No SMS — Create Ticket
                </button>
                <button type="button" class="btn btn-primary" onclick="confirmSmsConsentAndSubmit()">
                    <i class="fas fa-check"></i> Consent Confirmed — Create Ticket
                </button>
            </div>
        </div>
    </div>
</div>

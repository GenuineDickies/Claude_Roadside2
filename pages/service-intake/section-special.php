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

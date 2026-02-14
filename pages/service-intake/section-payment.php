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

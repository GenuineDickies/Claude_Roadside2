<!-- Add/Edit Expense Modal -->
<div class="modal fade" id="expenseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="background:var(--bg-surface);border:1px solid var(--border-medium);border-radius:14px">
            <div class="modal-header" style="border-bottom:1px solid var(--border-subtle);padding:16px 24px">
                <h5 class="modal-title" style="font-size:16px;font-weight:700;color:var(--navy-300)" id="expenseModalTitle">Add Expense</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:20px 24px">
                <form id="expenseForm">
                    <input type="hidden" name="id" id="expenseId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="expense-form-group">
                                <label><span class="req">*</span> Category</label>
                                <select class="expense-form-select" name="category_id" id="expCategoryId" required>
                                    <option value="">Select category...</option>
                                    <?php foreach ($expenseCats as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="expense-form-group">
                                <label><span class="req">*</span> Vendor / Payee</label>
                                <input type="text" class="expense-form-input" name="vendor" id="expVendor" placeholder="AutoZone, Shell, State Farm..." required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="expense-form-group">
                                <label><span class="req">*</span> Amount</label>
                                <div style="position:relative">
                                    <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-tertiary);font-family:'JetBrains Mono',monospace">$</span>
                                    <input type="number" class="expense-form-input" name="amount" id="expAmount" step="0.01" min="0" style="padding-left:24px;font-family:'JetBrains Mono',monospace" required oninput="calcTotal()">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="expense-form-group">
                                <label>Tax</label>
                                <div style="position:relative">
                                    <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-tertiary);font-family:'JetBrains Mono',monospace">$</span>
                                    <input type="number" class="expense-form-input" name="tax_amount" id="expTax" step="0.01" min="0" value="0" style="padding-left:24px;font-family:'JetBrains Mono',monospace" oninput="calcTotal()">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="expense-form-group">
                                <label>Total</label>
                                <div style="position:relative">
                                    <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-tertiary);font-family:'JetBrains Mono',monospace">$</span>
                                    <input type="number" class="expense-form-input" id="expTotal" step="0.01" style="padding-left:24px;font-family:'JetBrains Mono',monospace;color:var(--green-500)" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="expense-form-group">
                                <label><span class="req">*</span> Date</label>
                                <input type="date" class="expense-form-input" name="expense_date" id="expDate" value="<?= date('Y-m-d') ?>" required style="font-family:'JetBrains Mono',monospace">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="expense-form-group">
                                <label>Payment Method</label>
                                <select class="expense-form-select" name="payment_method" id="expPayMethod">
                                    <option value="card">Card</option>
                                    <option value="cash">Cash</option>
                                    <option value="check">Check</option>
                                    <option value="ach">ACH / Transfer</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="expense-form-group">
                                <label>Reference #</label>
                                <input type="text" class="expense-form-input" name="reference_number" id="expRef" placeholder="Receipt #, Check #" style="font-family:'JetBrains Mono',monospace">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="expense-form-group">
                                <label>Description / Notes</label>
                                <textarea class="expense-form-textarea" name="description" id="expDesc" rows="2" placeholder="What was this expense for?"></textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="expense-form-group">
                                <label>Link to Technician</label>
                                <select class="expense-form-select" name="technician_id" id="expTechId">
                                    <option value="">None</option>
                                    <?php foreach ($techsList as $t): ?>
                                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="expense-form-group">
                                <label>Vehicle Tag</label>
                                <input type="text" class="expense-form-input" name="vehicle_tag" id="expVehicleTag" placeholder="Unit # or plate">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="expense-form-group" style="padding-top:20px">
                                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                                    <input type="checkbox" name="is_tax_deductible" id="expTaxDeductible" value="1" checked>
                                    <span>Tax Deductible</span>
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <hr style="border-color:var(--border-subtle)">
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:12px;font-weight:600;color:#BCC3D4;margin-bottom:8px">
                                <input type="checkbox" name="is_recurring" id="expRecurring" onchange="toggleRecurring()">
                                <i class="fas fa-redo" style="color:var(--navy-300)"></i> This is a recurring expense
                            </label>
                            <div id="recurringFields" style="display:none" class="row g-3">
                                <div class="col-md-6">
                                    <div class="expense-form-group">
                                        <label>Frequency</label>
                                        <select class="expense-form-select" name="recurring_frequency" id="expRecurFreq">
                                            <option value="weekly">Weekly</option>
                                            <option value="biweekly">Bi-weekly</option>
                                            <option value="monthly" selected>Monthly</option>
                                            <option value="quarterly">Quarterly</option>
                                            <option value="annual">Annual</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="expense-form-group">
                                        <label>Next Due Date</label>
                                        <input type="date" class="expense-form-input" name="recurring_next_date" id="expRecurNext" style="font-family:'JetBrains Mono',monospace">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--border-subtle);padding:12px 24px">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveExpense()"><i class="fas fa-save"></i> Save Expense</button>
            </div>
        </div>
    </div>
</div>

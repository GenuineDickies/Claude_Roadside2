<!-- Add/Edit Modal -->
<div class="modal fade" id="itemModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="background:var(--bg-surface);border:1px solid var(--border-medium);border-radius:14px">
            <div class="modal-header" style="border-bottom:1px solid var(--border-subtle);padding:16px 24px">
                <h5 class="modal-title" style="font-size:16px;font-weight:700;color:var(--navy-300)" id="modalTitle">Add Document</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:20px 24px">
                <form id="itemForm">
                    <input type="hidden" name="id" id="fId">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <div class="comp-form-group">
                                <label><span class="req">*</span> Document Name</label>
                                <input type="text" class="comp-form-input" name="name" id="fName" placeholder="e.g. Business License, GL Insurance Policy" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="comp-form-group">
                                <label>Category</label>
                                <select class="comp-form-select" name="category" id="fCategory">
                                    <option value="license">License</option>
                                    <option value="permit">Permit</option>
                                    <option value="certification">Certification</option>
                                    <option value="insurance">Insurance</option>
                                    <option value="registration">Registration</option>
                                    <option value="inspection">Inspection</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="comp-have-toggle">
                                <input type="checkbox" name="have_it" id="fHaveIt" value="1">
                                <div>
                                    <div class="toggle-label">I have this document</div>
                                    <div class="toggle-hint">Check this if you currently possess a valid copy</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="comp-form-group">
                                <label>Issuing Authority</label>
                                <input type="text" class="comp-form-input" name="issuing_authority" id="fIssuer" placeholder="State DMV, insurance company, etc.">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="comp-form-group">
                                <label>Document / Policy Number</label>
                                <input type="text" class="comp-form-input" name="document_number" id="fDocNum" placeholder="License #, Policy #" style="font-family:'JetBrains Mono',monospace">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="comp-form-group">
                                <label>Issue Date</label>
                                <input type="date" class="comp-form-input" name="issue_date" id="fIssueDate" style="font-family:'JetBrains Mono',monospace">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="comp-form-group">
                                <label>Expiry / Renewal Date</label>
                                <input type="date" class="comp-form-input" name="expiry_date" id="fExpiryDate" style="font-family:'JetBrains Mono',monospace">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="comp-form-group">
                                <label>Remind Me (days before)</label>
                                <input type="number" class="comp-form-input" name="remind_days_before" id="fRemindDays" value="30" min="0" style="font-family:'JetBrains Mono',monospace">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="comp-form-group">
                                <label>Cost</label>
                                <div style="position:relative">
                                    <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-tertiary);font-family:'JetBrains Mono',monospace">$</span>
                                    <input type="number" class="comp-form-input" name="cost" id="fCost" step="0.01" min="0" value="0" style="padding-left:24px;font-family:'JetBrains Mono',monospace">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="comp-form-group">
                                <label>Notes</label>
                                <textarea class="comp-form-textarea" name="notes" id="fNotes" rows="2" placeholder="Coverage details, renewal instructions, where the document is stored..."></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--border-subtle);padding:12px 24px">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveItem()"><i class="fas fa-save"></i> Save</button>
            </div>
        </div>
    </div>
</div>

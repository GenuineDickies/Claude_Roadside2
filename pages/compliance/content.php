<!-- Header -->
<div class="comp-header">
    <div style="display:flex;align-items:center;gap:14px">
        <i class="fas fa-folder-open" style="font-size:26px;color:var(--navy-300)"></i>
        <div>
            <h1>Documents & Licenses</h1>
            <p class="subtitle">Track required permits, licenses, certifications, and insurance</p>
        </div>
    </div>
    <button class="btn btn-primary" onclick="openItemModal()">
        <i class="fas fa-plus"></i> Add Document
    </button>
</div>

<!-- Reminder Banner (populated by JS) -->
<div class="comp-reminder ok" id="reminderBanner" style="display:none"></div>

<!-- Summary Cards -->
<div class="comp-summary" id="summaryCards">
    <div class="comp-summary-card"><div class="val" id="sumTotal">0</div><div class="lbl">Total Items</div></div>
    <div class="comp-summary-card"><div class="val have-yes" id="sumHave">0</div><div class="lbl">Have</div></div>
    <div class="comp-summary-card"><div class="val have-no" id="sumMissing">0</div><div class="lbl">Don't Have</div></div>
    <div class="comp-summary-card"><div class="val expiry-warn" id="sumExpiring">0</div><div class="lbl">Expiring Soon</div></div>
    <div class="comp-summary-card"><div class="val expiry-expired" id="sumExpired">0</div><div class="lbl">Expired</div></div>
</div>

<!-- Filters -->
<div class="comp-filters">
    <button class="comp-filter-btn active" data-filter="all" onclick="setFilter('all', this)">All</button>
    <button class="comp-filter-btn" data-filter="have" onclick="setFilter('have', this)">Have It</button>
    <button class="comp-filter-btn" data-filter="missing" onclick="setFilter('missing', this)">Don't Have</button>
    <button class="comp-filter-btn" data-filter="expiring" onclick="setFilter('expiring', this)">Expiring</button>
    <input type="text" class="comp-search" id="compSearch" placeholder="Search..." oninput="debounceSearch()">
</div>

<!-- Items Table -->
<div class="card">
    <div class="card-body" style="padding:0;overflow-x:auto">
        <table class="comp-table">
            <thead>
                <tr>
                    <th>Document / License</th>
                    <th>Category</th>
                    <th>Have It?</th>
                    <th>Expiry Date</th>
                    <th>Status</th>
                    <th>Cost</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="itemsBody">
                <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-secondary)">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

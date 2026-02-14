<!-- P&L Summary -->
<div class="dash-pl-card">
    <div class="dash-pl-item">
        <div class="label"><i class="fas fa-arrow-up" style="color:#22C55E"></i> Revenue (30 Days)</div>
        <div class="value" style="color:#22C55E"><?= format_currency($totalRevenue) ?></div>
        <div class="sub">From paid invoices</div>
    </div>
    <div class="dash-pl-item">
        <div class="label"><i class="fas fa-arrow-down" style="color:#EF4444"></i> Expenses (This Month)</div>
        <div class="value" style="color:#EF4444"><?= format_currency($monthExpenses) ?></div>
        <div class="sub">All operating costs</div>
    </div>
    <div class="dash-pl-item">
        <div class="label"><i class="fas fa-chart-line" style="color:<?= $profit30 >= 0 ? '#22C55E' : '#EF4444' ?>"></i> Net Profit</div>
        <div class="value" style="color:<?= $profit30 >= 0 ? '#22C55E' : '#EF4444' ?>"><?= format_currency($profit30) ?></div>
        <div class="sub">Revenue minus expenses</div>
    </div>
</div>

<!-- Operations Stats -->
<div class="dash-stats">
    <div class="dash-stat navy">
        <div class="label"><i class="fas fa-users"></i> Customers</div>
        <div class="value"><?= $stats['total_customers'] ?></div>
        <div class="sub">Total in database</div>
    </div>
    <div class="dash-stat amber">
        <div class="label"><i class="fas fa-clipboard-list"></i> Active Jobs</div>
        <div class="value"><?= $stats['active_requests'] ?></div>
        <div class="sub">Pending, assigned, in-progress</div>
    </div>
    <div class="dash-stat green">
        <div class="label"><i class="fas fa-user-cog"></i> Techs Available</div>
        <div class="value"><?= $stats['available_technicians'] ?></div>
        <div class="sub">Ready to dispatch</div>
    </div>
    <div class="dash-stat blue">
        <div class="label"><i class="fas fa-file-invoice-dollar"></i> Unpaid Invoices</div>
        <div class="value"><?= $stats['pending_invoices'] ?></div>
        <div class="sub">Draft or sent</div>
    </div>
</div>

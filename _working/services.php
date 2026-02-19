<?php
/**
 * Service Catalog — Services & Parts Management
 * Two tabs: Services (from service_types) and Parts (from parts_inventory)
 * Full CRUD with database integration
 */

// Fetch services with category info
$services = $pdo->query("
    SELECT st.*, sc.name as category_name, sc.slug as category_slug 
    FROM service_types st 
    JOIN service_categories sc ON st.category_id = sc.id 
    ORDER BY sc.sort_order, st.name
")->fetchAll();

// Fetch categories for dropdowns
$categories = $pdo->query("SELECT * FROM service_categories WHERE active = 1 ORDER BY sort_order")->fetchAll();

// Fetch parts
$parts = $pdo->query("SELECT * FROM parts_inventory ORDER BY category, name")->fetchAll();

// Get unique part categories
$partCategories = $pdo->query("SELECT DISTINCT category FROM parts_inventory WHERE category IS NOT NULL ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
?>

<link rel="stylesheet" href="assets/css/pages/catalog.css?v=20260216-2">

<!-- Header -->
<div class="catalog-header">
    <div class="catalog-header-left">
        <i class="fas fa-book-open" style="font-size:26px;color:var(--navy-300)"></i>
        <div>
            <h1>Service Catalog</h1>
            <p class="subtitle">Manage services and parts inventory</p>
        </div>
    </div>
</div>

<!-- Tab Navigation -->
<div class="catalog-tabs">
    <button class="catalog-tab active" data-tab="services" onclick="switchTab('services', this)">
        <i class="fas fa-wrench"></i> Services
        <span class="tab-count" id="servicesCount"><?= count($services) ?></span>
    </button>
    <button class="catalog-tab" data-tab="parts" onclick="switchTab('parts', this)">
        <i class="fas fa-cogs"></i> Parts
        <span class="tab-count" id="partsCount"><?= count($parts) ?></span>
    </button>
</div>

<!-- Services Tab Content -->
<div class="catalog-content" id="tab-services">
    <div class="catalog-toolbar">
        <div class="catalog-search">
            <i class="fas fa-search"></i>
            <input type="text" id="servicesSearch" placeholder="Search services..." oninput="filterServices()">
        </div>
        <select class="catalog-filter" id="servicesCategoryFilter" onchange="filterServices()">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat['slug']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="toolbar-spacer"></div>
        <button class="btn btn-primary" onclick="openServiceModal()">
            <i class="fas fa-plus"></i> Add Service
        </button>
    </div>

    <div class="catalog-table-wrap">
        <table class="catalog-table" id="servicesTable">
            <thead>
                <tr>
                    <th>Service</th>
                    <th>Category</th>
                    <th>Base Rate</th>
                    <th>After Hours</th>
                    <th>Status</th>
                    <th style="width:120px">Actions</th>
                </tr>
            </thead>
            <tbody id="servicesBody">
                <?php if (empty($services)): ?>
                    <tr class="empty-row"><td colspan="6">No services found. Add your first service above.</td></tr>
                <?php else: ?>
                    <?php foreach ($services as $svc): ?>
                    <tr data-id="<?= $svc['id'] ?>" data-category="<?= htmlspecialchars($svc['category_slug']) ?>" data-search="<?= htmlspecialchars(strtolower($svc['name'] . ' ' . $svc['slug'] . ' ' . $svc['category_name'])) ?>">
                        <td>
                            <div class="item-name"><?= htmlspecialchars($svc['name']) ?></div>
                            <div class="item-code"><?= htmlspecialchars($svc['slug']) ?></div>
                        </td>
                        <td><span class="category-badge cat-<?= htmlspecialchars($svc['category_slug']) ?>"><?= htmlspecialchars($svc['category_name']) ?></span></td>
                        <td class="money">$<?= number_format($svc['base_rate'], 2) ?></td>
                        <td class="money after-hours">$<?= number_format($svc['after_hours_rate'], 2) ?></td>
                        <td>
                            <button class="status-toggle <?= $svc['active'] ? 'active' : 'inactive' ?>" onclick="toggleServiceStatus(<?= $svc['id'] ?>, this)">
                                <?= $svc['active'] ? 'ACTIVE' : 'INACTIVE' ?>
                            </button>
                        </td>
                        <td>
                            <div class="action-btns">
                                <button class="btn-icon" title="Edit" onclick="editService(<?= $svc['id'] ?>)"><i class="fas fa-edit"></i></button>
                                <button class="btn-icon btn-icon-danger" title="Delete" onclick="deleteService(<?= $svc['id'] ?>, '<?= htmlspecialchars(addslashes($svc['name'])) ?>')"><i class="fas fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Parts Tab Content -->
<div class="catalog-content" id="tab-parts" style="display:none">
    <div class="catalog-toolbar">
        <div class="catalog-search">
            <i class="fas fa-search"></i>
            <input type="text" id="partsSearch" placeholder="Search parts..." oninput="filterParts()">
        </div>
        <select class="catalog-filter" id="partsCategoryFilter" onchange="filterParts()">
            <option value="">All Categories</option>
            <?php foreach ($partCategories as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="toolbar-spacer"></div>
        <button class="btn btn-primary" onclick="openPartModal()">
            <i class="fas fa-plus"></i> Add Part
        </button>
    </div>

    <div class="catalog-table-wrap">
        <table class="catalog-table" id="partsTable">
            <thead>
                <tr>
                    <th>Part Number</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Unit Cost</th>
                    <th>Markup</th>
                    <th>Sell Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th style="width:120px">Actions</th>
                </tr>
            </thead>
            <tbody id="partsBody">
                <?php if (empty($parts)): ?>
                    <tr class="empty-row"><td colspan="9">No parts found. Add your first part above.</td></tr>
                <?php else: ?>
                    <?php foreach ($parts as $part): 
                        $sellPrice = $part['unit_cost'] * (1 + $part['markup_pct'] / 100);
                    ?>
                    <tr data-id="<?= $part['id'] ?>" data-category="<?= htmlspecialchars($part['category']) ?>" data-search="<?= htmlspecialchars(strtolower($part['part_number'] . ' ' . $part['name'] . ' ' . $part['category'])) ?>">
                        <td class="mono"><?= htmlspecialchars($part['part_number']) ?></td>
                        <td>
                            <div class="item-name"><?= htmlspecialchars($part['name']) ?></div>
                            <?php if ($part['description']): ?>
                                <div class="item-desc"><?= htmlspecialchars(substr($part['description'], 0, 50)) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><span class="category-badge"><?= htmlspecialchars($part['category'] ?: '—') ?></span></td>
                        <td class="money">$<?= number_format($part['unit_cost'], 2) ?></td>
                        <td class="mono"><?= number_format($part['markup_pct'], 0) ?>%</td>
                        <td class="money sell-price">$<?= number_format($sellPrice, 2) ?></td>
                        <td class="mono <?= $part['quantity_on_hand'] <= $part['reorder_point'] ? 'low-stock' : '' ?>">
                            <?= $part['quantity_on_hand'] ?>
                            <?php if ($part['quantity_on_hand'] <= $part['reorder_point']): ?>
                                <i class="fas fa-exclamation-triangle" title="Low stock"></i>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="status-toggle <?= $part['active'] ? 'active' : 'inactive' ?>" onclick="togglePartStatus(<?= $part['id'] ?>, this)">
                                <?= $part['active'] ? 'ACTIVE' : 'INACTIVE' ?>
                            </button>
                        </td>
                        <td>
                            <div class="action-btns">
                                <button class="btn-icon" title="Edit" onclick="editPart(<?= $part['id'] ?>)"><i class="fas fa-edit"></i></button>
                                <button class="btn-icon btn-icon-danger" title="Delete" onclick="deletePart(<?= $part['id'] ?>, '<?= htmlspecialchars(addslashes($part['name'])) ?>')"><i class="fas fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Service Modal -->
<div class="rr-modal-overlay hidden" id="serviceModal">
    <div class="rr-modal">
        <div class="rr-modal-header">
            <h3 id="serviceModalTitle">Add Service</h3>
            <button class="rr-modal-close" onclick="closeServiceModal()">&times;</button>
        </div>
        <div class="rr-modal-body">
            <form id="serviceForm">
                <input type="hidden" name="id" id="serviceId">
                <div class="form-row">
                    <div class="form-group">
                        <label>Service Name <span class="req">*</span></label>
                        <input type="text" name="name" id="serviceName" class="form-input" required placeholder="e.g. Jump Start">
                    </div>
                    <div class="form-group">
                        <label>Slug / Code <span class="req">*</span></label>
                        <input type="text" name="slug" id="serviceSlug" class="form-input mono" required placeholder="e.g. jump_start">
                    </div>
                </div>
                <div class="form-group">
                    <label>Category <span class="req">*</span></label>
                    <select name="category_id" id="serviceCategory" class="form-select" required>
                        <option value="">Select category...</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Base Rate ($) <span class="req">*</span></label>
                        <input type="number" name="base_rate" id="serviceBaseRate" class="form-input mono" step="0.01" min="0" required placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label>After Hours Rate ($)</label>
                        <input type="number" name="after_hours_rate" id="serviceAfterHours" class="form-input mono" step="0.01" min="0" placeholder="0.00">
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="serviceDescription" class="form-textarea" rows="2" placeholder="Optional description..."></textarea>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="active" id="serviceActive" checked>
                        <span>Active</span>
                    </label>
                </div>
            </form>
        </div>
        <div class="rr-modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeServiceModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="saveService()">Save Service</button>
        </div>
    </div>
</div>

<!-- Part Modal -->
<div class="rr-modal-overlay hidden" id="partModal">
    <div class="rr-modal rr-modal-lg">
        <div class="rr-modal-header">
            <h3 id="partModalTitle">Add Part</h3>
            <button class="rr-modal-close" onclick="closePartModal()">&times;</button>
        </div>
        <div class="rr-modal-body">
            <form id="partForm">
                <input type="hidden" name="id" id="partId">
                <div class="form-row">
                    <div class="form-group">
                        <label>Part Number <span class="req">*</span></label>
                        <input type="text" name="part_number" id="partNumber" class="form-input mono" required placeholder="e.g. BAT-001">
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <input type="text" name="category" id="partCategory" class="form-input" placeholder="e.g. Batteries" list="partCategoriesList">
                        <datalist id="partCategoriesList">
                            <?php foreach ($partCategories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                </div>
                <div class="form-group">
                    <label>Part Name <span class="req">*</span></label>
                    <input type="text" name="name" id="partName" class="form-input" required placeholder="e.g. Standard Car Battery">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="partDescription" class="form-textarea" rows="2" placeholder="Optional description..."></textarea>
                </div>
                <div class="form-row form-row-3">
                    <div class="form-group">
                        <label>Unit Cost ($) <span class="req">*</span></label>
                        <input type="number" name="unit_cost" id="partUnitCost" class="form-input mono" step="0.01" min="0" required placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label>Markup (%)</label>
                        <input type="number" name="markup_pct" id="partMarkup" class="form-input mono" step="1" min="0" value="50" placeholder="50">
                    </div>
                    <div class="form-group">
                        <label>Sell Price</label>
                        <input type="text" id="partSellPrice" class="form-input mono" readonly disabled>
                    </div>
                </div>
                <div class="form-row form-row-3">
                    <div class="form-group">
                        <label>Qty on Hand</label>
                        <input type="number" name="quantity_on_hand" id="partQty" class="form-input mono" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label>Reorder Point</label>
                        <input type="number" name="reorder_point" id="partReorder" class="form-input mono" min="0" value="5">
                    </div>
                    <div class="form-group">
                        <label>Supplier</label>
                        <input type="text" name="supplier" id="partSupplier" class="form-input" placeholder="Supplier name">
                    </div>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="active" id="partActive" checked>
                        <span>Active</span>
                    </label>
                </div>
            </form>
        </div>
        <div class="rr-modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closePartModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="savePart()">Save Part</button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="rr-modal-overlay hidden" id="deleteModal">
    <div class="rr-modal rr-modal-sm">
        <div class="rr-modal-header">
            <h3>Confirm Delete</h3>
            <button class="rr-modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        <div class="rr-modal-body">
            <p>Are you sure you want to delete <strong id="deleteItemName"></strong>?</p>
            <p class="text-muted">This action cannot be undone.</p>
        </div>
        <div class="rr-modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
            <button type="button" class="btn btn-danger" id="deleteConfirmBtn">Delete</button>
        </div>
    </div>
</div>

<script>
// Store data for JS operations
const servicesData = <?= json_encode($services) ?>;
const partsData = <?= json_encode($parts) ?>;

// API endpoints
const API_CATALOG = 'api/catalog.php';

// Tab switching
function switchTab(tab, btn) {
    document.querySelectorAll('.catalog-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.catalog-content').forEach(c => c.style.display = 'none');
    btn.classList.add('active');
    document.getElementById('tab-' + tab).style.display = 'block';

    // Persist active tab across reloads
    if (tab === 'services' || tab === 'parts') {
        window.location.hash = tab;
    }
}

// Restore active tab on load (after refresh/reload)
(function restoreCatalogTab() {
    const tab = (window.location.hash || '').replace('#', '');
    if (tab !== 'services' && tab !== 'parts') return;
    const btn = document.querySelector('.catalog-tab[data-tab="' + tab + '"]');
    if (btn) switchTab(tab, btn);
})();

// ═══ SERVICES ════════════════════════════════════════════════════════

function setCatalogBodyScrollLocked(locked) {
    const body = document.body;
    if (locked) {
        if (body.dataset.prevOverflow === undefined) {
            body.dataset.prevOverflow = body.style.overflow || '';
        }
        body.style.overflow = 'hidden';
        return;
    }
    if (body.dataset.prevOverflow !== undefined) {
        body.style.overflow = body.dataset.prevOverflow;
        delete body.dataset.prevOverflow;
    } else {
        body.style.overflow = '';
    }
}

function anyCatalogModalOpen() {
    return ['serviceModal', 'partModal', 'deleteModal'].some(id => {
        const el = document.getElementById(id);
        return el && !el.classList.contains('hidden');
    });
}

function syncCatalogScrollLock() {
    setCatalogBodyScrollLocked(anyCatalogModalOpen());
}

function filterServices() {
    const search = document.getElementById('servicesSearch').value.toLowerCase();
    const category = document.getElementById('servicesCategoryFilter').value;
    const rows = document.querySelectorAll('#servicesBody tr[data-id]');
    let visible = 0;
    
    rows.forEach(row => {
        const matchSearch = !search || row.dataset.search.includes(search);
        const matchCat = !category || row.dataset.category === category;
        const show = matchSearch && matchCat;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    
    document.getElementById('servicesCount').textContent = visible;
}

function openServiceModal(data = null) {
    document.getElementById('serviceModalTitle').textContent = data ? 'Edit Service' : 'Add Service';
    document.getElementById('serviceId').value = data?.id || '';
    document.getElementById('serviceName').value = data?.name || '';
    document.getElementById('serviceSlug').value = data?.slug || '';
    document.getElementById('serviceCategory').value = data?.category_id || '';
    document.getElementById('serviceBaseRate').value = data?.base_rate || '';
    document.getElementById('serviceAfterHours').value = data?.after_hours_rate || '';
    document.getElementById('serviceDescription').value = data?.description || '';
    document.getElementById('serviceActive').checked = data ? data.active == 1 : true;
    document.getElementById('serviceModal').classList.remove('hidden');
    syncCatalogScrollLock();
}

function closeServiceModal() {
    document.getElementById('serviceModal').classList.add('hidden');
    syncCatalogScrollLock();
}

function editService(id) {
    const svc = servicesData.find(s => s.id == id);
    if (svc) openServiceModal(svc);
}

async function saveService() {
    const form = document.getElementById('serviceForm');
    const fd = new FormData(form);
    fd.append('action', fd.get('id') ? 'update_service' : 'create_service');
    fd.append('active', document.getElementById('serviceActive').checked ? '1' : '0');
    
    try {
        const res = await fetch(API_CATALOG, { method: 'POST', body: fd });
        const json = await res.json();
        if (json.success) {
            location.reload();
        } else {
            alert('Error: ' + (json.error || 'Unknown error'));
        }
    } catch(e) {
        alert('Network error');
    }
}

async function toggleServiceStatus(id, btn) {
    const fd = new FormData();
    fd.append('action', 'toggle_service_status');
    fd.append('id', id);
    
    try {
        const res = await fetch(API_CATALOG, { method: 'POST', body: fd });
        const json = await res.json();
        if (json.success) {
            const isActive = json.data.active;
            btn.className = 'status-toggle ' + (isActive ? 'active' : 'inactive');
            btn.textContent = isActive ? 'ACTIVE' : 'INACTIVE';
        }
    } catch(e) {}
}

function deleteService(id, name) {
    document.getElementById('deleteItemName').textContent = name;
    document.getElementById('deleteModal').classList.remove('hidden');
    syncCatalogScrollLock();
    document.getElementById('deleteConfirmBtn').onclick = async () => {
        const fd = new FormData();
        fd.append('action', 'delete_service');
        fd.append('id', id);
        try {
            const res = await fetch(API_CATALOG, { method: 'POST', body: fd });
            const json = await res.json();
            if (json.success) location.reload();
            else alert('Error: ' + (json.error || 'Unknown'));
        } catch(e) { alert('Network error'); }
    };
}

// ═══ PARTS ═══════════════════════════════════════════════════════════

function filterParts() {
    const search = document.getElementById('partsSearch').value.toLowerCase();
    const category = document.getElementById('partsCategoryFilter').value;
    const rows = document.querySelectorAll('#partsBody tr[data-id]');
    let visible = 0;
    
    rows.forEach(row => {
        const matchSearch = !search || row.dataset.search.includes(search);
        const matchCat = !category || row.dataset.category === category;
        const show = matchSearch && matchCat;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    
    document.getElementById('partsCount').textContent = visible;
}

function openPartModal(data = null) {
    document.getElementById('partModalTitle').textContent = data ? 'Edit Part' : 'Add Part';
    document.getElementById('partId').value = data?.id || '';
    document.getElementById('partNumber').value = data?.part_number || '';
    document.getElementById('partName').value = data?.name || '';
    document.getElementById('partCategory').value = data?.category || '';
    document.getElementById('partDescription').value = data?.description || '';
    document.getElementById('partUnitCost').value = data?.unit_cost || '';
    document.getElementById('partMarkup').value = data?.markup_pct ?? 50;
    document.getElementById('partQty').value = data?.quantity_on_hand ?? 0;
    document.getElementById('partReorder').value = data?.reorder_point ?? 5;
    document.getElementById('partSupplier').value = data?.supplier || '';
    document.getElementById('partActive').checked = data ? data.active == 1 : true;
    updatePartSellPrice();
    document.getElementById('partModal').classList.remove('hidden');
    syncCatalogScrollLock();
}

function closePartModal() {
    document.getElementById('partModal').classList.add('hidden');
    syncCatalogScrollLock();
}

function editPart(id) {
    const part = partsData.find(p => p.id == id);
    if (part) openPartModal(part);
}

function updatePartSellPrice() {
    const cost = parseFloat(document.getElementById('partUnitCost').value) || 0;
    const markup = parseFloat(document.getElementById('partMarkup').value) || 0;
    const sell = cost * (1 + markup / 100);
    document.getElementById('partSellPrice').value = '$' + sell.toFixed(2);
}

document.getElementById('partUnitCost')?.addEventListener('input', updatePartSellPrice);
document.getElementById('partMarkup')?.addEventListener('input', updatePartSellPrice);

async function savePart() {
    const form = document.getElementById('partForm');
    const fd = new FormData(form);
    fd.append('action', fd.get('id') ? 'update_part' : 'create_part');
    fd.append('active', document.getElementById('partActive').checked ? '1' : '0');
    
    try {
        const res = await fetch(API_CATALOG, { method: 'POST', body: fd });
        const json = await res.json();
        if (json.success) {
            location.reload();
        } else {
            alert('Error: ' + (json.error || 'Unknown error'));
        }
    } catch(e) {
        alert('Network error');
    }
}

async function togglePartStatus(id, btn) {
    const fd = new FormData();
    fd.append('action', 'toggle_part_status');
    fd.append('id', id);
    
    try {
        const res = await fetch(API_CATALOG, { method: 'POST', body: fd });
        const json = await res.json();
        if (json.success) {
            const isActive = json.data.active;
            btn.className = 'status-toggle ' + (isActive ? 'active' : 'inactive');
            btn.textContent = isActive ? 'ACTIVE' : 'INACTIVE';
        }
    } catch(e) {}
}

function deletePart(id, name) {
    document.getElementById('deleteItemName').textContent = name;
    document.getElementById('deleteModal').classList.remove('hidden');
    syncCatalogScrollLock();
    document.getElementById('deleteConfirmBtn').onclick = async () => {
        const fd = new FormData();
        fd.append('action', 'delete_part');
        fd.append('id', id);
        try {
            const res = await fetch(API_CATALOG, { method: 'POST', body: fd });
            const json = await res.json();
            if (json.success) location.reload();
            else alert('Error: ' + (json.error || 'Unknown'));
        } catch(e) { alert('Network error'); }
    };
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    syncCatalogScrollLock();
}

// Close modals on escape
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        closeServiceModal();
        closePartModal();
        closeDeleteModal();
    }
});

// Auto-generate slug from name
document.getElementById('serviceName')?.addEventListener('input', function() {
    const slugField = document.getElementById('serviceSlug');
    if (!slugField.value || slugField.dataset.auto === 'true') {
        slugField.value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
        slugField.dataset.auto = 'true';
    }
});
document.getElementById('serviceSlug')?.addEventListener('input', function() {
    this.dataset.auto = 'false';
});
</script>

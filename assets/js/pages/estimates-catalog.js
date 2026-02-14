// ─── Catalog Panel ──────────────────────────────────────────────────
let catalogData = { services: [], parts: [] };
let catalogTab = 'services';

function toggleCatalog() {
    const panel = document.getElementById('catalogPanel');
    const isOpen = panel.classList.toggle('open');
    if (isOpen && !catalogData.services.length && !catalogData.parts.length) loadCatalog();
}

function switchCatalogTab(tab, btn) {
    catalogTab = tab;
    document.querySelectorAll('.est-catalog-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('catalogSearch').value = '';
    renderCatalog();
}

async function loadCatalog() {
    try {
        const [svcRes, partsRes] = await Promise.all([
            fetch(API_T + '?action=get_services'),
            fetch(API_W + '?action=search_parts&q=')
        ]);
        const svcJson = await svcRes.json();
        const partsJson = await partsRes.json();
        if (svcJson.success) catalogData.services = svcJson.data;
        if (partsJson.success) catalogData.parts = partsJson.data;
        renderCatalog();
    } catch(e) { console.error(e); }
}

function filterCatalog() { renderCatalog(); }

function renderCatalog() {
    const grid = document.getElementById('catalogGrid');
    const search = document.getElementById('catalogSearch').value.toLowerCase().trim();
    let items = catalogTab === 'services' ? catalogData.services : catalogData.parts;
    if (search) {
        items = items.filter(i => {
            const name = (i.name || '').toLowerCase();
            const cat = (i.category_name || i.category || '').toLowerCase();
            const num = (i.part_number || '').toLowerCase();
            return name.includes(search) || cat.includes(search) || num.includes(search);
        });
    }
    if (!items.length) {
        grid.innerHTML = '<div style="text-align:center;padding:30px;color:var(--text-tertiary);grid-column:1/-1">' +
            (search ? 'No matches for "' + search + '"' : 'No items in catalog') + '</div>';
        return;
    }
    if (catalogTab === 'services') {
        grid.innerHTML = items.map(s =>
            '<div class="est-catalog-item" onclick="addCatalogService(' + JSON.stringify(s).replace(/"/g, '&quot;') + ')">' +
            '<div class="item-info"><div class="item-name">' + escHtml(s.name) + '</div>' +
            '<div class="item-meta">' + escHtml(s.category_name || '') + '</div></div>' +
            '<div class="item-price">$' + parseFloat(s.base_rate).toFixed(2) + '</div></div>'
        ).join('');
    } else {
        grid.innerHTML = items.map(p =>
            '<div class="est-catalog-item" onclick="addCatalogPart(' + JSON.stringify(p).replace(/"/g, '&quot;') + ')">' +
            '<div class="item-info"><div class="item-name">' + escHtml(p.name) + '</div>' +
            '<div class="item-meta"><span style="font-family:JetBrains Mono,monospace;font-size:10px">' + escHtml(p.part_number) + '</span> · ' + escHtml(p.category || '') + ' · ' + p.markup_pct + '% markup</div></div>' +
            '<div class="item-price">$' + parseFloat(p.unit_cost).toFixed(2) + '</div></div>'
        ).join('');
    }
}

function addCatalogService(s) {
    document.getElementById('newItemType').value = 'service_fee';
    document.getElementById('newItemDesc').value = s.name;
    document.getElementById('newItemPrice').value = s.base_rate;
    document.getElementById('newItemMarkup').value = '';
    addLineItem();
}

function addCatalogPart(p) {
    document.getElementById('newItemType').value = 'parts';
    document.getElementById('newItemDesc').value = '[' + p.part_number + '] ' + p.name;
    document.getElementById('newItemPrice').value = p.unit_cost;
    document.getElementById('newItemMarkup').value = p.markup_pct;
    addLineItem();
}

<?php
// Services page - integrated with main app navigation
?>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
<script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
<script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>

<style>
/* Service Catalog Specific Styles */
.service-catalog-container {
    margin: -1rem;
    min-height: calc(100vh - 120px);
}

.service-catalog-container .app-header {
    background: linear-gradient(135deg, var(--bg-surface) 0%, var(--bg-secondary) 100%);
    border-bottom: 2px solid var(--navy-500);
    padding: 24px 28px;
}

.service-catalog-container .header-content {
    display: flex;
    align-items: center;
    gap: 14px;
}

.service-catalog-container .header-icon {
    font-size: 26px;
    color: var(--navy-300);
}

.service-catalog-container .header-title-group h1 {
    font-size: 24px;
    font-weight: 700;
    color: var(--navy-300);
    letter-spacing: -0.5px;
    margin: 0;
}

.service-catalog-container .header-subtitle {
    font-size: 13px;
    color: var(--text-secondary);
    margin: 0;
    margin-top: 2px;
}

.service-catalog-container .tab-nav {
    display: flex;
    background: var(--bg-secondary);
    border-bottom: 1px solid var(--border-subtle);
}

.service-catalog-container .tab-button {
    background: none;
    border: none;
    color: var(--text-secondary);
    padding: 14px 24px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all var(--transition-fast);
    position: relative;
}

.service-catalog-container .tab-button:hover {
    color: var(--text-primary);
    background: var(--bg-surface-hover);
}

.service-catalog-container .tab-button.active {
    color: var(--navy-300);
    border-bottom-color: var(--navy-500);
    background: var(--bg-surface);
}

.service-catalog-container .container-body {
    padding: 28px;
}

.service-catalog-container .content-toolbar {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
}

.service-catalog-container .toolbar-spacer {
    flex: 1;
}

.service-catalog-container .form-input {
    background: var(--bg-input);
    border: 1px solid var(--border-medium);
    border-radius: var(--radius-sm);
    padding: 10px 14px;
    color: var(--text-primary);
    font-size: 13px;
    min-width: 280px;
    transition: all var(--transition-fast);
}

.service-catalog-container .form-input:focus {
    outline: none;
    border-color: var(--navy-400);
    box-shadow: 0 0 0 3px var(--navy-glow);
}

.service-catalog-container .form-input::placeholder {
    color: var(--text-tertiary);
}

.service-catalog-container .data-table {
    width: 100%;
    border-collapse: collapse;
    background: var(--bg-surface);
    border: 1px solid var(--border-medium);
    border-radius: var(--radius-md);
    overflow: hidden;
}

.service-catalog-container .data-table thead th {
    background: var(--bg-secondary);
    color: var(--text-tertiary);
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 14px 20px;
    text-align: left;
    border-bottom: 1px solid var(--border-medium);
}

.service-catalog-container .data-table tbody td {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border-subtle);
    color: var(--text-primary);
    font-size: 13px;
}

.service-catalog-container .data-table tbody tr:hover {
    background: var(--bg-surface-hover);
}

.service-catalog-container .table-code {
    font-family: var(--font-mono);
    color: var(--navy-300);
    font-size: 12px;
}

.service-catalog-container .table-name {
    font-weight: 600;
    color: var(--text-primary);
}

.service-catalog-container .table-description {
    color: var(--text-secondary);
    max-width: 300px;
}

.service-catalog-container .table-money {
    font-family: var(--font-mono);
    font-weight: 600;
}

.service-catalog-container .color-success {
    color: var(--green-500);
}

.service-catalog-container .color-warning {
    color: var(--amber-500);
}

.service-catalog-container .color-error {
    color: var(--red-500);
}

.service-catalog-container .color-muted {
    color: var(--text-tertiary);
}

.service-catalog-container .badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: var(--radius-sm);
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.service-catalog-container .status-chip {
    padding: 6px 14px;
    border-radius: var(--radius-sm);
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    border: none;
    cursor: pointer;
    transition: all var(--transition-fast);
}

.service-catalog-container .status-chip.active {
    background: var(--green-glow);
    color: var(--green-500);
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.service-catalog-container .status-chip.inactive {
    background: rgba(92, 100, 120, 0.2);
    color: var(--text-tertiary);
    border: 1px solid rgba(92, 100, 120, 0.3);
}

.service-catalog-container .status-chip:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.service-catalog-container .modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.75);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.service-catalog-container .modal {
    background: var(--bg-surface);
    border: 1px solid var(--border-medium);
    border-radius: var(--radius-lg);
    width: 600px;
    max-width: 90vw;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.service-catalog-container .modal-header {
    background: var(--bg-secondary);
    border-bottom: 1px solid var(--border-medium);
    padding: 20px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.service-catalog-container .modal-title {
    font-size: 15px;
    font-weight: 700;
    color: var(--text-primary);
    letter-spacing: 0.5px;
    margin: 0;
}

.service-catalog-container .modal-close {
    background: none;
    border: none;
    color: var(--text-tertiary);
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--radius-sm);
    transition: all var(--transition-fast);
}

.service-catalog-container .modal-close:hover {
    background: var(--bg-surface-hover);
    color: var(--text-primary);
}

.service-catalog-container .modal-body {
    padding: 24px;
    overflow-y: auto;
    flex: 1;
}

.service-catalog-container .modal-footer {
    border-top: 1px solid var(--border-subtle);
    padding: 20px 24px;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.service-catalog-container .form-field {
    margin-bottom: 20px;
}

.service-catalog-container .form-label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: var(--text-secondary);
    margin-bottom: 8px;
}

.service-catalog-container .form-select,
.service-catalog-container .form-textarea {
    background: var(--bg-input);
    border: 1px solid var(--border-medium);
    border-radius: var(--radius-sm);
    padding: 10px 14px;
    color: var(--text-primary);
    font-size: 13px;
    width: 100%;
    transition: all var(--transition-fast);
}

.service-catalog-container .form-select:focus,
.service-catalog-container .form-textarea:focus {
    outline: none;
    border-color: var(--navy-400);
    box-shadow: 0 0 0 3px var(--navy-glow);
}

.service-catalog-container .form-textarea {
    resize: vertical;
    min-height: 80px;
    font-family: var(--font-sans);
}

.service-catalog-container .form-checkbox-group {
    display: flex;
    gap: 24px;
    margin-bottom: 20px;
}

.service-catalog-container .checkbox-field {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.service-catalog-container .checkbox-field input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: var(--navy-500);
    cursor: pointer;
}

.service-catalog-container .checkbox-field label {
    font-size: 13px;
    color: var(--text-secondary);
    cursor: pointer;
    user-select: none;
}

.service-catalog-container .form-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.service-catalog-container .empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--text-tertiary);
    font-style: italic;
}
</style>

<div class="service-catalog-container">
    <div id="root"></div>
</div>

<script type="text/babel">
const { useState, useEffect } = React;

// Mock data - replace with API calls
const initialServices = [
  {service_id:1, service_code:"RSA-001", service_name:"Jump Start", service_category:"jump_start", description:"Battery boost service", requires_parts:false, mobile_capable:true, shop_only:false, is_active:true},
  {service_id:2, service_code:"RSA-002", service_name:"Tire Change", service_category:"tire", description:"Replace flat tire with spare", requires_parts:false, mobile_capable:true, shop_only:false, is_active:true},
  {service_id:3, service_code:"RSA-003", service_name:"Fuel Delivery", service_category:"fuel_delivery", description:"Emergency fuel delivery", requires_parts:true, mobile_capable:true, shop_only:false, is_active:true},
  {service_id:4, service_code:"RSA-004", service_name:"Lockout Service", service_category:"lockout", description:"Vehicle entry assistance", requires_parts:false, mobile_capable:true, shop_only:false, is_active:true},
  {service_id:5, service_code:"MNT-001", service_name:"Brake Pad Replacement", service_category:"maintenance", description:"Replace worn brake pads", requires_parts:true, mobile_capable:false, shop_only:true, is_active:true},
  {service_id:6, service_code:"MNT-002", service_name:"Oil Change", service_category:"maintenance", description:"Engine oil and filter change", requires_parts:true, mobile_capable:true, shop_only:false, is_active:true},
  {service_id:7, service_code:"REP-001", service_name:"Battery Replacement", service_category:"battery", description:"Install new battery", requires_parts:true, mobile_capable:true, shop_only:false, is_active:true},
  {service_id:8, service_code:"DIG-001", service_name:"Diagnostic Scan", service_category:"diagnostic", description:"OBD-II diagnostic scanning", requires_parts:false, mobile_capable:true, shop_only:false, is_active:true},
  {service_id:9, service_code:"TOW-001", service_name:"Towing Service", service_category:"towing", description:"Vehicle towing up to 50 miles", requires_parts:false, mobile_capable:true, shop_only:false, is_active:true},
];

const initialLabor = [
  {labor_id:1, service_id:1, service_location:"mobile", standard_hours:0.5, labor_rate_per_hour:80, flat_rate_price:50, emergency_surcharge:25, after_hours_surcharge:15},
  {labor_id:2, service_id:2, service_location:"mobile", standard_hours:0.75, labor_rate_per_hour:80, flat_rate_price:60, emergency_surcharge:30, after_hours_surcharge:20},
  {labor_id:3, service_id:3, service_location:"mobile", standard_hours:0.5, labor_rate_per_hour:80, flat_rate_price:45, emergency_surcharge:20, after_hours_surcharge:15},
  {labor_id:4, service_id:4, service_location:"mobile", standard_hours:1, labor_rate_per_hour:100, flat_rate_price:100, emergency_surcharge:50, after_hours_surcharge:30},
  {labor_id:5, service_id:5, service_location:"shop", standard_hours:2, labor_rate_per_hour:90, flat_rate_price:180, emergency_surcharge:0, after_hours_surcharge:0},
  {labor_id:6, service_id:6, service_location:"mobile", standard_hours:1, labor_rate_per_hour:80, flat_rate_price:75, emergency_surcharge:0, after_hours_surcharge:15},
  {labor_id:7, service_id:6, service_location:"shop", standard_hours:0.75, labor_rate_per_hour:70, flat_rate_price:50, emergency_surcharge:0, after_hours_surcharge:0},
  {labor_id:8, service_id:7, service_location:"mobile", standard_hours:0.5, labor_rate_per_hour:80, flat_rate_price:50, emergency_surcharge:20, after_hours_surcharge:15},
];

const CATEGORIES = ["diagnostic","repair","maintenance","roadside_emergency","towing","battery","tire","lockout","fuel_delivery","jump_start","other"];
const LOCATIONS = ["mobile","shop","roadside"];

function App() {
  const [services, setServices] = useState(initialServices);
  const [labor, setLabor] = useState(initialLabor);
  const [activeTab, setActiveTab] = useState("services");
  const [search, setSearch] = useState("");
  const [modal, setModal] = useState(null);
  const [form, setForm] = useState({});
  const [deleteConfirm, setDeleteConfirm] = useState(null);

  const filteredServices = services.filter(s => 
    s.service_name.toLowerCase().includes(search.toLowerCase()) ||
    s.service_code.toLowerCase().includes(search.toLowerCase()) ||
    s.description.toLowerCase().includes(search.toLowerCase())
  );

  const filteredLabor = labor.filter(l => {
    const service = services.find(s => s.service_id === l.service_id);
    return service && (service.service_name.toLowerCase().includes(search.toLowerCase()) || service.service_code.toLowerCase().includes(search.toLowerCase()));
  });

  const handleOpenModal = (type, mode, data) => {
    if (type === "service") {
      setForm(mode === "add" ? {
        service_code: "", service_name: "", service_category: "diagnostic", description: "",
        requires_parts: false, mobile_capable: false, shop_only: false, is_active: true
      } : { ...data });
    } else {
      setForm(mode === "add" ? {
        service_id: "", service_location: "mobile", standard_hours: 1, labor_rate_per_hour: 80,
        flat_rate_price: 0, emergency_surcharge: 0, after_hours_surcharge: 0
      } : { ...data });
    }
    setModal({ type, mode });
  };

  const handleUpdateForm = (key, value) => {
    setForm(prev => ({ ...prev, [key]: value }));
  };

  const handleSaveService = () => {
    if (modal.mode === "add") {
      const newId = Math.max(...services.map(s => s.service_id), 0) + 1;
      setServices([...services, { ...form, service_id: newId }]);
    } else {
      setServices(services.map(s => s.service_id === form.service_id ? form : s));
    }
    setModal(null);
  };

  const handleSaveLabor = () => {
    if (modal.mode === "add") {
      const newId = Math.max(...labor.map(l => l.labor_id), 0) + 1;
      setLabor([...labor, { ...form, labor_id: newId, service_id: parseInt(form.service_id) }]);
    } else {
      setLabor(labor.map(l => l.labor_id === form.labor_id ? form : l));
    }
    setModal(null);
  };

  const handleDelete = () => {
    if (deleteConfirm.type === "service") {
      setServices(services.filter(s => s.service_id !== deleteConfirm.id));
      setLabor(labor.filter(l => l.service_id !== deleteConfirm.id));
    } else {
      setLabor(labor.filter(l => l.labor_id !== deleteConfirm.id));
    }
    setDeleteConfirm(null);
  };

  const handleToggleActive = (serviceId) => {
    setServices(services.map(s => s.service_id === serviceId ? { ...s, is_active: !s.is_active } : s));
  };

  const getServiceName = (serviceId) => {
    const service = services.find(s => s.service_id === serviceId);
    return service ? `${service.service_code} — ${service.service_name}` : "Unknown";
  };

  const formatCategoryLabel = (cat) => cat.split('_').map(w => w[0].toUpperCase() + w.slice(1)).join(' ');
  const formatLocationLabel = (loc) => loc[0].toUpperCase() + loc.slice(1);

  const CategoryBadge = ({ category }) => {
    const colors = {
      diagnostic: "var(--blue-500)", repair: "var(--red-500)", maintenance: "var(--green-500)",
      roadside_emergency: "var(--amber-500)", towing: "var(--purple-500)", battery: "var(--amber-400)",
      tire: "var(--blue-500)", lockout: "var(--purple-500)", fuel_delivery: "var(--amber-500)",
      jump_start: "var(--amber-400)", other: "var(--text-tertiary)"
    };
    return (
      <span className="badge" style={{ background: `${colors[category]}20`, color: colors[category], border: `1px solid ${colors[category]}` }}>
        {formatCategoryLabel(category)}
      </span>
    );
  };

  const Modal = ({ title, onClose, children }) => (
    <div className="modal-overlay" onClick={onClose}>
      <div className="modal" onClick={e => e.stopPropagation()}>
        <div className="modal-header">
          <h2 className="modal-title">{title}</h2>
          <button className="modal-close" onClick={onClose}>×</button>
        </div>
        <div className="modal-body">{children}</div>
      </div>
    </div>
  );

  const FormField = ({ label, children }) => (
    <div className="form-field">
      <label className="form-label">{label}</label>
      {children}
    </div>
  );

  const CheckboxField = ({ label, checked, onChange }) => (
    <div className="checkbox-field">
      <input type="checkbox" checked={checked} onChange={onChange} />
      <label onClick={onChange}>{label}</label>
    </div>
  );

  return (
    <div>
      <div className="app-header">
        <div className="header-content">
          <i className="fas fa-wrench header-icon"></i>
          <div className="header-title-group">
            <h1>Service Catalog</h1>
            <p className="header-subtitle">Manage services, pricing, and labor rates</p>
          </div>
        </div>
      </div>

      <div className="tab-nav">
        <button className={`tab-button ${activeTab === "services" ? "active" : ""}`} onClick={() => setActiveTab("services")}>
          Services
        </button>
        <button className={`tab-button ${activeTab === "labor" ? "active" : ""}`} onClick={() => setActiveTab("labor")}>
          Labor Rates
        </button>
      </div>

      <div className="container-body">
        {activeTab === "services" && (
          <>
            <div className="content-toolbar">
              <input className="form-input" placeholder="Search services..." value={search} onChange={e => setSearch(e.target.value)} />
              <div className="toolbar-spacer" />
              <button className="btn btn-primary" onClick={() => handleOpenModal("service", "add", null)}>+ Add Service</button>
            </div>
            <div style={{ overflowX: "auto" }}>
              <table className="data-table">
                <thead>
                  <tr>
                    <th>Code</th>
                    <th>Service</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Parts</th>
                    <th>Mobile</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {filteredServices.length === 0 ? (
                    <tr><td colSpan={8} className="empty-state">No services found</td></tr>
                  ) : filteredServices.map(s => (
                    <tr key={s.service_id}>
                      <td className="table-code">{s.service_code}</td>
                      <td className="table-name">{s.service_name}</td>
                      <td><CategoryBadge category={s.service_category} /></td>
                      <td className="table-description">{s.description}</td>
                      <td style={{ textAlign: "center", fontSize: 15 }}>{s.requires_parts ? "✓" : "—"}</td>
                      <td style={{ textAlign: "center", fontSize: 15 }}>{s.mobile_capable ? "✓" : "—"}</td>
                      <td>
                        <button className={`status-chip ${s.is_active ? "active" : "inactive"}`} onClick={() => handleToggleActive(s.service_id)}>
                          {s.is_active ? "ACTIVE" : "INACTIVE"}
                        </button>
                      </td>
                      <td>
                        <div style={{ display: "flex", gap: 6 }}>
                          <button className="btn btn-secondary" style={{ padding: "6px 12px", fontSize: 13 }} onClick={() => handleOpenModal("service", "edit", s)}>Edit</button>
                          <button className="btn btn-danger" style={{ padding: "6px 12px", fontSize: 13 }} onClick={() => setDeleteConfirm({ type: "service", id: s.service_id, name: s.service_name })}>Del</button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </>
        )}

        {activeTab === "labor" && (
          <>
            <div className="content-toolbar">
              <input className="form-input" placeholder="Search labor rates..." value={search} onChange={e => setSearch(e.target.value)} />
              <div className="toolbar-spacer" />
              <button className="btn btn-primary" onClick={() => handleOpenModal("labor", "add", null)}>+ Add Labor Rate</button>
            </div>
            <div style={{ overflowX: "auto" }}>
              <table className="data-table">
                <thead>
                  <tr>
                    <th>Service</th>
                    <th>Location</th>
                    <th>Std Hours</th>
                    <th>Rate/Hr</th>
                    <th>Flat Rate</th>
                    <th>Emergency +</th>
                    <th>After Hrs +</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {filteredLabor.length === 0 ? (
                    <tr><td colSpan={8} className="empty-state">No labor rates found</td></tr>
                  ) : filteredLabor.map(l => (
                    <tr key={l.labor_id}>
                      <td className="table-name">{getServiceName(l.service_id)}</td>
                      <td><span className="badge" style={{ background: "var(--blue-glow)", color: "var(--blue-500)", borderColor: "var(--blue-500)", border: "1px solid" }}>{formatLocationLabel(l.service_location)}</span></td>
                      <td style={{ fontFamily: "var(--font-mono)", color: "var(--text-secondary)" }}>{l.standard_hours}h</td>
                      <td className="table-money color-success">${l.labor_rate_per_hour.toFixed(2)}</td>
                      <td className="table-money color-warning">${l.flat_rate_price.toFixed(2)}</td>
                      <td className={`table-money ${l.emergency_surcharge ? "color-error" : "color-muted"}`}>{l.emergency_surcharge ? `+$${l.emergency_surcharge.toFixed(2)}` : "—"}</td>
                      <td style={{ fontFamily: "var(--font-mono)", color: l.after_hours_surcharge ? "var(--purple-500)" : "var(--text-tertiary)" }}>{l.after_hours_surcharge ? `+$${l.after_hours_surcharge.toFixed(2)}` : "—"}</td>
                      <td>
                        <div style={{ display: "flex", gap: 6 }}>
                          <button className="btn btn-secondary" style={{ padding: "6px 12px", fontSize: 13 }} onClick={() => handleOpenModal("labor", "edit", l)}>Edit</button>
                          <button className="btn btn-danger" style={{ padding: "6px 12px", fontSize: 13 }} onClick={() => setDeleteConfirm({ type: "labor", id: l.labor_id, name: getServiceName(l.service_id) })}>Del</button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </>
        )}
      </div>

      {/* SERVICE MODAL */}
      {modal && modal.type === "service" && (
        <Modal title={`${modal.mode === "add" ? "ADD" : "EDIT"} SERVICE`} onClose={() => setModal(null)}>
          <FormField label="Service Code">
            <input className="form-input" value={form.service_code} onChange={e => handleUpdateForm("service_code", e.target.value)} placeholder="e.g. RSA-005" />
          </FormField>
          <FormField label="Service Name">
            <input className="form-input" value={form.service_name} onChange={e => handleUpdateForm("service_name", e.target.value)} placeholder="e.g. Alternator Replacement" />
          </FormField>
          <FormField label="Category">
            <select className="form-select" value={form.service_category} onChange={e => handleUpdateForm("service_category", e.target.value)}>
              {CATEGORIES.map(c => <option key={c} value={c}>{formatCategoryLabel(c)}</option>)}
            </select>
          </FormField>
          <FormField label="Description">
            <textarea className="form-textarea" value={form.description} onChange={e => handleUpdateForm("description", e.target.value)} />
          </FormField>
          <div className="form-checkbox-group">
            <CheckboxField label="Requires Parts" checked={form.requires_parts} onChange={() => handleUpdateForm("requires_parts", !form.requires_parts)} />
            <CheckboxField label="Mobile Capable" checked={form.mobile_capable} onChange={() => handleUpdateForm("mobile_capable", !form.mobile_capable)} />
            <CheckboxField label="Shop Only" checked={form.shop_only} onChange={() => handleUpdateForm("shop_only", !form.shop_only)} />
          </div>
          <CheckboxField label="Active" checked={form.is_active} onChange={() => handleUpdateForm("is_active", !form.is_active)} />
          <div className="modal-footer">
            <button className="btn btn-secondary" onClick={() => setModal(null)}>Cancel</button>
            <button className="btn btn-primary" onClick={handleSaveService}>{modal.mode === "add" ? "Add Service" : "Save Changes"}</button>
          </div>
        </Modal>
      )}

      {/* LABOR MODAL */}
      {modal && modal.type === "labor" && (
        <Modal title={`${modal.mode === "add" ? "ADD" : "EDIT"} LABOR RATE`} onClose={() => setModal(null)}>
          <FormField label="Service">
            <select className="form-select" value={form.service_id} onChange={e => handleUpdateForm("service_id", e.target.value)}>
              <option value="">— Select Service —</option>
              {services.filter(s => s.is_active).map(s => <option key={s.service_id} value={s.service_id}>{s.service_code} — {s.service_name}</option>)}
            </select>
          </FormField>
          <FormField label="Service Location">
            <select className="form-select" value={form.service_location} onChange={e => handleUpdateForm("service_location", e.target.value)}>
              {LOCATIONS.map(l => <option key={l} value={l}>{formatLocationLabel(l)}</option>)}
            </select>
          </FormField>
          <div className="form-grid-2">
            <FormField label="Standard Hours">
              <input className="form-input" type="number" step="0.25" value={form.standard_hours} onChange={e => handleUpdateForm("standard_hours", e.target.value)} />
            </FormField>
            <FormField label="Rate / Hour ($)">
              <input className="form-input" type="number" step="5" value={form.labor_rate_per_hour} onChange={e => handleUpdateForm("labor_rate_per_hour", e.target.value)} />
            </FormField>
            <FormField label="Flat Rate ($)">
              <input className="form-input" type="number" step="5" value={form.flat_rate_price} onChange={e => handleUpdateForm("flat_rate_price", e.target.value)} />
            </FormField>
            <FormField label="Emergency Surcharge ($)">
              <input className="form-input" type="number" step="5" value={form.emergency_surcharge} onChange={e => handleUpdateForm("emergency_surcharge", e.target.value)} />
            </FormField>
            <FormField label="After Hours Surcharge ($)">
              <input className="form-input" type="number" step="5" value={form.after_hours_surcharge} onChange={e => handleUpdateForm("after_hours_surcharge", e.target.value)} />
            </FormField>
          </div>
          <div className="modal-footer">
            <button className="btn btn-secondary" onClick={() => setModal(null)}>Cancel</button>
            <button className="btn btn-primary" onClick={handleSaveLabor}>{modal.mode === "add" ? "Add Rate" : "Save Changes"}</button>
          </div>
        </Modal>
      )}

      {/* DELETE CONFIRMATION */}
      {deleteConfirm && (
        <Modal title="CONFIRM DELETE" onClose={() => setDeleteConfirm(null)}>
          <p style={{ color: "var(--text-secondary)", marginBottom: 6 }}>Are you sure you want to delete <strong style={{ color: "var(--color-error)" }}>{deleteConfirm.name}</strong>?</p>
          {deleteConfirm.type === "service" && <p style={{ color: "var(--text-muted)", fontSize: 12 }}>This will also remove any associated labor rates.</p>}
          <div className="modal-footer">
            <button className="btn btn-secondary" onClick={() => setDeleteConfirm(null)}>Cancel</button>
            <button className="btn btn-danger" onClick={handleDelete}>Delete</button>
          </div>
        </Modal>
      )}
    </div>
  );
}

const root = ReactDOM.createRoot(document.getElementById('root'));
root.render(<App />);
</script>

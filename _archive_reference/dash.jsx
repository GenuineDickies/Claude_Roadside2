import { useState } from "react";

// ── Data ──────────────────────────────────────────────────────
const INITIAL_SERVICES = [
  { service_id: 1, service_code: "RSA-001", service_name: "Jump Start", service_category: "jump_start", description: "Battery jump start service", requires_parts: true, mobile_capable: true, shop_only: false, is_active: true },
  { service_id: 2, service_code: "RSA-002", service_name: "Flat Tire Change", service_category: "tire", description: "Spare tire mount & inflation", requires_parts: false, mobile_capable: true, shop_only: false, is_active: true },
  { service_id: 3, service_code: "RSA-003", service_name: "Fuel Delivery", service_category: "fuel_delivery", description: "Emergency fuel delivery (up to 2 gal)", requires_parts: true, mobile_capable: true, shop_only: false, is_active: true },
  { service_id: 4, service_code: "RSA-004", service_name: "Vehicle Lockout", service_category: "lockout", description: "Non-destructive vehicle entry", requires_parts: false, mobile_capable: true, shop_only: false, is_active: true },
  { service_id: 5, service_code: "MEC-001", service_name: "Brake Pad Replacement", service_category: "repair", description: "Front or rear brake pad replacement", requires_parts: true, mobile_capable: true, shop_only: false, is_active: true },
  { service_id: 6, service_code: "MEC-002", service_name: "Oil Change", service_category: "maintenance", description: "Full synthetic oil change with filter", requires_parts: true, mobile_capable: true, shop_only: false, is_active: true },
  { service_id: 7, service_code: "MEC-003", service_name: "Battery Replacement", service_category: "battery", description: "Battery test, removal, and install", requires_parts: true, mobile_capable: true, shop_only: false, is_active: false },
  { service_id: 8, service_code: "DIA-001", service_name: "OBD-II Diagnostic Scan", service_category: "diagnostic", description: "Full vehicle diagnostic scan & report", requires_parts: false, mobile_capable: true, shop_only: false, is_active: true },
  { service_id: 9, service_code: "TOW-001", service_name: "Local Tow (0-10 mi)", service_category: "towing", description: "Flatbed tow within 10 miles", requires_parts: false, mobile_capable: false, shop_only: false, is_active: true },
];

const INITIAL_LABOR = [
  { labor_id: 1, service_id: 1, service_location: "mobile_onsite", standard_hours: 0.5, labor_rate_per_hour: 95.00, flat_rate_price: 75.00, emergency_surcharge: 35.00, after_hours_surcharge: 50.00 },
  { labor_id: 2, service_id: 2, service_location: "mobile_onsite", standard_hours: 0.75, labor_rate_per_hour: 95.00, flat_rate_price: 85.00, emergency_surcharge: 35.00, after_hours_surcharge: 50.00 },
  { labor_id: 3, service_id: 3, service_location: "mobile_onsite", standard_hours: 0.5, labor_rate_per_hour: 95.00, flat_rate_price: 65.00, emergency_surcharge: 25.00, after_hours_surcharge: 40.00 },
  { labor_id: 4, service_id: 4, service_location: "mobile_onsite", standard_hours: 0.5, labor_rate_per_hour: 95.00, flat_rate_price: 70.00, emergency_surcharge: 30.00, after_hours_surcharge: 45.00 },
  { labor_id: 5, service_id: 5, service_location: "both", standard_hours: 1.5, labor_rate_per_hour: 110.00, flat_rate_price: 165.00, emergency_surcharge: 50.00, after_hours_surcharge: 65.00 },
  { labor_id: 6, service_id: 6, service_location: "both", standard_hours: 0.75, labor_rate_per_hour: 95.00, flat_rate_price: 89.00, emergency_surcharge: 0, after_hours_surcharge: 40.00 },
  { labor_id: 7, service_id: 8, service_location: "mobile_onsite", standard_hours: 0.5, labor_rate_per_hour: 95.00, flat_rate_price: 49.00, emergency_surcharge: 20.00, after_hours_surcharge: 35.00 },
  { labor_id: 8, service_id: 9, service_location: "mobile_onsite", standard_hours: 1.0, labor_rate_per_hour: 0, flat_rate_price: 125.00, emergency_surcharge: 50.00, after_hours_surcharge: 75.00 },
];

const CATEGORIES = ["diagnostic","repair","maintenance","roadside_emergency","towing","battery","tire","lockout","fuel_delivery","jump_start","other"];
const LOCATIONS = ["mobile_onsite", "shop", "both"];
const PRIORITIES = ["emergency", "urgent", "scheduled"];
const TECHNICIANS = [
  { id: 1, name: "Mike R.", unit: "Unit 01", status: "available" },
  { id: 2, name: "Carlos S.", unit: "Unit 02", status: "on_call" },
  { id: 3, name: "Dave T.", unit: "Unit 03", status: "available" },
  { id: 4, name: "James L.", unit: "Unit 04", status: "unavailable" },
];

const catLabel = c => c.replace(/_/g, " ").replace(/\b\w/g, l => l.toUpperCase());
const locLabel = l => ({ mobile_onsite: "Mobile / On-Site", shop: "Shop Only", both: "Both" }[l] || l);
const catColor = c => ({ diagnostic:"#3b82f6", repair:"#ef4444", maintenance:"#22c55e", roadside_emergency:"#f59e0b", towing:"#8b5cf6", battery:"#f97316", tire:"#06b6d4", lockout:"#ec4899", fuel_delivery:"#eab308", jump_start:"#f97316", other:"#6b7280" }[c] || "#6b7280");
const priorityColor = p => ({ emergency: "#ef4444", urgent: "#f59e0b", scheduled: "#3b82f6" }[p] || "#6b7280");
const techStatusColor = s => ({ available: "#22c55e", on_call: "#f59e0b", unavailable: "#ef4444" }[s] || "#666");

// ── Shared UI ─────────────────────────────────────────────────
const Modal = ({ title, onClose, children }) => (
  <div style={{ position:"fixed", inset:0, background:"rgba(0,0,0,0.6)", display:"flex", alignItems:"center", justifyContent:"center", zIndex:1000, backdropFilter:"blur(4px)" }} onClick={onClose}>
    <div onClick={e => e.stopPropagation()} style={{ background:"#1a1a1a", border:"1px solid #333", borderRadius:8, width:"90%", maxWidth:560, maxHeight:"85vh", overflow:"auto", boxShadow:"0 24px 48px rgba(0,0,0,0.5)" }}>
      <div style={{ display:"flex", justifyContent:"space-between", alignItems:"center", padding:"16px 20px", borderBottom:"1px solid #333" }}>
        <h3 style={{ margin:0, fontFamily:"'DM Mono', monospace", fontSize:15, color:"#fbbf24", letterSpacing:1 }}>{title}</h3>
        <button onClick={onClose} style={{ background:"none", border:"none", color:"#666", fontSize:22, cursor:"pointer", lineHeight:1 }}>✕</button>
      </div>
      <div style={{ padding:20 }}>{children}</div>
    </div>
  </div>
);

const Field = ({ label, children }) => (
  <div style={{ marginBottom:14 }}>
    <label style={{ display:"block", fontSize:11, color:"#888", marginBottom:4, fontFamily:"'DM Mono', monospace", textTransform:"uppercase", letterSpacing:1 }}>{label}</label>
    {children}
  </div>
);

const inputStyle = { width:"100%", padding:"8px 10px", background:"#111", border:"1px solid #333", borderRadius:4, color:"#e5e5e5", fontSize:14, fontFamily:"'DM Mono', monospace", boxSizing:"border-box", outline:"none" };
const selectStyle = { ...inputStyle, appearance:"none" };
const btnStyle = (color = "#fbbf24") => ({ padding:"8px 18px", background:color, border:"none", borderRadius:4, color: color==="#fbbf24"?"#111":"#fff", fontSize:13, fontWeight:600, cursor:"pointer", fontFamily:"'DM Mono', monospace", letterSpacing:0.5 });

const CheckboxField = ({ label, checked, onChange }) => (
  <label style={{ display:"flex", alignItems:"center", gap:8, cursor:"pointer", fontSize:13, color:"#ccc", marginBottom:8 }}>
    <input type="checkbox" checked={checked} onChange={onChange} style={{ accentColor:"#fbbf24" }} />{label}
  </label>
);

const Badge = ({ color, children }) => (
  <span style={{ display:"inline-block", padding:"2px 8px", borderRadius:3, fontSize:11, background:color+"22", color, border:`1px solid ${color}44`, fontFamily:"'DM Mono', monospace" }}>{children}</span>
);

// ── Main App ──────────────────────────────────────────────────
export default function App() {
  const [services, setServices] = useState(INITIAL_SERVICES);
  const [labor, setLabor] = useState(INITIAL_LABOR);
  const [tab, setTab] = useState("dispatch");
  const [search, setSearch] = useState("");
  const [filterCat, setFilterCat] = useState("all");
  const [filterActive, setFilterActive] = useState("all");
  const [modal, setModal] = useState(null);
  const [deleteConfirm, setDeleteConfirm] = useState(null);
  const [form, setForm] = useState({});
  const [requests, setRequests] = useState([]);
  const [submitFlash, setSubmitFlash] = useState(false);

  const emptyService = { service_code:"", service_name:"", service_category:"diagnostic", description:"", requires_parts:false, mobile_capable:true, shop_only:false, is_active:true };
  const emptyLabor = { service_id:"", service_location:"mobile_onsite", standard_hours:"", labor_rate_per_hour:"", flat_rate_price:"", emergency_surcharge:"", after_hours_surcharge:"" };
  const emptyRequest = { customer_name:"", customer_phone:"", vehicle_year:"", vehicle_make:"", vehicle_model:"", vehicle_color:"", location_address:"", location_notes:"", selected_services:[], priority:"emergency", assigned_tech:"", is_after_hours:false, dispatcher_notes:"", payment_method:"pending" };
  const [reqForm, setReqForm] = useState(emptyRequest);

  const openModal = (type, mode, data) => { setForm(data || (type==="service"?emptyService:emptyLabor)); setModal({type,mode}); };
  const updateForm = (k,v) => setForm(f => ({...f,[k]:v}));
  const updateReq = (k,v) => setReqForm(f => ({...f,[k]:v}));
  const toggleReqService = (sid) => setReqForm(f => ({...f, selected_services: f.selected_services.includes(sid) ? f.selected_services.filter(id=>id!==sid) : [...f.selected_services, sid] }));

  const saveService = () => {
    if (!form.service_code || !form.service_name) return;
    if (modal.mode==="add") { setServices(s=>[...s,{...form,service_id:Math.max(0,...s.map(x=>x.service_id))+1}]); }
    else { setServices(s=>s.map(svc=>svc.service_id===form.service_id?form:svc)); }
    setModal(null);
  };

  const saveLabor = () => {
    if (!form.service_id) return;
    const p = { ...form, service_id:parseInt(form.service_id), standard_hours:parseFloat(form.standard_hours)||0, labor_rate_per_hour:parseFloat(form.labor_rate_per_hour)||0, flat_rate_price:parseFloat(form.flat_rate_price)||0, emergency_surcharge:parseFloat(form.emergency_surcharge)||0, after_hours_surcharge:parseFloat(form.after_hours_surcharge)||0 };
    if (modal.mode==="add") { setLabor(l=>[...l,{...p,labor_id:Math.max(0,...l.map(x=>x.labor_id))+1}]); }
    else { setLabor(l=>l.map(lr=>lr.labor_id===form.labor_id?p:lr)); }
    setModal(null);
  };

  const deleteItem = () => {
    if (!deleteConfirm) return;
    if (deleteConfirm.type==="service") { setServices(s=>s.filter(x=>x.service_id!==deleteConfirm.id)); setLabor(l=>l.filter(x=>x.service_id!==deleteConfirm.id)); }
    else { setLabor(l=>l.filter(x=>x.labor_id!==deleteConfirm.id)); }
    setDeleteConfirm(null);
  };

  const toggleActive = id => setServices(s=>s.map(x=>x.service_id===id?{...x,is_active:!x.is_active}:x));

  const calcEstimate = () => {
    let total = 0;
    reqForm.selected_services.forEach(sid => {
      const lr = labor.find(l => l.service_id === sid);
      if (lr) {
        total += lr.flat_rate_price || 0;
        if (reqForm.priority === "emergency") total += lr.emergency_surcharge || 0;
        if (reqForm.is_after_hours) total += lr.after_hours_surcharge || 0;
      }
    });
    return total;
  };

  const submitRequest = () => {
    if (!reqForm.customer_name || !reqForm.customer_phone || reqForm.selected_services.length===0) return;
    setRequests(r => [{ ...reqForm, request_id:`SR-${String(r.length+1001).padStart(4,"0")}`, created_at:new Date().toLocaleString(), estimate:calcEstimate(), status:"dispatched" }, ...r]);
    setReqForm(emptyRequest);
    setSubmitFlash(true);
    setTimeout(() => setSubmitFlash(false), 2000);
  };

  const filteredServices = services.filter(s => {
    if (search && !s.service_name.toLowerCase().includes(search.toLowerCase()) && !s.service_code.toLowerCase().includes(search.toLowerCase())) return false;
    if (filterCat!=="all" && s.service_category!==filterCat) return false;
    if (filterActive==="active" && !s.is_active) return false;
    if (filterActive==="inactive" && s.is_active) return false;
    return true;
  });
  const filteredLabor = labor.filter(l => { const svc=services.find(s=>s.service_id===l.service_id); return !(search && svc && !svc.service_name.toLowerCase().includes(search.toLowerCase())); });

  const getServiceName = sid => services.find(s=>s.service_id===sid)?.service_name || `ID: ${sid}`;
  const activeServices = services.filter(s => s.is_active);
  const totalActive = activeServices.length;
  const avgFlatRate = labor.length ? (labor.reduce((a,l)=>a+(l.flat_rate_price||0),0)/labor.length).toFixed(2) : "0.00";
  const mobileCount = services.filter(s=>s.mobile_capable&&s.is_active).length;

  const servicesByCategory = {};
  activeServices.forEach(s => { if (!servicesByCategory[s.service_category]) servicesByCategory[s.service_category]=[]; servicesByCategory[s.service_category].push(s); });

  const canSubmit = reqForm.customer_name && reqForm.customer_phone && reqForm.selected_services.length > 0;

  return (
    <div style={{ fontFamily:"'DM Sans', sans-serif", background:"#0d0d0d", color:"#e5e5e5", minHeight:"100vh" }}>
      <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=DM+Sans:wght@400;500;700&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet" />

      {/* Header */}
      <div style={{ background:"linear-gradient(135deg, #1a1a1a 0%, #111 100%)", borderBottom:"2px solid #fbbf24", padding:"16px 24px" }}>
        <div style={{ display:"flex", alignItems:"center", gap:12 }}>
          <span style={{ fontSize:22 }}>🔧</span>
          <div>
            <h1 style={{ margin:0, fontFamily:"'Oswald', sans-serif", fontSize:24, fontWeight:700, color:"#fbbf24", letterSpacing:2, textTransform:"uppercase" }}>Service Catalog</h1>
            <p style={{ margin:0, fontSize:12, color:"#555", fontFamily:"'DM Mono', monospace" }}>Roadside Assistance & Mobile Mechanic — Admin Dashboard</p>
          </div>
        </div>
      </div>

      {/* Tab Bar */}
      <div style={{ display:"flex", background:"#111", borderBottom:"1px solid #222", padding:"0 24px" }}>
        {[{id:"dispatch",icon:"📞",label:"Dispatch"},{id:"services",icon:"⚙",label:"Services"},{id:"labor",icon:"$",label:"Labor Rates"}].map(t => (
          <button key={t.id} onClick={() => { setTab(t.id); setSearch(""); setFilterCat("all"); setFilterActive("all"); }}
            style={{ padding:"12px 20px", background:"transparent", border:"none", borderBottom: tab===t.id?"2px solid #fbbf24":"2px solid transparent", color:tab===t.id?"#fbbf24":"#666", cursor:"pointer", fontSize:13, fontWeight:600, fontFamily:"'DM Mono', monospace", letterSpacing:1, display:"flex", alignItems:"center", gap:6, transition:"color 0.15s" }}>
            <span>{t.icon}</span> {t.label}
            {t.id==="dispatch" && requests.length>0 && <span style={{ background:"#ef4444", color:"#fff", borderRadius:10, padding:"1px 7px", fontSize:10, fontWeight:700, marginLeft:4 }}>{requests.length}</span>}
          </button>
        ))}
      </div>

      {/* Stats (services/labor only) */}
      {tab!=="dispatch" && (
        <div style={{ display:"flex", gap:12, padding:"16px 24px", flexWrap:"wrap" }}>
          {[{l:"Active Services",v:totalActive,a:"#22c55e"},{l:"Avg Flat Rate",v:`$${avgFlatRate}`,a:"#fbbf24"},{l:"Mobile-Ready",v:mobileCount,a:"#3b82f6"},{l:"Labor Entries",v:labor.length,a:"#a855f7"}].map(({l,v,a})=>(
            <div key={l} style={{ flex:"1 1 140px", background:"#151515", border:"1px solid #222", borderRadius:6, padding:"14px 16px", borderLeft:`3px solid ${a}` }}>
              <div style={{ fontSize:11, color:"#666", fontFamily:"'DM Mono', monospace", textTransform:"uppercase", letterSpacing:1, marginBottom:4 }}>{l}</div>
              <div style={{ fontSize:24, fontWeight:700, color:a, fontFamily:"'Oswald', sans-serif" }}>{v}</div>
            </div>
          ))}
        </div>
      )}

      {/* ═══════════ DISPATCH TAB ═══════════ */}
      {tab==="dispatch" && (
        <div style={{ padding:"20px 24px" }}>
          {submitFlash && (
            <div style={{ background:"#22c55e22", border:"1px solid #22c55e44", borderRadius:6, padding:"12px 16px", marginBottom:16, display:"flex", alignItems:"center", gap:10 }}>
              <span style={{ fontSize:18 }}>✅</span>
              <span style={{ color:"#22c55e", fontFamily:"'DM Mono', monospace", fontSize:13, fontWeight:600 }}>Service request dispatched successfully!</span>
            </div>
          )}
          <div style={{ display:"grid", gridTemplateColumns:"1fr 340px", gap:20, alignItems:"start" }}>
            {/* Left: Form */}
            <div>
              {/* Customer */}
              <div style={{ background:"#151515", border:"1px solid #222", borderRadius:8, padding:20, marginBottom:16 }}>
                <h3 style={{ margin:"0 0 14px", fontFamily:"'Oswald', sans-serif", fontSize:16, color:"#fbbf24", letterSpacing:1, textTransform:"uppercase" }}>👤 Customer Info</h3>
                <div style={{ display:"grid", gridTemplateColumns:"1fr 1fr", gap:12 }}>
                  <Field label="Name *"><input style={inputStyle} value={reqForm.customer_name} onChange={e=>updateReq("customer_name",e.target.value)} placeholder="John Doe" /></Field>
                  <Field label="Phone *"><input style={inputStyle} value={reqForm.customer_phone} onChange={e=>updateReq("customer_phone",e.target.value)} placeholder="(503) 555-0123" /></Field>
                </div>
              </div>

              {/* Vehicle */}
              <div style={{ background:"#151515", border:"1px solid #222", borderRadius:8, padding:20, marginBottom:16 }}>
                <h3 style={{ margin:"0 0 14px", fontFamily:"'Oswald', sans-serif", fontSize:16, color:"#fbbf24", letterSpacing:1, textTransform:"uppercase" }}>🚗 Vehicle</h3>
                <div style={{ display:"grid", gridTemplateColumns:"80px 1fr 1fr 100px", gap:12 }}>
                  <Field label="Year"><input style={inputStyle} value={reqForm.vehicle_year} onChange={e=>updateReq("vehicle_year",e.target.value)} placeholder="2019" maxLength={4} /></Field>
                  <Field label="Make"><input style={inputStyle} value={reqForm.vehicle_make} onChange={e=>updateReq("vehicle_make",e.target.value)} placeholder="Honda" /></Field>
                  <Field label="Model"><input style={inputStyle} value={reqForm.vehicle_model} onChange={e=>updateReq("vehicle_model",e.target.value)} placeholder="Civic" /></Field>
                  <Field label="Color"><input style={inputStyle} value={reqForm.vehicle_color} onChange={e=>updateReq("vehicle_color",e.target.value)} placeholder="Silver" /></Field>
                </div>
              </div>

              {/* Location */}
              <div style={{ background:"#151515", border:"1px solid #222", borderRadius:8, padding:20, marginBottom:16 }}>
                <h3 style={{ margin:"0 0 14px", fontFamily:"'Oswald', sans-serif", fontSize:16, color:"#fbbf24", letterSpacing:1, textTransform:"uppercase" }}>📍 Location</h3>
                <Field label="Address / Cross Streets"><input style={inputStyle} value={reqForm.location_address} onChange={e=>updateReq("location_address",e.target.value)} placeholder="123 Main St, Portland OR — or — I-5 NB near Exit 302" /></Field>
                <Field label="Location Details"><input style={inputStyle} value={reqForm.location_notes} onChange={e=>updateReq("location_notes",e.target.value)} placeholder="Parking lot B, near the dumpsters / shoulder of highway" /></Field>
              </div>

              {/* Services Quick-Pick */}
              <div style={{ background:"#151515", border:"1px solid #222", borderRadius:8, padding:20, marginBottom:16 }}>
                <h3 style={{ margin:"0 0 14px", fontFamily:"'Oswald', sans-serif", fontSize:16, color:"#fbbf24", letterSpacing:1, textTransform:"uppercase" }}>🛠 Services Needed *</h3>
                {Object.entries(servicesByCategory).map(([cat, svcs]) => (
                  <div key={cat} style={{ marginBottom:12 }}>
                    <div style={{ fontSize:10, color:"#666", fontFamily:"'DM Mono', monospace", textTransform:"uppercase", letterSpacing:1, marginBottom:6 }}>{catLabel(cat)}</div>
                    <div style={{ display:"flex", flexWrap:"wrap", gap:8 }}>
                      {svcs.map(s => {
                        const sel = reqForm.selected_services.includes(s.service_id);
                        const lr = labor.find(l => l.service_id === s.service_id);
                        return (
                          <button key={s.service_id} onClick={() => toggleReqService(s.service_id)}
                            style={{ padding:"8px 14px", borderRadius:5, cursor:"pointer", fontSize:12, fontFamily:"'DM Mono', monospace", fontWeight:sel?700:400, background:sel?catColor(cat)+"33":"#0d0d0d", border:`1px solid ${sel?catColor(cat):"#333"}`, color:sel?catColor(cat):"#999", transition:"all 0.15s" }}>
                            {s.service_name}{lr && <span style={{ opacity:0.6, marginLeft:6 }}>${lr.flat_rate_price}</span>}
                          </button>
                        );
                      })}
                    </div>
                  </div>
                ))}
              </div>

              {/* Notes */}
              <div style={{ background:"#151515", border:"1px solid #222", borderRadius:8, padding:20 }}>
                <h3 style={{ margin:"0 0 14px", fontFamily:"'Oswald', sans-serif", fontSize:16, color:"#fbbf24", letterSpacing:1, textTransform:"uppercase" }}>📝 Dispatcher Notes</h3>
                <textarea style={{ ...inputStyle, minHeight:70, resize:"vertical" }} value={reqForm.dispatcher_notes} onChange={e=>updateReq("dispatcher_notes",e.target.value)} placeholder="Customer says car won't turn over, clicking sound. AAA card expired..." />
              </div>
            </div>

            {/* Right: Sidebar */}
            <div style={{ position:"sticky", top:20 }}>
              {/* Priority & Options */}
              <div style={{ background:"#151515", border:"1px solid #222", borderRadius:8, padding:20, marginBottom:16 }}>
                <Field label="Priority">
                  <div style={{ display:"flex", gap:6 }}>
                    {PRIORITIES.map(p => (
                      <button key={p} onClick={() => updateReq("priority",p)}
                        style={{ flex:1, padding:"8px 4px", borderRadius:4, cursor:"pointer", fontSize:11, fontFamily:"'DM Mono', monospace", fontWeight:700, textTransform:"uppercase", letterSpacing:0.5, border:"1px solid", background:reqForm.priority===p?priorityColor(p)+"33":"#0d0d0d", borderColor:reqForm.priority===p?priorityColor(p):"#333", color:reqForm.priority===p?priorityColor(p):"#666", transition:"all 0.15s" }}>
                        {p==="emergency"?"🚨 ":p==="urgent"?"⚡ ":"📅 "}{p}
                      </button>
                    ))}
                  </div>
                </Field>
                <CheckboxField label="After Hours Call" checked={reqForm.is_after_hours} onChange={() => updateReq("is_after_hours",!reqForm.is_after_hours)} />
                <Field label="Payment">
                  <select style={selectStyle} value={reqForm.payment_method} onChange={e=>updateReq("payment_method",e.target.value)}>
                    <option value="pending">Collect on Site</option>
                    <option value="card_on_file">Card on File</option>
                    <option value="insurance">Insurance / 3rd Party</option>
                    <option value="fleet_account">Fleet Account</option>
                  </select>
                </Field>
              </div>

              {/* Assign Tech */}
              <div style={{ background:"#151515", border:"1px solid #222", borderRadius:8, padding:20, marginBottom:16 }}>
                <Field label="Assign Technician">
                  <div style={{ display:"flex", flexDirection:"column", gap:6 }}>
                    {TECHNICIANS.map(t => (
                      <button key={t.id} onClick={() => t.status!=="unavailable" && updateReq("assigned_tech",t.id)}
                        style={{ display:"flex", alignItems:"center", justifyContent:"space-between", padding:"8px 12px", borderRadius:4, cursor:t.status==="unavailable"?"not-allowed":"pointer", background:reqForm.assigned_tech===t.id?"#fbbf2418":"#0d0d0d", border:`1px solid ${reqForm.assigned_tech===t.id?"#fbbf24":"#333"}`, opacity:t.status==="unavailable"?0.4:1, transition:"all 0.15s" }}>
                        <div>
                          <span style={{ color:reqForm.assigned_tech===t.id?"#fbbf24":"#ccc", fontSize:13, fontWeight:600 }}>{t.name}</span>
                          <span style={{ color:"#666", fontSize:11, marginLeft:8 }}>{t.unit}</span>
                        </div>
                        <span style={{ width:8, height:8, borderRadius:"50%", background:techStatusColor(t.status) }} />
                      </button>
                    ))}
                  </div>
                </Field>
              </div>

              {/* Estimate & Submit */}
              <div style={{ background:"#1a1a0a", border:"1px solid #fbbf2444", borderRadius:8, padding:20 }}>
                <div style={{ fontSize:11, color:"#888", fontFamily:"'DM Mono', monospace", textTransform:"uppercase", letterSpacing:1, marginBottom:8 }}>Estimated Total</div>
                <div style={{ fontFamily:"'Oswald', sans-serif", fontSize:36, fontWeight:700, color:"#fbbf24", marginBottom:4 }}>${calcEstimate().toFixed(2)}</div>
                <div style={{ fontSize:11, color:"#666", fontFamily:"'DM Mono', monospace", marginBottom:16 }}>
                  {reqForm.selected_services.length} service{reqForm.selected_services.length!==1?"s":""}{reqForm.priority==="emergency"?" + emergency":""}{reqForm.is_after_hours?" + after hrs":""}
                </div>
                <button onClick={submitRequest} disabled={!canSubmit}
                  style={{ width:"100%", padding:"12px", background:canSubmit?"#fbbf24":"#333", color:canSubmit?"#111":"#666", border:"none", borderRadius:6, fontSize:14, fontWeight:700, cursor:canSubmit?"pointer":"not-allowed", fontFamily:"'Oswald', sans-serif", letterSpacing:1, textTransform:"uppercase" }}>
                  📞 Dispatch Service Request
                </button>
              </div>

              {/* Recent Requests */}
              {requests.length>0 && (
                <div style={{ background:"#151515", border:"1px solid #222", borderRadius:8, padding:16, marginTop:16 }}>
                  <div style={{ fontSize:11, color:"#888", fontFamily:"'DM Mono', monospace", textTransform:"uppercase", letterSpacing:1, marginBottom:10 }}>Recent Dispatches</div>
                  {requests.slice(0,5).map((r,i) => (
                    <div key={i} style={{ padding:"8px 0", borderBottom:i<Math.min(requests.length,5)-1?"1px solid #222":"none", display:"flex", justifyContent:"space-between", alignItems:"center" }}>
                      <div>
                        <span style={{ color:"#fbbf24", fontFamily:"'DM Mono', monospace", fontSize:11, marginRight:8 }}>{r.request_id}</span>
                        <span style={{ color:"#ccc", fontSize:12 }}>{r.customer_name}</span>
                      </div>
                      <span style={{ color:"#22c55e", fontFamily:"'DM Mono', monospace", fontSize:11 }}>${r.estimate.toFixed(2)}</span>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        </div>
      )}

      {/* ═══════════ SERVICES TAB ═══════════ */}
      {tab==="services" && (
        <div style={{ padding:"0 24px 24px" }}>
          <div style={{ display:"flex", alignItems:"center", gap:10, marginBottom:14, marginTop:16, flexWrap:"wrap" }}>
            <input placeholder="Search services..." value={search} onChange={e=>setSearch(e.target.value)} style={{ ...inputStyle, maxWidth:260, background:"#151515" }} />
            <select value={filterCat} onChange={e=>setFilterCat(e.target.value)} style={{ ...selectStyle, maxWidth:180, background:"#151515" }}><option value="all">All Categories</option>{CATEGORIES.map(c=><option key={c} value={c}>{catLabel(c)}</option>)}</select>
            <select value={filterActive} onChange={e=>setFilterActive(e.target.value)} style={{ ...selectStyle, maxWidth:140, background:"#151515" }}><option value="all">All Status</option><option value="active">Active</option><option value="inactive">Inactive</option></select>
            <div style={{ flex:1 }} />
            <button onClick={()=>openModal("service","add",null)} style={btnStyle()}>+ Add Service</button>
          </div>
          <div style={{ overflowX:"auto" }}>
            <table style={{ width:"100%", borderCollapse:"collapse", fontSize:13 }}>
              <thead><tr style={{ borderBottom:"2px solid #333" }}>
                {["Code","Service Name","Category","Description","Parts","Mobile","Status","Actions"].map(h=><th key={h} style={{ textAlign:"left", padding:"10px 12px", color:"#888", fontFamily:"'DM Mono', monospace", fontSize:11, textTransform:"uppercase", letterSpacing:1, fontWeight:500 }}>{h}</th>)}
              </tr></thead>
              <tbody>
                {filteredServices.length===0 ? <tr><td colSpan={8} style={{ textAlign:"center", padding:40, color:"#555" }}>No services found</td></tr> :
                filteredServices.map(s => (
                  <tr key={s.service_id} style={{ borderBottom:"1px solid #1e1e1e" }} onMouseEnter={e=>e.currentTarget.style.background="#151515"} onMouseLeave={e=>e.currentTarget.style.background="transparent"}>
                    <td style={{ padding:"10px 12px", fontFamily:"'DM Mono', monospace", color:"#fbbf24", fontSize:12 }}>{s.service_code}</td>
                    <td style={{ padding:"10px 12px", fontWeight:600 }}>{s.service_name}</td>
                    <td style={{ padding:"10px 12px" }}><Badge color={catColor(s.service_category)}>{catLabel(s.service_category)}</Badge></td>
                    <td style={{ padding:"10px 12px", color:"#999", maxWidth:200, overflow:"hidden", textOverflow:"ellipsis", whiteSpace:"nowrap" }}>{s.description}</td>
                    <td style={{ padding:"10px 12px", textAlign:"center" }}>{s.requires_parts?"✓":"—"}</td>
                    <td style={{ padding:"10px 12px", textAlign:"center" }}>{s.mobile_capable?"✓":"—"}</td>
                    <td style={{ padding:"10px 12px" }}><button onClick={()=>toggleActive(s.service_id)} style={{ padding:"2px 10px", borderRadius:3, border:"none", cursor:"pointer", fontSize:11, fontFamily:"'DM Mono', monospace", fontWeight:600, background:s.is_active?"#22c55e22":"#ef444422", color:s.is_active?"#22c55e":"#ef4444" }}>{s.is_active?"ACTIVE":"INACTIVE"}</button></td>
                    <td style={{ padding:"10px 12px" }}><div style={{ display:"flex", gap:6 }}>
                      <button onClick={()=>openModal("service","edit",s)} style={{ padding:"4px 10px", background:"#222", border:"1px solid #333", borderRadius:3, color:"#ccc", cursor:"pointer", fontSize:12 }}>Edit</button>
                      <button onClick={()=>setDeleteConfirm({type:"service",id:s.service_id,name:s.service_name})} style={{ padding:"4px 10px", background:"#1a0000", border:"1px solid #4a0000", borderRadius:3, color:"#ef4444", cursor:"pointer", fontSize:12 }}>Del</button>
                    </div></td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* ═══════════ LABOR TAB ═══════════ */}
      {tab==="labor" && (
        <div style={{ padding:"0 24px 24px" }}>
          <div style={{ display:"flex", alignItems:"center", gap:10, marginBottom:14, marginTop:16, flexWrap:"wrap" }}>
            <input placeholder="Search labor rates..." value={search} onChange={e=>setSearch(e.target.value)} style={{ ...inputStyle, maxWidth:260, background:"#151515" }} />
            <div style={{ flex:1 }} />
            <button onClick={()=>openModal("labor","add",null)} style={btnStyle()}>+ Add Labor Rate</button>
          </div>
          <div style={{ overflowX:"auto" }}>
            <table style={{ width:"100%", borderCollapse:"collapse", fontSize:13 }}>
              <thead><tr style={{ borderBottom:"2px solid #333" }}>
                {["Service","Location","Std Hours","Rate/Hr","Flat Rate","Emergency +","After Hrs +","Actions"].map(h=><th key={h} style={{ textAlign:"left", padding:"10px 12px", color:"#888", fontFamily:"'DM Mono', monospace", fontSize:11, textTransform:"uppercase", letterSpacing:1, fontWeight:500 }}>{h}</th>)}
              </tr></thead>
              <tbody>
                {filteredLabor.length===0 ? <tr><td colSpan={8} style={{ textAlign:"center", padding:40, color:"#555" }}>No labor rates found</td></tr> :
                filteredLabor.map(l => (
                  <tr key={l.labor_id} style={{ borderBottom:"1px solid #1e1e1e" }} onMouseEnter={e=>e.currentTarget.style.background="#151515"} onMouseLeave={e=>e.currentTarget.style.background="transparent"}>
                    <td style={{ padding:"10px 12px", fontWeight:600 }}>{getServiceName(l.service_id)}</td>
                    <td style={{ padding:"10px 12px" }}><Badge color="#3b82f6">{locLabel(l.service_location)}</Badge></td>
                    <td style={{ padding:"10px 12px", fontFamily:"'DM Mono', monospace" }}>{l.standard_hours}h</td>
                    <td style={{ padding:"10px 12px", fontFamily:"'DM Mono', monospace", color:"#22c55e" }}>${l.labor_rate_per_hour.toFixed(2)}</td>
                    <td style={{ padding:"10px 12px", fontFamily:"'DM Mono', monospace", color:"#fbbf24", fontWeight:700 }}>${l.flat_rate_price.toFixed(2)}</td>
                    <td style={{ padding:"10px 12px", fontFamily:"'DM Mono', monospace", color:l.emergency_surcharge?"#ef4444":"#444" }}>{l.emergency_surcharge?`+$${l.emergency_surcharge.toFixed(2)}`:"—"}</td>
                    <td style={{ padding:"10px 12px", fontFamily:"'DM Mono', monospace", color:l.after_hours_surcharge?"#a855f7":"#444" }}>{l.after_hours_surcharge?`+$${l.after_hours_surcharge.toFixed(2)}`:"—"}</td>
                    <td style={{ padding:"10px 12px" }}><div style={{ display:"flex", gap:6 }}>
                      <button onClick={()=>openModal("labor","edit",l)} style={{ padding:"4px 10px", background:"#222", border:"1px solid #333", borderRadius:3, color:"#ccc", cursor:"pointer", fontSize:12 }}>Edit</button>
                      <button onClick={()=>setDeleteConfirm({type:"labor",id:l.labor_id,name:getServiceName(l.service_id)})} style={{ padding:"4px 10px", background:"#1a0000", border:"1px solid #4a0000", borderRadius:3, color:"#ef4444", cursor:"pointer", fontSize:12 }}>Del</button>
                    </div></td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* ═══════════ MODALS ═══════════ */}
      {modal && modal.type==="service" && (
        <Modal title={`${modal.mode==="add"?"ADD":"EDIT"} SERVICE`} onClose={()=>setModal(null)}>
          <Field label="Service Code"><input style={inputStyle} value={form.service_code} onChange={e=>updateForm("service_code",e.target.value)} placeholder="e.g. RSA-005" /></Field>
          <Field label="Service Name"><input style={inputStyle} value={form.service_name} onChange={e=>updateForm("service_name",e.target.value)} placeholder="e.g. Alternator Replacement" /></Field>
          <Field label="Category"><select style={selectStyle} value={form.service_category} onChange={e=>updateForm("service_category",e.target.value)}>{CATEGORIES.map(c=><option key={c} value={c}>{catLabel(c)}</option>)}</select></Field>
          <Field label="Description"><textarea style={{ ...inputStyle, minHeight:60, resize:"vertical" }} value={form.description} onChange={e=>updateForm("description",e.target.value)} /></Field>
          <div style={{ display:"flex", gap:20, marginBottom:14 }}>
            <CheckboxField label="Requires Parts" checked={form.requires_parts} onChange={()=>updateForm("requires_parts",!form.requires_parts)} />
            <CheckboxField label="Mobile Capable" checked={form.mobile_capable} onChange={()=>updateForm("mobile_capable",!form.mobile_capable)} />
            <CheckboxField label="Shop Only" checked={form.shop_only} onChange={()=>updateForm("shop_only",!form.shop_only)} />
          </div>
          <CheckboxField label="Active" checked={form.is_active} onChange={()=>updateForm("is_active",!form.is_active)} />
          <div style={{ display:"flex", gap:10, justifyContent:"flex-end", marginTop:20 }}>
            <button onClick={()=>setModal(null)} style={btnStyle("#333")}>Cancel</button>
            <button onClick={saveService} style={btnStyle()}>{modal.mode==="add"?"Add Service":"Save Changes"}</button>
          </div>
        </Modal>
      )}

      {modal && modal.type==="labor" && (
        <Modal title={`${modal.mode==="add"?"ADD":"EDIT"} LABOR RATE`} onClose={()=>setModal(null)}>
          <Field label="Service"><select style={selectStyle} value={form.service_id} onChange={e=>updateForm("service_id",e.target.value)}><option value="">— Select Service —</option>{services.filter(s=>s.is_active).map(s=><option key={s.service_id} value={s.service_id}>{s.service_code} — {s.service_name}</option>)}</select></Field>
          <Field label="Service Location"><select style={selectStyle} value={form.service_location} onChange={e=>updateForm("service_location",e.target.value)}>{LOCATIONS.map(l=><option key={l} value={l}>{locLabel(l)}</option>)}</select></Field>
          <div style={{ display:"grid", gridTemplateColumns:"1fr 1fr", gap:10 }}>
            <Field label="Standard Hours"><input style={inputStyle} type="number" step="0.25" value={form.standard_hours} onChange={e=>updateForm("standard_hours",e.target.value)} /></Field>
            <Field label="Rate / Hour ($)"><input style={inputStyle} type="number" step="5" value={form.labor_rate_per_hour} onChange={e=>updateForm("labor_rate_per_hour",e.target.value)} /></Field>
            <Field label="Flat Rate ($)"><input style={inputStyle} type="number" step="5" value={form.flat_rate_price} onChange={e=>updateForm("flat_rate_price",e.target.value)} /></Field>
            <Field label="Emergency Surcharge ($)"><input style={inputStyle} type="number" step="5" value={form.emergency_surcharge} onChange={e=>updateForm("emergency_surcharge",e.target.value)} /></Field>
            <Field label="After Hours Surcharge ($)"><input style={inputStyle} type="number" step="5" value={form.after_hours_surcharge} onChange={e=>updateForm("after_hours_surcharge",e.target.value)} /></Field>
          </div>
          <div style={{ display:"flex", gap:10, justifyContent:"flex-end", marginTop:20 }}>
            <button onClick={()=>setModal(null)} style={btnStyle("#333")}>Cancel</button>
            <button onClick={saveLabor} style={btnStyle()}>{modal.mode==="add"?"Add Rate":"Save Changes"}</button>
          </div>
        </Modal>
      )}

      {deleteConfirm && (
        <Modal title="CONFIRM DELETE" onClose={()=>setDeleteConfirm(null)}>
          <p style={{ color:"#ccc", marginBottom:6 }}>Are you sure you want to delete <strong style={{ color:"#ef4444" }}>{deleteConfirm.name}</strong>?</p>
          {deleteConfirm.type==="service" && <p style={{ color:"#888", fontSize:12 }}>This will also remove any associated labor rates.</p>}
          <div style={{ display:"flex", gap:10, justifyContent:"flex-end", marginTop:20 }}>
            <button onClick={()=>setDeleteConfirm(null)} style={btnStyle("#333")}>Cancel</button>
            <button onClick={deleteItem} style={btnStyle("#ef4444")}>Delete</button>
          </div>
        </Modal>
      )}
    </div>
  );
}
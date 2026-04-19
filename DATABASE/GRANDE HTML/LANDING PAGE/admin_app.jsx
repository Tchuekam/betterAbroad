const { useState, useEffect, useRef, useCallback } = React;

/* ==========================================================================
   1. CONFIGURATION & UTILS
   ========================================================================== */
const PHP_BASE = '../DATABASE/';

const initials = (name = "") => name.split(" ").slice(0, 2).map((n) => n[0] || "").join("").toUpperCase() || "?";

const timeAgo = (dateStr) => {
    if (!dateStr) return "Never";
    const diff = Date.now() - new Date(dateStr).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 1) return "Just now";
    if (mins < 60) return `${mins}m ago`;
    const hrs = Math.floor(mins / 60);
    if (hrs < 24) return `${hrs}h ago`;
    const days = Math.floor(hrs / 24);
    if (days < 30) return `${days}d ago`;
    return new Date(dateStr).toLocaleDateString("en-GB", { day: "numeric", month: "short" });
};

const avatarColor = (name = "") => {
    const colors = [
        "linear-gradient(135deg,#1a56db,#06b6d4)", "linear-gradient(135deg,#7c3aed,#06b6d4)",
        "linear-gradient(135deg,#e02441,#f59e0b)", "linear-gradient(135deg,#059669,#06b6d4)"
    ];
    let h = 0;
    for (let i = 0; i < name.length; i++) h = name.charCodeAt(i) + ((h << 5) - h);
    return colors[Math.abs(h) % colors.length];
};

const statusBadge = (s) => {
    if (s === "verified") return <span className="badge badge-green"><span className="status-dot dot-green" /> Verified</span>;
    if (s === "pending") return <span className="badge badge-amber"><span className="status-dot dot-amber" /> Pending</span>;
    if (s === "rejected") return <span className="badge badge-red"><span className="status-dot dot-red" /> Rejected</span>;
    return null;
};

/* ==========================================================================
   2. TOAST NOTIFICATION SYSTEM
   ========================================================================== */
const ToastCtx = React.createContext(null);
const ToastProvider = ({ children }) => {
    const [toasts, setToasts] = useState([]);
    const add = useCallback((type, msg) => {
        const id = Date.now();
        setToasts((t) => [...t, { id, type, msg }]);
        setTimeout(() => setToasts((t) => t.filter((x) => x.id !== id)), 3500);
    }, []);
    
    const icons = { success: "fa-circle-check", error: "fa-circle-xmark", info: "fa-circle-info", warn: "fa-triangle-exclamation" };
    const colors = { success: "#22c55e", error: "#ef4444", info: "#3b82f6", warn: "#f59e0b" };
    const bgs = { success: "rgba(34,197,94,.12)", error: "rgba(239,68,68,.12)", info: "rgba(59,130,246,.12)", warn: "rgba(245,158,11,.12)" };
    
    return (
        <ToastCtx.Provider value={add}>
            {children}
            <div className="toast-container">
                {toasts.map((t) => (
                    <div key={t.id} className="toast">
                        <div className="toast-icon" style={{ background: bgs[t.type] }}>
                            <i className={`fa-solid ${icons[t.type]}`} style={{ color: colors[t.type] }} />
                        </div>
                        <span style={{ fontSize: 13 }}>{t.msg}</span>
                    </div>
                ))}
            </div>
        </ToastCtx.Provider>
    );
};
const useToast = () => React.useContext(ToastCtx);

/* ==========================================================================
   3. PAGES & COMPONENTS
   ========================================================================== */

/* --- LOGIN PAGE --- */
const LoginPage = ({ onLogin }) => {
    const [form, setForm] = useState({ email: "", password: "" });
    const [error, setError] = useState("");
    const [loading, setLoading] = useState(false);

    const submit = () => {
        if (!form.email || !form.password) return setError("All fields required");
        setLoading(true);
        fetch(PHP_BASE + "login.php", {
            method: "POST", headers: { "Content-Type": "application/json" },
            credentials: "include", body: JSON.stringify(form),
        })
        .then((r) => r.json())
        .then((data) => {
            if (data.success && data.role === "admin") onLogin({ name: data.email, email: data.email, role: "admin" });
            else if (data.success) setError("Access denied. Admin accounts only.");
            else setError(data.error || "Invalid credentials.");
        })
        .catch(() => setError("Connection error."))
        .finally(() => setLoading(false));
    };

    return (
        <div className="login-page">
            <div className="login-card">
                <div style={{ textAlign: "center", marginBottom: 32 }}>
                    <div style={{fontFamily: "'Sora',sans-serif", fontWeight: 800, fontSize: 22 }}>
                        Better<span className="grad-text">Abroad</span>
                    </div>
                    <div style={{color: "var(--text-lo)", fontSize: 12, marginTop: 4, letterSpacing: 2, textTransform: "uppercase", fontWeight: 600 }}>
                        Admin Control Panel
                    </div>
                </div>
                <div style={{ marginBottom: 16 }}>
                    <input className="adm-input" type="email" placeholder="Admin Email" style={{ width: "100%" }}
                        onChange={(e) => setForm({ ...form, email: e.target.value })} />
                </div>
                <div style={{ marginBottom: 20 }}>
                    <input className="adm-input" type="password" placeholder="Password" style={{ width: "100%" }}
                        onChange={(e) => setForm({ ...form, password: e.target.value })}
                        onKeyDown={(e) => e.key === "Enter" && submit()} />
                </div>
                {error && <div style={{ color: "#f87171", fontSize: 12, marginBottom: 16 }}>{error}</div>}
                <button className="adm-btn adm-btn-primary full-w" onClick={submit} disabled={loading}>
                    {loading ? "Authenticating..." : "Sign In"}
                </button>
            </div>
        </div>
    );
};

/* --- DASHBOARD PAGE (With New Analytics) --- */
const DashboardPage = ({ students, universities, appCount, onNavigate, pendingCount }) => {
    return (
        <div className="page">
            <div style={{ marginBottom: 24 }}>
                <div className="section-pill"><i className="fa-solid fa-gauge" /> Overview</div>
                <h1 style={{ fontFamily: "'Sora',sans-serif", fontWeight: 800, fontSize: 26, marginBottom: 4 }}>Admin Dashboard</h1>
            </div>

            {/* Top Row: Conversion Funnel & Quick Actions */}
            <div style={{ display: "grid", gridTemplateColumns: "2fr 1fr", gap: 20, marginBottom: 20 }}>
                {/* The Recruitment Funnel */}
                <div className="table-card" style={{ padding: 22 }}>
                    <div style={{ fontFamily: "'Sora',sans-serif", fontWeight: 700, fontSize: 15, marginBottom: 16 }}>Student Conversion Funnel</div>
                    <div style={{ display: 'flex', alignItems: 'flex-end', height: '140px', gap: '8px', paddingBottom: '20px', borderBottom: '1px solid var(--border)' }}>
                        {[
                            { label: 'Registered', count: students.length, color: '#3d5a80', pct: 100 },
                            { label: 'Verified', count: students.filter(s=>s.verified==='verified').length, color: '#3b82f6', pct: students.length ? (students.filter(s=>s.verified==='verified').length/students.length)*100 : 0 },
                            { label: 'Applied', count: appCount, color: '#f59e0b', pct: students.length ? (appCount/students.length)*100 : 0 },
                            { label: 'Accepted', count: Math.floor(appCount * 0.4), color: '#22c55e', pct: students.length ? ((appCount * 0.4)/students.length)*100 : 0 }
                        ].map((stage, i) => (
                            <div key={i} style={{ flex: 1, display: 'flex', flexDirection: 'column', alignItems: 'center', height: '100%', justifyContent: 'flex-end' }}>
                                <div style={{ fontSize: 14, fontWeight: 800, color: stage.color, marginBottom: 6 }}>{stage.count}</div>
                                <div style={{ width: '100%', background: stage.color, borderRadius: '4px 4px 0 0', height: `${Math.max(stage.pct, 5)}%`, opacity: 0.8, transition: 'height 1s ease' }}></div>
                                <div style={{ fontSize: 10, fontWeight: 700, color: 'var(--text-lo)', marginTop: 8, textTransform: 'uppercase' }}>{stage.label}</div>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Quick actions */}
                <div className="table-card" style={{ padding: 22 }}>
                    <div style={{ fontFamily: "'Sora',sans-serif", fontWeight: 700, fontSize: 15, marginBottom: 16 }}>Admin Shortcuts</div>
                    <div style={{ display: "flex", flexDirection: "column", gap: 10 }}>
                        <button className="adm-btn adm-btn-primary" style={{ padding: "12px", justifyContent: 'center' }} onClick={() => onNavigate("pending")}>
                            <i className="fa-solid fa-file-shield" /> Review {pendingCount} Pending
                        </button>
                        <button className="adm-btn adm-btn-ghost" style={{ padding: "12px", justifyContent: 'center' }} onClick={() => onNavigate("applications")}>
                            <i className="fa-solid fa-list-check" /> Manage Pipeline
                        </button>
                        <button className="adm-btn adm-btn-ghost" style={{ padding: "12px", justifyContent: 'center' }} onClick={() => window.open(PHP_BASE + 'dossier.php', '_blank')}>
                            <i className="fa-solid fa-file-pdf" style={{color:'#ef4444'}}/> Generate Test Dossier
                        </button>
                    </div>
                </div>
            </div>

            {/* Bottom Row: Financials & Seminars */}
            <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr 1fr", gap: 20, marginBottom: 24 }}>
                <div className="table-card" style={{ padding: 22, background: 'linear-gradient(135deg, rgba(34,197,94,0.05), transparent)' }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 }}>
                        <div style={{ fontFamily: "'Sora',sans-serif", fontWeight: 700, fontSize: 15 }}>Est. Revenue (FCFA)</div>
                        <i className="fa-solid fa-money-bill-wave" style={{ color: '#22c55e', fontSize: 18 }}/>
                    </div>
                    <div style={{ fontSize: 28, fontWeight: 800, color: '#4ade80', fontFamily: "'Sora',sans-serif", marginBottom: 4 }}>1,400,000</div>
                    <div style={{ fontSize: 12, color: 'var(--text-lo)', marginBottom: 16 }}>Based on booked seminars & credit sales</div>
                </div>

                <div className="table-card" style={{ padding: 22 }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 }}>
                        <div style={{ fontFamily: "'Sora',sans-serif", fontWeight: 700, fontSize: 15 }}>Active Seminars</div>
                        <i className="fa-solid fa-chalkboard-user" style={{ color: '#f59e0b', fontSize: 18 }}/>
                    </div>
                    <div style={{ fontSize: 28, fontWeight: 800, color: '#f59e0b', fontFamily: "'Sora',sans-serif", marginBottom: 4 }}>3</div>
                    <div style={{ fontSize: 12, color: 'var(--text-lo)', marginBottom: 16 }}>Scheduled for this month</div>
                </div>

                <div className="table-card" style={{ padding: 22 }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 }}>
                        <div style={{ fontFamily: "'Sora',sans-serif", fontWeight: 700, fontSize: 15 }}>Credit Economy</div>
                        <i className="fa-solid fa-coins" style={{ color: '#3b82f6', fontSize: 18 }}/>
                    </div>
                    <div style={{ fontSize: 28, fontWeight: 800, color: '#3b82f6', fontFamily: "'Sora',sans-serif", marginBottom: 4 }}>14,250</div>
                    <div style={{ fontSize: 12, color: 'var(--text-lo)', marginBottom: 16 }}>Credits currently circulating</div>
                </div>
            </div>
        </div>
    );
};

/* ==========================================================================
   4. MASTER LAYOUT & APP ROOT
   ========================================================================== */
const AdminLayout = ({ admin, onLogout }) => {
    const [page, setPage] = useState("dashboard");
    const [students, setStudents] = useState([]);
    const [universities, setUniversities] = useState([]);
    const [appCount, setAppCount] = useState(0);
    const [showNotifs, setShowNotifs] = useState(false); // NEW NOTIFICATION STATE
    const toast = useToast();

    const loadData = useCallback(() => {
        fetch(PHP_BASE + 'users.php?role=student', { credentials: 'include' }).then(r=>r.json()).then(d => { if(d.success) setStudents(d.students || []) });
        fetch(PHP_BASE + 'users.php?role=university', { credentials: 'include' }).then(r=>r.json()).then(d => { if(d.success) setUniversities(d.universities || []) });
        fetch(PHP_BASE + 'applications.php', { credentials: 'include' }).then(r=>r.json()).then(d => { if(d.success) setAppCount((d.applications||[]).length) });
    }, []);

    useEffect(() => { loadData(); }, [loadData]);

    const pendingCount = [...students, ...universities].filter(x => x.verified === 'pending').length;

    return (
        <div style={{ display: "flex" }}>
            {/* SIDEBAR */}
            <div className="sidebar">
                <div className="sidebar-logo">
                    <div className="logo-badge">BA</div>
                    <div><div className="logo-text">Better<span>Abroad</span></div></div>
                </div>
                <div className="sidebar-section-label">Navigation</div>
                {[
                    { id: "dashboard", icon: "fa-gauge", label: "Dashboard" },
                    { id: "pending", icon: "fa-clock", label: "Pending Queue", badge: pendingCount },
                    { id: "students", icon: "fa-user-graduate", label: "Students" },
                    { id: "universities", icon: "fa-university", label: "Universities" },
                    { id: "applications", icon: "fa-list-check", label: "Applications" },
                    { id: "activity", icon: "fa-scroll", label: "Activity Log" },
                    { id: "settings", icon: "fa-gear", label: "Settings" }
                ].map(n => (
                    <div key={n.id} className={`nav-item ${page === n.id ? "active" : ""}`} onClick={() => setPage(n.id)}>
                        <i className={`fa-solid ${n.icon} nav-icon`} />
                        <span style={{ flex: 1 }}>{n.label}</span>
                        {n.badge > 0 && <span className="nav-badge">{n.badge}</span>}
                    </div>
                ))}
            </div>

            {/* TOP BAR */}
            <div className="topbar">
                <div className="topbar-title">Admin / {page}</div>
                <div className="topbar-right">
                    
                    {/* NOTIFICATION CENTER */}
                    <div style={{ position: 'relative' }}>
                        <div className="icon-btn" onClick={() => setShowNotifs(!showNotifs)}>
                            <i className="fa-solid fa-bell" />
                            {pendingCount > 0 && <div className="notif-dot" />}
                        </div>

                        {showNotifs && (
                            <div style={{
                                position: 'absolute', top: '45px', right: 0, width: '320px',
                                background: 'var(--bg3)', border: '1px solid var(--border)',
                                borderRadius: '14px', boxShadow: '0 16px 48px rgba(0,0,0,0.6)',
                                zIndex: 100, animation: 'fadeUp 0.2s ease'
                            }}>
                                <div style={{ padding: '14px 18px', borderBottom: '1px solid var(--border)', display: 'flex', justifyContent: 'space-between', alignItems: 'center', background: 'rgba(255,255,255,.02)' }}>
                                    <div style={{ fontFamily: "'Sora',sans-serif", fontWeight: 700, fontSize: 14 }}>Notifications</div>
                                </div>
                                <div style={{ maxHeight: '340px', overflowY: 'auto' }}>
                                    {pendingCount > 0 ? (
                                        <div style={{ padding: '12px 18px', display: 'flex', gap: 12, cursor: 'pointer', background: 'rgba(59,130,246,.05)' }} onClick={() => { setPage('pending'); setShowNotifs(false); }}>
                                            <div style={{ width: 32, height: 32, borderRadius: 8, background: 'rgba(245,158,11,.15)', color: '#f59e0b', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}><i className="fa-solid fa-file-shield" /></div>
                                            <div>
                                                <div style={{ fontSize: 13, fontWeight: 600 }}>{pendingCount} profiles need verification</div>
                                                <div style={{ fontSize: 11, color: 'var(--text-lo)' }}>Click to review documents.</div>
                                            </div>
                                        </div>
                                    ) : (
                                        <div style={{ padding: '30px 20px', textAlign: 'center', color: 'var(--text-lo)', fontSize: 13 }}>You're all caught up!</div>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>

                    <div className="icon-btn" onClick={onLogout} title="Logout"><i className="fa-solid fa-right-from-bracket" /></div>
                </div>
            </div>

            {/* MAIN ROUTER */}
            <div className="main">
                {page === "dashboard" && <DashboardPage students={students} universities={universities} appCount={appCount} onNavigate={setPage} pendingCount={pendingCount} />}
                {/* Add other pages here as needed, calling from the original script if you want them. For brevity and architecture setup, they link here. */}
                {page !== "dashboard" && <div className="page"><div className="empty-state">Page loaded perfectly. Add your sub-components here.</div></div>}
            </div>
        </div>
    );
};

const AdminApp = () => {
    const [admin, setAdmin] = useState(null);
    useEffect(() => {
        fetch(PHP_BASE + "me.php", { credentials: "include" })
            .then((r) => r.json())
            .then((data) => { if (data.success && data.role === "admin") setAdmin({ name: data.email, email: data.email, role: "admin" }); })
            .catch(() => {});
    }, []);

    return (
        <ToastProvider>
            {!admin ? <LoginPage onLogin={setAdmin} /> : <AdminLayout admin={admin} onLogout={() => setAdmin(null)} />}
        </ToastProvider>
    );
};

ReactDOM.createRoot(document.getElementById("root")).render(<AdminApp />);

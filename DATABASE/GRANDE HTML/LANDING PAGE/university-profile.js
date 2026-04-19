/* ═══════════════════════════════════════════════════════════
   BetterAbroad — UniversityProfile React Component
   Loaded by market.html via: <script src="university-profile.js" type="text/babel">
   Uses React 18 UMD + Babel standalone (no bundler)
   API base: ../../
   ═══════════════════════════════════════════════════════════ */

'use strict';

const { useState, useEffect, useRef, useCallback } = React;

const API = '../../';

const UP_TABS = [
  { id:'overview',    icon:'fa-building-columns', label:'Overview' },
  { id:'applicants',  icon:'fa-users',             label:'Applicants' },
  { id:'seminars',    icon:'fa-chalkboard-teacher',label:'Seminars' },
  { id:'documents',   icon:'fa-folder-open',       label:'Documents' },
  { id:'messages',    icon:'fa-comment-dots',      label:'Messages' },
  { id:'settings',    icon:'fa-gear',              label:'Settings' },
];

const PRESET_PROGRAMS = [
  'Computer Science','Software Engineering','Data Science','Artificial Intelligence',
  'Business Administration','MBA','Finance','Accounting','Marketing','International Business',
  'Mechanical Engineering','Civil Engineering','Electrical Engineering','Chemical Engineering',
  'Architecture','Urban Planning','Medicine','Nursing','Public Health','Pharmacy',
  'Law','Political Science','International Relations','Economics','Psychology',
  'Environmental Science','Biology','Chemistry','Physics','Mathematics',
  'Education','Linguistics','Media Studies','Graphic Design','Film Studies',
];

const APP_STATUS = {
  new:       { label:'New',       cls:'', col:'#3b82f6' },
  review:    { label:'Review',    cls:'', col:'#f59e0b' },
  interview: { label:'Interview', cls:'', col:'#8b5cf6' },
  offer:     { label:'Offer',     cls:'', col:'#22c55e' },
  enrolled:  { label:'Enrolled',  cls:'', col:'#06b6d4' },
  rejected:  { label:'Rejected',  cls:'', col:'#ef4444' },
};

/* ── HELPERS ─────────────────────────────────────────── */
const abbr = (name='') => name.split(/\s+/).map(w=>w[0]).join('').slice(0,3).toUpperCase() || 'UNI';
const timeAgo = (dt) => {
  if (!dt) return '';
  const d = Math.floor((Date.now()-new Date(dt))/1000);
  if (d<60) return 'just now';
  if (d<3600) return `${Math.floor(d/60)}m ago`;
  if (d<86400) return `${Math.floor(d/3600)}h ago`;
  return `${Math.floor(d/86400)}d ago`;
};
const fmtDT = (dt) => dt ? new Date(dt).toLocaleString('en-GB',
  {weekday:'short',day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}) : '—';
const studentDisplayName = (name='') => {
  const parts = name.trim().split(/\s+/).filter(Boolean);
  if (!parts.length) return 'Student';
  if (parts.length === 1) return parts[0];
  return `${parts[0].charAt(0)}. ${parts[parts.length - 1]}`;
};

/* ── TOAST ───────────────────────────────────────────── */
const UpToastCtx = React.createContext(null);
const UpToastProvider = ({ children }) => {
  const [toasts, setToasts] = useState([]);
  const fire = useCallback((msg,type='info')=>{
    const id=Date.now(); setToasts(t=>[...t,{id,msg,type}]);
    setTimeout(()=>setToasts(t=>t.filter(x=>x.id!==id)),3800);
  },[]);
  const icon={success:'fa-circle-check',error:'fa-circle-xmark',info:'fa-circle-info'};
  const col ={success:'#22c55e',        error:'#ef4444',         info:'#f59e0b'};
  return (
    <UpToastCtx.Provider value={fire}>
      {children}
      <div className="up-toast-container">
        {toasts.map(t=>(
          <div key={t.id} className={`up-toast up-toast-${t.type}`}>
            <i className={`fa-solid ${icon[t.type]}`} style={{color:col[t.type],fontSize:16,flexShrink:0}}/>
            {t.msg}
          </div>
        ))}
      </div>
    </UpToastCtx.Provider>
  );
};
const useUpToast = () => React.useContext(UpToastCtx);

/* ── TOS MODAL (shown on first login) ─────────────────── */
const TosModal = ({ onAccept }) => {
  const [checked, setChecked] = useState(false);
  const [saving,  setSaving]  = useState(false);
  const toast = useUpToast();

  const accept = () => {
    if (!checked) { toast('Please check the box to agree.', 'error'); return; }
    setSaving(true);
    fetch(API + 'save.php', {
      method:'POST', credentials:'include',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ tos_accepted: true })
    })
    .then(r=>r.json())
    .then(d => {
      if (d && d.success) {
        onAccept();
      } else {
        toast(d?.error || 'Could not save agreement.', 'error');
      }
    })
    .catch(()=>toast('Could not save agreement.', 'error'))
    .finally(()=>setSaving(false));
  };

  return (
    <div className="up-tos-overlay">
      <div className="up-tos-box">
        <div style={{textAlign:'center'}}>
          <div style={{fontSize:40,marginBottom:12}}>📋</div>
          <h2 style={{fontFamily:"'Sora',sans-serif",fontWeight:800,fontSize:22,marginBottom:6}}>
            Partnership Agreement
          </h2>
          <p style={{fontSize:13,color:'var(--text-mid)'}}>
            Please read and accept before accessing your institution dashboard.
          </p>
        </div>
        <div className="up-tos-body">
          <strong style={{color:'var(--gold)'}}>BetterAbroad — University Partner Terms of Service & Non-Circumvention Agreement</strong>
          <br/><br/>
          By accessing BetterAbroad's partner platform, your institution agrees to the following binding terms:
          <br/><br/>
          <strong>1. Platform Integrity</strong><br/>
          All student contact initiated through BetterAbroad — including seminars, profile views, and direct messages — creates a documented introduction. Your institution may not circumvent this introduction to engage students outside the platform.
          <br/><br/>
          <strong>2. Non-Circumvention Clause</strong><br/>
          Any student discovered on or introduced through BetterAbroad who subsequently enrolls at your institution within 24 months of first contact constitutes a BetterAbroad placement, regardless of the channel through which the final application was submitted. A commission fee is owed to BetterAbroad for each such enrollment.
          <br/><br/>
          <strong>3. Communication Policy</strong><br/>
          All communication with students must occur through BetterAbroad's on-platform messaging system. Sharing personal contact information (email, phone, WhatsApp) directly through the platform is prohibited and will result in account suspension.
          <br/><br/>
          <strong>4. Commission Schedule</strong><br/>
          Niche/Mid-tier institutions: 150,000 FCFA per enrolled student.<br/>
          Major institutions (Top 200): 250,000 FCFA per enrolled student.<br/>
          Elite institutions (Top 50 global): 400,000 FCFA per enrolled student.<br/>
          Commissions are invoiced upon confirmed enrollment and payable within 30 days.
          <br/><br/>
          <strong>5. Data Privacy</strong><br/>
          Student personal data accessed through this platform may not be transferred to third-party systems or used for any purpose other than student recruitment for your own institution.
          <br/><br/>
          <strong>6. Governing Law</strong><br/>
          This agreement is governed by the laws of the Republic of Cameroon and applicable international commercial law. Any disputes shall be resolved through arbitration in Yaoundé, Cameroon.
        </div>
        <label style={{display:'flex',alignItems:'flex-start',gap:12,cursor:'pointer',
          padding:'14px',borderRadius:'var(--radius-sm)',
          background:'rgba(245,158,11,0.05)',border:'1px solid var(--gold-border)'}}>
          <input type="checkbox" checked={checked} onChange={e=>setChecked(e.target.checked)}
            style={{width:18,height:18,accentColor:'var(--gold)',marginTop:2,flexShrink:0}}/>
          <span style={{fontSize:13,color:'var(--text-mid)',lineHeight:1.6}}>
            I confirm that I am an authorized representative of this institution and I agree to the
            <strong style={{color:'var(--gold)'}}> BetterAbroad Terms of Service and Non-Circumvention Agreement</strong>.
          </span>
        </label>
        <button className="up-btn up-btn-gold" style={{width:'100%',justifyContent:'center'}}
          onClick={accept} disabled={saving}>
          <i className={`fa-solid ${saving?'fa-spinner fa-spin':'fa-handshake'}`}/>
          {saving ? 'Processing...' : 'I Agree — Enter Dashboard'}
        </button>
      </div>
    </div>
  );
};

/* ── INFO TILE ─────────────────────────────────────────── */
const UpInfoTile = ({ label, value, icon, editing, onChange, inputType='text', placeholder='' }) => (
  <div className="up-info-tile">
    <div className="up-info-label"><i className={`fa-solid ${icon}`}/> {label}</div>
    {editing
      ? <input className="up-input" style={{padding:'6px 10px',fontSize:13,marginTop:2}}
          type={inputType} defaultValue={value||''} placeholder={placeholder}
          onChange={e=>onChange&&onChange(e.target.value)}/>
      : <div className="up-info-value">{value||<span style={{color:'var(--text-faint)'}}>—</span>}</div>
    }
  </div>
);

/* ── PROGRAM SELECTOR ──────────────────────────────────── */
const ProgramSelector = ({ selected, onChange }) => {
  const [search, setSearch] = useState('');
  const sel = new Set(selected||[]);
  const toggle = (p) => {
    const next = new Set(sel);
    next.has(p) ? next.delete(p) : next.add(p);
    onChange([...next]);
  };
  const filtered = PRESET_PROGRAMS.filter(p =>
    p.toLowerCase().includes(search.toLowerCase())
  );
  return (
    <div>
      <input className="up-input" placeholder="Search programs..." style={{marginBottom:12}}
        value={search} onChange={e=>setSearch(e.target.value)}/>
      <div style={{display:'flex',flexWrap:'wrap',gap:8,maxHeight:200,overflowY:'auto'}}>
        {filtered.map(p=>(
          <div key={p} className={`up-program-tag ${sel.has(p)?'selected':''}`}
            onClick={()=>toggle(p)}>
            {sel.has(p) && <i className="fa-solid fa-check"/>}
            {p}
          </div>
        ))}
      </div>
      {sel.size>0 && (
        <div style={{marginTop:12,fontSize:12,color:'var(--text-lo)'}}>
          {sel.size} programme{sel.size>1?'s':''} selected
        </div>
      )}
    </div>
  );
};

/* ── DOC UPLOAD (uni) ──────────────────────────────────── */
const UpDocSlot = ({ docKey, label, icon, uploaded, onUpload }) => {
  const toast = useUpToast();
  const ref   = useRef();
  const [state, setState] = useState(uploaded ? 'done' : 'idle');
  const [fname, setFname] = useState(uploaded?.file_name||null);
  const [prog,  setProg]  = useState(0);

  const handleFile = (file) => {
    if (!file) return;
    if (file.size>5*1024*1024) { toast('Max 5 MB.','error'); return; }
    setState('uploading'); setProg(0);
    const fd=new FormData(); fd.append('file',file); fd.append('doc_type',docKey);
    const xhr=new XMLHttpRequest();
    xhr.upload.onprogress=e=>{if(e.lengthComputable)setProg(Math.round(e.loaded/e.total*100));};
    xhr.onload=()=>{
      try {
        const d=JSON.parse(xhr.responseText);
        if(d.success){setState('done');setFname(d.file_name);toast(`${label} uploaded.`,'success');onUpload&&onUpload(docKey,d);}
        else{setState('error');toast(d.error||'Upload failed.','error');}
      }catch{setState('error');toast('Upload failed.','error');}
    };
    xhr.onerror=()=>{setState('error');toast('Upload failed.','error');};
    xhr.open('POST',API+'upload.php'); xhr.withCredentials=true; xhr.send(fd);
  };

  const zoneClass=`up-upload-zone${state==='done'?' uploaded':state==='uploading'?' uploading':''}`;
  const ic=state==='done'?'fa-circle-check':state==='error'?'fa-circle-xmark':icon;
  const col=state==='done'?'#22c55e':state==='error'?'#ef4444':'var(--gold)';

  return (
    <div className={zoneClass} onClick={()=>state!=='uploading'&&ref.current?.click()}
      onDragOver={e=>e.preventDefault()} onDrop={e=>{e.preventDefault();handleFile(e.dataTransfer.files[0]);}}>
      <input type="file" ref={ref} accept=".pdf,.jpg,.png" style={{display:'none'}}
        onChange={e=>handleFile(e.target.files[0])}/>
      <div style={{width:44,height:44,borderRadius:12,margin:'0 auto 12px',
        background:state==='done'?'var(--green-dim)':'var(--gold-dim)',
        display:'flex',alignItems:'center',justifyContent:'center'}}>
        <i className={`fa-solid ${ic}`} style={{color:col,fontSize:18}}/>
      </div>
      <div style={{fontWeight:600,fontSize:13,marginBottom:4}}>
        {state==='done'?fname:state==='uploading'?'Uploading...':state==='error'?'Try again':label}
      </div>
      <div style={{fontSize:11,color:'var(--text-lo)'}}>
        {state==='idle'?'PDF, JPG or PNG · max 5 MB':''}
        {state==='done'?<span style={{color:'var(--green)'}}>Submitted for review</span>:''}
      </div>
      {state==='uploading'&&(
        <div className="up-progress-bar" style={{marginTop:12}}>
          <div className="up-progress-fill" style={{width:prog+'%'}}/>
        </div>
      )}
    </div>
  );
};

/* ── OVERVIEW TAB ──────────────────────────────────────── */
const UpOverviewTab = ({ user, setUser }) => {
  const toast   = useUpToast();
  const [editing, setEditing] = useState(false);
  const [form,    setForm]    = useState({...user});
  const [saving,  setSaving]  = useState(false);
  const [programs,setPrograms]= useState((user.programs||'').split('\n').filter(Boolean));

  const set=(k,v)=>setForm(f=>({...f,[k]:v}));

  const save=()=>{
    setSaving(true);
    const payload={...form, programs:programs.join('\n')};
    fetch(API+'save.php',{method:'POST',credentials:'include',
      headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)})
    .then(r=>r.json())
    .then(d=>{
      if(d.success){setUser(u=>({...u,...payload}));setEditing(false);toast('Profile saved.','success');}
      else toast(d.error||'Save failed.','error');
    })
    .catch(()=>toast('Save failed.','error'))
    .finally(()=>setSaving(false));
  };

  return (
    <div style={{display:'flex',flexDirection:'column',gap:20}}>

      {/* STATS */}
      <div className="up-stats up-fade-up">
        <div className="up-stat-box">
          <div className="up-stat-value">{user.appCount||0}</div>
          <div className="up-stat-label">Applications</div>
        </div>
        <div className="up-stat-box">
          <div className="up-stat-value">{programs.length}</div>
          <div className="up-stat-label">Programmes</div>
        </div>
        <div className="up-stat-box">
          <div className="up-stat-value">{user.seminarCount||0}</div>
          <div className="up-stat-label">Seminars Hosted</div>
        </div>
        <div className="up-stat-box">
          <div className="up-stat-value" style={{fontSize:18}}>
            {user.verified==='verified'?'✓ Live':'Pending'}
          </div>
          <div className="up-stat-label">Status</div>
        </div>
      </div>

      {/* INSTITUTION INFO */}
      <div className="up-card up-fade-up up-fade-up-1">
        <div style={{display:'flex',alignItems:'center',justifyContent:'space-between',marginBottom:16}}>
          <div className="up-pill"><i className="fa-solid fa-building"/> Institution Info</div>
          <button className="up-btn up-btn-ghost" style={{padding:'7px 14px',fontSize:12}}
            onClick={()=>editing?save():setEditing(true)} disabled={saving}>
            <i className={`fa-solid ${saving?'fa-spinner fa-spin':editing?'fa-check':'fa-pen'}`}/>
            {saving?'Saving...':editing?'Save Changes':'Edit'}
          </button>
        </div>
        <div className="up-info-grid">
          <UpInfoTile label="University Name" value={form.uniName}    icon="fa-university" editing={editing} onChange={v=>set('uni_name',v)}/>
          <UpInfoTile label="Country"         value={form.country}    icon="fa-flag"        editing={editing} onChange={v=>set('country',v)}/>
          <UpInfoTile label="Website"         value={form.website}    icon="fa-globe"       editing={editing} onChange={v=>set('website',v)}/>
          <UpInfoTile label="Intake Periods"  value={form.intakePeriods} icon="fa-calendar" editing={editing} onChange={v=>set('intake_periods',v)} placeholder="e.g. Sept & Jan"/>
          <UpInfoTile label="Type"            value={form.uniType}    icon="fa-landmark"   editing={editing} onChange={v=>set('uni_type',v)} placeholder="Public / Private"/>
          <UpInfoTile label="Year Founded"    value={form.yearFounded} icon="fa-clock"     editing={editing} onChange={v=>set('year_founded',v)} inputType="number"/>
        </div>
      </div>

      {/* PROGRAMS */}
      <div className="up-card up-fade-up up-fade-up-2">
        <div className="up-pill"><i className="fa-solid fa-book-open"/> Programmes Offered</div>
        {editing
          ? <ProgramSelector selected={programs} onChange={setPrograms}/>
          : programs.length===0
            ? <p style={{color:'var(--text-lo)',fontSize:13}}>No programmes added. Click Edit to add.</p>
            : <div style={{display:'flex',flexWrap:'wrap',gap:8}}>
                {programs.map((p,i)=>(
                  <span key={i} className="up-program-tag" style={{cursor:'default',pointerEvents:'none'}}>
                    {p}
                  </span>
                ))}
              </div>
        }
      </div>

      {/* DESCRIPTION */}
      <div className="up-card up-fade-up up-fade-up-3">
        <div className="up-pill"><i className="fa-solid fa-pen-to-square"/> Institution Description</div>
        <textarea className="up-textarea" rows={4}
          placeholder="Describe your university — programmes, campus culture, international opportunities, scholarship availability..."
          defaultValue={user.description||''}/>
        <div style={{display:'flex',justifyContent:'flex-end',marginTop:12}}>
          <button className="up-btn up-btn-gold" style={{fontSize:13}}
            onClick={()=>toast('Description saved.','success')}>
            <i className="fa-solid fa-floppy-disk"/> Save Description
          </button>
        </div>
      </div>

    </div>
  );
};

/* ── APPLICANTS TAB ────────────────────────────────────── */
const StudentCVModal = ({ student, onClose, onSendMessage }) => {
  const toast = useUpToast();
  const displayName = studentDisplayName(student.full_name || student.surname || '');
  const docs = [
    { label:'Transcript', done:(student.doc_count||0) > 0 },
    { label:'Passport / ID', done:(student.doc_count||0) > 1 },
    { label:'Personal Statement', done:(student.doc_count||0) > 2 },
    { label:'Intro Video', done:(student.doc_count||0) > 3 },
  ];

  const requestIntroduction = () => {
    fetch(API + 'notifications.php', {
      method:'POST',
      credentials:'include',
      headers:{ 'Content-Type':'application/json' },
      body: JSON.stringify({
        target_user_id: student.student_id || student.id,
        type: 'profile_viewed',
        message: 'A university has viewed your full profile.'
      })
    }).catch(() => {});
    fetch(API + 'notifications.php', {
      method:'POST',
      credentials:'include',
      headers:{ 'Content-Type':'application/json' },
      body: JSON.stringify({
        type: 'admin_alert',
        message: `University viewed student profile: ${displayName}`
      })
    }).catch(() => {});
    toast('Introduction request flow coming soon.', 'info');
  };

  return (
    <div style={{position:'fixed', inset:0, zIndex:9000, background:'rgba(6,16,30,0.97)',
      backdropFilter:'blur(20px)', overflowY:'auto', display:'flex', flexDirection:'column',
      alignItems:'center', padding:'40px 20px'}}>
      <button onClick={onClose} style={{position:'fixed', top:20, right:20,
        background:'rgba(255,255,255,0.08)', border:'none', color:'#f0f4ff',
        width:40, height:40, borderRadius:10, cursor:'pointer', fontSize:18}}>
        ×
      </button>
      <div style={{width:'100%', maxWidth:720}}>
        <div style={{padding:'28px', borderRadius:24, background:'linear-gradient(135deg,rgba(245,158,11,.18),rgba(26,86,219,.14))',
          border:'1px solid rgba(245,158,11,.25)', marginBottom:18}}>
          <div style={{display:'flex',justifyContent:'space-between',gap:16,alignItems:'center',flexWrap:'wrap'}}>
            <div style={{display:'flex',alignItems:'center',gap:16}}>
              <div style={{width:76,height:76,borderRadius:20,background:'rgba(255,255,255,.08)',
                display:'flex',alignItems:'center',justifyContent:'center',fontSize:24,fontWeight:800}}>
                {displayName.split(' ').map(p=>p[0]).join('').slice(0,2).toUpperCase()}
              </div>
              <div>
                <div style={{fontFamily:"'Sora',sans-serif",fontSize:28,fontWeight:800}}>{displayName}</div>
                <div style={{display:'flex',gap:8,marginTop:10,flexWrap:'wrap'}}>
                  <span className="up-program-tag">GPA {student.gpa || '—'}</span>
                  {student.verified === 'verified' && <span className="up-program-tag">Verified</span>}
                </div>
              </div>
            </div>
            {student.match_score && (
              <div style={{padding:'10px 14px',borderRadius:14,background:'rgba(255,255,255,.08)',fontWeight:700}}>
                Match {student.match_score}%
              </div>
            )}
          </div>
        </div>

        <div className="up-card" style={{display:'grid',gridTemplateColumns:'repeat(4,1fr)',gap:12,marginBottom:18}}>
          {[
            ['GPA', student.gpa || '—'],
            ['Major', student.major || '—'],
            ['Budget', student.budget || '—'],
            ['Target Intake', student.intake || '—'],
          ].map(([label, value]) => (
            <div key={label} style={{padding:'16px',borderRadius:'var(--radius-sm)',background:'rgba(255,255,255,.04)',border:'1px solid var(--glass-border)'}}>
              <div style={{fontSize:11,color:'var(--text-lo)',marginBottom:6}}>{label}</div>
              <div style={{fontWeight:700}}>{value}</div>
            </div>
          ))}
        </div>

        <div className="up-card" style={{marginBottom:18}}>
          <div className="up-pill up-pill-blue"><i className="fa-solid fa-quote-left"/> About</div>
          <blockquote style={{margin:0,padding:'18px 20px',borderLeft:'3px solid var(--gold)',background:'rgba(255,255,255,.03)',lineHeight:1.8,color:'var(--text-mid)'}}>
            {student.ai_description || student.description || 'No student summary submitted yet.'}
          </blockquote>
        </div>

        <div className="up-card" style={{marginBottom:18}}>
          <div className="up-pill"><i className="fa-solid fa-list-check"/> Documents</div>
          <div style={{display:'grid',gap:10}}>
            {docs.map(doc => (
              <div key={doc.label} style={{display:'flex',alignItems:'center',justifyContent:'space-between',padding:'12px 14px',borderRadius:12,
                background:'rgba(255,255,255,.03)',border:'1px solid var(--glass-border)'}}>
                <span>{doc.label}</span>
                <span style={{color:doc.done?'#22c55e':'#64748b'}}>
                  <i className={`fa-solid ${doc.done?'fa-circle-check':'fa-minus'}`}/>
                </span>
              </div>
            ))}
          </div>
        </div>

        <div style={{display:'flex',gap:12,flexWrap:'wrap'}}>
          <button className="up-btn up-btn-gold" onClick={requestIntroduction}>
            <i className="fa-solid fa-handshake"/> Request Introduction
          </button>
          <button className="up-btn up-btn-blue" onClick={() => onSendMessage(student)}>
            <i className="fa-solid fa-paper-plane"/> Send Message
          </button>
        </div>
      </div>
    </div>
  );
};

const ApplicantsTab = ({ user, onSendMessage }) => {
  const toast = useUpToast();
  const [apps,    setApps]    = useState([]);
  const [loading, setLoading] = useState(true);
  const [error,   setError]   = useState('');
  const [filter,  setFilter]  = useState('all');
  const [selectedStudent, setSelectedStudent] = useState(null);

  useEffect(()=>{
    fetch(API+'applications.php',{credentials:'include'})
    .then(r => {
      if (!r.ok) {
        console.error('❌ applications.php:', r.status);
        setError('Could not load data. Please refresh.');
        return null;
      }
      return r.json();
    })
    .then(d=>{
      if(d && d.success){
        setApps(d.applications||[]);
        setError('');
      }
    })
    .catch(err => {
      console.error('❌ applications.php:', err);
      setError('Could not load data. Please refresh.');
    })
    .finally(()=>setLoading(false));
  },[]);

  const updateStatus=(id,status)=>{
    fetch(API+'applications.php',{method:'POST',credentials:'include',
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify({id,status})})
    .then(r=>r.json())
    .then(d=>{
      if(d.success){
        setApps(a=>a.map(x=>x.id===id?{...x,status}:x));
        toast(`Status updated to ${status}.`,'success');
      }else toast(d.error||'Update failed.','error');
    })
    .catch(()=>toast('Update failed.','error'));
  };

  const filtered=filter==='all'?apps:apps.filter(a=>a.status===filter);

  return (
    <div style={{display:'flex',flexDirection:'column',gap:16}}>

      {/* INTAKE DASHBOARD STRIP */}
      <div className="up-card up-fade-up"
        style={{background:'linear-gradient(135deg,rgba(245,158,11,0.06),rgba(26,86,219,0.04))',
          border:'1px solid var(--gold-border)'}}>
        <div className="up-pill"><i className="fa-solid fa-chart-line"/> Live Intake Dashboard</div>
        <div style={{display:'grid',gridTemplateColumns:'repeat(4,1fr)',gap:12}}>
          <div className="up-intake-stat">
            <div className="up-intake-value">{apps.length}</div>
            <div className="up-intake-label">Total Applicants</div>
          </div>
          <div className="up-intake-stat">
            <div className="up-intake-value">
              {apps.length ? (apps.reduce((a,b)=>a+(parseFloat(b.gpa)||0),0)/apps.length).toFixed(1) : '—'}
            </div>
            <div className="up-intake-label">Avg GPA</div>
          </div>
          <div className="up-intake-stat">
            <div className="up-intake-value">
              {apps.filter(a=>a.status==='offer').length}
            </div>
            <div className="up-intake-label">Offers Made</div>
          </div>
          <div className="up-intake-stat">
            <div className="up-intake-value">
              {[...new Set(apps.map(a=>a.nationality).filter(Boolean))].length}
            </div>
            <div className="up-intake-label">Nationalities</div>
          </div>
        </div>
      </div>

      {/* FILTER + LIST */}
      <div className="up-card up-fade-up up-fade-up-1">
        <div className="up-pill up-pill-blue"><i className="fa-solid fa-users"/> Applicant Pipeline</div>

        <div className="up-tabs" style={{marginBottom:20}}>
          {['all','new','review','interview','offer','enrolled','rejected'].map(f=>(
            <div key={f} className={`up-tab ${filter===f?'active':''}`} onClick={()=>setFilter(f)}>
              {f.charAt(0).toUpperCase()+f.slice(1)}
              <span style={{fontSize:10,background:'rgba(255,255,255,0.08)',padding:'1px 6px',
                borderRadius:99,marginLeft:4}}>
                {f==='all'?apps.length:apps.filter(a=>a.status===f).length}
              </span>
            </div>
          ))}
        </div>

        {loading ? (
          <div style={{display:'flex',flexDirection:'column',gap:12}}>
            {[1,2,3].map(i=><div key={i} className="up-skeleton" style={{height:100,borderRadius:14}}/>)}
          </div>
        ) : error ? (
          <div style={{color:'#fca5a5',padding:'20px 0'}}>{error}</div>
        ) : filtered.length===0 ? (
          <div style={{textAlign:'center',padding:'40px 20px'}}>
            <i className="fa-solid fa-inbox" style={{fontSize:36,color:'var(--text-lo)',marginBottom:16}}/>
            <h4 style={{fontWeight:600,marginBottom:6}}>No applicants in this category</h4>
          </div>
        ) : (
          <div style={{display:'grid',gridTemplateColumns:'repeat(auto-fill,minmax(280px,1fr))',gap:16}}>
            {filtered.map((app,i)=>{
              const st=APP_STATUS[app.status]||APP_STATUS.new;
              return (
                <div key={i} className="up-student-card" onClick={()=>setSelectedStudent(app)}>
                  <div style={{display:'flex',justifyContent:'space-between',alignItems:'flex-start',marginBottom:12}}>
                    <div className="up-student-avatar">
                      {(app.surname||app.full_name||'?').charAt(0).toUpperCase()}
                    </div>
                    <span style={{fontSize:11,fontWeight:600,padding:'3px 9px',borderRadius:99,
                      background:`${st.col}18`,color:st.col,border:`1px solid ${st.col}30`}}>
                      {st.label}
                    </span>
                  </div>
                  <div className="up-student-surname">
                    {app.surname || (app.full_name||'').split(' ').pop() || 'Applicant'}
                  </div>
                  <div className="up-student-meta">
                    {app.major && <><i className="fa-solid fa-book" style={{marginRight:4}}/>{app.major} · </>}
                    GPA {app.gpa||'—'} · {app.nationality||'—'}
                  </div>
                  <div className="up-student-tags">
                    {app.intake && <span className="up-student-tag">{app.intake}</span>}
                    {app.budget && <span className="up-student-tag">{app.budget}</span>}
                    {app.verified==='verified' && <span className="up-student-tag">✓ Verified</span>}
                  </div>
                  <div style={{display:'flex',gap:6,marginTop:14,flexWrap:'wrap'}}>
                    {app.status!=='offer' && (
                      <button className="up-btn" style={{padding:'6px 12px',fontSize:11,
                        background:'var(--green-dim)',color:'var(--green)',
                        border:'1px solid rgba(34,197,94,0.25)'}}
                        onClick={(e)=>{ e.stopPropagation(); updateStatus(app.id,'offer'); }}>
                        <i className="fa-solid fa-check"/> Offer
                      </button>
                    )}
                    {app.status!=='interview' && (
                      <button className="up-btn" style={{padding:'6px 12px',fontSize:11,
                        background:'rgba(139,92,246,0.1)',color:'var(--purple)',
                        border:'1px solid rgba(139,92,246,0.25)'}}
                        onClick={(e)=>{ e.stopPropagation(); updateStatus(app.id,'interview'); }}>
                        <i className="fa-solid fa-video"/> Interview
                      </button>
                    )}
                    {app.status!=='rejected' && (
                      <button className="up-btn" style={{padding:'6px 12px',fontSize:11,
                        background:'var(--red-dim)',color:'var(--red)',
                        border:'1px solid rgba(239,68,68,0.25)'}}
                        onClick={(e)=>{ e.stopPropagation(); updateStatus(app.id,'rejected'); }}>
                        <i className="fa-solid fa-xmark"/> Reject
                      </button>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </div>
      {selectedStudent && (
        <StudentCVModal
          student={selectedStudent}
          onClose={() => setSelectedStudent(null)}
          onSendMessage={(student) => {
            onSendMessage && onSendMessage(student);
            setSelectedStudent(null);
          }}
        />
      )}
    </div>
  );
};

/* ── SEMINARS TAB (university) ─────────────────────────── */
const UpSeminarsTab = ({ user }) => {
  const toast = useUpToast();
  const [seminars, setSeminars] = useState([]);
  const [loading,  setLoading]  = useState(true);
  const [showForm, setShowForm] = useState(false);
  const [form,     setForm]     = useState({
    title:'', description:'', target_majors:'', target_intake:'',
    max_participants:100, meet_link:'', scheduled_at:'', tier:'standard',
  });
  const [creating, setCreating] = useState(false);

  useEffect(()=>{
    fetch(API+'seminars.php?action=list',{credentials:'include'})
    .then(r=>r.json())
    .then(d=>{if(d.success)setSeminars(d.seminars||[]);})
    .catch(()=>{})
    .finally(()=>setLoading(false));
  },[]);

  const createSeminar=()=>{
    if(!form.title||!form.scheduled_at) { toast('Title and date are required.','error'); return; }
    const requestedTime = new Date(form.scheduled_at).getTime();
    const conflict = seminars.find(s => {
      const semTime = new Date(s.scheduled_at || `${s.event_date || ''} ${s.event_time || ''}`).getTime();
      return semTime && Math.abs(semTime - requestedTime) < 2 * 60 * 60 * 1000;
    });
    if (conflict) {
      toast(`Scheduling conflict: "${conflict.title}" is already at ${fmtDT(conflict.scheduled_at || `${conflict.event_date || ''} ${conflict.event_time || ''}`)}.`, 'error');
      return;
    }
    setCreating(true);
    fetch(API+'seminars.php',{method:'POST',credentials:'include',
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify({action:'create',university_id:user.userId,...form,
        price_fcfa:form.tier==='basic'?150000:form.tier==='premium'?400000:250000})})
    .then(r=>r.json())
    .then(d=>{
      if(d.success){
        toast('Seminar created! BetterAbroad will notify students.','success');
        setShowForm(false);
        setSeminars(s=>[{...form,id:d.seminar_id,registered_count:0,status:'scheduled'},...s]);
      }else toast(d.error||'Creation failed.','error');
    })
    .catch(err => {
      console.error('❌ seminars.php:', err);
      toast('Creation failed.','error');
    })
    .finally(()=>setCreating(false));
  };

  return (
    <div style={{display:'flex',flexDirection:'column',gap:20}}>

      <div style={{display:'flex',justifyContent:'space-between',alignItems:'center'}}>
        <div>
          <h3 style={{fontFamily:"'Sora',sans-serif",fontWeight:700,fontSize:17,marginBottom:3}}>
            Virtual Recruitment Seminars
          </h3>
          <p style={{fontSize:12,color:'var(--text-mid)'}}>
            Host sessions with verified African students — no flights required.
          </p>
        </div>
        <button className="up-btn up-btn-gold" onClick={()=>setShowForm(!showForm)}>
          <i className={`fa-solid ${showForm?'fa-xmark':'fa-plus'}`}/>
          {showForm ? 'Cancel' : 'Book a Seminar'}
        </button>
      </div>

      {/* PRICING REMINDER */}
      <div className="up-card" style={{display:'grid',gridTemplateColumns:'repeat(3,1fr)',gap:12,
        background:'linear-gradient(135deg,rgba(245,158,11,0.05),rgba(26,86,219,0.03))',
        border:'1px solid var(--gold-border)'}}>
        {[
          {tier:'Basic',   cap:'50 students',   price:'$100 USD',  note:'150,000 FCFA'},
          {tier:'Standard',cap:'100 students',  price:'~$165 USD', note:'250,000 FCFA', highlight:true},
          {tier:'Premium', cap:'200 students',  price:'~$265 USD', note:'400,000 FCFA'},
        ].map(t=>(
          <div key={t.tier} style={{textAlign:'center',padding:'16px',borderRadius:'var(--radius-sm)',
            background:t.highlight?'rgba(245,158,11,0.08)':'transparent',
            border:t.highlight?'1px solid var(--gold-border)':'1px solid transparent'}}>
            <div style={{fontFamily:"'Sora',sans-serif",fontWeight:700,fontSize:14,marginBottom:4}}>
              {t.tier}
            </div>
            <div style={{fontSize:13,color:'var(--text-mid)',marginBottom:4}}>{t.cap}</div>
            <div style={{color:'var(--gold)',fontWeight:700,fontSize:16}}>{t.price}</div>
            <div style={{fontSize:11,color:'var(--text-lo)'}}>{t.note}</div>
          </div>
        ))}
      </div>

      {/* CREATE FORM */}
      {showForm && (
        <div className="up-card up-fade-up" style={{border:'1px solid var(--gold-border)'}}>
          <div className="up-pill"><i className="fa-solid fa-plus"/> New Seminar Request</div>
          <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:14}}>
            <div style={{gridColumn:'1/-1'}}>
              <label style={{fontSize:12,color:'var(--text-lo)',marginBottom:6,display:'block'}}>Seminar Title *</label>
              <input className="up-input" placeholder="e.g. Computer Science Intake 2026 — Recruitment Session"
                value={form.title} onChange={e=>setForm(f=>({...f,title:e.target.value}))}/>
            </div>
            <div>
              <label style={{fontSize:12,color:'var(--text-lo)',marginBottom:6,display:'block'}}>Date & Time *</label>
              <input className="up-input" type="datetime-local"
                value={form.scheduled_at} onChange={e=>setForm(f=>({...f,scheduled_at:e.target.value}))}/>
            </div>
            <div>
              <label style={{fontSize:12,color:'var(--text-lo)',marginBottom:6,display:'block'}}>Tier</label>
              <select className="up-select"
                value={form.tier} onChange={e=>setForm(f=>({...f,tier:e.target.value}))}>
                <option value="basic">Basic — 50 students ($100)</option>
                <option value="standard">Standard — 100 students (~$165)</option>
                <option value="premium">Premium — 200 students (~$265)</option>
              </select>
            </div>
            <div>
              <label style={{fontSize:12,color:'var(--text-lo)',marginBottom:6,display:'block'}}>Target Majors</label>
              <input className="up-input" placeholder="e.g. Computer Science, Data Science"
                value={form.target_majors} onChange={e=>setForm(f=>({...f,target_majors:e.target.value}))}/>
            </div>
            <div>
              <label style={{fontSize:12,color:'var(--text-lo)',marginBottom:6,display:'block'}}>Target Intake</label>
              <input className="up-input" placeholder="e.g. September 2026"
                value={form.target_intake} onChange={e=>setForm(f=>({...f,target_intake:e.target.value}))}/>
            </div>
            <div>
              <label style={{fontSize:12,color:'var(--text-lo)',marginBottom:6,display:'block'}}>Google Meet Link</label>
              <input className="up-input" placeholder="https://meet.google.com/..."
                value={form.meet_link} onChange={e=>setForm(f=>({...f,meet_link:e.target.value}))}/>
            </div>
            <div style={{gridColumn:'1/-1'}}>
              <label style={{fontSize:12,color:'var(--text-lo)',marginBottom:6,display:'block'}}>Description</label>
              <textarea className="up-textarea" rows={3} placeholder="Tell students what to expect from this session..."
                value={form.description} onChange={e=>setForm(f=>({...f,description:e.target.value}))}/>
            </div>
          </div>
          <div style={{display:'flex',justifyContent:'flex-end',gap:10,marginTop:16}}>
            <button className="up-btn up-btn-ghost" onClick={()=>setShowForm(false)}>Cancel</button>
            <button className="up-btn up-btn-gold" onClick={createSeminar} disabled={creating}>
              <i className={`fa-solid ${creating?'fa-spinner fa-spin':'fa-calendar-check'}`}/>
              {creating?'Submitting...':'Submit Seminar Request'}
            </button>
          </div>
          <p style={{fontSize:11,color:'var(--text-lo)',marginTop:10,textAlign:'center'}}>
            BetterAbroad admin will confirm your seminar and notify matching students.
            Payment is collected before confirmation.
          </p>
        </div>
      )}

      {/* SEMINAR LIST */}
      {loading ? (
        <div style={{display:'flex',flexDirection:'column',gap:12}}>
          {[1,2].map(i=><div key={i} className="up-skeleton" style={{height:120,borderRadius:16}}/>)}
        </div>
      ) : seminars.length===0 ? (
        <div className="up-card" style={{textAlign:'center',padding:'48px 20px'}}>
          <i className="fa-solid fa-chalkboard-teacher" style={{fontSize:36,color:'var(--text-lo)',marginBottom:16}}/>
          <h4 style={{fontWeight:600,marginBottom:6}}>No seminars yet</h4>
          <p style={{color:'var(--text-mid)',fontSize:13}}>Book your first virtual recruitment session above.</p>
        </div>
      ) : (
        seminars.map(s=>{
          const pct=Math.round((s.registered_count/s.max_participants)*100);
          return (
            <div key={s.id} className="up-seminar-upcoming up-fade-up">
              <div style={{display:'flex',justifyContent:'space-between',alignItems:'flex-start',gap:12,flexWrap:'wrap'}}>
                <div>
                  <div style={{fontFamily:"'JetBrains Mono',monospace",fontSize:11,color:'var(--gold)',marginBottom:6}}>
                    {fmtDT(s.scheduled_at)} · {s.tier?.charAt(0).toUpperCase()+s.tier?.slice(1)}
                  </div>
                  <div style={{fontFamily:"'Sora',sans-serif",fontWeight:700,fontSize:16,marginBottom:4}}>
                    {s.title}
                  </div>
                  {s.target_majors && (
                    <div style={{fontSize:12,color:'var(--text-mid)',marginBottom:10}}>
                      {s.target_majors}
                    </div>
                  )}
                </div>
                <span style={{fontSize:11,fontWeight:600,padding:'4px 12px',borderRadius:99,
                  background:s.status==='completed'?'var(--green-dim)':'var(--gold-dim)',
                  color:s.status==='completed'?'var(--green)':'var(--gold)',
                  border:`1px solid ${s.status==='completed'?'rgba(34,197,94,0.25)':'var(--gold-border)'}`}}>
                  {s.status?.charAt(0).toUpperCase()+s.status?.slice(1)||'Scheduled'}
                </span>
              </div>
              <div style={{marginBottom:8}}>
                <div style={{display:'flex',justifyContent:'space-between',
                  fontSize:11,color:'var(--text-lo)',marginBottom:6}}>
                  <span>{s.registered_count||0} / {s.max_participants} students registered</span>
                  <span>{pct}%</span>
                </div>
                <div className="up-progress-bar"><div className="up-progress-fill" style={{width:pct+'%'}}/></div>
              </div>
              {s.meet_link && s.status==='scheduled' && (
                <a href={s.meet_link} target="_blank" rel="noreferrer"
                  className="up-btn up-btn-blue" style={{textDecoration:'none',fontSize:12,display:'inline-flex'}}>
                  <i className="fa-solid fa-video"/> Join Google Meet
                </a>
              )}
            </div>
          );
        })
      )}
    </div>
  );
};

/* ── DOCUMENTS TAB ─────────────────────────────────────── */
const UpDocumentsTab = ({ docs, onUpload }) => {
  const uploadedMap={};
  (docs||[]).forEach(d=>{uploadedMap[d.doc_type]=d;});
  const slots=[
    {key:'logo',          label:'University Logo',          icon:'fa-image'},
    {key:'accreditation', label:'Accreditation Certificate',icon:'fa-certificate'},
    {key:'partnership',   label:'Partnership Agreement',    icon:'fa-handshake'},
    {key:'brochure',      label:'Institutional Brochure',   icon:'fa-file-lines'},
  ];
  return (
    <div className="up-card up-fade-up">
      <div className="up-pill"><i className="fa-solid fa-folder-open"/> Institution Documents</div>
      <p style={{fontSize:13,color:'var(--text-mid)',marginBottom:20,lineHeight:1.7}}>
        Upload your institution's documents for verification. Your profile becomes visible to students
        once BetterAbroad admin has verified your accreditation.
      </p>
      <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:16}}>
        {slots.map(s=>(
          <UpDocSlot key={s.key} docKey={s.key} label={s.label} icon={s.icon}
            uploaded={uploadedMap[s.key]} onUpload={onUpload}/>
        ))}
      </div>
    </div>
  );
};

/* ── MESSAGES TAB ──────────────────────────────────────── */
const UpMessagesTab = ({ user, messageTarget }) => {
  const toast = useUpToast();
  const [convos,  setConvos]  = useState([]);
  const [active,  setActive]  = useState(null);
  const [thread,  setThread]  = useState([]);
  const [body,    setBody]    = useState('');
  const [sending, setSending] = useState(false);
  const [loading, setLoading] = useState(true);
  const bottomRef = useRef();

  useEffect(()=>{
    fetch(API+'conversations.php',{credentials:'include'})
    .then(r=>r.json()).then(d=>{if(d.success)setConvos(d.conversations||[]);})
    .catch(()=>{}).finally(()=>setLoading(false));
  },[]);

  useEffect(() => {
    if (!messageTarget) return;
    const targetId = messageTarget.student_id || messageTarget.id;
    const existing = convos.find(c => String(c.contact_id) === String(targetId));
    setActive(existing || {
      contact_id: targetId,
      contact_name: studentDisplayName(messageTarget.full_name || messageTarget.surname || ''),
      contact_role: 'student',
      last_message: '',
      unread_count: 0,
    });
  }, [messageTarget, convos]);

  useEffect(()=>{
    if(!active) return;
    const poll=()=>fetch(`${API}thread.php?with=${active.contact_id}`,{credentials:'include'})
      .then(r=>r.json()).then(d=>{if(d.success)setThread(d.messages||[]);}).catch(()=>{});
    poll();
    const t=setInterval(poll,10000);
    return ()=>clearInterval(t);
  },[active]);

  useEffect(()=>{ bottomRef.current?.scrollIntoView({behavior:'smooth'}); },[thread]);

  const send=()=>{
    if(!body.trim()||!active) return;
    setSending(true);
    fetch(API+'send.php',{method:'POST',credentials:'include',
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify({to_user_id:active.contact_id,body:body.trim()})})
    .then(r=>r.json())
    .then(d=>{
      if(d.success){setThread(t=>[...t,{from_user_id:user.userId,body:body.trim(),created_at:d.created_at}]);setBody('');}
      else toast(d.error||'Send failed.','error');
    })
    .catch(()=>toast('Send failed.','error'))
    .finally(()=>setSending(false));
  };

  return (
    <div style={{display:'grid',gridTemplateColumns:'260px 1fr',gap:16,height:'calc(100vh - 280px)',minHeight:400}}>
      <div className="up-card" style={{padding:0,overflow:'hidden',display:'flex',flexDirection:'column'}}>
        <div style={{padding:'14px 16px',borderBottom:'1px solid var(--glass-border)',
          fontFamily:"'Sora',sans-serif",fontWeight:700,fontSize:14}}>Conversations</div>
        <div style={{flex:1,overflowY:'auto',padding:8}}>
          {loading?[1,2].map(i=><div key={i} className="up-skeleton" style={{height:52,borderRadius:10,margin:'4px 0'}}/>)
          :convos.length===0?<div style={{padding:'20px 12px',fontSize:13,color:'var(--text-lo)',textAlign:'center'}}>No conversations</div>
          :convos.map(c=>(
            <div key={c.contact_id} onClick={()=>setActive(c)}
              style={{display:'flex',alignItems:'center',gap:10,padding:'10px 12px',borderRadius:10,
                cursor:'pointer',transition:'all .2s',
                background:active?.contact_id===c.contact_id?'rgba(245,158,11,0.08)':'transparent',
                border:`1px solid ${active?.contact_id===c.contact_id?'var(--gold-border)':'transparent'}`}}>
              <div style={{width:34,height:34,borderRadius:8,flexShrink:0,
                background:'linear-gradient(135deg,rgba(245,158,11,.2),rgba(26,86,219,.1))',
                display:'flex',alignItems:'center',justifyContent:'center',
                fontFamily:"'Sora',sans-serif",fontWeight:700,fontSize:11,color:'var(--gold)'}}>
                {(c.contact_name||'?').charAt(0)}
              </div>
              <div style={{flex:1,minWidth:0}}>
                <div style={{fontWeight:600,fontSize:13,whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis'}}>
                  {c.contact_name}
                </div>
                <div style={{fontSize:11,color:'var(--text-lo)',whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis'}}>
                  {c.last_message}
                </div>
              </div>
              {c.unread_count>0&&<span style={{background:'var(--gold)',color:' var(--navy)',
                fontSize:10,fontWeight:800,borderRadius:99,padding:'2px 6px'}}>{c.unread_count}</span>}
            </div>
          ))}
        </div>
      </div>
      <div className="up-card" style={{padding:0,display:'flex',flexDirection:'column',overflow:'hidden'}}>
        {!active?(
          <div style={{flex:1,display:'flex',flexDirection:'column',alignItems:'center',justifyContent:'center',gap:12,color:'var(--text-lo)'}}>
            <i className="fa-solid fa-comment-dots" style={{fontSize:36}}/>
            <p style={{fontSize:13}}>Select a conversation</p>
          </div>
        ):(
          <>
            <div style={{padding:'14px 18px',borderBottom:'1px solid var(--glass-border)',
              display:'flex',alignItems:'center',gap:12}}>
              <div style={{width:36,height:36,borderRadius:8,
                background:'linear-gradient(135deg,rgba(245,158,11,.2),rgba(26,86,219,.1))',
                display:'flex',alignItems:'center',justifyContent:'center',
                fontFamily:"'Sora',sans-serif",fontWeight:700,fontSize:12,color:'var(--gold)'}}>
                {(active.contact_name||'?').charAt(0)}
              </div>
              <div>
                <div style={{fontWeight:700,fontSize:14}}>{active.contact_name}</div>
                <div style={{fontSize:11,color:'var(--text-lo)',textTransform:'capitalize'}}>{active.contact_role}</div>
              </div>
            </div>
            <div style={{flex:1,overflowY:'auto',padding:16,display:'flex',flexDirection:'column',gap:10}}>
              {thread.map((msg,i)=>{
                const mine=msg.from_user_id===user.userId;
                return(
                  <div key={i} style={{display:'flex',justifyContent:mine?'flex-end':'flex-start'}}>
                    <div style={{maxWidth:'70%',padding:'10px 14px',borderRadius:12,fontSize:13,lineHeight:1.6,
                      background:mine?'linear-gradient(135deg,var(--gold),#f97316)':'rgba(255,255,255,0.05)',
                      color:mine?'var(--navy)':'var(--text-hi)',
                      borderBottomRightRadius:mine?4:12,borderBottomLeftRadius:mine?12:4}}>
                      {msg.body}
                      <div style={{fontSize:10,color:mine?'rgba(6,16,30,0.5)':'var(--text-lo)',marginTop:4,textAlign:'right'}}>
                        {timeAgo(msg.created_at)}
                      </div>
                    </div>
                  </div>
                );
              })}
              <div ref={bottomRef}/>
            </div>
            <div style={{padding:'12px 16px',borderTop:'1px solid var(--glass-border)'}}>
              <div style={{fontSize:10,color:'var(--text-lo)',marginBottom:8,textAlign:'center'}}>
                Personal contact information sharing is not permitted on this platform.
              </div>
              <div style={{display:'flex',gap:10}}>
                <input className="up-input" style={{flex:1,padding:'10px 14px'}}
                  placeholder="Write a message..." value={body} onChange={e=>setBody(e.target.value)}
                  onKeyDown={e=>e.key==='Enter'&&!e.shiftKey&&send()}/>
                <button className="up-btn up-btn-gold" onClick={send} disabled={sending||!body.trim()}>
                  <i className={`fa-solid ${sending?'fa-spinner fa-spin':'fa-paper-plane'}`}/>
                </button>
              </div>
            </div>
          </>
        )}
      </div>
    </div>
  );
};

/* ── SETTINGS TAB ──────────────────────────────────────── */
const UpSettingsTab = ({ user, onLogout }) => {
  const toast = useUpToast();
  const [pw, setPw] = useState({current:'',next:'',confirm:''});
  const [saving, setSaving] = useState(false);
  const changePassword=()=>{
    if(pw.next!==pw.confirm){toast('Passwords do not match.','error');return;}
    if(pw.next.length<8){toast('Min 8 characters.','error');return;}
    setSaving(true);
    fetch(API+'save.php',{method:'POST',credentials:'include',
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify({current_password:pw.current,new_password:pw.next})})
    .then(r=>r.json())
    .then(d=>{if(d.success){toast('Password updated.','success');setPw({current:'',next:'',confirm:''});}else toast(d.error||'Failed.','error');})
    .catch(()=>toast('Failed.','error')).finally(()=>setSaving(false));
  };
  return (
    <div style={{display:'flex',flexDirection:'column',gap:20}}>
      <div className="up-card up-fade-up">
        <div className="up-pill"><i className="fa-solid fa-lock"/> Change Password</div>
        <div style={{display:'flex',flexDirection:'column',gap:12,maxWidth:420}}>
          <input className="up-input" type="password" placeholder="Current password" value={pw.current} onChange={e=>setPw(p=>({...p,current:e.target.value}))}/>
          <input className="up-input" type="password" placeholder="New password (min 8 chars)" value={pw.next} onChange={e=>setPw(p=>({...p,next:e.target.value}))}/>
          <input className="up-input" type="password" placeholder="Confirm new password" value={pw.confirm} onChange={e=>setPw(p=>({...p,confirm:e.target.value}))}/>
          <button className="up-btn up-btn-gold" style={{alignSelf:'flex-start'}} onClick={changePassword} disabled={saving}>
            <i className={`fa-solid ${saving?'fa-spinner fa-spin':'fa-key'}`}/>{saving?'Saving...':'Update Password'}
          </button>
        </div>
      </div>
      <div className="up-card up-fade-up up-fade-up-1"
        style={{border:'1px solid rgba(239,68,68,0.15)',background:'rgba(239,68,68,0.03)'}}>
        <div className="up-pill" style={{background:'var(--red-dim)',color:'var(--red)',borderColor:'rgba(239,68,68,0.2)'}}>
          <i className="fa-solid fa-triangle-exclamation"/> Danger Zone
        </div>
        <p style={{fontSize:13,color:'var(--text-mid)',marginBottom:16}}>
          Logging out will end your session. Your data remains saved.
        </p>
        <button className="up-btn" style={{background:'var(--red-dim)',color:'var(--red)',border:'1px solid rgba(239,68,68,0.25)'}} onClick={onLogout}>
          <i className="fa-solid fa-right-from-bracket"/> Sign Out
        </button>
      </div>
    </div>
  );
};

/* ══════════════════════════════════════════════════════
   MAIN UNIVERSITY PROFILE COMPONENT
   Props: user, setUser, onNavigate
   ══════════════════════════════════════════════════════ */
window.UniversityProfilePage = ({ user, setUser, onNavigate }) => {
  const [activeTab, setActiveTab] = useState('overview');
  const [docs,      setDocs]      = useState([]);
  const [unread,    setUnread]    = useState(0);
  const [tosShown,  setTosShown]  = useState(() => !Boolean(user.tosAccepted));
  const [loading,   setLoading]   = useState(true);
  const [error,     setError]     = useState('');
  const [messageTarget, setMessageTarget] = useState(null);

  useEffect(()=>{
    fetch(API+'documents.php',{credentials:'include'})
    .then(r=>r.json()).then(d=>{
      if(d.success){
        setDocs(d.documents||[]);
        setError('');
      }
    }).catch(err=>{
      console.error('❌ documents.php:', err);
      setError('Could not load data. Please refresh.');
    }).finally(()=>setLoading(false));
  },[]);

  useEffect(()=>{
    const poll=()=>fetch(API+'unread.php',{credentials:'include'})
      .then(r=>r.json()).then(d=>{if(d.success)setUnread(d.count||0);}).catch(err=>{
        console.error('❌ unread.php:', err);
        setError('Could not load data. Please refresh.');
      });
    poll();
    const t=setInterval(poll,30000);
    return()=>clearInterval(t);
  },[]);

  useEffect(() => {
    const checkVerification = () => {
      fetch(API + 'me.php', { credentials:'include' })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            const newVerified = data.profile?.verified || data.user?.verified;
            const newTosAccepted = Boolean(data.tosAccepted ?? data.user?.tosAccepted);
            if ((newVerified && newVerified !== user.verified) || newTosAccepted !== Boolean(user.tosAccepted)) {
              setUser(u => ({
                ...u,
                verified: newVerified || u.verified,
                tosAccepted: newTosAccepted,
              }));
            }
          }
        })
        .catch(err => console.error('❌ me.php:', err));
    };
    const t = setInterval(checkVerification, 60000);
    return () => clearInterval(t);
  }, [user.verified, user.tosAccepted, setUser]);

  const handleLogout=()=>{
    fetch(API+'logout.php',{method:'POST',credentials:'include'})
    .finally(()=>{setUser({role:'university'});onNavigate('/signup');});
  };

  const isVerified = user.verified==='verified';

  if (loading) {
    return <div className="up-skeleton" style={{height:80,borderRadius:12}}/>;
  }

  if (error) {
    return <div className="up-card" style={{color:'#fca5a5'}}>{error}</div>;
  }

  return (
    <UpToastProvider>
      {tosShown && (
        <TosModal
          onAccept={() => {
            setUser(u => ({ ...u, tosAccepted: true }));
            setTosShown(false);
          }}
        />
      )}
      <div id="up-root">

        {/* TOPBAR */}
        <header className="up-topbar">
          <div className="up-logo">Better<span>Abroad</span></div>
          <div className="up-topbar-right">
            <button className="up-notif-btn" onClick={()=>setActiveTab('messages')}>
              <i className="fa-solid fa-bell"/>
              {unread>0&&<div className="up-notif-dot"/>}
            </button>
            <div className="up-avatar-btn">{abbr(user.uniName)}</div>
          </div>
        </header>

        {/* SIDEBAR */}
        <aside className="up-sidebar">
          <div className="up-sidebar-card">
            <div className="up-uni-logo">{abbr(user.uniName)}</div>
            <div className="up-sidebar-name">{user.uniName||'Your University'}</div>
            <div className="up-sidebar-email">{user.email}</div>
            <div style={{marginTop:4}}>
              <span className={`up-badge-verified ${isVerified?'':'up-badge-pending'}`}>
                <i className={`fa-solid ${isVerified?'fa-circle-check':'fa-clock'}`}/>
                {isVerified?'Verified Partner':'Pending Verification'}
              </span>
            </div>
          </div>

          <div className="up-nav-section-label">Dashboard</div>
          {UP_TABS.slice(0,4).map(t=>(
            <div key={t.id} className={`up-nav-item ${activeTab===t.id?'active':''}`}
              onClick={()=>setActiveTab(t.id)}>
              <i className={`fa-solid ${t.icon}`}/>{t.label}
              {t.id==='messages'&&unread>0&&<span className="up-nav-badge">{unread}</span>}
            </div>
          ))}

          <div className="up-nav-section-label">Account</div>
          {UP_TABS.slice(4).map(t=>(
            <div key={t.id} className={`up-nav-item ${activeTab===t.id?'active':''}`}
              onClick={()=>setActiveTab(t.id)}>
              <i className={`fa-solid ${t.icon}`}/>{t.label}
            </div>
          ))}

          <div className="up-nav-section-label">Discover</div>
          <div className="up-nav-item" onClick={()=>onNavigate('/marketplace')}>
            <i className="fa-solid fa-store"/> Student Marketplace
          </div>

          <div className="up-sidebar-bottom">
            <button className="up-logout-btn" onClick={handleLogout}>
              <i className="fa-solid fa-right-from-bracket"/> Sign Out
            </button>
          </div>
        </aside>

        {/* MAIN */}
        <main className="up-main">
          <div className="up-page-header up-fade-up">
            <div>
              <h1 className="up-page-title">
                {activeTab==='overview'  ?'Institution Dashboard':
                 activeTab==='applicants'?'Applicants':
                 activeTab==='seminars'  ?'Seminars':
                 activeTab==='documents' ?'Documents':
                 activeTab==='messages'  ?'Messages':'Settings'}
              </h1>
              <p className="up-page-subtitle">
                {activeTab==='overview'  ?`Welcome, ${user.uniName||'Partner Institution'}`:
                 activeTab==='applicants'?'Review and manage your student pipeline':
                 activeTab==='seminars'  ?'Host virtual recruitment sessions with verified students':
                 activeTab==='documents' ?'Upload and manage institution documents':
                 activeTab==='messages'  ?'Communicate with prospective students':'Manage your account'}
              </p>
            </div>
            <div className="up-header-actions">
              {activeTab==='applicants'&&(
                <button className="up-btn up-btn-ghost" onClick={()=>onNavigate('/marketplace')}>
                  <i className="fa-solid fa-store"/> Browse Students
                </button>
              )}
            </div>
          </div>

          {activeTab==='overview'   &&<UpOverviewTab   user={user} setUser={setUser}/>}
          {activeTab==='applicants' &&<ApplicantsTab   user={user} onSendMessage={(student)=>{
            setMessageTarget(student);
            setActiveTab('messages');
          }}/>}
          {activeTab==='seminars'   &&<UpSeminarsTab   user={user}/>}
          {activeTab==='documents'  &&<UpDocumentsTab  docs={docs} onUpload={(k,d)=>{
            setDocs(prev=>[...prev.filter(x=>x.doc_type!==k),{doc_type:k,file_name:d.file_name,status:'pending'}]);
          }}/>}
          {activeTab==='messages'   &&<UpMessagesTab   user={user} messageTarget={messageTarget}/>}
          {activeTab==='settings'   &&<UpSettingsTab   user={user} onLogout={handleLogout}/>}
        </main>

      </div>
    </UpToastProvider>
  );
};

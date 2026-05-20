/* ═══════════════════════════════════════════════════════════
   BetterAbroad — StudentProfile React Component
   Loaded by market.html via: <script src="student-profile.js" type="text/babel">
   Uses React 18 UMD + Babel standalone (no bundler)
   API base: ../../
   ═══════════════════════════════════════════════════════════ */

'use strict';

const { useState, useEffect, useRef, useCallback } = React;

/* ── CONSTANTS ─────────────────────────────────────── */
const API = '../../';
const TABS = [
  { id:'overview',    icon:'fa-user',         label:'Overview' },
  { id:'documents',   icon:'fa-folder-open',  label:'Documents' },
  { id:'applications',icon:'fa-list-check',   label:'Applications' },
  { id:'seminars',    icon:'fa-chalkboard-teacher', label:'Seminars' },
  { id:'messages',    icon:'fa-comment-dots', label:'Messages' },
  { id:'settings',    icon:'fa-gear',         label:'Settings' },
];

const DOC_SLOTS = [
  { key:'transcript',           label:'Academic Transcript',      icon:'fa-file-lines',   accept:'.pdf,.jpg,.png' },
  { key:'passport',             label:'Passport / National ID',   icon:'fa-passport',     accept:'.pdf,.jpg,.png' },
  { key:'recommendation',       label:'Recommendation Letter',    icon:'fa-envelope-open-text', accept:'.pdf' },
  { key:'personal_statement',   label:'Personal Statement',       icon:'fa-file-pen',     accept:'.pdf' },
  { key:'transcript_official',  label:'Official Transcript',      icon:'fa-file-circle-check',  accept:'.pdf' },
  { key:'financial_proof',      label:'Financial Proof',          icon:'fa-file-invoice-dollar', accept:'.pdf,.jpg,.png' },
  { key:'language_cert',        label:'Language Certificate',     icon:'fa-language',     accept:'.pdf,.jpg,.png' },
  { key:'awards',               label:'Awards & Honours',         icon:'fa-award',        accept:'.pdf,.jpg,.png' },
];

const STATUS_MAP = {
  new:       { label:'New',       cls:'sp-status-new' },
  review:    { label:'In Review', cls:'sp-status-review' },
  interview: { label:'Interview', cls:'sp-status-interview' },
  offer:     { label:'Offer',     cls:'sp-status-offer' },
  enrolled:  { label:'Enrolled',  cls:'sp-status-enrolled' },
  rejected:  { label:'Rejected',  cls:'sp-status-rejected' },
};

/* ── HELPERS ───────────────────────────────────────── */
const initials = (name='') => name.split(' ').map(n=>n[0]).join('').slice(0,2).toUpperCase() || 'BA';
const timeAgo  = (dt) => {
  if (!dt) return '';
  const d = Math.floor((Date.now() - new Date(dt)) / 1000);
  if (d < 60)    return 'just now';
  if (d < 3600)  return `${Math.floor(d/60)}m ago`;
  if (d < 86400) return `${Math.floor(d/3600)}h ago`;
  return `${Math.floor(d/86400)}d ago`;
};
const fmtDate = (dt) => dt ? new Date(dt).toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'}) : '—';
const countryFlag = (country='') => ({
  Canada: '🇨🇦',
  'United Kingdom': '🇬🇧',
  UK: '🇬🇧',
  USA: '🇺🇸',
  'United States': '🇺🇸',
  Australia: '🇦🇺',
  Germany: '🇩🇪',
  France: '🇫🇷',
  Ireland: '🇮🇪',
  Netherlands: '🇳🇱',
}[country] || '🌍');
const calcCompletion = (u, docs) => {
  const fields = [u.fullName || u.full_name, u.nationality, u.major, u.gpa, u.budget, u.intake];
  const filled = fields.filter(Boolean).length;
  const docCount = (docs || []).length;
  const fieldPct = Math.round((filled / fields.length) * 60);
  const docPct = Math.min(docCount * 10, 30);
  const videoPct = docs?.some(d => d.doc_type === 'intro_video') ? 10 : 0;
  return Math.min(fieldPct + docPct + videoPct, 100);
};

/* ── TOAST SYSTEM ──────────────────────────────────── */
const ToastContext = React.createContext(null);
const ToastProvider = ({ children }) => {
  const [toasts, setToasts] = useState([]);
  const fire = useCallback((msg, type='info') => {
    const id = Date.now();
    setToasts(t => [...t, { id, msg, type }]);
    setTimeout(() => setToasts(t => t.filter(x => x.id !== id)), 3800);
  }, []);
  const icon = { success:'fa-circle-check', error:'fa-circle-xmark', info:'fa-circle-info' };
  const col  = { success:'#22c55e',         error:'#ef4444',          info:'#3b82f6' };
  return (
    <ToastContext.Provider value={fire}>
      {children}
      <div className="sp-toast-container">
        {toasts.map(t => (
          <div key={t.id} className={`sp-toast sp-toast-${t.type}`}>
            <i className={`fa-solid ${icon[t.type]} sp-toast-icon`} style={{color:col[t.type]}}/>
            {t.msg}
          </div>
        ))}
      </div>
    </ToastContext.Provider>
  );
};
const useToast = () => React.useContext(ToastContext);

/* ── COMPLETION RING ───────────────────────────────── */
const CompletionRing = ({ pct=0 }) => {
  const r = 32, circ = 2 * Math.PI * r;
  const dash = circ - (pct / 100) * circ;
  return (
    <div className="sp-completion">
      <svg width="80" height="80" viewBox="0 0 80 80">
        <circle cx="40" cy="40" r={r} fill="none" stroke="rgba(255,255,255,0.06)" strokeWidth="6"/>
        <circle cx="40" cy="40" r={r} fill="none"
          stroke="url(#ringGrad)" strokeWidth="6"
          strokeDasharray={circ} strokeDashoffset={dash}
          strokeLinecap="round" style={{transition:'stroke-dashoffset 0.6s ease'}}/>
        <defs>
          <linearGradient id="ringGrad" x1="0%" y1="0%" x2="100%" y2="0%">
            <stop offset="0%" stopColor="#1a56db"/>
            <stop offset="100%" stopColor="#06b6d4"/>
          </linearGradient>
        </defs>
      </svg>
      <div className="sp-completion-text">
        <div className="sp-completion-pct">{pct}%</div>
        <div className="sp-completion-lbl">Done</div>
      </div>
    </div>
  );
};

/* ── INFO TILE ─────────────────────────────────────── */
const InfoTile = ({ label, value, icon, editing, inputType='text', onChange }) => (
  <div className="sp-info-tile">
    <div className="sp-info-tile-label">
      <i className={`fa-solid ${icon}`}/> {label}
    </div>
    {editing
      ? <input className="sp-input" style={{padding:'6px 10px',fontSize:13,marginTop:2}}
          type={inputType} defaultValue={value||''}
          onChange={e => onChange && onChange(e.target.value)}/>
      : <div className="sp-info-tile-value">{value || <span style={{color:'var(--text-faint)'}}>—</span>}</div>
    }
  </div>
);

/* ── DOC UPLOAD SLOT ───────────────────────────────── */
const DocSlot = ({ slot, uploaded, onUpload }) => {
  const toast  = useToast();
  const ref    = useRef();
  const [state, setState] = useState('idle'); // idle | uploading | done | error
  const [fname, setFname] = useState(uploaded?.file_name || null);
  const [prog,  setProg]  = useState(0);

  const handleFile = (file) => {
    if (!file) return;
    if (file.size > 5 * 1024 * 1024) { toast('File too large. Max 5 MB.', 'error'); return; }
    const allowed = ['application/pdf','image/jpeg','image/png','image/jpg'];
    if (!allowed.includes(file.type)) { toast('Only PDF, JPG, PNG allowed.', 'error'); return; }

    setState('uploading'); setProg(0);
    const fd = new FormData();
    fd.append('file', file);
    fd.append('doc_type', slot.key);

    const xhr = new XMLHttpRequest();
    xhr.upload.onprogress = e => { if (e.lengthComputable) setProg(Math.round(e.loaded/e.total*100)); };
    xhr.onload = () => {
      try {
        const data = JSON.parse(xhr.responseText);
        if (data.success) {
          setState('done');
          setFname(data.file_name);
          toast(`${slot.label} uploaded.`, 'success');
          onUpload && onUpload(slot.key, data);
        } else {
          setState('error');
          toast(data.error || 'Upload failed.', 'error');
        }
      } catch { setState('error'); toast('Upload failed.', 'error'); }
    };
    xhr.onerror = () => { setState('error'); toast('Upload failed.', 'error'); };
    xhr.open('POST', API + 'upload.php');
    xhr.withCredentials = true;
    xhr.send(fd);
  };

  const onDrop = (e) => { e.preventDefault(); handleFile(e.dataTransfer.files[0]); };

  const zoneClass = `sp-upload-zone${state==='done'?' uploaded':state==='uploading'?' uploading':''}`;
  const iconBg = state==='done'
    ? 'var(--green-dim)' : state==='error'
    ? 'var(--red-dim)'  : 'rgba(26,86,219,0.1)';
  const iconColor = state==='done' ? '#22c55e' : state==='error' ? '#ef4444' : '#3b82f6';
  const iconName  = state==='done' ? 'fa-circle-check' : state==='error' ? 'fa-circle-xmark' : slot.icon;

  return (
    <div className={zoneClass}
      onClick={() => state!=='uploading' && ref.current?.click()}
      onDragOver={e=>{e.preventDefault();}}
      onDrop={onDrop}>
      <input type="file" ref={ref} accept={slot.accept} style={{display:'none'}}
        onChange={e => handleFile(e.target.files[0])}/>
      <div className="sp-upload-icon" style={{background:iconBg}}>
        <i className={`fa-solid ${iconName}`} style={{color:iconColor}}/>
      </div>
      <div className="sp-upload-label">
        {state==='done' ? fname : state==='uploading' ? 'Uploading...' : state==='error' ? 'Try again' : slot.label}
      </div>
      <div className="sp-upload-hint">
        {state==='idle' ? 'PDF, JPG or PNG · max 5 MB' :
         state==='done' ? <span style={{color:'var(--green-dim)'}}>Submitted for review</span> : ''}
      </div>
      {state==='uploading' && (
        <div className="sp-progress-bar" style={{marginTop:12}}>
          <div className="sp-progress-fill" style={{width:prog+'%'}}/>
        </div>
      )}
    </div>
  );
};

/* ── VIDEO UPLOAD ──────────────────────────────────── */
const VideoUpload = ({ onUpload }) => {
  const toast = useToast();
  const ref   = useRef();
  const [videoSrc, setVideoSrc] = useState(null);
  const [uploading, setUploading] = useState(false);
  const [title, setTitle] = useState('');
  const [desc,  setDesc]  = useState('');

  const handleFile = (file) => {
    if (!file) return;
    if (!file.type.startsWith('video/')) { toast('Please upload a video file.', 'error'); return; }
    if (file.size > 150 * 1024 * 1024)  { toast('Video too large. Max 150 MB.', 'error'); return; }

    // Duration check via HTMLVideoElement
    const url = URL.createObjectURL(file);
    const vid  = document.createElement('video');
    vid.preload = 'metadata';
    vid.onloadedmetadata = () => {
      if (vid.duration > 125) { toast('Video must be 2 minutes max.', 'error'); URL.revokeObjectURL(url); return; }
      setVideoSrc(url);
      uploadVideo(file);
    };
    vid.src = url;
  };

  const uploadVideo = (file) => {
    setUploading(true);
    const fd = new FormData();
    fd.append('file', file);
    fd.append('doc_type', 'intro_video');
    fetch(API + 'upload.php', { method:'POST', credentials:'include', body:fd })
      .then(r => r.json())
      .then(data => {
        if (data.success) { toast('Intro video uploaded!', 'success'); onUpload && onUpload('intro_video', data); }
        else toast(data.error || 'Upload failed.', 'error');
      })
      .catch(() => toast('Upload failed.', 'error'))
      .finally(() => setUploading(false));
  };

  return (
    <div style={{display:'grid', gridTemplateColumns:'1fr 1fr', gap:20, alignItems:'start'}}>
      <div>
        <h4 style={{fontFamily:"'Sora',sans-serif",fontWeight:700,fontSize:15,marginBottom:8}}>
          2-Minute Introduction Video
        </h4>
        <p style={{color:'var(--text-mid)',fontSize:13,lineHeight:1.7,marginBottom:16}}>
          Record yourself explaining your academic goals and why you want to study abroad.
          Universities see a real person — not just numbers. <strong style={{color:'var(--teal)'}}>Max 2 minutes.</strong>
        </p>
        <div style={{display:'flex',flexDirection:'column',gap:10,marginBottom:16}}>
          <input className="sp-input" placeholder="Video title (e.g. 'Why I want to study Computer Science abroad')"
            value={title} onChange={e=>setTitle(e.target.value)}/>
          <input className="sp-input" placeholder="Short description (optional)"
            value={desc} onChange={e=>setDesc(e.target.value)}/>
        </div>
        <div style={{display:'flex',gap:10}}>
          <button className="sp-btn sp-btn-primary" onClick={()=>ref.current?.click()} disabled={uploading}>
            <i className="fa-solid fa-upload"/> {uploading ? 'Uploading...' : 'Upload Video'}
          </button>
        </div>
        <input type="file" ref={ref} accept="video/*" style={{display:'none'}}
          onChange={e=>handleFile(e.target.files[0])}/>
      </div>
      <div className="sp-video-box" onClick={()=>ref.current?.click()}>
        {videoSrc
          ? <><video src={videoSrc} controls style={{width:'100%',height:'100%',objectFit:'cover'}}/></>
          : <>
              <div className="sp-video-play-btn"><i className="fa-solid fa-play"/></div>
              <div style={{fontSize:13,color:'var(--text-mid)'}}>Click to upload</div>
              <div style={{fontSize:11,color:'var(--text-lo)'}}>MP4, MOV, WebM · max 2 min</div>
            </>
        }
      </div>
    </div>
  );
};

/* ── DESCRIPTION WITH AI CLEAN ─────────────────────── */
const DescriptionEditor = ({ initial='', userId }) => {
  const toast = useToast();
  const [raw,     setRaw]     = useState(initial);
  const [saving,  setSaving]  = useState(false);
  const [cleaning,setCleaning]= useState(false);
  const [aiPreview, setAiPreview] = useState(null);

  const save = () => {
    setSaving(true);
    fetch(API + 'save.php', {
      method:'POST', credentials:'include',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ description: raw })
    })
    .then(r=>r.json())
    .then(d => { if(d.success) toast('Description saved.','success'); else toast(d.error||'Save failed.','error'); })
    .catch(()=>toast('Save failed.','error'))
    .finally(()=>setSaving(false));
  };

  const cleanWithAI = () => {
    if (!raw.trim()) { toast('Write something first.','error'); return; }
    setCleaning(true);
    fetch(API + 'clean_description.php', {
      method:'POST', credentials:'include',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ raw_description: raw })
    })
    .then(r=>r.json())
    .then(d => {
      if(d.success) { setAiPreview(d.ai_description); toast('AI version ready. Review before saving.','info'); }
      else toast(d.error||'AI polish failed.','error');
    })
    .catch(()=>toast('AI polish failed.','error'))
    .finally(()=>setCleaning(false));
  };

  return (
    <div style={{display:'flex',flexDirection:'column',gap:14}}>
      <textarea className="sp-textarea" rows={5}
        placeholder="Write about your academic goals, achievements, and why you want to study abroad. Be honest and personal — our AI will help polish it."
        value={raw} onChange={e=>setRaw(e.target.value)}/>
      {aiPreview && (
        <div style={{padding:16,borderRadius:12,
          background:'rgba(6,182,212,0.05)',border:'1px solid rgba(6,182,212,0.2)'}}>
          <div style={{fontSize:11,fontWeight:700,color:'var(--teal)',textTransform:'uppercase',
            letterSpacing:'0.7px',marginBottom:8}}>AI-Polished Version (what universities see)</div>
          <p style={{fontSize:13,color:'var(--text-mid)',lineHeight:1.8}}>{aiPreview}</p>
          <button className="sp-btn sp-btn-teal" style={{marginTop:12,fontSize:12}}
            onClick={()=>{ setRaw(aiPreview); setAiPreview(null); }}>
            <i className="fa-solid fa-check"/> Use This Version
          </button>
        </div>
      )}
      <div style={{display:'flex',justifyContent:'flex-end',gap:10}}>
        <button className="sp-btn sp-btn-ghost" onClick={cleanWithAI} disabled={cleaning}>
          <i className={`fa-solid ${cleaning?'fa-spinner fa-spin':'fa-wand-magic-sparkles'}`}/>
          {cleaning ? 'Polishing...' : 'AI Polish'}
        </button>
        <button className="sp-btn sp-btn-primary" onClick={save} disabled={saving}>
          <i className={`fa-solid ${saving?'fa-spinner fa-spin':'fa-floppy-disk'}`}/>
          {saving ? 'Saving...' : 'Save'}
        </button>
      </div>
    </div>
  );
};

/* ── OVERVIEW TAB ──────────────────────────────────── */
const OverviewTab = ({ user, setUser, docs, onVideoUploaded }) => {
  const toast   = useToast();
  const [editing, setEditing] = useState(false);
  const [form,    setForm]    = useState({ ...user });
  const [saving,  setSaving]  = useState(false);

  const setField = (camelKey, snakeKey, value) =>
    setForm(f => ({ ...f, [camelKey]: value, [snakeKey]: value }));

  const save = () => {
    setSaving(true);
    fetch(API + 'save.php', {
      method:'POST', credentials:'include',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify(form)
    })
    .then(r=>r.json())
    .then(d => {
      if(d.success) {
        setUser(u => {
          const nextUser = {
            ...u,
            ...form,
            fullName: form.full_name || form.fullName || u.fullName,
            nationality: form.nationality,
            major: form.major,
            gpa: form.gpa,
            budget: form.budget,
            intake: form.intake,
          };
          return {
            ...nextUser,
            completionPct: calcCompletion(nextUser, docs),
          };
        });
        setEditing(false);
        toast('Profile saved.','success');
      } else toast(d.error||'Save failed.','error');
    })
    .catch(()=>toast('Save failed.','error'))
    .finally(()=>setSaving(false));
  };

  return (
    <div style={{display:'flex',flexDirection:'column',gap:20}}>

      {/* STAT STRIP */}
      <div className="sp-stats sp-fade-up">
        <div className="sp-stat-box">
          <div className="sp-stat-value">{user.gpa||'—'}</div>
          <div className="sp-stat-label">GPA / 5.0</div>
        </div>
        <div className="sp-stat-box">
          <div className="sp-stat-value">{user.completionPct||0}%</div>
          <div className="sp-stat-label">Profile Complete</div>
        </div>
        <div className="sp-stat-box">
          <div className="sp-stat-value">{user.docCount||0}</div>
          <div className="sp-stat-label">Docs Uploaded</div>
        </div>
        <div className="sp-stat-box">
          <div className="sp-stat-value" style={{fontSize:18}}>{user.verified==='verified'?'✓ Yes':'Pending'}</div>
          <div className="sp-stat-label">Verified</div>
        </div>
      </div>

      {/* PERSONAL INFO */}
      <div className="sp-card sp-fade-up sp-fade-up-1">
        <div style={{display:'flex',alignItems:'center',justifyContent:'space-between',marginBottom:16}}>
          <div className="sp-pill"><i className="fa-solid fa-id-card"/> Personal Info</div>
          <button className="sp-btn sp-btn-ghost" style={{padding:'7px 14px',fontSize:12}}
            onClick={() => editing ? save() : setEditing(true)} disabled={saving}>
            <i className={`fa-solid ${saving?'fa-spinner fa-spin':editing?'fa-check':'fa-pen'}`}/>
            {saving ? 'Saving...' : editing ? 'Save Changes' : 'Edit'}
          </button>
        </div>
        <div className="sp-info-grid">
          <InfoTile label="Full Name"    value={form.full_name || form.fullName} icon="fa-user"   editing={editing} onChange={v=>setField('fullName','full_name',v)}/>
          <InfoTile label="Nationality"  value={form.nationality} icon="fa-globe"         editing={editing} onChange={v=>setField('nationality','nationality',v)}/>
          <InfoTile label="Major / Field" value={form.major}      icon="fa-book"          editing={editing} onChange={v=>setField('major','major',v)}/>
          <InfoTile label="GPA"          value={form.gpa}         icon="fa-star"          editing={editing} inputType="number" onChange={v=>setField('gpa','gpa',v)}/>
          <InfoTile label="Annual Budget" value={form.budget}     icon="fa-wallet"        editing={editing} onChange={v=>setField('budget','budget',v)}/>
          <InfoTile label="Target Intake" value={form.intake}     icon="fa-calendar"      editing={editing} onChange={v=>setField('intake','intake',v)}/>
        </div>
      </div>

      {/* VIDEO */}
      <div className="sp-card sp-fade-up sp-fade-up-2">
        <div className="sp-pill"><i className="fa-solid fa-video"/> Intro Video</div>
        <VideoUpload onUpload={(docType, data)=>{
          onVideoUploaded && onVideoUploaded(docType, data);
          toast('Video saved.','success');
        }}/>
      </div>

      {/* DESCRIPTION */}
      <div className="sp-card sp-fade-up sp-fade-up-3">
        <div className="sp-pill"><i className="fa-solid fa-pen-to-square"/> About Me</div>
        <DescriptionEditor initial={user.description||''} userId={user.userId}/>
      </div>

    </div>
  );
};

/* ── DOCUMENTS TAB ─────────────────────────────────── */
const DocumentsTab = ({ user, docs, onDocUploaded }) => {
  const toast = useToast();
  const uploadedMap = {};
  (docs||[]).forEach(d => { uploadedMap[d.doc_type] = d; });

  const downloadDossier = () => {
    toast('Generating your dossier PDF...', 'info');
    window.open(API + 'dossier.php', '_blank');
    fetch(API + 'dossier.php?action=log_download', {
      method: 'POST',
      credentials: 'include',
    }).catch(() => {});
  };

  return (
    <div style={{display:'flex',flexDirection:'column',gap:20}}>
      <div className="sp-card sp-fade-up">
        <div className="sp-pill"><i className="fa-solid fa-folder-open"/> Your Documents</div>
        <p style={{fontSize:13,color:'var(--text-mid)',marginBottom:20,lineHeight:1.7}}>
          Upload all required documents. Once verified by BetterAbroad admin,
          your profile will be visible to universities on the marketplace.
        </p>
        <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:16}}>
          {DOC_SLOTS.map(slot => (
            <DocSlot key={slot.key} slot={slot}
              uploaded={uploadedMap[slot.key]}
              onUpload={onDocUploaded}/>
          ))}
        </div>
      </div>

      <div className="sp-card sp-fade-up sp-fade-up-1"
        style={{background:'linear-gradient(135deg,rgba(6,182,212,0.06),rgba(26,86,219,0.04))',
          border:'1px solid rgba(6,182,212,0.15)'}}>
        <div style={{display:'flex',alignItems:'center',gap:16}}>
          <div style={{width:44,height:44,borderRadius:12,flexShrink:0,
            background:'rgba(6,182,212,0.15)',display:'flex',alignItems:'center',justifyContent:'center'}}>
            <i className="fa-solid fa-file-arrow-down" style={{color:'var(--teal)',fontSize:18}}/>
          </div>
          <div style={{flex:1}}>
            <div style={{fontFamily:"'Sora',sans-serif",fontWeight:700,fontSize:14,marginBottom:3}}>
              Download Your Dossier
            </div>
            <div style={{fontSize:12,color:'var(--text-mid)'}}>
              Get your auto-generated application bundle — ready to submit to any university.
            </div>
          </div>
          <button
            type="button"
            onClick={downloadDossier}
            className="sp-btn sp-btn-teal"
            style={{textDecoration:'none'}}>
            <i className="fa-solid fa-download"/> Download PDF
          </button>
        </div>
      </div>
    </div>
  );
};

/* ── APPLICATIONS TAB ──────────────────────────────── */
const ApplicationsTab = () => {
  const toast = useToast();
  const [apps, setApps] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    fetch(API + 'applications.php', { credentials:'include' })
      .then(r => {
        if (!r.ok) {
          console.error('❌ applications.php:', r.status);
          setError('Could not load data. Please refresh.');
          return null;
        }
        return r.json();
      })
      .then(d => {
        if (d && d.success) {
          setApps(d.applications||[]);
          setError('');
        }
      })
      .catch(err => {
        console.error('❌ applications.php:', err);
        setError('Could not load data. Please refresh.');
      })
      .finally(()=>setLoading(false));
  }, []);

  const reportEnrollment = (app) => {
    fetch(API + 'enrollments.php', {
      method:'POST',
      credentials:'include',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ university_id: app.university_id })
    })
    .then(r => r.json())
    .then(d => {
      if (d.success) {
        setApps(prev => prev.map(x => x.id === app.id ? { ...x, status:'enrolled' } : x));
        toast('Enrollment reported. Commission invoice issued.', 'success');
      } else {
        toast(d.error || 'Enrollment report failed.', 'error');
      }
    })
    .catch(err => {
      console.error('❌ enrollments.php:', err);
      toast('Enrollment report failed.', 'error');
    });
  };

  if (loading) return (
    <div style={{display:'flex',flexDirection:'column',gap:12}}>
      {[1,2,3].map(i=>(
        <div key={i} className="sp-skeleton" style={{height:80,borderRadius:12}}/>
      ))}
    </div>
  );

  if (error) return <div className="sp-card" style={{color:'#fca5a5'}}>{error}</div>;

  return (
    <div className="sp-card sp-fade-up">
      <div className="sp-pill"><i className="fa-solid fa-list-check"/> My Applications</div>
      {apps.length === 0 ? (
        <div style={{textAlign:'center',padding:'40px 20px'}}>
          <i className="fa-solid fa-inbox" style={{fontSize:36,color:'var(--text-lo)',marginBottom:16}}/>
          <h4 style={{fontWeight:600,marginBottom:6}}>No applications yet</h4>
          <p style={{color:'var(--text-mid)',fontSize:13}}>
            Browse the marketplace to discover and apply to universities.
          </p>
        </div>
      ) : (
        <div style={{display:'flex',flexDirection:'column',gap:12}}>
          {apps.map((app) => {
            const st = STATUS_MAP[app.status] || STATUS_MAP.new;
            return (
              <div key={app.id} className="sp-app-card" style={{alignItems:'stretch'}}>
                <div className="sp-app-uni-logo">
                  {(app.uni_name||'?').slice(0,3).toUpperCase()}
                </div>
                <div className="sp-app-info">
                  <div className="sp-app-name">
                    {app.country && <span style={{marginRight:8}}>{countryFlag(app.country)}</span>}
                    {app.uni_name || `University #${app.university_id}`}
                  </div>
                  <div className="sp-app-meta" style={{marginBottom:8}}>
                    <i className="fa-solid fa-book" style={{marginRight:4}}/>
                    {app.major || app.program || 'Programme pending'}
                  </div>
                  <div className="sp-app-meta">
                    <i className="fa-solid fa-clock" style={{marginRight:4}}/>
                    Applied {fmtDate(app.applied_at || app.created_at)}
                  </div>
                  <div style={{display:'flex',gap:8,marginTop:12,flexWrap:'wrap'}}>
                    <button className="sp-btn sp-btn-ghost" style={{fontSize:12,padding:'8px 12px'}}
                      onClick={() => toast('Application details view coming soon.', 'info')}>
                      <i className="fa-solid fa-eye"/> View Details
                    </button>
                    {app.status === 'offer' && (
                      <button className="sp-btn sp-btn-teal" style={{fontSize:12,padding:'8px 12px'}}
                        onClick={() => reportEnrollment(app)}>
                        <i className="fa-solid fa-graduation-cap"/> Report Enrolled
                      </button>
                    )}
                  </div>
                </div>
                <div style={{display:'flex',alignItems:'flex-start'}}>
                  <span className={`sp-status ${st.cls}`}>{st.label}</span>
                </div>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
};

/* ── SEMINARS TAB ──────────────────────────────────── */
const SeminarsTab = ({ user }) => {
  const toast = useToast();
  const [seminars, setSeminars] = useState([]);
  const [loading,  setLoading]  = useState(true);
  const [regSet,   setRegSet]   = useState(new Set());

  useEffect(() => {
    fetch(API + 'seminars.php?action=list', { credentials:'include' })
      .then(r=>r.json())
      .then(d => {
        if(d.success) {
          setSeminars(d.seminars||[]);
          setRegSet(new Set((d.seminars||[]).filter(s=>s.is_registered).map(s=>s.id)));
        }
      })
      .catch(()=>{})
      .finally(()=>setLoading(false));
  }, []);

  const register = (id) => {
    fetch(API + 'seminars.php', {
      method:'POST', credentials:'include',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ action:'register', seminar_id: id })
    })
    .then(r=>r.json())
    .then(d => {
      if(d.success) {
        setRegSet(s => new Set([...s, id]));
        toast('Registered for seminar!','success');
      } else toast(d.error||'Registration failed.','error');
    })
    .catch(()=>toast('Registration failed.','error'));
  };

  if (loading) return (
    <div style={{display:'flex',flexDirection:'column',gap:16}}>
      {[1,2].map(i=><div key={i} className="sp-skeleton" style={{height:140,borderRadius:16}}/>)}
    </div>
  );

  return (
    <div style={{display:'flex',flexDirection:'column',gap:16}}>
      {seminars.length===0 ? (
        <div className="sp-card" style={{textAlign:'center',padding:'48px 20px'}}>
          <i className="fa-solid fa-chalkboard-teacher"
            style={{fontSize:36,color:'var(--text-lo)',marginBottom:16}}/>
          <h4 style={{fontWeight:600,marginBottom:6}}>No seminars scheduled yet</h4>
          <p style={{color:'var(--text-mid)',fontSize:13}}>Check back soon — sessions are announced weekly.</p>
        </div>
      ) : seminars.map(s => {
        const pct = Math.round((s.registered_count / s.max_participants) * 100);
        const isReg = regSet.has(s.id);
        return (
          <div key={s.id} className="sp-seminar-card sp-fade-up">
            <div className="sp-seminar-date">
              <i className="fa-solid fa-calendar" style={{marginRight:6}}/>
              {new Date(s.scheduled_at).toLocaleDateString('en-GB',
                {weekday:'short',day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'})}
            </div>
            <div className="sp-seminar-title">{s.title}</div>
            <div className="sp-seminar-uni">
              <i className="fa-solid fa-building-columns" style={{marginRight:5}}/>
              {s.university_name || 'Partner University'}
              <span style={{color:'var(--text-lo)',marginLeft:10}}>
                · {s.tier?.charAt(0).toUpperCase()+s.tier?.slice(1)} Seminar
              </span>
            </div>
            {s.target_majors && (
              <div className="sp-seminar-tags">
                {s.target_majors.split(',').map((m,i)=>(
                  <span key={i} className="sp-tag">{m.trim()}</span>
                ))}
              </div>
            )}
            <div style={{marginBottom:12}}>
              <div style={{display:'flex',justifyContent:'space-between',
                fontSize:11,color:'var(--text-lo)',marginBottom:6}}>
                <span>{s.registered_count} / {s.max_participants} registered</span>
                <span>{pct}%</span>
              </div>
              <div className="sp-progress-bar">
                <div className="sp-progress-fill" style={{width:pct+'%'}}/>
              </div>
            </div>
            <div style={{display:'flex',alignItems:'center',justifyContent:'space-between'}}>
              <span style={{fontSize:12,color:'var(--text-lo)'}}>
                <i className="fa-solid fa-tag" style={{marginRight:5}}/>
                Free to attend
              </span>
              {isReg
                ? <span className="sp-btn sp-btn-teal" style={{fontSize:12,pointerEvents:'none'}}>
                    <i className="fa-solid fa-check"/> Registered
                  </span>
                : <button className="sp-btn sp-btn-primary" style={{fontSize:12}}
                    onClick={()=>register(s.id)}
                    disabled={s.registered_count>=s.max_participants}>
                    {s.registered_count>=s.max_participants ? 'Full' : 'Register — Free'}
                  </button>
              }
            </div>
          </div>
        );
      })}
    </div>
  );
};

/* ── MESSAGES TAB ──────────────────────────────────── */
const MessagesTab = ({ user }) => {
  const toast = useToast();
  const [convos,    setConvos]    = useState([]);
  const [active,    setActive]    = useState(null);
  const [thread,    setThread]    = useState([]);
  const [body,      setBody]      = useState('');
  const [sending,   setSending]   = useState(false);
  const [loading,   setLoading]   = useState(true);
  const bottomRef = useRef();

  useEffect(() => {
    fetch(API + 'conversations.php', { credentials:'include' })
      .then(r=>r.json())
      .then(d => { if(d.success) setConvos(d.conversations||[]); })
      .catch(()=>{})
      .finally(()=>setLoading(false));
  }, []);

  useEffect(() => {
    if (!active) return;
    fetch(`${API}thread.php?with=${active.contact_id}`, { credentials:'include' })
      .then(r=>r.json())
      .then(d => { if(d.success) setThread(d.messages||[]); })
      .catch(()=>{});
    const t = setInterval(() => {
      fetch(`${API}thread.php?with=${active.contact_id}`, { credentials:'include' })
        .then(r=>r.json()).then(d=>{ if(d.success) setThread(d.messages||[]); }).catch(()=>{});
    }, 10000);
    return () => clearInterval(t);
  }, [active]);

  useEffect(() => {
    bottomRef.current?.scrollIntoView({ behavior:'smooth' });
  }, [thread]);

  const send = () => {
    if (!body.trim() || !active) return;
    setSending(true);
    fetch(API + 'send.php', {
      method:'POST', credentials:'include',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ to_user_id: active.contact_id, body: body.trim() })
    })
    .then(r=>r.json())
    .then(d => {
      if(d.success) {
        setThread(t=>[...t,{from_user_id:user.userId,body:body.trim(),created_at:d.created_at,is_read:0}]);
        setBody('');
      } else toast(d.error||'Send failed.','error');
    })
    .catch(()=>toast('Send failed.','error'))
    .finally(()=>setSending(false));
  };

  return (
    <div style={{display:'grid',gridTemplateColumns:'280px 1fr',gap:16,height:'calc(100vh - 280px)',minHeight:400}}>
      {/* Conversation list */}
      <div className="sp-card" style={{padding:0,overflow:'hidden',display:'flex',flexDirection:'column'}}>
        <div style={{padding:'16px',borderBottom:'1px solid var(--glass-border)',
          fontFamily:"'Sora',sans-serif",fontWeight:700,fontSize:14}}>
          Messages
        </div>
        <div style={{flex:1,overflowY:'auto',padding:'8px'}}>
          {loading ? [1,2,3].map(i=>(
            <div key={i} className="sp-skeleton" style={{height:56,borderRadius:10,margin:'4px 0'}}/>
          )) : convos.length===0 ? (
            <div style={{padding:'24px 16px',textAlign:'center',color:'var(--text-lo)',fontSize:13}}>
              No conversations yet
            </div>
          ) : convos.map(c => (
            <div key={c.contact_id}
              onClick={()=>setActive(c)}
              style={{display:'flex',alignItems:'center',gap:10,padding:'10px 12px',
                borderRadius:10,cursor:'pointer',transition:'all .2s',
                background: active?.contact_id===c.contact_id ? 'rgba(26,86,219,0.1)' : 'transparent',
                border:`1px solid ${active?.contact_id===c.contact_id ? 'var(--blue-border)' : 'transparent'}`}}>
              <div style={{width:36,height:36,borderRadius:10,flexShrink:0,
                background:'linear-gradient(135deg,rgba(26,86,219,.25),rgba(6,182,212,.15))',
                display:'flex',alignItems:'center',justifyContent:'center',
                fontFamily:"'Sora',sans-serif",fontWeight:700,fontSize:12,color:'var(--blue-light)'}}>
                {initials(c.contact_name)}
              </div>
              <div style={{flex:1,minWidth:0}}>
                <div style={{fontWeight:600,fontSize:13,marginBottom:2,
                  whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis'}}>
                  {c.contact_name}
                </div>
                <div style={{fontSize:11,color:'var(--text-lo)',
                  whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis'}}>
                  {c.last_message}
                </div>
              </div>
              {c.unread_count>0 && (
                <span style={{background:'var(--teal)',color:'#fff',fontSize:10,
                  fontWeight:700,borderRadius:99,padding:'2px 6px',flexShrink:0}}>
                  {c.unread_count}
                </span>
              )}
            </div>
          ))}
        </div>
      </div>

      {/* Thread */}
      <div className="sp-card" style={{padding:0,display:'flex',flexDirection:'column',overflow:'hidden'}}>
        {!active ? (
          <div style={{flex:1,display:'flex',flexDirection:'column',alignItems:'center',
            justifyContent:'center',gap:12,color:'var(--text-lo)'}}>
            <i className="fa-solid fa-comment-dots" style={{fontSize:36}}/>
            <p style={{fontSize:13}}>Select a conversation</p>
          </div>
        ) : (
          <>
            <div style={{padding:'14px 18px',borderBottom:'1px solid var(--glass-border)',
              display:'flex',alignItems:'center',gap:12}}>
              <div style={{width:36,height:36,borderRadius:10,
                background:'linear-gradient(135deg,rgba(26,86,219,.25),rgba(6,182,212,.15))',
                display:'flex',alignItems:'center',justifyContent:'center',
                fontFamily:"'Sora',sans-serif",fontWeight:700,fontSize:12,color:'var(--blue-light)'}}>
                {initials(active.contact_name)}
              </div>
              <div>
                <div style={{fontWeight:700,fontSize:14}}>{active.contact_name}</div>
                <div style={{fontSize:11,color:'var(--text-lo)',textTransform:'capitalize'}}>{active.contact_role}</div>
              </div>
            </div>
            <div style={{flex:1,overflowY:'auto',padding:'16px',display:'flex',flexDirection:'column',gap:10}}>
              {thread.map((msg,i) => {
                const mine = msg.from_user_id === user.userId;
                return (
                  <div key={i} style={{display:'flex',justifyContent:mine?'flex-end':'flex-start'}}>
                    <div style={{maxWidth:'70%',padding:'10px 14px',borderRadius:12,
                      fontSize:13,lineHeight:1.6,
                      background: mine ? 'var(--blue)' : 'rgba(255,255,255,0.05)',
                      color: mine ? 'white' : 'var(--text-hi)',
                      borderBottomRightRadius: mine ? 4 : 12,
                      borderBottomLeftRadius:  mine ? 12 : 4,
                    }}>
                      {msg.body}
                      <div style={{fontSize:10,color:mine?'rgba(255,255,255,0.5)':'var(--text-lo)',
                        marginTop:4,textAlign:'right'}}>
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
                Sharing personal contact information is not permitted on this platform.
              </div>
              <div style={{display:'flex',gap:10}}>
                <input className="sp-input" style={{flex:1,padding:'10px 14px'}}
                  placeholder="Type a message..."
                  value={body} onChange={e=>setBody(e.target.value)}
                  onKeyDown={e=>e.key==='Enter'&&!e.shiftKey&&send()}/>
                <button className="sp-btn sp-btn-primary" onClick={send} disabled={sending||!body.trim()}>
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

/* ── SETTINGS TAB ──────────────────────────────────── */
const SettingsTab = ({ user, onLogout }) => {
  const toast = useToast();
  const [pw,    setPw]    = useState({ current:'', next:'', confirm:'' });
  const [saving,setSaving]= useState(false);

  const changePassword = () => {
    if (pw.next !== pw.confirm) { toast('Passwords do not match.','error'); return; }
    if (pw.next.length < 8)     { toast('Password must be at least 8 characters.','error'); return; }
    setSaving(true);
    fetch(API + 'save.php', {
      method:'POST', credentials:'include',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ current_password: pw.current, new_password: pw.next })
    })
    .then(r=>r.json())
    .then(d => {
      if(d.success) { toast('Password updated.','success'); setPw({current:'',next:'',confirm:''}); }
      else toast(d.error||'Failed.','error');
    })
    .catch(()=>toast('Failed.','error'))
    .finally(()=>setSaving(false));
  };

  return (
    <div style={{display:'flex',flexDirection:'column',gap:20}}>
      <div className="sp-card sp-fade-up">
        <div className="sp-pill"><i className="fa-solid fa-lock"/> Change Password</div>
        <div style={{display:'flex',flexDirection:'column',gap:12,maxWidth:420}}>
          <input className="sp-input" type="password" placeholder="Current password"
            value={pw.current} onChange={e=>setPw(p=>({...p,current:e.target.value}))}/>
          <input className="sp-input" type="password" placeholder="New password (min 8 chars)"
            value={pw.next} onChange={e=>setPw(p=>({...p,next:e.target.value}))}/>
          <input className="sp-input" type="password" placeholder="Confirm new password"
            value={pw.confirm} onChange={e=>setPw(p=>({...p,confirm:e.target.value}))}/>
          <button className="sp-btn sp-btn-primary" style={{alignSelf:'flex-start'}}
            onClick={changePassword} disabled={saving}>
            <i className={`fa-solid ${saving?'fa-spinner fa-spin':'fa-key'}`}/>
            {saving ? 'Saving...' : 'Update Password'}
          </button>
        </div>
      </div>

      <div className="sp-card sp-fade-up sp-fade-up-1"
        style={{border:'1px solid rgba(239,68,68,0.15)',background:'rgba(239,68,68,0.03)'}}>
        <div className="sp-pill" style={{background:'var(--red-dim)',color:'var(--red)',borderColor:'rgba(239,68,68,0.2)'}}>
          <i className="fa-solid fa-triangle-exclamation"/> Danger Zone
        </div>
        <p style={{fontSize:13,color:'var(--text-mid)',marginBottom:16}}>
          Once you log out you will need your email and password to access your account.
        </p>
        <button className="sp-btn" style={{background:'var(--red-dim)',color:'var(--red)',
          border:'1px solid rgba(239,68,68,0.25)'}} onClick={onLogout}>
          <i className="fa-solid fa-right-from-bracket"/> Sign Out
        </button>
      </div>
    </div>
  );
};

/* ══════════════════════════════════════════════════════
   MAIN STUDENT PROFILE COMPONENT
   Props: user, setUser, onNavigate
   ══════════════════════════════════════════════════════ */
window.StudentProfilePage = ({ user, setUser, onNavigate }) => {
  const [activeTab, setActiveTab] = useState('overview');
  const [docs,      setDocs]      = useState([]);
  const [unread,    setUnread]    = useState(0);
  const [loading,   setLoading]   = useState(true);
  const [error,     setError]     = useState('');

  // Load documents
  useEffect(() => {
    fetch(API + 'documents.php', { credentials:'include' })
      .then(r=>r.json())
      .then(d => {
        if(d.success) {
          setDocs(d.documents||[]);
          setError('');
        }
      })
      .catch(err => {
        console.error('❌ documents.php:', err);
        setError('Could not load data. Please refresh.');
      })
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    setUser(u => ({
      ...u,
      docCount: docs.length,
      completionPct: calcCompletion(u, docs),
    }));
  }, [docs, setUser]);

  // Poll unread count
  useEffect(() => {
    const poll = () => {
      fetch(API + 'unread.php', { credentials:'include' })
        .then(r=>r.json())
        .then(d => { if(d.success) setUnread(d.count||0); })
        .catch(err => {
          console.error('❌ unread.php:', err);
          setError('Could not load data. Please refresh.');
        });
    };
    poll();
    const t = setInterval(poll, 30000);
    return () => clearInterval(t);
  }, []);

  useEffect(() => {
    const checkVerification = () => {
      fetch(API + 'me.php', { credentials:'include' })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            const newVerified = data.profile?.verified || data.user?.verified;
            if (newVerified && newVerified !== user.verified) {
              setUser(u => ({ ...u, verified: newVerified }));
            }
          }
        })
        .catch(err => console.error('❌ me.php:', err));
    };
    const t = setInterval(checkVerification, 60000);
    return () => clearInterval(t);
  }, [user.verified, setUser]);

  const handleLogout = () => {
    fetch(API + 'logout.php', { method:'POST', credentials:'include' })
      .finally(() => { setUser({role:'student'}); onNavigate('/signup'); });
  };

  const pct = user.completionPct || 0;
  const isVerified = user.verified === 'verified';

  if (loading) {
    return <div className="sp-skeleton" style={{height:80, borderRadius:12}}/>;
  }

  if (error) {
    return <div className="sp-card" style={{color:'#fca5a5'}}>{error}</div>;
  }

  return (
    <ToastProvider>
      <div id="sp-root">

        {/* TOPBAR */}
        <header className="sp-topbar">
          <div className="sp-logo">Better<span>Abroad</span></div>
          <div className="sp-topbar-right">
            <button className="sp-notif-btn" onClick={()=>setActiveTab('messages')}>
              <i className="fa-solid fa-bell"/>
              {unread>0 && <div className="sp-notif-dot"/>}
            </button>
            <div className="sp-avatar-btn">{initials(user.fullName)}</div>
          </div>
        </header>

        {/* SIDEBAR */}
        <aside className="sp-sidebar">
          <div className="sp-sidebar-card">
            <div className="sp-avatar-lg">
              {initials(user.fullName)}
              {isVerified && <div className="sp-verified-ring"/>}
            </div>
            <div className="sp-sidebar-name">{user.fullName||'Student'}</div>
            <div className="sp-sidebar-email">{user.email}</div>
            <CompletionRing pct={pct}/>
            <div style={{marginTop:8}}>
              <span className={`sp-badge-verified ${isVerified?'':'sp-badge-pending'}`}>
                <i className={`fa-solid ${isVerified?'fa-circle-check':'fa-clock'}`}/>
                {isVerified ? 'Verified' : 'Pending Review'}
              </span>
            </div>
          </div>

          <div className="sp-nav-section-label">Profile</div>
          {TABS.slice(0,4).map(t => (
            <div key={t.id} className={`sp-nav-item ${activeTab===t.id?'active':''}`}
              onClick={()=>setActiveTab(t.id)}>
              <i className={`fa-solid ${t.icon}`}/>{t.label}
              {t.id==='messages' && unread>0 && <span className="sp-nav-badge">{unread}</span>}
            </div>
          ))}

          <div className="sp-nav-section-label">Account</div>
          {TABS.slice(4).map(t => (
            <div key={t.id} className={`sp-nav-item ${activeTab===t.id?'active':''}`}
              onClick={()=>setActiveTab(t.id)}>
              <i className={`fa-solid ${t.icon}`}/>{t.label}
            </div>
          ))}

          <div className="sp-nav-section-label">Discover</div>
          <div className="sp-nav-item" onClick={()=>onNavigate('/marketplace')}>
            <i className="fa-solid fa-store"/> Marketplace
          </div>

          <div className="sp-sidebar-bottom">
            <button className="sp-logout-btn" onClick={handleLogout}>
              <i className="fa-solid fa-right-from-bracket"/> Sign Out
            </button>
          </div>
        </aside>

        {/* MAIN */}
        <main className="sp-main">
          {/* Page header */}
          <div className="sp-page-header sp-fade-up">
            <div>
              <h1 className="sp-page-title">
                {activeTab==='overview'    ? 'My Profile'       :
                 activeTab==='documents'   ? 'Documents'        :
                 activeTab==='applications'? 'Applications'     :
                 activeTab==='seminars'    ? 'Seminars'         :
                 activeTab==='messages'    ? 'Messages'         : 'Settings'}
              </h1>
              <p className="sp-page-subtitle">
                {activeTab==='overview'    ? `Welcome back, ${user.fullName?.split(' ')[0]||'Student'}` :
                 activeTab==='documents'   ? 'Upload and manage your verification documents' :
                 activeTab==='applications'? 'Track all your university applications' :
                 activeTab==='seminars'    ? 'Register for virtual university recruitment sessions' :
                 activeTab==='messages'    ? 'Communicate with partner universities' :
                                            'Manage your account'}
              </p>
            </div>
            <div className="sp-header-actions">
              {activeTab==='overview' && (
                <button className="sp-btn sp-btn-ghost" onClick={()=>onNavigate('/marketplace')}>
                  <i className="fa-solid fa-store"/> Browse Universities
                </button>
              )}
            </div>
          </div>

          {/* Tab content */}
          {activeTab==='overview'     && <OverviewTab     user={user} setUser={setUser} docs={docs}
            onVideoUploaded={(docType, data) => {
              setDocs(prev => {
                const next = prev.filter(x => x.doc_type !== docType);
                return [...next, { doc_type: docType, file_name: data.file_name, status:'pending' }];
              });
            }}/>}
          {activeTab==='documents'    && <DocumentsTab    user={user} docs={docs} onDocUploaded={(k,d)=>{
            setDocs(prev => {
              const next = prev.filter(x=>x.doc_type!==k);
              return [...next, {doc_type:k, file_name:d.file_name, status:'pending'}];
            });
          }}/>}
          {activeTab==='applications' && <ApplicationsTab/>}
          {activeTab==='seminars'     && <SeminarsTab user={user}/>}
          {activeTab==='messages'     && <MessagesTab  user={user}/>}
          {activeTab==='settings'     && <SettingsTab  user={user} onLogout={()=>{
            fetch(API+'logout.php',{method:'POST',credentials:'include'})
              .finally(()=>{ setUser({role:'student'}); onNavigate('/signup'); });
          }}/>}
        </main>

      </div>
    </ToastProvider>
  );
};

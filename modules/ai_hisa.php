<div class="container-fluid py-3">
  <div class="hisa-hero mb-4">
    <div class="hero-glow"></div>
    <div class="hero-particles">
      <span></span><span></span><span></span><span></span><span></span>
    </div>
    <div class="d-flex align-items-center gap-3 position-relative">
      <div class="hero-icon"><i class="bi bi-cpu"></i></div>
      <div>
        <h4 class="fw-bold mb-0 text-white">HISA AI</h4>
        <p class="mb-0 hero-desc">Pusat kecerdasan keuangan masjid — tanya, analisis, audit, dan laporan dalam satu layar</p>
      </div>
    </div>
  </div>

  <div class="hisa-tabs mb-4">
    <div class="tab-indicator" id="tabIndicator"></div>
    <button class="tab-btn active" data-tab="finance" onclick="switchTab('finance')"><i class="bi bi-wallet2"></i> Finance</button>
    <button class="tab-btn" data-tab="analytics" onclick="switchTab('analytics')"><i class="bi bi-graph-up"></i> Analytics</button>
  </div>

  <div id="tab-finance" class="tab-pane active">
    <div class="hisa-monitor mb-3">
      <div class="monitor-glow"></div>
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="d-flex align-items-center gap-2">
          <div class="monitor-pulse"><i class="bi bi-robot text-accent"></i></div>
          <span class="fw-bold monitor-title">AI Financial Monitor</span>
          <span class="badge bg-accent" id="monitorBadge">0</span>
        </div>
        <button class="monitor-btn" onclick="refreshMonitor()">
          <i class="bi bi-arrow-clockwise me-1"></i> Segarkan
        </button>
      </div>
      <div id="monitorAlerts" class="monitor-body"></div>
    </div>

    <div class="chat-card">
      <div class="chat-card-head">
        <div class="d-flex align-items-center gap-2">
          <div class="chat-head-icon"><i class="bi bi-chat-dots"></i></div>
          <div>
            <h6 class="fw-bold mb-0 text-white">Tanya & Catat Keuangan</h6>
            <small class="text-white-50">Tanya kondisi keuangan atau catat transaksi baru</small>
          </div>
        </div>
        <button class="chat-head-btn" onclick="document.getElementById('chatContainer').innerHTML='';refreshMonitor()" title="Bersihkan percakapan"><i class="bi bi-trash3"></i></button>
      </div>
      <div class="chat-card-body">
        <div class="chat-quick">
          <button onclick="quickChat('Buat ringkasan keuangan')"><i class="bi bi-lightbulb"></i> Ringkasan</button>
          <button onclick="quickChat('Buat narasi laporan keuangan')"><i class="bi bi-journal-text"></i> Narasi</button>
          <button onclick="quickChat('Beri rekomendasi efisiensi')"><i class="bi bi-piggy-bank"></i> Efisiensi</button>
          <button onclick="quickChat('Bagaimana kondisi keuangan?')"><i class="bi bi-bar-chart"></i> Analisis</button>
        </div>
        <div class="chat-box" id="chatContainer"></div>
        <div class="chat-input">
          <input type="text" id="chatInput" class="chat-input-field" placeholder="Tanya keuangan, catat transaksi, atau minta laporan..." onkeypress="if(event.key==='Enter') sendChat()" autocomplete="off">
          <button class="chat-send" onclick="sendChat()"><i class="bi bi-send-fill"></i></button>
        </div>
      </div>
    </div>
    <div id="aiParsePopup" class="ai-overlay" onclick="if(event.target===this) tutupParse()">
      <div class="ai-confirm">
        <div class="ai-confirm-body">
          <div class="ai-confirm-icon masuk" id="aiParseIcon"><i class="bi bi-check-lg"></i></div>
          <h5 class="fw-bold mb-1">Konfirmasi & Edit Transaksi</h5>
          <p class="small text-secondary mb-2">Hasil analisis AI — bisa diedit sebelum ditambahkan:</p>
          <table class="w-100">
            <tr><td class="label">Jenis</td><td><select id="aiPJenis" class="form-select form-select-sm"><option value="Pemasukan">Pemasukan</option><option value="Pengeluaran">Pengeluaran</option></select></td></tr>
            <tr><td class="label">Jumlah</td><td><div class="input-group input-group-sm"><span class="input-group-text">Rp</span><input type="text" id="aiPJumlah" class="form-control fw-bold"></div></td></tr>
            <tr><td class="label">Keterangan</td><td><input type="text" id="aiPKeterangan" class="form-control form-control-sm"></td></tr>
            <tr><td class="label">Akun</td><td><select id="aiPAkun" class="form-select form-select-sm"></select></td></tr>
            <tr><td class="label">Tanggal</td><td><input type="date" id="aiPTanggal" class="form-control form-control-sm"></td></tr>
          </table>
        </div>
        <div class="ai-confirm-footer">
          <button class="btn btn-outline-secondary btn-sm" onclick="tutupParse()">Batal</button>
          <button class="btn btn-accent btn-sm" id="aiParseBtn" onclick="konfirmasiParse()"><i class="bi bi-check-lg"></i> Tambahkan</button>
        </div>
      </div>
    </div>
  </div>

  <div id="tab-analytics" class="tab-pane">
    <div class="action-grid cols-4 mb-3">
      <button class="action-btn" onclick="jalanAnalisis('prediktif')" data-action="prediktif">
        <div class="ab-icon"><i class="bi bi-graph-up-arrow"></i></div>
        <div class="ab-label">Analisis Prediktif</div>
        <div class="ab-desc">Prediksi saldo & ketahanan finansial</div>
      </button>
      <button class="action-btn" onclick="jalanAnalisis('pola_anomali')" data-action="pola_anomali">
        <div class="ab-icon"><i class="bi bi-binoculars"></i></div>
        <div class="ab-label">Pola & Anomali</div>
        <div class="ab-desc">Tren, pola & deteksi anomali</div>
      </button>
      <button class="action-btn" onclick="jalanAnalisis('audit_keuangan')" data-action="audit_keuangan">
        <div class="ab-icon"><i class="bi bi-shield-check"></i></div>
        <div class="ab-label">Audit & Risiko</div>
        <div class="ab-desc">Audit, transparansi, kepatuhan & risiko</div>
      </button>
      <button class="action-btn" onclick="jalanAnalisis('laporan_sosial')" data-action="laporan_sosial">
        <div class="ab-icon"><i class="bi bi-journal-richtext"></i></div>
        <div class="ab-label">Laporan & Sosial</div>
        <div class="ab-desc">Dampak sosial, ringkasan & narasi dana</div>
      </button>
    </div>

    <div id="analisisResult" class="result-box">
      <div class="result-header" id="analisisHeader"><i class="bi bi-robot text-accent"></i> <span>Memilih analisis...</span><button class="btn-close ms-auto" onclick="sembHasil('analisis')"></button></div>
      <div id="analisisLoading" class="result-loading">
        <div class="spinner-box"><div></div><div></div><div></div></div>
        <p class="text-secondary mt-2 mb-0" id="analisisLoadingText">Menganalisis data...</p>
      </div>
      <div id="analisisContent" class="result-body"></div>
    </div>
    <div id="analisisPlaceholder">
      <div class="empty-state">
        <div class="empty-icon"><i class="bi bi-graph-up"></i></div>
        <h5 class="fw-bold mb-1">Pilih Analisis</h5>
        <p class="text-secondary mb-0">Klik tombol di atas untuk memulai analisis keuangan</p>
      </div>
    </div>
  </div>
</div>

<style>
/* ===== BASE ===== */
.hisa-hero, .hisa-tabs, .action-btn, .result-box, .empty-state, .pola-card, .ai-confirm {
  animation: fadeSlideUp 0.5s ease both;
}
.hisa-tabs { animation-delay: 0.15s; }
.chat-card { animation-delay: 0.2s; }
.action-btn:nth-child(1) { animation-delay: 0.22s; }
.action-btn:nth-child(2) { animation-delay: 0.26s; }
.action-btn:nth-child(3) { animation-delay: 0.3s; }
.action-btn:nth-child(4) { animation-delay: 0.34s; }
.result-box { animation-delay: 0.3s; }

@keyframes fadeSlideUp {
  from { opacity: 0; transform: translateY(18px); }
  to { opacity: 1; transform: translateY(0); }
}

/* ===== HERO ===== */
.hisa-hero {
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
  background-size: 200% 200%;
  animation: heroBgShift 8s ease infinite;
  padding: 1.5rem 2rem;
  border-radius: 18px;
  position: relative;
  overflow: hidden;
}
@keyframes heroBgShift {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}
.hisa-hero::before {
  content: '';
  position: absolute;
  top: -50%; right: -20%;
  width: 400px; height: 400px;
  background: radial-gradient(circle, rgba(16,185,129,0.12) 0%, transparent 70%);
  border-radius: 50%;
  pointer-events: none;
}
.hero-glow {
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  background: radial-gradient(ellipse at 30% 50%, rgba(16,185,129,0.06) 0%, transparent 60%);
  pointer-events: none;
}
.hero-particles span {
  position: absolute;
  width: 4px; height: 4px;
  background: rgba(16,185,129,0.25);
  border-radius: 50%;
  animation: heroFloat 6s infinite;
  pointer-events: none;
}
.hero-particles span:nth-child(1) { top: 20%; left: 10%; animation-delay: 0s; width: 6px; height: 6px; }
.hero-particles span:nth-child(2) { top: 60%; left: 25%; animation-delay: 1.2s; }
.hero-particles span:nth-child(3) { top: 30%; right: 15%; animation-delay: 2.4s; width: 5px; height: 5px; }
.hero-particles span:nth-child(4) { top: 70%; right: 30%; animation-delay: 3.6s; }
.hero-particles span:nth-child(5) { top: 15%; right: 40%; animation-delay: 4.8s; width: 3px; height: 3px; }
@keyframes heroFloat {
  0%, 100% { transform: translateY(0) scale(1); opacity: 0.25; }
  50% { transform: translateY(-20px) scale(1.5); opacity: 0.5; }
}
.hero-icon {
  width: 52px; height: 52px;
  background: rgba(16,185,129,0.15);
  border-radius: 16px;
  display: flex;
  align-items: center; justify-content: center;
  font-size: 1.5rem;
  color: var(--accent,#10b981);
  flex-shrink: 0;
  animation: heroIconPulse 3s ease infinite;
}
@keyframes heroIconPulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(16,185,129,0.3); }
  50% { box-shadow: 0 0 0 12px rgba(16,185,129,0); }
}
.hero-desc { color: rgba(255,255,255,0.65); font-size: 0.9rem; }

/* ===== MONITOR ===== */
.hisa-monitor {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 16px;
  padding: 1rem 1.25rem;
  position: relative;
  overflow: hidden;
}
.hisa-monitor::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0; height: 1px;
  background: linear-gradient(90deg, transparent, rgba(16,185,129,0.25), transparent);
  animation: monitorSweep 3s ease infinite;
}
@keyframes monitorSweep {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}
.monitor-glow {
  position: absolute;
  top: -50%; left: -50%;
  width: 200%; height: 200%;
  background: radial-gradient(circle at 50% 50%, rgba(16,185,129,0.04) 0%, transparent 50%);
  animation: monitorGlow 6s ease infinite;
  pointer-events: none;
}
@keyframes monitorGlow {
  0%, 100% { transform: scale(1); opacity: 0.5; }
  50% { transform: scale(1.1); opacity: 1; }
}
.monitor-pulse {
  animation: monitorPulse 2s ease infinite;
  display: flex;
}
@keyframes monitorPulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}
.monitor-title { color: var(--text-primary); }
.monitor-btn {
  background: var(--bg-body);
  border: 1px solid var(--border-color);
  color: var(--text-primary);
  padding: 0.35rem 0.85rem;
  border-radius: 8px;
  font-size: 0.8rem;
  display: flex;
  align-items: center;
  cursor: pointer;
  transition: all 0.3s ease;
}
.monitor-btn:hover {
  background: rgba(16,185,129,0.08);
  border-color: var(--accent);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(16,185,129,0.12);
}
.monitor-btn:active { transform: translateY(0); }
.monitor-btn i { transition: transform 0.5s ease; }
.monitor-btn:hover i { transform: rotate(360deg); }
.monitor-body { min-height: 40px; }
.monitor-alert {
  padding: 10px 12px;
  margin-bottom: 6px;
  border-radius: 10px;
  background: var(--bg-body);
  border: 1px solid var(--border-color);
  transition: all 0.3s ease;
  animation: alertSlide 0.4s ease both;
}
.monitor-alert:hover {
  background: rgba(16,185,129,0.03);
  border-color: rgba(16,185,129,0.15);
  transform: translateX(4px);
}
@keyframes alertSlide {
  from { opacity: 0; transform: translateX(-12px); }
  to { opacity: 1; transform: translateX(0); }
}
.monitor-alert:nth-child(2) { animation-delay: 0.05s; }
.monitor-alert:nth-child(3) { animation-delay: 0.1s; }
.monitor-alert:nth-child(4) { animation-delay: 0.15s; }

/* ===== TAB BAR ===== */
.hisa-tabs {
  display: flex;
  gap: 0.25rem;
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 14px;
  padding: 0.35rem;
  position: relative;
}
.tab-indicator {
  position: absolute;
  bottom: 0.35rem; left: 0.35rem;
  height: calc(100% - 0.7rem);
  background: rgba(16,185,129,0.1);
  border: 1px solid rgba(16,185,129,0.15);
  border-radius: 11px;
  transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
  pointer-events: none;
  z-index: 0;
}
.tab-btn {
  flex: 1;
  border: none;
  background: transparent;
  padding: 0.7rem 0.5rem;
  border-radius: 11px;
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--text-muted);
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  position: relative;
  z-index: 1;
}
.tab-btn:hover { color: var(--text-primary); background: rgba(255,255,255,0.05); }
.tab-btn.active { color: var(--accent); }
.tab-btn i { transition: transform 0.3s ease; }
.tab-btn:hover i { transform: scale(1.15); }

/* ===== TAB PANES ===== */
.tab-pane { display: none; animation: tabFade 0.4s ease; }
.tab-pane.active { display: block; }
@keyframes tabFade {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}

/* ===== ACTION BUTTONS ===== */
.action-grid { display: grid; gap: 0.5rem; }
.action-grid.cols-2 { grid-template-columns: repeat(2, 1fr); }
.action-grid.cols-3 { grid-template-columns: repeat(3, 1fr); }
.action-grid.cols-4 { grid-template-columns: repeat(4, 1fr); }
@media (max-width: 576px) {
  .action-grid.cols-2,
  .action-grid.cols-3,
  .action-grid.cols-4 { grid-template-columns: repeat(2, 1fr); }
}

.action-btn {
  width: 100%;
  border: 1px solid var(--border-color);
  border-radius: 14px;
  padding: 1rem 0.5rem;
  background: var(--bg-card);
  text-align: center;
  cursor: pointer;
  transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}
.action-btn::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  background: linear-gradient(135deg, rgba(16,185,129,0.04), transparent);
  opacity: 0;
  transition: opacity 0.35s ease;
}
.action-btn:hover::before { opacity: 1; }
.action-btn:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 28px rgba(0,0,0,0.1);
  border-color: var(--accent);
}
.action-btn:active { transform: translateY(-1px); }
.action-btn .ab-icon {
  width: 44px; height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center; justify-content: center;
  font-size: 1.2rem;
  margin: 0 auto 0.5rem;
  transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}
.action-btn .ab-label { font-weight: 700; font-size: 0.82rem; color: var(--text-primary); position: relative; }
.action-btn .ab-desc { font-size: 0.68rem; color: var(--text-muted); margin-top: 0.15rem; position: relative; }
.action-btn.active {
  border-color: var(--accent);
  background: rgba(16,185,129,0.04);
  box-shadow: 0 0 0 2px rgba(16,185,129,0.15), 0 8px 24px rgba(16,185,129,0.08);
}
.action-btn[data-action="prediktif"] .ab-icon { background: rgba(16,185,129,0.12); color: #10b981; }
.action-btn[data-action="pola_anomali"] .ab-icon { background: rgba(239,68,68,0.12); color: #ef4444; }
.action-btn[data-action="audit_keuangan"] .ab-icon { background: rgba(245,158,11,0.12); color: #f59e0b; }
.action-btn[data-action="dampak_sosial"] .ab-icon { background: rgba(236,72,153,0.12); color: #ec4899; }
.action-btn[data-action="ringkasan"] .ab-icon { background: rgba(168,85,247,0.12); color: #a855f7; }
.action-btn[data-action="laporan_sosial"] .ab-icon { background: rgba(168,85,247,0.12); color: #a855f7; }
.action-btn:hover .ab-icon { transform: scale(1.12) rotate(-3deg); }

/* ===== RESULT BOX ===== */
.result-box {
  border: 1px solid var(--border-color);
  border-radius: 16px;
  overflow: hidden;
  background: var(--bg-card);
  transition: all 0.4s ease;
}
.result-box[style*="display: none"] { display: none !important; }
.result-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.85rem 1.25rem;
  background: linear-gradient(135deg, rgba(16,185,129,0.08), rgba(59,130,246,0.04));
  font-weight: 600;
  font-size: 0.9rem;
  color: var(--text-primary);
}
.result-loading { padding: 2rem; text-align: center; animation: fadeSlideUp 0.3s ease; }
.result-body {
  padding: 1.25rem;
  border-top: 1px solid var(--border-color);
  animation: fadeSlideUp 0.4s ease;
}

/* ===== SPINNER ===== */
.spinner-box { display: inline-flex; gap: 6px; }
.spinner-box div {
  width: 10px; height: 10px;
  border-radius: 50%;
  background: var(--accent);
  animation: bounceSpin 1.2s infinite ease-in-out;
}
.spinner-box div:nth-child(2) { animation-delay: 0.15s; }
.spinner-box div:nth-child(3) { animation-delay: 0.3s; }
@keyframes bounceSpin {
  0%, 60%, 100% { transform: translateY(0) scale(1); opacity: 0.3; }
  30% { transform: translateY(-10px) scale(1.2); opacity: 1; }
}

/* ===== EMPTY STATE ===== */
.empty-state {
  text-align: center;
  padding: 3rem 1rem;
  border: 2px dashed var(--border-color);
  border-radius: 16px;
  background: var(--bg-card);
  transition: all 0.3s ease;
}
.empty-state:hover {
  border-color: var(--accent);
  background: rgba(16,185,129,0.02);
}
.empty-state .empty-icon {
  font-size: 3rem;
  color: var(--text-muted);
  opacity: 0.3;
  margin-bottom: 0.75rem;
  transition: all 0.4s ease;
}
.empty-state:hover .empty-icon {
  opacity: 0.5;
  transform: scale(1.1);
}

/* ===== CHAT CARD ===== */
.chat-card {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 18px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0,0,0,0.04);
  animation: fadeSlideUp 0.5s ease both;
  animation-delay: 0.2s;
  transition: box-shadow 0.3s ease;
}
.chat-card:hover { box-shadow: 0 8px 32px rgba(0,0,0,0.07); }
.chat-card-head {
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
  padding: 1rem 1.25rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.chat-head-icon {
  width: 38px; height: 38px;
  background: rgba(16,185,129,0.15);
  border-radius: 10px;
  display: flex;
  align-items: center; justify-content: center;
  font-size: 1.1rem;
  color: var(--accent);
  flex-shrink: 0;
}
.chat-head-btn {
  width: 32px; height: 32px;
  border: none; background: rgba(255,255,255,0.06);
  border-radius: 8px;
  color: rgba(255,255,255,0.4);
  display: flex;
  align-items: center; justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 0.8rem;
}
.chat-head-btn:hover { background: rgba(239,68,68,0.2); color: #ef4444; }
.chat-card-body { padding: 1rem 1.25rem 1.25rem; }

.chat-quick {
  display: flex;
  gap: 0.4rem;
  flex-wrap: wrap;
  margin-bottom: 0.85rem;
}
.chat-quick button {
  border: 1px solid var(--border-color);
  background: var(--bg-body);
  color: var(--text-muted);
  padding: 0.35rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.3rem;
}
.chat-quick button:hover {
  border-color: var(--accent);
  color: var(--accent);
  background: rgba(16,185,129,0.06);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(16,185,129,0.1);
}
.chat-quick button i { font-size: 0.8rem; }

.chat-box {
  max-height: 380px;
  overflow-y: auto;
  padding: 0.75rem 0.5rem;
  scroll-behavior: smooth;
  background: var(--bg-body);
  border-radius: 14px;
  border: 1px solid var(--border-color);
  margin-bottom: 0.85rem;
}
.chat-box::-webkit-scrollbar { width: 4px; }
.chat-box::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 4px; }
.chat-box::-webkit-scrollbar-track { background: transparent; }

.chat-msg {
  max-width: 88%;
  margin-bottom: 0.7rem;
  padding: 0.7rem 1rem;
  border-radius: 14px 14px 14px 4px;
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  font-size: 0.85rem;
  line-height: 1.65;
  color: var(--text-primary);
  box-shadow: 0 1px 4px rgba(0,0,0,0.03);
}
.chat-msg.user {
  background: linear-gradient(135deg, var(--accent), #059669);
  color: #fff;
  margin-left: auto;
  border-radius: 14px 14px 4px 14px;
  border: none;
  box-shadow: 0 4px 12px rgba(16,185,129,0.2);
}
.chat-msg:last-child { animation: msgSlide 0.35s ease; }
@keyframes msgSlide { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }
.chat-time { font-size: 0.62rem; margin-top: 0.3rem; opacity: 0.6; }
.chat-msg.user .chat-time { color: rgba(255,255,255,0.55); }

.chat-msg.ai-error {
  border-left: 3px solid #ef4444;
  background: rgba(239,68,68,0.04);
}

.chat-input {
  display: flex;
  gap: 0.5rem;
  align-items: stretch;
}
.chat-input-field {
  flex: 1;
  border: 1px solid var(--border-color);
  background: var(--bg-body);
  border-radius: 12px;
  padding: 0.6rem 1rem;
  font-size: 0.85rem;
  color: var(--text-primary);
  outline: none;
  transition: all 0.3s ease;
}
.chat-input-field::placeholder { color: var(--text-muted); font-size: 0.82rem; }
.chat-input-field:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(16,185,129,0.08);
}
.chat-send {
  width: 42px;
  border: none;
  background: var(--accent);
  color: #fff;
  border-radius: 12px;
  display: flex;
  align-items: center; justify-content: center;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.3s ease;
  flex-shrink: 0;
}
.chat-send:hover { background: #059669; transform: scale(1.05); }
.chat-send:active { transform: scale(0.92); }

.chat-typing {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.chat-typing span {
  width: 7px; height: 7px;
  border-radius: 50%;
  background: var(--accent);
  animation: chatTyping 1.4s infinite ease-in-out;
}
.chat-typing span:nth-child(2) { animation-delay: 0.2s; }
.chat-typing span:nth-child(3) { animation-delay: 0.4s; }
@keyframes chatTyping {
  0%, 60%, 100% { transform: translateY(0); opacity: 0.3; }
  30% { transform: translateY(-8px); opacity: 1; }
}

/* ===== POLA CARD ===== */
.pola-card {
  border: 1px solid var(--border-color);
  border-radius: 14px;
  margin-bottom: 1rem;
  background: var(--bg-card);
  overflow: hidden;
  transition: all 0.3s ease;
  animation: fadeSlideUp 0.4s ease both;
}
.pola-card:hover {
  box-shadow: 0 8px 24px rgba(0,0,0,0.08);
  transform: translateY(-2px);
}
.pola-card:nth-child(2) { animation-delay: 0.05s; }
.pola-card:nth-child(3) { animation-delay: 0.1s; }
.pola-card:nth-child(4) { animation-delay: 0.15s; }
.pola-card:nth-child(5) { animation-delay: 0.2s; }
.pola-card-header {
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
  color: #fff;
  padding: 0.85rem 1.15rem;
  font-weight: 700;
  font-size: 0.92rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.pola-card-header i { color: var(--accent,#10b981); font-size: 1.1rem; }
.pola-card-body { padding: 1rem 1.15rem; }
.pola-field {
  display: flex;
  gap: 0.75rem;
  padding: 0.65rem 0;
  border-bottom: 1px solid var(--border-color);
  transition: background 0.2s ease;
}
.pola-field:last-child { border-bottom: none; }
.pola-field:hover { background: rgba(16,185,129,0.02); }
.pola-field-icon {
  width: 32px; height: 32px;
  border-radius: 10px;
  display: flex;
  align-items: center; justify-content: center;
  flex-shrink: 0; font-size: 0.9rem;
}
.pola-field:nth-child(1) .pola-field-icon { background: rgba(16,185,129,0.1); color: #10b981; }
.pola-field:nth-child(2) .pola-field-icon { background: rgba(239,68,68,0.1); color: #ef4444; }
.pola-field:nth-child(3) .pola-field-icon { background: rgba(245,158,11,0.1); color: #f59e0b; }
.pola-field:nth-child(4) .pola-field-icon { background: rgba(59,130,246,0.1); color: #3b82f6; }
.pola-field:nth-child(5) .pola-field-icon { background: rgba(139,92,246,0.1); color: #8b5cf6; }
.pola-field-body { flex: 1; min-width: 0; }
.pola-field-label {
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--text-muted);
  font-weight: 600;
  margin-bottom: 0.15rem;
}
.pola-field-value { font-size: 0.88rem; color: var(--text-primary); line-height: 1.55; }

/* ===== RESULT INLINE BOXES ===== */
.result-inline {
  margin-bottom: 1rem;
  padding: 0.85rem 1rem;
  border-radius: 10px;
  animation: fadeSlideUp 0.3s ease both;
}
.result-inline:hover { transform: translateX(3px); }

/* ===== AI CONFIRM POPUP ===== */
.ai-overlay {
  position: fixed; top:0; left:0; right:0; bottom:0;
  background: rgba(0,0,0,0.5); z-index:9999;
  display:none; align-items:center; justify-content:center;
  animation: overlayFade 0.25s ease;
}
@keyframes overlayFade {
  from { opacity: 0; }
  to { opacity: 1; }
}
.ai-confirm {
  background: var(--bg-card);
  border-radius:20px; max-width:460px; width:90%;
  position:relative; overflow:hidden;
  animation: confirmSlide 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}
@keyframes confirmSlide {
  from { opacity: 0; transform: scale(0.9) translateY(20px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}
.ai-confirm-body { padding:1.75rem; }
.ai-confirm-icon {
  width:48px; height:48px; border-radius:50%;
  display:flex; align-items:center; justify-content:center;
  font-size:1.4rem; margin-bottom:0.75rem;
}
.ai-confirm-icon.masuk { background:rgba(16,185,129,0.12); color:var(--accent); }
.ai-confirm-icon.keluar { background:rgba(239,68,68,0.12); color:#ef4444; }
.ai-confirm table { margin-bottom:0; }
.ai-confirm table td { padding:0.35rem 0; }
.ai-confirm table td.label { font-weight:600; font-size:0.8rem; color:var(--text-muted); width:100px; white-space:nowrap; vertical-align:middle; }
.ai-confirm-footer {
  display:flex; gap:0.5rem; justify-content:flex-end;
  padding:0 1.75rem 1.25rem;
}

/* ===== AUDIT INPUT ===== */

/* ===== MONITOR ALERT INNER ===== */
.monitor-tx-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.3rem 0;
  border-bottom: 1px solid var(--border-color);
  font-size: 0.8rem;
  transition: background 0.2s ease;
}
.monitor-tx-row:hover { background: rgba(0,0,0,0.015); }
.monitor-solution {
  font-size: 0.8rem;
  padding: 4px 8px;
  margin-top: 4px;
  border-radius: 6px;
  background: rgba(16,185,129,0.07);
  border-left: 3px solid #10b981;
}
.monitor-link {
  font-size: 0.8rem;
  color: #10b981;
  transition: all 0.3s ease;
}
.monitor-link:hover {
  color: #34d399;
  text-decoration: underline !important;
}
</style>

<script>
/* ========== SHARED UTILITIES ========== */
function aiMarkdown(text) {
    text = String(text);
    text = text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    text = text.replace(/^#### (.+)$/gm, '<h4>$1</h4>');
    text = text.replace(/^### (.+)$/gm, '<h3>$1</h3>');
    text = text.replace(/^## (.+)$/gm, '<h2>$1</h2>');
    text = text.replace(/^# (.+)$/gm, '<h1>$1</h1>');
    text = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    text = text.replace(/\*(.+?)\*/g, '<em>$1</em>');
    text = text.replace(/`(.+?)`/g, '<code>$1</code>');
    text = text.replace(/^> (.+)$/gm, '<blockquote>$1</blockquote>');
    text = text.replace(/^- (.+)$/gm, '<li>$1</li>');
    text = text.replace(/(<li>.*<\/li>\n?)/gs, function(m) {
        if (!m.includes('<ul>')) return '<ul>' + m.trimEnd() + '</ul>';
        return m;
    });
    text = text.replace(/<\/ul>\n<ul>/g, '');
    text = text.replace(/---+/g, '<hr>');
    text = text.replace(/\n/g, '<br>');
    text = text.replace(/<br><br>/g, '</p><p>');
    text = text.replace(/<br><\/?[^>]+>/g, function(m) { return m.replace('<br>', ''); });
    text = '<p>' + text + '</p>';
    text = text.replace(/<p><\/p>/g, '');
    text = text.replace(/<p><br><\/p>/g, '');
    return text;
}

function escapeHtml(t) {
    const d = document.createElement('div'); d.textContent = t; return d.innerHTML;
}

function now() {
    const d = new Date();
    return d.getHours().toString().padStart(2,'0') + ':' + d.getMinutes().toString().padStart(2,'0');
}

function extractJson(text) {
    let cleaned = text.replace(/```(?:json)?\s*|\s*```/g, '').trim();
    try { return JSON.parse(cleaned); } catch(e) {}
    for (let start = 0; start < cleaned.length; start++) {
        if (cleaned[start] !== '{' && cleaned[start] !== '[') continue;
        let depth = 0, inStr = false;
        for (let i = start; i < cleaned.length; i++) {
            const c = cleaned[i];
            if (c === '\\') { i++; continue; }
            if (c === '"') { inStr = !inStr; continue; }
            if (inStr) continue;
            if (c === '{' || c === '[') depth++;
            if (c === '}' || c === ']') { depth--; if (depth === 0) { try { return JSON.parse(cleaned.substring(start, i + 1)); } catch(e) { break; } } }
        }
    }
    return null;
}

function fieldIcon(key) {
    const map = {
        analisis:'bi-search', deskripsi:'bi-info-circle', dampak:'bi-exclamation-diamond',
        dampak_keuangan:'bi-cash-stack', dampak_sosial:'bi-people',
        saran:'bi-lightbulb', rekomendasi:'bi-check-circle', alasan:'bi-chat-quote',
        risiko:'bi-shield-exclamation', peluang:'bi-arrow-up-circle',
        skor:'bi-trophy', prediksi:'bi-graph-up-arrow', ketahanan:'bi-shield-check',
        kepatuhan:'bi-journal-check', transparansi:'bi-eye',
        solusi:'bi-wrench', total:'bi-calculator',
        tanggal:'bi-calendar', jumlah:'bi-currency-dollar', keterangan:'bi-card-text',
        judul:'bi-bookmark', pola:'bi-diagram-3', tren:'bi-bar-chart-line',
        rekomendasi_strategis:'bi-star', referensi:'bi-link-45deg',
        estimasi_penerima:'bi-person-badge', alamat:'bi-geo-alt',
        pemasukan:'bi-arrow-down-circle', pengeluaran:'bi-arrow-up-circle',
        saldo:'bi-wallet2', posisi:'bi-layers', aktivitas:'bi-activity',
        bulan:'bi-calendar-month', defisit:'bi-exclamation-triangle',
        analisis:'bi-search', kesimpulan:'bi-check2-all'
    };
    return map[key] || 'bi-dot';
}

function sembHasil(prefix) {
    const map = { analisis:'analisis' };
    const p = map[prefix] || prefix;
    const result = document.getElementById(p + 'Result');
    const placeholder = document.getElementById(p + 'Placeholder');
    if (result) result.style.display = 'none';
    if (placeholder) placeholder.style.display = 'block';
    const tab = document.getElementById('tab-' + p);
    if (tab) tab.querySelectorAll('.action-btn').forEach(b => b.classList.remove('active'));
}

/* ========== TAB SWITCHING WITH INDICATOR ========== */
function switchTab(name) {
    document.querySelectorAll('.tab-pane').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    const activeBtn = document.querySelector('.tab-btn[data-tab="' + name + '"]');
    activeBtn.classList.add('active');
    moveIndicator(activeBtn);
    if (window.location.hash !== '#tab-' + name) {
        history.replaceState(null, '', '#tab-' + name);
    }
}

function moveIndicator(el) {
    const indicator = document.getElementById('tabIndicator');
    const tabs = document.querySelector('.hisa-tabs');
    if (!indicator || !tabs || !el) return;
    const tabsRect = tabs.getBoundingClientRect();
    const btnRect = el.getBoundingClientRect();
    indicator.style.left = (btnRect.left - tabsRect.left) + 'px';
    indicator.style.width = btnRect.width + 'px';
}

/* ========== FINANCE: CHAT ========== */
function quickChat(msg) {
    switchTab('finance');
    document.getElementById('chatInput').value = msg; sendChat();
}

function addChatMsg(msg, isUser) {
    const container = document.getElementById('chatContainer');
    container.innerHTML += '<div class="chat-msg' + (isUser ? ' user' : '') + '">' + msg + '<div class="chat-time">' + now() + '</div></div>';
    container.scrollTop = container.scrollHeight;
}

async function sendChat() {
    const input = document.getElementById('chatInput');
    const msg = input.value.trim();
    if (!msg) return;
    addChatMsg(escapeHtml(msg), true);
    input.value = '';
    const loadingId = 'aiLoading_' + Date.now();
    document.getElementById('chatContainer').innerHTML += '<div class="chat-msg" id="' + loadingId + '"><div class="chat-typing"><span></span><span></span><span></span> </div></div>';
    try {
        const res = await fetch('ajax_ai.php?action=chat_keuangan&pesan=' + encodeURIComponent(msg));
        const text = await res.text();
        let parsed;
        try { parsed = JSON.parse(text); } catch(e) { parsed = null; }
        const aiEl = document.getElementById(loadingId);
        if (!aiEl) return;
        if (parsed && parsed.error) {
            aiEl.outerHTML = '<div class="chat-msg"><span class="text-warning">' + parsed.error + '</span><div class="chat-time">' + now() + '</div></div>';
        } else if (parsed && parsed._parse) {
            const jenis = parsed.jenis === 'Pemasukan' ? 'Pemasukan' : 'Pengeluaran';
            document.getElementById('aiParseIcon').className = 'ai-confirm-icon ' + (jenis === 'Pemasukan' ? 'masuk' : 'keluar');
            document.getElementById('aiParseIcon').innerHTML = jenis === 'Pemasukan' ? '<i class="bi bi-arrow-down-circle"></i>' : '<i class="bi bi-arrow-up-circle"></i>';
            document.getElementById('aiPJenis').value = jenis;
            document.getElementById('aiPJumlah').value = Number(parsed.jumlah).toLocaleString('id-ID');
            document.getElementById('aiPKeterangan').value = parsed.keterangan || '';
            document.getElementById('aiPTanggal').value = parsed.tanggal || new Date().toISOString().split('T')[0];
            const akunSel = document.getElementById('aiPAkun');
            akunSel.innerHTML = '';
            const mainAkun = document.getElementById('akun_id');
            if (mainAkun) {
                for (const opt of mainAkun.options) {
                    if (opt.value === '') continue;
                    akunSel.add(new Option(opt.text, opt.value));
                }
                if (parsed.akun_id && akunSel.querySelector('option[value="' + parsed.akun_id + '"]')) akunSel.value = parsed.akun_id;
            }
            aiEl.outerHTML = '<div class="chat-msg">Transaksi berhasil dipahami. Klik "Tambahkan" untuk memasukkan atau edit dulu jika perlu.<div class="chat-time">' + now() + '</div></div>';
            document.getElementById('aiParsePopup').style.display = 'flex';
        } else if (parsed) {
            let html = '';
            for (const [k, v] of Object.entries(parsed)) {
                const label = k.replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase());
                if (typeof v === 'object' && Array.isArray(v)) {
                    for (const item of v) {
                        if (typeof item === 'object') {
                            for (const [ik, iv] of Object.entries(item)) {
                                html += '<div class="mb-1"><i class="bi ' + fieldIcon(ik) + ' me-1"></i><strong>' + ik.replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase()) + ':</strong> ' + aiMarkdown(String(iv)) + '</div>';
                            }
                        }
                    }
                } else {
                    html += '<div class="mb-1"><i class="bi ' + fieldIcon(k) + ' me-1"></i><strong>' + label + ':</strong> ' + aiMarkdown(String(v)) + '</div>';
                }
            }
            aiEl.outerHTML = '<div class="chat-msg">' + html + '<div class="chat-time">' + now() + '</div></div>';
        } else {
            aiEl.outerHTML = '<div class="chat-msg">' + aiMarkdown(text) + '<div class="chat-time">' + now() + '</div></div>';
        }
    } catch(e) {
        const el = document.getElementById(loadingId);
        if (el) el.outerHTML = '<div class="chat-msg"><span class="text-danger">Gagal terhubung</span><div class="chat-time">' + now() + '</div></div>';
    }
}

function tutupParse() { document.getElementById('aiParsePopup').style.display = 'none'; }

function konfirmasiParse() {
    const jenis = document.getElementById('aiPJenis').value;
    const jumlah = document.getElementById('aiPJumlah').value.replace(/[^\d]/g, '');
    const keterangan = document.getElementById('aiPKeterangan').value;
    const akun_id = document.getElementById('aiPAkun').value;
    const tanggal = document.getElementById('aiPTanggal').value;
    if (!jenis || !jumlah || !akun_id) return;
    tutupParse();
    const params = new URLSearchParams();
    params.set('page', 'transaksi');
    params.set('jenis', jenis);
    params.set('jumlah', jumlah);
    params.set('keterangan', keterangan);
    params.set('akun_id', akun_id);
    params.set('tanggal', tanggal);
    params.set('ai_fill', '1');
    window.location.href = 'index.php?' + params.toString();
}

/* ========== ANALYTICS ========== */
const analisisLabels = {
    prediktif: { icon: 'bi-graph-up-arrow', label: 'Analisis Prediktif', load: 'Menganalisis prediksi saldo dan ketahanan...', subactions: ['prediksi', 'ketahanan'] },
    pola_anomali: { icon: 'bi-binoculars', label: 'Pola & Anomali', load: 'Mencari pola transaksi dan mendeteksi anomali...', subactions: ['pola_transaksi', 'anomali', 'tren_keuangan'] },
    audit_keuangan: { icon: 'bi-shield-check', label: 'Audit & Risiko', load: 'Memeriksa audit, transparansi, kepatuhan dan risiko kas...', subactions: ['audit', 'skor_transparansi', 'kepatuhan', 'risiko_kas'] },
    laporan_sosial: { icon: 'bi-journal-richtext', label: 'Laporan & Sosial', load: 'Menganalisis dampak sosial...', subactions: ['dampak_sosial'] }
};

function renderJson(parsed) {
    function renderItem(ik, iv) {
        const ilabel = ik.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
        if (ik === 'judul') return '';
        const icon = fieldIcon(ik);
        return '<div class="pola-field"><div class="pola-field-icon"><i class="bi ' + icon + '"></i></div><div class="pola-field-body"><div class="pola-field-label">' + ilabel + '</div><div class="pola-field-value">' + aiMarkdown(String(iv)) + '</div></div></div>';
    }
    let html = '';
    if (Array.isArray(parsed)) {
        for (const item of parsed) {
            html += '<div class="pola-card">';
            if (typeof item === 'object') {
                const judul = item.judul || '';
                html += '<div class="pola-card-header"><i class="bi bi-binoculars"></i> ' + judul + '</div><div class="pola-card-body">';
                for (const [ik, iv] of Object.entries(item)) { if (ik === 'judul') continue; html += renderItem(ik, iv); }
                html += '</div></div>';
            } else { html += '<div class="pola-card-body">' + aiMarkdown(String(item)) + '</div></div>'; }
        }
    } else {
        for (const [k, v] of Object.entries(parsed)) {
            const label = k.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
            if (typeof v === 'object' && Array.isArray(v)) {
                for (const item of v) {
                    html += '<div class="pola-card">';
                    if (typeof item === 'object') {
                        const judul = item.judul || label;
                        html += '<div class="pola-card-header"><i class="bi bi-binoculars"></i> ' + judul + '</div><div class="pola-card-body">';
                        for (const [ik, iv] of Object.entries(item)) { if (ik === 'judul') continue; html += renderItem(ik, iv); }
                        html += '</div></div>';
                    } else { html += '<div class="pola-card-body">' + aiMarkdown(String(item)) + '</div></div>'; }
                }
            } else {
                html += '<div class="result-inline" style="border-left:4px solid var(--accent,#10b981);background:rgba(16,185,129,0.04);"><i class="bi ' + fieldIcon(k) + ' me-1"></i><strong>' + label + ':</strong> ' + aiMarkdown(String(v)) + '</div>';
            }
        }
    }
    return html;
}

function renderJsonMulti(parsed) {
    function flatVal(val) {
        if (typeof val === 'string') return aiMarkdown(val);
        if (typeof val === 'number') return String(val);
        if (Array.isArray(val)) return val.map(v => flatVal(v)).join('<br>');
        if (val !== null && typeof val === 'object') {
            let parts = [];
            for (const [kk, vv] of Object.entries(val)) {
                if (kk === 'judul') continue;
                const kl = kk.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
                parts.push('<strong>' + kl + ':</strong> ' + flatVal(vv));
            }
            return parts.join('<br>');
        }
        return String(val);
    }
    function fieldLabelIcon(ik) { return '<i class="bi ' + fieldIcon(ik) + ' me-1"></i>'; }
    let html = '';
    for (const [k, v] of Object.entries(parsed)) {
        const label = k.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
        if (typeof v === 'string') {
            html += '<div class="mb-3"><h6 class="fw-bold mb-2 text-accent">' + fieldLabelIcon(k) + label + '</h6>' + aiMarkdown(v) + '</div>';
        } else if (Array.isArray(v)) {
            for (const item of v) {
                html += '<div class="pola-card">';
                if (typeof item === 'object') {
                    const judul = item.judul || '';
                    html += '<div class="pola-card-header"><i class="bi ' + fieldIcon(k) + '"></i> ' + judul + '</div><div class="pola-card-body">';
                    for (const [ik, iv] of Object.entries(item)) { if (ik === 'judul') continue; html += '<div class="pola-field"><div class="pola-field-icon"><i class="bi ' + fieldIcon(ik) + '"></i></div><div class="pola-field-body"><div class="pola-field-label">' + ik.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) + '</div><div class="pola-field-value">' + flatVal(iv) + '</div></div></div>'; }
                    html += '</div></div>';
                } else { html += '<div class="pola-card-body">' + aiMarkdown(String(item)) + '</div></div>'; }
            }
        } else if (typeof v === 'object' && v !== null) {
            html += '<div class="pola-card"><div class="pola-card-header"><i class="bi ' + fieldIcon(k) + '"></i> ' + label + '</div><div class="pola-card-body">';
            for (const [ik, iv] of Object.entries(v)) { if (ik === 'judul') continue; html += '<div class="pola-field"><div class="pola-field-icon"><i class="bi ' + fieldIcon(ik) + '"></i></div><div class="pola-field-body"><div class="pola-field-label">' + ik.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) + '</div><div class="pola-field-value">' + flatVal(iv) + '</div></div></div>'; }
            html += '</div></div>';
        }
    }
    return html;
}

async function jalanAnalisis(action) {
    const result = document.getElementById('analisisResult');
    const header = document.getElementById('analisisHeader');
    const loading = document.getElementById('analisisLoading');
    const content = document.getElementById('analisisContent');
    const placeholder = document.getElementById('analisisPlaceholder');
    const info = analisisLabels[action];
    if (!info) return;

    if (info.input) {
        const input = prompt(info.placeholder || 'Masukkan keputusan/langkah yang akan dievaluasi:');
        if (!input) return;
        info._keputusan = input;
    }
    document.querySelectorAll('#tab-analytics .action-btn').forEach(b => b.classList.remove('active'));
    const btn = document.querySelector('#tab-analytics .action-btn[data-action="' + action + '"]');
    if (btn) btn.classList.add('active');
    header.innerHTML = '<i class="bi ' + info.icon + ' text-accent"></i> <span>' + info.label + '</span><button class="btn-close ms-auto" onclick="sembHasil(\'analisis\')"></button>';
    document.getElementById('analisisLoadingText').textContent = info.load;
    result.style.display = 'block'; placeholder.style.display = 'none'; loading.style.display = 'block'; content.innerHTML = '';
    try {
        if (info.subactions) {
            const allResults = {};
            for (const subAction of info.subactions) {
                let url = 'ajax_ai.php?action=' + subAction;
                if (info._keputusan) url += '&keputusan=' + encodeURIComponent(info._keputusan);
                const res = await fetch(url);
                const text = await res.text();
                let parsed = extractJson(text);
                if (parsed && !parsed.error) { allResults[subAction] = parsed; }
                else if (parsed && parsed.error) { allResults[subAction] = '<em class="text-warning">' + parsed.error + '</em>'; }
                else { allResults[subAction] = text; }
            }
            loading.style.display = 'none';
            const allFailed = Object.values(allResults).every(r => typeof r === 'string' && r.startsWith('<em'));
            if (allFailed) { content.innerHTML = '<span class="text-warning">Semua analisis gagal diproses</span>'; return; }
            content.innerHTML = renderJsonMulti(allResults);
            return;
        }
        const res = await fetch('ajax_ai.php?action=' + action);
        const text = await res.text();
        loading.style.display = 'none';
        if (!text || text.trim() === '') { content.innerHTML = '<span class="text-warning">Tidak ada respons dari AI</span>'; return; }
        let parsed = extractJson(text);
        if (parsed && parsed.error) { content.innerHTML = '<span class="text-warning">' + parsed.error + '</span>'; return; }
        if (parsed) { content.innerHTML = renderJson(parsed); return; }
        content.innerHTML = aiMarkdown(text);
    } catch(e) {
        loading.style.display = 'none';
        content.innerHTML = '<span class="text-danger">Gagal terhubung</span>';
    }
}

/* ========== FINANCIAL MONITOR ========== */
async function refreshMonitor() {
    const container = document.getElementById('monitorAlerts');
    const badge = document.getElementById('monitorBadge');
    container.innerHTML = '<div class="text-center py-2"><span class="text-secondary" style="font-size:0.85rem;">Memuat monitoring...</span></div>';
    try {
        const res = await fetch('ajax_ai.php?action=financial_monitor');
        const text = await res.text();
        let alerts;
        try { alerts = JSON.parse(text); } catch(e) { container.innerHTML = '<div class="text-center py-2"><span class="text-warning" style="font-size:0.85rem;">Gagal parse: ' + e.message + '</span></div>'; return; }
        if (alerts.error) { container.innerHTML = '<div class="text-center py-2"><span class="text-warning" style="font-size:0.85rem;">' + alerts.error + '</span></div>'; return; }
        badge.textContent = alerts.length;
        if (alerts.length === 0) {
            container.innerHTML = '<div style="opacity:0.5;font-size:0.85rem;"><i class="bi bi-check-circle me-1 text-accent"></i> Tidak ada peringatan keuangan. Kondisi stabil.</div>';
            return;
        }
        let html = '';
        alerts.forEach(a => {
            const color = a.type === 'danger' ? '#ef4444' : a.type === 'warning' ? '#f59e0b' : '#3b82f6';
            let txHtml = '';
            if (a.transactions && a.transactions.length > 0) {
                txHtml = '<div class="mt-1 pt-1" style="border-top:1px solid var(--border-color);">';
                a.transactions.forEach(tx => {
                    txHtml += '<div class="monitor-tx-row">';
                    txHtml += '<span>#' + tx.id + ' ' + tx.tanggal + ' — ' + (tx.keterangan || '').substring(0, 40) + '</span>';
                    txHtml += '<span class="fw-bold ms-2" style="white-space:nowrap;">' + tx.jumlah + '</span></div>';
                });
                txHtml += '</div>';
            }
            let solHtml = '';
            if (a.solution) {
                solHtml = '<div class="monitor-solution"><strong style="color:#10b981;">Solusi:</strong> <span style="opacity:0.7;">' + a.solution + '</span></div>';
            }
            html += '<div class="monitor-alert">';
            html += '<div class="d-flex align-items-start gap-2"><i class="bi ' + a.icon + ' mt-1" style="font-size:1rem;color:' + color + ';"></i>';
            html += '<div style="flex:1;min-width:0;"><div class="fw-bold" style="font-size:0.85rem;">' + a.title + '</div>';
            html += '<div style="font-size:0.8rem;opacity:0.6;">' + a.message + '</div>' + txHtml + solHtml;
            if (a.url) html += '<a href="' + a.url + '" class="mt-1 d-inline-block monitor-link">' + (a.action || 'Lihat Detail') + ' →</a>';
            html += '</div></div></div>';
        });
        container.innerHTML = html;
    } catch (e) {
        container.innerHTML = '<div style="opacity:0.4;font-size:0.85rem;">Gagal memuat monitoring</div>';
    }
}

/* ========== INIT ========== */
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('chatContainer').innerHTML = '<div class="chat-msg">Halo! Saya asisten keuangan masjid. Saya bisa membantu:<ul class="mb-0 mt-1"><li><strong>Mencatat transaksi</strong> — ketik deskripsi seperti "infak jumat 50000"</li><li><strong>Menjawab pertanyaan</strong> — tanya kondisi keuangan, saldo, dll</li><li><strong>Membuat ringkasan & narasi</strong> — klik tombol di atas</li><li><strong>Rekomendasi efisiensi</strong> — analisis pengeluaran</li></ul><div class="chat-time">Sekarang</div></div>';
    refreshMonitor();
    const hash = window.location.hash || '#tab-finance';
    const tab = hash.replace('#tab-', '');
    if (document.getElementById('tab-' + tab)) {
        switchTab(tab);
        const activeBtn = document.querySelector('.tab-btn[data-tab="' + tab + '"]');
        if (activeBtn) moveIndicator(activeBtn);
    } else {
        const firstBtn = document.querySelector('.tab-btn.active');
        if (firstBtn) moveIndicator(firstBtn);
    }
});
</script>

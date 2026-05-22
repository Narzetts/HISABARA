<div class="container-fluid py-3">
    <!-- Hero -->
    <div class="analytics-hero mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="hero-icon-box"><i class="bi bi-graph-up-arrow"></i></div>
            <div>
                <h4 class="fw-bold mb-0 text-white">HISA Analytics</h4>
                <p class="mb-0" style="color:rgba(255,255,255,0.65);font-size:0.9rem;">Analisis cerdas untuk pengambilan keputusan yang lebih tepat</p>
            </div>
        </div>
    </div>

    <!-- Tombol Analisis -->
    <div class="row g-2 mb-4" id="analyticsButtons">
        <div class="col-6 col-md">
            <button class="analytics-btn" onclick="jalanAnalisis('prediksi')" data-action="prediksi">
                <div class="ab-icon" style="background:rgba(16,185,129,0.12);color:#10b981;"><i class="bi bi-cash-stack"></i></div>
                <div class="ab-label">Prediksi Saldo</div>
                <div class="ab-desc">Estimasi saldo masa depan</div>
            </button>
        </div>
        <div class="col-6 col-md">
            <button class="analytics-btn" onclick="jalanAnalisis('ketahanan')" data-action="ketahanan">
                <div class="ab-icon" style="background:rgba(59,130,246,0.12);color:#3b82f6;"><i class="bi bi-shield-check"></i></div>
                <div class="ab-label">Ketahanan</div>
                <div class="ab-desc">Skor kesehatan finansial</div>
            </button>
        </div>
        <div class="col-6 col-md">
            <button class="analytics-btn" onclick="jalanAnalisis('anomali')" data-action="anomali">
                <div class="ab-icon" style="background:rgba(239,68,68,0.12);color:#ef4444;"><i class="bi bi-exclamation-triangle"></i></div>
                <div class="ab-label">Anomali</div>
                <div class="ab-desc">Deteksi transaksi mencurigakan</div>
            </button>
        </div>
        <div class="col-6 col-md">
            <button class="analytics-btn" onclick="jalanAnalisis('tren_keuangan')" data-action="tren_keuangan">
                <div class="ab-icon" style="background:rgba(245,158,11,0.12);color:#f59e0b;"><i class="bi bi-graph-up-arrow"></i></div>
                <div class="ab-label">Tren Keuangan</div>
                <div class="ab-desc">Pola 12 bulan terakhir</div>
            </button>
        </div>
        <div class="col-6 col-md">
            <button class="analytics-btn" onclick="jalanAnalisis('pola_transaksi')" data-action="pola_transaksi">
                <div class="ab-icon" style="background:rgba(139,92,246,0.12);color:#8b5cf6;"><i class="bi bi-binoculars"></i></div>
                <div class="ab-label">Pola Transaksi</div>
                <div class="ab-desc">Korelasi & pola tidak biasa</div>
            </button>
        </div>
    </div>

    <div class="d-flex align-items-center gap-2 mb-3">
        <hr style="flex:1;border-color:var(--border-color);opacity:.5;">
        <small class="text-secondary fw-medium" style="white-space:nowrap;">Audit & Pengawasan</small>
        <hr style="flex:1;border-color:var(--border-color);opacity:.5;">
    </div>

    <div class="row g-2 mb-4">
        <div class="col-6 col-md">
            <button class="analytics-btn" onclick="jalanAnalisis('audit')" data-action="audit">
                <div class="ab-icon" style="background:rgba(245,158,11,0.12);color:#f59e0b;"><i class="bi bi-balance-scale"></i></div>
                <div class="ab-label">Audit Neraca</div>
                <div class="ab-desc">Periksa keseimbangan debit-kredit</div>
            </button>
        </div>
        <div class="col-6 col-md">
            <button class="analytics-btn" onclick="jalanAnalisis('skor_transparansi')" data-action="skor_transparansi">
                <div class="ab-icon" style="background:rgba(59,130,246,0.12);color:#3b82f6;"><i class="bi bi-award"></i></div>
                <div class="ab-label">Skor Transparansi</div>
                <div class="ab-desc">Skor A-E transparansi laporan</div>
            </button>
        </div>
        <div class="col-6 col-md">
            <button class="analytics-btn" onclick="jalanAnalisis('kepatuhan')" data-action="kepatuhan">
                <div class="ab-icon" style="background:rgba(16,185,129,0.12);color:#10b981;"><i class="bi bi-shield-check"></i></div>
                <div class="ab-label">Evaluasi Kepatuhan</div>
                <div class="ab-desc">Kepatuhan syariah & etika</div>
            </button>
        </div>
        <div class="col-6 col-md">
            <button class="analytics-btn" onclick="jalanAnalisis('risiko_kas')" data-action="risiko_kas">
                <div class="ab-icon" style="background:rgba(239,68,68,0.12);color:#ef4444;"><i class="bi bi-shield-exclamation"></i></div>
                <div class="ab-label">Risiko Kas</div>
                <div class="ab-desc">Simulasi risiko keuangan</div>
            </button>
        </div>
        <div class="col-6 col-md">
            <button class="analytics-btn" onclick="jalanAnalisis('audit_risk')" data-action="audit_risk">
                <div class="ab-icon" style="background:rgba(139,92,246,0.12);color:#8b5cf6;"><i class="bi bi-exclamation-diamond"></i></div>
                <div class="ab-label">Risk & Fraud</div>
                <div class="ab-desc">Skor risiko & indikasi fraud</div>
            </button>
        </div>
        <div class="col-6 col-md">
            <button class="analytics-btn" onclick="jalanAnalisis('audit_timing')" data-action="audit_timing">
                <div class="ab-icon" style="background:rgba(255,159,67,0.12);color:#ff9f43;"><i class="bi bi-clock-history"></i></div>
                <div class="ab-label">Waktu Transaksi</div>
                <div class="ab-desc">Analisis pola waktu transaksi</div>
            </button>
        </div>
        <div class="col-6 col-md">
            <button class="analytics-btn" onclick="jalanAnalisis('audit_comprehensive')" data-action="audit_comprehensive">
                <div class="ab-icon" style="background:rgba(108,92,231,0.12);color:#6c5ce7;"><i class="bi bi-file-spreadsheet"></i></div>
                <div class="ab-label">Audit Komprehensif</div>
                <div class="ab-desc">Laporan audit lengkap</div>
            </button>
        </div>
    </div>

    <!-- Hasil Analisis -->
    <div id="analisisResult" style="display:none;">
        <div class="analytics-result-header" id="analisisHeader">
            <i class="bi bi-robot"></i> <span>Memilih analisis...</span>
            <button class="btn btn-sm btn-outline-secondary ms-auto" onclick="sembunyikanHasil()" style="border:none;color:var(--text-muted);"><i class="bi bi-x-lg"></i></button>
        </div>
        <div id="analisisLoading" style="display:none;padding:2rem;text-align:center;">
            <div class="analytics-spinner"><div></div><div></div><div></div></div>
            <p class="text-secondary mt-2 mb-0" id="analisisLoadingText">Menganalisis data...</p>
        </div>
        <div id="analisisContent"></div>
    </div>

    <!-- Placeholder -->
    <div id="analisisPlaceholder">
        <div class="analytics-empty">
            <div class="empty-icon"><i class="bi bi-graph-up"></i></div>
            <h5 class="fw-bold mb-1">Pilih Analisis</h5>
            <p class="text-secondary mb-3">Klik salah satu tombol di atas untuk memulai analisis keuangan</p>
            <div class="row g-2 justify-content-center" style="max-width:700px;margin:0 auto;">
                <div class="col-4 col-md">
                    <div class="empty-hint"><i class="bi bi-cash-stack" style="color:#10b981;"></i> Prediksi Saldo</div>
                </div>
                <div class="col-4 col-md">
                    <div class="empty-hint"><i class="bi bi-shield-check" style="color:#3b82f6;"></i> Ketahanan</div>
                </div>
                <div class="col-4 col-md">
                    <div class="empty-hint"><i class="bi bi-exclamation-triangle" style="color:#ef4444;"></i> Anomali</div>
                </div>
                <div class="col-4 col-md">
                    <div class="empty-hint"><i class="bi bi-graph-up-arrow" style="color:#f59e0b;"></i> Tren</div>
                </div>
                <div class="col-4 col-md">
                    <div class="empty-hint"><i class="bi bi-binoculars" style="color:#8b5cf6;"></i> Pola</div>
                </div>
                <div class="col-4 col-md">
                    <div class="empty-hint"><i class="bi bi-balance-scale" style="color:#f59e0b;"></i> Audit</div>
                </div>
                <div class="col-4 col-md">
                    <div class="empty-hint"><i class="bi bi-shield-exclamation" style="color:#ef4444;"></i> Risiko</div>
                </div>
                <div class="col-4 col-md">
                    <div class="empty-hint"><i class="bi bi-file-spreadsheet" style="color:#6c5ce7;"></i> Komprehensif</div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.analytics-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    padding: 1.5rem 2rem;
    border-radius: 18px;
    position: relative;
    overflow: hidden;
}
.analytics-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(16,185,129,0.08) 0%, transparent 70%);
    border-radius: 50%;
}
.hero-icon-box {
    width: 52px;
    height: 52px;
    background: rgba(16,185,129,0.15);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: #10b981;
    flex-shrink: 0;
}

.analytics-btn {
    width: 100%;
    border: 1px solid var(--border-color);
    border-radius: 14px;
    padding: 1rem 0.75rem;
    background: var(--bg-card);
    text-align: center;
    cursor: pointer;
    transition: all 0.25s ease;
    position: relative;
    overflow: hidden;
}
.analytics-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    border-color: var(--accent);
}
.analytics-btn:active { transform: translateY(-1px); }
.analytics-btn .ab-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    margin: 0 auto 0.5rem;
    transition: transform 0.25s;
}
.analytics-btn:hover .ab-icon { transform: scale(1.1); }
.analytics-btn .ab-label { font-weight: 700; font-size: 0.85rem; color: var(--text-primary); }
.analytics-btn .ab-desc { font-size: 0.7rem; color: var(--text-muted); margin-top: 0.15rem; }

.analytics-btn.active {
    border-color: var(--accent);
    background: rgba(16,185,129,0.04);
    box-shadow: 0 0 0 2px rgba(16,185,129,0.15);
}

.analytics-result-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.85rem 1.25rem;
    background: linear-gradient(135deg, rgba(16,185,129,0.08), rgba(59,130,246,0.05));
    border: 1px solid var(--border-color);
    border-bottom: none;
    border-radius: 16px 16px 0 0;
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--text-primary);
}
.analytics-result-header i:first-child { color: var(--accent); font-size: 1.1rem; }

.analytics-spinner {
    display: inline-flex;
    gap: 6px;
}
.analytics-spinner div {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--accent);
    animation: analyticsBounce 1.2s infinite;
}
.analytics-spinner div:nth-child(2) { animation-delay: 0.15s; }
.analytics-spinner div:nth-child(3) { animation-delay: 0.3s; }
@keyframes analyticsBounce {
    0%, 60%, 100% { transform: translateY(0); opacity: 0.3; }
    30% { transform: translateY(-8px); opacity: 1; }
}

.analytics-empty {
    text-align: center;
    padding: 3rem 1rem;
    border: 2px dashed var(--border-color);
    border-radius: 16px;
    background: var(--bg-card);
}
.analytics-empty .empty-icon {
    font-size: 3rem;
    color: var(--text-muted);
    opacity: 0.3;
    margin-bottom: 0.75rem;
}
.empty-hint {
    padding: 0.4rem 0.5rem;
    border-radius: 8px;
    background: var(--bg-body);
    font-size: 0.78rem;
    color: var(--text-secondary);
    font-weight: 500;
}
.empty-hint i { margin-right: 0.3rem; }

.pola-card {
    border: 1px solid var(--border-color);
    border-radius: 14px;
    margin-bottom: 1rem;
    background: var(--bg-card);
    overflow: hidden;
    transition: box-shadow 0.2s;
}
.pola-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.06); }
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
}
.pola-field:last-child { border-bottom: none; }
.pola-field-icon {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 0.9rem;
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
.pola-field-value {
    font-size: 0.88rem;
    color: var(--text-primary);
    line-height: 1.55;
}
.pola-judul { display: none; }
</style>

<script>
const analisisLabels = {
    prediksi: { icon: 'bi-cash-stack', label: 'Prediksi Saldo', load: 'Menganalisis tren pemasukan dan pengeluaran...' },
    ketahanan: { icon: 'bi-shield-check', label: 'Ketahanan Finansial', load: 'Menghitung skor kesehatan keuangan...' },
    anomali: { icon: 'bi-exclamation-triangle', label: 'Deteksi Anomali', load: 'Memeriksa indikasi transaksi tidak wajar...' },
    tren_keuangan: { icon: 'bi-graph-up-arrow', label: 'Analisis Tren Keuangan', load: 'Menganalisis pola 12 bulan terakhir...' },
    pola_transaksi: { icon: 'bi-binoculars', label: 'Pola Transaksi', load: 'Mencari korelasi dan pola tidak biasa...' },
    audit: { icon: 'bi-balance-scale', label: 'Audit Neraca Saldo', load: 'Memeriksa keseimbangan debit dan kredit...' },
    skor_transparansi: { icon: 'bi-award', label: 'Skor Transparansi', load: 'Menghitung skor transparansi laporan...' },
    kepatuhan: { icon: 'bi-shield-check', label: 'Evaluasi Kepatuhan', load: 'Menganalisis kepatuhan syariah dan etika...', input: true },
    risiko_kas: { icon: 'bi-shield-exclamation', label: 'Analisis Risiko Kas', load: 'Menjalankan simulasi risiko keuangan...' },
    audit_risk: { icon: 'bi-exclamation-diamond', label: 'Risk Score & Fraud', load: 'Menghitung skor risiko dan indikasi fraud...' },
    audit_timing: { icon: 'bi-clock-history', label: 'Analisis Waktu Transaksi', load: 'Menganalisis pola waktu transaksi...' },
    audit_comprehensive: { icon: 'bi-file-spreadsheet', label: 'Audit Komprehensif', load: 'Menyusun laporan audit lengkap...' }
};

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
    text = text.replace(/(<li>.*<\/li>(\n)?)/gs, function(m) {
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

function renderJson(parsed) {
    function renderItem(ik, iv) {
        const ilabel = ik.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
        if (ik === 'judul') {
            return '<div class="pola-judul">' + ilabel.replace('Judul','') + iv + '</div>';
        }
        const icons = { deskripsi:'bi-info-circle', dampak:'bi-exclamation-diamond', saran:'bi-lightbulb', rekomendasi:'bi-check-circle', alasan:'bi-chat-quote', risiko:'bi-shield-exclamation', peluang:'bi-arrow-up-circle' };
        const icon = icons[ik] || 'bi-dot';
        return '<div class="pola-field"><div class="pola-field-icon"><i class="bi ' + icon + '"></i></div><div class="pola-field-body"><div class="pola-field-label">' + ilabel + '</div><div class="pola-field-value">' + aiMarkdown(String(iv)) + '</div></div></div>';
    }

    let html = '<div style="padding:1.25rem;border:1px solid var(--border-color);border-top:none;border-radius:0 0 16px 16px;background:var(--bg-card);">';
    if (Array.isArray(parsed)) {
        for (const item of parsed) {
            html += '<div class="pola-card">';
            if (typeof item === 'object') {
                const judul = item.judul || '';
                html += '<div class="pola-card-header"><i class="bi bi-binoculars"></i> ' + judul + '</div>';
                html += '<div class="pola-card-body">';
                for (const [ik, iv] of Object.entries(item)) {
                    if (ik === 'judul') continue;
                    html += renderItem(ik, iv);
                }
                html += '</div></div>';
            } else {
                html += '<div style="padding:0.75rem;">' + aiMarkdown(String(item)) + '</div></div>';
            }
        }
    } else {
        for (const [k, v] of Object.entries(parsed)) {
            const label = k.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
            if (typeof v === 'object' && Array.isArray(v)) {
                for (const item of v) {
                    html += '<div class="pola-card">';
                    if (typeof item === 'object') {
                        const judul = item.judul || label + ' #' + (Object.keys(v).indexOf(k)+1);
                        html += '<div class="pola-card-header"><i class="bi bi-binoculars"></i> ' + judul + '</div>';
                        html += '<div class="pola-card-body">';
                        for (const [ik, iv] of Object.entries(item)) {
                            if (ik === 'judul') continue;
                            html += renderItem(ik, iv);
                        }
                        html += '</div></div>';
                    } else {
                        html += '<div style="padding:0.75rem;">' + aiMarkdown(String(item)) + '</div></div>';
                    }
                }
            } else {
                html += '<div class="mb-2 p-3" style="border-left:4px solid var(--accent,#10b981);border-radius:0 8px 8px 0;background:rgba(16,185,129,0.04);">' + aiMarkdown(String(v)) + '</div>';
            }
        }
    }
    html += '</div>';
    return html;
}

function sembunyikanHasil() {
    document.getElementById('analisisResult').style.display = 'none';
    document.getElementById('analisisPlaceholder').style.display = 'block';
    document.querySelectorAll('.analytics-btn').forEach(b => b.classList.remove('active'));
}

async function jalanAnalisis(action) {
    const result = document.getElementById('analisisResult');
    const header = document.getElementById('analisisHeader');
    const loading = document.getElementById('analisisLoading');
    const content = document.getElementById('analisisContent');
    const placeholder = document.getElementById('analisisPlaceholder');

    const info = analisisLabels[action] || { icon: 'bi-robot', label: action, load: 'Menganalisis...' };

    if (info.input) {
        const input = prompt('Masukkan keputusan/langkah yang akan dievaluasi:');
        if (!input) return;
        info.inputValue = input;
    }

    document.querySelectorAll('.analytics-btn').forEach(b => b.classList.remove('active'));
    const btn = document.querySelector('.analytics-btn[data-action="' + action + '"]');
    if (btn) btn.classList.add('active');

    header.innerHTML = '<i class="bi ' + info.icon + '"></i> <span>' + info.label + '</span><button class="btn btn-sm btn-outline-secondary ms-auto" onclick="sembunyikanHasil()" style="border:none;color:var(--text-muted);"><i class="bi bi-x-lg"></i></button>';
    document.getElementById('analisisLoadingText').textContent = info.load;

    result.style.display = 'block';
    placeholder.style.display = 'none';
    loading.style.display = 'block';
    content.innerHTML = '';

    try {
        let url = 'ajax_ai.php?action=' + action;
        if (info.inputValue) url += '&keputusan=' + encodeURIComponent(info.inputValue);
        const res = await fetch(url);
        const text = await res.text();
        loading.style.display = 'none';
        let parsed = extractJson(text);
        if (parsed && parsed.error) { content.innerHTML = '<div style="padding:1.25rem;border:1px solid var(--border-color);border-top:none;border-radius:0 0 16px 16px;background:var(--bg-card);"><span class="text-warning">' + parsed.error + '</span></div>'; return; }
        if (parsed) { content.innerHTML = renderJson(parsed); return; }
        content.innerHTML = '<div style="padding:1.25rem;border:1px solid var(--border-color);border-top:none;border-radius:0 0 16px 16px;background:var(--bg-card);">' + aiMarkdown(text) + '</div>';
    } catch(e) {
        loading.style.display = 'none';
        content.innerHTML = '<div style="padding:1.25rem;border:1px solid var(--border-color);border-top:none;border-radius:0 0 16px 16px;background:var(--bg-card);"><span class="text-danger">Gagal terhubung</span></div>';
    }
}
</script>

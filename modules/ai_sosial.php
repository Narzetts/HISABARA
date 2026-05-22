<div class="container-fluid py-3">
    <div class="insight-hero mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="hero-icon-box"><i class="bi bi-people"></i></div>
            <div>
                <h4 class="fw-bold mb-0 text-white">HISA Insight</h4>
                <p class="mb-0" style="color:rgba(255,255,255,0.65);font-size:0.9rem;">Informasi keuangan transparan untuk jamaah dan masyarakat umum</p>
            </div>
            <a href="public/index.php" target="_blank" class="btn btn-sm ms-auto" style="background:rgba(255,255,255,0.1);color:#fff;border:1px solid rgba(255,255,255,0.15);border-radius:10px;"><i class="bi bi-globe me-1"></i> Halaman Publik</a>
        </div>
    </div>

    <div class="row g-2 mb-3" id="insightButtons">
        <div class="col-4">
            <button class="insight-btn" onclick="jalanInsight('dampak_sosial')" data-action="dampak_sosial">
                <div class="ib-icon" style="background:rgba(236,72,153,0.12);color:#ec4899;"><i class="bi bi-graph-up-arrow"></i></div>
                <div class="ib-label">Analisis Dampak Sosial</div>
                <div class="ib-desc">Pemanfaatan dana & penerima manfaat</div>
            </button>
        </div>
        <div class="col-4">
            <button class="insight-btn" onclick="jalanInsight('ringkasan')" data-action="ringkasan">
                <div class="ib-icon" style="background:rgba(168,85,247,0.12);color:#a855f7;"><i class="bi bi-megaphone"></i></div>
                <div class="ib-label">Ringkasan Jamaah</div>
                <div class="ib-desc">Bahasa sederhana & mudah dipahami</div>
            </button>
        </div>
        <div class="col-4">
            <button class="insight-btn" onclick="jalanInsight('cerita_dana')" data-action="cerita_dana">
                <div class="ib-icon" style="background:rgba(249,115,22,0.12);color:#f97316;"><i class="bi bi-journal-text"></i></div>
                <div class="ib-label">Narasi Penggunaan Dana</div>
                <div class="ib-desc">Perjalanan & pencapaian keuangan</div>
            </button>
        </div>
    </div>

    <div id="insightResult" style="display:none;">
        <div class="insight-result-header" id="insightResultHeader">
            <i class="bi bi-robot"></i> <span>Memuat...</span>
            <button class="btn btn-sm ms-auto" onclick="sembunyikanHasil()" style="border:none;color:var(--text-muted);"><i class="bi bi-x-lg"></i></button>
        </div>
        <div id="insightLoading" style="display:none;padding:2rem;text-align:center;">
            <div class="insight-spinner"><div></div><div></div><div></div></div>
            <p class="text-secondary mt-2 mb-0" id="insightLoadingText">Memproses...</p>
        </div>
        <div id="insightContent"></div>
    </div>

    <div id="insightPlaceholder">
        <div class="insight-empty">
            <div class="empty-icon"><i class="bi bi-people"></i></div>
            <h5 class="fw-bold mb-1">Pilih Informasi</h5>
            <p class="text-secondary mb-3">Klik salah satu tombol di atas untuk menampilkan data keuangan</p>
            <div class="row g-2 justify-content-center" style="max-width:500px;margin:0 auto;">
                <div class="col-4"><div class="empty-hint"><i class="bi bi-graph-up-arrow" style="color:#ec4899;"></i> Dampak Sosial</div></div>
                <div class="col-4"><div class="empty-hint"><i class="bi bi-megaphone" style="color:#a855f7;"></i> Ringkasan</div></div>
                <div class="col-4"><div class="empty-hint"><i class="bi bi-journal-text" style="color:#f97316;"></i> Narasi Dana</div></div>
            </div>
        </div>
    </div>
</div>

<style>
.insight-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    padding: 1.5rem 2rem;
    border-radius: 18px;
    position: relative;
    overflow: hidden;
}
.insight-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(236,72,153,0.08) 0%, transparent 70%);
    border-radius: 50%;
}
.insight-hero .hero-icon-box {
    width: 52px; height: 52px;
    background: rgba(236,72,153,0.15);
    border-radius: 16px;
    display: flex;
    align-items: center; justify-content: center;
    font-size: 1.5rem;
    color: #ec4899;
    flex-shrink: 0;
}

.insight-btn {
    width: 100%;
    border: 1px solid var(--border-color);
    border-radius: 14px;
    padding: 1.15rem 0.75rem;
    background: var(--bg-card);
    text-align: center;
    cursor: pointer;
    transition: all 0.25s ease;
}
.insight-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    border-color: #ec4899;
}
.insight-btn:active { transform: translateY(-1px); }
.insight-btn .ib-icon {
    width: 48px; height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center; justify-content: center;
    font-size: 1.3rem;
    margin: 0 auto 0.6rem;
    transition: transform 0.25s;
}
.insight-btn:hover .ib-icon { transform: scale(1.1); }
.insight-btn .ib-label { font-weight: 700; font-size: 0.85rem; color: var(--text-primary); }
.insight-btn .ib-desc { font-size: 0.7rem; color: var(--text-muted); margin-top: 0.15rem; }
.insight-btn.active {
    border-color: #ec4899;
    background: rgba(236,72,153,0.04);
    box-shadow: 0 0 0 2px rgba(236,72,153,0.15);
}

.insight-result-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.85rem 1.25rem;
    background: linear-gradient(135deg, rgba(236,72,153,0.08), rgba(168,85,247,0.05));
    border: 1px solid var(--border-color);
    border-bottom: none;
    border-radius: 16px 16px 0 0;
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--text-primary);
}
.insight-result-header i:first-child { color: #ec4899; font-size: 1.1rem; }

.insight-spinner {
    display: inline-flex;
    gap: 6px;
}
.insight-spinner div {
    width: 10px; height: 10px;
    border-radius: 50%;
    background: #ec4899;
    animation: insightBounce 1.2s infinite;
}
.insight-spinner div:nth-child(2) { animation-delay: 0.15s; }
.insight-spinner div:nth-child(3) { animation-delay: 0.3s; }
@keyframes insightBounce {
    0%, 60%, 100% { transform: translateY(0); opacity: 0.3; }
    30% { transform: translateY(-8px); opacity: 1; }
}

.insight-empty {
    text-align: center;
    padding: 3rem 1rem;
    border: 2px dashed var(--border-color);
    border-radius: 16px;
    background: var(--bg-card);
}
.insight-empty .empty-icon {
    font-size: 3rem;
    color: var(--text-muted);
    opacity: 0.3;
    margin-bottom: 0.75rem;
}
.insight-empty .empty-hint {
    padding: 0.4rem 0.5rem;
    border-radius: 8px;
    background: var(--bg-body);
    font-size: 0.78rem;
    color: var(--text-secondary);
    font-weight: 500;
}
.insight-empty .empty-hint i { margin-right: 0.3rem; }
</style>

<script>
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

const insightLabels = {
    dampak_sosial: { icon: 'bi-graph-up-arrow', label: 'Analisis Dampak Sosial', load: 'Menganalisis dampak program sosial...' },
    ringkasan: { icon: 'bi-megaphone', label: 'Ringkasan Jamaah', load: 'Membuat ringkasan untuk jamaah...' },
    cerita_dana: { icon: 'bi-journal-text', label: 'Narasi Penggunaan Dana', load: 'Menyusun narasi perjalanan dana...' }
};

function renderInsightJson(parsed) {
    let html = '<div style="padding:1.25rem;border:1px solid var(--border-color);border-top:none;border-radius:0 0 16px 16px;background:var(--bg-card);">';
    for (const [k, v] of Object.entries(parsed)) {
        const label = k.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
        if (typeof v === 'object' && Array.isArray(v)) {
            html += '<div class="mb-3"><strong class="fs-6" style="color:#ec4899;">' + label + '</strong>';
            for (const item of v) {
                html += '<div style="padding:0.65rem 0.85rem;border-left:3px solid #ec4899;margin:0.4rem 0;background:rgba(236,72,153,0.04);border-radius:0 8px 8px 0;">';
                if (typeof item === 'object') {
                    for (const [ik, iv] of Object.entries(item)) {
                        const ilabel = ik.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
                        html += '<div class="mb-1"><strong>' + ilabel + ':</strong> ' + aiMarkdown(String(iv)) + '</div>';
                    }
                } else {
                    html += aiMarkdown(String(item));
                }
                html += '</div>';
            }
            html += '</div>';
        } else {
            const isSkor = k === 'skor' || k === 'skor_dampak' || k === 'estimasi_penerima';
            const badgeColor = isSkor ? '#ec4899' : 'transparent';
            const bgColor = isSkor ? 'rgba(236,72,153,0.06)' : 'transparent';
            html += '<div class="mb-2 p-3" style="border-left:4px solid ' + badgeColor + ';border-radius:0 8px 8px 0;background:' + bgColor + ';"><strong>' + label + ':</strong> ' + aiMarkdown(String(v)) + '</div>';
        }
    }
    html += '</div>';
    return html;
}

function sembunyikanHasil() {
    document.getElementById('insightResult').style.display = 'none';
    document.getElementById('insightPlaceholder').style.display = 'block';
    document.querySelectorAll('.insight-btn').forEach(b => b.classList.remove('active'));
}

async function jalanInsight(action) {
    const info = insightLabels[action];
    if (!info) return;

    document.querySelectorAll('.insight-btn').forEach(b => b.classList.remove('active'));
    const btn = document.querySelector('.insight-btn[data-action="' + action + '"]');
    if (btn) btn.classList.add('active');

    const result = document.getElementById('insightResult');
    const header = document.getElementById('insightResultHeader');
    const loading = document.getElementById('insightLoading');
    const content = document.getElementById('insightContent');
    const placeholder = document.getElementById('insightPlaceholder');

    header.innerHTML = '<i class="bi ' + info.icon + '"></i> <span>' + info.label + '</span><button class="btn btn-sm ms-auto" onclick="sembunyikanHasil()" style="border:none;color:var(--text-muted);"><i class="bi bi-x-lg"></i></button>';
    document.getElementById('insightLoadingText').textContent = info.load;

    result.style.display = 'block';
    placeholder.style.display = 'none';
    loading.style.display = 'block';
    content.innerHTML = '';

    try {
        const res = await fetch('ajax_ai.php?action=' + action);
        const text = await res.text();
        loading.style.display = 'none';

        let parsed;
        try { parsed = JSON.parse(text); } catch(e) { parsed = null; }
        if (parsed && parsed.error) { content.innerHTML = '<div style="padding:1.25rem;border:1px solid var(--border-color);border-top:none;border-radius:0 0 16px 16px;background:var(--bg-card);"><span class="text-warning">' + parsed.error + '</span></div>'; return; }
        if (parsed) {
            content.innerHTML = renderInsightJson(parsed);
        } else {
            content.innerHTML = '<div style="padding:1.25rem;border:1px solid var(--border-color);border-top:none;border-radius:0 0 16px 16px;background:var(--bg-card);">' + aiMarkdown(text) + '</div>';
        }
    } catch(e) {
        loading.style.display = 'none';
        content.innerHTML = '<div style="padding:1.25rem;border:1px solid var(--border-color);border-top:none;border-radius:0 0 16px 16px;background:var(--bg-card);"><span class="text-danger">Gagal terhubung</span></div>';
    }
}
</script>

<div class="container-fluid py-3">
    <div class="d-flex align-items-center gap-3 mb-4">
        <div style="width:48px;height:48px;border-radius:14px;background:#d1fae5;display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:#10b981;"><i class="bi bi-wallet2"></i></div>
        <div>
            <h4 class="fw-bold mb-1">HISA Finance</h4>
            <p class="text-secondary small mb-0">Asisten AI keuangan masjid — tanya, catat, ringkas, dan analisis dalam satu layar</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span><i class="bi bi-robot text-accent me-1"></i> Tanya & Catat Keuangan</span>
            <div class="d-flex gap-1 flex-wrap">
                <button class="btn btn-sm btn-outline-accent" onclick="quickChat('Buat ringkasan keuangan')"><i class="bi bi-lightbulb"></i> Ringkasan</button>
                <button class="btn btn-sm btn-outline-accent" onclick="quickChat('Buat narasi laporan keuangan')"><i class="bi bi-journal-text"></i> Narasi</button>
                <button class="btn btn-sm btn-outline-accent" onclick="quickChat('Beri rekomendasi efisiensi')"><i class="bi bi-piggy-bank"></i> Efisiensi</button>
                <button class="btn btn-sm btn-outline-accent" onclick="quickChat('Bagaimana kondisi keuangan?')"><i class="bi bi-bar-chart"></i> Analisis</button>
            </div>
        </div>
        <div class="card-body">
            <div class="chat-container mb-3" id="chatContainer" style="max-height:500px;overflow-y:auto;padding:0.5rem;scroll-behavior:smooth;">
                <div class="chat-msg chat-ai" style="max-width:90%;margin-bottom:0.75rem;padding:0.75rem 1rem;background:var(--bg-body);border-radius:14px 14px 14px 4px;border:1px solid var(--border-color);font-size:0.88rem;line-height:1.7;">
                    Halo! Saya asisten keuangan masjid. Saya bisa membantu:
                    <ul class="mb-0 mt-1">
                        <li><strong>Mencatat transaksi</strong> — ketik deskripsi seperti "infak jumat 50000"</li>
                        <li><strong>Menjawab pertanyaan</strong> — tanya kondisi keuangan, saldo, dll</li>
                        <li><strong>Membuat ringkasan & narasi</strong> — klik tombol di atas</li>
                        <li><strong>Rekomendasi efisiensi</strong> — analisis pengeluaran</li>
                    </ul>
                    <div class="chat-time" style="font-size:0.65rem;color:var(--text-muted);margin-top:0.35rem;opacity:0.7;">Sekarang</div>
                </div>
            </div>
            <div class="input-group">
                <input type="text" id="chatInput" class="form-control" placeholder="Deskripsikan transaksi, tanya keuangan, atau minta ringkasan..." onkeypress="if(event.key==='Enter') sendChat()">
                <button class="btn btn-accent" onclick="sendChat()"><i class="bi bi-send"></i></button>
            </div>
        </div>
    </div>

    <!-- Confirm Popup (reuse from transaksi.php style) -->
    <div id="aiParsePopup" class="ai-overlay" style="display:none;" onclick="if(event.target===this) tutupParse()">
        <div class="ai-confirm">
            <div class="ai-confirm-glow"></div>
            <div class="ai-confirm-body">
                <div class="ai-confirm-icon masuk" id="aiParseIcon"><i class="bi bi-check-lg"></i></div>
                <h5 class="fw-bold mb-1">Konfirmasi & Edit Transaksi</h5>
                <p class="small text-secondary mb-2">Hasil analisis AI — bisa diedit sebelum ditambahkan:</p>
                <table class="ai-confirm-table w-100">
                    <tr><td>Jenis</td><td><select id="aiPJenis" class="form-select form-select-sm"><option value="Pemasukan">Pemasukan</option><option value="Pengeluaran">Pengeluaran</option></select></td></tr>
                    <tr><td>Jumlah</td><td style="position:relative;"><span style="position:absolute;left:8px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:0.85rem;z-index:5;">Rp</span><input type="text" id="aiPJumlah" class="form-control" style="padding-left:28px;font-weight:700;color:var(--accent);"></td></tr>
                    <tr><td>Keterangan</td><td><input type="text" id="aiPKeterangan" class="form-control"></td></tr>
                    <tr><td>Akun</td><td><select id="aiPAkun" class="form-select form-select-sm"></select></td></tr>
                    <tr><td>Tanggal</td><td><input type="date" id="aiPTanggal" class="form-control"></td></tr>
                </table>
            </div>
            <div class="ai-confirm-footer">
                <button class="btn btn-outline-secondary" onclick="tutupParse()">Batal</button>
                <button class="btn btn-accent" id="aiParseBtn" onclick="konfirmasiParse()"><i class="bi bi-check-lg"></i> Tambahkan Transaksi</button>
            </div>
        </div>
    </div>
</div>

<style>
.chat-container::-webkit-scrollbar { width: 4px; }
.chat-container::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 4px; }
.chat-msg:last-child { animation: msgSlide 0.25s ease; }
@keyframes msgSlide { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:translateY(0); } }
@keyframes chatTyping { 0%,60%,100% { opacity:0.3; transform:scale(0.8); } 30% { opacity:1; transform:scale(1.2); } }
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

function escapeHtml(t) {
    const d = document.createElement('div'); d.textContent = t; return d.innerHTML;
}

function now() {
    const d = new Date();
    return d.getHours().toString().padStart(2,'0') + ':' + d.getMinutes().toString().padStart(2,'0');
}

function quickChat(msg) {
    document.getElementById('chatInput').value = msg; sendChat();
}

async function sendChat() {
    const input = document.getElementById('chatInput');
    const msg = input.value.trim();
    if (!msg) return;
    const container = document.getElementById('chatContainer');
    container.innerHTML += '<div class="chat-msg" style="max-width:90%;margin-bottom:0.75rem;padding:0.75rem 1rem;background:linear-gradient(135deg,var(--accent),#059669);color:white;margin-left:auto;border-radius:14px 14px 4px 14px;font-size:0.88rem;line-height:1.7;">' + escapeHtml(msg) + '<div class="chat-time" style="font-size:0.65rem;margin-top:0.35rem;opacity:0.7;color:rgba(255,255,255,0.6);">' + now() + '</div></div>';
    input.value = '';
    container.innerHTML += '<div class="chat-msg chat-ai" id="aiLoading" style="max-width:90%;margin-bottom:0.75rem;padding:0.75rem 1rem;background:var(--bg-body);border-radius:14px 14px 14px 4px;border:1px solid var(--border-color);font-size:0.88rem;line-height:1.7;"><div style="display:flex;align-items:center;gap:0.5rem;"><div style="display:flex;gap:3px;"><span style="width:6px;height:6px;border-radius:50%;background:var(--accent);animation:chatTyping 1.4s infinite;"></span><span style="width:6px;height:6px;border-radius:50%;background:var(--accent);animation:chatTyping 1.4s infinite;animation-delay:0.2s;"></span><span style="width:6px;height:6px;border-radius:50%;background:var(--accent);animation:chatTyping 1.4s infinite;animation-delay:0.4s;"></span></div> <span>Memproses...</span></div></div>';
    container.scrollTop = container.scrollHeight;
    try {
        const res = await fetch('ajax_ai.php?action=chat_keuangan&pesan=' + encodeURIComponent(msg));
        const text = await res.text();
        let parsed;
        try { parsed = JSON.parse(text); } catch(e) { parsed = null; }
        const aiEl = document.getElementById('aiLoading');
        if (parsed && parsed.error) {
            aiEl.outerHTML = '<div class="chat-msg chat-ai" style="max-width:90%;margin-bottom:0.75rem;padding:0.75rem 1rem;background:var(--bg-body);border-radius:14px 14px 14px 4px;border:1px solid var(--border-color);font-size:0.88rem;line-height:1.7;"><span class="text-warning">' + parsed.error + '</span></div>';
        } else if (parsed && parsed._parse) {
            // Parse result — show confirm popup
            const icon = document.getElementById('aiParseIcon');
            const jenis = parsed.jenis === 'Pemasukan' ? 'Pemasukan' : 'Pengeluaran';
            icon.className = 'ai-confirm-icon ' + (jenis === 'Pemasukan' ? 'masuk' : 'keluar');
            icon.innerHTML = jenis === 'Pemasukan' ? '<i class="bi bi-arrow-down-circle"></i>' : '<i class="bi bi-arrow-up-circle"></i>';
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
                if (parsed.akun_id && akunSel.querySelector('option[value="' + parsed.akun_id + '"]')) {
                    akunSel.value = parsed.akun_id;
                }
            }
            aiEl.outerHTML = '<div class="chat-msg chat-ai" style="max-width:90%;margin-bottom:0.75rem;padding:0.75rem 1rem;background:var(--bg-body);border-radius:14px 14px 14px 4px;border:1px solid var(--border-color);font-size:0.88rem;line-height:1.7;">Transaksi berhasil dipahami. Klik "Tambahkan Transaksi" untuk memasukkan atau edit dulu jika perlu.</div>';
            document.getElementById('aiParsePopup').style.display = 'flex';
        } else if (parsed) {
            let html = '';
            for (const [k, v] of Object.entries(parsed)) {
                const label = k.replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase());
                if (typeof v === 'object' && Array.isArray(v)) {
                    for (const item of v) {
                        if (typeof item === 'object') {
                            for (const [ik, iv] of Object.entries(item)) {
                                html += '<div class="mb-1"><strong>' + ik.replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase()) + ':</strong> ' + aiMarkdown(String(iv)) + '</div>';
                            }
                        }
                    }
                } else {
                    html += '<div class="mb-1"><strong>' + label + ':</strong> ' + aiMarkdown(String(v)) + '</div>';
                }
            }
            aiEl.outerHTML = '<div class="chat-msg chat-ai" style="max-width:90%;margin-bottom:0.75rem;padding:0.75rem 1rem;background:var(--bg-body);border-radius:14px 14px 14px 4px;border:1px solid var(--border-color);font-size:0.88rem;line-height:1.7;">' + html + '<div class="chat-time" style="font-size:0.65rem;color:var(--text-muted);margin-top:0.35rem;opacity:0.7;">' + now() + '</div></div>';
        } else {
            aiEl.outerHTML = '<div class="chat-msg chat-ai" style="max-width:90%;margin-bottom:0.75rem;padding:0.75rem 1rem;background:var(--bg-body);border-radius:14px 14px 14px 4px;border:1px solid var(--border-color);font-size:0.88rem;line-height:1.7;">' + aiMarkdown(text) + '<div class="chat-time" style="font-size:0.65rem;color:var(--text-muted);margin-top:0.35rem;opacity:0.7;">' + now() + '</div></div>';
        }
    } catch(e) {
        document.getElementById('aiLoading').outerHTML = '<div class="chat-msg chat-ai" style="max-width:90%;margin-bottom:0.75rem;padding:0.75rem 1rem;background:var(--bg-body);border-radius:14px 14px 14px 4px;border:1px solid var(--border-color);font-size:0.88rem;line-height:1.7;"><span class="text-danger">Gagal terhubung</span></div>';
    }
    container.scrollTop = container.scrollHeight;
}

function tutupParse() {
    document.getElementById('aiParsePopup').style.display = 'none';
}

function konfirmasiParse() {
    const jenis = document.getElementById('aiPJenis').value;
    const jumlah = document.getElementById('aiPJumlah').value.replace(/[^\d]/g, '');
    const keterangan = document.getElementById('aiPKeterangan').value;
    const akun_id = document.getElementById('aiPAkun').value;
    const tanggal = document.getElementById('aiPTanggal').value;
    if (!jenis || !jumlah || !akun_id) return;
    tutupParse();
    // Fill the main form and redirect to transaksi page
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

document.addEventListener('DOMContentLoaded', function() {
    // If redirected from ai_keuangan with params, fill transaksi form
    const params = new URLSearchParams(window.location.search);
    if (params.get('page') === 'transaksi' && params.get('ai_fill')) {
        // Auto-fill will be handled by the transaksi page
    }
});
</script>

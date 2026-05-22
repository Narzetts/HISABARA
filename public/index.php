<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

$nama_masjid = get_setting('nama_masjid') ?: 'Masjid';
$alamat_masjid = get_setting('alamat_masjid') ?: '';

$keuangan = get_total_saldo();
$grafik = get_data_grafik_publik();
$labels = []; $pemasukan = []; $pengeluaran = [];
foreach ($grafik as $g) {
    $labels[] = format_tanggal($g['bulan'] . '-01');
    $pemasukan[] = (float)$g['pemasukan'];
    $pengeluaran[] = (float)$g['pengeluaran'];
}

// Public budget progress
$pdo = get_connection();
$dana_khusus = get_all_dana_khusus();
$total_aset = get_total_aset();
$health_score = get_financial_health_score();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publik - <?= sanitize($nama_masjid) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="../images/logo/logo.png">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        body { background: #f8fafc; }
        .hero-section {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            padding: 3rem 0;
            text-align: center;
            color: white;
        }
        .hero-section h1 { font-size: 2rem; font-weight: 700; }
        .hero-section p { color: #94a3b8; font-size: 1rem; }
        .hero-icon {
            width: 70px; height: 70px;
            background: rgba(16,185,129,0.15);
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
            color: #10b981;
        }
        .info-card {
            text-align: center;
            padding: 1.5rem;
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            height: 100%;
        }
        .info-card .info-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 0.75rem;
            font-size: 1.3rem;
        }
        .info-card .info-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
        }
        .info-card .info-label {
            font-size: 0.85rem;
            color: #64748b;
        }
        .footer-section {
            background: #0f172a;
            color: #94a3b8;
            text-align: center;
            padding: 1.5rem;
            font-size: 0.85rem;
        }
        .fund-card {
            border-left: 4px solid #10b981;
        }
        .chat-container::-webkit-scrollbar { width: 4px; }
        .chat-container::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        @keyframes chatTyping {
            0%, 60%, 100% { opacity: 0.3; transform: scale(0.8); }
            30% { opacity: 1; transform: scale(1.2); }
        }
    </style>
</head>
<body>

    <div class="hero-section">
        <div class="container">
            <div class="hero-icon"><img src="../images/icon/mosque.png" alt="Masjid" style="width:100%;height:100%;object-fit:contain;padding:12px;"></div>
            <h1><?= sanitize($nama_masjid) ?></h1>
            <p><?= sanitize($alamat_masjid) ?></p>
        </div>
    </div>

    <div class="container py-4">
        <!-- Overview Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6">
                <div class="info-card">
                    <div class="info-icon" style="background:#d1fae5;color:#10b981;"><i class="bi bi-wallet2"></i></div>
                    <div class="info-value" style="color:#10b981;"><?= format_rupiah($keuangan['saldo_kas']) ?></div>
                    <div class="info-label">Total Saldo Kas</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="info-card">
                    <div class="info-icon" style="background:#dbeafe;color:#3b82f6;"><i class="bi bi-arrow-down-circle"></i></div>
                    <div class="info-value" style="color:#3b82f6;"><?= format_rupiah($keuangan['pemasukan']) ?></div>
                    <div class="info-label">Total Pemasukan</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="info-card">
                    <div class="info-icon" style="background:#fee2e2;color:#ef4444;"><i class="bi bi-arrow-up-circle"></i></div>
                    <div class="info-value" style="color:#ef4444;"><?= format_rupiah($keuangan['pengeluaran']) ?></div>
                    <div class="info-label">Total Pengeluaran</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="info-card">
                    <div class="info-icon" style="background:#ede9fe;color:#8b5cf6;"><i class="bi bi-building"></i></div>
                    <div class="info-value" style="color:#8b5cf6;"><?= format_rupiah($total_aset) ?></div>
                    <div class="info-label">Total Aset Masjid</div>
                </div>
            </div>
        </div>

        <!-- Grafik -->
        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-graph-up me-1"></i> Grafik Keuangan</div>
            <div class="card-body">
                <div style="height: 300px;">
                    <canvas id="chartPublik"
                        data-labels='<?= json_encode($labels) ?>'
                        data-pemasukan='<?= json_encode($pemasukan) ?>'
                        data-pengeluaran='<?= json_encode($pengeluaran) ?>'>
                    </canvas>
                </div>
            </div>
        </div>

        <!-- Tanya AI -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-robot text-accent me-1"></i> Tanya Keuangan Masjid
                <small class="text-secondary ms-2">Tanya apa pun tentang keuangan masjid</small>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-1 mb-3">
                    <button class="btn btn-sm btn-outline-accent" onclick="quickChat('Berapa total saldo kas?')">Saldo Kas</button>
                    <button class="btn btn-sm btn-outline-accent" onclick="quickChat('Buat ringkasan keuangan')">Ringkasan</button>
                    <button class="btn btn-sm btn-outline-accent" onclick="quickChat('Ceritakan penggunaan dana')">Cerita Dana</button>
                    <button class="btn btn-sm btn-outline-accent" onclick="quickChat('Apa program sosial yang didanai?')">Dampak Sosial</button>
                </div>
                <div class="chat-container mb-3" id="chatContainer" style="max-height:420px;overflow-y:auto;padding:0.5rem;scroll-behavior:smooth;">
                    <div class="chat-msg chat-ai" style="max-width:90%;margin-bottom:0.75rem;padding:0.75rem 1rem;background:var(--bg-body);border-radius:14px 14px 14px 4px;border:1px solid var(--border-color);font-size:0.88rem;line-height:1.7;">
                        Halo! Saya asisten keuangan <?= sanitize($nama_masjid) ?>. Tanya apa pun tentang keuangan masjid, atau klik pertanyaan cepat di atas.
                        <div class="chat-time" style="font-size:0.65rem;color:var(--text-muted);margin-top:0.35rem;opacity:0.7;">Sekarang</div>
                    </div>
                </div>
                <div class="input-group">
                    <input type="text" id="chatInput" class="form-control" placeholder="Tanya tentang keuangan masjid..." onkeypress="if(event.key==='Enter') sendChat()">
                    <button class="btn btn-accent" onclick="sendChat()"><i class="bi bi-send"></i></button>
                </div>
            </div>
        </div>

        <!-- Dana Khusus -->
        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-piggy-bank me-1"></i> Program & Dana Khusus</div>
            <div class="card-body">
                <div class="row g-3">
                    <?php foreach ($dana_khusus as $dk):
                        $saldo_dk = hitung_saldo_dana_khusus($dk['id']);
                    ?>
                    <div class="col-md-4 col-6">
                        <div class="info-card fund-card">
                            <div class="info-label"><?= sanitize($dk['nama_dana']) ?></div>
                            <div class="info-value" style="font-size:1.2rem;color:#10b981;"><?= format_rupiah($saldo_dk) ?></div>
                            <small class="text-secondary"><?= sanitize($dk['deskripsi']) ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Program informasi -->
        <div class="card">
            <div class="card-header"><i class="bi bi-info-circle me-1"></i> Program Masjid</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start gap-3 p-3 rounded-3" style="background:#f0fdf4;">
                            <div style="font-size:1.5rem;color:#10b981;"><i class="bi bi-heart"></i></div>
                            <div>
                                <h6 class="mb-1">Program Infak & Sedekah</h6>
                                <p class="mb-0 small text-secondary">Salurkan infak dan sedekah terbaik Anda untuk kemakmuran masjid.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start gap-3 p-3 rounded-3" style="background:#fef3c7;">
                            <div style="font-size:1.5rem;color:#f59e0b;"><i class="bi bi-building"></i></div>
                            <div>
                                <h6 class="mb-1">Program Pembangunan</h6>
                                <p class="mb-0 small text-secondary">Dukung pembangunan dan renovasi fasilitas masjid.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start gap-3 p-3 rounded-3" style="background:#e0f2fe;">
                            <div style="font-size:1.5rem;color:#3b82f6;"><i class="bi bi-book"></i></div>
                            <div>
                                <h6 class="mb-1">Program Pendidikan</h6>
                                <p class="mb-0 small text-secondary">Program belajar mengaji dan kajian rutin untuk jamaah.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start gap-3 p-3 rounded-3" style="background:#ede9fe;">
                            <div style="font-size:1.5rem;color:#8b5cf6;"><i class="bi bi-people"></i></div>
                            <div>
                                <h6 class="mb-1">Program Sosial</h6>
                                <p class="mb-0 small text-secondary">Bantuan untuk anak yatim dan dhuafa sekitar masjid.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="footer-section">
        <p class="mb-0">&copy; <?= date('Y') ?> <?= sanitize($nama_masjid) ?> | Hisabara</p>
        <small class="text-muted">Transparansi dan akuntabilitas pengelolaan dana masjid</small>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/chart.js"></script>
    <script>
    function aiMarkdown(text) {
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
        container.innerHTML += '<div class="chat-msg" style="max-width:90%;margin-bottom:0.75rem;padding:0.75rem 1rem;background:linear-gradient(135deg,var(--accent),#059669);color:white;margin-left:auto;border-radius:14px 14px 4px 14px;font-size:0.88rem;line-height:1.7;box-shadow:0 1px 2px rgba(0,0,0,0.04);">' + escapeHtml(msg) + '<div class="chat-time" style="font-size:0.65rem;margin-top:0.35rem;opacity:0.7;color:rgba(255,255,255,0.6);">' + now() + '</div></div>';
        input.value = '';
        container.innerHTML += '<div class="chat-msg chat-ai" id="aiLoading" style="max-width:90%;margin-bottom:0.75rem;padding:0.75rem 1rem;background:var(--bg-body);border-radius:14px 14px 14px 4px;border:1px solid var(--border-color);font-size:0.88rem;line-height:1.7;"><div style="display:flex;align-items:center;gap:0.5rem;"><div style="display:flex;gap:3px;"><span style="width:6px;height:6px;border-radius:50%;background:var(--accent);animation:chatTyping 1.4s infinite;"></span><span style="width:6px;height:6px;border-radius:50%;background:var(--accent);animation:chatTyping 1.4s infinite;animation-delay:0.2s;"></span><span style="width:6px;height:6px;border-radius:50%;background:var(--accent);animation:chatTyping 1.4s infinite;animation-delay:0.4s;"></span></div> <span>Mengetik...</span></div></div>';
        container.scrollTop = container.scrollHeight;
        try {
            const res = await fetch('ajax.php?action=chat&pesan=' + encodeURIComponent(msg));
            const text = await res.text();
            let parsed;
            try { parsed = JSON.parse(text); } catch(e) { parsed = null; }
            const aiEl = document.getElementById('aiLoading');
            if (parsed && parsed.error) {
                aiEl.outerHTML = '<div class="chat-msg chat-ai" style="max-width:90%;margin-bottom:0.75rem;padding:0.75rem 1rem;background:var(--bg-body);border-radius:14px 14px 14px 4px;border:1px solid var(--border-color);font-size:0.88rem;line-height:1.7;"><span class="text-warning">' + parsed.error + '</span></div>';
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
    </script>
</body>
</html>

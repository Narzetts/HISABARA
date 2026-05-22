<?php require_once 'config/ai.php'; ?>

<style>
.chat-container { max-height: 500px; overflow-y: auto; padding: 0.75rem; scroll-behavior: smooth; }
.chat-container::-webkit-scrollbar { width: 4px; }
.chat-container::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 4px; }
.chat-msg { max-width: 82%; margin-bottom: 1rem; padding: 0.75rem 1rem; font-size: 0.88rem; line-height: 1.7; position: relative; box-shadow: 0 1px 2px rgba(0,0,0,0.04); }
.chat-msg strong, .chat-msg b { font-weight: 700; }
.chat-msg em { font-style: italic; }
.chat-msg code { background: rgba(16,185,129,0.1); padding: 0.1rem 0.4rem; border-radius: 4px; font-size: 0.85em; }
.chat-msg ul, .chat-msg ol { padding-left: 1.25rem; margin-bottom: 0.25rem; }
.chat-msg li { margin-bottom: 0.15rem; }
.chat-msg blockquote { border-left: 3px solid var(--accent); padding-left: 0.65rem; margin: 0.35rem 0; color: var(--text-secondary); }
.chat-msg p { margin-bottom: 0.35rem; }
.chat-msg p:last-child { margin-bottom: 0; }
.chat-user { background: linear-gradient(135deg, var(--accent), #059669); color: white; margin-left: auto; border-radius: 14px 14px 4px 14px; }
.chat-ai { background: var(--bg-body); color: var(--text-primary); border-radius: 14px 14px 14px 4px; border: 1px solid var(--border-color); }
.chat-time { font-size: 0.65rem; color: var(--text-muted); margin-top: 0.35rem; opacity: 0.7; }
.chat-user .chat-time { color: rgba(255,255,255,0.6); }
.chat-msg:last-child { animation: msgIn 0.25s ease; }
@keyframes msgIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
.chat-ai-loading { display: flex; align-items: center; gap: 0.5rem; }
.chat-ai-loading .typing-dots { display: flex; gap: 3px; }
.chat-ai-loading .typing-dots span { width: 6px; height: 6px; border-radius: 50%; background: var(--accent); animation: chatTyping 1.4s infinite; }
.chat-ai-loading .typing-dots span:nth-child(2) { animation-delay: 0.2s; }
.chat-ai-loading .typing-dots span:nth-child(3) { animation-delay: 0.4s; }
@keyframes chatTyping {
    0%, 60%, 100% { opacity: 0.3; transform: scale(0.8); }
    30% { opacity: 1; transform: scale(1.2); }
}
</style>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-robot text-accent me-1"></i> Asisten Keuangan</span>
        <small class="text-secondary">Tanya apa pun tentang keuangan masjid</small>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-12">
                <div class="d-flex flex-wrap gap-1">
                    <button class="btn btn-sm btn-outline-accent" onclick="quickChat('Berapa total saldo kas saat ini?')">Saldo Kas</button>
                    <button class="btn btn-sm btn-outline-accent" onclick="quickChat('Pengeluaran terbesar bulan ini apa?')">Pengeluaran Terbesar</button>
                    <button class="btn btn-sm btn-outline-accent" onclick="quickChat('Bagaimana kondisi keuangan masjid saat ini?')">Kondisi Keuangan</button>
                    <button class="btn btn-sm btn-outline-accent" onclick="quickChat('Beri rekomendasi penghematan')">Rekomendasi</button>
                </div>
            </div>
        </div>

        <div class="chat-container mb-3" id="chatContainer">
            <div class="chat-msg chat-ai">
                Halo! Saya asisten keuangan masjid. Tanyakan seputar keuangan, atau klik pertanyaan cepat di atas.
                <div class="chat-time">Sekarang</div>
            </div>
        </div>

        <div class="input-group">
            <input type="text" id="chatInput" class="form-control" placeholder="Tanya tentang keuangan masjid..." onkeypress="if(event.key==='Enter') sendChat()">
            <button class="btn btn-accent" onclick="sendChat()"><i class="bi bi-send"></i></button>
        </div>
    </div>
</div>

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

function quickChat(msg) {
    document.getElementById('chatInput').value = msg;
    sendChat();
}

async function sendChat() {
    const input = document.getElementById('chatInput');
    const msg = input.value.trim();
    if (!msg) return;

    const container = document.getElementById('chatContainer');
    container.innerHTML += '<div class="chat-msg chat-user">' + escapeHtml(msg) + '<div class="chat-time text-white-50">' + now() + '</div></div>';
    input.value = '';

    container.innerHTML += '<div class="chat-msg chat-ai" id="aiLoading"><div class="chat-ai-loading"><div class="typing-dots"><span></span><span></span><span></span></div> <span>Sedang mengetik...</span></div></div>';
    container.scrollTop = container.scrollHeight;

    try {
        const res = await fetch('ajax_ai.php?action=chat&pesan=' + encodeURIComponent(msg) + '&stream=1');
        if (res.body) {
            const reader = res.body.getReader();
            const decoder = new TextDecoder();
            const aiEl = document.getElementById('aiLoading');
            aiEl.outerHTML = '<div class="chat-msg chat-ai" id="aiStreaming"></div>';
            const streamingEl = document.getElementById('aiStreaming');
            let fullText = '';
            while (true) {
                const { done, value } = await reader.read();
                if (done) break;
                fullText += decoder.decode(value, { stream: true });
                streamingEl.innerHTML = aiMarkdown(fullText);
            }
            streamingEl.innerHTML = aiMarkdown(fullText) + '<div class="chat-time">' + now() + '</div>';
            streamingEl.id = '';
        } else {
            const text = await res.text();
            document.getElementById('aiLoading').outerHTML = '<div class="chat-msg chat-ai">' + aiMarkdown(text) + '<div class="chat-time">' + now() + '</div></div>';
        }
    } catch (e) {
        document.getElementById('aiLoading').outerHTML = '<div class="chat-msg chat-ai"><span class="text-danger">Gagal terhubung</span></div>';
    }
    container.scrollTop = container.scrollHeight;
}

function escapeHtml(t) {
    const d = document.createElement('div');
    d.textContent = t;
    return d.innerHTML;
}

function now() {
    const d = new Date();
    return d.getHours().toString().padStart(2,'0') + ':' + d.getMinutes().toString().padStart(2,'0');
}
</script>

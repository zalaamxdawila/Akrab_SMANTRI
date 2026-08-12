document.addEventListener('DOMContentLoaded', function () {
    const chatbotHTML = `
        <section id="akrab-chatbot" role="dialog" aria-modal="false" aria-labelledby="chatbot-title" hidden class="shadow-lg" style="position:fixed;bottom:30px;right:30px;width:350px;max-width:90vw;background:white;border-radius:15px;overflow:hidden;z-index:1050;border:1px solid #dee2e6;">
            <header class="bg-primary text-white p-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2"><i data-lucide="message-circle-question" aria-hidden="true"></i><h2 id="chatbot-title" class="h6 mb-0 fw-bold">Asisten Informasi AKRAB</h2></div>
                <button type="button" id="close-chatbot" class="btn-close btn-close-white" aria-label="Tutup asisten informasi"></button>
            </header>
            <div id="chatbot-messages" role="log" aria-live="polite" aria-relevant="additions" class="p-3" style="height:300px;overflow-y:auto;background:#f8f9fa;">
                <div class="mb-3"><div class="bg-white p-2 rounded shadow-sm d-inline-block" style="max-width:85%;font-size:.9rem;border-left:3px solid #0d6efd;">Halo! Saya asisten informasi otomatis, bukan dokter dan tidak memberikan diagnosis. Saya dapat berbagi informasi umum tentang anemia, gizi, dan TTD.</div></div>
                <div class="alert alert-warning py-2 small">Jika mengalami sesak napas, pingsan, nyeri dada, perdarahan berat, atau kondisi memburuk, segera hubungi orang dewasa tepercaya dan fasilitas kesehatan.</div>
            </div>
            <div class="p-2 border-top bg-white d-flex gap-2">
                <label for="chatbot-input" class="visually-hidden">Pertanyaan untuk asisten informasi</label>
                <input type="text" id="chatbot-input" maxlength="300" class="form-control form-control-sm rounded-pill" placeholder="Tanyakan informasi umum..." autocomplete="off">
                <button type="button" id="chatbot-send" aria-label="Kirim pertanyaan" class="btn btn-primary btn-sm rounded-circle d-flex justify-content-center align-items-center" style="width:32px;height:32px;"><i data-lucide="send" aria-hidden="true"></i></button>
            </div>
        </section>
        <button type="button" id="toggle-chatbot" aria-label="Buka asisten informasi AKRAB" aria-expanded="false" class="btn btn-primary rounded-circle shadow-lg d-flex justify-content-center align-items-center" style="position:fixed;bottom:30px;right:30px;width:60px;height:60px;z-index:1049;"><i data-lucide="message-circle-question" aria-hidden="true"></i></button>`;

    document.body.insertAdjacentHTML('beforeend', chatbotHTML);
    if (typeof lucide !== 'undefined') lucide.createIcons();

    const box = document.getElementById('akrab-chatbot');
    const toggle = document.getElementById('toggle-chatbot');
    const close = document.getElementById('close-chatbot');
    const input = document.getElementById('chatbot-input');
    const send = document.getElementById('chatbot-send');
    const messages = document.getElementById('chatbot-messages');

    const openDialog = () => { box.hidden = false; toggle.hidden = true; toggle.setAttribute('aria-expanded', 'true'); input.focus(); };
    const closeDialog = () => { box.hidden = true; toggle.hidden = false; toggle.setAttribute('aria-expanded', 'false'); toggle.focus(); };
    toggle.addEventListener('click', openDialog);
    close.addEventListener('click', closeDialog);
    box.addEventListener('keydown', event => { if (event.key === 'Escape') closeDialog(); });

    const replyFor = text => {
        const query = text.toLowerCase();
        if (/pingsan|sesak|nyeri dada|darah banyak|perdarahan/.test(query)) return 'Keluhan tersebut dapat memerlukan pertolongan segera. Hubungi orang dewasa tepercaya dan fasilitas kesehatan sekarang. Asisten ini tidak dapat menilai tingkat kegawatan.';
        if (/lemas|lemah|lesu|pucat|pusing|capek/.test(query)) return 'Keluhan tersebut dapat memiliki banyak penyebab dan tidak cukup untuk menetapkan anemia. Sampaikan kepada petugas UKS atau tenaga kesehatan untuk penilaian yang tepat.';
        if (/anemia|kurang darah/.test(query)) return 'Anemia adalah kondisi ketika hemoglobin atau sel darah merah berada di bawah kebutuhan tubuh. Diagnosis memerlukan penilaian tenaga kesehatan dan, bila diperlukan, pemeriksaan darah.';
        if (/gizi|makan|sayur|buah/.test(query)) return 'Pola makan beragam dengan sumber zat besi dan vitamin C membantu menjaga kesehatan. Ikuti anjuran petugas UKS atau tenaga kesehatan yang mengetahui kondisi Anda.';
        if (/ttd|tablet|tambah darah/.test(query)) return 'Gunakan Tablet Tambah Darah sesuai program sekolah atau petunjuk tenaga kesehatan. Jangan mengubah dosis berdasarkan jawaban asisten ini.';
        if (/sakit|uks|bantuan/.test(query)) return 'Jika merasa tidak sehat, beri tahu guru, orang tua, atau petugas UKS. Untuk kondisi berat atau memburuk, cari pertolongan medis segera.';
        return 'Saya hanya menyediakan informasi umum tentang anemia, gizi remaja, dan TTD. Saya bukan dokter dan tidak dapat memberikan diagnosis atau dosis pribadi.';
    };

    const addMessage = (text, isUser) => {
        const row = document.createElement('div');
        row.className = 'mb-3 ' + (isUser ? 'text-end' : '');
        const bubble = document.createElement('div');
        bubble.className = (isUser ? 'bg-primary text-white' : 'bg-white') + ' p-2 rounded shadow-sm d-inline-block text-start';
        bubble.style.maxWidth = '85%';
        bubble.style.fontSize = '.9rem';
        bubble.textContent = text;
        row.appendChild(bubble);
        messages.appendChild(row);
        messages.scrollTop = messages.scrollHeight;
    };

    const handleSend = () => {
        const text = input.value.trim();
        if (!text) return;
        addMessage(text, true);
        input.value = '';
        addMessage(replyFor(text), false);
        input.focus();
    };
    send.addEventListener('click', handleSend);
    input.addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); handleSend(); } });
});

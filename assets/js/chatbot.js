document.addEventListener('DOMContentLoaded', function() {
    // Inject Chatbot UI into DOM
    const chatbotHTML = `
        <div id="akrab-chatbot" class="shadow-lg" style="position: fixed; bottom: 30px; right: 30px; width: 350px; max-width: 90vw; background: white; border-radius: 15px; overflow: hidden; display: none; z-index: 1050; border: 1px solid #dee2e6;">
            <div class="bg-primary text-white p-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i data-lucide="bot" style="width: 24px; height: 24px;"></i>
                    <h6 class="mb-0 fw-bold">Dokter AI AKRAB</h6>
                </div>
                <button id="close-chatbot" class="btn-close btn-close-white" style="font-size: 0.8rem;"></button>
            </div>
            <div id="chatbot-messages" class="p-3" style="height: 300px; overflow-y: auto; background: #f8f9fa;">
                <div class="mb-3">
                    <div class="bg-white p-2 rounded shadow-sm d-inline-block" style="max-width: 85%; font-size: 0.9rem; border-left: 3px solid #0d6efd;">
                        Halo! Saya Dokter AI AKRAB. Ada yang bisa saya bantu terkait Anemia, gizi, atau jadwal minum TTD hari ini?
                    </div>
                </div>
            </div>
            <div class="p-2 border-top bg-white d-flex gap-2">
                <input type="text" id="chatbot-input" class="form-control form-control-sm rounded-pill" placeholder="Tanya sesuatu..." autocomplete="off">
                <button id="chatbot-send" class="btn btn-primary btn-sm rounded-circle d-flex justify-content-center align-items-center" style="width: 32px; height: 32px;">
                    <i data-lucide="send" style="width: 16px; height: 16px; margin-left: -2px;"></i>
                </button>
            </div>
        </div>
        
        <button id="toggle-chatbot" class="btn btn-primary rounded-circle shadow-lg d-flex justify-content-center align-items-center" style="position: fixed; bottom: 30px; right: 30px; width: 60px; height: 60px; z-index: 1049;">
            <i data-lucide="message-circle-question" style="width: 28px; height: 28px;"></i>
        </button>
    `;
    
    document.body.insertAdjacentHTML('beforeend', chatbotHTML);
    lucide.createIcons();

    const chatbotBox = document.getElementById('akrab-chatbot');
    const toggleBtn = document.getElementById('toggle-chatbot');
    const closeBtn = document.getElementById('close-chatbot');
    const input = document.getElementById('chatbot-input');
    const sendBtn = document.getElementById('chatbot-send');
    const msgContainer = document.getElementById('chatbot-messages');

    // Toggle logic
    toggleBtn.addEventListener('click', () => {
        chatbotBox.style.display = 'block';
        toggleBtn.style.display = 'none';
        input.focus();
    });

    closeBtn.addEventListener('click', () => {
        chatbotBox.style.display = 'none';
        toggleBtn.style.display = 'flex';
    });

    // Utils
    const escapeHTML = (str) => str.replace(/[&<>'"]/g, 
        tag => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#39;',
            '"': '&quot;'
        }[tag] || tag)
    );

    const parseMarkdown = (text) => {
        let parsed = escapeHTML(text);
        parsed = parsed.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        parsed = parsed.replace(/\n/g, '<br>');
        return parsed;
    };

    // Chat logic using Robust Keyword-Based AI (Local Fallback)
    const fetchPollinationsAI = async (text) => {
        // Karena API Pollinations sering down/berbayar, kita gunakan AI Lokal cerdas
        return new Promise((resolve) => {
            setTimeout(() => {
                const query = text.toLowerCase();
                let response = "Maaf, saat ini saya hanya difokuskan untuk menjawab seputar **Anemia, Gizi Remaja, dan Tablet Tambah Darah (TTD)**. Bisa tolong perjelas pertanyaanmu? (Contoh: 'apa itu anemia', 'makanan sehat', atau 'gejala lemes')";

                if (query.includes('halo') || query.includes('hai') || query.includes('pagi') || query.includes('siang')) {
                    response = "Halo! Saya Dokter AI AKRAB. Ada yang bisa saya bantu terkait **Anemia**, **gizi**, atau **jadwal minum TTD** hari ini?";
                } else if (query.includes('lemes') || query.includes('lemah') || query.includes('lesu') || query.includes('pucat') || query.includes('pusing') || query.includes('capek')) {
                    response = "Gejala yang kamu sebutkan (lemes, pusing, pucat) adalah ciri-ciri umum **Anemia** atau kurang darah.\n\nApakah kamu sudah rutin minum Tablet Tambah Darah (TTD) minggu ini? Pastikan juga perbanyak istirahat dan makan makanan bergizi ya!";
                } else if (query.includes('anemia') || query.includes('kurang darah')) {
                    response = "**Anemia** adalah kondisi ketika tubuh kekurangan sel darah merah yang sehat. Bagi remaja putri, ini sangat sering terjadi akibat menstruasi bulanan.\n\nDampaknya bisa mengganggu konsentrasi belajar dan membuatmu mudah sakit. Pencegahan utamanya adalah minum **1 butir TTD** setiap minggu.";
                } else if (query.includes('gizi') || query.includes('makan') || query.includes('sayur') || query.includes('buah') || query.includes('laper') || query.includes('lapar')) {
                    response = "Untuk mencegah anemia dan tetap fokus belajar, kamu butuh asupan **Zat Besi** dan **Vitamin C**.\n\nMakanan terbaik yang disarankan UKS: hati ayam, daging merah, telur, bayam, kangkung, dan jeruk. Jangan lupa minum air putih minimal 8 gelas sehari!";
                } else if (query.includes('ttd') || query.includes('tablet') || query.includes('tambah darah') || query.includes('obat') || query.includes('pil') || query.includes('minum')) {
                    response = "**Tablet Tambah Darah (TTD)** wajib diminum **1 butir setiap minggu** bagi remaja putri.\n\n⚠️ **Penting:** Jangan diminum bersamaan dengan TEH, KOPI, atau SUSU karena akan menghambat penyerapan zat besi. Gunakan air putih matang atau jus jeruk agar hasilnya maksimal.";
                } else if (query.includes('terima kasih') || query.includes('makasih') || query.includes('ok') || query.includes('baik')) {
                    response = "Sama-sama! Jangan ragu untuk bertanya lagi jika kamu butuh bantuan. Jaga kesehatanmu selalu ya! 🩺😊";
                } else if (query.includes('uks') || query.includes('sakit')) {
                    response = "Jika kamu merasa sangat sakit dan tidak sanggup belajar, segera lapor ke guru piket dan kunjungi ruang **UKS** sekarang juga agar mendapat penanganan langsung dari petugas.";
                }

                resolve(response);
            }, 600); // Simulasi waktu mikir AI (600ms)
        });
    };

    const addMessage = (text, isUser = false) => {
        const div = document.createElement('div');
        div.className = 'mb-3 ' + (isUser ? 'text-end' : '');
        
        const formattedText = isUser ? escapeHTML(text) : parseMarkdown(text);
        
        let bubble = '';
        if (isUser) {
            bubble = `<div class="bg-primary text-white p-2 rounded shadow-sm d-inline-block text-start" style="max-width: 85%; font-size: 0.9rem;">${formattedText}</div>`;
        } else {
            bubble = `<div class="bg-white p-2 rounded shadow-sm d-inline-block text-start" style="max-width: 85%; font-size: 0.9rem; border-left: 3px solid #0d6efd;">${formattedText}</div>`;
        }
        
        div.innerHTML = bubble;
        msgContainer.appendChild(div);
        msgContainer.scrollTop = msgContainer.scrollHeight;
    };

    const handleSend = async () => {
        const text = input.value.trim();
        if (!text) return;
        
        addMessage(text, true);
        input.value = '';
        input.focus();

        // Simulate thinking animation (Optional: add a "typing..." bubble, but for now we just wait)
        const loadingDiv = document.createElement('div');
        loadingDiv.id = 'ai-typing';
        loadingDiv.className = 'mb-3';
        loadingDiv.innerHTML = `<div class="bg-white p-2 rounded shadow-sm d-inline-block text-start text-muted" style="font-size: 0.8rem; font-style: italic;">Sedang mengetik...</div>`;
        msgContainer.appendChild(loadingDiv);
        msgContainer.scrollTop = msgContainer.scrollHeight;

        const reply = await fetchPollinationsAI(text);
        
        // Remove typing indicator
        const typingEl = document.getElementById('ai-typing');
        if (typingEl) typingEl.remove();

        addMessage(reply, false);
    };

    sendBtn.addEventListener('click', handleSend);
    input.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') handleSend();
    });
});

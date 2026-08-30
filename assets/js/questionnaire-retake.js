(() => {
    'use strict';

    document.querySelectorAll('[data-questionnaire-retake-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!form.checkValidity()) {
                event.preventDefault();
                form.reportValidity();
                return;
            }

            const studentName = form.dataset.studentName || 'siswa ini';
            const confirmed = window.confirm(
                `Aktifkan pengisian ulang untuk ${studentName}? `
                + 'Hasil sebelumnya tetap tersimpan sebagai riwayat pribadi.'
            );
            if (!confirmed) {
                event.preventDefault();
            }
        });
    });
})();

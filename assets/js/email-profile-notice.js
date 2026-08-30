(() => {
    'use strict';

    const notice = document.querySelector('[data-email-profile-notice]');
    if (!notice) return;

    const closeButton = notice.querySelector('[data-email-notice-close]');
    const profileLink = document.querySelector('[data-email-profile-link]');
    const storageKey = notice.dataset.storageKey;

    try {
        if (storageKey && sessionStorage.getItem(storageKey) === 'dismissed') {
            notice.hidden = true;
        }
    } catch (_) {
        // Storage can be unavailable in privacy-restricted browsers.
    }

    if (!closeButton) return;

    closeButton.addEventListener('click', () => {
        notice.hidden = true;
        try {
            if (storageKey) sessionStorage.setItem(storageKey, 'dismissed');
        } catch (_) {
            // The notice still closes for this page even when storage is unavailable.
        }
        if (profileLink instanceof HTMLElement) profileLink.focus();
    });
})();

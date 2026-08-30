// PWA registration. Updates never force-reload a page, so an in-progress form is safe.
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js?v=20260831-safe-install', { updateViaCache: 'none' }).catch(() => {
            // The website remains usable when service-worker registration is unavailable.
        });
    });
}

// Inject install metadata on legacy pages that do not declare it in their head.
const manifestLink = document.createElement('link');
manifestLink.rel = 'manifest';
manifestLink.href = '/manifest.json?v=20260831-safe-install';
if (!document.querySelector('link[rel="manifest"]')) document.head.appendChild(manifestLink);

const themeColor = document.createElement('meta');
themeColor.name = 'theme-color';
themeColor.content = '#047857';
if (!document.querySelector('meta[name="theme-color"]')) document.head.appendChild(themeColor);

const appleTouchIcon = document.createElement('link');
appleTouchIcon.rel = 'apple-touch-icon';
appleTouchIcon.href = '/assets/icons/apple-touch-icon.png';
if (!document.querySelector('link[rel="apple-touch-icon"]')) document.head.appendChild(appleTouchIcon);

let installPrompt = null;
const standaloneDisplay = window.matchMedia('(display-mode: standalone)');
const isIosDevice = /iphone|ipad|ipod/i.test(navigator.userAgent)
    || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

function isAppInstalled() {
    return standaloneDisplay.matches || navigator.standalone === true;
}

function installElements() {
    return {
        buttons: Array.from(document.querySelectorAll('[data-install-app]')),
        status: document.querySelector('[data-install-status]'),
        help: document.querySelector('[data-install-help]'),
        helpTitle: document.getElementById('installHelpTitle')
    };
}

function setInstallButtonsVisible(visible) {
    installElements().buttons.forEach(button => {
        button.hidden = !visible;
    });
}

function setInstallStatus(message) {
    const { status } = installElements();
    if (status) status.textContent = message;
}

function showIosInstallHelp() {
    const { help, helpTitle } = installElements();
    if (!help) return;
    help.hidden = false;
    if (helpTitle) helpTitle.focus();
}

async function requestAppInstall() {
    if (isAppInstalled()) {
        setInstallButtonsVisible(false);
        setInstallStatus('AKRAB sudah terpasang di perangkat ini.');
        return;
    }

    if (!installPrompt) {
        if (isIosDevice) {
            showIosInstallHelp();
        } else {
            setInstallStatus('Gunakan menu browser lalu pilih Pasang aplikasi atau Tambahkan ke layar utama.');
        }
        return;
    }

    installPrompt.prompt();
    const choice = await installPrompt.userChoice;
    installPrompt = null;
    if (choice.outcome === 'accepted') {
        setInstallButtonsVisible(false);
        setInstallStatus('AKRAB sedang dipasang.');
    } else {
        setInstallStatus('Pemasangan dibatalkan. Anda dapat mencobanya lagi kapan saja.');
    }
}

window.addEventListener('beforeinstallprompt', event => {
    event.preventDefault();
    installPrompt = event;
    setInstallButtonsVisible(true);
});

window.addEventListener('appinstalled', () => {
    installPrompt = null;
    setInstallButtonsVisible(false);
    setInstallStatus('AKRAB berhasil dipasang. Buka melalui ikon di layar utama.');
});

// Theme is applied before the page finishes loading to avoid a light-mode flash.
const htmlElement = document.documentElement;
const systemTheme = window.matchMedia('(prefers-color-scheme: dark)');

function readSavedTheme() {
    try {
        const savedTheme = localStorage.getItem('theme');
        return ['light', 'dark'].includes(savedTheme) ? savedTheme : null;
    } catch (error) {
        return null;
    }
}

function applyTheme(theme) {
    htmlElement.setAttribute('data-bs-theme', theme);
    htmlElement.style.colorScheme = theme;
    document.dispatchEvent(new CustomEvent('akrab:themechange', {
        detail: { theme }
    }));
}

applyTheme(readSavedTheme() || (systemTheme.matches ? 'dark' : 'light'));

// Accessibility helpers and navbar theme control.
document.addEventListener('DOMContentLoaded', () => {
    const installUi = installElements();
    installUi.buttons.forEach(button => {
        button.addEventListener('click', requestAppInstall);
    });
    const closeInstallHelp = document.querySelector('[data-install-help-close]');
    if (closeInstallHelp && installUi.help) {
        closeInstallHelp.addEventListener('click', () => {
            installUi.help.hidden = true;
            installUi.buttons[0]?.focus();
        });
    }
    if (isAppInstalled()) {
        setInstallButtonsVisible(false);
        setInstallStatus('AKRAB sudah terpasang di perangkat ini.');
    } else if (isIosDevice) {
        setInstallButtonsVisible(true);
    }

    const mainContent = document.querySelector('main, body > .container');
    if (mainContent && !document.getElementById('main-content')) {
        mainContent.id = 'main-content';
        mainContent.setAttribute('tabindex', '-1');
        const skipLink = document.createElement('a');
        skipLink.href = '#main-content';
        skipLink.className = 'skip-link';
        skipLink.textContent = 'Lewati ke konten utama';
        document.body.insertBefore(skipLink, document.body.firstChild);
    }

    // Inject one accessible toggle into any navbar shape used by the app.
    const navbar = document.querySelector('.navbar');
    const navbarNav = navbar ? navbar.querySelector('.navbar-nav') : null;
    const navbarToggler = navbar ? navbar.querySelector('.navbar-toggler') : null;
    if (navbarToggler) {
        const targetId = (navbarToggler.getAttribute('data-bs-target') || '').replace('#', '');
        if (targetId) navbarToggler.setAttribute('aria-controls', targetId);
        navbarToggler.setAttribute('aria-expanded', 'false');
        navbarToggler.setAttribute('aria-label', 'Buka atau tutup menu navigasi');
    }

    if (navbar && !document.getElementById('darkModeToggle')) {
        const toggleLi = document.createElement('li');
        toggleLi.className = 'nav-item ms-lg-2 d-flex align-items-center';

        const toggleBtn = document.createElement('button');
        toggleBtn.className = 'btn theme-toggle rounded-pill px-3 d-flex align-items-center gap-2';
        toggleBtn.id = 'darkModeToggle';
        toggleBtn.type = 'button';

        const updateBtnUI = () => {
            const isDark = htmlElement.getAttribute('data-bs-theme') === 'dark';
            toggleBtn.innerHTML = isDark
                ? '<i data-lucide="sun" aria-hidden="true"></i><span>Terang</span>'
                : '<i data-lucide="moon" aria-hidden="true"></i><span>Gelap</span>';
            toggleBtn.setAttribute('aria-label', isDark ? 'Gunakan tema terang' : 'Gunakan tema gelap');
            toggleBtn.setAttribute('aria-pressed', String(isDark));
            if (typeof lucide !== 'undefined') lucide.createIcons();
        };

        updateBtnUI();

        toggleBtn.addEventListener('click', () => {
            const currentTheme = htmlElement.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            applyTheme(newTheme);
            try {
                localStorage.setItem('theme', newTheme);
            } catch (error) {
                // Theme still works for this page when storage is unavailable.
            }
            updateBtnUI();
        });

        if (navbarNav) {
            toggleLi.appendChild(toggleBtn);
            navbarNav.appendChild(toggleLi);
        } else {
            toggleBtn.classList.add('ms-auto');
            (navbar.querySelector('.container, .container-fluid') || navbar).appendChild(toggleBtn);
        }
    }

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});

systemTheme.addEventListener('change', event => {
    if (!readSavedTheme()) {
        applyTheme(event.matches ? 'dark' : 'light');
    }
});

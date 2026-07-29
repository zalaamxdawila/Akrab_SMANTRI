// PWA Registration
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js', { updateViaCache: 'none' });
    });
}

// Inject Manifest
const manifestLink = document.createElement('link');
manifestLink.rel = 'manifest';
manifestLink.href = '/manifest.json?v=20260729';
if (!document.querySelector('link[rel="manifest"]')) document.head.appendChild(manifestLink);

// Dark Mode Logic
document.addEventListener('DOMContentLoaded', () => {
    const htmlElement = document.documentElement;
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
    
    // Load preference
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        htmlElement.setAttribute('data-bs-theme', savedTheme);
    } else {
        htmlElement.setAttribute('data-bs-theme', 'light');
    }

    // Inject Toggle Button into Navbars
    const navbarNav = document.querySelector('.navbar-nav');
    if (navbarNav) {
        const toggleLi = document.createElement('li');
        toggleLi.className = 'nav-item ms-lg-2 mt-2 mt-lg-0 d-flex align-items-center';
        
        const toggleBtn = document.createElement('button');
        toggleBtn.className = 'btn btn-outline-secondary rounded-pill px-3 d-flex align-items-center gap-2';
        toggleBtn.id = 'darkModeToggle';
        toggleBtn.type = 'button';
        
        const updateBtnUI = () => {
            const isDark = htmlElement.getAttribute('data-bs-theme') === 'dark';
            toggleBtn.innerHTML = isDark ? '<i data-lucide="sun"></i> Terang' : '<i data-lucide="moon"></i> Gelap';
            toggleBtn.setAttribute('aria-label', isDark ? 'Gunakan tema terang' : 'Gunakan tema gelap');
            toggleBtn.setAttribute('aria-pressed', String(isDark));
            if (typeof lucide !== 'undefined') lucide.createIcons();
        };
        
        updateBtnUI();
        
        toggleBtn.addEventListener('click', () => {
            const currentTheme = htmlElement.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            htmlElement.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateBtnUI();
        });
        
        toggleLi.appendChild(toggleBtn);
        navbarNav.appendChild(toggleLi);
    }

    // Initialize Lucide Icons globally just in case
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});

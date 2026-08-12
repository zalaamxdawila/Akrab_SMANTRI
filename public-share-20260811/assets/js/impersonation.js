document.querySelectorAll('[data-impersonation-countdown]').forEach((node) => {
    let remaining = Number(node.dataset.impersonationCountdown || 0);
    const update = () => {
        const minutes = Math.floor(Math.max(0, remaining) / 60);
        const seconds = Math.max(0, remaining) % 60;
        node.textContent = `${minutes}:${String(seconds).padStart(2, '0')}`;
        remaining -= 1;
    };
    update();
    window.setInterval(update, 1000);
});

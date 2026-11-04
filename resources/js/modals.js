function overlaysVisibles() {
    return Array.from(document.querySelectorAll('.fixed.inset-0')).filter(
        (el) => window.getComputedStyle(el).display !== 'none'
    );
}

document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;

    overlaysVisibles().forEach((overlay) => {
        overlay.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
    });
});

document.addEventListener('keydown', (e) => {
    if (e.key !== 'Enter') return;

    const target = e.target;
    if (target.tagName === 'TEXTAREA' || target.tagName === 'BUTTON') return;

    const overlay = target.closest('.fixed.inset-0');
    if (!overlay || window.getComputedStyle(overlay).display === 'none') return;

    const form = target.closest('form') || overlay.querySelector('form');
    if (form) {
        e.preventDefault();
        form.requestSubmit();
    }
});

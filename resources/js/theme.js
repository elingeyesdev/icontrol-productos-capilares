
(function () {
    'use strict';

    const STORAGE_KEY = 'luciane_tema';

    function aplicarTema(tema) {
        if (tema === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        } else {
            document.documentElement.removeAttribute('data-theme');
        }
    }

    function initTema() {
        const btn = document.getElementById('btnTema');
        if (!btn) return;

        btn.addEventListener('click', function () {
            const actual = document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
            const nuevo = actual === 'dark' ? 'light' : 'dark';
            aplicarTema(nuevo);
            try {
                localStorage.setItem(STORAGE_KEY, nuevo);
            } catch (e) { }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTema);
    } else {
        initTema();
    }
})();
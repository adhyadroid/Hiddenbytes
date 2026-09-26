/**
 * assets/js/search.js
 * Optional live suggestions under the landing page hero search box.
 */
(function () {
    const input = document.querySelector('.hero-search input');
    if (!input) return;
    let timer = null;
    input.addEventListener('input', function () {
        clearTimeout(timer);
        const q = input.value.trim();
        if (q.length < 2) return;
        timer = setTimeout(() => { fetch(apiBase() + 'search.php?q=' + encodeURIComponent(q)); }, 250);
    });
})();

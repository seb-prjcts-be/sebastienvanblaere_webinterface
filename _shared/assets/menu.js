(function () {
    'use strict';

    // GSAP is optioneel: zonder gsap (CDN faalt) of met reduced-motion
    // valt alles terug op de bestaande instant/CSS-gedreven UI.
    function motionOK() {
        return typeof window.gsap !== 'undefined' &&
            !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    /* === LEFT MENU — open by default, persisted in localStorage === */

    function setupMenu() {
        var toggler = document.querySelector('.menu-toggler');
        var overlay = document.querySelector('.menu-overlay');
        if (!toggler || !overlay) return null;

        function getState() {
            return document.documentElement.getAttribute('data-menu-state') || 'open';
        }

        function setState(state) {
            document.documentElement.setAttribute('data-menu-state', state);
            try { localStorage.setItem('prjcts_menu', state); } catch (e) {}
            toggler.setAttribute('aria-expanded', state === 'open' ? 'true' : 'false');
            // Icoon wordt via CSS-pseudo-element gestuurd, geen JS textContent nodig
        }

        // Sync initial UI met de state die door de inline init-script gezet is
        setState(getState());

        toggler.addEventListener('click', function (e) {
            e.preventDefault();
            setState(getState() === 'open' ? 'closed' : 'open');
        });

        return {
            open:   function () { setState('open'); },
            close:  function () { setState('closed'); },
            isOpen: function () { return getState() === 'open'; }
        };
    }

    /* === RIGHT CONSTELLATION — closed by default, klassiek class-based === */

    function setupConstellation(menu) {
        var toggler = document.querySelector('.constellation-toggler');
        var overlay = document.querySelector('.constellation-overlay');
        if (!toggler || !overlay) return null;

        function isOpen() { return overlay.classList.contains('is-open'); }
        function setOpen(open) {
            overlay.classList.toggle('is-open', open);
            document.documentElement.setAttribute('data-constellation-state', open ? 'open' : 'closed');
            toggler.setAttribute('aria-expanded', open ? 'true' : 'false');
            overlay.setAttribute('aria-hidden', open ? 'false' : 'true');
        }

        toggler.addEventListener('click', function (e) {
            e.preventDefault();
            setOpen(!isOpen());
        });

        return { open: function () { setOpen(true); }, close: function () { setOpen(false); }, isOpen: isOpen };
    }

    /* === FONT-SIZE TOOL — sync UI met state op <html> === */

    function setupFontSize() {
        function setSize(size) {
            document.documentElement.setAttribute('data-fontsize', size);
            // Schrijf naar BEIDE: localStorage (intra-site) + cookie (server-side leesbaar, basis voor cross-site sync).
            try { localStorage.setItem('prjcts_fontsize', size); } catch (e) {}
            try {
                document.cookie = 'prjcts_fontsize=' + encodeURIComponent(size) +
                    '; path=/; max-age=31536000; samesite=Lax';
            } catch (e) {}
            document.querySelectorAll('.fontsize-btn').forEach(function (b) {
                b.classList.toggle('is-active', b.dataset.size === size);
            });
        }

        var current = document.documentElement.getAttribute('data-fontsize') || 'medium';
        setSize(current);

        document.querySelectorAll('.fontsize-btn').forEach(function (btn) {
            btn.addEventListener('click', function () { setSize(btn.dataset.size); });
        });
    }

    /* === LIGHTBOX — klik op thumbnail → fullscreen overlay === */

    function setupLightbox() {
        var overlay = document.querySelector('.lightbox-overlay');
        var content = overlay && overlay.querySelector('.lightbox-content');
        var closeBtn = overlay && overlay.querySelector('.lightbox-close');
        if (!overlay || !content) return null;

        function open(src, type) {
            content.innerHTML = '';
            var el;
            if (type === 'video') {
                el = document.createElement('video');
                el.src = src;
                el.controls = true;
                el.autoplay = true;
                el.loop = true;
            } else if (type === 'audio') {
                el = document.createElement('audio');
                el.src = src;
                el.controls = true;
                el.autoplay = true;
                el.classList.add('lightbox-audio');
            } else if (type === 'pdf') {
                el = document.createElement('iframe');
                el.src = src;
                el.setAttribute('allowfullscreen', '');
                el.setAttribute('frameborder', '0');
                el.classList.add('lightbox-iframe');
            } else if (type === 'youtube') {
                el = document.createElement('iframe');
                el.src = src;
                el.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
                el.setAttribute('allowfullscreen', '');
                el.setAttribute('frameborder', '0');
                el.classList.add('lightbox-iframe');
            } else {
                el = document.createElement('img');
                el.src = src;
            }
            content.appendChild(el);
            overlay.classList.add('is-open');
            overlay.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            if (motionOK()) {
                gsap.killTweensOf([overlay, content]);
                gsap.fromTo(overlay, { opacity: 0 },
                    { opacity: 1, duration: 0.25, ease: 'power1.out', clearProps: 'opacity' });
                gsap.fromTo(content, { scale: 0.97 },
                    { scale: 1, duration: 0.35, ease: 'power2.out', clearProps: 'scale' });
            }
        }

        var closing = false;
        function close() {
            function finish() {
                closing = false;
                overlay.classList.remove('is-open');
                overlay.setAttribute('aria-hidden', 'true');
                content.innerHTML = '';
                document.body.style.overflow = '';
            }
            if (motionOK()) {
                if (closing) return;
                closing = true;
                gsap.killTweensOf([overlay, content]);
                gsap.to(overlay, {
                    opacity: 0, duration: 0.2, ease: 'power1.in',
                    clearProps: 'opacity', onComplete: finish
                });
            } else {
                finish();
            }
        }

        // klik op elk element met data-src + data-type → open lightbox
        document.addEventListener('click', function (e) {
            var t = e.target.closest('[data-src][data-type]');
            if (t) {
                e.preventDefault();
                open(t.dataset.src, t.dataset.type);
            }
        });

        if (closeBtn) closeBtn.addEventListener('click', close);
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) close();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && overlay.classList.contains('is-open')) close();
        });

        return { open: open, close: close };
    }

    /* === WORKS-GRID REVEAL — discrete stagger bij paginalaad === */

    function setupGridReveal() {
        if (!motionOK()) return;
        var items = document.querySelectorAll('.works-grid .thumbnail-container');
        if (!items.length) return;
        // Totale intro blijft kort, ook bij grote grids
        gsap.from(items, {
            autoAlpha: 0,
            y: 10,
            duration: 0.45,
            ease: 'power2.out',
            stagger: Math.min(0.06, 0.8 / items.length),
            clearProps: 'all'
        });
    }

    function init() {
        var menu = setupMenu();
        var constellation = setupConstellation(menu);
        setupFontSize();
        setupLightbox();
        setupGridReveal();

        // Esc sluit elke open paneel
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            if (constellation && constellation.isOpen()) constellation.close();
            else if (menu && menu.isOpen()) menu.close();
        });

        // Markeer huidige pagina in linker menu — inclusief query (anders matcht /about álle about-links)
        var herePath  = window.location.pathname.replace(/\/$/, '');
        var hereQuery = window.location.search; // bv. "?key=04-brein"
        var here      = herePath + hereQuery;

        document.querySelectorAll('.menu-overlay a.menu-link').forEach(function (a) {
            var href = a.getAttribute('href') || '';
            var full = href.replace(/^https?:\/\/[^/]+/, '');
            // Normaliseer trailing slash op het pad-deel
            var qIdx     = full.indexOf('?');
            var fullPath = qIdx >= 0 ? full.slice(0, qIdx) : full;
            var fullQs   = qIdx >= 0 ? full.slice(qIdx)     : '';
            var compare  = fullPath.replace(/\/$/, '') + fullQs;

            if (compare && compare === here) {
                a.setAttribute('aria-current', 'page');
            } else {
                a.removeAttribute('aria-current');
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

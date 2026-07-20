/**
 * hub-injection.js — voegt minimal hub-shell toe aan een satelliet-site:
 *   - hub-back-link "← sebastien" (linksboven, fixed)
 *   - constellation-toggler (rechtsboven, fixed, Bootstrap Icons arrow-down-left/right) → mini cross-site overlay
 *
 * Bewust: enkel <button>/<a>/<aside> elementen, alle styling INLINE, géén
 * site-wide CSS-overrides. Achtergrondkleuren van de host-site blijven intact.
 *
 * Inkluseren:
 *   <script src="<absolute-or-relative-pad>/_shared/assets/hub-injection.js" defer></script>
 *
 * Lokaal (localhost path-mode):
 *   <script src="/sebastienvanblaere.be/_shared/assets/hub-injection.js" defer></script>
 *
 * Productie (host-mode, _shared/ via toekomstig shared.prjcts.be):
 *   <script src="//shared.prjcts.be/assets/hub-injection.js" defer></script>
 */
(function () {
    'use strict';

    // === Host-detectie: lokaal path-mode vs productie host-mode ===
    var host = window.location.hostname;
    var isLocal = host === 'localhost' || host === '127.0.0.1' || host.indexOf('localhost:') === 0;

    // Bouw URL naar een satelliet (mode-aware)
    function satUrl(satHost, servicePath, serviceHost) {
        if (servicePath) {
            return isLocal
                ? '/sebastienvanblaere.be/services/' + servicePath + '/'
                : 'https://' + serviceHost + '/services/' + servicePath + '/';
        }
        // Lokaal: elke site is folder onder htdocs/, URL = /<host>/
        // Productie: expliciete https://<host>/, elk domein eigen webspace.
        if (isLocal) {
            return '/' + satHost + '/';
        }
        return 'https://' + satHost + '/';
    }

    function hubUrl() {
        // Productie: absolute https-URL — werkt ongeacht het huidige satelliet-domein.
        return isLocal ? '/sebastienvanblaere.be/' : 'https://sebastienvanblaere.be/';
    }

    // === Detect huidige satelliet ===
    function currentKey() {
        if (host === 'prjcts.be' || host === 'www.prjcts.be') return 'prjcts';
        if (host === 'kunstmijnoren.be' || host === 'www.kunstmijnoren.be') return 'kunstmijnoren';
        if (host === 'creativecoding.prjcts.be' || host === 'p5.prjcts.be') return 'p5';
        if (host === 'waves.prjcts.be') return 'waves';
        if (host === 'p5waves.org' || host === 'www.p5waves.org') return 'p5waves';
        if (host === 'export.prjcts.be') return 'export';
        // Lokaal: detect via path — elke site is folder onder htdocs/
        var path = window.location.pathname;
        if (path.indexOf('/creativecoding.prjcts.be') === 0 || path.indexOf('/p5.prjcts.be') === 0 || path.indexOf('/services/creative_coding_p5.js') === 0) return 'p5';
        if (path.indexOf('/prjcts.be') === 0) return 'prjcts';
        if (path.indexOf('/kunstmijnoren.be') === 0) return 'kunstmijnoren';
        if (path.indexOf('/waves.prjcts.be') === 0) return 'waves';
        if (path.indexOf('/p5waves.org') === 0) return 'p5waves';
        if (path.indexOf('/export.prjcts.be') === 0) return 'export';
        return null;
    }

    var current = currentKey();

    // === Satelliet-lijst (matcht constellation.php) ===
    // `external` = absolute URL; satelliet zit niet onder *.prjcts.be (bv. GitHub Pages).
    var sats = [
        { key: 'prjcts',        host: 'prjcts.be',         label: 'prjcts.be',         desc: 'het werk' },
        { key: 'kunstmijnoren', host: 'kunstmijnoren.be',  label: 'kunstmijnoren.be',  desc: 'alter ego' },
        { key: 'p5',            serviceHost: 'sebastienvanblaere.be', servicePath: 'creative_coding_p5.js', label: 'Creative Coding - p5.js', desc: 'creative coding' },
        { key: 'three',         serviceHost: 'sebastienvanblaere.be', servicePath: 'creative_coding_three.js', label: 'Creative Coding - Three', desc: 'creative coding' },
        { key: 'p5waves',       host: 'p5waves.org',       label: 'p5.waves (libraries and services)', desc: 'library + services' },
        { key: 'gysin',         label: 'p5.gysin (Library)', desc: 'library', external: 'https://seb-prjcts-be.github.io/p5.gysin/' },
        { key: 'export',        serviceHost: 'prjcts.be', servicePath: 'p5.export', label: 'p5.export', desc: 'export-utility' }
    ];

    // === DOM elements ===

    // 1. Hub-back link (linksboven)
    var back = document.createElement('a');
    back.href = hubUrl();
    back.textContent = '← sebastien';
    back.setAttribute('aria-label', 'terug naar Sebastien Vanblaere');
    back.style.cssText = [
        'position:fixed', 'top:8px', 'left:8px', 'z-index:99998',
        'padding:4px 10px',
        'font:12px/1.2 system-ui, -apple-system, sans-serif',
        'color:#444', 'text-decoration:none',
        'background:rgba(255,255,255,0.85)',
        'border:1px solid rgba(0,0,0,0.15)',
        'backdrop-filter:blur(4px)',
        '-webkit-backdrop-filter:blur(4px)'
    ].join(';');
    back.addEventListener('mouseenter', function () { back.style.color = '#ff0000'; back.style.borderColor = '#ff0000'; });
    back.addEventListener('mouseleave', function () { back.style.color = '#444';    back.style.borderColor = 'rgba(0,0,0,0.15)'; });

    // 2. Constellation-toggler (rechtsboven) — Bootstrap Icons (zelfde als constellation.php)
    // Defensief: laad bootstrap-icons CDN als die nog niet aanwezig is
    if (!document.querySelector('link[href*="bootstrap-icons"]')) {
        var bi = document.createElement('link');
        bi.rel = 'stylesheet';
        bi.href = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css';
        document.head.appendChild(bi);
    }

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.setAttribute('aria-label', 'andere sites — Sebastien Vanblaere');
    btn.setAttribute('aria-expanded', 'false');

    var iconClosed = document.createElement('i');
    iconClosed.className = 'bi bi-arrow-down-left';
    iconClosed.setAttribute('aria-hidden', 'true');
    var iconOpen = document.createElement('i');
    iconOpen.className = 'bi bi-arrow-down-right';
    iconOpen.setAttribute('aria-hidden', 'true');
    iconOpen.style.display = 'none';
    btn.appendChild(iconClosed);
    btn.appendChild(iconOpen);

    btn.style.cssText = [
        'position:fixed', 'top:8px', 'right:8px', 'z-index:99998',
        'padding:6px 10px',
        'font:18px/1 system-ui, -apple-system, sans-serif',
        'color:#444', 'cursor:pointer',
        'background:rgba(255,255,255,0.85)',
        'border:1px solid rgba(0,0,0,0.15)',
        'backdrop-filter:blur(4px)',
        '-webkit-backdrop-filter:blur(4px)'
    ].join(';');
    btn.addEventListener('mouseenter', function () { btn.style.color = '#ff0000'; btn.style.borderColor = '#ff0000'; });
    btn.addEventListener('mouseleave', function () { btn.style.color = '#444';    btn.style.borderColor = 'rgba(0,0,0,0.15)'; });

    // 3. Constellation-overlay (rechts-offcanvas)
    var overlay = document.createElement('aside');
    overlay.setAttribute('aria-label', 'cross-site navigatie');
    overlay.setAttribute('aria-hidden', 'true');
    overlay.style.cssText = [
        'position:fixed', 'top:0', 'right:0', 'width:340px', 'max-width:92vw', 'height:100vh',
        'z-index:99997',
        'background:#fafafa',
        'box-shadow:-2px 0 18px rgba(0,0,0,0.12)',
        'padding:60px 24px 24px',
        'box-sizing:border-box',
        'overflow-y:auto',
        'transform:translateX(100%)',
        'transition:transform 0.3s cubic-bezier(0.2, 0.7, 0.2, 1)',
        'font:14px/1.5 system-ui, -apple-system, sans-serif',
        'color:#121212'
    ].join(';');

    var heading = document.createElement('div');
    heading.style.cssText = 'font-size:0.72rem;letter-spacing:0.15em;text-transform:uppercase;color:#888;margin-bottom:1rem;padding-bottom:0.6rem;border-bottom:1px solid #ddd;';
    heading.textContent = 'Sebastien Vanblaere';
    overlay.appendChild(heading);

    sats.forEach(function (s) {
        var isCur = s.key === current;
        var card = document.createElement(isCur ? 'div' : 'a');
        if (!isCur) {
            // External wint over lokale/host-detectie
            card.href = s.external || satUrl(s.host, s.servicePath, s.serviceHost);
            if (s.external) {
                card.target = '_blank';
                card.rel = 'noopener';
            }
        }
        card.style.cssText = [
            'display:block', 'padding:0.85rem 0', 'border-bottom:1px solid #eee',
            'text-decoration:none',
            'color:' + (isCur ? '#bbb' : '#121212'),
            'cursor:' + (isCur ? 'default' : 'pointer'),
            'transition:color 0.15s'
        ].join(';');
        if (!isCur) {
            card.addEventListener('mouseenter', function () { card.style.color = '#ff0000'; });
            card.addEventListener('mouseleave', function () { card.style.color = '#121212'; });
        }

        var title = document.createElement('strong');
        title.textContent = s.label;
        title.style.cssText = 'display:block;font-size:1rem;line-height:1.3;font-weight:500;';
        card.appendChild(title);

        var desc = document.createElement('small');
        desc.textContent = s.desc;
        desc.style.cssText = 'display:block;margin-top:0.15rem;font-size:0.78rem;color:' + (isCur ? '#ccc' : '#888') + ';';
        card.appendChild(desc);

        overlay.appendChild(card);
    });

    // 4. Toggler-gedrag (overlay schuift in/uit + icon-swap)
    function setOpen(open) {
        overlay.style.transform = open ? 'translateX(0)' : 'translateX(100%)';
        overlay.setAttribute('aria-hidden', open ? 'false' : 'true');
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        iconClosed.style.display = open ? 'none' : 'inline-block';
        iconOpen.style.display   = open ? 'inline-block' : 'none';
    }
    btn.addEventListener('click', function (e) {
        e.preventDefault();
        var open = btn.getAttribute('aria-expanded') !== 'true';
        setOpen(open);
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && btn.getAttribute('aria-expanded') === 'true') setOpen(false);
    });
    overlay.addEventListener('click', function (e) {
        // klik op een card sluit overlay (cards zijn anchor-tags)
        if (e.target.tagName === 'A' || e.target.closest('a')) setOpen(false);
    });

    // === Mount ===
    function mount() {
        if (!document.body) return;
        document.body.appendChild(back);
        document.body.appendChild(btn);
        document.body.appendChild(overlay);
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', mount);
    } else {
        mount();
    }
})();

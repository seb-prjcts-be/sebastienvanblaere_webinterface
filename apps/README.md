# Apps on sebastienvanblaere.be

The homepage inserts `catalog.php` after Art, before Libraries (or before the first existing section header if the library group is absent). This works with both the root fallback constellation and the production `services/constellation.json`, which belongs to a separate project. Existing project data is not rewritten.

The homepage expands the original p5waves.org entry into direct links to p5.waves, processing.waves and vanilla.waves under Libraries. It places p5.waves_lab and p5.waves_snippets under Services, followed by the existing services. These destinations come from the p5waves.org launcher and were checked on 19 September 2026.

The first app lives at `/apps/a-shot-in-the-dark/`. Add future landing pages and catalog entries here. The current page keeps the English wording of the original download site and identifies the APK as a beta. The interactive phone is explicitly an illustration, not a screenshot or a functioning camera.

The page follows the hub's restrained visual identity: #f5f5f5, Oswald headings, IBM Plex Mono text, thin rules and small red accents. Fonts use the same Google Fonts stylesheet as the homepage. Keep the download prominent without introducing oversized promotional panels.

Even Care lives at `/apps/even-care/` and follows the same visual identity. Its hour slider illustrates the default 22:00-07:00 night and alternating daytime hours; it does not control a phone. Keep the main download and rhythm explanation usable without JavaScript. The page must describe dimming, not grayscale, and retain the visible warning about total-silence DND and the beta's limited device testing.

Even Care's signed release, checksum and metadata come from `C:\server\htdocs\even_care\releases\<version>`. Its release procedure is `C:\server\htdocs\even_care\RELEASING.md`. Copy only the signed APK and its public metadata, preserve versioned filenames, and update the page's version, bytes and SHA-256 together.

A Shot in the Dark's APK, release metadata and checksum are copied unchanged from `C:\server\htdocs\ashotinthedark-site\dist\downloads`. Never include signing credentials or replace published APK bytes under an existing version name. Source and signing procedure remain in `C:\server\htdocs\ashotinthedark\RELEASING.md`.

Run locally with `C:\server\php\php.exe -S 127.0.0.1:8768 -t C:\server\htdocs\sebastienvanblaere_webinterface`, or use the existing Apache folder URL. The app page uses relative assets and a relative link back to the hub.

Publication: pushes to `main` or `master` trigger the existing one.com SFTP workflow. Confirm the reviewed version before publishing. Check homepage, app route, download headers, byte count and SHA-256 after deployment. The original chatgpt.site page is a separate deployment and is unchanged by this work.

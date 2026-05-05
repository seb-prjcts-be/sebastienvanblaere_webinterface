# Security audit — sebastienvanblaere.be hub

**Audit-datum**: 2026-05-03
**Scope**: actieve PHP/Apache codebase = `_shared/` + `sites/{prjcts,kunstmijnoren}/`. Expliciet **niet** `pickFromOriginal/` (dood archief, niet door Apache geserveerd via de huidige routes).

## Kernarchitectuur (security-relevant)
- File-based site, **geen DB** → geen SQL-injection-aanvalsoppervlak.
- **Geen authenticated zones**, geen forms, geen file uploads → geen CSRF/upload-aanval.
- Alle querystring-input via strikte allowlist (`preg_replace('/[^a-z0-9-]/i', '', ...)` of `in_array($val, $allowed, true)`).
- Alle dynamische output via `e()` = `htmlspecialchars(ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`. Het patroon `<?= $body ?>` zonder escape is intentional voor `.md`-content (admin-controlled, geen user input).
- File reads gevalideerd via `realpath()` + `strpos($abs, $sat_root) !== 0`-check (in `media_url`-closures van pages).
- Geen `eval/exec/system/popen/passthru/shell_exec` in actieve code.
- `header('Location: ...')` gebruikt path-only redirects gebouwd uit `strtok(REQUEST_URI, '?')` + `http_build_query($safe_qs)` → geen open-redirect of header-injection.

## Findings + fixes (deze ronde)

| # | Severity | Findings | Status | Fix-locatie |
|---|---|---|---|---|
| 1 | 🟠 Hoog | `content/zoomfield/file_lister.php` was dood-publiek (hardcoded paden bestaan niet meer in gemerdge structure → output altijd `[]`, maar onnodig PHP-endpoint). | ✅ Fixed | Verwijderd uit actieve tree; kopie blijft in `pickFromOriginal/LIVE_prjcts3.0/zoomfield/file_lister.php`. |
| 2 | 🟡 Medium | `display_errors` niet expliciet uitgezet; bij errors zou stack trace lekken op productie. | ✅ Fixed | `bootstrap.php`: `ini_set('display_errors', $is_local ? '1' : '0')` + `error_reporting(E_ALL)` + `log_errors=1`. |
| 3 | 🟡 Medium | `.htaccess` miste `Options -Indexes` en security headers. | ✅ Fixed | `sebastienvanblaere.be/.htaccess`: `Options -Indexes`, `<FilesMatch "^\.">`-block voor dotfiles, `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, `Strict-Transport-Security` (HSTS conditional op HTTPS). |
| 4 | 🟡 Medium | 500-error in `bootstrap.php` toonde folder-name bij onbekende satelliet (info disclosure). | ✅ Fixed | Conditional: lokaal toont diagnose, productie toont generieke `Configuration error.`. |
| 5 | 🟢 Laag | `setcookie()` miste `secure`-flag. | ✅ Fixed | Dynamische HTTPS-detectie via `$_SERVER['HTTPS']` + `X-Forwarded-Proto`-header; `secure` wordt aan op productie. `httponly` bewust **uit** voor `prjcts_lang` + `prjcts_fontsize` — header.php inline-script en de fontsize-tool moeten ze via `document.cookie` kunnen lezen. |
| 6 | 🟢 Laag | `@session_start()` met error suppression. | ✅ Fixed | `@` weg; `session_set_cookie_params()` met `secure`/`httponly`/`samesite=Lax` voor de PHPSESSID-cookie zelf (die wél httponly mag/moet). |

## Open / accepted risks
- **Geen Content-Security-Policy header gezet.** De site laadt veel third-party JS (jsdelivr, fonts.googleapis, fonts.gstatic, Bootstrap-icons CDN, p5play.org-link in HTML, Elfsight, Facebook SDK in oude code, YouTube-embeds via lightbox, reCAPTCHA in pickFromOriginal). Een correcte CSP vereist een lange whitelist en risico op breaking changes; voor een puur-static-content portfolio is de marginal benefit beperkt. **Status**: niet geïmplementeerd, bewust postponed. Kan later in `report-only`-mode worden toegevoegd.
- **`<?= $body ?>` zonder escape** in pages — intentional, content-files zijn admin-controlled. Acceptabel zolang `content/`-folder niet door web-users beschrijfbaar is.
- **localStorage `ballsState`** is per-origin gedeeld tussen prjcts en kunstmijnoren op localhost. Geen security-issue (geen sensitive data), wel een UX-quirk.

## Verificatie-checklist (smoke tests)

Lokaal (HTTP, 2026-05-03):
- [x] `/.htaccess` → 403 (dotfiles geblokt)
- [x] `/sites/` → 403 (geen directory listing)
- [x] `X-Content-Type-Options: nosniff` aanwezig
- [x] `X-Frame-Options: SAMEORIGIN` aanwezig
- [x] `Referrer-Policy: strict-origin-when-cross-origin` aanwezig
- [x] `Permissions-Policy: interest-cohort=(), browsing-topics=()` aanwezig
- [x] `?key=../../../etc/passwd` → preg_replace sanered, geen `/etc/passwd`-content in response (URL wordt wel veilig gereflecteerd in og:url + lang-switch hrefs, maar via `e()` ge-escaped)
- [x] `?lang=en` → 302 redirect naar schone URL
- [x] `Set-Cookie: PHPSESSID` heeft `HttpOnly; SameSite=Lax`
- [x] `Set-Cookie: prjcts_lang` heeft `SameSite=Lax` (geen httponly — by design, JS-leesbaar nodig)

Deploy-only (HTTPS-productie nog te verifiëren):
- [ ] `Strict-Transport-Security` header aanwezig
- [ ] `Set-Cookie` op productie zet `Secure`-flag op alle prjcts-cookies + PHPSESSID
- [ ] Forceer 500 (host-mismatch) → toont enkel `Configuration error.` (geen path-disclosure)

## Audit-checklist voor toekomstige sessies
1. Elke nieuwe `$_GET`/`$_POST`-toegang krijgt **strikte allowlist** (`preg_replace` of `in_array`).
2. Elke `echo`/`<?=` van variabele content via `e()`, behalve admin-controlled `.md`-bodies.
3. Elke `file_get_contents`/`include`/`require` met dynamisch pad krijgt `realpath()` + start-with-check.
4. Geen `@`-error-suppression — verbeter de code, suppress niet de error.
5. Geen directe `header('Location: ' . $user_input)` — altijd via `link_to()` of een interne path.
6. Geen `eval/exec/system/popen/passthru/shell_exec`.
7. Cookies altijd via `setcookie([...])`-array-vorm met expliciete `samesite`, `secure`, `httponly`.
8. Nieuwe forms krijgen CSRF-token (sessie-based) + `POST` only voor state-changing actions.
9. Bij toevoegen van auth: `password_hash()` + `password_verify()`, `session_regenerate_id(true)` na login.
10. Security headers: review of CSP nog steeds future work is, of inmiddels haalbaar.

## Niet-actieve code
`sites/prjcts/pickFromOriginal/` bevat de prjcts3.0 + oude kunstmijnoren codebase als referentie. Die bevat onveiligere patterns (raw `$_GET['img_dir']` in show_img endpoints, etc.) maar wordt **niet** door de huidige Apache-config geserveerd via host-routing — de hub `.htaccess` rewrite enkel `prjcts.be` en `kunstmijnoren.be` naar `sites/{prjcts,kunstmijnoren}/`, niet naar `pickFromOriginal/`. Zolang die map niet expliciet gerouteerd wordt, is het dood archief. Bij twijfel: die folder kan altijd buiten de webroot worden verplaatst.

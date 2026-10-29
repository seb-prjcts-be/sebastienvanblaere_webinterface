# Deploy naar one.com

Vastgesteld op 2026-10-29.

## Waar de site echt staat

- De publieke site sebastienvanblaere.be draait uit de map `webroots/www` op de SFTP-server, niet uit de home-map (`./`) van het SFTP-account.
- Bewijs: de camera-app-deploy (`.github/scripts/publish_camera_app.py`) zoekt de webroot door de publieke pagina te vergelijken. Log van 2026-10-19: `App index found at .; matches public page: False` en `App index found at webroots/www; matches public page: True`.
- Mappen in de SFTP-root: `_shared`, `apps`, `httpd.www`, `webroots`, en nog één (afgeschermd in de log).

## Het probleem

- `.github/workflows/deploy.yml` uploadt alles naar `remote_path: './'`. De GitHub-run slaagt, maar de bestanden komen in de home-map terecht, niet in de webroot. De live site verandert dus niet.
- Te zien aan: `https://sebastienvanblaere.be/STATUS.md` geeft `404` (bestaat niet in de webroot) in plaats van `403` (bestaat, maar `.htaccess` weigert `.md`).

## Oplossing (uitgevoerd 2026-10-29)

- In `deploy.yml` staat `remote_path` nu op `'./webroots/www/'`, met `delete_remote_files: false` zoals voorheen, zodat niets op de server verdwijnt.
- Vooraf live met de repo vergeleken: `_shared/assets/shared.css`, `menu.css`, `decoration.js` en `hub-injection.js` waren gelijk. Verschillend: `menu.js` (repo negeert `lang`/`fontsize` bij het markeren van het actieve menu-item), `constellation.json` (alleen terugval; de pagina leest eerst `services/constellation.json`), en `robots.txt`/`llms.txt` (stonden niet live).
- `./*` uploadt geen verborgen bestanden: `.htaccess` gaat dus niet mee en blijft zoals op de server.
- De oude uploads in de home-map (`./index.php`, `./_shared`, `./apps` …) blijven staan; ze doen niets.

## Gevolg: `.md`-bestanden publiek (2026-10-29)

- Na de eerste deploy naar `webroots/www` zijn de `.md`-bestanden van de repo publiek leesbaar (HTTP 200). De `.htaccess` van de repo weigert `.md`, maar `local_path: './*'` slaat verborgen bestanden over, dus de server houdt zijn eigen `.htaccess`, en die weigert ze niet.
- Inhoud nagekeken: geen wachtwoorden of sleutels, alleen namen van secrets (`SFTP_HOST`, `SFTP_USER`, `SFTP_PASS`) en interne notities.
- Opgelost (2026-10-29): `deploy.yml` laat de `.md`-bestanden weg uit de upload en verwijdert daarna precies de `.md`-paden die in de repo staan uit `webroots/www` (`.github/scripts/remove_public_markdown.py`). Andere bestanden op de server worden nooit aangeraakt.

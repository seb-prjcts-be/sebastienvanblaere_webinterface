## Volgende stap
- In Search Console bij "Dubbele pagina zonder ... canonieke versie" op "Oplossing valideren" klikken, na de upload hieronder.
- In Search Console bij Sitemaps `https://sebastienvanblaere.be/sitemap.php` indienen.

## Blokkades
- Upload via SFTP uit `C:\server\htdocs\cursus_site_three.js`: `index.html` en `handleiding/index.html` naar `webroots/www/services/creative_coding_three.js/` (zelfde mappen). Ze bevatten de canonical voor de handleiding. Die cursus heeft geen automatische deploy.

## Gedaan
- 2026-10-29: three.js-handleiding krijgt een canonical (repo `cursus_site_three.js`, commit `1e5f226`); sitemap noemt de handleiding i.p.v. de doorverwijzing.
- 2026-10-29: `robots.txt` verwijst live naar `sitemap.php` (sitemap.xml gaf 404).
- 2026-10-29: ".md blocker for Google Docs" (`google_docs_md_filter`) onder Services, na SVG_converter; linkt naar de GitHub Pages-site. Niet "MD Block": dat leest als een van de blocks-projecten.
- 2026-10-29: deploy laat `.md` weg en verwijdert de `.md`-bestanden van de repo uit de webroot (waren publiek leesbaar). Nagekeken: alle 11 geven live 404.
- 2026-10-29: homepage met beeldvak staat live op sebastienvanblaere.be; prjcts.be en kunstmijnoren.be werken nog.
- 2026-10-29: deploy uploadde naar `./` in plaats van de webroot `webroots/www`; `remote_path` aangepast.
- 2026-10-29: p5.gysin toont een foto van een echt geplot blad ("I LOVE YOU") van de p5.gysin-site; geen SFTP meer nodig.
- 2026-10-29: prjcts.be en kunstmijnoren.be tonen hun eigen werk: één thumbnail per project, overvloeiend (eerst balletjes geprobeerd, vervangen).
- 2026-10-29: Checkboxdance toont een raster van vakjes met een dansende curve door de aangevinkte.
- 2026-10-29: blocks.system toont vensters die in een 3×3-raster klikken en verschuiven.
- 2026-10-29: p5.penplotter toont zijn CGA-hero (GIF); vanilla.penplotter tekent het plot-gedeelte zelf na in zijn 4 CGA-kleuren (de GIF toonde lang een leeg blad).
- 2026-10-29: p5.waves_snippets toont het specimen "Live Connector" (eerst de pastelbanden van de hero).
- 2026-10-29: p5.waves_lab toont een Binary Field met de lab-standaardinstellingen.
- 2026-10-29: plaatsing gekozen (naast de kaart), lijst met/zonder vak vastgelegd, leeg vak gebouwd in `index.php`.
- 2026-10-29: vak verfijnd: witte achtergrond (zoals knop-hover), zwart kader 1px, geen tekst, blijft open en glijdt tussen kaarten.
- 2026-10-29: p5.waves, processing.waves en vanilla.waves tonen hun hero in hun eigen palet (echte p5.waves v3.6.0, pas geladen bij hover).

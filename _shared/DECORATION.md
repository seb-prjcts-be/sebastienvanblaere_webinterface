# p5-decoratie — `_shared/assets/decoration.js`

Per-satelliet zwevende p5play-balletjes met physics + DOM-obstakels. Origineel uit prjcts3.0/index.php; nu cfg-driven en herbruikbaar.

## Files
| Pad | Rol |
|---|---|
| `_shared/assets/decoration.js` | sketch (preload/setup/draw) |
| `_shared/assets/p5play.js` | gefixeerde p5play-versie, **lokaal** (kopie van `LIVE_prjcts3.0/js/p5play.js`, 277 KB) |
| `_shared/partials/decoration.php` | injecteert `<script>` tags (p5 + planck via CDN, p5play lokaal, DECORATION_CONFIG inline) |
| `_shared/assets/shared.css:285-298` | canvas styling + p5play credit-link suppression |
| `_shared/partials/footer.php` | auto-include van decoration.php als `$cfg['decoration']` is gezet |
| `_shared/config.php` | per-satelliet `'decoration' => [...]` block |

## Activeren voor een satelliet
Voeg in `_shared/config.php` onder de satelliet:
```php
'decoration' => [
    'count'         => 3,                       // aantal balletjes
    'colors'        => ['#ff0000'],             // randkleur(en); cyclet per index
    'fill'          => '#f1f1f1',               // vulkleur (matcht achtergrond → outline-effect)
    'diameter'      => 80,                      // px
    'gravity'       => 10,                      // (informatief — sketch hardcoded op 10)
    'enableCursor'  => true,                    // cursorBall aanmaken (kinematic)
    'cursorVisible' => true,                    // false = duwt content maar onzichtbaar
    'spriteImage'   => null,                    // optioneel: 'assets/oor_ets.png' → image binnen circle
    'mirrorEven'    => false,                   // image spiegelen op oneven index (1,3,...)
    'obstacleSelector' => 'img, .thumbnail-container',
    'persistKey'    => 'ballsState',            // localStorage; null = niet bewaren
],
```

Geen `decoration` block = geen balletjes (footer.php skipt include).

## Voorbeelden in prod
- **prjcts**: 3 rode lege circles, cursor zichtbaar.
- **kunstmijnoren**: 2 oor-balletjes (`spriteImage: 'assets/oor_ets.png'`), `mirrorEven: true`, cursor onzichtbaar, diameter 100.

## Render-paden
- Zonder `spriteImage` → `sprite.draw()` (default p5play render: gevulde circle met stroke).
- Met `spriteImage` → custom `drawWithImage()`: clipped circle + image binnen + outline. `mirrorEven` flipt via `scale(-1, 1)` op oneven index.

## Gotchas
1. **CDN p5play.org/v3/**: vermijden — kan stilzwijgend updaten met API-changes. Altijd lokaal serveren.
2. **CSS-selector**: `canvas.p5Canvas` (niet `body > canvas.p5Canvas`) — p5play kan canvas in een wrapper plaatsen.
3. **Credit-link**: p5play v3 plakt een `<a href="https://p5play.org">Made with p5play!</a>` aan body. Zonder `display:none` verlengt dat de pagina-hoogte → scrollbars in alle richtingen.
4. **localStorage `ballsState`**: per-origin. Lokaal delen alle satellieten dezelfde key — count-mismatch triggert auto-reset, maar overlapping satellites kunnen elkaars state overschrijven.
5. **Asset-pad**: relative URL (bv. `assets/oor_ets.png`) werkt voor zowel `localhost/sebastienvanblaere.be/sites/<key>/` als productie `<key>.be/`. Gebruik geen leading slash.

## Troubleshooting
| Symptoom | Hoogstwaarschijnlijke oorzaak |
|---|---|
| Geen balletjes, geen JS-errors | Canvas niet `fixed` (selector matcht niet) of credit-link verlengt body |
| Pagina scrollt in alle richtingen | Canvas als block in flow → CSS-selector check |
| Balletje stuitert pas onder windowHeight | OK — bottomWall-rand zit op `windowHeight`, ball-radius onderaan raakt daar (volledig zichtbaar binnen viewport) |
| Image laadt niet | URL-pad fout; check Network tab; gebruik relative pad |
| Minder balletjes dan `count` | localStorage stale state, of overlap door random spawn |

## Niet doen
- `world.gravity.y` veranderen — 10 is intentioneel (zware balletjes, prjcts3.0 feel).
- p5play upgraden naar nieuwere versie zonder volledige regressietest.
- CCD aanzetten in planck — niet exposed, globale fysica wijziging.
- Aparte sketch-file per satelliet — alle variatie via cfg-keys.

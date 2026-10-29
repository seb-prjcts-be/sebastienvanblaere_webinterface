# Hover-handtekeningen op de homepage (voorstel, 2026-10-29)

Idee: een kaart toont bij hover kort het beeld van het project zelf. Alleen waar een project een eigen beeldtaal heeft. De rest houdt de rode hover van nu.

## Principe

- Eén laag: een `<canvas>` achter het label, onzichtbaar tot hover of focus.
- Inkt eerst. Dunne lijnen in de tekstkleur, lage dekking. Het label blijft leesbaar.
- Kleur alleen waar de kleur het werk is (CGA bij Play).
- Geen extra libraries. Kleine tekenfuncties in vanilla JS, één `requestAnimationFrame` alleen zolang de muis op de kaart staat.
- `prefers-reduced-motion`: één stilstaand beeld, geen animatie.
- Touch: geen hover. Toon de handtekening dan kort bij aanraken, of laat ze weg.

## Eerst: het lege beeldvak

Een extra grafisch element dat bij mouseover verschijnt. Eerst kiezen waar het staat, nog leeg. Plaatsingen:

1. Naast de kaart (voorstel): een vierkant vak van ca. 150px in de rechtermarge, op de hoogte van de kaart. Het label blijft onaangeroerd.
2. Vast venster: één vak naast de kolom dat blijft staan en van inhoud wisselt.
3. Kaart klapt open: onder het label opent een band van ca. 90px.
4. Volgt de muis: een klein vak naast de cursor.

Op een telefoon is er geen marge en geen hover. Daar blijft het vak weg.

Vaste regels:

- Niet elke kaart krijgt een vak. Kaarten zonder vak houden de hover van nu, zonder animatie.
- Rijen met twee kaarten blijven twee kaarten. Het vak staat in de marge op de hoogte van die rij; elke helft toont haar eigen beeld. Daarom valt plaatsing 3 af: die zou de paren breken.

## Welke kaarten een vak krijgen (voorstel)

| Sectie | Met vak | Zonder vak |
|---|---|---|
| Art | prjcts.be, kunstmijnoren.be | |
| Libraries | p5.waves, p5.waves_lab, p5.waves_snippets, processing.waves, vanilla.waves, p5.gysin, vanilla.penplotter, p5.penplotter, blocks.system | |
| Services | | p5.export, SVG_converter, Audiovisueel |
| Play | Checkboxdance | |
| Education | | alles |
| Inspiration | | most loved |
| Apps | | a shot in the dark, Even Care |
| Resume | | curriculum vitae |

## Per kaart

| Kaart | Handtekening |
|---|---|
| p5.waves, lab, snippets, processing.waves, vanilla.waves | golvende lijnen, elke kaart een ander golftype |
| p5.gysin | het woord slijt (letters vallen weg), lichte arcering |
| vanilla.penplotter, p5.penplotter, blocks.system | één doorlopende penlijn die zich tekent |
| Checkboxdance | CGA-pixels in 4 kleuren |
| prjcts.be, kunstmijnoren, services, education, cv | blijft zoals nu |

## Hoe het nu gebouwd is (2026-10-29)

- In `index.php` bepaalt `$vak_keys` welke kaarten een vak krijgen. Die links krijgen `data-vak="<key>"`.
- Geen tekst in het vak.
- Vak: wit (`#ffffff`, zoals een knop bij hover) met een zwart kader van 1px. De tekeningen vullen geen eigen achtergrond, zodat het wit van het vak overal doorkomt. (245 als op de hero-pagina's was eerst de keuze.) (Rood als de gehoverde knop is geprobeerd en verworpen.) Een kaart zonder beeld toont dat lege vak.
- Beweging (2026-10-29): Het vak is één object: tussen twee kaarten blijft het open en glijdt het naar de nieuwe hoogte (`transform`, 300 ms, ease-out); het nieuwe beeld vloeit in (200 ms). Pas 150 ms na het verlaten van een kaart verdwijnt het (fade 150 ms, 8px naar links). Verschijnen: 200 ms, 8px van links. Bij `prefers-reduced-motion` alles zonder animatie.
- De penplotter-paren kregen daarvoor een key: `vanillapenplotter`, `p5penplotter`.
- Eén `<div class="vak">` onderaan in `<nav>`. Een klein script zet het op de hoogte van de kaart (ook bij toetsenbordfocus) en zet `data-vak` op het vak.
- Alleen met een muis (`hover: hover`). Het script past de grootte aan de vrije marge rechts van de lijst aan: maximaal 150px, minimaal 72px. Is er minder plaats (ongeveer onder 720px breed), dan blijft het vak weg. Op een telefoon: weg.

### p5.waves (2026-10-29)

Elk vak toont een kleine versie van de hero van die library, in de kleuren van die hero:

| Kaart | Beeld | Kleuren (bron) |
|---|---|---|
| p5.waves | grijze silhouetten onder vijf gekleurde linten | `#174cff #ff3b2f #d7ff22 #ff4fb3 #00c7ff` (p5.waves `docs/sketch.js`) |
| processing.waves | zelfde opbouw, Signal Loom-palet met violet | `#0647ff #ff3d2e #c7ff2d #8e51ff #00bdd6` (processing.waves `sketch.js`, `LOOM`) |
| vanilla.waves | gestapelde lijnen in koffietinten | espresso → teal → zand (vanilla.waves `docs/waves-demos.js`, `SPECTRUM`) |
| p5.waves_lab | Binary Field: 30×30 cellen, aan als rij + kolom boven de drempel | zwart op 245 (lab-standaard: seed 13, golven 1 en 14, shift 3 / 1.2, stretch 5 en 2.5, offset 0.4) |
| p5.waves_snippets | het specimen "Live Connector": drie knopen bedraad aan een bewegende knoop B; rustig golft de rand zacht (gentle), hoe sneller B beweegt hoe meer hij mengt naar harsh, een te snelle ruk scheurt de rand (perzik). Zonder labels en knoopnummers | inkt `#111213`, perzik `#f6a796` (snippets `script.js`, specimen `cursor-echo`). Eerst de pastelbanden van de hero geprobeerd, op vraag vervangen |

- Seeds, frequenties en `shift` komen uit de hero-sketches, verkleind tot het vak. De bovenste strook van de hero (waar de titel staat) valt weg.
- Verandert een hero van kleur, pas dan `P5_COLOURS`, `LOOM_COLOURS` of `COFFEE` aan in `index.php`.
- p5.waves heeft geen p5 nodig. De library (v3.6.0, jsDelivr, ca. 12 kB) laadt pas zodra de muis of de focus in de lijst komt. Wie niet hovert, laadt niets extra.
- `prefers-reduced-motion`: één stilstaand beeld.
- Een nieuw beeld toevoegen: een functie in `beelden` in het script onderaan `index.php`, met de key van de kaart. Kaarten zonder functie tonen een leeg kader.

### p5.gysin (2026-10-29)

- De gysin-hero is zelf een stilstaande tekening (`noLoop`). Het vak toont een vierkante uitsnede van die echte hero, op wit: het permutatieblok "RUB OUT THE WORD", het raster en het letterveld.
- Gemaakt met de echte p5.gysin (seed 12071960, hero op 1440×900, uitsnede x 780 · y 60 · 480×480, verkleind tot 300×300). Bestand: `img/vak/p5gysin.webp` (23 kB).
- Waarom een beeld en geen live tekening: p5.gysin vraagt p5 en drie libraries; een SVG-export van de hero is 950 kB.
- Afbeeldingen staan niet in git (`.gitignore`): `img/vak/` moet via SFTP naar de server. Ontbreekt het bestand, dan blijft het vak gewoon wit.

### Penplotters (2026-10-29)

- Beide penplotters hebben een CGA-animatie als hero (600×400 GIF, 4 kleuren).
- p5.penplotter: het vak toont die GIF zelf (zwart, cyaan, magenta, wit — `p5.penplotter/docs/images/animations/hero.gif`, 837 kB), uitgesneden tot 300×300 rond de plotter rechts (`x`, `y`, `side` in `GIFS`). Laadt rechtstreeks van de library-site, pas bij de eerste hover. Een GIF kan niet op het canvas spelen, dus het vak heeft er een eigen `<img>` voor; een GIF valt niet te pauzeren, dus bij `prefers-reduced-motion` blijft het vak leeg.
- vanilla.penplotter: getekend, niet de GIF (`vanillaPlot()`). Het plot-gedeelte van `overview.gif`: de teal balk en de oker pen plotten een wiebelende spiraal op het crème blad, 6 s tekenen, 1,5 s rust, opnieuw. Op een raster van 60×60 pixels, scherp opgeschaald, in de vier kleuren van de GIF: espresso `#2b1f1a`, crème `#ecddb8`, teal `#8fa595`, oker `#c4874b`.
  - Waarom niet de GIF: ruim uitgesneden is het beeld te donker (espresso); dicht op het blad is het blad de eerste ~10 van de 23,8 s leeg, omdat de GIF eerst de code op het scherm toont.

### blocks.system (2026-10-29)

- Drie vensters (zwarte balk, lichte inhoud) die in een raster van 3×3 klikken en om de 2 seconden naar een nieuwe plek schuiven: add · span · place. Vier vaste schikkingen in `BLOCK_LAYOUTS`.
- Kleuren uit `blocks.system.css`: inkt `#111`, papier `#efeee8`, rasterlijn `rgba(0,0,0,0.2)`; het middelste venster is donkerder (`#bdbcb6`), zoals de foto in de hero. Op het wit van het vak.

### Checkboxdance (2026-10-29)

- Niet CGA, zoals eerst gedacht: Checkboxdance is een raster van echte checkboxen. Het vak toont een raster van 7×7 vakjes, vijf aangevinkt (zwart). Die vijf dansen, verbonden door één gesloten curve (zwart, 0,75px, zoals `curveVertex`) met rode punten (`rgb(255,70,70)`).
- De beweging volgt `Node.update()` uit `services/checkboxdance/sketch.js`, verkleind tot het vak; de zijwaartse uitslag is iets smaller zodat de curve binnen het vak blijft.

### prjcts.be en kunstmijnoren.be (2026-10-29)

- Het vak toont het werk zelf: één vierkante thumbnail per project, die om de 2,4 s overvloeit (0,6 s) in de volgende (`work()` in `index.php`).
  - prjcts.be ("het werk"): vooral zwart-wit — Form & Repetition, Entrench. Enshrine., Das Dunkelheit Prinzip, en uit het archief Membering | Inneren, Something from nothing, Experiments, Tree, Walks.
  - kunstmijnoren.be ("foliotheek"): gevonden papier, drukwerk, oude foto's — Zinneroersels en Folio an sich om beurt.
- De beelden komen van de `thumb.php` van elke site (`w=300&h=300`, vierkant bijgesneden), pas bij hover geladen, telkens het volgende beeld vooraf. Niets te uploaden. Een ander beeld kiezen: de bestandsnaam in de lijst in `index.php` vervangen.
- Bij `prefers-reduced-motion`: één beeld per beurt, zonder overvloei.
- Eerst geprobeerd en vervangen: de vallende decoratie-balletjes van beide sites (rode cirkels, oor-balletjes). Te veel decoratie, te weinig werk.

Alle kaarten met een vak hebben nu een beeld.

<?php
// Bezoekersteller (GoatCounter): geen cookies, geen persoonsgegevens, geen banner nodig.
// Sitecode staat in config.php onder 'teller'. Leeg = er wordt niets geladen.
// Eigen bezoeken uitsluiten: open een pagina met #toggle-goatcounter achter het adres.
$_teller = (string) ($config['teller'] ?? '');
if ($_teller === '') {
    return;
}
?>
<script data-goatcounter="https://<?= e($_teller) ?>.goatcounter.com/count" async src="//gc.zgo.at/count.js"></script>

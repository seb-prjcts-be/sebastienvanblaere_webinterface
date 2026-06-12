    </main>
</div><!-- /.main-wrapper -->
<script src="https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/gsap.min.js" defer></script>
<script src="<?= e(shared_asset_url($config)) ?>/assets/menu.js" defer></script>
<?php
// Decoratie (p5-balletjes) — page-override > site-config > niets.
$decoration = null;
if (isset($GLOBALS['_prjcts_decoration']) && is_array($GLOBALS['_prjcts_decoration'])) {
    $decoration = $GLOBALS['_prjcts_decoration'];
} elseif (!empty($cfg['decoration']) && is_array($cfg['decoration'])) {
    $decoration = $cfg['decoration'];
}
if ($decoration !== null) {
    require __DIR__ . '/decoration.php';
}
?>
</body>
</html>

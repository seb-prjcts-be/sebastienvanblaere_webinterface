<?php
/** @var array $cfg
 *  @var array $config */
declare(strict_types=1);

$lang     = $GLOBALS['_prjcts_lang'] ?? 'nl';
$root     = realpath(__DIR__ . '/../content') ?: '';
$artforms = discover_artforms($root, $lang);

$artform_key = isset($_GET['artform']) ? preg_replace('/[^a-z0-9-]/i', '', (string) $_GET['artform']) : '';
$project_key = isset($_GET['project']) ? preg_replace('/[^a-z0-9-]/i', '', (string) $_GET['project']) : '';

$projects_url = link_to(null, 'projects', $config);

/** Bouw URL voor een media-bestand binnen een project-folder */
$media_url = function (array $project, string $filename) use ($config): string {
    $sat_root = realpath($GLOBALS['_prjcts_root']) ?: '';
    $abs      = realpath($project['dir'] . '/' . $filename) ?: '';
    if ($sat_root === '' || $abs === '' || strpos($abs, $sat_root) !== 0) {
        return '';
    }
    $rel = ltrim(str_replace('\\', '/', substr($abs, strlen($sat_root))), '/');
    return link_to(null, $rel, $config);
};
?>

<?php if ($artform_key !== '' && $project_key !== ''):
    $project = read_project($root, $artform_key, $project_key, $lang);

    if ($project): ?>
        <div class="prjcts-row">
            <div class="prjcts-col-main">
                <h1><?= e($project['name']) ?></h1>
                <?php if ($project['subtitle']): ?>
                    <p class="subtitle"><em><?= e($project['subtitle']) ?></em></p>
                <?php endif; ?>
                <?php if ($project['date']): ?>
                    <p><small><?= e($project['date']) ?></small></p>
                <?php endif; ?>

                <div class="project-body"><?= $project['body'] ?></div>

                <?php if (!empty($project['works'])): ?>
                    <hr style='border: 1px solid #e0e0e0;'>
                    <div class="works-grid">
                        <?php foreach ($project['works'] as $w):
                            $url = $media_url($project, $w['filename']);
                            if ($url === '') continue;
                            $is_pdf = ($w['ext'] ?? '') === 'pdf';
                            if ($is_pdf)            $type = 'pdf';
                            elseif ($w['is_video']) $type = 'video';
                            elseif ($w['is_audio']) $type = 'audio';
                            else                    $type = 'image';
                            $cls  = 'thumbnail-container'
                                  . ($w['is_video'] ? ' is-video' : '')
                                  . ($w['is_audio'] ? ' is-audio' : '')
                                  . ($is_pdf       ? ' is-pdf'   : '');
                            $thumb = $type === 'image'
                                ? thumb_url($project['dir'] . '/' . $w['filename'], 400)
                                : '';
                        ?>
                            <button type="button"
                                    class="<?= e($cls) ?>"
                                    data-src="<?= e($url) ?>"
                                    data-type="<?= e($type) ?>"
                                    aria-label="<?= e($w['filename']) ?>">
                                <?php if ($type === 'video'): ?>
                                    <video src="<?= e($url) ?>" muted preload="metadata"></video>
                                <?php elseif ($type === 'audio'): ?>
                                    <span class="thumb-icon"><i class="bi bi-music-note-beamed" aria-hidden="true"></i></span>
                                    <span class="thumb-label"><?= e($w['filename']) ?></span>
                                <?php elseif ($type === 'pdf'): ?>
                                    <span class="thumb-icon"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i></span>
                                    <span class="thumb-label"><?= e($w['filename']) ?></span>
                                <?php else: ?>
                                    <img src="<?= e($thumb !== '' ? $thumb : $url) ?>"
                                         loading="lazy" decoding="async" fetchpriority="low"
                                         width="400" height="400" alt="">
                                <?php endif; ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <aside class="prjcts-col-side">
                <?php if (!empty($project['youtube'])): ?>
                    <h3>youtube</h3>
                    <ul class="works-list">
                        <?php foreach ($project['youtube'] as $yt): ?>
                            <li>
                                <button type="button"
                                        class="works-list-item"
                                        data-src="https://www.youtube.com/embed/<?= e($yt['code']) ?>?autoplay=1"
                                        data-type="youtube"
                                        aria-label="<?= e($yt['title']) ?>">
                                    <code><?= e($yt['title']) ?></code>
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if (!empty($project['works'])): ?>
                    <h3>works (<?= count($project['works']) ?>)</h3>
                    <ul class="works-list">
                        <?php foreach ($project['works'] as $w):
                            $url = $media_url($project, $w['filename']);
                            if ($url === '') continue;
                            $is_pdf = ($w['ext'] ?? '') === 'pdf';
                            if ($is_pdf)            $type = 'pdf';
                            elseif ($w['is_video']) $type = 'video';
                            elseif ($w['is_audio']) $type = 'audio';
                            else                    $type = 'image';
                        ?>
                            <li>
                                <button type="button"
                                        class="works-list-item"
                                        data-src="<?= e($url) ?>"
                                        data-type="<?= e($type) ?>"
                                        aria-label="<?= e($w['filename']) ?>">
                                    <code><?= e($w['filename']) ?></code>
                                    <small>[<?= e($type) ?>]</small>
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </aside>
        </div>
    <?php else: ?>
        <p><em>project niet gevonden.</em></p>
    <?php endif; ?>

<?php else: ?>
    <h1>projects</h1>
    <?php if (empty($artforms)): ?>
        <p><em>geen artforms gevonden &mdash; drop een folder in <code>content/projects/&lt;artform&gt;/</code>.</em></p>
    <?php endif; ?>
<?php endif; ?>

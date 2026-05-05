<?php
declare(strict_types=1);

return [
    'shared_url' => '/_shared',
    'hub_root'   => 'sebastienvanblaere.be',  // folder-naam voor de hub-DocumentRoot
    'satellites' => [
        'prjcts' => [
            'host'        => 'prjcts.be',
            'title'       => 'prjcts',
            'home_label'  => 'prjcts.be',
            'description' => 'het werk — abouts, projecten, archief',
            'register'    => 'meta',
            'density'     => 0.3,
            'accent'      => '#ff0000',
            'in_hub'      => true,
            // SEO / social
            'tagline'     => '"Don\'t be scared," he whispered. "Everything is connected anyway. Remix! Experiment! Read! Plunder!"',
            'keywords'    => ['sebastien', 'vanblaere', '@prjcts.be', 'code', 'procedural', 'prjcts', 'lifeform', 'seb', 'lab44', 'brain', 'NAH', 'p5js', 'processing', 'teacher', 'photography', 'machiniques', 'graphiques', 'entropy', 'deleuze', 'gilles'],
            'author'      => 'Seb Vanblaere',
            'og_title'    => '@prjcts.be',
            'og_image'    => '',   // vul aan met absolute of relatieve URL
            'fb_app_id'   => '298335268665588',
            // p5-decoratie default voor prjcts: 3 rode bouncing balls
            'decoration'  => [
                'count'        => 3,
                'colors'       => ['#ff0000'],
                'fill'         => '#f1f1f1',
                'diameter'     => 80,
                'gravity'      => 10,
                'enableCursor' => true,
            ],
        ],
        'kunstmijnoren' => [
            'host'        => 'kunstmijnoren.be',
            'title'       => 'kunstmijnoren',
            'home_label'  => 'Kunst, mijn oren.',
            'description' => 'alter ego — foliotheek',
            'register'    => 'meta',
            'density'     => 0.3,
            'accent'      => '#ff0000',
            'in_hub'      => true,
            // SEO / social
            'tagline'     => 'Filosofische en existentiële beslommeringen, krassen, tekeningen foto\'s en scans. Foliominnend Herwaarderingsfonds - Kunst, mijn oren.',
            'keywords'    => ['sebastien', 'vanblaere', '@kunstmijnoren', 'foliominnend', 'folio', 'herwaardering', 'fonds'],
            'author'      => 'Sebastien Vanblaere',
            'og_title'    => '@kunstmijnoren',
            'og_image'    => '/assets/ear.png',  // relatief vanaf site-root → wordt absolute URL
            'fb_app_id'   => '746058895106767',
            // p5-decoratie — 2 oren in clipped circles, één gespiegeld;
            // cursor-bal duwt content maar is onzichtbaar (zoals origineel kunstmijnoren).
            'decoration'  => [
                'count'         => 2,
                'colors'        => ['#ff0000'],
                'fill'          => '#f1f1f1',
                'diameter'      => 100,
                'gravity'       => 10,
                'enableCursor'  => true,
                'cursorVisible' => false,
                'spriteImage'   => 'assets/oor_ets.png',
                'mirrorEven'    => true,
            ],
        ],
        'p5' => [
            'host'        => 'creativecoding.prjcts.be',
            'title'       => 'p5 cursus',
            'description' => 'creative coding handleiding',
            'register'    => 'educational',
            'density'     => 0.5,
            'accent'      => '#ff0000',
        ],
        'waves' => [
            'host'         => 'waves.prjcts.be',
            'title'        => 'p5.waves',
            'description'  => 'p5.js wave-formules',
            'register'     => 'technical',
            'density'      => 0.7,
            'accent'       => '#ff0000',
            // External: geen lokale folder/satelliet — geserveerd via GitHub Pages
            'external_url' => 'https://seb-prjcts-be.github.io/p5.waves/',
        ],
        'export' => [
            'host'        => 'export.prjcts.be',
            'title'       => 'p5.export',
            'description' => 'p5.js export-utility',
            'register'    => 'technical',
            'density'     => 0.7,
            'accent'      => '#ff0000',
        ],
    ],
];

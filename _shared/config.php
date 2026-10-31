<?php
declare(strict_types=1);

return [
    'shared_url' => '/_shared',
    'hub_root'   => 'sebastienvanblaere.be',  // folder-naam voor de hub-DocumentRoot
    'teller'     => 'prjcts',  // GoatCounter-sitecode (het deel vóór .goatcounter.com); leeg = geen teller

    // Persoon-hub: linktree-pagina (sebastienvanblaere.be) — niet een satelliet.
    // Wordt gebruikt voor SEO + Open Graph + JSON-LD Knowledge Graph + page-tagline.
    'hub' => [
        'title'       => 'Sebastien Vanblaere',
        'tagline'     => "Neurodiverse lover of coffee, art and philosophy.\nPreferably in that order...",
        'description' => 'Explore Sebastien Vanblaere\'s digital art, creative coding libraries and Android apps, alongside audiovisual projects and educational resources.',
        'keywords'    => ['Sebastien Vanblaere', 'prjcts', 'kunstmijnoren', 'digital art', 'digitaal kunstenaar', 'creative coding', 'generative art', 'audiovisual art', 'p5.js', 'Processing', 'Three.js', 'p5.waves', 'p5.gysin', 'pen plotter art', 'kunstonderwijs'],
        'author'      => 'Sebastien Vanblaere',
        'job_title'   => 'Digital Artist, Creative Technologist & Educator',
        'og_title'    => 'Sebastien Vanblaere: Digital Art, Creative Coding and Libraries',
        'og_image'    => '/assets/og-portrait.jpg',  // relatief; wordt absolute in <meta>
        'same_as'     => [
            'https://prjcts.be/',
            'https://kunstmijnoren.be/',
            'https://p5waves.org/',
            'https://github.com/seb-prjcts-be',
            'https://www.instagram.com/prjcts.be/',
            'https://www.linkedin.com/in/sebvanb',
            'https://www.buymeacoffee.com/prjcts',
        ],
        'knows_about' => [
            'Generative Art',
            'Creative Coding',
            'Digital Art',
            'Audiovisual Art',
            'p5.js',
            'Processing',
            'Three.js',
            'Pen Plotter Art',
            'Algorithmic Art',
            'Philosophy',
            'Art Education',
        ],
    ],

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
                'fill'         => '#faf9f5',
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
                'fill'          => '#faf9f5',
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
        'waves_lab' => [
            'host'         => 'waves-lab.prjcts.be',
            'title'        => 'p5.waves_lab',
            'description'  => 'p5.waves experimenten + showcase',
            'register'     => 'technical',
            'density'      => 0.7,
            'accent'       => '#ff0000',
            // External: GitHub Pages, pendant van p5.waves
            'external_url' => 'https://seb-prjcts-be.github.io/p5.waves_lab/',
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

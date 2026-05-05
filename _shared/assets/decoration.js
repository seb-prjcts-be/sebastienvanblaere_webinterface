/**
 * Satelliet-decoratie — zwevende balletjes met p5play, botsen tegen DOM-elementen.
 * Origineel uit prjcts3.0/index.php, hier herbruikbaar gemaakt.
 *
 * VEREIST (in deze volgorde, vóór dit bestand):
 *   p5.js          — https://cdn.jsdelivr.net/npm/p5@1.11.4/lib/p5.js
 *   planck         — https://cdn.jsdelivr.net/npm/planck@latest/dist/planck.min.js
 *   p5play         — lokale kopie /_shared/assets/p5play.js (geteste versie)
 *
 * PER-PAGINA CONFIG — zet `window.DECORATION_CONFIG` vóór dit script. Bv.:
 *
 *   <script>
 *     window.DECORATION_CONFIG = {
 *       count:         3,                       // aantal balletjes
 *       colors:        ['#ff0000'],             // randkleur(en); cyclet per bal
 *       fill:          '#f1f1f1',               // vulkleur (matcht achtergrond → outline-effect)
 *       diameter:      80,
 *       gravity:       10,
 *       enableCursor:  true,                    // muis-volger ball aanmaken
 *       cursorVisible: true,                    // cursor zichtbaar (false = enkel duwt content)
 *       spriteImage:   '/assets/oor_ets.png',   // optioneel: image binnen elke bal (clipped circle)
 *       mirrorEven:    false,                   // image spiegelen op oneven index (1,3,...)
 *       obstacleSelector: 'img, .thumbnail-container',
 *       persistKey:    'ballsState'             // localStorage; null = niet bewaren
 *     };
 *   </script>
 *   <script src="/_shared/assets/decoration.js"></script>
 *
 * Default = 3 rode ballen + zichtbare cursor-bal (prjcts-stijl).
 */
(function () {
    var cfg = Object.assign({
        count:            3,
        colors:           ['#ff0000'],
        fill:             '#f1f1f1',
        diameter:         80,
        gravity:          10,
        enableCursor:     true,
        cursorVisible:    true,
        spriteImage:      null,
        mirrorEven:       false,
        obstacleSelector: 'img, .thumbnail-container',
        persistKey:       'ballsState'
    }, window.DECORATION_CONFIG || {});

    var balls       = [];
    var cursorBall  = null;
    var obstacles   = [];
    var spriteImg   = null;          // p5.Image — alleen gezet als cfg.spriteImage
    var topWall, bottomWall, leftWall, rightWall;

    if (cfg.spriteImage) {
        window.preload = function () {
            spriteImg = loadImage(cfg.spriteImage);
        };
    }

    window.setup = function () {
        createCanvas(windowWidth, windowHeight);
        world.gravity.y = 10;

        if (cfg.enableCursor) {
            cursorBall            = new Sprite();
            cursorBall.diameter   = 30;
            cursorBall.color      = cfg.fill;
            cursorBall.stroke     = cfg.fill;
            cursorBall.collider   = 'kinematic';
            cursorBall.bounciness = 0.8;
            cursorBall.visible    = cfg.cursorVisible;
        }

        // Restore from localStorage als count matcht
        var saved = null;
        if (cfg.persistKey) {
            try { saved = JSON.parse(localStorage.getItem(cfg.persistKey)); }
            catch (e) { saved = null; }
        }

        if (saved && saved.length === cfg.count) {
            for (var i = 0; i < cfg.count; i++) {
                var c = cfg.colors[i % cfg.colors.length];
                var b = createBall(saved[i].x, saved[i].y, c, isMirror(i));
                b.sprite.velocity.x = saved[i].vel_x;
                b.sprite.velocity.y = saved[i].vel_y;
                balls.push(b);
            }
        } else {
            for (var j = 0; j < cfg.count; j++) {
                var col = cfg.colors[j % cfg.colors.length];
                balls.push(createBall(random(windowWidth), random(windowHeight - 100), col, isMirror(j)));
            }
        }

        // Onzichtbare wanden rond viewport
        topWall    = new Sprite(width / 2, -10,        width, 20, 'static');
        bottomWall = new Sprite(width / 2, height + 10, width, 20, 'static');
        leftWall   = new Sprite(-10,        height / 2, 20, height, 'static');
        rightWall  = new Sprite(width + 10, height / 2, 20, height, 'static');
        topWall.visible = bottomWall.visible = leftWall.visible = rightWall.visible = false;

        setTimeout(buildObstacles, 500);
    };

    function isMirror(index) {
        return cfg.mirrorEven && (index % 2 === 1);
    }

    function createBall(x, y, strokeColor, flip) {
        var s = new Sprite();
        s.x            = x;
        s.y            = y;
        s.color        = cfg.fill;
        s.stroke       = strokeColor;
        s.strokeWeight = 4;
        s.diameter     = cfg.diameter;
        s.bounciness   = 0.8;
        s.friction     = 0.001;
        s.mass         = 5;
        s.autoDraw     = false;

        return {
            sprite: s,
            flip:   !!flip,
            draw:   spriteImg ? drawWithImage : drawPlain,
            checkBounds: function () {
                if (this.sprite.y > camera.y + height * 2 ||
                    this.sprite.y < camera.y - height ||
                    this.sprite.x < -width ||
                    this.sprite.x > width * 2) {
                    this.sprite.x          = width / 2;
                    this.sprite.y          = camera.y + height / 2;
                    this.sprite.velocity.x = 0;
                    this.sprite.velocity.y = 0;
                }
            }
        };
    }

    function drawPlain() {
        this.sprite.draw();
    }

    // Render: image binnen circle-clip + gekleurde outline (zoals oude kunstmijnoren).
    function drawWithImage() {
        var d = this.sprite.diameter;
        push();
        translate(this.sprite.x, this.sprite.y);
        rotate(this.sprite.rotation);
        scale(this.flip ? -1 : 1, 1);

        drawingContext.save();
        drawingContext.beginPath();
        drawingContext.arc(0, 0, d / 2, 0, Math.PI * 2);
        drawingContext.clip();

        imageMode(CENTER);
        image(spriteImg, 0, 0, d, d);
        drawingContext.restore();

        noFill();
        stroke(this.sprite.stroke);
        strokeWeight(this.sprite.strokeWeight);
        circle(0, 0, d);
        pop();
    }

    function buildObstacles() {
        for (var i = 0; i < obstacles.length; i++) obstacles[i].remove();
        obstacles = [];
        var els = document.querySelectorAll(cfg.obstacleSelector);
        for (var k = 0; k < els.length; k++) {
            var el = els[k];
            var r  = el.getBoundingClientRect();
            if (r.width > 0 && r.height > 0) {
                var o = new Sprite(
                    r.left + r.width  / 2,
                    r.top  + r.height / 2 + window.scrollY,
                    r.width, r.height, 'static'
                );
                o.visible    = false;
                o.domElement = el;
                obstacles.push(o);
            }
        }
    }

    window.draw = function () {
        clear();
        camera.y = window.scrollY + height / 2;

        topWall.y     = camera.y - height / 2 - 10;
        bottomWall.y  = camera.y + height / 2 + 10;
        leftWall.y    = camera.y;
        rightWall.y   = camera.y;
        topWall.w     = bottomWall.w = width;
        leftWall.h    = rightWall.h  = height;
        leftWall.x    = -10;
        rightWall.x   = width + 10;

        for (var i = 0; i < obstacles.length; i++) {
            var o = obstacles[i];
            var r = o.domElement.getBoundingClientRect();
            o.x = r.left + r.width  / 2;
            o.y = r.top  + r.height / 2 + window.scrollY;
            o.w = r.width;
            o.h = r.height;
        }

        if (cursorBall) {
            var f = createVector(mouseX - cursorBall.x, (mouseY + window.scrollY) - cursorBall.y);
            f.mult(0.2);
            cursorBall.velocity = f;
        }

        for (var j = 0; j < balls.length; j++) balls[j].draw();
        for (var k = 0; k < balls.length; k++) balls[k].checkBounds();

        if (cfg.persistKey && frameCount % 120 === 0) saveState();
    };

    window.windowResized = function () {
        resizeCanvas(windowWidth, windowHeight);
        buildObstacles();
    };

    window.mouseReleased = function () {
        if (cfg.persistKey) saveState();
    };

    function saveState() {
        var states = balls.map(function (b) {
            return {
                x:     b.sprite.x,
                y:     b.sprite.y,
                vel_x: b.sprite.velocity.x,
                vel_y: b.sprite.velocity.y
            };
        });
        try { localStorage.setItem(cfg.persistKey, JSON.stringify(states)); } catch (e) {}
    }
})();

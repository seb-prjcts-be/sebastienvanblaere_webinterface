let s = 0.8,
  ox = 0,
  oy = 0,
  drag = false,
  px = 0,
  py = 0;
const W = 1200,
  H = 800;
let allFiles = [];
let mediaFiles = [];
let loadedImages = {};
let loadedVideos = {};
const scrambleSeed = Date.now().toString();

function preload() {
  loadJSON('file_lister.php', data => {
    mediaFiles = data;
  });
}

function setup() {
  createCanvas(windowWidth, windowHeight);
  frameRate(12);
}

function draw() {
  background(245);
  translate(width / 2, height / 2);
  scale(s);
  translate(ox, oy);

  const vw = width / s,
    vh = height / s;
  const minX = -ox - vw / 2,
    maxX = -ox + vw / 2;
  const minY = -oy - vh / 2,
    maxY = -oy + vh / 2;
  const tx0 = Math.floor(minX / W) - 1,
    tx1 = Math.ceil(maxX / W) + 1;
  const ty0 = Math.floor(minY / H) - 1,
    ty1 = Math.ceil(maxY / H) + 1;

  for (let ty = ty0; ty <= ty1; ty++) {
    for (let tx = tx0; tx <= tx1; tx++) {
      drawTile(tx, ty);
    }
  }
}

function getScaledDimensions(originalWidth, originalHeight) {
  const aspectRatio = originalWidth / originalHeight;
  let newWidth = W - 50;
  let newHeight = newWidth / aspectRatio;
  if (newHeight > H - 50) {
    newHeight = H - 50;
    newWidth = newHeight * aspectRatio;
  }
  return {
    w: newWidth,
    h: newHeight
  };
}

function drawTile(tx, ty) {
  const x = tx * W + W / 2,
    y = ty * H + H / 2;
  push();
  translate(x, y);

  // achtergrond (neutraal)
  fill(240); // Lichtgrijs
  noStroke();
  rect(-W / 2, -H / 2, W, H);

  if (mediaFiles.length > 0) {
    // Use a hash of tile coordinates for consistent and unique image selection
    const str = `${tx},${ty},${scrambleSeed}`;
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
      const char = str.charCodeAt(i);
      hash = (hash << 5) - hash + char;
      hash |= 0; // Convert to 32bit integer
    }
    const index = (Math.abs(hash)) % mediaFiles.length;
    let media = mediaFiles[index];
    let filePath = media.full;
    const extension = filePath.split('.').pop().toLowerCase();

    if (['jpg', 'jpeg', 'png', 'gif'].includes(extension)) {
      const img = loadedImages[filePath];
      if (img && img !== 'loading') {
        imageMode(CENTER);
        image(img, 0, 0);
      } else if (img !== 'loading') {
        loadedImages[filePath] = 'loading'; // Mark as loading
        loadImage('../' + filePath, loadedImg => {
          const { w, h } = getScaledDimensions(loadedImg.width, loadedImg.height);
          loadedImg.resize(w, h);
          loadedImages[filePath] = loadedImg;
        });
      }
    } else if (['mp4'].includes(extension)) {
      const vid = loadedVideos[filePath];
      if (vid && vid !== 'loading') {
        imageMode(CENTER);
        const { w, h } = getScaledDimensions(vid.width, vid.height);
        image(vid, 0, 0, w, h);
      } else if (vid !== 'loading') {
        loadedVideos[filePath] = 'loading';
        const video = createVideo('../' + filePath, () => {
          video.loop();
          video.volume(0);
          loadedVideos[filePath] = video;
        });
        video.hide();
      }
    }
  }

  pop();
}

function mousePressed() {
  drag = true;
  px = mouseX;
  py = mouseY;
}

function mouseDragged() {
  if (drag) {
    ox += (mouseX - px) / s;
    oy += (mouseY - py) / s;
    px = mouseX;
    py = mouseY;
  }
}
function mouseReleased() {
  drag = false;
}
function mouseWheel(e) {
  let zs = 1.0 - Math.sign(e.deltaY) * 0.1;
  let ns = constrain(s * zs, 0.2, 5);
  s = ns;
  return false;
}
function windowResized() {
  resizeCanvas(windowWidth, windowHeight);
}

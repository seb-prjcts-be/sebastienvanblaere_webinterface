const compare = document.getElementById('compare');
const phone = document.getElementById('phone-demo');
const status = document.getElementById('demo-status');

if (compare && phone && status) {
  compare.hidden = false;
  compare.addEventListener('click', () => {
    const bright = compare.getAttribute('aria-pressed') !== 'true';
    compare.setAttribute('aria-pressed', String(bright));
    phone.classList.toggle('is-bright', bright);
    status.textContent = bright ? 'Oops! There goes the atmosphere.' : 'Less screen. More stage.';
  });
}

// An in-page install link should reveal its instructions, including a direct #install visit.
function revealInstall() {
  if (window.location.hash !== '#install') return;
  const install = document.getElementById('install');
  if (install) install.open = true;
}
window.addEventListener('hashchange', revealInstall);
document.querySelector('a[href="#install"]')?.addEventListener('click', () => {
  document.getElementById('install').open = true;
});
revealInstall();

(() => {
  'use strict';
  const install = document.getElementById('install');
  if (install) {
    if (location.hash === '#install') install.open = true;
    document.querySelector('a[href="#install"]')?.addEventListener('click', () => {
      install.open = true;
    });
  }
  const hour = document.getElementById('hour');
  const clock = document.getElementById('clock');
  const clockTime = document.getElementById('clock-time');
  const clockLabel = document.getElementById('clock-label');
  const output = document.getElementById('hour-value');
  const state = document.getElementById('demo-state');
  const controls = document.getElementById('demo-controls');
  if (!hour || !clock || !clockTime || !clockLabel || !output || !state || !controls) return;

  const update = () => {
    const value = Number(hour.value);
    const night = value >= 22 || value < 7;
    const quiet = night || value % 2 === 0;
    const time = `${String(value).padStart(2, '0')}:00`;
    const label = night ? 'Night' : quiet ? 'Quiet hour' : 'Active hour';
    clock.classList.toggle('is-quiet', quiet);
    clockTime.textContent = time;
    clockLabel.textContent = label;
    output.value = time;
    hour.setAttribute('aria-valuetext', `${time}, ${label.toLowerCase()}`);
    state.textContent = `${time} · ${label}. ${quiet
      ? 'Do Not Disturb on, screen dimmed.'
      : 'Dim layer off, previous sound setting restored.'}`;
  };

  hour.addEventListener('input', update);
  update();
  controls.hidden = false;
})();

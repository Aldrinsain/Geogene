let dark = false;

function toggle() {
  dark = !dark;
  const s = document.getElementById('scene');
  const k = document.getElementById('knob');
  const craters = k.querySelectorAll('.crater');

  if (dark) {
    document.body.classList.add('dark-bg');
    document.body.classList.remove('light-bg');
    s.className = 'scene night';
    k.className = 'knob night';
    craters.forEach(c => c.style.opacity = '1');
    document.getElementById('lbl').style.opacity = '0.4';
    document.getElementById('lbl2').style.opacity = '1';
  } else {
    document.body.classList.add('light-bg');
    document.body.classList.remove('dark-bg');
    s.className = 'scene day';
    k.className = 'knob day';
    craters.forEach(c => c.style.opacity = '0');
    document.getElementById('lbl').style.opacity = '1';
    document.getElementById('lbl2').style.opacity = '0.4';
  }
}

document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('lbl2').style.opacity = '0.4';
  document.body.classList.add('light-bg');
});

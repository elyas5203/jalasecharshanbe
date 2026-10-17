// Elite redesign enhancement: pointer spotlight only changes CSS variables, app logic remains untouched.
(() => {
  const finePointer = window.matchMedia && window.matchMedia('(pointer: fine)').matches;
  const reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (!finePointer || reducedMotion) return;
  let raf = 0;
  window.addEventListener('pointermove', (event) => {
    if (raf) return;
    raf = requestAnimationFrame(() => {
      raf = 0;
      document.documentElement.style.setProperty('--spot-x', `${Math.round((event.clientX / window.innerWidth) * 100)}%`);
      document.documentElement.style.setProperty('--spot-y', `${Math.round((event.clientY / window.innerHeight) * 100)}%`);
    });
  }, { passive: true });
})();

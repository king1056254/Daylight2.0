// Runs before first paint (loaded synchronously in <head>) so there is no theme flash.
(function () {
  var root = document.documentElement;
  root.classList.add('js');
  var theme = 'dark';
  try {
    var stored = window.localStorage.getItem('daylight-theme');
    if (stored === 'light' || stored === 'dark') {
      theme = stored;
    } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) {
      theme = 'light';
    }
  } catch (e) { /* storage blocked: keep default */ }
  root.classList.toggle('dark', theme === 'dark');
})();

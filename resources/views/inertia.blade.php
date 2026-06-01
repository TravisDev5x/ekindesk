<!DOCTYPE html>
<html lang="es">
<head>
  <script>
    (function () {
      try {
        var VALID = ['light', 'dark', 'system'];
        var stored = localStorage.getItem('ekindesk_theme') || localStorage.getItem('theme');
        var pref = VALID.indexOf(stored) >= 0 ? stored : 'system';
        var resolved = pref === 'system'
          ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
          : pref;
        var root = document.documentElement;
        root.classList.remove('light', 'dark');
        if (resolved === 'dark') {
          root.classList.add('dark');
          root.style.colorScheme = 'dark';
        } else {
          root.classList.add('light');
          root.style.colorScheme = 'light';
        }
        root.dataset.themeInit = '1';
      } catch (e) {}
    })();
  </script>

  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" media="(prefers-color-scheme: light)" content="#ffffff">
  <meta name="theme-color" media="(prefers-color-scheme: dark)" content="#000000">
  <title>HelpDesk Enterprise</title>

  @viteReactRefresh
  @vite('resources/js/inertia.jsx')
  @inertiaHead
</head>
<body>
  @inertia
</body>
</html>

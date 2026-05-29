<!DOCTYPE html>
<html lang="es">
<head>
  <script>
    (function () {
      try {
        var resolved = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        var root = document.documentElement;
        if (resolved === 'dark') {
          root.classList.add('dark');
          root.style.colorScheme = 'dark';
        } else {
          root.classList.remove('dark');
          root.style.colorScheme = 'light';
        }
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

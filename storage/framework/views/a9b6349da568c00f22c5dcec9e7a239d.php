<!DOCTYPE html>
<html lang="es">
<head>
  <!-- build: <?php echo e(config('app.env')); ?>-<?php echo e(now()->format('YmdHis')); ?> -->
  <!-- Anti-flicker: aplicar tema (clase dark) antes de que React renderice -->
  <script>
    (function () {
      try {
        var stored = localStorage.getItem('theme');
        var theme = (stored === 'dark' || stored === 'light' || stored === 'system') ? stored : 'system';
        var resolved = theme === 'system'
          ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
          : theme;
        var root = document.documentElement;
        root.dataset.themeInit = '1';
        if (resolved === 'dark') {
          root.classList.add('dark');
          root.style.colorScheme = 'dark';
        } else {
          root.classList.remove('dark');
          root.style.colorScheme = 'light';
        }
        var storedLocale = localStorage.getItem('locale');
        if (storedLocale) root.lang = storedLocale;
      } catch (e) {}
    })();
  </script>

  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" media="(prefers-color-scheme: light)" content="#ffffff">
  <meta name="theme-color" media="(prefers-color-scheme: dark)" content="#000000">
  <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">
  <title>HelpDesk Enterprise</title>

  <?php echo app('Illuminate\Foundation\Vite')->reactRefresh(); ?>
  <?php echo app('Illuminate\Foundation\Vite')('resources/js/app.jsx'); ?>
</head>

<body>
  <div id="app"></div>
</body>
</html>
<?php /**PATH C:\laragon\www\HelpdeskReact\resources\views/app.blade.php ENDPATH**/ ?>
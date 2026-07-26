<!DOCTYPE html>
<html lang="el" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= \App\Core\View::escape($title ?? 'Είσοδος') ?> — AppForm</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <!-- AppForm Design System -->
    <link href="/assets/css/style.css" rel="stylesheet">
    <link href="/assets/css/responsive.css" rel="stylesheet">

    <!-- Theme: apply before render to avoid FOUC -->
    <script>
      (function(){
        var t=localStorage.getItem('appform_theme')||'system';
        var r=(t==='system')?(window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light'):t;
        document.documentElement.setAttribute('data-theme',r);
        document.documentElement.setAttribute('data-theme-pref',t);
      })();
    </script>
</head>
<body class="auth-wrapper">

    <?= $content ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/theme.js?v=20260718-01"></script>
</body>
</html>


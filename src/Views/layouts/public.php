<!DOCTYPE html>
<html lang="el" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= \App\Core\View::escape($title ?? 'AppForm Engine') ?> — AppForm</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <!-- AppForm Design System -->
    <link href="/assets/css/style.css" rel="stylesheet">
    <link href="/assets/css/responsive.css" rel="stylesheet">

    <!-- Favicon -->
    <?php
    $favPath = '';
    try {
        $db = \App\Core\Database::getInstance();
        $favStmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'app_favicon_path'");
        $favStmt->execute();
        $favPath = (string)($favStmt->fetchColumn() ?? '');
    } catch (\Throwable $e) {
        $favPath = '';
    }
    $hasCustomFavicon = $favPath !== '' && is_file(dirname(__DIR__, 3) . '/public/' . ltrim($favPath, '/'));
    if ($hasCustomFavicon):
        $favExt = strtolower(pathinfo($favPath, PATHINFO_EXTENSION));
        $favType = $favExt === 'png' ? 'image/png' : ($favExt === 'webp' ? 'image/webp' : 'image/x-icon');
    ?>
        <link rel="icon" href="/<?= htmlspecialchars($favPath) ?>?v=<?= filemtime(dirname(__DIR__, 3) . '/public/' . ltrim($favPath, '/')) ?>" type="<?= $favType ?>">
    <?php else: ?>
        <link rel="icon" href="/assets/img/favicon.ico" type="image/x-icon">
    <?php endif; ?>

    <!-- Awesomplete Autocomplete -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/awesomplete/1.1.5/awesomplete.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/awesomplete/1.1.5/awesomplete.min.js"></script>

    <!-- Tagify Tags Select -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css" />
    <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify"></script>

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
<body style="min-height: 100vh; display: flex; flex-direction: column;">

    <!-- Standalone Clean Public Header -->
    <header class="border-bottom border-glass py-3 mb-4" style="background: var(--color-surface-soft); backdrop-filter: blur(10px);">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <span class="fs-4 fw-bold text-white"><i class="fa-solid fa-cloud-arrow-up text-primary me-2"></i>AppForm</span>
            </div>
            <div>
                <a href="/login" class="btn btn-outline-secondary btn-sm" style="font-size: 0.85rem;"><i class="fa-solid fa-sign-in me-1"></i>Σύνδεση προσωπικού</a>
            </div>
        </div>
    </header>

    <!-- Standalone Responsive Content Workspace -->
    <main class="flex-grow-1" style="min-height: auto;">
        <div class="container py-2" style="max-width: 800px; margin: 0 auto;">
            <?= $content ?>
        </div>
    </main>

    <!-- Standalone Minimal Footer -->
    <footer class="border-top border-glass py-3 mt-5 text-center" style="background: var(--color-surface-soft); font-size: 0.85rem;">
        <div class="container">
            <span class="text-muted">&copy; <?= date('Y') ?> AppForm Engine — Κέντρο Κοινωνικής Φροντίδας «Θεοτόκος». All rights reserved.</span>
        </div>
    </footer>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

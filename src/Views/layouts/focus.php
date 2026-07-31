<!DOCTYPE html>
<html lang="el" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= \App\Core\View::escape($title ?? 'AppForm Document Viewer') ?> — AppForm</title>

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
<body class="bg-dark text-white overflow-hidden" style="margin:0; padding:0; height:100vh; width:100vw;">

    <!-- Focus Mode Workspace (No Sidebar column, no header margin padding offsets) -->
    <main class="w-100 h-100 d-flex flex-column" id="main-content" role="main" style="height: 100vh; width: 100vw;">
        <!-- Page Content -->
        <div class="flex-grow-1 w-100 h-100 p-3" style="box-sizing: border-box; overflow-y: auto;">
            <?= $content ?>
        </div>
    </main>

    <!-- Global Custom Confirmation Modal -->
    <div id="app-confirm-modal" class="app-modal" role="dialog" aria-modal="true" aria-labelledby="app-confirm-title" aria-hidden="true" style="display:none;">
        <div class="app-modal-backdrop"></div>
        <div class="app-modal-dialog">
            <div class="app-modal-header">
                <h2 id="app-confirm-title" class="font-heading m-0 text-white" style="font-size: 1.25rem;">Επιβεβαίωση ενέργειας</h2>
            </div>
            <div class="app-modal-body">
                <p id="app-confirm-text" class="m-0 text-white-50">Είστε σίγουροι ότι θέλετε να προχωρήσετε;</p>
            </div>
            <div class="app-modal-footer">
                <button type="button" class="btn btn-secondary" id="app-confirm-cancel">Ακύρωση</button>
                <button type="button" class="btn btn-primary" id="app-confirm-ok">Επιβεβαίωση</button>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

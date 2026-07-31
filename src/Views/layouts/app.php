<!DOCTYPE html>
<html lang="<?= \App\Services\Lang::locale() ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= \App\Core\View::escape(__($title ?? 'AppForm Engine')) ?> — AppForm</title>

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

    <!-- Vendor libraries -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <!-- Awesomplete Autocomplete -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/awesomplete/1.1.5/awesomplete.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/awesomplete/1.1.5/awesomplete.min.js"></script>

    <!-- Tagify Tags Select -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css" />
    <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify"></script>
</head>
<body>

    <!-- Mobile backdrop -->
    <div id="sidebar-backdrop" class="sidebar-backdrop" aria-hidden="true"></div>

    <div class="app-container">
        <!-- Sidebar Navigation -->
        <?php require __DIR__ . '/sidebar.php'; ?>

        <!-- Main Workspace -->
        <main class="main-content" id="main-content" role="main">
            <!-- Top Header -->
            <?php require __DIR__ . '/header.php'; ?>

            <!-- Page Content -->
            <div class="workspace-area">
                <?= $content ?>
            </div>

            <!-- Footer -->
            <?php require __DIR__ . '/footer.php'; ?>
        </main>
    </div>

    <!-- Global Custom Confirmation Modal -->
    <div id="app-confirm-modal" class="app-modal" role="dialog" aria-modal="true" aria-labelledby="app-confirm-title" aria-hidden="true">
        <div class="app-modal-backdrop"></div>
        <div class="app-modal-dialog">
            <div class="app-modal-header">
                <h2 id="app-confirm-title" class="font-heading m-0 text-white" style="font-size: 1.25rem;"><?= __('Action Confirmation') ?></h2>
            </div>
            <div class="app-modal-body py-3">
                <p id="app-confirm-message" class="m-0" style="color: var(--color-text-soft);"><?= __('Are you sure you want to continue?') ?></p>
            </div>
            <div class="app-modal-actions d-flex justify-content-end gap-2 mt-2">
                <button type="button" id="app-confirm-cancel" class="btn btn-secondary btn-sm"><?= __('Cancel') ?></button>
                <button type="button" id="app-confirm-accept" class="btn btn-danger btn-sm"><?= __('Confirm') ?></button>
            </div>
        </div>
    </div>

    <!-- Global Signature Capture Modal -->
    <div id="app-signature-modal" class="app-modal" role="dialog" aria-modal="true" aria-labelledby="app-signature-title" aria-hidden="true">
        <div class="app-modal-backdrop"></div>
        <div class="app-modal-dialog" style="max-width: 600px;">
            <div class="app-modal-header">
                <h2 id="app-signature-title" class="font-heading m-0 text-white" style="font-size: 1.25rem;">
                    <i class="fa-solid fa-signature text-primary me-2"></i> <?= __('Digital Document Signature') ?>
                </h2>
            </div>
            <div class="app-modal-body py-3 text-center">
                <p class="small text-muted mb-3"><?= __('Draw your signature on the surface below using a mouse, touch or stylus.') ?></p>
                <div class="border border-secondary rounded overflow-hidden position-relative mb-3" style="background: var(--color-surface); height: 250px;">
                    <canvas id="signature-modal-canvas" style="display: block; width: 100%; height: 100%; touch-action: none; cursor: crosshair;"></canvas>
                </div>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" id="sig-canvas-undo" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-solid fa-undo me-1"></i> <?= __('Undo') ?>
                    </button>
                    <button type="button" id="sig-canvas-clear" class="btn btn-outline-danger btn-sm">
                        <i class="fa-solid fa-eraser me-1"></i> <?= __('Clear') ?>
                    </button>
                </div>
            </div>
            <div class="app-modal-actions d-flex justify-content-end gap-2 mt-2">
                <button type="button" id="app-signature-cancel" class="btn btn-secondary btn-sm"><?= __('Cancel') ?></button>
                <button type="button" id="signature-accept-btn" class="btn btn-success btn-sm"><?= __('Accept') ?></button>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Theme Switcher + App Utilities (load before app.js) -->
    <script src="/assets/js/theme.js?v=20260718-01"></script>

    <!-- Global Help Modal -->
    <?php require __DIR__ . '/../shared/help_modal.php'; ?>

    <!-- App JS -->
    <script src="/assets/js/app.js"></script>
</body>
</html>


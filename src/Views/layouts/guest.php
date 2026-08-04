<?php
use App\Services\Lang;
$currentLang = Lang::locale();
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= \App\Core\View::escape(__($title ?? 'Login')) ?> — AppForm</title>

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
<body class="auth-wrapper">

    <!-- Centered Login Container -->
    <div class="guest-login-wrapper">
        <!-- Language Selector (positioned directly above login card) -->
        <div class="guest-lang-row" role="navigation" aria-label="Language / Γλώσσα">
            <form action="/set-language" method="POST" style="display:inline-flex;margin:0;">
                <?= \App\Core\Csrf::field() ?>
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/login') ?>">
                <div class="lang-switcher" role="group" aria-label="Language / Γλώσσα">
                    <button type="submit" name="lang" value="el"
                            class="lang-btn <?= $currentLang === 'el' ? 'active' : '' ?>"
                            title="Ελληνικά" aria-pressed="<?= $currentLang === 'el' ? 'true' : 'false' ?>">
                        🇬🇷 EL
                    </button>
                    <button type="submit" name="lang" value="en"
                            class="lang-btn <?= $currentLang === 'en' ? 'active' : '' ?>"
                            title="English" aria-pressed="<?= $currentLang === 'en' ? 'true' : 'false' ?>">
                        🇬🇧 EN
                    </button>
                </div>
            </form>
        </div>

        <?= $content ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/theme.js?v=20260718-01"></script>
</body>
</html>

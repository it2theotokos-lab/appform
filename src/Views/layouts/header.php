<?php
use App\Core\Auth;
use App\Services\NotificationService;

$user        = Auth::user();
$userId      = Auth::id();
$unreadCount = $userId ? NotificationService::getUnreadCount($userId) : 0;
$currentPref = 'system'; // will be overridden by JS
?>
<header class="app-header" role="banner">
    <!-- Left: mobile toggle + page title -->
    <div class="header-left">
        <button class="btn-mobile-menu" id="btn-mobile-menu" aria-label="Άνοιγμα μενού" aria-controls="app-sidebar" aria-expanded="false">
            <i class="fa-solid fa-bars" aria-hidden="true"></i>
        </button>
        <div>
            <div class="header-page-title"><?= \App\Core\View::escape($title ?? 'AppForm') ?></div>
            <div class="header-page-subtitle">AppForm Enterprise Portal</div>
        </div>
    </div>

    <!-- Right: theme switcher + notifications + user -->
    <div class="header-right">

        <!-- Global Help Info Button -->
        <?php $helpCtx = \App\Services\HelpService::resolveContext(); ?>
        <button class="btn-icon" onclick="openGlobalHelp('<?= htmlspecialchars($helpCtx) ?>')" title="Οδηγίες Χρήσης" aria-label="Οδηγίες Χρήσης">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
        </button>

        <!-- Theme Switcher -->
        <div class="theme-switcher" role="group" aria-label="Επιλογή θέματος">
            <button class="theme-btn" data-theme-value="light"
                    title="Light Theme" aria-label="Φωτεινό θέμα" aria-pressed="false">
                <i class="fa-solid fa-sun" aria-hidden="true"></i>
            </button>
            <button class="theme-btn" data-theme-value="dark"
                    title="Dark Theme" aria-label="Σκοτεινό θέμα" aria-pressed="false">
                <i class="fa-solid fa-moon" aria-hidden="true"></i>
            </button>
            <button class="theme-btn" data-theme-value="system"
                    title="System Theme" aria-label="Θέμα συστήματος" aria-pressed="false">
                <i class="fa-solid fa-desktop" aria-hidden="true"></i>
            </button>
        </div>

        <!-- Notifications bell -->
        <a href="/notifications" class="btn-icon" title="Ειδοποιήσεις" aria-label="<?= $unreadCount > 0 ? "$unreadCount αδιάβαστες ειδοποιήσεις" : 'Ειδοποιήσεις' ?>">
            <i class="fa-solid fa-bell" aria-hidden="true"></i>
            <?php if ($unreadCount > 0): ?>
                <span class="notif-dot" aria-hidden="true"></span>
            <?php endif; ?>
        </a>

        <!-- User info + avatar dropdown -->
        <div class="d-flex align-items-center gap-2">
            <div class="user-info-block" aria-hidden="true">
                <div class="user-info-name"><?= \App\Core\View::escape($user['full_name'] ?? '') ?></div>
                <div class="user-info-role"><?= \App\Core\View::escape($user['role_name'] ?? '') ?></div>
            </div>
            <div class="dropdown">
                <button class="user-avatar" data-bs-toggle="dropdown" aria-expanded="false"
                        aria-label="Μενού χρήστη — <?= \App\Core\View::escape($user['full_name'] ?? '') ?>">
                    <?= substr(strtoupper($user['username'] ?? 'U'), 0, 2) ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <div class="px-3 py-2 border-bottom border" style="border-color: var(--color-border) !important;">
                            <div style="font-size:0.875rem;font-weight:600;color:var(--color-text);">
                                <?= \App\Core\View::escape($user['full_name'] ?? '') ?>
                            </div>
                            <div style="font-size:0.75rem;color:var(--color-text-muted);">
                                @<?= \App\Core\View::escape($user['username'] ?? '') ?>
                            </div>
                        </div>
                    </li>
                    <li>
                        <a class="dropdown-item" href="/admin/profile">
                            <i class="fa-solid fa-user-circle" aria-hidden="true"></i> Το Προφίλ μου
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="/notifications">
                            <i class="fa-solid fa-bell" aria-hidden="true"></i> Ειδοποιήσεις
                            <?php if ($unreadCount > 0): ?>
                                <span class="badge bg-danger ms-auto"><?= $unreadCount ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="/logout" method="POST">
                            <?= \App\Core\Csrf::field() ?>
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i> Αποσύνδεση
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>

    </div>
</header>

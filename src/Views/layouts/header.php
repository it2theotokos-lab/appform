<?php
use App\Core\Auth;
use App\Services\NotificationService;
use App\Services\Lang;

$user        = Auth::user();
$userId      = Auth::id();
$unreadCount = $userId ? NotificationService::getUnreadCount($userId) : 0;
$currentPref = 'system'; // will be overridden by JS
$currentLang = Lang::locale();

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
        <button class="btn-icon" onclick="openGlobalHelp('<?= htmlspecialchars($helpCtx) ?>')" title="<?= __('Usage Guide') ?>" aria-label="<?= __('Usage Guide') ?>">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
        </button>

        <!-- Language Selector -->
        <form action="/admin/set-language" method="POST" id="lang-switcher-form" style="display:inline-flex;margin:0;">
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/') ?>">
            <div class="lang-switcher" role="group" aria-label="Language / Γλώσσα">
                <button type="submit" name="lang" value="el"
                        class="lang-btn <?= $currentLang === 'el' ? 'active' : '' ?>"
                        title="Ελληνικά" aria-pressed="<?= $currentLang === 'el' ? 'true' : 'false' ?>">
                    ΕΛ
                </button>
                <button type="submit" name="lang" value="en"
                        class="lang-btn <?= $currentLang === 'en' ? 'active' : '' ?>"
                        title="English" aria-pressed="<?= $currentLang === 'en' ? 'true' : 'false' ?>">
                    EN
                </button>
            </div>
        </form>

        <!-- Theme Switcher -->
        <div class="theme-switcher" role="group" aria-label="<?= __("Select theme") ?>">
            <button class="theme-btn" data-theme-value="light"
                    title="Light Theme" aria-label="<?= __('Light Theme') ?>" aria-pressed="false">
                <i class="fa-solid fa-sun" aria-hidden="true"></i>
            </button>
            <button class="theme-btn" data-theme-value="dark"
                    title="Dark Theme" aria-label="<?= __('Dark Theme') ?>" aria-pressed="false">
                <i class="fa-solid fa-moon" aria-hidden="true"></i>
            </button>
            <button class="theme-btn" data-theme-value="system"
                    title="System Theme" aria-label="<?= __('System Theme') ?>" aria-pressed="false">
                <i class="fa-solid fa-desktop" aria-hidden="true"></i>
            </button>
        </div>

        <!-- Notifications bell -->
        <a href="/notifications" class="btn-icon" title="<?= __('Notifications') ?>" aria-label="<?= $unreadCount > 0 ? "$unreadCount " . __('unread notifications') : __('Notifications') ?>">
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
                        aria-label="Μενού χρήστη — <?= \App\Core\View::escape($user['full_name'] ?? '') ?>"
                        style="padding: 0; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                    <?php if (!empty($user['avatar_path']) && file_exists(dirname(dirname(dirname(__DIR__))) . '/public' . $user['avatar_path'])): ?>
                        <img src="<?= htmlspecialchars($user['avatar_path']) ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                    <?php else: ?>
                        <?= substr(strtoupper($user['username'] ?? 'U'), 0, 2) ?>
                    <?php endif; ?>
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
                            <i class="fa-solid fa-user-circle" aria-hidden="true"></i> <?= __('My Profile') ?>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="/notifications">
                            <i class="fa-solid fa-bell" aria-hidden="true"></i> <?= __('Notifications') ?>
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
                                <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i> <?= __('Logout') ?>
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>

    </div>
</header>

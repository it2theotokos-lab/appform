<?php
// Shared Settings Navigation Component
// Order of elements:
// 1. Γενικές Ρυθμίσεις (tab=general)
// 2. Backup (tab=backup)
// 3. Demo Data (tab=demo)
// 4. SMTP & Email (tab=smtp)
// 5. Global Notifications (tab=global_notifications)
// 6. Επαναφορά (tab=restore)
// 7. Cloud Backup (tab=cloud)
// 8. Ουρά Εργασιών (tab=queue)
// 9. Πρόσθετα (tab=plugins)
// 10. Αναβάθμιση (/admin/settings/updates)
// 11. Active Directory / LDAP (/admin/settings/ldap)
// 12. Καταγραφές Ενεργειών (tab=audit)

// Resolve current active tab
$currentUrl = $_SERVER['REQUEST_URI'] ?? '';
$activeTab = $_GET['tab'] ?? '';

if (str_contains($currentUrl, '/admin/settings/updates')) {
    $activeItem = 'updates';
} elseif (str_contains($currentUrl, '/admin/settings/ldap/role-mappings')) {
    $activeItem = 'ldap'; // Active Directory / LDAP tab should be active for role mappings too
} elseif (str_contains($currentUrl, '/admin/settings/ldap')) {
    $activeItem = 'ldap';
} else {
    $activeItem = $activeTab ?: 'general';
}

$hasUpdatesPermission = \App\Core\Auth::hasPermission('updates.view');
?>
<style>
.settings-nav-card {
    background-color: #1e1e1e !important;
    border-color: #2d2d2d !important;
    padding: 0.5rem !important;
}
.settings-nav-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin: 0;
    padding: 0;
    list-style: none;
}
.settings-nav-pills .nav-item {
    flex: 1 1 auto;
    min-width: 140px;
    margin: 0 !important;
}
.settings-nav-pills .nav-link {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 40px;
    padding: 0 1rem;
    font-size: 0.9rem;
    border-radius: 4px;
    color: #aeaeae;
    background-color: transparent;
    transition: all 0.2s ease;
    white-space: nowrap;
}
.settings-nav-pills .nav-link:hover {
    color: #ffffff;
    background-color: rgba(255, 255, 255, 0.05);
}
.settings-nav-pills .nav-link.active {
    color: #ffffff !important;
    background-color: #0d6efd !important;
}
.settings-nav-pills .nav-link i {
    font-size: 1rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
</style>
<!-- Tabs Navigation Header -->
<div class="card p-2 mb-4 bg-dark border-secondary settings-nav-card">
    <ul class="nav nav-pills nav-fill settings-nav-pills" role="tablist">
        <li class="nav-item">
            <a class="nav-link <?= $activeItem === 'general' ? 'active' : '' ?>" href="/admin/settings?tab=general">
                <i class="fa-solid fa-sliders me-1"></i> Γενικές Ρυθμίσεις
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeItem === 'backup' ? 'active' : '' ?>" href="/admin/settings?tab=backup">
                <i class="fa-solid fa-database me-1"></i> Backup
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeItem === 'demo' ? 'active' : '' ?>" href="/admin/settings?tab=demo">
                <i class="fa-solid fa-cubes me-1"></i> Demo Data
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeItem === 'smtp' ? 'active' : '' ?>" href="/admin/settings?tab=smtp">
                <i class="fa-solid fa-envelope me-1"></i> SMTP & Email
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeItem === 'global_notifications' ? 'active' : '' ?>" href="/admin/settings?tab=global_notifications">
                <i class="fa-solid fa-envelope-circle-check me-1"></i> Global Notifications
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeItem === 'restore' ? 'active' : '' ?>" href="/admin/settings?tab=restore">
                <i class="fa-solid fa-clock-rotate-left me-1"></i> Επαναφορά
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeItem === 'cloud' ? 'active' : '' ?>" href="/admin/settings?tab=cloud">
                <i class="fa-solid fa-cloud me-1"></i> Cloud Backup
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeItem === 'queue' ? 'active' : '' ?>" href="/admin/settings?tab=queue">
                <i class="fa-solid fa-list-check me-1"></i> Ουρά Εργασιών
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeItem === 'plugins' ? 'active' : '' ?>" href="/admin/settings?tab=plugins">
                <i class="fa-solid fa-puzzle-piece me-1"></i> Πρόσθετα
            </a>
        </li>
        <?php if ($hasUpdatesPermission): ?>
        <li class="nav-item">
            <a class="nav-link <?= $activeItem === 'updates' ? 'active' : '' ?>" href="/admin/settings/updates">
                <i class="fa-solid fa-cloud-arrow-down me-1"></i> Αναβάθμιση
            </a>
        </li>
        <?php endif; ?>
        <li class="nav-item">
            <a class="nav-link <?= $activeItem === 'ldap' ? 'active' : '' ?>" href="/admin/settings/ldap">
                <i class="fa-solid fa-server me-1"></i> Active Directory / LDAP
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeItem === 'audit' ? 'active' : '' ?>" href="/admin/settings?tab=audit">
                <i class="fa-solid fa-clock-rotate-left me-1"></i> Καταγραφές Ενεργειών
            </a>
        </li>
    </ul>
</div>

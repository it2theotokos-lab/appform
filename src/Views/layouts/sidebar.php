<?php
use App\Core\Auth;
use App\Services\NotificationService;
use App\Core\Database;

$user      = Auth::user();
$roleSlug  = Auth::role();
$userId    = Auth::id();
$unreadCount = $userId ? NotificationService::getUnreadCount($userId) : 0;
$currentUri  = strtok($_SERVER['REQUEST_URI'], '?');

// ── Sidebar menu definition by role ─────────────────────────────────
$menuSections = [];

if ($roleSlug === 'administrator') {
    $menuSections = [
        [
            'title' => 'Γενικά',
            'items' => [
                ['label' => 'Dashboard',          'route' => '/dashboard',          'icon' => 'fa-solid fa-gauge-high'],
            ]
        ],
        [
            'title' => 'Διαχείριση',
            'items' => [
                ['label' => 'Χρήστες',            'route' => '/admin/users',         'icon' => 'fa-solid fa-users'],
                ['label' => 'Ρόλοι',              'route' => '/admin/roles',         'icon' => 'fa-solid fa-user-shield'],
                ['label' => 'Repositories',        'route' => '/admin/repositories',  'icon' => 'fa-solid fa-database'],
            ]
        ],
        [
            'title' => 'Φόρμες',
            'items' => [
                ['label' => 'Φόρμες',             'route' => '/admin/forms',         'icon' => 'fa-solid fa-file-invoice'],
                ['label' => 'Πρότυπα Εγγράφων',   'route' => '/admin/document-templates', 'icon' => 'fa-solid fa-file-pdf', 'permission' => 'document_templates.view'],
                ['label' => 'Σχεδιασμός Workflow', 'route' => '/admin/workflows',     'icon' => 'fa-solid fa-diagram-project'],
                ['label' => 'Navigation',          'route' => '/admin/menus',         'icon' => 'fa-solid fa-bars'],
            ]
        ],
        [
            'title' => 'Workflow',
            'items' => [
                ['label' => 'Υποβολές',           'route' => '/admin/submissions',   'icon' => 'fa-solid fa-envelope-open-text'],
                ['label' => 'Notifications',       'route' => '/notifications',       'icon' => 'fa-solid fa-bell', 'badge' => $unreadCount],
            ]
        ],
        [
            'title' => 'Αναφορές',
            'items' => [
                ['label' => 'Analytics',           'route' => '/admin/analytics',     'icon' => 'fa-solid fa-chart-line'],
                ['label' => 'Εισαγωγές/Εξαγωγές',   'route' => '/admin/data-exchange', 'icon' => 'fa-solid fa-arrow-right-arrow-left', 'permission' => 'data_exchange.view'],
                ['label' => 'Audit Logs',          'route' => '/admin/audit',         'icon' => 'fa-solid fa-clock-rotate-left', 'permission' => 'audit.view'],
            ]
        ],
        [
            'title' => 'Σύστημα',
            'items' => [
                ['label' => 'Ρυθμίσεις',          'route' => '/admin/settings',      'icon' => 'fa-solid fa-gears', 'permission' => 'settings.manage'],
                ['label' => 'System Health',      'route' => '/admin/health',        'icon' => 'fa-solid fa-heart-pulse'],
            ]
        ],
        [
            'title' => 'Έγγραφα',
            'items' => [
                ['label' => 'Νέο Έγγραφο',         'route' => '/documents/templates', 'icon' => 'fa-solid fa-file-signature'],
                ['label' => 'Τα Πρόχειρά μου',     'route' => '/documents/drafts',    'icon' => 'fa-solid fa-file-pen'],
                ['label' => 'Οι Υποβολές μου',     'route' => '/documents/submissions', 'icon' => 'fa-solid fa-folder-open'],
                ['label' => 'Εργασίες Έγκρισης',   'route' => '/workflow/tasks',       'icon' => 'fa-solid fa-square-check'],
            ]
        ],
    ];
} elseif ($roleSlug === 'manager' || $roleSlug === 'reviewer') {
    $menuSections = [
        [
            'title' => 'Γενικά',
            'items' => [
                ['label' => 'Dashboard',   'route' => '/dashboard',         'icon' => 'fa-solid fa-gauge-high'],
            ]
        ],
        [
            'title' => 'Workflow',
            'items' => [
                ['label' => 'Υποβολές',   'route' => '/admin/submissions',  'icon' => 'fa-solid fa-envelope-open-text'],
                ['label' => 'Notifications','route' => '/notifications',     'icon' => 'fa-solid fa-bell', 'badge' => $unreadCount],
            ]
        ],
        [
            'title' => 'Αναφορές',
            'items' => [
                ['label' => 'Analytics',   'route' => '/admin/analytics',   'icon' => 'fa-solid fa-chart-line'],
            ]
        ],
        [
            'title' => 'Έγγραφα',
            'items' => [
                ['label' => 'Νέο Έγγραφο',         'route' => '/documents/templates', 'icon' => 'fa-solid fa-file-signature'],
                ['label' => 'Τα Πρόχειρά μου',     'route' => '/documents/drafts',    'icon' => 'fa-solid fa-file-pen'],
                ['label' => 'Οι Υποβολές μου',     'route' => '/documents/submissions', 'icon' => 'fa-solid fa-folder-open'],
                ['label' => 'Εργασίες Έγκρισης',   'route' => '/workflow/tasks',       'icon' => 'fa-solid fa-square-check'],
            ]
        ],
    ];
} else {
    // Regular user
    $menuSections = [
        [
            'title' => 'Γενικά',
            'items' => [
                ['label' => 'Dashboard',                'route' => '/dashboard',      'icon' => 'fa-solid fa-gauge-high'],
                ['label' => 'Οι Υποβολές Φορμών μου',  'route' => '/my-submissions', 'icon' => 'fa-solid fa-receipt'],
                ['label' => 'Notifications',            'route' => '/notifications',  'icon' => 'fa-solid fa-bell', 'badge' => $unreadCount],
            ]
        ],
        [
            'title' => 'Έγγραφα (PDF)',
            'items' => [
                ['label' => 'Νέο Έγγραφο',                 'route' => '/documents/templates', 'icon' => 'fa-solid fa-file-signature'],
                ['label' => 'Τα Πρόχειρα Εγγράφων μου',   'route' => '/documents/drafts',    'icon' => 'fa-solid fa-file-pen'],
                ['label' => 'Τα Υποβληθέντα Έγγραφά μου', 'route' => '/documents/submissions', 'icon' => 'fa-solid fa-folder-open'],
                ['label' => 'Εργασίες Έγκρισης',           'route' => '/workflow/tasks',       'icon' => 'fa-solid fa-square-check'],
            ]
        ],
    ];
}

// ── Append Dynamic Menu Items from Database ────────────────────────
try {
    $db = Database::getInstance();
    $roleId = $user['role_id'] ?? null;
    if ($roleId) {
        $stmtMenu = $db->prepare("SELECT id FROM navigation_menus WHERE role_id = ? AND is_active = 1 LIMIT 1");
        $stmtMenu->execute([$roleId]);
        $menuId = $stmtMenu->fetchColumn();

        if ($menuId) {
            $rawDbItems = \App\Models\NavigationMenuItem::getTreeByMenuId((int)$menuId, true);

            if (!empty($rawDbItems)) {
                // Build nested tree structure
                $buildNested = function(array $items, ?int $parentId = null) use (&$buildNested) {
                    $branch = [];
                    foreach ($items as $item) {
                        $itemParentId = $item['parent_id'] !== null ? (int)$item['parent_id'] : null;
                        if ($itemParentId === $parentId) {
                            // Resolve route
                            $route = $item['route_name'] ?? $item['url'] ?? '#';
                            if ($item['item_type'] === 'form' && !empty($item['form_id'])) {
                                $fObj = \App\Models\Form::findById((int)$item['form_id']);
                                if ($fObj && !empty($fObj['slug'])) {
                                    $route = "/forms/" . $fObj['slug'];
                                } else {
                                    $route = "/forms/" . $item['form_id'];
                                }
                            }
                            
                            $children = $buildNested($items, (int)$item['id']);
                            $branch[] = [
                                'id' => $item['id'],
                                'label' => $item['label'],
                                'route' => $route,
                                'icon' => $item['icon'] ?: 'fa-solid fa-link',
                                'permission' => $item['permission_slug'] ?: null,
                                'target' => !empty($item['open_in_new_tab']) ? '_blank' : '_self',
                                'children' => $children
                            ];
                        }
                    }
                    return $branch;
                };

                $dynItems = $buildNested($rawDbItems, null);

                if (!empty($dynItems)) {
                    $menuSections[] = [
                        'title' => 'Συνδέσμοι Πλοήγησης',
                        'items' => $dynItems
                    ];
                }
            }
        }
    }
} catch (\Exception $e) {}

// Helper: is route active?
if (!function_exists('isActive')) {
    function isActive(string $route, string $current): bool {
        if ($route === '/dashboard') return $current === '/dashboard';
        if ($route === '#' || empty($route)) return false;
        return str_starts_with($current, $route);
    }
}

// Helper: does branch have an active child route?
if (!function_exists('hasActiveChild')) {
    function hasActiveChild(array $item, string $currentUri): bool {
        if (!empty($item['route']) && isActive($item['route'], $currentUri)) return true;
        if (!empty($item['children'])) {
            foreach ($item['children'] as $child) {
                if (hasActiveChild($child, $currentUri)) return true;
            }
        }
        return false;
    }
}
?>

<aside class="sidebar" id="app-sidebar" role="navigation" aria-label="Κύριο μενού">
    <!-- Logo -->
    <div class="sidebar-header">
        <a href="/dashboard" class="sidebar-logo" aria-label="AppForm - Αρχική">
            <div class="sidebar-logo-icon" aria-hidden="true">
                <i class="fa-solid fa-cubes-stacked"></i>
            </div>
            <span class="sidebar-logo-text">App<span>Form</span></span>
        </a>
    </div>

    <!-- Nav Sections -->
    <div class="sidebar-body">
        <?php foreach ($menuSections as $section): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title" aria-hidden="true"><?= htmlspecialchars($section['title']) ?></div>
                <ul class="nav-menu" role="list">
                    <?php 
                    $renderMenuItem = function($item, $depth = 0) use (&$renderMenuItem, $currentUri) {
                        if (isset($item['permission']) && !\App\Core\Auth::hasPermission($item['permission'])) return;
                        
                        $hasChildren = !empty($item['children']);
                        $active = isActive($item['route'] ?? '', $currentUri);
                        $branchActive = hasActiveChild($item, $currentUri);
                        
                        // Skip empty parent with no visible children and no valid route
                        if ($hasChildren) {
                            $visibleChildren = array_filter($item['children'], function($c) {
                                return !isset($c['permission']) || \App\Core\Auth::hasPermission($c['permission']);
                            });
                            if (empty($visibleChildren) && ($item['route'] === '#' || empty($item['route']))) return;
                        }

                        $paddingLeft = $depth * 15;
                    ?>
                        <li class="nav-item <?= ($active || $branchActive) ? 'active' : '' ?> <?= $hasChildren ? 'has-submenu' : '' ?> <?= ($branchActive) ? 'is-open' : '' ?>" 
                            data-menu-id="<?= isset($item['id']) ? (int)$item['id'] : '' ?>"
                            role="listitem" 
                            style="padding-left: <?= $paddingLeft ?>px;">
                            <?php if ($hasChildren): ?>
                                <button type="button" 
                                        class="d-flex justify-content-between align-items-center w-100 bg-transparent border-0 nav-item-btn dropdown-toggle-nav"
                                        aria-expanded="<?= ($branchActive) ? 'true' : 'false' ?>"
                                        title="<?= htmlspecialchars($item['label']) ?>">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="nav-icon <?= htmlspecialchars($item['icon'] ?? 'fa-solid fa-link') ?>" aria-hidden="true"></i>
                                        <span class="nav-label text-start"><?= htmlspecialchars($item['label']) ?></span>
                                    </div>
                                    <i class="fa-solid fa-chevron-down chevron-icon small ms-1 <?= $branchActive ? 'rotate-180' : '' ?>" style="transition: transform 0.2s;"></i>
                                </button>
                                <ul class="nav-submenu list-unstyled ms-2 mt-1 <?= $branchActive ? '' : 'd-none' ?>">
                                    <?php foreach ($item['children'] as $child): ?>
                                        <?php $renderMenuItem($child, $depth + 1); ?>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <a href="<?= htmlspecialchars($item['route']) ?>"
                                   target="<?= htmlspecialchars($item['target'] ?? '_self') ?>"
                                   <?= $active ? 'aria-current="page"' : '' ?>
                                   title="<?= htmlspecialchars($item['label']) ?>">
                                    <i class="nav-icon <?= htmlspecialchars($item['icon'] ?? 'fa-solid fa-link') ?>" aria-hidden="true"></i>
                                    <span class="nav-label"><?= htmlspecialchars($item['label']) ?></span>
                                    <?php if (!empty($item['badge'])): ?>
                                        <span class="nav-badge" aria-label="<?= (int)$item['badge'] ?> αδιάβαστες ειδοποιήσεις">
                                             <?= (int)$item['badge'] ?>
                                        </span>
                                    <?php endif; ?>
                                </a>
                            <?php endif; ?>
                        </li>
                    <?php 
                    };

                    foreach ($section['items'] as $item) {
                        $renderMenuItem($item, 0);
                    }
                    ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Footer: User info + Logout -->
    <div class="sidebar-footer">
        <!-- User mini-profile -->
        <div class="d-flex align-items-center gap-2 mb-3 px-2">
            <div class="user-avatar" style="width:32px;height:32px;font-size:0.75rem;padding: 0; overflow: hidden; display: flex; align-items: center; justify-content: center;" aria-hidden="true">
                <?php if (!empty($user['avatar_path']) && file_exists(dirname(dirname(dirname(__DIR__))) . '/public' . $user['avatar_path'])): ?>
                    <img src="<?= htmlspecialchars($user['avatar_path']) ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                <?php else: ?>
                    <?= substr(strtoupper($user['username'] ?? 'U'), 0, 2) ?>
                <?php endif; ?>
            </div>
            <div style="min-width:0;flex:1">
                <div style="font-size:0.8125rem;font-weight:600;color:var(--color-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    <?= \App\Core\View::escape($user['full_name'] ?? '') ?>
                </div>
                <div style="font-size:0.6875rem;color:var(--color-text-muted);">
                    <?= \App\Core\View::escape($user['role_name'] ?? '') ?>
                </div>
            </div>
        </div>

        <!-- Logout -->
        <form action="/logout" method="POST">
            <?= \App\Core\Csrf::field() ?>
            <button type="submit" class="sidebar-logout-btn" aria-label="Αποσύνδεση από το σύστημα">
                <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>
                <span>Αποσύνδεση</span>
            </button>
        </form>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('app-sidebar');
    if (!sidebar) return;

    // Single Centralized Event Listener for Submenu Toggle
    sidebar.addEventListener('click', function(e) {
        const toggleBtn = e.target.closest('.dropdown-toggle-nav');
        if (!toggleBtn) return;

        e.preventDefault();
        e.stopPropagation();

        const parentLi = toggleBtn.closest('.nav-item');
        if (!parentLi) return;

        const sub = parentLi.querySelector('.nav-submenu');
        const chev = parentLi.querySelector('.chevron-icon');

        const isOpen = parentLi.classList.contains('is-open');

        if (isOpen) {
            parentLi.classList.remove('is-open');
            if (sub) sub.classList.add('d-none');
            toggleBtn.setAttribute('aria-expanded', 'false');
            if (chev) chev.classList.remove('rotate-180');
        } else {
            parentLi.classList.add('is-open');
            if (sub) sub.classList.remove('d-none');
            toggleBtn.setAttribute('aria-expanded', 'true');
            if (chev) chev.classList.add('rotate-180');
        }
    });
});
</script>

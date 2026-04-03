<?php
/**
 * Sidebar navigation
 * Detects the active menu item from REQUEST_URI
 */
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

/**
 * Return 'active' CSS class if $pattern matches the current URI.
 */
function navActive(string $pattern): string
{
    global $uri;
    return preg_match('#' . $pattern . '#i', $uri) ? 'active' : '';
}
?>
<nav class="sidebar" id="sidebar" role="navigation" aria-label="Main navigation">

    <!-- Sidebar header -->
    <div class="sidebar-header">
        <span class="sidebar-title">Navigation</span>
        <button class="sidebar-close-btn" id="sidebarClose" aria-label="Close sidebar">&times;</button>
    </div>

    <ul class="nav-list">

        <!-- Dashboard -->
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/" class="nav-link <?= navActive('^/repair-system/public/?$') ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                </svg>
                <span>Dashboard</span>
            </a>
        </li>

        <!-- Repairs -->
        <li class="nav-item has-sub <?= navActive('/repairs') ?>">
            <button class="nav-link nav-group-toggle" aria-expanded="<?= navActive('/repairs') ? 'true' : 'false' ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                </svg>
                <span>Repairs</span>
                <svg class="sub-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <polyline points="6 9 12 15 18 9"/>
                </svg>
            </button>
            <ul class="sub-nav <?= navActive('/repairs') ? 'open' : '' ?>">
                <li><a href="<?= BASE_URL ?>/repairs/create" class="sub-nav-link <?= navActive('/repairs/create') ?>">+ New Repair</a></li>
                <li><a href="<?= BASE_URL ?>/repairs" class="sub-nav-link <?= navActive('^.*/repairs/?$') ?>">All Repairs</a></li>
            </ul>
        </li>

        <!-- Customers -->
        <li class="nav-item has-sub <?= navActive('/customers') ?>">
            <button class="nav-link nav-group-toggle" aria-expanded="<?= navActive('/customers') ? 'true' : 'false' ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                <span>Customers</span>
                <svg class="sub-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <polyline points="6 9 12 15 18 9"/>
                </svg>
            </button>
            <ul class="sub-nav <?= navActive('/customers') ? 'open' : '' ?>">
                <li><a href="<?= BASE_URL ?>/customers/create" class="sub-nav-link <?= navActive('/customers/create') ?>">+ New Customer</a></li>
                <li><a href="<?= BASE_URL ?>/customers" class="sub-nav-link <?= navActive('^.*/customers/?$') ?>">All Customers</a></li>
            </ul>
        </li>

        <!-- Invoices -->
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/invoices" class="nav-link <?= navActive('/invoices') ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                    <polyline points="10 9 9 9 8 9"/>
                </svg>
                <span>Invoices</span>
            </a>
        </li>

        <!-- Reports (manager+) -->
        <?php if (Auth::can('manager')): ?>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/reports" class="nav-link <?= navActive('/reports') ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="18" y1="20" x2="18" y2="10"/>
                    <line x1="12" y1="20" x2="12" y2="4"/>
                    <line x1="6"  y1="20" x2="6"  y2="14"/>
                </svg>
                <span>Reports</span>
            </a>
        </li>
        <?php endif; ?>

        <!-- Staff (manager+) -->
        <?php if (Auth::can('manager')): ?>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/staff" class="nav-link <?= navActive('/staff') ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                <span>Staff</span>
            </a>
        </li>
        <?php endif; ?>

        <!-- Admin section (admin only) -->
        <?php if (Auth::isAdmin()): ?>
        <li class="nav-divider" role="separator"></li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/admin/settings" class="nav-link <?= navActive('/admin') ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06A1.65 1.65 0 0 0 15 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 8.6 15a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 12 8.6a1.65 1.65 0 0 0 1.82.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 15z"/>
                </svg>
                <span>Settings</span>
            </a>
        </li>
        <?php endif; ?>

    </ul>

    <!-- Sidebar footer: logout -->
    <div class="sidebar-footer">
        <a href="<?= BASE_URL ?>/logout" class="sidebar-logout">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            Logout
        </a>
    </div>

</nav>

<!-- Mobile overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>

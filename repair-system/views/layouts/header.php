<?php
/**
 * Main layout header — included at the top of every authenticated view.
 * Expects $pageTitle to be set by the calling view.
 */
$pageTitle   = $pageTitle ?? APP_NAME;
$flashMsgs   = Utils::getFlashMessages();
$currentUser = Auth::user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?= Utils::e($pageTitle) ?> — <?= Utils::e(APP_NAME) ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/img/favicon.svg">

    <!-- App stylesheet -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">

    <!-- Chart.js (lazy-loaded only on dashboard/reports) -->
    <?php if (isset($loadCharts) && $loadCharts): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>
    <?php endif; ?>
</head>
<body>

<!-- ══════════════════════════════════════════════════════════════
     TOP NAV BAR
══════════════════════════════════════════════════════════════ -->
<header class="topbar" role="banner">
    <!-- Hamburger (mobile) -->
    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar" aria-expanded="false">
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
    </button>

    <!-- Logo + app name -->
    <a href="<?= BASE_URL ?>/" class="topbar-brand">
        <svg class="brand-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
             aria-hidden="true">
            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
        </svg>
        <span class="brand-name"><?= Utils::e(APP_NAME) ?></span>
    </a>

    <!-- Right side controls -->
    <div class="topbar-right">
        <!-- Notification bell placeholder -->
        <button class="topbar-icon-btn" aria-label="Notifications" title="Notifications">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
        </button>

        <!-- User menu -->
        <div class="user-menu" id="userMenu">
            <button class="user-menu-trigger" aria-haspopup="true" aria-expanded="false" id="userMenuBtn">
                <div class="user-avatar" aria-hidden="true">
                    <?= strtoupper(substr($currentUser['first_name'] ?? $currentUser['username'] ?? 'U', 0, 1)) ?>
                </div>
                <div class="user-info">
                    <span class="user-name"><?= Utils::e($currentUser['full_name'] ?: $currentUser['username']) ?></span>
                    <span class="user-role"><?= Utils::e(USER_ROLES[$currentUser['role']] ?? $currentUser['role']) ?></span>
                </div>
                <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" aria-hidden="true">
                    <polyline points="6 9 12 15 18 9"/>
                </svg>
            </button>

            <div class="user-dropdown" id="userDropdown" role="menu" hidden>
                <a href="<?= BASE_URL ?>/profile" role="menuitem">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    My Profile
                </a>
                <?php if (Auth::isAdmin()): ?>
                <a href="<?= BASE_URL ?>/admin/settings" role="menuitem">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06A1.65 1.65 0 0 0 15 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 8.6 15a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 12 8.6a1.65 1.65 0 0 0 1.82.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 15z"/>
                    </svg>
                    Settings
                </a>
                <?php endif; ?>
                <hr class="dropdown-divider">
                <a href="<?= BASE_URL ?>/logout" role="menuitem" class="logout-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    Logout
                </a>
            </div>
        </div>
    </div>
</header>

<!-- ══════════════════════════════════════════════════════════════
     FLASH MESSAGES
══════════════════════════════════════════════════════════════ -->
<?php if ($flashMsgs): ?>
<div class="flash-container" id="flashContainer" role="alert" aria-live="polite">
    <?php foreach ($flashMsgs as $type => $messages): ?>
        <?php foreach ($messages as $msg): ?>
        <div class="flash flash-<?= Utils::e($type) ?>">
            <?= Utils::e($msg) ?>
            <button class="flash-close" aria-label="Dismiss">&times;</button>
        </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════════
     LAYOUT WRAPPER
══════════════════════════════════════════════════════════════ -->
<div class="layout-wrapper">

    <!-- Sidebar -->
    <?php require VIEWS_PATH . '/layouts/sidebar.php'; ?>

    <!-- Main content -->
    <main class="main-content" id="mainContent" role="main">

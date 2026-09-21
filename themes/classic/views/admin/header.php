<?php
/**
 * Admin Panel Header
 * Completely Separate Admin Navigation - Admin Access Only - No User/Public Links
 */
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/currency.php';

Auth::requireAdmin();
$adminUser = Auth::user();
$siteName = e(get_setting('site_name', 'Rose SMM Panel'));
$currentPage = $currentPage ?? 'admin_dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? 'Admin Console') ?> - <?= $siteName ?></title>
  <link rel="stylesheet" href="/themes/classic/assets/css/style.css">
</head>
<body>

<div class="panel-wrapper">
  <?php require __DIR__ . '/sidebar.php'; ?>

  <div class="panel-main">
    <header class="panel-topbar" id="admin_topbar" style="border-bottom-color: var(--rose-200);">
      <div style="display: flex; align-items: center; gap: 0.75rem;">
        <button type="button" class="mobile-toggle" id="admin_mobile_toggle" aria-label="Toggle navigation">
          &#9776;
        </button>
        <div style="display: flex; align-items: center; gap: 0.5rem;">
          <h2 style="font-size: 1.125rem; font-weight: 700; margin: 0;"><?= e($pageTitle ?? 'Admin Console') ?></h2>
          <span class="admin-badge-indicator">Admin</span>
        </div>
      </div>

      <div style="display: flex; align-items: center; gap: 1rem;">
        <span style="font-size: 0.875rem; color: var(--text-muted);">
          Logged in as <strong><?= e($adminUser['username']) ?></strong>
        </span>
        <a href="/logout" class="btn btn-secondary btn-sm" id="admin_topbar_logout">Logout</a>
      </div>
    </header>

    <div class="panel-content">
      <?php if ($flash = flash_get()): ?>
        <div class="alert alert-<?= e($flash['type']) ?>" id="admin_flash_alert">
          <span><?= e($flash['message']) ?></span>
        </div>
      <?php endif; ?>

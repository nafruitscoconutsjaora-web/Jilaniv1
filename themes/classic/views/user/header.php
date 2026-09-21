<?php
/**
 * User Panel Header
 * Completely Separate User Navigation - No Admin Links / No Public Landing Navigation
 */
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/currency.php';

Auth::requireLogin();
$user = Auth::user();
$userCurrency = Currency::getUserCurrency($user);
$activeCurrencies = Currency::getAllActive();
$siteName = e(get_setting('site_name', 'Rose SMM Panel'));
$currentPage = $currentPage ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? 'User Portal') ?> - <?= $siteName ?></title>
  <link rel="stylesheet" href="/themes/classic/assets/css/style.css">
  <script>
    window.userCurrencySymbol = "<?= e($userCurrency['symbol']) ?>";
    window.userCurrencyRate = <?= (float)$userCurrency['rate'] ?>;
    window.userCurrencyCode = "<?= e($userCurrency['code']) ?>";
  </script>
</head>
<body>

<div class="panel-wrapper">
  <?php require __DIR__ . '/sidebar.php'; ?>

  <div class="panel-main">
    <header class="panel-topbar" id="user_topbar">
      <div style="display: flex; align-items: center; gap: 0.75rem;">
        <button type="button" class="mobile-toggle" id="user_mobile_toggle" aria-label="Toggle navigation">
          &#9776;
        </button>
        <h2 style="font-size: 1.125rem; font-weight: 700; margin: 0;"><?= e($pageTitle ?? 'Dashboard') ?></h2>
      </div>

      <div style="display: flex; align-items: center; gap: 0.875rem; flex-wrap: wrap;">
        <!-- Real Wallet Balance in INR -->
        <div class="user-balance-pill" id="user_wallet_pill">
          <span style="font-weight: 500; opacity: 0.85;">Balance:</span>
          <span style="font-weight: 800;">₹<?= number_format((float)$user['balance'], 2) ?></span>
        </div>

        <a href="/add-funds" class="btn btn-primary btn-sm" id="topbar_add_funds_btn">+ Add Funds</a>
      </div>
    </header>

    <div class="panel-content">
      <?php if ($flash = flash_get()): ?>
        <div class="alert alert-<?= e($flash['type']) ?>" id="user_flash_alert">
          <span><?= e($flash['message']) ?></span>
        </div>
      <?php endif; ?>

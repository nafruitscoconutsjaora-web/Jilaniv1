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

      <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
        <!-- Currency Selection - ONLY Visible to Authenticated Logged-In Users -->
        <form method="POST" action="/user/currency" class="currency-selector-form" id="user_currency_form">
          <?= csrf_field() ?>
          <label for="user_currency_select" style="font-size: 0.8125rem; font-weight: 600; color: var(--text-muted);">Currency:</label>
          <select name="currency" id="user_currency_select" class="currency-select" onchange="this.form.submit()">
            <?php foreach ($activeCurrencies as $c): ?>
              <option value="<?= e($c['code']) ?>" <?= $user['currency_code'] === $c['code'] ? 'selected' : '' ?>>
                <?= e($c['code']) ?> (<?= e($c['symbol']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </form>

        <!-- Real Wallet Balance with Converted Numerical Value -->
        <div class="user-balance-pill" id="user_wallet_pill">
          <span>Balance:</span>
          <span><?= Currency::format((float)$user['balance'], $userCurrency) ?></span>
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

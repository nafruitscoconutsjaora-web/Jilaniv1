<?php
/**
 * User Panel Header
 * Matches Rose + White reference image design (70692DF5-E0CE-4C17-B77E-0FB36661524C)
 */
$user = Auth::user();
$activeCurrencies = Currency::getActive();
$userCurrency = Currency::getUserCurrency($user);
$siteName = e(get_setting('site_name', 'RoseSMM'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? 'Dashboard') ?> - <?= $siteName ?></title>
  <link rel="stylesheet" href="/themes/classic/assets/css/style.css">
  <script>
    window.userCurrencyRate = <?= (float)$userCurrency['rate'] ?>;
    window.userCurrencyCode = "<?= e($userCurrency['code']) ?>";
    window.userCurrencySymbol = "<?= e($userCurrency['symbol']) ?>";
    try {
      const savedTheme = localStorage.getItem('rosesmm_theme');
      if (savedTheme === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
      }
    } catch(e) {}
  </script>
</head>
<body>

<div class="panel-wrapper">
  <?php require __DIR__ . '/sidebar.php'; ?>

  <div class="panel-main">
    <header class="panel-topbar" id="user_topbar" style="padding: 0.75rem 1.5rem;">
      <div style="display: flex; align-items: center; gap: 1rem; flex: 1;">
        <button type="button" class="mobile-toggle" id="user_mobile_toggle" aria-label="Toggle navigation">
          &#9776;
        </button>

        <!-- Search Bar matching reference image -->
        <div class="topbar-search" id="topbar_search_box">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--text-light); flex-shrink: 0;">
            <circle cx="11" cy="11" r="8"/>
            <path d="m21 21-4.3-4.3"/>
          </svg>
          <input type="text" id="topbar_service_search" placeholder="Search for services..." onkeydown="if(event.key==='Enter'){ window.location.href='/user/services?search=' + encodeURIComponent(this.value); }">
        </div>
      </div>

      <!-- Right Action Items -->
      <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
        <!-- Notification Bell with count -->
        <a href="/tickets" class="topbar-icon-btn" id="topbar_bell_btn" title="Notifications">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
          </svg>
          <span class="topbar-badge" id="notif_badge">3</span>
        </a>

        <!-- Dark/Light Mode Moon Toggle -->
        <button type="button" class="topbar-icon-btn" id="topbar_darkmode_btn" onclick="toggleDashboardTheme()" title="Toggle Dark/Light Mode">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>
          </svg>
        </button>

        <!-- Language Selector -->
        <div style="display: flex; align-items: center; gap: 0.35rem; background: #ffffff; border: 1px solid var(--rose-200); border-radius: var(--radius-pill); padding: 0.35rem 0.65rem; font-size: 0.8125rem; font-weight: 700; color: var(--text-main);">
          <span>&#127482;&#127480;</span>
          <span>EN</span>
        </div>

        <!-- Currency Selector Form -->
        <form method="POST" action="/user/currency" class="currency-selector-form" id="user_currency_form" style="margin: 0;">
          <?= csrf_field() ?>
          <select name="currency" id="user_currency_select" class="currency-select" onchange="this.form.submit()" style="border-radius: var(--radius-pill); padding: 0.35rem 0.65rem; font-size: 0.8125rem;">
            <?php foreach ($activeCurrencies as $c): ?>
              <option value="<?= e($c['code']) ?>" <?= $user['currency_code'] === $c['code'] ? 'selected' : '' ?>>
                <?= e($c['code']) ?> (<?= e($c['symbol']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </form>

        <!-- Wallet Balance Widget matching reference image -->
        <div class="user-balance-widget" id="user_wallet_widget">
          <div class="wallet-icon-circle">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/>
              <path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/>
              <path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/>
            </svg>
          </div>
          <div class="wallet-meta">
            <span class="wallet-val" id="topbar_wallet_amount"><?= Currency::format((float)$user['balance'], $userCurrency) ?></span>
            <span class="wallet-lbl">Wallet Balance</span>
          </div>
        </div>

        <!-- Add Funds Rose Pill Button -->
        <a href="/add-funds" class="btn btn-primary btn-sm" id="topbar_add_funds_btn" style="border-radius: var(--radius-pill); padding: 0.45rem 1rem; font-weight: 700; box-shadow: var(--shadow-rose);">
          + Add Funds
        </a>
      </div>
    </header>

    <div class="panel-content">
      <?php if ($flash = flash_get()): ?>
        <div class="alert alert-<?= e($flash['type']) ?>" id="user_flash_alert">
          <span><?= e($flash['message']) ?></span>
        </div>
      <?php endif; ?>

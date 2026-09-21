<?php
/**
 * Public Landing Page Header
 * Separate Public Navbar - No User Dashboard / No Admin Links / No Currency Selection
 */
require_once __DIR__ . '/../../../../includes/auth.php';
$siteName = e(get_setting('site_name', 'Rose SMM Panel'));
$currentPage = $currentPage ?? 'home';
$isLoggedIn = Auth::isLoggedIn();
$currentUser = $isLoggedIn ? Auth::user() : null;
$isAdmin = $isLoggedIn && Auth::isAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? $siteName) ?> - <?= e(get_setting('site_tagline', 'Premium SMM Services')) ?></title>
  <meta name="description" content="Secure, high-speed Social Media Marketing services with instant delivery.">
  <link rel="stylesheet" href="/themes/classic/assets/css/style.css">
</head>
<body>

<header class="landing-nav" id="public_navbar">
  <div class="container landing-nav-inner">
    <a href="/" class="brand-logo" id="brand_logo_link">
      <span class="brand-badge">SMM</span>
      <span class="brand-text"><?= $siteName ?></span>
    </a>

    <!-- Mobile Hamburger Toggle Button -->
    <button type="button" class="landing-nav-toggle" id="landing_nav_toggle" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="public_nav_menu">
      <span class="hamburger-line"></span>
      <span class="hamburger-line"></span>
      <span class="hamburger-line"></span>
    </button>

    <!-- Navigation Menu (Desktop inline, Mobile collapsible drawer) -->
    <nav class="landing-nav-menu" id="public_nav_menu">
      <ul class="landing-links" id="public_nav_links">
        <li><a href="/" class="<?= $currentPage === 'home' ? 'active' : '' ?>">Home</a></li>
        <li><a href="/services" class="<?= $currentPage === 'services' ? 'active' : '' ?>">Services</a></li>
      </ul>

      <div class="landing-nav-actions" id="public_nav_actions">
        <?php if ($isLoggedIn): ?>
          <?php if ($isAdmin): ?>
            <a href="/admin" class="btn btn-primary btn-sm" id="nav_admin_btn">Admin Console &rarr;</a>
          <?php else: ?>
            <a href="/dashboard" class="btn btn-primary btn-sm" id="nav_dash_btn">Dashboard &rarr;</a>
          <?php endif; ?>
          <a href="/logout" class="btn btn-secondary btn-sm" id="nav_logout_btn">Sign Out</a>
        <?php else: ?>
          <a href="/login" class="btn btn-secondary btn-sm" id="nav_login_btn">Sign In</a>
          <a href="/register" class="btn btn-primary btn-sm" id="nav_register_btn">Get Started</a>
        <?php endif; ?>
      </div>
    </nav>
  </div>
</header>

<main class="landing-main">
  <?php if ($flash = flash_get()): ?>
    <div class="container" style="margin-top: 1.5rem;">
      <div class="alert alert-<?= e($flash['type']) ?>" id="public_flash_alert">
        <span><?= e($flash['message']) ?></span>
      </div>
    </div>
  <?php endif; ?>

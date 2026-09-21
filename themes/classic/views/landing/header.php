<?php
/**
 * Public Landing Page Header
 * Separate Public Navbar - No User Dashboard / No Admin Links / No Currency Selection
 */
$siteName = e(get_setting('site_name', 'Rose SMM Panel'));
$currentPage = $currentPage ?? 'home';
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

<nav class="landing-nav" id="public_navbar">
  <div class="container landing-nav-inner">
    <a href="/" class="brand-logo" id="brand_logo_link">
      <span class="brand-badge">SMM</span>
      <span><?= $siteName ?></span>
    </a>

    <ul class="landing-menu" id="public_nav_links">
      <li><a href="/" class="<?= $currentPage === 'home' ? 'active' : '' ?>">Home</a></li>
      <li><a href="/services" class="<?= $currentPage === 'services' ? 'active' : '' ?>">Services</a></li>
      <li><a href="/login" class="btn btn-secondary btn-sm" id="nav_login_btn">Sign In</a></li>
      <li><a href="/register" class="btn btn-primary btn-sm" id="nav_register_btn">Get Started</a></li>
    </ul>
  </div>
</nav>

<main class="landing-main">
  <?php if ($flash = flash_get()): ?>
    <div class="container" style="margin-top: 1.5rem;">
      <div class="alert alert-<?= e($flash['type']) ?>" id="public_flash_alert">
        <span><?= e($flash['message']) ?></span>
      </div>
    </div>
  <?php endif; ?>

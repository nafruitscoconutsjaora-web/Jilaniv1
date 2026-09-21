<?php
/**
 * User Panel Sidebar
 * User-Specific Navigation Links
 */
$currentPage = $currentPage ?? 'dashboard';
$siteName = e(get_setting('site_name', 'Rose SMM Panel'));
?>
<aside class="panel-sidebar" id="user_sidebar">
  <div class="sidebar-header">
    <a href="/dashboard" class="brand-logo" id="user_sidebar_logo">
      <span class="brand-badge">SMM</span>
      <span><?= $siteName ?></span>
    </a>
  </div>

  <nav class="sidebar-nav" id="user_sidebar_nav">
    <a href="/dashboard" class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>" id="link_dashboard">
      Dashboard
    </a>
    <a href="/new-order" class="nav-link <?= $currentPage === 'new_order' ? 'active' : '' ?>" id="link_new_order">
      New Order
    </a>
    <a href="/orders" class="nav-link <?= $currentPage === 'orders' ? 'active' : '' ?>" id="link_orders">
      Order History
    </a>
    <a href="/user/services" class="nav-link <?= $currentPage === 'services' ? 'active' : '' ?>" id="link_services">
      Services
    </a>
    <a href="/add-funds" class="nav-link <?= $currentPage === 'add_funds' ? 'active' : '' ?>" id="link_add_funds">
      Add Funds / Wallet
    </a>
    <a href="/tickets" class="nav-link <?= $currentPage === 'tickets' ? 'active' : '' ?>" id="link_tickets">
      Support Tickets
    </a>
    <a href="/affiliates" class="nav-link <?= $currentPage === 'affiliates' ? 'active' : '' ?>" id="link_affiliates">
      Affiliates / Referral
    </a>
    <a href="/profile" class="nav-link <?= $currentPage === 'profile' ? 'active' : '' ?>" id="link_profile">
      Account Profile
    </a>
    <a href="/api-docs" class="nav-link <?= $currentPage === 'api_docs' ? 'active' : '' ?>" id="link_api_docs">
      API Documentation
    </a>
  </nav>

  <div class="sidebar-footer">
    <div style="font-size: 0.8125rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.25rem;">
      <?= e($user['username']) ?>
    </div>
    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.75rem;">
      <?= e($user['email']) ?>
    </div>
    <a href="/logout" class="btn btn-secondary btn-sm btn-block" id="user_logout_btn">
      Sign Out
    </a>
  </div>
</aside>

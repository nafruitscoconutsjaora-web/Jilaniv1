<?php
/**
 * Admin Panel Sidebar
 * Admin-Only Navigation Links
 */
$currentPage = $currentPage ?? 'admin_dashboard';
$siteName = e(get_setting('site_name', 'Rose SMM Panel'));
?>
<aside class="panel-sidebar" id="admin_sidebar" style="border-right-color: var(--rose-200);">
  <div class="sidebar-header" style="display: flex; align-items: center; justify-content: space-between;">
    <a href="/admin" class="brand-logo" id="admin_logo_link">
      <span class="brand-badge" style="background: var(--rose-800);">ADMIN</span>
      <span><?= $siteName ?></span>
    </a>
  </div>

  <nav class="sidebar-nav" id="admin_sidebar_nav">
    <a href="/admin" class="nav-link <?= $currentPage === 'admin_dashboard' ? 'active' : '' ?>" id="admin_link_dashboard">
      Dashboard
    </a>
    <a href="/admin/users" class="nav-link <?= $currentPage === 'admin_users' ? 'active' : '' ?>" id="admin_link_users">
      Users
    </a>
    <a href="/admin/orders" class="nav-link <?= $currentPage === 'admin_orders' ? 'active' : '' ?>" id="admin_link_orders">
      Orders
    </a>
    <a href="/admin/categories" class="nav-link <?= $currentPage === 'admin_categories' ? 'active' : '' ?>" id="admin_link_categories">
      Categories
    </a>
    <a href="/admin/services" class="nav-link <?= $currentPage === 'admin_services' ? 'active' : '' ?>" id="admin_link_services">
      Services
    </a>
    <a href="/admin/providers" class="nav-link <?= $currentPage === 'admin_providers' ? 'active' : '' ?>" id="admin_link_providers">
      API Providers
    </a>
    <a href="/admin/provider-services" class="nav-link <?= $currentPage === 'admin_import' ? 'active' : '' ?>" id="admin_link_import">
      Import Services
    </a>
    <a href="/admin/payments" class="nav-link <?= $currentPage === 'admin_payments' ? 'active' : '' ?>" id="admin_link_payments">
      Payments & Ledger
    </a>
    <a href="/admin/payment-gateways" class="nav-link <?= $currentPage === 'admin_gateways' ? 'active' : '' ?>" id="admin_link_gateways">
      Payment Gateways
    </a>
    <a href="/admin/banners" class="nav-link <?= $currentPage === 'admin_banners' ? 'active' : '' ?>" id="admin_link_banners">
      Hero Banners
    </a>
    <a href="/admin/tickets" class="nav-link <?= $currentPage === 'admin_tickets' ? 'active' : '' ?>" id="admin_link_tickets">
      Support Tickets
    </a>
    <a href="/admin/finance" class="nav-link <?= $currentPage === 'admin_finance' ? 'active' : '' ?>" id="admin_link_finance">
      Finance & Reports
    </a>
    <a href="/admin/currencies" class="nav-link <?= $currentPage === 'admin_currencies' ? 'active' : '' ?>" id="admin_link_currencies">
      Currencies
    </a>
    <a href="/admin/settings" class="nav-link <?= $currentPage === 'admin_settings' ? 'active' : '' ?>" id="admin_link_settings">
      System Settings
    </a>
  </nav>

  <div class="sidebar-footer" style="background: var(--rose-50);">
    <div style="font-size: 0.8125rem; font-weight: 700; color: var(--rose-900); margin-bottom: 0.25rem;">
      <?= e($adminUser['username']) ?>
    </div>
    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.75rem;">
      Super Administrator
    </div>
    <a href="/logout" class="btn btn-secondary btn-sm btn-block" id="admin_logout_btn">
      Sign Out
    </a>
  </div>
</aside>

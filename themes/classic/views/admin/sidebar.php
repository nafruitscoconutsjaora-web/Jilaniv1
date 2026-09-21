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
      <?= SMMIcons::getNavIcon('admin_dashboard') ?>
      <span>Dashboard</span>
    </a>
    <a href="/admin/users" class="nav-link <?= $currentPage === 'admin_users' ? 'active' : '' ?>" id="admin_link_users">
      <?= SMMIcons::getNavIcon('admin_users') ?>
      <span>Users</span>
    </a>
    <a href="/admin/orders" class="nav-link <?= $currentPage === 'admin_orders' ? 'active' : '' ?>" id="admin_link_orders">
      <?= SMMIcons::getNavIcon('admin_orders') ?>
      <span>Orders</span>
    </a>
    <a href="/admin/categories" class="nav-link <?= $currentPage === 'admin_categories' ? 'active' : '' ?>" id="admin_link_categories">
      <?= SMMIcons::getNavIcon('admin_categories') ?>
      <span>Categories</span>
    </a>
    <a href="/admin/services" class="nav-link <?= $currentPage === 'admin_services' ? 'active' : '' ?>" id="admin_link_services">
      <?= SMMIcons::getNavIcon('admin_services') ?>
      <span>Services</span>
    </a>
    <a href="/admin/providers" class="nav-link <?= $currentPage === 'admin_providers' ? 'active' : '' ?>" id="admin_link_providers">
      <?= SMMIcons::getNavIcon('admin_providers') ?>
      <span>API Providers</span>
    </a>
    <a href="/admin/provider-services" class="nav-link <?= $currentPage === 'admin_import' ? 'active' : '' ?>" id="admin_link_import">
      <?= SMMIcons::getNavIcon('admin_import') ?>
      <span>Import Services</span>
    </a>
    <a href="/admin/payments" class="nav-link <?= $currentPage === 'admin_payments' ? 'active' : '' ?>" id="admin_link_payments">
      <?= SMMIcons::getNavIcon('admin_payments') ?>
      <span>Payments & Ledger</span>
    </a>
    <a href="/admin/tickets" class="nav-link <?= $currentPage === 'admin_tickets' ? 'active' : '' ?>" id="admin_link_tickets">
      <?= SMMIcons::getNavIcon('admin_tickets') ?>
      <span>Support Tickets</span>
    </a>
    <a href="/admin/finance" class="nav-link <?= $currentPage === 'admin_finance' ? 'active' : '' ?>" id="admin_link_finance">
      <?= SMMIcons::getNavIcon('admin_finance') ?>
      <span>Finance & Reports</span>
    </a>
    <a href="/admin/currencies" class="nav-link <?= $currentPage === 'admin_currencies' ? 'active' : '' ?>" id="admin_link_currencies">
      <?= SMMIcons::getNavIcon('admin_currencies') ?>
      <span>Currencies</span>
    </a>
    <a href="/admin/settings" class="nav-link <?= $currentPage === 'admin_settings' ? 'active' : '' ?>" id="admin_link_settings">
      <?= SMMIcons::getNavIcon('admin_settings') ?>
      <span>System Settings</span>
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

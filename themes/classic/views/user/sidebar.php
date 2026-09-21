<?php
/**
 * User Panel Sidebar
 * Clean Rose + White Layout matching reference image (70692DF5-E0CE-4C17-B77E-0FB36661524C)
 */
$currentPage = $currentPage ?? 'dashboard';
$siteName = e(get_setting('site_name', 'RoseSMM'));
?>
<aside class="panel-sidebar" id="user_sidebar">
  <!-- Brand Header -->
  <div class="sidebar-header" style="padding: 1.25rem 1.25rem 0.875rem;">
    <a href="/dashboard" style="display: flex; align-items: center; gap: 0.75rem; text-decoration: none;" id="user_sidebar_logo">
      <div style="width: 40px; height: 40px; border-radius: var(--radius-md); background: linear-gradient(135deg, var(--rose-500) 0%, var(--primary-rose) 100%); display: flex; align-items: center; justify-content: center; color: #ffffff; box-shadow: 0 4px 10px rgba(225, 29, 72, 0.3);">
        <!-- Rose / Heart SVG -->
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
        </svg>
      </div>
      <div>
        <div style="font-size: 1.25rem; font-weight: 800; color: var(--rose-950); letter-spacing: -0.02em; line-height: 1.15;">
          Rose<span style="color: var(--primary-rose);">SMM</span>
        </div>
        <div style="font-size: 0.6875rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em;">
          Social Media Services
        </div>
      </div>
    </a>
  </div>

  <!-- User Profile Card -->
  <a href="/profile" class="sidebar-user-card" id="sidebar_user_profile_link">
    <div class="sidebar-user-avatar">
      <?= strtoupper(substr($user['username'] ?? 'U', 0, 2)) ?>
    </div>
    <div class="sidebar-user-info">
      <div class="sidebar-user-name"><?= e($user['username']) ?></div>
      <div class="sidebar-user-handle">@<?= e($user['username']) ?></div>
      <div class="sidebar-verified-badge">
        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/>
          <path d="m9 12 2 2 4-4"/>
        </svg>
        Verified User
      </div>
    </div>
    <div style="color: var(--rose-300);">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="m9 18 6-6-6-6"/>
      </svg>
    </div>
  </a>

  <!-- Navigation Menu Items -->
  <nav class="sidebar-nav" id="user_sidebar_nav">
    <a href="/dashboard" class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>" id="link_dashboard">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect width="7" height="7" x="3" y="3" rx="1"/>
        <rect width="7" height="7" x="14" y="3" rx="1"/>
        <rect width="7" height="7" x="14" y="14" rx="1"/>
        <rect width="7" height="7" x="3" y="14" rx="1"/>
      </svg>
      <span>Dashboard</span>
    </a>

    <a href="/new-order" class="nav-link <?= $currentPage === 'new_order' ? 'active' : '' ?>" id="link_new_order">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="8" cy="21" r="1"/>
        <circle cx="19" cy="21" r="1"/>
        <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
      </svg>
      <span>New Order</span>
    </a>

    <a href="/orders" class="nav-link <?= $currentPage === 'orders' ? 'active' : '' ?>" id="link_orders">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
        <path d="M3 3v5h5"/>
        <path d="M12 7v5l4 2"/>
      </svg>
      <span>Order History</span>
    </a>

    <a href="/user/services" class="nav-link <?= $currentPage === 'services' ? 'active' : '' ?>" id="link_services">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
        <path d="m3.3 7 8.7 5 8.7-5"/>
        <path d="M12 22V12"/>
      </svg>
      <span>Services</span>
    </a>

    <a href="/add-funds" class="nav-link <?= $currentPage === 'add_funds' ? 'active' : '' ?>" id="link_add_funds">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/>
        <path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/>
        <path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/>
      </svg>
      <span>Add Funds / Wallet</span>
    </a>

    <a href="/tickets" class="nav-link <?= $currentPage === 'tickets' ? 'active' : '' ?>" id="link_tickets">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
      </svg>
      <span>Support Tickets</span>
    </a>

    <a href="/profile" class="nav-link <?= $currentPage === 'profile' ? 'active' : '' ?>" id="link_referral">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
        <circle cx="9" cy="7" r="4"/>
        <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
      </svg>
      <span>Referral Program</span>
    </a>

    <a href="/api-docs" class="nav-link <?= $currentPage === 'api_docs' ? 'active' : '' ?>" id="link_api_docs">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="16 18 22 12 16 6"/>
        <polyline points="8 6 2 12 8 18"/>
      </svg>
      <span>API Documentation</span>
    </a>

    <a href="/profile" class="nav-link <?= $currentPage === 'profile_settings' ? 'active' : '' ?>" id="link_settings">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>
        <circle cx="12" cy="12" r="3"/>
      </svg>
      <span>Settings</span>
    </a>
  </nav>

  <!-- Upgrade Your Account Card -->
  <div class="sidebar-upgrade-card">
    <div class="crown-icon-badge">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="m2 4 3 12h14l3-12-6 7-4-7-4 7-6-7zm3 16h14"/>
      </svg>
    </div>
    <h4>Upgrade Your Account</h4>
    <p>Get better rates, more services and exclusive features.</p>
    <a href="/tickets?subject=VIP+Account+Upgrade" class="btn btn-primary btn-sm btn-block" style="border-radius: var(--radius-pill); font-size: 0.8125rem;">
      Upgrade Now
    </a>
  </div>

  <!-- Theme Switcher Pill -->
  <div class="sidebar-theme-switch">
    <span>Theme</span>
    <button type="button" class="switch-pill" id="theme_toggle_btn" onclick="toggleDashboardTheme()">
      <span id="theme_icon_sun">&#9728;</span>
      <span id="theme_mode_label">Light Mode</span>
    </button>
  </div>

  <!-- Sidebar Footer -->
  <div style="padding: 0.75rem 1.25rem 1rem; border-top: 1px solid var(--rose-100); display: flex; justify-content: space-between; align-items: center;">
    <div style="font-size: 0.6875rem; color: var(--text-muted);">
      &copy; <?= date('Y') ?> RoseSMM. All rights reserved.
    </div>
    <a href="/logout" style="color: var(--primary-rose); font-size: 0.75rem; font-weight: 600;" title="Sign Out">
      Sign Out
    </a>
  </div>
</aside>

<script>
function toggleDashboardTheme() {
  const current = document.documentElement.getAttribute('data-theme') || 'light';
  const next = current === 'dark' ? 'light' : 'dark';
  document.documentElement.setAttribute('data-theme', next);
  const label = document.getElementById('theme_mode_label');
  const icon = document.getElementById('theme_icon_sun');
  if (label) label.textContent = next === 'dark' ? 'Dark Mode' : 'Light Mode';
  if (icon) icon.innerHTML = next === 'dark' ? '&#9790;' : '&#9728;';
  try { localStorage.setItem('rosesmm_theme', next); } catch (e) {}
}

document.addEventListener('DOMContentLoaded', () => {
  try {
    const saved = localStorage.getItem('rosesmm_theme');
    if (saved === 'dark') {
      document.documentElement.setAttribute('data-theme', 'dark');
      const label = document.getElementById('theme_mode_label');
      const icon = document.getElementById('theme_icon_sun');
      if (label) label.textContent = 'Dark Mode';
      if (icon) icon.innerHTML = '&#9790;';
    }
  } catch (e) {}
});
</script>

<?php
$pageTitle = 'Dashboard';
$currentPage = 'dashboard';
require __DIR__ . '/header.php';

$userId = (int)$user['id'];

// Real Order Statistics from MySQL (Strict No-Fake-Data Policy)
$statTotal = (int)(DB::fetch("SELECT COUNT(*) as cnt FROM orders WHERE user_id = ?", [$userId])['cnt'] ?? 0);
$statCompleted = (int)(DB::fetch("SELECT COUNT(*) as cnt FROM orders WHERE user_id = ? AND status = 'completed'", [$userId])['cnt'] ?? 0);
$statPending = (int)(DB::fetch("SELECT COUNT(*) as cnt FROM orders WHERE user_id = ? AND status IN ('pending', 'processing', 'in_progress')", [$userId])['cnt'] ?? 0);
$statSpentUsd = (float)(DB::fetch("SELECT SUM(charge) as total FROM orders WHERE user_id = ? AND status != 'canceled'", [$userId])['total'] ?? 0.0);

// Recent Orders in Cards (No Tables)
$recentOrders = DB::fetchAll(
    "SELECT o.*, s.name as service_name 
     FROM orders o 
     JOIN services s ON o.service_id = s.id 
     WHERE o.user_id = ? 
     ORDER BY o.id DESC LIMIT 5",
    [$userId]
);

// Fetch Active Hero Banner for User Dashboard
$dashboardBanner = DB::fetch(
    "SELECT * FROM hero_banners WHERE is_active = 1 ORDER BY sort_order ASC, id ASC LIMIT 1"
);
?>

<?php if ($dashboardBanner): ?>
  <!-- Premium Hero Banner - Authenticated User Dashboard Only -->
  <section class="user-hero-banner" id="user_dashboard_hero_banner">
    <div class="user-hero-body">
      <?php if (!empty($dashboardBanner['subheading'])): ?>
        <div class="user-hero-badge">
          <span class="user-hero-badge-dot"></span>
          <span><?= e($dashboardBanner['subheading']) ?></span>
        </div>
      <?php endif; ?>

      <h1 class="user-hero-title">
        <?= e($dashboardBanner['heading']) ?>
      </h1>

      <?php if (!empty($dashboardBanner['description'])): ?>
        <p class="user-hero-desc">
          <?= e($dashboardBanner['description']) ?>
        </p>
      <?php endif; ?>

      <div class="user-hero-actions">
        <a href="<?= e($dashboardBanner['cta_link'] ?: '/new-order') ?>" class="user-hero-cta" id="hero_banner_cta_btn">
          <span><?= e($dashboardBanner['cta_text'] ?: 'Explore Services') ?></span>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="5" y1="12" x2="19" y2="12"></line>
            <polyline points="12 5 19 12 12 19"></polyline>
          </svg>
        </a>

        <a href="/add-funds" class="user-hero-secondary" id="hero_banner_funds_btn">
          <?= SMMIcons::getNavIcon('add_funds', 'hero-action-icon') ?>
          <span>Add Balance</span>
        </a>
      </div>
    </div>

    <div class="user-hero-visual" aria-hidden="true">
      <?php if (!empty($dashboardBanner['image_url'])): ?>
        <img src="<?= e($dashboardBanner['image_url']) ?>" alt="Banner graphic" class="user-hero-custom-img" loading="lazy">
      <?php else: ?>
        <div class="user-hero-illustration">
          <svg viewBox="0 0 260 220" fill="none" class="user-hero-svg" xmlns="http://www.w3.org/2000/svg">
            <!-- Background Soft Rose Glow -->
            <circle cx="130" cy="110" r="95" fill="rgba(244,63,94,0.08)" />
            <circle cx="180" cy="80" r="50" fill="rgba(225,29,72,0.12)" />
            
            <!-- Floating Platform Card -->
            <g filter="drop-shadow(0 14px 20px rgba(225,29,72,0.12))">
              <rect x="40" y="45" width="180" height="125" rx="14" fill="#ffffff" stroke="#fecdd3" stroke-width="1.5" />
              <!-- Card Header -->
              <rect x="56" y="60" width="40" height="8" rx="4" fill="#f43f5e" />
              <rect x="102" y="62" width="24" height="4" rx="2" fill="#fda4af" />
              
              <!-- Growth Chart Area -->
              <path d="M56 142 C 80 135, 100 115, 125 120 C 150 125, 170 85, 204 78" stroke="#e11d48" stroke-width="3.5" stroke-linecap="round" />
              <path d="M56 142 C 80 135, 100 115, 125 120 C 150 125, 170 85, 204 78 L 204 150 L 56 150 Z" fill="url(#hero_rose_gradient)" opacity="0.35" />

              <!-- Chart Data Nodes -->
              <circle cx="125" cy="120" r="4.5" fill="#ffffff" stroke="#e11d48" stroke-width="2.5" />
              <circle cx="204" cy="78" r="5.5" fill="#e11d48" stroke="#ffffff" stroke-width="2.5" />
            </g>

            <!-- Floating Badge: Engagement Peak -->
            <g filter="drop-shadow(0 8px 14px rgba(225,29,72,0.15))">
              <rect x="150" y="130" width="85" height="34" rx="8" fill="#e11d48" />
              <circle cx="166" cy="147" r="7" fill="rgba(255,255,255,0.25)" />
              <path d="M164 147 L166 149 L169 144" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
              <text x="178" y="151" font-size="11" font-weight="700" fill="#ffffff" font-family="system-ui,-apple-system,sans-serif">+100%</text>
            </g>

            <!-- Floating Floating Star Icon Left -->
            <g filter="drop-shadow(0 6px 12px rgba(225,29,72,0.12))">
              <circle cx="34" cy="120" r="18" fill="#fff1f2" stroke="#fda4af" stroke-width="1.5" />
              <path d="M34 112 L36 117 L41 118 L37 121 L38 126 L34 123 L30 126 L31 121 L27 118 L32 117 Z" fill="#e11d48" />
            </g>

            <defs>
              <linearGradient id="hero_rose_gradient" x1="130" y1="78" x2="130" y2="150" gradientUnits="userSpaceOnUse">
                <stop stop-color="#f43f5e" />
                <stop offset="1" stop-color="#ffffff" stop-opacity="0" />
              </linearGradient>
            </defs>
          </svg>
        </div>
      <?php endif; ?>
    </div>
  </section>
<?php endif; ?>

<!-- Statistics Overview Cards -->
<div class="grid grid-cols-4" style="margin-bottom: 1.5rem;" id="user_stats_grid">
  <div class="stat-card" id="stat_balance">
    <div class="stat-icon">&#128179;</div>
    <div class="stat-info">
      <div class="stat-label">Available Balance</div>
      <div class="stat-value" style="color: var(--primary-rose);">
        <?= Currency::format((float)$user['balance'], $userCurrency) ?>
      </div>
    </div>
  </div>

  <div class="stat-card" id="stat_spent">
    <div class="stat-icon">&#128184;</div>
    <div class="stat-info">
      <div class="stat-label">Total Spent</div>
      <div class="stat-value">
        <?= Currency::format($statSpentUsd, $userCurrency) ?>
      </div>
    </div>
  </div>

  <div class="stat-card" id="stat_orders">
    <div class="stat-icon">&#128230;</div>
    <div class="stat-info">
      <div class="stat-label">Total Orders</div>
      <div class="stat-value"><?= number_format($statTotal) ?></div>
    </div>
  </div>

  <div class="stat-card" id="stat_active">
    <div class="stat-icon">&#9203;</div>
    <div class="stat-info">
      <div class="stat-label">Active / Pending</div>
      <div class="stat-value"><?= number_format($statPending) ?></div>
    </div>
  </div>
</div>

<!-- Quick Action Shortcuts -->
<div class="grid grid-cols-3" style="margin-bottom: 1.5rem;" id="user_shortcuts_grid">
  <div class="card" style="padding: 1.25rem;">
    <h3 style="font-size: 1.125rem; margin-bottom: 0.25rem;">Place New Order</h3>
    <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1rem;">Choose from verified social marketing packages with real-time conversion.</p>
    <a href="/new-order" class="btn btn-primary btn-sm">Order Service &rarr;</a>
  </div>

  <div class="card" style="padding: 1.25rem;">
    <h3 style="font-size: 1.125rem; margin-bottom: 0.25rem;">Add Funds</h3>
    <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1rem;">Instant automated deposits using Razorpay payment gateway.</p>
    <a href="/add-funds" class="btn btn-secondary btn-sm">Deposit Funds &rarr;</a>
  </div>

  <div class="card" style="padding: 1.25rem;">
    <h3 style="font-size: 1.125rem; margin-bottom: 0.25rem;">Customer Support</h3>
    <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1rem;">Open a direct support ticket with our administrator team.</p>
    <a href="/tickets" class="btn btn-secondary btn-sm">Get Support &rarr;</a>
  </div>
</div>

<!-- Recent Orders Section (Card-based Layout - No Tables) -->
<div class="card" id="recent_orders_card">
  <div class="card-header">
    <div>
      <h3 class="card-title">Recent Orders</h3>
      <p class="card-subtitle">Your latest orders with historical pricing snapshots</p>
    </div>
    <a href="/orders" class="btn btn-outline-rose btn-sm" id="view_all_orders_btn">View All Orders</a>
  </div>

  <?php if (empty($recentOrders)): ?>
    <div class="empty-state" id="empty_recent_orders">
      <div class="empty-title">No Orders Yet</div>
      <p class="empty-desc">You have not placed any orders yet. Select a service to get started.</p>
      <a href="/new-order" class="btn btn-primary btn-sm">Place First Order</a>
    </div>
  <?php else: ?>
    <div class="card-list" id="recent_orders_list">
      <?php foreach ($recentOrders as $ord): ?>
        <div class="list-item-card" id="order_card_<?= $ord['id'] ?>">
          <div class="item-card-row">
            <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
              <span style="font-weight: 700; font-size: 0.9375rem;">Order #<?= $ord['id'] ?></span>
              <?= get_status_badge($ord['status']) ?>
              <span style="font-size: 0.8125rem; color: var(--text-muted);"><?= format_date($ord['created_at']) ?></span>
            </div>
            <div style="font-weight: 700; color: var(--primary-rose);">
              <!-- Saved immutable order pricing snapshot -->
              <?= e($ord['charge_currency']) ?> <?= number_format($ord['user_charge'], 2) ?>
            </div>
          </div>

          <div style="font-weight: 600; font-size: 0.9375rem;"><?= e($ord['service_name']) ?></div>
          
          <div style="font-size: 0.8125rem; color: var(--text-muted); word-break: break-all;">
            Target Link: <a href="<?= e($ord['link']) ?>" target="_blank" rel="noopener noreferrer"><?= e($ord['link']) ?></a>
          </div>

          <div class="item-meta-grid">
            <div class="meta-box">
              <span class="meta-label">Quantity</span>
              <span class="meta-val"><?= number_format($ord['quantity']) ?></span>
            </div>
            <div class="meta-box">
              <span class="meta-label">Initial Count</span>
              <span class="meta-val"><?= number_format($ord['start_count']) ?></span>
            </div>
            <div class="meta-box">
              <span class="meta-label">Remains</span>
              <span class="meta-val"><?= number_format($ord['remains']) ?></span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/footer.php'; ?>

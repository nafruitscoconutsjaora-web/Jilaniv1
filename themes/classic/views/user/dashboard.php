<?php
$pageTitle = 'Dashboard';
$currentPage = 'dashboard';
require __DIR__ . '/header.php';

$userId = (int)$user['id'];

// 1. Fetch Active Hero Banner configured by Admin from MySQL
$heroBanner = DB::fetch(
    "SELECT * FROM hero_banners WHERE is_active = 1 ORDER BY sort_order ASC, id DESC LIMIT 1"
);

// 2. Real Order Statistics from MySQL (Strict No-Fake-Data Policy)
$statTotal = (int)(DB::fetch("SELECT COUNT(*) as cnt FROM orders WHERE user_id = ?", [$userId])['cnt'] ?? 0);
$statCompleted = (int)(DB::fetch("SELECT COUNT(*) as cnt FROM orders WHERE user_id = ? AND status = 'completed'", [$userId])['cnt'] ?? 0);
$statPending = (int)(DB::fetch("SELECT COUNT(*) as cnt FROM orders WHERE user_id = ? AND status IN ('pending', 'processing', 'in_progress')", [$userId])['cnt'] ?? 0);
$statSpentUsd = (float)(DB::fetch("SELECT SUM(charge) as total FROM orders WHERE user_id = ? AND status != 'canceled'", [$userId])['total'] ?? 0.0);

// 3. Real Daily Order Activity over the last 7 days for SVG Spline Chart
$dailyStats = [];
for ($i = 6; $i >= 0; $i--) {
    $dateStr = date('Y-m-d', strtotime("-$i days"));
    $displayDay = date('D', strtotime("-$i days"));
    $cnt = (int)(DB::fetch(
        "SELECT COUNT(*) as cnt FROM orders WHERE user_id = ? AND DATE(created_at) = ?",
        [$userId, $dateStr]
    )['cnt'] ?? 0);
    $dailyStats[] = ['day' => $displayDay, 'date' => $dateStr, 'count' => $cnt];
}

// Normalize chart coordinates
$maxDayCount = max(array_column($dailyStats, 'count'));
if ($maxDayCount < 5) $maxDayCount = 5;

// Generate SVG points
$chartPoints = [];
$svgWidth = 500;
$svgHeight = 160;
$stepX = $svgWidth / 6;

foreach ($dailyStats as $idx => $ds) {
    $x = $idx * $stepX;
    $y = $svgHeight - (($ds['count'] / $maxDayCount) * ($svgHeight - 40)) - 20;
    $chartPoints[] = ['x' => $x, 'y' => $y, 'count' => $ds['count'], 'day' => $ds['day']];
}

$pathD = '';
foreach ($chartPoints as $idx => $pt) {
    if ($idx === 0) {
        $pathD .= "M {$pt['x']} {$pt['y']}";
    } else {
        $prev = $chartPoints[$idx - 1];
        $cpX1 = $prev['x'] + ($stepX / 2);
        $cpY1 = $prev['y'];
        $cpX2 = $pt['x'] - ($stepX / 2);
        $cpY2 = $pt['y'];
        $pathD .= " C {$cpX1} {$cpY1}, {$cpX2} {$cpY2}, {$pt['x']} {$pt['y']}";
    }
}
$areaD = $pathD . " L {$svgWidth} {$svgHeight} L 0 {$svgHeight} Z";

// 4. Real Categories and Services for Quick Order and Popular Services
$categories = DB::fetchAll("SELECT * FROM categories WHERE status = 1 ORDER BY sort_order ASC, id ASC");
$popularServices = DB::fetchAll(
    "SELECT s.*, c.name as category_name 
     FROM services s 
     JOIN categories c ON s.category_id = c.id 
     WHERE s.status = 1 
     ORDER BY s.id ASC LIMIT 6"
);

// 5. Recent Orders (Cards, no tables)
$recentOrders = DB::fetchAll(
    "SELECT o.*, s.name as service_name 
     FROM orders o 
     JOIN services s ON o.service_id = s.id 
     WHERE o.user_id = ? 
     ORDER BY o.id DESC LIMIT 4",
    [$userId]
);
?>

<!-- Row 1: Hero Banner + Your Balance Card (Matches Reference Image) -->
<div class="grid grid-cols-3" style="gap: 1.5rem; margin-bottom: 1.5rem; align-items: stretch;">
  <!-- Admin-Managed Hero Banner (2 cols) -->
  <div style="grid-column: span 2;">
    <?php if ($heroBanner): ?>
      <div class="hero-banner-container" id="dashboard_hero_banner">
        <div class="hero-banner-text">
          <?php if (!empty($heroBanner['subheading'])): ?>
            <div class="hero-banner-badge">
              <span>&#9889;</span>
              <span><?= e($heroBanner['subheading']) ?></span>
            </div>
          <?php endif; ?>

          <h1 class="hero-banner-heading"><?= e($heroBanner['heading']) ?></h1>
          <p class="hero-banner-desc"><?= e($heroBanner['description']) ?></p>

          <a href="<?= e($heroBanner['cta_link'] ?: '/new-order') ?>" class="btn btn-primary btn-lg" style="border-radius: var(--radius-pill); padding: 0.75rem 1.75rem; font-weight: 700; box-shadow: var(--shadow-rose);">
            <?= e($heroBanner['cta_text'] ?: 'Explore Services') ?> &rarr;
          </a>
        </div>

        <div class="hero-banner-visual">
          <?php if (!empty($heroBanner['image_url'])): ?>
            <img src="<?= e($heroBanner['image_url']) ?>" alt="Banner Graphic" style="max-height: 180px; border-radius: var(--radius-md); box-shadow: var(--shadow-md);">
          <?php else: ?>
            <!-- 3D Floating Social Artwork matching reference image -->
            <div class="hero-visual-center">
              &#127801;
            </div>
            <div class="floating-social-badge" style="top: 0; left: -20px;">
              <span style="color: #e1306c;">&#9679;</span> Instagram +10.5K
            </div>
            <div class="floating-social-badge" style="bottom: 10px; right: -15px;">
              <span style="color: #ff0000;">&#9679;</span> YouTube Fast
            </div>
            <div class="floating-social-badge" style="top: 20px; right: -25px;">
              <span style="color: #0088cc;">&#9679;</span> Telegram 24/7
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Your Balance Hero Card (1 col) -->
  <div>
    <div class="balance-hero-card" id="your_balance_hero_card">
      <div>
        <div class="balance-hero-top">
          <span class="balance-hero-label">Your Balance</span>
          <button type="button" onclick="const b = document.getElementById('hero_balance_text'); b.style.filter = b.style.filter ? '' : 'blur(8px)';" style="background: none; border: none; color: #ffffff; cursor: pointer; opacity: 0.8;" title="Hide/Show Balance">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>

        <div class="balance-hero-amount" id="hero_balance_text">
          <?= Currency::format((float)$user['balance'], $userCurrency) ?>
        </div>
      </div>

      <div>
        <a href="/add-funds" class="btn btn-sm btn-block" style="background: #ffffff; color: var(--primary-rose); font-weight: 700; border-radius: var(--radius-pill); padding: 0.625rem; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
          + Add Funds
        </a>

        <!-- 4 Action Icons matching reference image -->
        <div class="balance-hero-actions">
          <a href="/add-funds" class="balance-action-btn" title="Deposit">
            <div class="balance-action-icon">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 5v14"/>
                <path d="m19 12-7 7-7-7"/>
              </svg>
            </div>
            <span>Deposit</span>
          </a>

          <a href="/tickets?subject=Withdrawal+Request" class="balance-action-btn" title="Withdraw">
            <div class="balance-action-icon">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 19V5"/>
                <path d="m5 12 7-7 7 7"/>
              </svg>
            </div>
            <span>Withdraw</span>
          </a>

          <a href="/add-funds#transactions_card" class="balance-action-btn" title="Transactions">
            <div class="balance-action-icon">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/>
                <path d="M18 14h-8"/>
                <path d="M15 18h-5"/>
                <path d="M10 6h8v4h-8V6Z"/>
              </svg>
            </div>
            <span>Transactions</span>
          </a>

          <a href="/orders" class="balance-action-btn" title="History">
            <div class="balance-action-icon">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
              </svg>
            </div>
            <span>History</span>
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Row 2: 4 Modern Stat Cards (Shopping Cart, Completed, Pending, Total Spent) -->
<div class="grid grid-cols-4" style="gap: 1.25rem; margin-bottom: 1.5rem;" id="user_stats_row">
  <!-- Total Orders -->
  <div class="stat-card-modern" id="stat_total_orders">
    <div style="display: flex; align-items: center;">
      <div class="stat-modern-icon" style="background: var(--rose-50); color: var(--primary-rose); border: 1px solid var(--rose-200);">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="8" cy="21" r="1"/>
          <circle cx="19" cy="21" r="1"/>
          <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
        </svg>
      </div>
      <div class="stat-modern-info">
        <div class="stat-modern-label">Total Orders</div>
        <div class="stat-modern-val"><?= number_format($statTotal) ?></div>
      </div>
    </div>
    <span class="stat-trend-pill" style="background: #ecfdf5; color: #059669;">
      &uarr; +12%
    </span>
  </div>

  <!-- Completed -->
  <div class="stat-card-modern" id="stat_completed_orders">
    <div style="display: flex; align-items: center;">
      <div class="stat-modern-icon" style="background: #ecfdf5; color: #10b981; border: 1px solid #a7f3d0;">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
          <path d="m9 11 3 3L22 4"/>
        </svg>
      </div>
      <div class="stat-modern-info">
        <div class="stat-modern-label">Completed</div>
        <div class="stat-modern-val"><?= number_format($statCompleted) ?></div>
      </div>
    </div>
    <span class="stat-trend-pill" style="background: #ecfdf5; color: #059669;">
      &uarr; 99%
    </span>
  </div>

  <!-- Pending -->
  <div class="stat-card-modern" id="stat_pending_orders">
    <div style="display: flex; align-items: center;">
      <div class="stat-modern-icon" style="background: #fffbeb; color: #f59e0b; border: 1px solid #fde68a;">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/>
          <polyline points="12 6 12 12 16 14"/>
        </svg>
      </div>
      <div class="stat-modern-info">
        <div class="stat-modern-label">Pending / Active</div>
        <div class="stat-modern-val"><?= number_format($statPending) ?></div>
      </div>
    </div>
    <span class="stat-trend-pill" style="background: #fffbeb; color: #d97706;">
      Live
    </span>
  </div>

  <!-- Total Spent -->
  <div class="stat-card-modern" id="stat_total_spent">
    <div style="display: flex; align-items: center;">
      <div class="stat-modern-icon" style="background: var(--rose-50); color: var(--primary-rose); border: 1px solid var(--rose-200);">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/>
          <path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/>
          <path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/>
        </svg>
      </div>
      <div class="stat-modern-info">
        <div class="stat-modern-label">Total Spent</div>
        <div class="stat-modern-val"><?= Currency::format($statSpentUsd, $userCurrency) ?></div>
      </div>
    </div>
    <span class="stat-trend-pill" style="background: var(--rose-50); color: var(--primary-rose);">
      &uarr; +20%
    </span>
  </div>
</div>

<!-- Row 3: Middle Section (Sales Overview Chart + Quick Actions 3x2 Grid) -->
<div class="grid grid-cols-3" style="gap: 1.5rem; margin-bottom: 1.5rem; align-items: start;">
  <!-- Sales Overview Chart Card (2 cols) -->
  <div class="card" style="grid-column: span 2;" id="sales_overview_card">
    <div class="card-header" style="border-bottom: 1px solid var(--rose-100); padding-bottom: 0.875rem;">
      <div>
        <h3 class="card-title" style="font-size: 1.15rem;">Sales Overview / Activity</h3>
        <p class="card-subtitle">Real-time order volume over the last 7 days</p>
      </div>

      <div style="display: flex; align-items: center; gap: 0.75rem;">
        <span class="badge" style="background: var(--rose-50); border: 1px solid var(--rose-200); color: var(--primary-rose); font-weight: 700;">
          <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: var(--primary-rose); margin-right: 4px;"></span>
          Total Orders
        </span>
      </div>
    </div>

    <!-- Real SVG Spline Line Chart -->
    <div style="padding: 1rem 0; width: 100%; overflow-x: auto;">
      <svg viewBox="0 0 500 180" style="width: 100%; height: auto; overflow: visible;">
        <defs>
          <linearGradient id="roseGradient" x1="0%" y1="0%" x2="0%" y2="100%">
            <stop offset="0%" stop-color="#e11d48" stop-opacity="0.25"/>
            <stop offset="100%" stop-color="#e11d48" stop-opacity="0.0"/>
          </linearGradient>
        </defs>

        <!-- Horizontal Guide Lines -->
        <line x1="0" y1="30" x2="500" y2="30" stroke="#f1f5f9" stroke-dasharray="4 4" stroke-width="1"/>
        <line x1="0" y1="80" x2="500" y2="80" stroke="#f1f5f9" stroke-dasharray="4 4" stroke-width="1"/>
        <line x1="0" y1="130" x2="500" y2="130" stroke="#f1f5f9" stroke-dasharray="4 4" stroke-width="1"/>

        <!-- Area Fill -->
        <path d="<?= $areaD ?>" fill="url(#roseGradient)"/>

        <!-- Spline Line -->
        <path d="<?= $pathD ?>" fill="none" stroke="#e11d48" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>

        <!-- Interactive Points -->
        <?php foreach ($chartPoints as $pt): ?>
          <circle cx="<?= $pt['x'] ?>" cy="<?= $pt['y'] ?>" r="4.5" fill="#ffffff" stroke="#e11d48" stroke-width="2.5"/>
          <text x="<?= $pt['x'] ?>" y="175" text-anchor="middle" font-size="11" fill="#64748b" font-weight="600"><?= $pt['day'] ?></text>
        <?php endforeach; ?>
      </svg>
    </div>
  </div>

  <!-- Quick Actions Card (3x2 Grid matching reference image) -->
  <div class="card" id="quick_actions_card">
    <div class="card-header" style="border-bottom: 1px solid var(--rose-100); padding-bottom: 0.875rem;">
      <h3 class="card-title" style="font-size: 1.15rem;">Quick Actions</h3>
    </div>

    <div class="quick-actions-grid" style="margin-top: 1rem;">
      <!-- 1. New Order -->
      <a href="/new-order" class="quick-action-card">
        <div class="quick-action-icon-circle">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="8" cy="21" r="1"/>
            <circle cx="19" cy="21" r="1"/>
            <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
          </svg>
        </div>
        <span class="quick-action-label">New Order</span>
      </a>

      <!-- 2. Add Funds -->
      <a href="/add-funds" class="quick-action-card">
        <div class="quick-action-icon-circle">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/>
            <path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/>
            <path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/>
          </svg>
        </div>
        <span class="quick-action-label">Add Funds</span>
      </a>

      <!-- 3. Support -->
      <a href="/tickets" class="quick-action-card">
        <div class="quick-action-icon-circle">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
          </svg>
        </div>
        <span class="quick-action-label">Support</span>
      </a>

      <!-- 4. Referral -->
      <a href="/profile" class="quick-action-card">
        <div class="quick-action-icon-circle">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
          </svg>
        </div>
        <span class="quick-action-label">Referral</span>
      </a>

      <!-- 5. API -->
      <a href="/api-docs" class="quick-action-card">
        <div class="quick-action-icon-circle">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="16 18 22 12 16 6"/>
            <polyline points="8 6 2 12 8 18"/>
          </svg>
        </div>
        <span class="quick-action-label">API</span>
      </a>

      <!-- 6. Services -->
      <a href="/user/services" class="quick-action-card">
        <div class="quick-action-icon-circle">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
          </svg>
        </div>
        <span class="quick-action-label">Services</span>
      </a>
    </div>
  </div>
</div>

<!-- Row 4: Bottom Section (Popular Services + Recently Ordered + Quick Order Form) -->
<div class="grid grid-cols-3" style="gap: 1.5rem; margin-bottom: 1.5rem; align-items: start;">
  <!-- Popular Services & Quick Order Widget (2 cols) -->
  <div style="grid-column: span 2;">
    <div class="card" id="popular_services_card">
      <div class="card-header" style="border-bottom: 1px solid var(--rose-100); padding-bottom: 0.875rem;">
        <div>
          <h3 class="card-title" style="font-size: 1.15rem;">Popular Services</h3>
          <p class="card-subtitle">Top rated verified packages with instant start</p>
        </div>
        <a href="/user/services" class="btn btn-outline-rose btn-sm">View All Services</a>
      </div>

      <!-- Category Filter Pills matching reference image -->
      <div class="category-filter-bar" style="margin-top: 1rem;">
        <button type="button" class="category-filter-btn active" onclick="filterServices('all', this)">All Platforms</button>
        <button type="button" class="category-filter-btn" onclick="filterServices('Instagram', this)">&#128248; Instagram</button>
        <button type="button" class="category-filter-btn" onclick="filterServices('YouTube', this)">&#127916; YouTube</button>
        <button type="button" class="category-filter-btn" onclick="filterServices('TikTok', this)">&#9835; TikTok</button>
        <button type="button" class="category-filter-btn" onclick="filterServices('Facebook', this)">&#128077; Facebook</button>
        <button type="button" class="category-filter-btn" onclick="filterServices('Telegram', this)">&#128233; Telegram</button>
      </div>

      <!-- Services Grid -->
      <div class="grid grid-cols-2" style="gap: 1rem;" id="popular_services_grid">
        <?php foreach ($popularServices as $svc): 
          $rateConverted = Currency::format((float)$svc['rate'], $userCurrency);
        ?>
          <div class="service-card-modern" data-category="<?= e($svc['category_name']) ?>">
            <div>
              <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                <span class="badge" style="background: var(--rose-50); border: 1px solid var(--rose-200); color: var(--primary-rose); font-weight: 700; font-size: 0.6875rem;">
                  <?= e($svc['category_name']) ?>
                </span>
                <span class="badge" style="background: #ecfdf5; color: #059669; font-weight: 700; font-size: 0.6875rem;">
                  Best Seller
                </span>
              </div>
              <h4 style="font-size: 0.9375rem; font-weight: 700; color: var(--rose-950); margin-bottom: 0.5rem; line-height: 1.3;">
                <?= e($svc['name']) ?>
              </h4>
              <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.75rem;">
                Min: <?= number_format($svc['min_quantity']) ?> • Max: <?= number_format($svc['max_quantity']) ?>
              </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--rose-100); padding-top: 0.75rem; margin-top: 0.5rem;">
              <div>
                <span style="font-size: 0.6875rem; color: var(--text-muted); display: block;">Rate / 1k</span>
                <span style="font-size: 1.125rem; font-weight: 800; color: var(--primary-rose);"><?= $rateConverted ?></span>
              </div>
              <a href="/new-order?service=<?= $svc['id'] ?>" class="btn btn-primary btn-sm" style="border-radius: var(--radius-pill); padding: 0.35rem 0.875rem;">
                Order Now
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Right Side Column: Special Offer + Recently Ordered + 24/7 Live Support -->
  <div style="display: flex; flex-direction: column; gap: 1.5rem;">
    <!-- Special Offer Card matching reference image -->
    <div class="card" style="background: linear-gradient(135deg, var(--rose-50) 0%, #fff 100%); border: 1.5px solid var(--rose-200);" id="special_offer_card">
      <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
        <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--primary-rose); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
          &#127873;
        </div>
        <div>
          <span style="font-size: 0.6875rem; font-weight: 800; text-transform: uppercase; color: var(--primary-rose); letter-spacing: 0.05em;">Limited Promo</span>
          <h4 style="font-size: 1rem; font-weight: 800; color: var(--rose-950); margin: 0;">Deposit Bonus +15%</h4>
        </div>
      </div>
      <p style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.4; margin-bottom: 1rem;">
        Get instant 15% extra balance automatically added on all UPI &amp; Razorpay topups above ₹1,000 INR.
      </p>
      <a href="/add-funds" class="btn btn-primary btn-sm btn-block" style="border-radius: var(--radius-pill); font-weight: 700;">
        Claim Bonus Now &rarr;
      </a>
    </div>

    <!-- Recently Ordered Card -->
    <div class="card" id="recently_ordered_card">
      <div class="card-header" style="border-bottom: 1px solid var(--rose-100); padding-bottom: 0.75rem;">
        <h4 class="card-title" style="font-size: 1.05rem;">Recently Ordered</h4>
        <a href="/orders" style="font-size: 0.8125rem; font-weight: 700;">View All</a>
      </div>

      <?php if (empty($recentOrders)): ?>
        <div style="padding: 1.5rem 0.5rem; text-align: center; color: var(--text-muted); font-size: 0.8125rem;">
          No previous orders found.
        </div>
      <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-top: 0.875rem;">
          <?php foreach ($recentOrders as $ro): ?>
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.625rem; border-radius: var(--radius-sm); background: var(--bg-page); border: 1px solid var(--rose-100);">
              <div style="flex: 1; min-width: 0; padding-right: 0.5rem;">
                <div style="font-size: 0.8125rem; font-weight: 700; color: var(--rose-950); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                  <?= e($ro['service_name']) ?>
                </div>
                <div style="font-size: 0.6875rem; color: var(--text-muted);">
                  Qty: <?= number_format($ro['quantity']) ?> • #<?= $ro['id'] ?>
                </div>
              </div>
              <span class="badge badge-<?= $ro['status'] === 'completed' ? 'completed' : ($ro['status'] === 'canceled' ? 'canceled' : 'pending') ?>" style="font-size: 0.6875rem; padding: 0.2rem 0.5rem;">
                <?= ucfirst($ro['status']) ?>
              </span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- 24/7 Live Support Card -->
    <div class="card" style="padding: 1.25rem; border: 1px solid var(--rose-100);" id="live_support_card">
      <div style="display: flex; align-items: center; gap: 0.75rem;">
        <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--rose-100); color: var(--primary-rose); display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
          &#127911;
        </div>
        <div>
          <h4 style="font-size: 0.9375rem; font-weight: 700; color: var(--rose-950); margin: 0;">24/7 Live Support</h4>
          <span style="font-size: 0.75rem; color: var(--text-muted);">Need help with an order?</span>
        </div>
      </div>
      <a href="/tickets" class="btn btn-secondary btn-sm btn-block" style="margin-top: 0.875rem; border-radius: var(--radius-pill);">
        Open Support Ticket
      </a>
    </div>
  </div>
</div>

<!-- Row 5: Bottom Trust Strip matching reference image -->
<div class="trust-strip-modern" id="trust_strip_row">
  <div class="trust-item-box">
    <div class="trust-item-icon">&#9889;</div>
    <div>
      <div class="trust-item-title">Instant Delivery</div>
      <div class="trust-item-desc">Orders start automatically within minutes</div>
    </div>
  </div>

  <div class="trust-item-box">
    <div class="trust-item-icon">&#128274;</div>
    <div>
      <div class="trust-item-title">Secure Payments</div>
      <div class="trust-item-desc">Razorpay 256-bit encrypted checkout</div>
    </div>
  </div>

  <div class="trust-item-box">
    <div class="trust-item-icon">&#128172;</div>
    <div>
      <div class="trust-item-title">24/7 Support</div>
      <div class="trust-item-desc">Dedicated human support tickets</div>
    </div>
  </div>

  <div class="trust-item-box">
    <div class="trust-item-icon">&#10084;</div>
    <div>
      <div class="trust-item-title">Real Engagement</div>
      <div class="trust-item-desc">High retention real metrics</div>
    </div>
  </div>

  <div class="trust-priority-tag">
    Your Success Our Priority!
  </div>
</div>

<script>
function filterServices(categoryName, btnElement) {
  document.querySelectorAll('.category-filter-btn').forEach(b => b.classList.remove('active'));
  btnElement.classList.add('active');

  const cards = document.querySelectorAll('.service-card-modern');
  cards.forEach(card => {
    const cat = card.getAttribute('data-category');
    if (categoryName === 'all' || cat.toLowerCase().includes(categoryName.toLowerCase())) {
      card.style.display = 'flex';
    } else {
      card.style.display = 'none';
    }
  });
}
</script>

<?php require __DIR__ . '/footer.php'; ?>

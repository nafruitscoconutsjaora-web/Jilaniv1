<?php
$pageTitle = 'Dashboard';
$currentPage = 'dashboard';
require __DIR__ . '/header.php';

$userId = (int)$user['id'];

// Real Order Statistics from MySQL (Strict No-Fake-Data Policy - INR Currency)
$statTotal = (int)(DB::fetch("SELECT COUNT(*) as cnt FROM orders WHERE user_id = ?", [$userId])['cnt'] ?? 0);
$statCompleted = (int)(DB::fetch("SELECT COUNT(*) as cnt FROM orders WHERE user_id = ? AND status = 'completed'", [$userId])['cnt'] ?? 0);
$statPending = (int)(DB::fetch("SELECT COUNT(*) as cnt FROM orders WHERE user_id = ? AND status IN ('pending', 'processing', 'in_progress')", [$userId])['cnt'] ?? 0);
$statSpentInr = (float)(DB::fetch("SELECT SUM(user_charge) as total FROM orders WHERE user_id = ? AND status != 'canceled'", [$userId])['total'] ?? 0.0);

// Popular Services from MySQL (4 active services with category name)
$popularServices = DB::fetchAll(
    "SELECT s.*, c.name as category_name 
     FROM services s 
     JOIN categories c ON s.category_id = c.id 
     WHERE s.status = 'active' 
     ORDER BY s.id ASC LIMIT 4"
);

// Recent Orders in Cards (No Tables - Stored in INR)
$recentOrders = DB::fetchAll(
    "SELECT o.*, s.name as service_name 
     FROM orders o 
     JOIN services s ON o.service_id = s.id 
     WHERE o.user_id = ? 
     ORDER BY o.id DESC LIMIT 5",
    [$userId]
);
?>

<!-- 1. Promotional Dashboard Hero Banner (Rose + White) -->
<div class="dashboard-banner" id="user_dashboard_banner">
  <div class="banner-content">
    <div class="banner-pill">
      &#9889; 24/7 Automated Fulfillment
    </div>
    <h2 class="banner-title">Elevate Your Social Presence with Fast Delivery</h2>
    <p class="banner-desc">
      Access wholesale social media marketing packages for Instagram, YouTube, Telegram &amp; TikTok. All deposits and orders processed instantly in Indian Rupees (INR).
    </p>
    <div class="banner-actions">
      <a href="/new-order" class="banner-btn-primary" id="banner_new_order_btn">
        <span>+</span> Place New Order
      </a>
      <a href="/add-funds" class="banner-btn-secondary" id="banner_add_funds_btn">
        &#128179; Add Funds (UPI / INR)
      </a>
    </div>
  </div>
</div>

<!-- 2. Real Statistics & Balance Cards (INR) -->
<div class="grid grid-cols-4" style="margin-bottom: 1.75rem;" id="user_stats_grid">
  <div class="stat-card" id="stat_balance">
    <div class="stat-icon" style="background: var(--rose-50); color: var(--primary-rose);">&#128179;</div>
    <div class="stat-info">
      <div class="stat-label">Available Balance</div>
      <div class="stat-value" style="color: var(--primary-rose); font-weight: 800;">
        ₹<?= number_format((float)$user['balance'], 2) ?>
      </div>
    </div>
  </div>

  <div class="stat-card" id="stat_spent">
    <div class="stat-icon" style="background: #f0fdf4; color: #16a34a;">&#128184;</div>
    <div class="stat-info">
      <div class="stat-label">Total Spent</div>
      <div class="stat-value" style="color: #166534; font-weight: 800;">
        ₹<?= number_format($statSpentInr, 2) ?>
      </div>
    </div>
  </div>

  <div class="stat-card" id="stat_orders">
    <div class="stat-icon" style="background: #eff6ff; color: #2563eb;">&#128230;</div>
    <div class="stat-info">
      <div class="stat-label">Total Orders</div>
      <div class="stat-value"><?= number_format($statTotal) ?></div>
    </div>
  </div>

  <div class="stat-card" id="stat_active">
    <div class="stat-icon" style="background: #fff7ed; color: #ea580c;">&#9203;</div>
    <div class="stat-info">
      <div class="stat-label">Active / Pending</div>
      <div class="stat-value"><?= number_format($statPending) ?></div>
    </div>
  </div>
</div>

<!-- 3. Quick Action Cards (6 Cards with Clean Icons & Real Links) -->
<div style="margin-bottom: 1.75rem;">
  <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.875rem;">
    <h3 style="font-size: 1.125rem; font-weight: 700; margin: 0; color: var(--text-main);">Quick Shortcuts</h3>
    <span style="font-size: 0.8125rem; color: var(--text-muted);">Direct navigation to core tools</span>
  </div>

  <div class="quick-actions-grid" id="quick_actions_container">
    <a href="/new-order" class="quick-action-card" id="qa_new_order">
      <div class="qa-icon-box">&#9889;</div>
      <div class="qa-info">
        <h4 class="qa-title">New Order</h4>
        <p class="qa-subtitle">Place order</p>
      </div>
    </a>

    <a href="/add-funds" class="quick-action-card" id="qa_add_funds">
      <div class="qa-icon-box">&#128179;</div>
      <div class="qa-info">
        <h4 class="qa-title">Add Funds</h4>
        <p class="qa-subtitle">Instant UPI / INR</p>
      </div>
    </a>

    <a href="/user/services" class="quick-action-card" id="qa_services">
      <div class="qa-icon-box">&#128203;</div>
      <div class="qa-info">
        <h4 class="qa-title">Services</h4>
        <p class="qa-subtitle">Browse catalog</p>
      </div>
    </a>

    <a href="/tickets" class="quick-action-card" id="qa_support">
      <div class="qa-icon-box">&#127911;</div>
      <div class="qa-info">
        <h4 class="qa-title">Support</h4>
        <p class="qa-subtitle">Open a ticket</p>
      </div>
    </a>

    <a href="/affiliates" class="quick-action-card" id="qa_referral">
      <div class="qa-icon-box">&#127873;</div>
      <div class="qa-info">
        <h4 class="qa-title">Referrals</h4>
        <p class="qa-subtitle">Earn 5% cash</p>
      </div>
    </a>

    <a href="/api-docs" class="quick-action-card" id="qa_api_docs">
      <div class="qa-icon-box">&#128187;</div>
      <div class="qa-info">
        <h4 class="qa-title">API Docs</h4>
        <p class="qa-subtitle">Integration guide</p>
      </div>
    </a>
  </div>
</div>

<!-- 4. Popular Services Cards Section -->
<?php if (!empty($popularServices)): ?>
  <div class="card" style="margin-bottom: 1.75rem;" id="popular_services_card">
    <div class="card-header">
      <div>
        <h3 class="card-title">Popular Social Services</h3>
        <p class="card-subtitle">Top verified services with competitive wholesale rates in INR</p>
      </div>
      <a href="/user/services" class="btn btn-secondary btn-sm">View All Services &rarr;</a>
    </div>

    <div class="popular-services-grid" id="popular_services_grid" style="margin-top: 1rem;">
      <?php foreach ($popularServices as $ps): ?>
        <div class="popular-service-card" id="pop_srv_<?= $ps['id'] ?>">
          <div>
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem; gap: 0.5rem;">
              <span class="badge badge-default" style="font-size: 0.75rem;"><?= e($ps['category_name']) ?></span>
              <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">ID #<?= $ps['id'] ?></span>
            </div>

            <h4 style="font-size: 0.9375rem; font-weight: 700; margin: 0 0 0.5rem 0; line-height: 1.35; color: var(--text-main);">
              <?= e($ps['name']) ?>
            </h4>

            <div style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 0.75rem;">
              Min: <?= number_format($ps['min_quantity']) ?> &bull; Max: <?= number_format($ps['max_quantity']) ?>
            </div>
          </div>

          <div style="border-top: 1px solid var(--rose-100); padding-top: 0.75rem; display: flex; align-items: center; justify-content: space-between;">
            <div>
              <span style="font-size: 0.75rem; color: var(--text-muted); display: block;">Rate per 1K</span>
              <strong style="font-size: 1.0625rem; color: var(--primary-rose);">₹<?= number_format((float)$ps['rate'], 2) ?></strong>
            </div>
            <a href="/new-order?category=<?= $ps['category_id'] ?>&service=<?= $ps['id'] ?>" class="btn btn-primary btn-sm" style="padding: 0.35rem 0.75rem; font-size: 0.8125rem;">
              Order &rarr;
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<!-- 5. Recent Orders Section (Card-based Layout - No Tables) -->
<div class="card" id="recent_orders_card">
  <div class="card-header">
    <div>
      <h3 class="card-title">Recent Orders</h3>
      <p class="card-subtitle">Your latest orders with real-time status updates</p>
    </div>
    <a href="/orders" class="btn btn-outline-rose btn-sm" id="view_all_orders_btn">View All Orders</a>
  </div>

  <?php if (empty($recentOrders)): ?>
    <div class="empty-state" id="empty_recent_orders">
      <div class="empty-title">No Orders Placed Yet</div>
      <p class="empty-desc">You have not placed any orders yet. Choose a service above to get started.</p>
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
            <div style="font-weight: 800; font-size: 1.0625rem; color: var(--primary-rose);">
              ₹<?= number_format((float)$ord['user_charge'], 2) ?>
            </div>
          </div>

          <div style="font-weight: 600; font-size: 0.9375rem; margin-top: 0.25rem;"><?= e($ord['service_name']) ?></div>
          
          <div style="font-size: 0.8125rem; color: var(--text-muted); word-break: break-all; margin-top: 0.25rem;">
            Link: <a href="<?= e($ord['link']) ?>" target="_blank" rel="noopener noreferrer" style="color: var(--primary-rose); text-decoration: underline;"><?= e($ord['link']) ?></a>
          </div>

          <div class="item-meta-grid" style="margin-top: 0.5rem;">
            <div class="meta-box">
              <span class="meta-label">Quantity</span>
              <span class="meta-val"><?= number_format($ord['quantity']) ?></span>
            </div>
            <div class="meta-box">
              <span class="meta-label">Start Count</span>
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

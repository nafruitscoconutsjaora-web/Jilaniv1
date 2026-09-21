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
?>

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

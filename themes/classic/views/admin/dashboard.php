<?php
$pageTitle = 'Administration Dashboard';
$currentPage = 'admin_dashboard';
require __DIR__ . '/header.php';

// Real Database Queries (Strict No-Fake-Data)
$totalUsers = (int)(DB::fetch("SELECT COUNT(*) as cnt FROM users WHERE role = 'user'")['cnt'] ?? 0);
$totalOrders = (int)(DB::fetch("SELECT COUNT(*) as cnt FROM orders")['cnt'] ?? 0);
$pendingOrders = (int)(DB::fetch("SELECT COUNT(*) as cnt FROM orders WHERE status IN ('pending', 'processing', 'in_progress')")['cnt'] ?? 0);
$totalRevenue = (float)(DB::fetch("SELECT SUM(charge) as rev FROM orders WHERE status != 'canceled'")['rev'] ?? 0.0);
$openTickets = (int)(DB::fetch("SELECT COUNT(*) as cnt FROM tickets WHERE status IN ('open', 'customer_reply')")['cnt'] ?? 0);
$totalServices = (int)(DB::fetch("SELECT COUNT(*) as cnt FROM services")['cnt'] ?? 0);
$totalProviders = (int)(DB::fetch("SELECT COUNT(*) as cnt FROM providers")['cnt'] ?? 0);

// Recent Orders
$recentOrders = DB::fetchAll(
    "SELECT o.*, u.username, s.name as service_name 
     FROM orders o 
     JOIN users u ON o.user_id = u.id 
     JOIN services s ON o.service_id = s.id 
     ORDER BY o.id DESC LIMIT 6"
);
?>

<!-- Statistics Overview -->
<div class="grid grid-cols-4" style="margin-bottom: 1.5rem;" id="admin_stats_grid">
  <div class="stat-card" id="admin_stat_users">
    <div class="stat-icon">&#128101;</div>
    <div class="stat-info">
      <div class="stat-label">Registered Users</div>
      <div class="stat-value"><?= number_format($totalUsers) ?></div>
    </div>
  </div>

  <div class="stat-card" id="admin_stat_orders">
    <div class="stat-icon">&#128230;</div>
    <div class="stat-info">
      <div class="stat-label">Total Orders</div>
      <div class="stat-value"><?= number_format($totalOrders) ?></div>
    </div>
  </div>

  <div class="stat-card" id="admin_stat_pending">
    <div class="stat-icon">&#9203;</div>
    <div class="stat-info">
      <div class="stat-label">Pending / Processing</div>
      <div class="stat-value" style="color: var(--primary-rose);"><?= number_format($pendingOrders) ?></div>
    </div>
  </div>

  <div class="stat-card" id="admin_stat_revenue">
    <div class="stat-icon">&#128176;</div>
    <div class="stat-info">
      <div class="stat-label">Total Volume (USD)</div>
      <div class="stat-value">$<?= number_format($totalRevenue, 2) ?></div>
    </div>
  </div>
</div>

<div class="grid grid-cols-3" style="margin-bottom: 1.5rem;">
  <div class="stat-card">
    <div class="stat-icon">&#127915;</div>
    <div class="stat-info">
      <div class="stat-label">Open Support Tickets</div>
      <div class="stat-value"><?= number_format($openTickets) ?></div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon">&#9881;</div>
    <div class="stat-info">
      <div class="stat-label">Active Services</div>
      <div class="stat-value"><?= number_format($totalServices) ?></div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon">&#127760;</div>
    <div class="stat-info">
      <div class="stat-label">Connected Providers</div>
      <div class="stat-value"><?= number_format($totalProviders) ?></div>
    </div>
  </div>
</div>

<!-- Recent Orders Management (Cards, No Tables) -->
<div class="card" id="admin_recent_orders_card">
  <div class="card-header">
    <div>
      <h3 class="card-title">Recent System Orders</h3>
      <p class="card-subtitle">Real-time order feed across all users</p>
    </div>
    <a href="/admin/orders" class="btn btn-outline-rose btn-sm">Manage All Orders</a>
  </div>

  <?php if (empty($recentOrders)): ?>
    <div class="empty-state" id="empty_admin_orders">
      <div class="empty-title">No Orders in System</div>
      <p class="empty-desc">No users have placed orders yet.</p>
    </div>
  <?php else: ?>
    <div class="card-list" id="admin_orders_list">
      <?php foreach ($recentOrders as $ord): ?>
        <div class="list-item-card" id="adm_order_card_<?= $ord['id'] ?>">
          <div class="item-card-row">
            <div style="display: flex; align-items: center; gap: 0.625rem; flex-wrap: wrap;">
              <span style="font-weight: 800; font-size: 1rem;">Order #<?= $ord['id'] ?></span>
              <?= get_status_badge($ord['status']) ?>
              <span style="font-size: 0.8125rem; color: var(--text-muted);">User: <strong><?= e($ord['username']) ?></strong></span>
              <span style="font-size: 0.8125rem; color: var(--text-muted);"><?= format_date($ord['created_at']) ?></span>
            </div>

            <div style="font-weight: 800; color: var(--primary-rose); font-size: 1rem;">
              <?= e($ord['charge_currency']) ?> <?= number_format($ord['user_charge'], 2) ?>
              <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 500;">($<?= number_format($ord['charge'], 4) ?>)</span>
            </div>
          </div>

          <h4 style="font-size: 0.9375rem; margin: 0.125rem 0;"><?= e($ord['service_name']) ?></h4>

          <div style="font-size: 0.8125rem; color: var(--text-muted); word-break: break-all;">
            Target: <a href="<?= e($ord['link']) ?>" target="_blank" rel="noopener noreferrer"><?= e($ord['link']) ?></a>
          </div>

          <div class="item-meta-grid">
            <div class="meta-box">
              <span class="meta-label">Quantity</span>
              <span class="meta-val"><?= number_format($ord['quantity']) ?></span>
            </div>
            <div class="meta-box">
              <span class="meta-label">Provider ID</span>
              <span class="meta-val"><?= !empty($ord['provider_order_id']) ? '#' . e($ord['provider_order_id']) : 'Manual / None' ?></span>
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

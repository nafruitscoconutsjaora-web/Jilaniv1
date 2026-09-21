<?php
$pageTitle = 'Order Management';
$currentPage = 'admin_orders';
require __DIR__ . '/header.php';

$search = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? 'all');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$where = "WHERE 1=1";
$params = [];

if ($status !== 'all' && in_array($status, ['pending', 'processing', 'in_progress', 'completed', 'partial', 'canceled'])) {
    $where .= " AND o.status = ?";
    $params[] = $status;
}

if (!empty($search)) {
    $where .= " AND (o.id = ? OR u.username LIKE ? OR o.link LIKE ? OR s.name LIKE ?)";
    $params[] = is_numeric($search) ? (int)$search : 0;
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$totalOrders = (int)(DB::fetch("SELECT COUNT(*) as cnt FROM orders o JOIN users u ON o.user_id = u.id JOIN services s ON o.service_id = s.id {$where}", $params)['cnt'] ?? 0);
$totalPages = ceil($totalOrders / $limit);

$orders = DB::fetchAll(
    "SELECT o.*, u.username, s.name as service_name, p.name as provider_name 
     FROM orders o 
     JOIN users u ON o.user_id = u.id 
     JOIN services s ON o.service_id = s.id 
     LEFT JOIN providers p ON o.provider_id = p.id 
     {$where} 
     ORDER BY o.id DESC 
     LIMIT {$limit} OFFSET {$offset}",
    $params
);
?>

<div class="filter-bar" id="admin_orders_filter">
  <div class="filter-chips">
    <a href="/admin/orders" class="chip <?= $status === 'all' ? 'active' : '' ?>">All Orders (<?= $totalOrders ?>)</a>
    <a href="/admin/orders?status=pending" class="chip <?= $status === 'pending' ? 'active' : '' ?>">Pending</a>
    <a href="/admin/orders?status=processing" class="chip <?= $status === 'processing' ? 'active' : '' ?>">Processing</a>
    <a href="/admin/orders?status=in_progress" class="chip <?= $status === 'in_progress' ? 'active' : '' ?>">In Progress</a>
    <a href="/admin/orders?status=completed" class="chip <?= $status === 'completed' ? 'active' : '' ?>">Completed</a>
    <a href="/admin/orders?status=partial" class="chip <?= $status === 'partial' ? 'active' : '' ?>">Partial</a>
    <a href="/admin/orders?status=canceled" class="chip <?= $status === 'canceled' ? 'active' : '' ?>">Canceled</a>
  </div>

  <form method="GET" action="/admin/orders" style="display: flex; gap: 0.5rem; flex: 1; max-width: 320px;">
    <?php if ($status !== 'all'): ?>
      <input type="hidden" name="status" value="<?= e($status) ?>">
    <?php endif; ?>
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search ID, user, link..." class="form-control" style="padding: 0.45rem 0.75rem;">
    <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
  </form>
</div>

<?php if (empty($orders)): ?>
  <div class="empty-state">
    <div class="empty-title">No Orders Found</div>
    <p class="empty-desc">No orders match your filter criteria.</p>
  </div>
<?php else: ?>
  <div class="card-list" id="admin_orders_card_list">
    <?php foreach ($orders as $ord): ?>
      <div class="list-item-card" id="admin_order_<?= $ord['id'] ?>">
        <div class="item-card-row">
          <div style="display: flex; align-items: center; gap: 0.625rem; flex-wrap: wrap;">
            <span style="font-weight: 800; font-size: 1rem;">Order #<?= $ord['id'] ?></span>
            <?= get_status_badge($ord['status']) ?>
            <span style="font-size: 0.8125rem; color: var(--text-muted);">
              User: <a href="/admin/users?q=<?= urlencode($ord['username']) ?>"><strong><?= e($ord['username']) ?></strong></a>
            </span>
            <span style="font-size: 0.8125rem; color: var(--text-muted);"><?= format_date($ord['created_at']) ?></span>
          </div>

          <div style="font-weight: 800; color: var(--primary-rose);">
            $<?= number_format($ord['charge'], 4) ?> USD
            <span style="font-size: 0.8125rem; color: var(--text-muted); font-weight: 500;">
              (User paid: <?= e($ord['charge_currency']) ?> <?= number_format($ord['user_charge'], 2) ?>)
            </span>
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
            <span class="meta-label">Provider</span>
            <span class="meta-val"><?= e($ord['provider_name'] ?? 'None / Manual') ?></span>
          </div>
          <div class="meta-box">
            <span class="meta-label">Provider Order ID</span>
            <span class="meta-val"><?= !empty($ord['provider_order_id']) ? '#' . e($ord['provider_order_id']) : 'None' ?></span>
          </div>
          <div class="meta-box">
            <span class="meta-label">Remains</span>
            <span class="meta-val"><?= number_format($ord['remains']) ?></span>
          </div>
        </div>

        <!-- Order Actions & Status Change Controls inside card -->
        <div class="item-card-row" style="margin-top: 0.5rem; padding-top: 0.75rem; border-top: 1px solid var(--rose-100);">
          <form method="POST" action="/admin/orders/status" style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
            <?= csrf_field() ?>
            <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
            <label style="font-size: 0.8125rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0;">Status:</label>
            <select name="status" class="form-control" style="width: auto; padding: 0.35rem 0.5rem; font-size: 0.8125rem;">
              <option value="pending" <?= $ord['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
              <option value="processing" <?= $ord['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
              <option value="in_progress" <?= $ord['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
              <option value="completed" <?= $ord['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
              <option value="partial" <?= $ord['status'] === 'partial' ? 'selected' : '' ?>>Partial</option>
              <option value="canceled" <?= $ord['status'] === 'canceled' ? 'selected' : '' ?>>Canceled</option>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">Update</button>
          </form>

          <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
            <?php if ($ord['status'] !== 'canceled'): ?>
              <form method="POST" action="/admin/orders/refund">
                <?= csrf_field() ?>
                <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                <button type="submit" class="btn btn-outline-rose btn-sm" onclick="return confirm('Refund $<?= number_format($ord['charge'], 4) ?> to user wallet and cancel order?')">
                  Cancel & Refund
                </button>
              </form>
            <?php endif; ?>

            <?php if ($ord['provider_id'] && empty($ord['provider_order_id'])): ?>
              <form method="POST" action="/admin/orders/resend">
                <?= csrf_field() ?>
                <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                <button type="submit" class="btn btn-primary btn-sm">
                  Send to Provider
                </button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <a href="/admin/orders?page=<?= $p ?><?= $status !== 'all' ? '&status=' . urlencode($status) : '' ?><?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" 
           class="page-link <?= $page === $p ? 'active' : '' ?>">
          <?= $p ?>
        </a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>

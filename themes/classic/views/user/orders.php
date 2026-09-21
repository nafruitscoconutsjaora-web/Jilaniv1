<?php
$pageTitle = 'Order History';
$currentPage = 'orders';
require __DIR__ . '/header.php';

$userId = (int)$user['id'];
$status = trim($_GET['status'] ?? 'all');
$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$whereClause = "WHERE o.user_id = ?";
$params = [$userId];

if ($status !== 'all' && in_array($status, ['pending', 'processing', 'in_progress', 'completed', 'partial', 'canceled'])) {
    $whereClause .= " AND o.status = ?";
    $params[] = $status;
}

if (!empty($search)) {
    $whereClause .= " AND (o.id = ? OR o.link LIKE ? OR s.name LIKE ?)";
    $params[] = is_numeric($search) ? (int)$search : 0;
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

// Count total
$countQuery = "SELECT COUNT(*) as cnt FROM orders o JOIN services s ON o.service_id = s.id {$whereClause}";
$totalOrders = (int)(DB::fetch($countQuery, $params)['cnt'] ?? 0);
$totalPages = ceil($totalOrders / $limit);

// Fetch orders
$query = "SELECT o.*, s.name as service_name 
          FROM orders o 
          JOIN services s ON o.service_id = s.id 
          {$whereClause} 
          ORDER BY o.id DESC 
          LIMIT {$limit} OFFSET {$offset}";
$orders = DB::fetchAll($query, $params);
?>

<div class="filter-bar" id="orders_filter_bar">
  <div class="filter-chips" id="order_status_chips">
    <a href="/orders<?= !empty($search) ? '?q=' . urlencode($search) : '' ?>" class="chip <?= $status === 'all' ? 'active' : '' ?>">All</a>
    <a href="/orders?status=pending<?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" class="chip <?= $status === 'pending' ? 'active' : '' ?>">Pending</a>
    <a href="/orders?status=processing<?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" class="chip <?= $status === 'processing' ? 'active' : '' ?>">Processing</a>
    <a href="/orders?status=in_progress<?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" class="chip <?= $status === 'in_progress' ? 'active' : '' ?>">In Progress</a>
    <a href="/orders?status=completed<?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" class="chip <?= $status === 'completed' ? 'active' : '' ?>">Completed</a>
    <a href="/orders?status=partial<?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" class="chip <?= $status === 'partial' ? 'active' : '' ?>">Partial</a>
    <a href="/orders?status=canceled<?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" class="chip <?= $status === 'canceled' ? 'active' : '' ?>">Canceled</a>
  </div>

  <form method="GET" action="/orders" style="display: flex; gap: 0.5rem; flex: 1; max-width: 320px;">
    <?php if ($status !== 'all'): ?>
      <input type="hidden" name="status" value="<?= e($status) ?>">
    <?php endif; ?>
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search ID, link, service..." class="form-control" style="padding: 0.45rem 0.75rem;">
    <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
  </form>
</div>

<?php if (empty($orders)): ?>
  <div class="empty-state" id="empty_orders_state">
    <div class="empty-title">No Orders Found</div>
    <p class="empty-desc">There are no orders matching your selected status filter or search keyword.</p>
    <a href="/new-order" class="btn btn-primary btn-sm">Place a New Order</a>
  </div>
<?php else: ?>
  <div class="card-list" id="orders_card_list">
    <?php foreach ($orders as $ord): ?>
      <div class="list-item-card" id="user_order_<?= $ord['id'] ?>">
        <div class="item-card-row">
          <div style="display: flex; align-items: center; gap: 0.625rem; flex-wrap: wrap;">
            <span style="font-weight: 800; font-size: 1rem;">Order #<?= $ord['id'] ?></span>
            <?= get_status_badge($ord['status']) ?>
            <span style="font-size: 0.8125rem; color: var(--text-muted);"><?= format_date($ord['created_at']) ?></span>
          </div>

          <!-- Exact immutable snapshot price -->
          <div style="font-weight: 800; font-size: 1.0625rem; color: var(--primary-rose);">
            <?= e($ord['charge_currency']) ?> <?= number_format($ord['user_charge'], 2) ?>
            <span style="font-size: 0.75rem; font-weight: 500; color: var(--text-muted);">($<?= number_format($ord['charge'], 4) ?>)</span>
          </div>
        </div>

        <h4 style="font-size: 1rem; margin: 0.125rem 0;"><?= e($ord['service_name']) ?></h4>

        <div style="font-size: 0.875rem; color: var(--text-muted); word-break: break-all;">
          <strong>Target Link:</strong> <a href="<?= e($ord['link']) ?>" target="_blank" rel="noopener noreferrer"><?= e($ord['link']) ?></a>
        </div>

        <div class="item-meta-grid">
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
          <div class="meta-box">
            <span class="meta-label">Exchange Rate</span>
            <span class="meta-val"><?= number_format($ord['currency_rate'], 4) ?></span>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Pagination Controls -->
  <?php if ($totalPages > 1): ?>
    <div class="pagination" id="orders_pagination">
      <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <a href="/orders?page=<?= $p ?><?= $status !== 'all' ? '&status=' . urlencode($status) : '' ?><?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" 
           class="page-link <?= $page === $p ? 'active' : '' ?>">
          <?= $p ?>
        </a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>

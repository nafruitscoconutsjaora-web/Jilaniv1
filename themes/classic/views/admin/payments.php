<?php
$pageTitle = 'Payments & Transactions';
$currentPage = 'admin_payments';
require __DIR__ . '/header.php';

$search = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? 'all');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$where = "WHERE 1=1";
$params = [];

if ($status !== 'all' && in_array($status, ['completed', 'pending', 'failed'])) {
    $where .= " AND p.status = ?";
    $params[] = $status;
}

if (!empty($search)) {
    $where .= " AND (p.order_id LIKE ? OR p.payment_id LIKE ? OR u.username LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$totalPayments = (int)(DB::fetch("SELECT COUNT(*) as cnt FROM payments p JOIN users u ON p.user_id = u.id {$where}", $params)['cnt'] ?? 0);
$totalPages = ceil($totalPayments / $limit);

$payments = DB::fetchAll(
    "SELECT p.*, u.username, u.email 
     FROM payments p 
     JOIN users u ON p.user_id = u.id 
     {$where} 
     ORDER BY p.id DESC 
     LIMIT {$limit} OFFSET {$offset}",
    $params
);
?>

<div class="filter-bar">
  <div class="filter-chips">
    <a href="/admin/payments" class="chip <?= $status === 'all' ? 'active' : '' ?>">All Payments (<?= $totalPayments ?>)</a>
    <a href="/admin/payments?status=completed" class="chip <?= $status === 'completed' ? 'active' : '' ?>">Completed</a>
    <a href="/admin/payments?status=pending" class="chip <?= $status === 'pending' ? 'active' : '' ?>">Pending</a>
    <a href="/admin/payments?status=failed" class="chip <?= $status === 'failed' ? 'active' : '' ?>">Failed</a>
  </div>

  <form method="GET" action="/admin/payments" style="display: flex; gap: 0.5rem; flex: 1; max-width: 320px;">
    <?php if ($status !== 'all'): ?>
      <input type="hidden" name="status" value="<?= e($status) ?>">
    <?php endif; ?>
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search Order ID, user..." class="form-control" style="padding: 0.45rem 0.75rem;">
    <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
  </form>
</div>

<?php if (empty($payments)): ?>
  <div class="empty-state">
    <div class="empty-title">No Payment Records Found</div>
    <p class="empty-desc">No payment transactions match your query.</p>
  </div>
<?php else: ?>
  <div class="card-list" id="admin_payments_list">
    <?php foreach ($payments as $pay): ?>
      <div class="list-item-card" id="payment_record_<?= $pay['id'] ?>">
        <div class="item-card-row">
          <div style="display: flex; align-items: center; gap: 0.625rem; flex-wrap: wrap;">
            <span style="font-weight: 800;">Payment #<?= $pay['id'] ?></span>
            <?= get_status_badge($pay['status']) ?>
            <span class="badge badge-default"><?= strtoupper(e($pay['payment_method'])) ?></span>
            <span style="font-size: 0.8125rem; color: var(--text-muted);"><?= format_date($pay['created_at']) ?></span>
          </div>

          <div style="font-size: 1.125rem; font-weight: 800; color: #166534;">
            +<?= e($pay['currency']) ?> <?= number_format($pay['converted_amount'], 2) ?>
            <span style="font-size: 0.8125rem; color: var(--text-muted); font-weight: 500;">($<?= number_format($pay['amount'], 2) ?> USD)</span>
          </div>
        </div>

        <div class="item-card-row">
          <span style="font-size: 0.875rem;">
            Customer: <a href="/admin/users?q=<?= urlencode($pay['username']) ?>"><strong><?= e($pay['username']) ?></strong></a> (<?= e($pay['email']) ?>)
          </span>
        </div>

        <div class="item-meta-grid">
          <div class="meta-box">
            <span class="meta-label">Gateway Order ID</span>
            <span class="meta-val"><?= e($pay['order_id']) ?></span>
          </div>
          <div class="meta-box">
            <span class="meta-label">Gateway Payment ID</span>
            <span class="meta-val"><?= !empty($pay['payment_id']) ? e($pay['payment_id']) : 'Pending Gateway' ?></span>
          </div>
          <div class="meta-box">
            <span class="meta-label">Updated At</span>
            <span class="meta-val"><?= format_date($pay['updated_at']) ?></span>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <a href="/admin/payments?page=<?= $p ?><?= $status !== 'all' ? '&status=' . urlencode($status) : '' ?><?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" 
           class="page-link <?= $page === $p ? 'active' : '' ?>">
          <?= $p ?>
        </a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>

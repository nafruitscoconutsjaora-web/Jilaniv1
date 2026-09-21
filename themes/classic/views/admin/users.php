<?php
$pageTitle = 'User Management';
$currentPage = 'admin_users';
require __DIR__ . '/header.php';

$search = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? 'all');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$where = "WHERE role = 'user'";
$params = [];

if ($status === 'active' || $status === 'suspended') {
    $where .= " AND status = ?";
    $params[] = $status;
}

if (!empty($search)) {
    $where .= " AND (username LIKE ? OR email LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$totalUsers = (int)(DB::fetch("SELECT COUNT(*) as cnt FROM users {$where}", $params)['cnt'] ?? 0);
$totalPages = ceil($totalUsers / $limit);

$users = DB::fetchAll(
    "SELECT u.*, 
            (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as order_count,
            (SELECT SUM(charge) FROM orders WHERE user_id = u.id AND status != 'canceled') as total_spent
     FROM users u 
     {$where} 
     ORDER BY u.id DESC 
     LIMIT {$limit} OFFSET {$offset}",
    $params
);
?>

<div class="filter-bar" id="admin_users_filter">
  <div class="filter-chips">
    <a href="/admin/users" class="chip <?= $status === 'all' ? 'active' : '' ?>">All Users (<?= $totalUsers ?>)</a>
    <a href="/admin/users?status=active" class="chip <?= $status === 'active' ? 'active' : '' ?>">Active</a>
    <a href="/admin/users?status=suspended" class="chip <?= $status === 'suspended' ? 'active' : '' ?>">Suspended</a>
  </div>

  <form method="GET" action="/admin/users" style="display: flex; gap: 0.5rem; flex: 1; max-width: 320px;">
    <?php if ($status !== 'all'): ?>
      <input type="hidden" name="status" value="<?= e($status) ?>">
    <?php endif; ?>
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search username, email..." class="form-control" style="padding: 0.45rem 0.75rem;">
    <button type="submit" class="btn btn-secondary btn-sm">Search</button>
  </form>
</div>

<?php if (empty($users)): ?>
  <div class="empty-state">
    <div class="empty-title">No Users Found</div>
    <p class="empty-desc">No accounts found matching your search or filter.</p>
  </div>
<?php else: ?>
  <div class="card-list" id="admin_users_card_list">
    <?php foreach ($users as $u): ?>
      <div class="list-item-card" id="user_entry_<?= $u['id'] ?>">
        <div class="item-card-row">
          <div style="display: flex; align-items: center; gap: 0.625rem; flex-wrap: wrap;">
            <span style="font-weight: 800; font-size: 1.0625rem;"><?= e($u['username']) ?></span>
            <?= get_status_badge($u['status']) ?>
            <span style="font-size: 0.8125rem; color: var(--text-muted);"><?= e($u['email']) ?></span>
          </div>

          <div style="font-size: 1.125rem; font-weight: 800; color: var(--primary-rose);">
            $<?= number_format($u['balance'], 4) ?> USD
            <span style="font-size: 0.8125rem; color: var(--text-muted); font-weight: 500;">(Pref: <?= e($u['currency_code']) ?>)</span>
          </div>
        </div>

        <div class="item-meta-grid">
          <div class="meta-box">
            <span class="meta-label">User ID</span>
            <span class="meta-val">#<?= $u['id'] ?></span>
          </div>
          <div class="meta-box">
            <span class="meta-label">Total Orders</span>
            <span class="meta-val"><?= number_format($u['order_count']) ?></span>
          </div>
          <div class="meta-box">
            <span class="meta-label">Total Spent</span>
            <span class="meta-val">$<?= number_format((float)$u['total_spent'], 2) ?></span>
          </div>
          <div class="meta-box">
            <span class="meta-label">Registered</span>
            <span class="meta-val"><?= format_date($u['created_at'], 'M d, Y') ?></span>
          </div>
        </div>

        <!-- Management Actions within Card -->
        <div class="item-card-row" style="margin-top: 0.5rem; padding-top: 0.75rem; border-top: 1px solid var(--rose-100);">
          <!-- Balance Adjustment Form -->
          <form method="POST" action="/admin/users/balance" style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
            <?= csrf_field() ?>
            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
            <select name="action_type" class="form-control" style="width: auto; padding: 0.35rem 0.5rem; font-size: 0.8125rem;">
              <option value="add">Add (+)</option>
              <option value="deduct">Deduct (-)</option>
            </select>
            <input type="number" step="0.01" min="0.01" name="amount" required placeholder="USD Amount" class="form-control" style="width: 110px; padding: 0.35rem 0.5rem; font-size: 0.8125rem;">
            <input type="text" name="reason" placeholder="Ledger Note / Reason" class="form-control" style="width: 150px; padding: 0.35rem 0.5rem; font-size: 0.8125rem;" required>
            <button type="submit" class="btn btn-primary btn-sm">Adjust</button>
          </form>

          <!-- Status Toggle Form -->
          <form method="POST" action="/admin/users/status">
            <?= csrf_field() ?>
            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
            <input type="hidden" name="new_status" value="<?= $u['status'] === 'active' ? 'suspended' : 'active' ?>">
            <button type="submit" class="btn <?= $u['status'] === 'active' ? 'btn-secondary' : 'btn-outline-rose' ?> btn-sm" onclick="return confirm('Are you sure you want to change this user status?')">
              <?= $u['status'] === 'active' ? 'Suspend User' : 'Activate User' ?>
            </button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <a href="/admin/users?page=<?= $p ?><?= $status !== 'all' ? '&status=' . urlencode($status) : '' ?><?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" 
           class="page-link <?= $page === $p ? 'active' : '' ?>">
          <?= $p ?>
        </a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>

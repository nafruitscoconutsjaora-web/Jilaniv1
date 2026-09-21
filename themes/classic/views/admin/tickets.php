<?php
$pageTitle = 'Support Ticket Management';
$currentPage = 'admin_tickets';
require __DIR__ . '/header.php';

$status = trim($_GET['status'] ?? 'all');
$where = "WHERE 1=1";
$params = [];

if ($status !== 'all' && in_array($status, ['open', 'answered', 'customer_reply', 'closed'])) {
    $where .= " AND t.status = ?";
    $params[] = $status;
}

$tickets = DB::fetchAll(
    "SELECT t.*, u.username, u.email 
     FROM tickets t 
     JOIN users u ON t.user_id = u.id 
     {$where} 
     ORDER BY t.updated_at DESC",
    $params
);
?>

<div class="filter-bar">
  <div class="filter-chips">
    <a href="/admin/tickets" class="chip <?= $status === 'all' ? 'active' : '' ?>">All Tickets</a>
    <a href="/admin/tickets?status=open" class="chip <?= $status === 'open' ? 'active' : '' ?>">Open</a>
    <a href="/admin/tickets?status=customer_reply" class="chip <?= $status === 'customer_reply' ? 'active' : '' ?>">User Reply</a>
    <a href="/admin/tickets?status=answered" class="chip <?= $status === 'answered' ? 'active' : '' ?>">Answered</a>
    <a href="/admin/tickets?status=closed" class="chip <?= $status === 'closed' ? 'active' : '' ?>">Closed</a>
  </div>
</div>

<?php if (empty($tickets)): ?>
  <div class="empty-state">
    <div class="empty-title">No Tickets in System</div>
    <p class="empty-desc">There are no support tickets matching the filter.</p>
  </div>
<?php else: ?>
  <div class="card-list" id="admin_tickets_list">
    <?php foreach ($tickets as $t): ?>
      <div class="list-item-card" id="admin_ticket_<?= $t['id'] ?>">
        <div class="item-card-row">
          <div style="display: flex; align-items: center; gap: 0.625rem; flex-wrap: wrap;">
            <span style="font-weight: 800;">Ticket #<?= $t['id'] ?></span>
            <?= get_status_badge($t['status']) ?>
            <span class="badge badge-default" style="text-transform: capitalize;"><?= e($t['priority']) ?> Priority</span>
            <span style="font-size: 0.8125rem; color: var(--text-muted);">
              User: <a href="/admin/users?q=<?= urlencode($t['username']) ?>"><strong><?= e($t['username']) ?></strong></a>
            </span>
          </div>

          <span style="font-size: 0.8125rem; color: var(--text-muted);"><?= format_date($t['updated_at']) ?></span>
        </div>

        <h4 style="font-size: 1.0625rem; margin: 0.25rem 0;"><?= e($t['subject']) ?></h4>

        <div class="item-card-row" style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid var(--rose-100);">
          <span style="font-size: 0.8125rem; color: var(--text-muted);">Opened: <?= format_date($t['created_at']) ?></span>
          <a href="/admin/tickets/view?id=<?= $t['id'] ?>" class="btn btn-primary btn-sm">
            View Conversation & Reply &rarr;
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>

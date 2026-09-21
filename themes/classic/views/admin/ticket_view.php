<?php
$pageTitle = 'Manage Ticket';
$currentPage = 'admin_tickets';
require __DIR__ . '/header.php';

$ticketId = (int)($_GET['id'] ?? 0);
$ticket = DB::fetch(
    "SELECT t.*, u.username, u.email 
     FROM tickets t 
     JOIN users u ON t.user_id = u.id 
     WHERE t.id = ? LIMIT 1",
    [$ticketId]
);

if (!$ticket) {
    flash_set('error', 'Ticket not found.');
    redirect('/admin/tickets');
}

$messages = DB::fetchAll(
    "SELECT tm.*, u.username, u.role 
     FROM ticket_messages tm 
     JOIN users u ON tm.user_id = u.id 
     WHERE tm.ticket_id = ? 
     ORDER BY tm.id ASC",
    [$ticketId]
);
?>

<div style="max-width: 860px; margin: 0 auto;">
  <div style="margin-bottom: 1.25rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
    <a href="/admin/tickets" class="btn btn-secondary btn-sm">&larr; Back to Tickets</a>

    <!-- Status Change Form for Admin -->
    <form method="POST" action="/admin/tickets/status" style="display: flex; gap: 0.5rem; align-items: center;">
      <?= csrf_field() ?>
      <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
      <select name="status" class="form-control" style="width: auto; padding: 0.35rem 0.6rem; font-size: 0.8125rem;">
        <option value="open" <?= $ticket['status'] === 'open' ? 'selected' : '' ?>>Open</option>
        <option value="answered" <?= $ticket['status'] === 'answered' ? 'selected' : '' ?>>Answered</option>
        <option value="customer_reply" <?= $ticket['status'] === 'customer_reply' ? 'selected' : '' ?>>Customer Reply</option>
        <option value="closed" <?= $ticket['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
      </select>
      <button type="submit" class="btn btn-secondary btn-sm">Set Status</button>
    </form>
  </div>

  <div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
      <h3 class="card-title">Ticket #<?= $ticket['id'] ?>: <?= e($ticket['subject']) ?></h3>
      <?= get_status_badge($ticket['status']) ?>
    </div>
    <div class="item-meta-grid" style="margin: 0;">
      <div class="meta-box">
        <span class="meta-label">Customer</span>
        <span class="meta-val"><?= e($ticket['username']) ?> (<?= e($ticket['email']) ?>)</span>
      </div>
      <div class="meta-box">
        <span class="meta-label">Priority</span>
        <span class="meta-val" style="text-transform: capitalize;"><?= e($ticket['priority']) ?></span>
      </div>
      <div class="meta-box">
        <span class="meta-label">Opened</span>
        <span class="meta-val"><?= format_date($ticket['created_at']) ?></span>
      </div>
    </div>
  </div>

  <!-- Messages Conversation Stream -->
  <div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 2rem;">
    <?php foreach ($messages as $msg): ?>
      <?php $isAdminReply = ($msg['role'] === 'admin'); ?>
      <div class="card" style="border-left: 4px solid <?= $isAdminReply ? 'var(--primary-rose)' : 'var(--rose-300)' ?>; background: <?= $isAdminReply ? 'var(--bg-subtle)' : '#ffffff' ?>;">
        <div class="item-card-row" style="margin-bottom: 0.75rem;">
          <div style="display: flex; align-items: center; gap: 0.5rem;">
            <strong style="font-size: 0.9375rem;"><?= e($msg['username']) ?></strong>
            <?php if ($isAdminReply): ?>
              <span class="badge badge-completed">Staff Administrator</span>
            <?php else: ?>
              <span class="badge badge-default">Customer</span>
            <?php endif; ?>
          </div>
          <span style="font-size: 0.8125rem; color: var(--text-muted);"><?= format_date($msg['created_at']) ?></span>
        </div>
        <div style="font-size: 0.9375rem; line-height: 1.6; color: var(--text-main); white-space: pre-line;">
          <?= e($msg['message']) ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Reply Form Card -->
  <div class="card" id="admin_reply_card">
    <div class="card-header">
      <h4 class="card-title" style="font-size: 1.125rem;">Reply as Administrator</h4>
    </div>

    <form method="POST" action="/admin/tickets/reply">
      <?= csrf_field() ?>
      <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">

      <div class="form-group">
        <textarea name="message" class="form-control" rows="4" required placeholder="Write a professional support response to the user..."></textarea>
      </div>

      <button type="submit" class="btn btn-primary">
        Post Admin Response & Mark Answered
      </button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>

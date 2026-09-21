<?php
$pageTitle = 'View Ticket';
$currentPage = 'tickets';
require __DIR__ . '/header.php';

$userId = (int)$user['id'];
$ticketId = (int)($_GET['id'] ?? 0);

$ticket = DB::fetch(
    "SELECT * FROM tickets WHERE id = ? AND user_id = ? LIMIT 1",
    [$ticketId, $userId]
);

if (!$ticket) {
    flash_set('error', 'Ticket not found or access denied.');
    redirect('/tickets');
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
  <div style="margin-bottom: 1.25rem; display: flex; justify-content: space-between; align-items: center;">
    <a href="/tickets" class="btn btn-secondary btn-sm">&larr; Back to Tickets</a>
    <div style="display: flex; gap: 0.5rem; align-items: center;">
      <span style="font-weight: 700;">Status:</span>
      <?= get_status_badge($ticket['status']) ?>
    </div>
  </div>

  <div class="card" style="margin-bottom: 1.5rem;" id="ticket_meta_card">
    <div class="card-header">
      <h3 class="card-title">Ticket #<?= $ticket['id'] ?>: <?= e($ticket['subject']) ?></h3>
    </div>
    <div class="item-meta-grid" style="margin: 0;">
      <div class="meta-box">
        <span class="meta-label">Priority</span>
        <span class="meta-val" style="text-transform: capitalize;"><?= e($ticket['priority']) ?></span>
      </div>
      <div class="meta-box">
        <span class="meta-label">Opened At</span>
        <span class="meta-val"><?= format_date($ticket['created_at']) ?></span>
      </div>
      <div class="meta-box">
        <span class="meta-label">Last Updated</span>
        <span class="meta-val"><?= format_date($ticket['updated_at']) ?></span>
      </div>
    </div>
  </div>

  <!-- Messages Conversation Stream -->
  <div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 2rem;" id="conversation_stream">
    <?php foreach ($messages as $msg): ?>
      <?php $isAdminReply = ($msg['role'] === 'admin'); ?>
      <div class="card" style="border-left: 4px solid <?= $isAdminReply ? 'var(--primary-rose)' : 'var(--rose-300)' ?>; background: <?= $isAdminReply ? 'var(--bg-subtle)' : '#ffffff' ?>;">
        <div class="item-card-row" style="margin-bottom: 0.75rem;">
          <div style="display: flex; align-items: center; gap: 0.5rem;">
            <strong style="font-size: 0.9375rem;"><?= e($msg['username']) ?></strong>
            <?php if ($isAdminReply): ?>
              <span class="badge badge-completed">Administrator</span>
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
  <?php if ($ticket['status'] !== 'closed'): ?>
    <div class="card" id="reply_ticket_card">
      <div class="card-header">
        <h4 class="card-title" style="font-size: 1.125rem;">Reply to Ticket</h4>
      </div>

      <form method="POST" action="/tickets/reply" id="reply_ticket_form">
        <?= csrf_field() ?>
        <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">

        <div class="form-group">
          <textarea name="message" class="form-control" rows="4" required placeholder="Type your response or follow-up question here..."></textarea>
        </div>

        <button type="submit" class="btn btn-primary">
          Send Reply
        </button>
      </form>
    </div>
  <?php else: ?>
    <div class="alert alert-info">
      This ticket has been marked as closed by the administration. If you have additional inquiries, please open a new ticket.
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/footer.php'; ?>

<?php
$pageTitle = 'Support Tickets';
$currentPage = 'tickets';
require __DIR__ . '/header.php';

$userId = (int)$user['id'];
$tickets = DB::fetchAll(
    "SELECT * FROM tickets WHERE user_id = ? ORDER BY updated_at DESC",
    [$userId]
);
?>

<div class="grid grid-cols-3" style="align-items: start;">
  <!-- Existing Tickets Card List (2 cols) -->
  <div class="card" style="grid-column: span 2;" id="tickets_list_card">
    <div class="card-header">
      <div>
        <h3 class="card-title">My Support Tickets</h3>
        <p class="card-subtitle">Direct communication with system support representatives</p>
      </div>
    </div>

    <?php if (empty($tickets)): ?>
      <div class="empty-state" id="empty_tickets_state">
        <div class="empty-title">No Tickets Created</div>
        <p class="empty-desc">Need help with an order or payment? Submit your first ticket using the form on the right.</p>
      </div>
    <?php else: ?>
      <div class="card-list" id="tickets_card_list">
        <?php foreach ($tickets as $t): ?>
          <div class="list-item-card" id="ticket_card_<?= $t['id'] ?>">
            <div class="item-card-row">
              <div style="display: flex; align-items: center; gap: 0.625rem; flex-wrap: wrap;">
                <span style="font-weight: 700;">Ticket #<?= $t['id'] ?></span>
                <?= get_status_badge($t['status']) ?>
                <span class="badge badge-default" style="text-transform: capitalize;"><?= e($t['priority']) ?> Priority</span>
              </div>
              <span style="font-size: 0.8125rem; color: var(--text-muted);"><?= format_date($t['updated_at']) ?></span>
            </div>

            <h4 style="font-size: 1.0625rem; margin: 0.25rem 0;"><?= e($t['subject']) ?></h4>

            <div class="item-card-row" style="margin-top: 0.5rem;">
              <span style="font-size: 0.8125rem; color: var(--text-muted);">Created on <?= format_date($t['created_at']) ?></span>
              <a href="/tickets/view?id=<?= $t['id'] ?>" class="btn btn-outline-rose btn-sm">
                Open Conversation &rarr;
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Create New Ticket Form (1 col) -->
  <div class="card" id="create_ticket_card">
    <div class="card-header">
      <h4 class="card-title" style="font-size: 1.125rem;">Create New Ticket</h4>
    </div>

    <form method="POST" action="/tickets/create" id="create_ticket_form">
      <?= csrf_field() ?>

      <div class="form-group">
        <label for="ticket_subject" class="form-label">Subject</label>
        <input type="text" id="ticket_subject" name="subject" class="form-control" required placeholder="Order inquiry, payment question...">
      </div>

      <div class="form-group">
        <label for="ticket_priority" class="form-label">Priority</label>
        <select id="ticket_priority" name="priority" class="form-control">
          <option value="low">Low</option>
          <option value="medium" selected>Medium</option>
          <option value="high">High</option>
        </select>
      </div>

      <div class="form-group">
        <label for="ticket_message" class="form-label">Detailed Message</label>
        <textarea id="ticket_message" name="message" class="form-control" rows="5" required placeholder="Please provide specific order IDs or transaction details..."></textarea>
      </div>

      <button type="submit" class="btn btn-primary btn-block" id="submit_ticket_btn">
        Submit Ticket
      </button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>

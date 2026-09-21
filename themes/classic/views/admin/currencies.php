<?php
$pageTitle = 'Currency Management';
$currentPage = 'admin_currencies';
require __DIR__ . '/header.php';

$currencies = DB::fetchAll("SELECT * FROM currencies ORDER BY is_default DESC, code ASC");
?>

<div class="grid grid-cols-3" style="align-items: start;">
  <!-- Currencies Card List (2 cols) -->
  <div class="card" style="grid-column: span 2;" id="currencies_card">
    <div class="card-header">
      <div>
        <h3 class="card-title">Supported Currencies</h3>
        <p class="card-subtitle">Configure real exchange rates and supported currencies for user accounts</p>
      </div>
    </div>

    <div class="card-list">
      <?php foreach ($currencies as $curr): ?>
        <div class="list-item-card" id="currency_card_<?= e($curr['code']) ?>">
          <div class="item-card-row">
            <div style="display: flex; align-items: center; gap: 0.625rem; flex-wrap: wrap;">
              <span style="font-weight: 800; font-size: 1.125rem;"><?= e($curr['code']) ?></span>
              <span style="font-size: 0.9375rem; color: var(--text-muted);"><?= e($curr['name']) ?></span>
              <span class="badge badge-default">Symbol: <?= e($curr['symbol']) ?></span>
              <?= get_status_badge($curr['status']) ?>
              <?php if ($curr['is_default']): ?>
                <span class="badge badge-completed">Default System Currency</span>
              <?php endif; ?>
            </div>

            <div style="font-size: 1.0625rem; font-weight: 800; color: var(--primary-rose);">
              1 USD = <?= number_format($curr['rate'], 4) ?> <?= e($curr['code']) ?>
            </div>
          </div>

          <!-- Update Rate & Status Form -->
          <form method="POST" action="/admin/currencies/update" class="item-card-row" style="margin-top: 0.5rem; padding-top: 0.75rem; border-top: 1px solid var(--rose-100);">
            <?= csrf_field() ?>
            <input type="hidden" name="code" value="<?= e($curr['code']) ?>">

            <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
              <label style="font-size: 0.8125rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0;">Rate to 1 USD:</label>
              <input type="number" step="0.000001" min="0.000001" name="rate" value="<?= (float)$curr['rate'] ?>" class="form-control" style="width: 120px; padding: 0.35rem 0.5rem; font-size: 0.8125rem;" required>

              <select name="status" class="form-control" style="width: auto; padding: 0.35rem 0.5rem; font-size: 0.8125rem;">
                <option value="active" <?= $curr['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $curr['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
              </select>
            </div>

            <div style="display: flex; gap: 0.5rem; align-items: center;">
              <button type="submit" class="btn btn-secondary btn-sm">Update Rate</button>

              <?php if (!$curr['is_default'] && $curr['status'] === 'active'): ?>
                <button type="submit" name="set_default" value="1" class="btn btn-outline-rose btn-sm">
                  Make Default
                </button>
              <?php endif; ?>
            </div>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Add New Currency Form (1 col) -->
  <div class="card" id="add_currency_card">
    <div class="card-header">
      <h4 class="card-title" style="font-size: 1.125rem;">Add New Currency</h4>
    </div>

    <form method="POST" action="/admin/currencies/create" id="create_currency_form">
      <?= csrf_field() ?>

      <div class="form-group">
        <label for="curr_code" class="form-label">Currency Code (ISO 4217)</label>
        <input type="text" id="curr_code" name="code" class="form-control" required placeholder="e.g. AED, GBP, CAD" maxlength="10">
      </div>

      <div class="form-group">
        <label for="curr_name" class="form-label">Currency Name</label>
        <input type="text" id="curr_name" name="name" class="form-control" required placeholder="e.g. UAE Dirham">
      </div>

      <div class="form-group">
        <label for="curr_symbol" class="form-label">Currency Symbol</label>
        <input type="text" id="curr_symbol" name="symbol" class="form-control" required placeholder="e.g. د.إ, £, CA$" maxlength="10">
      </div>

      <div class="form-group">
        <label for="curr_rate" class="form-label">Exchange Rate (1 USD = X)</label>
        <input type="number" step="0.000001" min="0.000001" id="curr_rate" name="rate" class="form-control" required placeholder="3.6725">
        <span class="form-text">Actual mathematical conversion multiplier applied to USD base rates</span>
      </div>

      <button type="submit" class="btn btn-primary btn-block">
        Add Currency
      </button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>

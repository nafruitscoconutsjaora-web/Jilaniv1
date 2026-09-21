<?php
$pageTitle = 'Payment Gateway Manager';
$currentPage = 'admin_gateways';
require __DIR__ . '/header.php';

// Fetch all payment gateways from MySQL
$gateways = DB::fetchAll("SELECT * FROM payment_gateways ORDER BY sort_order ASC, id ASC");
$webhookUrl = rtrim(APP_URL, '/') . '/payment/razorpay/webhook';
?>

<div class="card-header" style="margin-bottom: 1.5rem; padding-bottom: 0;">
  <div>
    <h2 class="card-title" style="font-size: 1.35rem;">Payment Gateway Manager</h2>
    <p class="card-subtitle">Configure, enable/disable, and securely manage API keys and credentials for payment gateways.</p>
  </div>
</div>

<div class="grid grid-cols-1" style="gap: 1.5rem;" id="gateways_list">
  <?php foreach ($gateways as $gw): 
    $config = !empty($gw['config']) ? json_decode($gw['config'], true) : [];
    $isEnabled = (bool)$gw['is_enabled'];
  ?>
    <div class="card" id="gateway_card_<?= e($gw['code']) ?>" style="border: 1px solid <?= $isEnabled ? 'var(--rose-200)' : 'var(--border-light)' ?>;">
      <div class="card-header" style="border-bottom: 1px solid var(--rose-100); padding-bottom: 1rem;">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
          <div style="width: 44px; height: 44px; border-radius: var(--radius-sm); background: var(--rose-50); border: 1px solid var(--rose-200); display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
            <?= $gw['code'] === 'razorpay' ? '&#128179;' : '&#128181;' ?>
          </div>
          <div>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
              <h3 class="card-title" style="font-size: 1.15rem; margin: 0;"><?= e($gw['name']) ?></h3>
              <span class="badge <?= $isEnabled ? 'badge-completed' : 'badge-canceled' ?>" id="gw_status_badge_<?= $gw['id'] ?>">
                <?= $isEnabled ? 'Active (Enabled)' : 'Inactive (Disabled)' ?>
              </span>
              <span class="badge" style="background: #f1f5f9; color: #475569; font-weight: 700;">
                Currency: <?= e($gw['currency']) ?>
              </span>
            </div>
            <p class="card-subtitle" style="margin-top: 0.25rem;"><?= e($gw['description']) ?></p>
          </div>
        </div>

        <form method="POST" action="/admin/payment-gateways/toggle" style="margin: 0;">
          <?= csrf_field() ?>
          <input type="hidden" name="gateway_id" value="<?= $gw['id'] ?>">
          <button type="submit" class="btn <?= $isEnabled ? 'btn-secondary' : 'btn-primary' ?> btn-sm" id="toggle_btn_<?= $gw['id'] ?>">
            <?= $isEnabled ? 'Disable Gateway' : 'Enable Gateway' ?>
          </button>
        </form>
      </div>

      <!-- Gateway Configuration Form -->
      <form method="POST" action="/admin/payment-gateways/update" id="gw_form_<?= $gw['id'] ?>" style="margin-top: 1.25rem;">
        <?= csrf_field() ?>
        <input type="hidden" name="gateway_id" value="<?= $gw['id'] ?>">

        <div class="grid grid-cols-2" style="gap: 1.25rem;">
          <?php if ($gw['code'] === 'razorpay'): ?>
            <div class="form-group">
              <label for="key_id_<?= $gw['id'] ?>" class="form-label">Razorpay Key ID <span style="color: var(--primary-rose);">*</span></label>
              <input type="text" id="key_id_<?= $gw['id'] ?>" name="config[key_id]" value="<?= e($config['key_id'] ?? '') ?>" class="form-control" placeholder="rzp_live_..." required>
              <span class="form-text">Found in Razorpay Dashboard &rarr; Settings &rarr; API Keys</span>
            </div>

            <div class="form-group">
              <label for="key_secret_<?= $gw['id'] ?>" class="form-label">Razorpay Key Secret <span style="color: var(--primary-rose);">*</span></label>
              <div style="position: relative;">
                <input type="password" id="key_secret_<?= $gw['id'] ?>" name="config[key_secret]" value="<?= e($config['key_secret'] ?? '') ?>" class="form-control" placeholder="Key Secret" required>
                <button type="button" onclick="const f = document.getElementById('key_secret_<?= $gw['id'] ?>'); f.type = f.type === 'password' ? 'text' : 'password';" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--text-muted); font-size: 0.8125rem;">
                  Show/Hide
                </button>
              </div>
              <span class="form-text">Used for server-side HMAC-SHA256 signature verification</span>
            </div>

            <div class="form-group" style="grid-column: span 2;">
              <label for="webhook_secret_<?= $gw['id'] ?>" class="form-label">Razorpay Webhook Secret</label>
              <input type="text" id="webhook_secret_<?= $gw['id'] ?>" name="config[webhook_secret]" value="<?= e($config['webhook_secret'] ?? '') ?>" class="form-control" placeholder="Secret entered in Razorpay Webhook settings">
              <div style="margin-top: 0.5rem; background: var(--bg-subtle); padding: 0.625rem 0.875rem; border-radius: var(--radius-sm); border: 1px solid var(--rose-100); font-size: 0.8125rem;">
                <strong>Webhook Callback URL:</strong> <code style="color: var(--primary-rose); user-select: all;"><?= e($webhookUrl) ?></code>
                <div style="margin-top: 0.25rem; color: var(--text-muted);">Enable <code>payment.captured</code> and <code>order.paid</code> events in your Razorpay Dashboard.</div>
              </div>
            </div>
          <?php endif; ?>

          <div class="form-group">
            <label for="min_amount_<?= $gw['id'] ?>" class="form-label">Minimum Deposit Amount (<?= e($gw['currency']) ?>)</label>
            <input type="number" step="1" min="1" id="min_amount_<?= $gw['id'] ?>" name="min_amount" value="<?= number_format((float)$gw['min_amount'], 2, '.', '') ?>" class="form-control" required>
            <span class="form-text">Minimum allowable single deposit</span>
          </div>

          <div class="form-group">
            <label for="max_amount_<?= $gw['id'] ?>" class="form-label">Maximum Deposit Amount (<?= e($gw['currency']) ?>)</label>
            <input type="number" step="1" min="1" id="max_amount_<?= $gw['id'] ?>" name="max_amount" value="<?= number_format((float)$gw['max_amount'], 2, '.', '') ?>" class="form-control" required>
            <span class="form-text">Maximum allowable single deposit</span>
          </div>

          <div class="form-group" style="grid-column: span 2;">
            <label for="instructions_<?= $gw['id'] ?>" class="form-label">Payment Instructions / Notes for Users</label>
            <textarea id="instructions_<?= $gw['id'] ?>" name="instructions" rows="2" class="form-control"><?= e($gw['instructions'] ?? '') ?></textarea>
            <span class="form-text">Displayed on the user-side Add Funds page when this gateway is selected.</span>
          </div>

          <div class="form-group" style="grid-column: span 2;">
            <label class="form-label">Gateway Status</label>
            <div style="display: flex; gap: 1.5rem; align-items: center;">
              <label style="display: flex; align-items: center; gap: 0.4rem; cursor: pointer;">
                <input type="radio" name="is_enabled" value="1" <?= $isEnabled ? 'checked' : '' ?>>
                <strong>Enabled</strong> (Appears on User Add Funds Page)
              </label>
              <label style="display: flex; align-items: center; gap: 0.4rem; cursor: pointer;">
                <input type="radio" name="is_enabled" value="0" <?= !$isEnabled ? 'checked' : '' ?>>
                <strong>Disabled</strong> (Hidden from User Add Funds Page)
              </label>
            </div>
          </div>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1rem;">
          <button type="submit" class="btn btn-primary" id="save_gw_btn_<?= $gw['id'] ?>">
            Save Gateway Settings
          </button>
        </div>
      </form>
    </div>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/footer.php'; ?>

<?php
$pageTitle = 'System Settings';
$currentPage = 'admin_settings';
require __DIR__ . '/header.php';

$siteName = get_setting('site_name', 'Rose SMM Panel');
$siteTagline = get_setting('site_tagline', 'Premium SMM Services');
$supportEmail = get_setting('support_email', 'admin@rosesmm.test');
$signupBonus = get_setting('signup_bonus', '0.00');
$maintenanceMode = get_setting('maintenance_mode', '0');

$razorpayEnabled = get_setting('razorpay_enabled', '0');
$razorpayKeyId = get_setting('razorpay_key_id', '');
$razorpayKeySecret = get_setting('razorpay_key_secret', '');
$razorpayWebhookSecret = get_setting('razorpay_webhook_secret', '');
$cronKey = get_setting('cron_key', 'cron_smm_secure_key_2026');

$cronUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/cron.php?key=' . urlencode($cronKey);
?>

<div class="grid grid-cols-2" style="align-items: start;">
  <!-- General Site Settings -->
  <div class="card" id="general_settings_card">
    <div class="card-header">
      <h3 class="card-title">General Platform Settings</h3>
    </div>

    <form method="POST" action="/admin/settings/save" id="general_settings_form">
      <?= csrf_field() ?>
      <input type="hidden" name="section" value="general">

      <div class="form-group">
        <label for="site_name" class="form-label">Platform Name</label>
        <input type="text" id="site_name" name="site_name" value="<?= e($siteName) ?>" class="form-control" required>
      </div>

      <div class="form-group">
        <label for="site_tagline" class="form-label">Tagline</label>
        <input type="text" id="site_tagline" name="site_tagline" value="<?= e($siteTagline) ?>" class="form-control" required>
      </div>

      <div class="form-group">
        <label for="support_email" class="form-label">Support Contact Email</label>
        <input type="email" id="support_email" name="support_email" value="<?= e($supportEmail) ?>" class="form-control" required>
      </div>

      <div class="form-group">
        <label for="signup_bonus" class="form-label">Welcome Signup Bonus (USD)</label>
        <input type="number" step="0.01" min="0" id="signup_bonus" name="signup_bonus" value="<?= e($signupBonus) ?>" class="form-control">
        <span class="form-text">Free balance automatically credited to new user wallets upon registration</span>
      </div>

      <div class="form-group">
        <label class="form-label">Maintenance Mode</label>
        <div style="display: flex; gap: 1rem; align-items: center;">
          <label style="display: flex; align-items: center; gap: 0.35rem; cursor: pointer;">
            <input type="radio" name="maintenance_mode" value="0" <?= $maintenanceMode === '0' ? 'checked' : '' ?>>
            Disabled (Online)
          </label>
          <label style="display: flex; align-items: center; gap: 0.35rem; cursor: pointer;">
            <input type="radio" name="maintenance_mode" value="1" <?= $maintenanceMode === '1' ? 'checked' : '' ?>>
            Enabled (Under Maintenance)
          </label>
        </div>
      </div>

      <button type="submit" class="btn btn-primary">
        Save General Settings
      </button>
    </form>
  </div>

  <!-- Razorpay Payment Gateway & Cron Settings -->
  <div style="display: flex; flex-direction: column; gap: 1.5rem;">
    <div class="card" id="razorpay_settings_card">
      <div class="card-header">
        <h3 class="card-title">Razorpay Payment Gateway</h3>
      </div>

      <form method="POST" action="/admin/settings/save" id="razorpay_settings_form">
        <?= csrf_field() ?>
        <input type="hidden" name="section" value="razorpay">

        <div class="form-group">
          <label class="form-label">Gateway Status</label>
          <div style="display: flex; gap: 1rem; align-items: center;">
            <label style="display: flex; align-items: center; gap: 0.35rem; cursor: pointer;">
              <input type="radio" name="razorpay_enabled" value="1" <?= $razorpayEnabled === '1' ? 'checked' : '' ?>>
              Enabled
            </label>
            <label style="display: flex; align-items: center; gap: 0.35rem; cursor: pointer;">
              <input type="radio" name="razorpay_enabled" value="0" <?= $razorpayEnabled === '0' ? 'checked' : '' ?>>
              Disabled
            </label>
          </div>
        </div>

        <div class="form-group">
          <label for="razorpay_key_id" class="form-label">Razorpay Key ID</label>
          <input type="text" id="razorpay_key_id" name="razorpay_key_id" value="<?= e($razorpayKeyId) ?>" class="form-control" placeholder="rzp_live_...">
        </div>

        <div class="form-group">
          <label for="razorpay_key_secret" class="form-label">Razorpay Key Secret</label>
          <input type="password" id="razorpay_key_secret" name="razorpay_key_secret" value="<?= e($razorpayKeySecret) ?>" class="form-control" placeholder="Secret Key">
        </div>

        <div class="form-group">
          <label for="razorpay_webhook_secret" class="form-label">Webhook Secret (Optional)</label>
          <input type="password" id="razorpay_webhook_secret" name="razorpay_webhook_secret" value="<?= e($razorpayWebhookSecret) ?>" class="form-control" placeholder="Webhook Secret">
        </div>

        <button type="submit" class="btn btn-primary">
          Save Razorpay Settings
        </button>
      </form>
    </div>

    <!-- Automated Order Synchronization (Cron Job) -->
    <div class="card" id="cron_settings_card">
      <div class="card-header">
        <h4 class="card-title" style="font-size: 1.125rem;">Automated Cron Job</h4>
      </div>

      <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 0.75rem;">
        Configure a cron job in cPanel or your Linux server every 5 minutes to automatically check order statuses with external providers and update customer accounts.
      </p>

      <div class="form-group">
        <label class="form-label">Cron Webhook URL:</label>
        <input type="text" readonly value="<?= e($cronUrl) ?>" class="form-control" style="font-family: monospace; font-size: 0.8125rem; background: var(--bg-subtle);">
      </div>

      <div style="background: var(--bg-subtle); padding: 0.75rem; border-radius: var(--radius-sm); font-family: monospace; font-size: 0.75rem; color: var(--text-muted);">
        cPanel Command:<br>
        */5 * * * * curl -s "<?= e($cronUrl) ?>" >/dev/null 2>&1
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>

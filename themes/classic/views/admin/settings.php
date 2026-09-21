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

$allBanners = DB::fetchAll("SELECT * FROM hero_banners ORDER BY sort_order ASC, id ASC");
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

    <!-- User Dashboard Promotional Hero Banner Slider Management -->
    <div class="card" id="hero_banner_settings_card" style="margin-top: 1.5rem;">
      <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3 class="card-title">Dashboard Hero Banner Slider</h3>
        <span class="badge" style="background: var(--rose-50); color: var(--primary-rose); border: 1px solid var(--rose-200); font-weight: 700;">
          <?= count($allBanners) ?> Banners Total
        </span>
      </div>
      <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1.25rem;">
        Manage the image-based promotional slider banners displayed exclusively to authenticated users on their dashboard.
      </p>

      <!-- Existing Banners List -->
      <div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 2rem;" id="slider_banners_list">
        <?php if (empty($allBanners)): ?>
          <div style="padding: 1.5rem; text-align: center; color: var(--text-muted); background: var(--bg-subtle); border-radius: var(--radius-sm); border: 1px dashed var(--rose-200);">
            No slider banners added yet. Upload your first banner below.
          </div>
        <?php else: ?>
          <?php foreach ($allBanners as $b): ?>
            <div style="display: flex; gap: 1rem; align-items: center; padding: 0.875rem; border: 1px solid var(--rose-200); border-radius: var(--radius-md); background: #ffffff; flex-wrap: wrap;">
              <!-- Banner Thumbnail Preview -->
              <div style="width: 140px; height: 50px; border-radius: var(--radius-sm); overflow: hidden; background: var(--rose-50); flex-shrink: 0; border: 1px solid var(--rose-100); display: flex; align-items: center; justify-content: center;">
                <?php if (!empty($b['image_url'])): ?>
                  <img src="<?= e($b['image_url']) ?>" alt="Banner" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                  <span style="font-size: 0.75rem; color: var(--text-muted);">No image</span>
                <?php endif; ?>
              </div>

              <!-- Banner Info -->
              <div style="flex: 1; min-width: 180px;">
                <div style="font-weight: 700; font-size: 0.875rem; color: var(--text-main); margin-bottom: 0.2rem;">
                  <?= e($b['heading']) ?>
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted); word-break: break-all;">
                  Link: <span style="color: var(--primary-rose); font-family: monospace;"><?= e($b['cta_link'] ?: '/new-order') ?></span>
                  &bull; Order: <strong><?= (int)$b['sort_order'] ?></strong>
                </div>
              </div>

              <!-- Actions & Status -->
              <div style="display: flex; align-items: center; gap: 0.5rem; margin-left: auto;">
                <!-- Status Toggle -->
                <form method="POST" action="/admin/banners/toggle" style="display: inline; margin: 0;">
                  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                  <input type="hidden" name="banner_id" value="<?= (int)$b['id'] ?>">
                  <button type="submit" class="btn btn-sm" style="background: <?= $b['is_active'] ? 'var(--emerald-500, #10b981)' : '#94a3b8' ?>; color: #ffffff; padding: 0.35rem 0.65rem; font-size: 0.75rem; border: none;">
                    <?= $b['is_active'] ? 'Active' : 'Inactive' ?>
                  </button>
                </form>

                <!-- Delete Banner -->
                <form method="POST" action="/admin/banners/delete" style="display: inline; margin: 0;" onsubmit="return confirm('Are you sure you want to remove this banner from the slider?');">
                  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                  <input type="hidden" name="banner_id" value="<?= (int)$b['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm" style="padding: 0.35rem 0.65rem; font-size: 0.75rem;">
                    Delete
                  </button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Add / Upload New Banner Form -->
      <div style="border-top: 1px solid var(--rose-200); padding-top: 1.5rem;" id="add_slider_banner_box">
        <h4 style="font-size: 0.9375rem; font-weight: 700; margin-bottom: 0.75rem; color: var(--rose-950);">
          Upload New Slider Banner
        </h4>
        <form method="POST" action="/admin/banners/add" enctype="multipart/form-data" id="add_slider_banner_form">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

          <div class="form-group">
            <label class="form-label">Banner Title / Caption (for Accessibility & Alt)</label>
            <input type="text" name="heading" class="form-control" placeholder="e.g. 2026 Engagement Booster Pack" required>
          </div>

          <div class="form-group">
            <label class="form-label">Banner Image Upload (PNG, JPG, WEBP, SVG, max 5MB)</label>
            <input type="file" name="banner_file" class="form-control" accept="image/png,image/jpeg,image/webp,image/svg+xml,image/gif" style="padding: 0.4rem;">
            <small style="color: var(--text-muted); font-size: 0.75rem; display: block; margin-top: 0.25rem;">
              Recommended widescreen ratio: 1200 &times; 380 px or 16:9 for clean responsive display.
            </small>
          </div>

          <div class="form-group">
            <label class="form-label">Or External Image URL (Alternative)</label>
            <input type="url" name="image_url" class="form-control" placeholder="https://example.com/uploads/banners/my-banner.png">
          </div>

          <div class="grid grid-cols-2" style="gap: 0.75rem;">
            <div class="form-group">
              <label class="form-label">Target Click URL</label>
              <input type="text" name="cta_link" class="form-control" value="/new-order" required>
            </div>

            <div class="form-group">
              <label class="form-label">Sort Order</label>
              <input type="number" name="sort_order" class="form-control" value="<?= count($allBanners) + 1 ?>" min="1" required>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
              <input type="checkbox" name="is_active" value="1" checked style="width: auto;">
              <span>Activate this banner immediately in the user dashboard slider</span>
            </label>
          </div>

          <button type="submit" class="btn btn-primary" id="btn_upload_banner">
            Upload &amp; Add Banner
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>

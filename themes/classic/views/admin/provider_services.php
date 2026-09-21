<?php
$pageTitle = 'Import Services from Provider';
$currentPage = 'admin_import';
require_once __DIR__ . '/../../../../includes/smm_provider.php';
require __DIR__ . '/header.php';

$providers = DB::fetchAll("SELECT * FROM providers WHERE status = 'active' ORDER BY name ASC");
$categories = DB::fetchAll("SELECT * FROM categories ORDER BY sort_order ASC, name ASC");

$providerId = (int)($_GET['provider_id'] ?? ($providers[0]['id'] ?? 0));
$providerObj = $providerId > 0 ? SMMProvider::find($providerId) : null;

$providerServices = [];
$fetchError = null;

if ($providerObj) {
    $res = $providerObj->getServices();
    if ($res['success']) {
        $providerServices = $res['services'];
    } else {
        $fetchError = $res['error'];
    }
}
?>

<div class="card" style="margin-bottom: 1.5rem;">
  <div class="card-header">
    <div>
      <h3 class="card-title">Select SMM Provider & Import Settings</h3>
      <p class="card-subtitle">Retrieve live catalog from external provider API & apply automatic profit margin</p>
    </div>
  </div>

  <form method="GET" action="/admin/provider-services" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
    <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 220px;">
      <label class="form-label">Active Provider</label>
      <select name="provider_id" class="form-control" onchange="this.form.submit()">
        <?php foreach ($providers as $p): ?>
          <option value="<?= $p['id'] ?>" <?= $providerId === (int)$p['id'] ? 'selected' : '' ?>>
            <?= e($p['name']) ?> (<?= e($p['api_url']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <button type="submit" class="btn btn-secondary btn-sm" style="height: 42px;">
      &#8635; Fetch Live Services
    </button>
  </form>
</div>

<?php if ($fetchError): ?>
  <div class="alert alert-error">
    <div>
      <strong>Provider API Connection Notice:</strong><br>
      <?= e($fetchError) ?>
      <p style="font-size: 0.8125rem; margin-top: 0.5rem;">
        Please verify the provider's API URL and API Key in <a href="/admin/providers" style="text-decoration: underline;">Providers Settings</a>.
      </p>
    </div>
  </div>
<?php elseif (empty($providerServices)): ?>
  <div class="empty-state">
    <div class="empty-title">No Services Returned</div>
    <p class="empty-desc">The selected provider returned zero services or is not yet configured.</p>
  </div>
<?php else: ?>
  <div class="card">
    <div class="card-header">
      <div>
        <h3 class="card-title">Live Provider Services (<?= count($providerServices) ?> Available)</h3>
        <p class="card-subtitle">Choose a service to import with instant profit margin</p>
      </div>
    </div>

    <div class="card-list" style="margin-top: 1rem;">
      <?php foreach (array_slice($providerServices, 0, 40) as $ps): ?>
        <?php 
          $provRate = (float)($ps['rate'] ?? 0);
          $defaultMargin = 30.0;
          $marginPrice = round($provRate * (1 + ($defaultMargin / 100)), 4);
        ?>
        <div class="list-item-card" id="prov_srv_<?= e($ps['service'] ?? '') ?>">
          <div class="item-card-row">
            <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
              <span style="font-weight: 800;">ID: #<?= e($ps['service'] ?? '') ?></span>
              <span class="badge badge-default"><?= e($ps['category'] ?? 'General') ?></span>
            </div>

            <div style="font-size: 1.0625rem; font-weight: 800; color: var(--primary-rose);">
              Cost: $<?= number_format($provRate, 4) ?> / 1K
            </div>
          </div>

          <h4 style="font-size: 1rem; margin: 0.25rem 0;"><?= e($ps['name'] ?? 'Unnamed Service') ?></h4>

          <div class="item-meta-grid">
            <div class="meta-box">
              <span class="meta-label">Min</span>
              <span class="meta-val"><?= number_format($ps['min'] ?? 10) ?></span>
            </div>
            <div class="meta-box">
              <span class="meta-label">Max</span>
              <span class="meta-val"><?= number_format($ps['max'] ?? 10000) ?></span>
            </div>
            <div class="meta-box">
              <span class="meta-label">Type</span>
              <span class="meta-val"><?= e($ps['type'] ?? 'Default') ?></span>
            </div>
          </div>

          <!-- Import Form per card -->
          <form method="POST" action="/admin/provider-services/import" class="item-card-row" style="margin-top: 0.5rem; padding-top: 0.75rem; border-top: 1px solid var(--rose-100);">
            <?= csrf_field() ?>
            <input type="hidden" name="provider_id" value="<?= $providerId ?>">
            <input type="hidden" name="provider_service_id" value="<?= e($ps['service'] ?? '') ?>">
            <input type="hidden" name="name" value="<?= e($ps['name'] ?? '') ?>">
            <input type="hidden" name="original_rate" value="<?= $provRate ?>">
            <input type="hidden" name="min_quantity" value="<?= (int)($ps['min'] ?? 10) ?>">
            <input type="hidden" name="max_quantity" value="<?= (int)($ps['max'] ?? 10000) ?>">
            <input type="hidden" name="type" value="<?= e($ps['type'] ?? 'Default') ?>">

            <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
              <select name="category_id" class="form-control" style="width: auto; padding: 0.35rem 0.5rem; font-size: 0.8125rem;" required>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                <?php endforeach; ?>
              </select>

              <div style="display: flex; align-items: center; gap: 0.25rem;">
                <label style="font-size: 0.8125rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0;">Margin %:</label>
                <input type="number" name="margin_percent" value="30" min="0" step="5" class="form-control" style="width: 75px; padding: 0.35rem 0.5rem; font-size: 0.8125rem;">
              </div>
            </div>

            <button type="submit" class="btn btn-primary btn-sm">
              Import to Catalog
            </button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>

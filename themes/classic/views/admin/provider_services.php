<?php
$pageTitle = 'Import Services from Provider';
$currentPage = 'admin_import';
require_once __DIR__ . '/../../../../includes/smm_provider.php';
require __DIR__ . '/header.php';

$providers = DB::fetchAll("SELECT * FROM providers WHERE status = 'active' ORDER BY name ASC");
$categories = DB::fetchAll("SELECT * FROM categories ORDER BY sort_order ASC, name ASC");

$providerId = (int)($_GET['provider_id'] ?? ($providers[0]['id'] ?? 0));
$providerObj = $providerId > 0 ? SMMProvider::find($providerId) : null;
$selectedProvider = $providerId > 0 ? DB::fetch("SELECT * FROM providers WHERE id = ?", [$providerId]) : null;

$providerServices = [];
$providerCategories = [];
$fetchError = null;
$importedMap = [];

if ($providerObj && $selectedProvider) {
    // Map existing imported services for duplicate detection
    $importedRows = DB::fetchAll("SELECT provider_service_id, id, rate, original_rate, name FROM services WHERE provider_id = ?", [$providerId]);
    foreach ($importedRows as $r) {
        $importedMap[(string)$r['provider_service_id']] = $r;
    }

    try {
        $res = $providerObj->getServices();
        if ($res['success'] && is_array($res['services'])) {
            $providerServices = $res['services'];
            foreach ($providerServices as $ps) {
                if (!empty($ps['category']) && !in_array($ps['category'], $providerCategories)) {
                    $providerCategories[] = $ps['category'];
                }
            }
            sort($providerCategories);
        } else {
            $fetchError = $res['error'] ?? 'Provider API returned an unsuccessful response.';
        }
    } catch (\Throwable $e) {
        error_log("Provider API Service Fetch Exception: " . $e->getMessage());
        $fetchError = 'Exception fetching services: ' . $e->getMessage();
    }
}
?>

<!-- Provider Selection & Live Diagnostics Header -->
<div class="card" style="margin-bottom: 1.5rem;" id="admin_import_header_card">
  <div class="card-header">
    <div>
      <h3 class="card-title">Provider API Service Importer</h3>
      <p class="card-subtitle">Connect live to your external SMM provider API, configure profit margins, and sync catalog</p>
    </div>
  </div>

  <div style="display: flex; gap: 1.25rem; flex-wrap: wrap; align-items: flex-end;">
    <form method="GET" action="/admin/provider-services" style="display: flex; gap: 0.75rem; flex: 1; min-width: 280px; align-items: flex-end;">
      <div class="form-group" style="margin-bottom: 0; flex: 1;">
        <label class="form-label">Select Active Provider</label>
        <select name="provider_id" class="form-control" onchange="this.form.submit()">
          <?php if (empty($providers)): ?>
            <option value="0">-- No Active Providers Found --</option>
          <?php else: ?>
            <?php foreach ($providers as $p): ?>
              <option value="<?= $p['id'] ?>" <?= $providerId === (int)$p['id'] ? 'selected' : '' ?>>
                <?= e($p['name']) ?> (<?= e($p['currency']) ?> - <?= e($p['api_url']) ?>)
              </option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
      </div>

      <button type="submit" class="btn btn-secondary btn-sm" style="height: 40px; white-space: nowrap;">
        &#8635; Refresh Live API
      </button>
    </form>

    <?php if ($selectedProvider): ?>
      <div style="background: var(--bg-subtle); border: 1px solid var(--rose-100); border-radius: var(--radius-sm); padding: 0.5rem 1rem; display: flex; align-items: center; gap: 1.5rem;">
        <div>
          <span style="font-size: 0.75rem; color: var(--text-muted); display: block;">Wholesale Currency</span>
          <strong style="color: var(--primary-rose);"><?= e($selectedProvider['currency']) ?></strong>
        </div>
        <div>
          <span style="font-size: 0.75rem; color: var(--text-muted); display: block;">Catalog Services</span>
          <strong style="color: var(--text-main);"><?= count($importedMap) ?> Imported</strong>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php if (empty($providers)): ?>
  <div class="alert alert-warning">
    <div>
      <strong>No SMM Providers Configured:</strong><br>
      You must register at least one SMM provider with a valid API URL and Key before you can import services.
      <p style="margin-top: 0.5rem;"><a href="/admin/providers" class="btn btn-primary btn-sm">Add New Provider &rarr;</a></p>
    </div>
  </div>
<?php elseif ($fetchError): ?>
  <div class="alert alert-error" id="provider_api_error_banner">
    <div>
      <strong>Provider API Error:</strong><br>
      <?= e($fetchError) ?>
      <p style="font-size: 0.8125rem; margin-top: 0.5rem; line-height: 1.5;">
        Common fixes: Check if the provider's API URL and API Key are valid, verify your server's outbound cURL internet connection, or confirm with the provider if your IP is whitelisted.
      </p>
      <div style="margin-top: 0.75rem;">
        <a href="/admin/providers" class="btn btn-secondary btn-sm">Review Provider Credentials &rarr;</a>
      </div>
    </div>
  </div>
<?php elseif (empty($providerServices)): ?>
  <div class="empty-state">
    <div class="empty-title">Zero Services Returned</div>
    <p class="empty-desc">The provider API connected successfully but returned an empty list of services.</p>
  </div>
<?php else: ?>

  <!-- Bulk Import Action Banner -->
  <div class="card" style="margin-bottom: 1.5rem; background: linear-gradient(135deg, #fff 0%, #fff1f2 100%); border-color: var(--rose-200);">
    <div class="card-header">
      <div>
        <h4 class="card-title" style="color: var(--rose-900);">Fast Bulk Importer</h4>
        <p class="card-subtitle">Automatically import all or filtered services, auto-create categories, and apply profit margins</p>
      </div>
      <span class="badge badge-completed"><?= count($providerServices) ?> Live Services Available</span>
    </div>

    <form method="POST" action="/admin/provider-services/bulk-import" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;" onsubmit="return confirm('Bulk import will process all selected services with automatic duplicate checking. Proceed?');">
      <?= csrf_field() ?>
      <input type="hidden" name="provider_id" value="<?= $providerId ?>">

      <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
        <label class="form-label" style="font-size: 0.8125rem;">Provider Category Filter</label>
        <select name="filter_category" class="form-control" style="font-size: 0.875rem;">
          <option value="all">All Categories (Entire Catalog - <?= count($providerServices) ?> Services)</option>
          <?php foreach ($providerCategories as $pc): ?>
            <option value="<?= e($pc) ?>"><?= e($pc) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group" style="margin-bottom: 0; width: 140px;">
        <label class="form-label" style="font-size: 0.8125rem;">Profit Margin %</label>
        <input type="number" name="margin_percent" value="30" min="0" step="5" class="form-control" required style="font-size: 0.875rem; font-weight: 700;">
      </div>

      <button type="submit" class="btn btn-primary" style="height: 42px; font-weight: 700;">
        &darr; Bulk Import to Catalog
      </button>
    </form>
  </div>

  <!-- Individual Service Cards -->
  <div class="card">
    <div class="card-header">
      <div>
        <h3 class="card-title">Live Provider Services (Showing <?= count($providerServices) ?>)</h3>
        <p class="card-subtitle">Review individual services, customize category mapping or profit margin, and import</p>
      </div>
    </div>

    <div class="card-list" style="margin-top: 1rem;">
      <?php foreach ($providerServices as $ps): ?>
        <?php 
          $psId = (string)($ps['service'] ?? '');
          $provRate = (float)($ps['rate'] ?? 0);
          $isAlreadyImported = isset($importedMap[$psId]);
          $existingItem = $isAlreadyImported ? $importedMap[$psId] : null;
        ?>
        <div class="list-item-card" id="prov_srv_card_<?= e($psId) ?>" style="<?= $isAlreadyImported ? 'border-left: 4px solid #16a34a;' : '' ?>">
          <div class="item-card-row">
            <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
              <span style="font-weight: 800;">Provider ID: #<?= e($psId) ?></span>
              <span class="badge badge-default"><?= e($ps['category'] ?? 'General') ?></span>
              <?php if ($isAlreadyImported): ?>
                <span class="badge badge-completed">In Catalog (ID #<?= $existingItem['id'] ?>)</span>
              <?php else: ?>
                <span class="badge badge-pending">New</span>
              <?php endif; ?>
            </div>

            <div style="font-size: 1.0625rem; font-weight: 800; color: var(--primary-rose);">
              Provider Wholesale: <?= e($selectedProvider['currency']) ?> <?= number_format($provRate, 4) ?> / 1K
            </div>
          </div>

          <h4 style="font-size: 1rem; margin: 0.35rem 0;"><?= e($ps['name'] ?? 'Unnamed Service') ?></h4>

          <div class="item-meta-grid">
            <div class="meta-box">
              <span class="meta-label">Min Quantity</span>
              <span class="meta-val"><?= number_format($ps['min'] ?? 10) ?></span>
            </div>
            <div class="meta-box">
              <span class="meta-label">Max Quantity</span>
              <span class="meta-val"><?= number_format($ps['max'] ?? 10000) ?></span>
            </div>
            <div class="meta-box">
              <span class="meta-label">Service Type</span>
              <span class="meta-val"><?= e($ps['type'] ?? 'Default') ?></span>
            </div>
            <?php if ($isAlreadyImported): ?>
              <div class="meta-box">
                <span class="meta-label">Current Selling Rate</span>
                <span class="meta-val" style="color: #166534; font-weight: 700;">₹<?= number_format((float)$existingItem['rate'], 2) ?></span>
              </div>
            <?php endif; ?>
          </div>

          <!-- Import Form per card -->
          <form method="POST" action="/admin/provider-services/import" class="item-card-row" style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid var(--rose-100); flex-wrap: wrap; gap: 0.75rem;">
            <?= csrf_field() ?>
            <input type="hidden" name="provider_id" value="<?= $providerId ?>">
            <input type="hidden" name="provider_service_id" value="<?= e($psId) ?>">
            <input type="hidden" name="name" value="<?= e($ps['name'] ?? '') ?>">
            <input type="hidden" name="category_name" value="<?= e($ps['category'] ?? 'General') ?>">
            <input type="hidden" name="original_rate" value="<?= $provRate ?>">
            <input type="hidden" name="min_quantity" value="<?= (int)($ps['min'] ?? 10) ?>">
            <input type="hidden" name="max_quantity" value="<?= (int)($ps['max'] ?? 10000) ?>">
            <input type="hidden" name="type" value="<?= e($ps['type'] ?? 'Default') ?>">
            <input type="hidden" name="description" value="<?= e($ps['desc'] ?? '') ?>">

            <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; flex: 1;">
              <div style="flex: 1; min-width: 180px;">
                <label style="font-size: 0.75rem; color: var(--text-muted); display: block; margin-bottom: 2px;">Assign Local Category</label>
                <select name="category_id" class="form-control" style="padding: 0.35rem 0.5rem; font-size: 0.8125rem;">
                  <option value="0">Auto-create: "<?= e($ps['category'] ?? 'General') ?>"</option>
                  <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div style="width: 100px;">
                <label style="font-size: 0.75rem; color: var(--text-muted); display: block; margin-bottom: 2px;">Margin %</label>
                <input type="number" name="margin_percent" value="30" min="0" step="5" class="form-control" style="padding: 0.35rem 0.5rem; font-size: 0.8125rem; font-weight: 700;">
              </div>
            </div>

            <button type="submit" class="btn <?= $isAlreadyImported ? 'btn-secondary' : 'btn-primary' ?> btn-sm" style="align-self: flex-end; height: 36px; white-space: nowrap;">
              <?= $isAlreadyImported ? 'Update in Catalog' : 'Import to Catalog' ?>
            </button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>

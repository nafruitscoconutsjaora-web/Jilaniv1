<?php
$pageTitle = 'API Providers';
$currentPage = 'admin_providers';
require __DIR__ . '/header.php';

$providers = DB::fetchAll("SELECT * FROM providers ORDER BY id DESC");
?>

<div class="grid grid-cols-3" style="align-items: start;">
  <!-- Providers Card List (2 cols) -->
  <div class="card" style="grid-column: span 2;" id="providers_card">
    <div class="card-header">
      <div>
        <h3 class="card-title">Connected SMM Providers (<?= count($providers) ?>)</h3>
        <p class="card-subtitle">Manage automated API connections to external SMM panels</p>
      </div>
    </div>

    <?php if (empty($providers)): ?>
      <div class="empty-state">
        <div class="empty-title">No Providers Configured</div>
        <p class="empty-desc">Connect your first external SMM API provider using the form on the right.</p>
      </div>
    <?php else: ?>
      <div class="card-list">
        <?php foreach ($providers as $prov): ?>
          <div class="list-item-card" id="provider_card_<?= $prov['id'] ?>">
            <div class="item-card-row">
              <div style="display: flex; align-items: center; gap: 0.625rem; flex-wrap: wrap;">
                <span style="font-weight: 800; font-size: 1.0625rem;"><?= e($prov['name']) ?></span>
                <?= get_status_badge($prov['status']) ?>
              </div>

              <div style="font-size: 1.125rem; font-weight: 800; color: var(--primary-rose);">
                Balance: <?= e($prov['currency']) ?> <?= number_format($prov['balance'], 2) ?>
              </div>
            </div>

            <div style="font-family: monospace; font-size: 0.8125rem; color: var(--text-muted); word-break: break-all;">
              API URL: <?= e($prov['api_url']) ?>
            </div>

            <div class="item-meta-grid">
              <div class="meta-box">
                <span class="meta-label">Provider ID</span>
                <span class="meta-val">#<?= $prov['id'] ?></span>
              </div>
              <div class="meta-box">
                <span class="meta-label">Last Synced</span>
                <span class="meta-val"><?= format_date($prov['last_sync']) ?></span>
              </div>
              <div class="meta-box">
                <span class="meta-label">API Key</span>
                <span class="meta-val"><?= substr(e($prov['api_key']), 0, 8) ?>••••••••</span>
              </div>
            </div>

            <div class="item-card-row" style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid var(--rose-100);">
              <div style="display: flex; gap: 0.5rem; align-items: center;">
                <!-- Sync Balance via Real cURL -->
                <form method="POST" action="/admin/providers/sync">
                  <?= csrf_field() ?>
                  <input type="hidden" name="provider_id" value="<?= $prov['id'] ?>">
                  <button type="submit" class="btn btn-secondary btn-sm">
                    &#8635; Sync Balance
                  </button>
                </form>

                <a href="/admin/provider-services?provider_id=<?= $prov['id'] ?>" class="btn btn-primary btn-sm">
                  Import Services
                </a>
              </div>

              <form method="POST" action="/admin/providers/delete" onsubmit="return confirm('Delete this provider?')">
                <?= csrf_field() ?>
                <input type="hidden" name="provider_id" value="<?= $prov['id'] ?>">
                <button type="submit" class="btn btn-outline-rose btn-sm">Delete</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Add Provider Form (1 col) -->
  <div class="card" id="add_provider_card">
    <div class="card-header">
      <h4 class="card-title" style="font-size: 1.125rem;">Add New Provider</h4>
    </div>

    <form method="POST" action="/admin/providers/create" id="create_provider_form">
      <?= csrf_field() ?>

      <div class="form-group">
        <label for="prov_name" class="form-label">Provider Title</label>
        <input type="text" id="prov_name" name="name" class="form-control" required placeholder="e.g. Peak SMM API">
      </div>

      <div class="form-group">
        <label for="prov_url" class="form-label">API Endpoint URL</label>
        <input type="url" id="prov_url" name="api_url" class="form-control" required placeholder="https://provider.com/api/v2">
        <span class="form-text">Must support standard SMM API v2</span>
      </div>

      <div class="form-group">
        <label for="prov_key" class="form-label">API Key</label>
        <input type="password" id="prov_key" name="api_key" class="form-control" required placeholder="Secret API key">
      </div>

      <div class="form-group">
        <label for="prov_curr" class="form-label">Provider Base Currency</label>
        <input type="text" id="prov_curr" name="currency" class="form-control" value="USD" required>
      </div>

      <button type="submit" class="btn btn-primary btn-block">
        Connect Provider
      </button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>

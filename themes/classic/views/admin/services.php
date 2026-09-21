<?php
$pageTitle = 'Service Management';
$currentPage = 'admin_services';
require __DIR__ . '/header.php';

$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = trim($_GET['q'] ?? '');

$where = "WHERE 1=1";
$params = [];

if ($categoryId > 0) {
    $where .= " AND s.category_id = ?";
    $params[] = $categoryId;
}

if (!empty($search)) {
    $where .= " AND (s.name LIKE ? OR s.description LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$services = DB::fetchAll(
    "SELECT s.*, c.name as category_name, p.name as provider_name 
     FROM services s 
     JOIN categories c ON s.category_id = c.id 
     LEFT JOIN providers p ON s.provider_id = p.id 
     {$where} 
     ORDER BY c.sort_order ASC, s.id ASC",
    $params
);

$categories = DB::fetchAll("SELECT * FROM categories ORDER BY sort_order ASC, name ASC");
$providers = DB::fetchAll("SELECT * FROM providers WHERE status = 'active' ORDER BY name ASC");
?>

<div class="filter-bar">
  <div class="filter-chips">
    <a href="/admin/services" class="chip <?= $categoryId === 0 ? 'active' : '' ?>">All Categories</a>
    <?php foreach ($categories as $cat): ?>
      <a href="/admin/services?category=<?= $cat['id'] ?><?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" 
         class="chip <?= $categoryId === (int)$cat['id'] ? 'active' : '' ?>">
        <?= e($cat['name']) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
    <form method="GET" action="/admin/services" style="display: flex; gap: 0.5rem;">
      <?php if ($categoryId > 0): ?>
        <input type="hidden" name="category" value="<?= $categoryId ?>">
      <?php endif; ?>
      <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search services..." class="form-control" style="padding: 0.45rem 0.75rem;">
      <button type="submit" class="btn btn-secondary btn-sm">Search</button>
    </form>
    <a href="#create_service_card" class="btn btn-primary btn-sm">+ Add New Service</a>
  </div>
</div>

<div class="grid grid-cols-3" style="align-items: start; margin-top: 1.5rem;">
  <!-- Service Cards List (2 cols) -->
  <div class="card" style="grid-column: span 2;" id="services_card">
    <div class="card-header">
      <div>
        <h3 class="card-title">Configured Services (<?= count($services) ?>)</h3>
        <p class="card-subtitle">Manage customer-facing service catalog & API mappings</p>
      </div>
    </div>

    <?php if (empty($services)): ?>
      <div class="empty-state">
        <div class="empty-title">No Services Found</div>
        <p class="empty-desc">Create your first service using the form on the right.</p>
      </div>
    <?php else: ?>
      <div class="card-list">
        <?php foreach ($services as $srv): ?>
          <div class="list-item-card" id="service_card_<?= $srv['id'] ?>">
            <div class="item-card-row">
              <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                <span style="font-weight: 800;">#<?= $srv['id'] ?></span>
                <span class="badge badge-default"><?= e($srv['category_name']) ?></span>
                <?= get_status_badge($srv['status']) ?>
              </div>

              <div style="font-size: 1.125rem; font-weight: 800; color: var(--primary-rose);">
                $<?= number_format($srv['rate'], 4) ?> <span style="font-size: 0.75rem; color: var(--text-muted);">/ 1K USD</span>
              </div>
            </div>

            <h4 style="font-size: 1.0625rem; margin: 0.25rem 0;"><?= e($srv['name']) ?></h4>

            <?php if (!empty($srv['description'])): ?>
              <p style="font-size: 0.8125rem; color: var(--text-muted);"><?= nl2br(e($srv['description'])) ?></p>
            <?php endif; ?>

            <div class="item-meta-grid">
              <div class="meta-box">
                <span class="meta-label">Min / Max</span>
                <span class="meta-val"><?= number_format($srv['min_quantity']) ?> / <?= number_format($srv['max_quantity']) ?></span>
              </div>
              <div class="meta-box">
                <span class="meta-label">Provider</span>
                <span class="meta-val"><?= e($srv['provider_name'] ?? 'Manual / None') ?></span>
              </div>
              <div class="meta-box">
                <span class="meta-label">Provider Service ID</span>
                <span class="meta-val"><?= !empty($srv['provider_service_id']) ? '#' . e($srv['provider_service_id']) : '-' ?></span>
              </div>
            </div>

            <div class="item-card-row" style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid var(--rose-100);">
              <form method="POST" action="/admin/services/toggle">
                <?= csrf_field() ?>
                <input type="hidden" name="service_id" value="<?= $srv['id'] ?>">
                <button type="submit" class="btn btn-secondary btn-sm">
                  Set <?= $srv['status'] === 'active' ? 'Inactive' : 'Active' ?>
                </button>
              </form>

              <form method="POST" action="/admin/services/delete" onsubmit="return confirm('Delete this service?')">
                <?= csrf_field() ?>
                <input type="hidden" name="service_id" value="<?= $srv['id'] ?>">
                <button type="submit" class="btn btn-outline-rose btn-sm">Delete</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Create Service Form (1 col) -->
  <div class="card" id="create_service_card">
    <div class="card-header">
      <h4 class="card-title" style="font-size: 1.125rem;">Create New Service</h4>
    </div>

    <form method="POST" action="/admin/services/create" id="create_service_form">
      <?= csrf_field() ?>

      <div class="form-group">
        <label for="srv_name" class="form-label">Service Name</label>
        <input type="text" id="srv_name" name="name" class="form-control" required placeholder="Instagram Likes - High Speed">
      </div>

      <div class="form-group">
        <label for="srv_cat" class="form-label">Category</label>
        <select id="srv_cat" name="category_id" class="form-control" required>
          <?php foreach ($categories as $c): ?>
            <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="srv_rate" class="form-label">Rate per 1,000 (USD)</label>
        <input type="number" step="0.0001" min="0.0001" id="srv_rate" name="rate" class="form-control" required placeholder="0.5000">
      </div>

      <div class="grid grid-cols-2" style="gap: 0.75rem;">
        <div class="form-group">
          <label for="srv_min" class="form-label">Min Qty</label>
          <input type="number" min="1" id="srv_min" name="min_quantity" class="form-control" value="10" required>
        </div>
        <div class="form-group">
          <label for="srv_max" class="form-label">Max Qty</label>
          <input type="number" min="1" id="srv_max" name="max_quantity" class="form-control" value="100000" required>
        </div>
      </div>

      <div class="form-group">
        <label for="srv_provider" class="form-label">Link to SMM Provider (Optional)</label>
        <select id="srv_provider" name="provider_id" class="form-control">
          <option value="">-- None (Manual Order Handling) --</option>
          <?php foreach ($providers as $p): ?>
            <option value="<?= $p['id'] ?>"><?= e($p['name']) ?> (<?= e($p['api_url']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="srv_prov_id" class="form-label">Provider Service ID</label>
        <input type="text" id="srv_prov_id" name="provider_service_id" class="form-control" placeholder="e.g. 1254">
      </div>

      <div class="form-group">
        <label for="srv_desc" class="form-label">Description / Instructions</label>
        <textarea id="srv_desc" name="description" class="form-control" rows="3" placeholder="Speed, drop rate, refill guarantee..."></textarea>
      </div>

      <button type="submit" class="btn btn-primary btn-block">
        Save Service
      </button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>

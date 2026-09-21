<?php
$pageTitle = 'Services Directory';
$currentPage = 'services';
require __DIR__ . '/header.php';

$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = trim($_GET['q'] ?? '');

$query = "SELECT s.*, c.name as category_name 
          FROM services s 
          JOIN categories c ON s.category_id = c.id 
          WHERE s.status = 'active'";
$params = [];

if ($categoryId > 0) {
    $query .= " AND s.category_id = ?";
    $params[] = $categoryId;
}

if (!empty($search)) {
    $query .= " AND (s.name LIKE ? OR s.description LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$query .= " ORDER BY c.sort_order ASC, s.id ASC";
$services = DB::fetchAll($query, $params);
$categories = DB::fetchAll("SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order ASC, name ASC");
?>

<div class="filter-bar" id="user_services_filter_bar">
  <div class="filter-chips">
    <a href="/user/services" class="chip <?= $categoryId === 0 ? 'active' : '' ?>">All Categories</a>
    <?php foreach ($categories as $cat): ?>
      <a href="/user/services?category=<?= $cat['id'] ?><?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" 
         class="chip <?= $categoryId === (int)$cat['id'] ? 'active' : '' ?>">
        <?= e($cat['name']) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <form method="GET" action="/user/services" style="display: flex; gap: 0.5rem; flex: 1; max-width: 320px;">
    <?php if ($categoryId > 0): ?>
      <input type="hidden" name="category" value="<?= $categoryId ?>">
    <?php endif; ?>
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search services..." class="form-control" style="padding: 0.45rem 0.75rem;">
    <button type="submit" class="btn btn-secondary btn-sm">Search</button>
  </form>
</div>

<?php if (empty($services)): ?>
  <div class="empty-state" id="empty_services_view">
    <div class="empty-title">No Services Found</div>
    <p class="empty-desc">No services match your filters. Please select a different category or search term.</p>
  </div>
<?php else: ?>
  <div class="card-list" id="user_services_list">
    <?php foreach ($services as $srv): ?>
      <div class="list-item-card" id="srv_card_<?= $srv['id'] ?>">
        <div class="item-card-row">
          <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
            <span class="badge badge-default"><?= e($srv['category_name']) ?></span>
            <span style="font-size: 0.8125rem; color: var(--text-muted);">ID: #<?= $srv['id'] ?></span>
          </div>

          <!-- Converted Price in User's Preferred Currency -->
          <div style="font-size: 1.125rem; font-weight: 800; color: var(--primary-rose);">
            <?= Currency::format((float)$srv['rate'], $userCurrency) ?> 
            <span style="font-size: 0.8125rem; font-weight: 500; color: var(--text-muted);">/ 1,000</span>
          </div>
        </div>

        <h3 style="font-size: 1.0625rem; margin: 0.25rem 0;"><?= e($srv['name']) ?></h3>

        <?php if (!empty($srv['description'])): ?>
          <p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.5;"><?= nl2br(e($srv['description'])) ?></p>
        <?php endif; ?>

        <div class="item-meta-grid">
          <div class="meta-box">
            <span class="meta-label">Min Qty</span>
            <span class="meta-val"><?= number_format($srv['min_quantity']) ?></span>
          </div>
          <div class="meta-box">
            <span class="meta-label">Max Qty</span>
            <span class="meta-val"><?= number_format($srv['max_quantity']) ?></span>
          </div>
          <div class="meta-box">
            <span class="meta-label">Base Rate (USD)</span>
            <span class="meta-val">$<?= number_format($srv['rate'], 4) ?></span>
          </div>
        </div>

        <div class="item-card-row" style="margin-top: 0.5rem;">
          <span></span>
          <a href="/new-order?category=<?= $srv['category_id'] ?>&service=<?= $srv['id'] ?>" class="btn btn-primary btn-sm">
            Order This Service &rarr;
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>

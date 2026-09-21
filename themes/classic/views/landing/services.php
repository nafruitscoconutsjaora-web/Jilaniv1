<?php
$pageTitle = 'Services Directory';
$currentPage = 'services';
require __DIR__ . '/header.php';

// Category filter
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

<div class="container" style="padding: 2.5rem 1.25rem;">
  <div style="margin-bottom: 2rem; text-align: center;">
    <h1 style="font-size: 2.25rem; margin-bottom: 0.5rem;">Services Catalog</h1>
    <p style="color: var(--text-muted); max-width: 600px; margin: 0 auto;">Browse all real, verified social marketing packages currently available in our system.</p>
  </div>

  <div class="filter-bar" id="services_filter_bar">
    <div class="filter-chips" id="category_chips">
      <a href="/services" class="chip <?= $categoryId === 0 ? 'active' : '' ?>">
        <?= SMMIcons::getCategoryIcon('general', 'chip-icon') ?>
        <span>All Categories</span>
      </a>
      <?php foreach ($categories as $cat): ?>
        <a href="/services?category=<?= $cat['id'] ?><?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" 
           class="chip <?= $categoryId === (int)$cat['id'] ? 'active' : '' ?>">
          <?= SMMIcons::getCategoryIcon($cat['name'], 'chip-icon') ?>
          <span><?= e($cat['name']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>

    <form method="GET" action="/services" style="display: flex; gap: 0.5rem; flex: 1; max-width: 320px;">
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
      <p class="empty-desc">No services currently match your search criteria. Please adjust your filters.</p>
      <a href="/services" class="btn btn-secondary btn-sm">Reset Filter</a>
    </div>
  <?php else: ?>
    <div class="card-list" id="services_card_list">
      <?php foreach ($services as $srv): ?>
        <div class="list-item-card" id="service_card_<?= $srv['id'] ?>">
          <div class="item-card-row">
            <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
              <span class="badge badge-default badge-with-icon">
                <?= SMMIcons::getCategoryIcon($srv['category_name'], 'badge-icon') ?>
                <span><?= e($srv['category_name']) ?></span>
              </span>
              <span style="font-size: 0.8125rem; color: var(--text-muted);">ID: #<?= $srv['id'] ?></span>
            </div>
            <div style="font-size: 1.125rem; font-weight: 800; color: var(--primary-rose);">
              $<?= number_format($srv['rate'], 4) ?> <span style="font-size: 0.8125rem; font-weight: 500; color: var(--text-muted);">per 1K</span>
            </div>
          </div>

          <h3 style="font-size: 1.125rem; margin: 0.25rem 0;"><?= e($srv['name']) ?></h3>
          
          <?php if (!empty($srv['description'])): ?>
            <p style="font-size: 0.875rem; color: var(--text-muted);"><?= nl2br(e($srv['description'])) ?></p>
          <?php endif; ?>

          <div class="item-meta-grid">
            <div class="meta-box">
              <span class="meta-label">Minimum</span>
              <span class="meta-val"><?= number_format($srv['min_quantity']) ?></span>
            </div>
            <div class="meta-box">
              <span class="meta-label">Maximum</span>
              <span class="meta-val"><?= number_format($srv['max_quantity']) ?></span>
            </div>
            <div class="meta-box">
              <span class="meta-label">Type</span>
              <span class="meta-val"><?= e($srv['type']) ?></span>
            </div>
          </div>

          <div class="item-card-row" style="margin-top: 0.5rem;">
            <span style="font-size: 0.8125rem; color: var(--text-muted);">Sign in to purchase in your selected currency</span>
            <a href="/login" class="btn btn-primary btn-sm">Order Now</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/footer.php'; ?>

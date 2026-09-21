<?php
$pageTitle = 'Category Management';
$currentPage = 'admin_categories';
require __DIR__ . '/header.php';

$categories = DB::fetchAll(
    "SELECT c.*, (SELECT COUNT(*) FROM services WHERE category_id = c.id) as service_count 
     FROM categories c 
     ORDER BY c.sort_order ASC, c.id ASC"
);
?>

<div class="grid grid-cols-3" style="align-items: start;">
  <!-- Categories Card List (2 cols) -->
  <div class="card" style="grid-column: span 2;" id="categories_card">
    <div class="card-header">
      <div>
        <h3 class="card-title">Service Categories</h3>
        <p class="card-subtitle">Manage catalog classification and ordering</p>
      </div>
    </div>

    <?php if (empty($categories)): ?>
      <div class="empty-state">
        <div class="empty-title">No Categories Defined</div>
        <p class="empty-desc">Create your first category using the form on the right.</p>
      </div>
    <?php else: ?>
      <div class="card-list">
        <?php foreach ($categories as $cat): ?>
          <div class="list-item-card" id="category_card_<?= $cat['id'] ?>">
            <div class="item-card-row">
              <div style="display: flex; align-items: center; gap: 0.5rem;">
                <span class="badge badge-default">Sort: <?= $cat['sort_order'] ?></span>
                <h4 style="font-size: 1.0625rem; margin: 0;"><?= e($cat['name']) ?></h4>
                <?= get_status_badge($cat['status']) ?>
              </div>
              <span style="font-size: 0.8125rem; color: var(--text-muted);"><?= $cat['service_count'] ?> active services</span>
            </div>

            <div class="item-card-row" style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid var(--rose-100);">
              <form method="POST" action="/admin/categories/toggle">
                <?= csrf_field() ?>
                <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                <button type="submit" class="btn btn-secondary btn-sm">
                  Set <?= $cat['status'] === 'active' ? 'Inactive' : 'Active' ?>
                </button>
              </form>

              <form method="POST" action="/admin/categories/delete" onsubmit="return confirm('Delete category? Only possible if no services are attached.')">
                <?= csrf_field() ?>
                <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                <button type="submit" class="btn btn-outline-rose btn-sm" <?= $cat['service_count'] > 0 ? 'disabled' : '' ?>>
                  Delete
                </button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Create Category Form (1 col) -->
  <div class="card" id="create_category_card">
    <div class="card-header">
      <h4 class="card-title" style="font-size: 1.125rem;">Add Category</h4>
    </div>

    <form method="POST" action="/admin/categories/create" id="create_category_form">
      <?= csrf_field() ?>

      <div class="form-group">
        <label for="cat_name" class="form-label">Category Name</label>
        <input type="text" id="cat_name" name="name" class="form-control" required placeholder="e.g. Instagram Followers">
      </div>

      <div class="form-group">
        <label for="cat_sort" class="form-label">Sort Order</label>
        <input type="number" id="cat_sort" name="sort_order" class="form-control" value="0" required>
        <span class="form-text">Lower numbers appear first in the catalog</span>
      </div>

      <div class="form-group">
        <label for="cat_status" class="form-label">Initial Status</label>
        <select id="cat_status" name="status" class="form-control">
          <option value="active" selected>Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>

      <button type="submit" class="btn btn-primary btn-block">
        Create Category
      </button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>

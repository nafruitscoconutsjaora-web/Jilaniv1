<?php
$pageTitle = 'Hero Banner Manager';
$currentPage = 'admin_banners';
require __DIR__ . '/header.php';

// Fetch all banners from MySQL
$banners = DB::fetchAll("SELECT * FROM hero_banners ORDER BY sort_order ASC, id DESC");
$editId = (int)($_GET['edit'] ?? 0);
$editBanner = $editId > 0 ? DB::fetch("SELECT * FROM hero_banners WHERE id = ?", [$editId]) : null;
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
  <div>
    <h2 class="card-title" style="font-size: 1.35rem; margin: 0;">Hero Banner Manager</h2>
    <p class="card-subtitle">Manage hero banners displayed on the user dashboard matching the RoseSMM design.</p>
  </div>
  <?php if ($editBanner): ?>
    <a href="/admin/banners" class="btn btn-secondary btn-sm">+ Create New Banner</a>
  <?php endif; ?>
</div>

<div class="grid grid-cols-3" style="align-items: start; gap: 1.5rem;">
  <!-- Form Card (1 col) -->
  <div class="card" id="banner_form_card">
    <div class="card-header">
      <h3 class="card-title" style="font-size: 1.125rem;">
        <?= $editBanner ? 'Edit Hero Banner #' . $editBanner['id'] : 'Create New Hero Banner' ?>
      </h3>
    </div>

    <form method="POST" action="<?= $editBanner ? '/admin/banners/update' : '/admin/banners/create' ?>" enctype="multipart/form-data" id="banner_mgmt_form">
      <?= csrf_field() ?>
      <?php if ($editBanner): ?>
        <input type="hidden" name="banner_id" value="<?= $editBanner['id'] ?>">
      <?php endif; ?>

      <div class="form-group">
        <label for="banner_heading" class="form-label">Heading <span style="color: var(--primary-rose);">*</span></label>
        <input type="text" id="banner_heading" name="heading" value="<?= e($editBanner['heading'] ?? 'Grow Your Social Media') ?>" class="form-control" required placeholder="e.g. Grow Your Social Media">
      </div>

      <div class="form-group">
        <label for="banner_subheading" class="form-label">Subheading / Bullet Highlights</label>
        <input type="text" id="banner_subheading" name="subheading" value="<?= e($editBanner['subheading'] ?? 'Fast • Secure • Reliable') ?>" class="form-control" placeholder="e.g. Fast • Secure • Reliable">
      </div>

      <div class="form-group">
        <label for="banner_description" class="form-label">Description <span style="color: var(--primary-rose);">*</span></label>
        <textarea id="banner_description" name="description" rows="3" class="form-control" required placeholder="Description text"><?= e($editBanner['description'] ?? 'Get real engagement and boost your online presence with our premium SMM services.') ?></textarea>
      </div>

      <div class="form-group">
        <label for="banner_cta_text" class="form-label">CTA Button Text <span style="color: var(--primary-rose);">*</span></label>
        <input type="text" id="banner_cta_text" name="cta_text" value="<?= e($editBanner['cta_text'] ?? 'Explore Services') ?>" class="form-control" required placeholder="e.g. Explore Services">
      </div>

      <div class="form-group">
        <label for="banner_cta_link" class="form-label">CTA Target Route <span style="color: var(--primary-rose);">*</span></label>
        <select id="banner_cta_link" name="cta_link" class="form-control" required>
          <?php
          $routes = [
            '/new-order' => 'Place New Order (/new-order)',
            '/user/services' => 'Services Directory (/user/services)',
            '/add-funds' => 'Add Funds / Wallet (/add-funds)',
            '/tickets' => 'Support Tickets (/tickets)',
            '/profile' => 'Referrals & Profile (/profile)',
            '/api-docs' => 'API Documentation (/api-docs)'
          ];
          $currentLink = $editBanner['cta_link'] ?? '/new-order';
          foreach ($routes as $path => $label):
          ?>
            <option value="<?= e($path) ?>" <?= $currentLink === $path ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="banner_image" class="form-label">Banner Image (Optional)</label>
        <?php if (!empty($editBanner['image_url'])): ?>
          <div style="margin-bottom: 0.5rem;">
            <img src="<?= e($editBanner['image_url']) ?>" alt="Current Banner" style="max-height: 80px; border-radius: var(--radius-sm); border: 1px solid var(--rose-200);">
          </div>
        <?php endif; ?>
        <input type="file" id="banner_image" name="banner_image" class="form-control" accept="image/png,image/jpeg,image/webp,image/jpg">
        <span class="form-text">Allowed formats: PNG, JPG, WebP. Max size: 4MB. If left empty, default reference 3D graphic is rendered.</span>
      </div>

      <div class="form-group">
        <label for="banner_sort" class="form-label">Sort Order</label>
        <input type="number" id="banner_sort" name="sort_order" value="<?= (int)($editBanner['sort_order'] ?? 1) ?>" class="form-control">
      </div>

      <div class="form-group">
        <label class="form-label">Status</label>
        <div style="display: flex; gap: 1rem; align-items: center;">
          <label style="display: flex; align-items: center; gap: 0.35rem; cursor: pointer;">
            <input type="radio" name="is_active" value="1" <?= ($editBanner['is_active'] ?? 1) ? 'checked' : '' ?>>
            Active (Display on Dashboard)
          </label>
          <label style="display: flex; align-items: center; gap: 0.35rem; cursor: pointer;">
            <input type="radio" name="is_active" value="0" <?= isset($editBanner['is_active']) && !$editBanner['is_active'] ? 'checked' : '' ?>>
            Inactive (Hidden)
          </label>
        </div>
      </div>

      <div style="display: flex; gap: 0.75rem; margin-top: 1rem;">
        <button type="submit" class="btn btn-primary btn-block">
          <?= $editBanner ? 'Update Banner' : 'Create Banner' ?>
        </button>
        <?php if ($editBanner): ?>
          <a href="/admin/banners" class="btn btn-secondary">Cancel</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Existing Banners List (2 cols) -->
  <div style="grid-column: span 2;" id="banners_list_col">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title" style="font-size: 1.125rem;">Configured Hero Banners</h3>
        <span class="badge" style="background: var(--rose-100); color: var(--rose-800); font-weight: 700;">
          <?= count($banners) ?> <?= count($banners) === 1 ? 'Banner' : 'Banners' ?>
        </span>
      </div>

      <?php if (empty($banners)): ?>
        <div class="empty-state">
          <div class="empty-title">No Banners Found</div>
          <p class="empty-desc">Create your first hero banner using the form on the left.</p>
        </div>
      <?php else: ?>
        <div class="card-list">
          <?php foreach ($banners as $b): ?>
            <div class="list-item-card" id="banner_item_<?= $b['id'] ?>" style="border-left: 4px solid <?= $b['is_active'] ? 'var(--primary-rose)' : 'var(--text-light)' ?>;">
              <div class="item-card-row">
                <div style="display: flex; align-items: center; gap: 0.625rem; flex-wrap: wrap;">
                  <span class="badge <?= $b['is_active'] ? 'badge-completed' : 'badge-canceled' ?>">
                    <?= $b['is_active'] ? 'Active' : 'Inactive' ?>
                  </span>
                  <span style="font-weight: 700; font-size: 1rem; color: var(--rose-900);">
                    <?= e($b['heading']) ?>
                  </span>
                  <?php if (!empty($b['subheading'])): ?>
                    <span style="font-size: 0.8125rem; color: var(--primary-rose); font-weight: 600;">
                      <?= e($b['subheading']) ?>
                    </span>
                  <?php endif; ?>
                </div>

                <div style="display: flex; gap: 0.5rem; align-items: center;">
                  <!-- Toggle Status -->
                  <form method="POST" action="/admin/banners/toggle" style="margin: 0;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="banner_id" value="<?= $b['id'] ?>">
                    <button type="submit" class="btn btn-secondary btn-sm" title="Toggle status">
                      <?= $b['is_active'] ? 'Disable' : 'Enable' ?>
                    </button>
                  </form>

                  <!-- Edit -->
                  <a href="/admin/banners?edit=<?= $b['id'] ?>" class="btn btn-outline-rose btn-sm">Edit</a>

                  <!-- Delete -->
                  <form method="POST" action="/admin/banners/delete" style="margin: 0;" onsubmit="return confirm('Are you sure you want to delete this hero banner?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="banner_id" value="<?= $b['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                  </form>
                </div>
              </div>

              <p style="font-size: 0.875rem; color: var(--text-muted); margin: 0.5rem 0;">
                <?= e($b['description']) ?>
              </p>

              <div class="item-meta-grid" style="margin-top: 0.5rem;">
                <div class="meta-box">
                  <span class="meta-label">Button Text</span>
                  <span class="meta-val"><?= e($b['cta_text']) ?></span>
                </div>
                <div class="meta-box">
                  <span class="meta-label">Destination Route</span>
                  <span class="meta-val"><code><?= e($b['cta_link']) ?></code></span>
                </div>
                <div class="meta-box">
                  <span class="meta-label">Order Priority</span>
                  <span class="meta-val">#<?= (int)$b['sort_order'] ?></span>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>

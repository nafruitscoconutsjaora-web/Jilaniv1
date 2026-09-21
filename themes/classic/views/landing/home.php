<?php
$pageTitle = 'Home';
$currentPage = 'home';
require __DIR__ . '/header.php';

// Fetch real categories and sample services from MySQL
$categories = DB::fetchAll("SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order ASC, name ASC LIMIT 6");
$totalServices = (int)(DB::fetch("SELECT COUNT(*) as cnt FROM services WHERE status = 'active'")['cnt'] ?? 0);
$sampleServices = DB::fetchAll(
    "SELECT s.*, c.name as category_name 
     FROM services s 
     JOIN categories c ON s.category_id = c.id 
     WHERE s.status = 'active' 
     ORDER BY s.id ASC LIMIT 6"
);
?>

<section class="landing-hero" id="hero_section">
  <div class="container">
    <h1 class="landing-hero-title">Elevate Your Social Presence with <span>Rose SMM</span></h1>
    <p class="landing-hero-subtitle">Fast, reliable, and automated social media growth infrastructure. Connect directly with top-tier API providers and manage orders with ease.</p>
    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
      <a href="/register" class="btn btn-primary btn-lg" id="hero_get_started_btn">Create Account</a>
      <a href="/services" class="btn btn-secondary btn-lg" id="hero_browse_services_btn">View Service Catalog</a>
    </div>
  </div>
</section>

<section style="padding: 3.5rem 0;" id="features_section">
  <div class="container">
    <div class="grid grid-cols-3">
      <div class="card" id="feature_card_fast">
        <div class="card-header">
          <h3 class="card-title">Automated API Dispatch</h3>
        </div>
        <p style="color: var(--text-muted); font-size: 0.9375rem;">Orders are processed programmatically through verified high-speed providers with instant tracking and updates.</p>
      </div>

      <div class="card" id="feature_card_secure">
        <div class="card-header">
          <h3 class="card-title">Bank-Grade Security</h3>
        </div>
        <p style="color: var(--text-muted); font-size: 0.9375rem;">Strict CSRF validation, prepared statements, and verified Razorpay payment verification safeguard every transaction.</p>
      </div>

      <div class="card" id="feature_card_support">
        <div class="card-header">
          <h3 class="card-title">Dedicated Support Tickets</h3>
        </div>
        <p style="color: var(--text-muted); font-size: 0.9375rem;">Direct 24/7 ticket management system connecting you directly with authorized administrators.</p>
      </div>
    </div>
  </div>
</section>

<section style="padding: 2rem 0 4rem;" id="catalog_preview_section">
  <div class="container">
    <div class="card" id="services_preview_card">
      <div class="card-header">
        <div>
          <h2 class="card-title">Available Services</h2>
          <p class="card-subtitle">Real live catalog directly from our verified inventory</p>
        </div>
        <a href="/services" class="btn btn-outline-rose btn-sm" id="view_all_services_btn">View All (<?= $totalServices ?>)</a>
      </div>

      <?php if (empty($sampleServices)): ?>
        <div class="empty-state" id="empty_services_state">
          <div class="empty-title">Catalog Currently Updating</div>
          <p class="empty-desc">Services are being loaded by the administrator. Please check back shortly or create an account.</p>
          <a href="/login" class="btn btn-secondary btn-sm">Administrator Sign In</a>
        </div>
      <?php else: ?>
        <div class="card-list" id="sample_services_list">
          <?php foreach ($sampleServices as $srv): ?>
            <div class="list-item-card" id="service_item_<?= $srv['id'] ?>">
              <div class="item-card-row">
                <span class="badge badge-default"><?= e($srv['category_name']) ?></span>
                <span class="meta-val" style="color: var(--primary-rose); font-size: 1.0625rem;">$<?= number_format($srv['rate'], 4) ?> / 1K</span>
              </div>
              <h4 style="font-size: 1rem; margin: 0.25rem 0;"><?= e($srv['name']) ?></h4>
              <div class="item-meta-grid">
                <div class="meta-box">
                  <span class="meta-label">Min Qty</span>
                  <span class="meta-val"><?= number_format($srv['min_quantity']) ?></span>
                </div>
                <div class="meta-box">
                  <span class="meta-label">Max Qty</span>
                  <span class="meta-val"><?= number_format($srv['max_quantity']) ?></span>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require __DIR__ . '/footer.php'; ?>

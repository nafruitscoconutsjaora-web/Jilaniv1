<?php
$pageTitle = 'New Order';
$currentPage = 'new_order';
require __DIR__ . '/header.php';

// Fetch active categories and services
$categories = DB::fetchAll("SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order ASC, name ASC");
$services = DB::fetchAll(
    "SELECT id, category_id, name, type, rate, min_quantity, max_quantity, description 
     FROM services 
     WHERE status = 'active' 
     ORDER BY id ASC"
);

$selectedServiceId = (int)($_GET['service'] ?? 0);
if ($selectedServiceId > 0) {
    foreach ($services as $srvItem) {
        if ((int)$srvItem['id'] === $selectedServiceId) {
            $selectedCategory = (int)$srvItem['category_id'];
            break;
        }
    }
}
?>

<div class="grid grid-cols-3" style="align-items: start;">
  <!-- Order Placement Form (2 columns wide on desktop) -->
  <div class="card" style="grid-column: span 2;" id="new_order_card">
    <div class="card-header">
      <div>
        <h3 class="card-title">Place a New Order</h3>
        <p class="card-subtitle">Real-time instant price calculation in Indian Rupees (INR - ₹)</p>
      </div>
      <div class="badge badge-completed">Instant Delivery</div>
    </div>

    <?php if (empty($services)): ?>
      <div class="empty-state" id="empty_services_order_state">
        <div class="empty-title">No Services Available</div>
        <p class="empty-desc">There are currently no active services configured in the system. Please check back later.</p>
      </div>
    <?php else: ?>
      <form method="POST" action="/order/create" id="new_order_form">
        <?= csrf_field() ?>

        <div class="form-group">
          <label for="order_category" class="form-label">Select Category</label>
          <select id="order_category" name="category_id" class="form-control" required>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= $selectedCategory === (int)$cat['id'] ? 'selected' : '' ?>>
                <?= e($cat['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="order_service" class="form-label">Select Service</label>
          <select id="order_service" name="service_id" class="form-control" required>
            <option value="">-- Choose a Service --</option>
          </select>
        </div>

        <div class="form-group">
          <label for="order_link" class="form-label">Target Link / URL</label>
          <input type="url" id="order_link" name="link" class="form-control" required placeholder="https://instagram.com/p/xxx or channel URL">
          <span class="form-text">Ensure the target account is set to public.</span>
        </div>

        <div class="form-group">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.375rem;">
            <label for="order_quantity" class="form-label" style="margin-bottom: 0;">Quantity</label>
            <span id="service_min_max" style="font-size: 0.8125rem; font-weight: 600; color: var(--text-muted);">Min: - | Max: -</span>
          </div>
          <input type="number" id="order_quantity" name="quantity" class="form-control" required placeholder="Enter quantity">
        </div>

        <!-- Dynamic Calculation Box -->
        <div style="background: var(--bg-subtle); border: 1px solid var(--rose-200); border-radius: var(--radius-sm); padding: 1.25rem; margin-bottom: 1.5rem;">
          <div class="item-card-row">
            <span style="font-weight: 600; color: var(--text-main);">Total Charge:</span>
            <span id="order_total_price" style="font-size: 1.5rem; font-weight: 800; color: var(--primary-rose);">
              ₹0.00
            </span>
          </div>
          <div style="font-size: 0.8125rem; color: var(--text-muted); margin-top: 0.25rem;">
            Direct instant deduction from your INR wallet balance
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg" id="submit_order_btn">
          Confirm and Submit Order
        </button>
      </form>
    <?php endif; ?>
  </div>

  <!-- Service Details & Guidance Card (1 column wide) -->
  <div style="display: flex; flex-direction: column; gap: 1.25rem;">
    <div class="card" id="service_details_card">
      <div class="card-header">
        <h4 class="card-title" style="font-size: 1.125rem;">Service Information</h4>
      </div>
      <div class="card-body">
        <div id="service_description" style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.6; white-space: pre-line;">
          Select a category and service from the list to view specific execution details, start times, and instructions.
        </div>
      </div>
    </div>

    <div class="card" id="balance_status_card">
      <div class="card-header">
        <h4 class="card-title" style="font-size: 1.125rem;">Wallet Status</h4>
      </div>
      <div class="card-body">
        <div class="item-card-row" style="margin-bottom: 0.5rem;">
          <span style="font-size: 0.875rem; color: var(--text-muted);">Available:</span>
          <span style="font-weight: 800; color: var(--primary-rose);">₹<?= number_format((float)$user['balance'], 2) ?></span>
        </div>
        <p style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1rem;">
          Funds are deducted in real-time in INR.
        </p>
        <a href="/add-funds" class="btn btn-secondary btn-sm btn-block">+ Add Funds to Wallet</a>
      </div>
    </div>
  </div>
</div>

<script>
  window.smmServices = <?= json_encode($services, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  window.userCurrencySymbol = '₹';
  window.userCurrencyRate = 1.0;
  window.preselectedServiceId = <?= $selectedServiceId ?>;
</script>

<?php require __DIR__ . '/footer.php'; ?>

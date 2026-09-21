<?php
$pageTitle = 'API Documentation';
$currentPage = 'api_docs';
require __DIR__ . '/header.php';

$apiUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/api';
?>

<div style="max-width: 900px; margin: 0 auto;">
  <div class="card" style="margin-bottom: 1.5rem;" id="api_overview_card">
    <div class="card-header">
      <h3 class="card-title">Standard SMM API (v2)</h3>
    </div>
    <p style="font-size: 0.9375rem; color: var(--text-muted); margin-bottom: 1rem;">
      Integrate your own applications, scripts, or bot networks directly with our panel using standard HTTP POST requests.
    </p>

    <div class="item-meta-grid" style="margin-bottom: 1rem;">
      <div class="meta-box">
        <span class="meta-label">HTTP Method</span>
        <span class="meta-val">POST</span>
      </div>
      <div class="meta-box">
        <span class="meta-label">API Endpoint URL</span>
        <span class="meta-val" style="font-family: monospace; font-size: 0.8125rem;"><?= e($apiUrl) ?></span>
      </div>
      <div class="meta-box">
        <span class="meta-label">Response Format</span>
        <span class="meta-val">JSON</span>
      </div>
    </div>
  </div>

  <!-- Action: Service List -->
  <div class="card" style="margin-bottom: 1.5rem;" id="api_action_services">
    <div class="card-header">
      <h4 class="card-title" style="font-size: 1.125rem;">1. Service List</h4>
    </div>
    <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 0.75rem;">
      Retrieve the complete list of active services, rates, and limits.
    </p>
    <div style="background: var(--bg-subtle); padding: 0.875rem; border-radius: var(--radius-sm); font-family: monospace; font-size: 0.8125rem; margin-bottom: 0.75rem;">
      POST Parameters:<br>
      key: "<?= e($user['api_key'] ?? 'YOUR_API_KEY') ?>"<br>
      action: "services"
    </div>
  </div>

  <!-- Action: Add Order -->
  <div class="card" style="margin-bottom: 1.5rem;" id="api_action_add">
    <div class="card-header">
      <h4 class="card-title" style="font-size: 1.125rem;">2. Add Order</h4>
    </div>
    <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 0.75rem;">
      Submit a new automated order. Balance will be deducted immediately.
    </p>
    <div style="background: var(--bg-subtle); padding: 0.875rem; border-radius: var(--radius-sm); font-family: monospace; font-size: 0.8125rem; margin-bottom: 0.75rem;">
      POST Parameters:<br>
      key: "<?= e($user['api_key'] ?? 'YOUR_API_KEY') ?>"<br>
      action: "add"<br>
      service: 1<br>
      link: "https://example.com/target"<br>
      quantity: 1000
    </div>
  </div>

  <!-- Action: Order Status -->
  <div class="card" style="margin-bottom: 1.5rem;" id="api_action_status">
    <div class="card-header">
      <h4 class="card-title" style="font-size: 1.125rem;">3. Order Status</h4>
    </div>
    <div style="background: var(--bg-subtle); padding: 0.875rem; border-radius: var(--radius-sm); font-family: monospace; font-size: 0.8125rem; margin-bottom: 0.75rem;">
      POST Parameters:<br>
      key: "<?= e($user['api_key'] ?? 'YOUR_API_KEY') ?>"<br>
      action: "status"<br>
      order: 1234
    </div>
  </div>

  <!-- Action: User Balance -->
  <div class="card" id="api_action_balance">
    <div class="card-header">
      <h4 class="card-title" style="font-size: 1.125rem;">4. User Balance</h4>
    </div>
    <div style="background: var(--bg-subtle); padding: 0.875rem; border-radius: var(--radius-sm); font-family: monospace; font-size: 0.8125rem;">
      POST Parameters:<br>
      key: "<?= e($user['api_key'] ?? 'YOUR_API_KEY') ?>"<br>
      action: "balance"
    </div>
  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>

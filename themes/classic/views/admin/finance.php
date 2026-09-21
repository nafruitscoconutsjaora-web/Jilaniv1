<?php
$pageTitle = 'Finance & Reports';
$currentPage = 'admin_finance';
require __DIR__ . '/header.php';

// Real financial metrics from MySQL
$totalGrossEarnings = (float)(DB::fetch("SELECT SUM(charge) as s FROM orders WHERE status != 'canceled'")['s'] ?? 0.0);
$totalDeposits = (float)(DB::fetch("SELECT SUM(amount) as s FROM payments WHERE status = 'completed'")['s'] ?? 0.0);
$totalRefunds = (float)(DB::fetch("SELECT SUM(amount) as s FROM transactions WHERE type = 'refund'")['s'] ?? 0.0);
$totalUserBalances = (float)(DB::fetch("SELECT SUM(balance) as s FROM users WHERE role = 'user'")['s'] ?? 0.0);

// Today vs This Month
$todayVolume = (float)(DB::fetch("SELECT SUM(charge) as s FROM orders WHERE status != 'canceled' AND DATE(created_at) = CURDATE()")['s'] ?? 0.0);
$monthVolume = (float)(DB::fetch("SELECT SUM(charge) as s FROM orders WHERE status != 'canceled' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")['s'] ?? 0.0);
$todayOrders = (int)(DB::fetch("SELECT COUNT(*) as cnt FROM orders WHERE DATE(created_at) = CURDATE()")['cnt'] ?? 0);
$monthOrders = (int)(DB::fetch("SELECT COUNT(*) as cnt FROM orders WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")['cnt'] ?? 0);
?>

<div class="grid grid-cols-4" style="margin-bottom: 1.5rem;">
  <div class="stat-card">
    <div class="stat-icon">&#128181;</div>
    <div class="stat-info">
      <div class="stat-label">Total Verified Deposits</div>
      <div class="stat-value" style="color: #166534;">$<?= number_format($totalDeposits, 2) ?></div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon">&#128200;</div>
    <div class="stat-info">
      <div class="stat-label">Gross Order Volume</div>
      <div class="stat-value">$<?= number_format($totalGrossEarnings, 2) ?></div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon">&#128179;</div>
    <div class="stat-info">
      <div class="stat-label">Outstanding Balances</div>
      <div class="stat-value">$<?= number_format($totalUserBalances, 2) ?></div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon">&#9194;</div>
    <div class="stat-info">
      <div class="stat-label">Total Refunds Issued</div>
      <div class="stat-value" style="color: var(--primary-rose);">$<?= number_format($totalRefunds, 2) ?></div>
    </div>
  </div>
</div>

<div class="grid grid-cols-2" style="margin-bottom: 1.5rem;">
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Today's Performance</h3>
    </div>
    <div class="item-meta-grid" style="margin: 0;">
      <div class="meta-box">
        <span class="meta-label">Orders Placed</span>
        <span class="meta-val"><?= number_format($todayOrders) ?></span>
      </div>
      <div class="meta-box">
        <span class="meta-label">Order Volume</span>
        <span class="meta-val">$<?= number_format($todayVolume, 2) ?></span>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h3 class="card-title">This Month's Performance</h3>
    </div>
    <div class="item-meta-grid" style="margin: 0;">
      <div class="meta-box">
        <span class="meta-label">Orders Placed</span>
        <span class="meta-val"><?= number_format($monthOrders) ?></span>
      </div>
      <div class="meta-box">
        <span class="meta-label">Order Volume</span>
        <span class="meta-val">$<?= number_format($monthVolume, 2) ?></span>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>

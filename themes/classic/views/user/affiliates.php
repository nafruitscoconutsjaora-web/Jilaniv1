<?php
$pageTitle = 'Affiliate & Referral Program';
$currentPage = 'affiliates';
require __DIR__ . '/header.php';

$userId = (int)$user['id'];
$commissionRate = (float)get_setting('referral_commission', '5'); // 5% default
$siteUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$referralLink = $siteUrl . '/register?ref=' . urlencode($user['username']);
?>

<div class="card" style="margin-bottom: 1.5rem; background: linear-gradient(135deg, #fff 0%, #fff1f2 100%); border-color: var(--rose-200);" id="affiliate_hero_card">
  <div class="card-header" style="border-bottom: none; padding-bottom: 0;">
    <div>
      <span class="badge badge-completed" style="margin-bottom: 0.5rem;">Earn <?= $commissionRate ?>% Lifetime Commission</span>
      <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--rose-900); margin: 0.25rem 0;">Invite Friends & Earn Real INR Cash</h2>
      <p style="color: var(--text-muted); max-width: 600px; font-size: 0.9375rem; line-height: 1.5; margin-top: 0.25rem;">
        Share your personal referral link with other creators, agencies, and businesses. Receive instant wallet commissions on every deposit they make.
      </p>
    </div>
  </div>

  <div style="padding: 1.5rem 1.75rem;">
    <label class="form-label" style="font-weight: 700; color: var(--rose-900);">Your Unique Referral Link</label>
    <div style="display: flex; gap: 0.5rem; max-width: 650px; flex-wrap: wrap;">
      <input type="text" id="ref_link_input" class="form-control" value="<?= e($referralLink) ?>" readonly style="font-weight: 600; background: #fff; flex: 1; min-width: 250px;">
      <button type="button" class="btn btn-primary" id="copy_ref_btn" onclick="copyRefLink()" style="font-weight: 700;">
        Copy Link
      </button>
    </div>
    <span id="copy_feedback" style="font-size: 0.8125rem; color: #166534; font-weight: 600; display: none; margin-top: 0.35rem;">
      &check; Referral link copied to clipboard!
    </span>
  </div>
</div>

<!-- Referral Stats Grid -->
<div class="grid grid-cols-3" style="margin-bottom: 1.5rem;" id="referral_stats_grid">
  <div class="stat-card" id="stat_ref_count">
    <div class="stat-icon">&#128101;</div>
    <div class="stat-info">
      <div class="stat-label">Total Referrals</div>
      <div class="stat-value">0</div>
    </div>
  </div>

  <div class="stat-card" id="stat_ref_earned">
    <div class="stat-icon">&#128176;</div>
    <div class="stat-info">
      <div class="stat-label">Total Commissions Earned</div>
      <div class="stat-value" style="color: #166534;">₹0.00</div>
    </div>
  </div>

  <div class="stat-card" id="stat_ref_rate">
    <div class="stat-icon">&#127873;</div>
    <div class="stat-info">
      <div class="stat-label">Commission Rate</div>
      <div class="stat-value" style="color: var(--primary-rose);"><?= $commissionRate ?>%</div>
    </div>
  </div>
</div>

<!-- How It Works Card -->
<div class="card" id="affiliate_rules_card">
  <div class="card-header">
    <h3 class="card-title">How the Affiliate System Works</h3>
  </div>
  <div class="card-body">
    <div class="grid grid-cols-3" style="gap: 1.5rem;">
      <div>
        <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--rose-100); color: var(--primary-rose); font-weight: 800; display: flex; align-items: center; justify-content: center; margin-bottom: 0.75rem;">1</div>
        <h4 style="font-size: 1rem; margin-bottom: 0.25rem;">Share Your Link</h4>
        <p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.5;">Distribute your custom invitation link across Telegram, social channels, or your marketing websites.</p>
      </div>

      <div>
        <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--rose-100); color: var(--primary-rose); font-weight: 800; display: flex; align-items: center; justify-content: center; margin-bottom: 0.75rem;">2</div>
        <h4 style="font-size: 1rem; margin-bottom: 0.25rem;">They Deposit Funds</h4>
        <p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.5;">When your referred clients fund their wallets via Razorpay UPI or NetBanking, their deposit is logged automatically.</p>
      </div>

      <div>
        <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--rose-100); color: var(--primary-rose); font-weight: 800; display: flex; align-items: center; justify-content: center; margin-bottom: 0.75rem;">3</div>
        <h4 style="font-size: 1rem; margin-bottom: 0.25rem;">Get Paid in INR</h4>
        <p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.5;">Commissions are deposited directly into your INR panel balance, ready for instant services orders.</p>
      </div>
    </div>
  </div>
</div>

<script>
function copyRefLink() {
  const input = document.getElementById('ref_link_input');
  input.select();
  input.setSelectionRange(0, 99999);
  navigator.clipboard.writeText(input.value).then(() => {
    const feedback = document.getElementById('copy_feedback');
    feedback.style.display = 'block';
    setTimeout(() => { feedback.style.display = 'none'; }, 3000);
  });
}
</script>

<?php require __DIR__ . '/footer.php'; ?>

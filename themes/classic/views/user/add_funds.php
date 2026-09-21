<?php
$pageTitle = 'Add Funds';
$currentPage = 'add_funds';
require_once __DIR__ . '/../../../../includes/razorpay.php';
require __DIR__ . '/header.php';

$userId = (int)$user['id'];
$razorpay = new RazorpayGateway();
$isGatewayReady = $razorpay->isConfigured();

// User Transaction Ledger from MySQL (Stored in INR)
$transactions = DB::fetchAll(
    "SELECT * FROM transactions WHERE user_id = ? ORDER BY id DESC LIMIT 15",
    [$userId]
);
?>

<div class="grid grid-cols-3" style="align-items: start; gap: 1.5rem;" id="add_funds_container">
  <!-- Deposit Funds Form Card (2 cols) -->
  <div class="card" style="grid-column: span 2;" id="deposit_funds_card">
    <div class="card-header">
      <div>
        <h3 class="card-title">Add Funds to Wallet</h3>
        <p class="card-subtitle">Instant, secure automated topup via Razorpay in Indian Rupees (INR)</p>
      </div>
      <div class="badge badge-completed" style="display: flex; align-items: center; gap: 0.35rem;">
        <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: #16a34a;"></span>
        INR Payments Active
      </div>
    </div>

    <?php if (!$isGatewayReady): ?>
      <div class="alert alert-warning" id="gateway_notice" style="margin-bottom: 1.25rem;">
        <div>
          <strong>Razorpay Gateway Configuration Notice:</strong><br>
          Automated payments via Razorpay are currently awaiting API credentials in Admin Settings.
          Please configure your Razorpay Key ID and Secret in <a href="/admin/settings" style="text-decoration: underline; font-weight: 700;">Admin &rarr; Settings</a>.
        </div>
      </div>
    <?php endif; ?>

    <form id="payment_initiate_form">
      <?= csrf_field() ?>

      <!-- Payment Method Selection (INR Only) -->
      <div class="form-group">
        <label class="form-label">Payment Gateway</label>
        <div style="background: var(--bg-subtle); border: 2px solid var(--primary-rose); border-radius: var(--radius-sm); padding: 1rem; display: flex; align-items: center; justify-content: space-between;">
          <div style="display: flex; align-items: center; gap: 0.75rem;">
            <input type="radio" checked id="gw_razorpay" name="gateway" value="razorpay" style="accent-color: var(--primary-rose); width: 18px; height: 18px;">
            <label for="gw_razorpay" style="font-weight: 700; cursor: pointer; margin-bottom: 0; color: var(--text-main);">
              Razorpay (All Indian Payment Methods)
            </label>
          </div>
          <span class="badge badge-default" style="font-size: 0.75rem;">UPI &bull; Cards &bull; NetBanking</span>
        </div>
        <p class="form-text" style="margin-top: 0.375rem;">Supports Google Pay, PhonePe, Paytm, BHIM UPI, Visa, Mastercard, RuPay, and 50+ NetBanking banks.</p>
      </div>

      <!-- Quick Amount Preset Buttons (INR Only) -->
      <div class="form-group">
        <label class="form-label">Quick Preset Amount</label>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;" id="preset_amount_buttons">
          <?php foreach ([100, 250, 500, 1000, 2000, 5000] as $preset): ?>
            <button type="button" class="btn btn-secondary btn-sm preset-btn" data-val="<?= $preset ?>" style="flex: 1; min-width: 70px; font-weight: 700;">
              ₹<?= number_format($preset) ?>
            </button>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Deposit Amount Input (INR Only) -->
      <div class="form-group">
        <label for="deposit_amount" class="form-label">
          Deposit Amount (INR)
        </label>
        <div style="position: relative; display: flex; align-items: center;">
          <span style="position: absolute; left: 14px; font-weight: 800; font-size: 1.125rem; color: var(--primary-rose); pointer-events: none;">₹</span>
          <input type="number" id="deposit_amount" name="amount" class="form-control" step="1" min="10" max="500000" required placeholder="500" style="padding-left: 32px; font-size: 1.125rem; font-weight: 700;" value="500">
        </div>
        <div style="display: flex; justify-content: space-between; margin-top: 0.375rem; font-size: 0.8125rem; color: var(--text-muted);">
          <span>Minimum Deposit: <strong>₹10.00</strong></span>
          <span>Zero convenience fee</span>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-block btn-lg" id="pay_now_btn" <?= !$isGatewayReady ? 'disabled' : '' ?> style="font-weight: 800; letter-spacing: 0.02em;">
        Pay ₹<span id="btn_display_amount">500.00</span> with Razorpay
      </button>
    </form>
  </div>

  <!-- Wallet Summary Card (1 col) -->
  <div class="card" id="wallet_summary_card">
    <div class="card-header">
      <h4 class="card-title" style="font-size: 1.125rem;">Wallet Overview</h4>
    </div>
    <div class="card-body">
      <div class="item-card-row" style="margin-bottom: 0.75rem;">
        <span style="color: var(--text-muted); font-size: 0.9375rem;">Current Balance:</span>
        <span style="font-size: 1.375rem; font-weight: 800; color: var(--primary-rose);">
          ₹<?= number_format((float)$user['balance'], 2) ?>
        </span>
      </div>
      <div class="item-card-row" style="margin-bottom: 0.75rem;">
        <span style="color: var(--text-muted); font-size: 0.9375rem;">Account Currency:</span>
        <span style="font-weight: 700; color: var(--text-main);">INR (₹)</span>
      </div>
      <div class="item-card-row" style="margin-bottom: 0.75rem;">
        <span style="color: var(--text-muted); font-size: 0.9375rem;">Processing Type:</span>
        <span style="font-weight: 600; color: #166534;">Instant Webhook Sync</span>
      </div>

      <div style="font-size: 0.8125rem; color: var(--text-muted); border-top: 1px solid var(--rose-100); padding-top: 0.875rem; margin-top: 0.5rem; line-height: 1.5;">
        All wallet deposits are encrypted end-to-end and credited to your balance instantly upon successful payment authorization.
      </div>
    </div>
  </div>
</div>

<!-- Transaction History Section (Cards Layout - Stored in INR) -->
<div class="card" style="margin-top: 2rem;" id="transactions_card">
  <div class="card-header">
    <div>
      <h3 class="card-title">Transaction Ledger</h3>
      <p class="card-subtitle">Complete ledger of wallet deposits, deductions, and refunds in INR</p>
    </div>
  </div>

  <?php if (empty($transactions)): ?>
    <div class="empty-state" id="empty_trans_state">
      <div class="empty-title">No Transactions Yet</div>
      <p class="empty-desc">Your wallet transaction history will be recorded here automatically.</p>
    </div>
  <?php else: ?>
    <div class="card-list" id="trans_card_list">
      <?php foreach ($transactions as $tx): ?>
        <div class="list-item-card" id="tx_card_<?= $tx['id'] ?>">
          <div class="item-card-row">
            <div style="display: flex; align-items: center; gap: 0.625rem; flex-wrap: wrap;">
              <span class="badge <?= $tx['type'] === 'deposit' ? 'badge-completed' : ($tx['type'] === 'refund' ? 'badge-processing' : 'badge-canceled') ?>">
                <?= ucfirst($tx['type']) ?>
              </span>
              <span style="font-weight: 800;">Tx #<?= $tx['id'] ?></span>
              <span style="font-size: 0.8125rem; color: var(--text-muted);"><?= format_date($tx['created_at']) ?></span>
            </div>
            <div style="font-weight: 800; font-size: 1.125rem; color: <?= $tx['type'] === 'deposit' ? '#166534' : 'var(--primary-rose)' ?>;">
              <?= $tx['type'] === 'deposit' ? '+' : '-' ?>₹<?= number_format((float)$tx['amount'], 2) ?>
            </div>
          </div>

          <div style="font-size: 0.875rem; color: var(--text-muted); margin-top: 0.25rem;">
            <?= e($tx['description']) ?>
            <?php if (!empty($tx['reference_id'])): ?>
              <span style="font-size: 0.8125rem; color: var(--text-light);">(Ref: <?= e($tx['reference_id']) ?>)</span>
            <?php endif; ?>
          </div>

          <div class="item-meta-grid" style="margin-top: 0.5rem;">
            <div class="meta-box">
              <span class="meta-label">Balance Before</span>
              <span class="meta-val">₹<?= number_format((float)$tx['balance_before'], 2) ?></span>
            </div>
            <div class="meta-box">
              <span class="meta-label">Balance After</span>
              <span class="meta-val">₹<?= number_format((float)$tx['balance_after'], 2) ?></span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- Razorpay Checkout Integration Script (INR Only) -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const amountInput = document.getElementById('deposit_amount');
  const btnDisplayAmount = document.getElementById('btn_display_amount');
  const form = document.getElementById('payment_initiate_form');
  const payBtn = document.getElementById('pay_now_btn');
  const presetBtns = document.querySelectorAll('.preset-btn');

  function updateDisplayAmount() {
    const val = parseFloat(amountInput.value) || 0;
    btnDisplayAmount.textContent = val.toFixed(2);
  }

  if (amountInput) {
    amountInput.addEventListener('input', updateDisplayAmount);
  }

  presetBtns.forEach(btn => {
    btn.addEventListener('click', function () {
      amountInput.value = this.dataset.val;
      updateDisplayAmount();
      amountInput.focus();
    });
  });

  if (form) {
    form.addEventListener('submit', async function (e) {
      e.preventDefault();
      const amount = parseFloat(amountInput.value);
      if (!amount || amount < 10) {
        alert('Please enter a minimum deposit of ₹10.00 INR.');
        return;
      }

      payBtn.disabled = true;
      payBtn.textContent = 'Connecting to Razorpay...';

      try {
        const formData = new FormData();
        formData.append('amount', amount);
        formData.append('csrf_token', "<?= csrf_token() ?>");

        const res = await fetch('/payment/razorpay/initiate', {
          method: 'POST',
          body: formData
        });

        const data = await res.json();
        if (!data.success) {
          alert(data.message || 'Payment initiation failed.');
          payBtn.disabled = false;
          payBtn.innerHTML = 'Pay ₹<span id="btn_display_amount">' + amount.toFixed(2) + '</span> with Razorpay';
          return;
        }

        // Open Real Razorpay Modal in INR
        const options = {
          key: data.key_id,
          amount: data.amount, // in paise
          currency: 'INR',
          name: "<?= e(get_setting('site_name', 'Rose SMM Panel')) ?>",
          description: "Wallet Balance Deposit (₹" + amount.toFixed(2) + ")",
          order_id: data.order_id,
          prefill: {
            name: "<?= e($user['username']) ?>",
            email: "<?= e($user['email']) ?>"
          },
          theme: {
            color: "#e11d48"
          },
          handler: async function (response) {
            payBtn.textContent = 'Verifying signature & crediting wallet...';

            const verifyData = new FormData();
            verifyData.append('razorpay_order_id', response.razorpay_order_id);
            verifyData.append('razorpay_payment_id', response.razorpay_payment_id);
            verifyData.append('razorpay_signature', response.razorpay_signature);
            verifyData.append('csrf_token', "<?= csrf_token() ?>");

            const vRes = await fetch('/payment/razorpay/verify', {
              method: 'POST',
              body: verifyData
            });

            const vData = await vRes.json();
            if (vData.success) {
              window.location.reload();
            } else {
              alert(vData.message || 'Payment verification failed.');
              payBtn.disabled = false;
              payBtn.innerHTML = 'Pay ₹<span id="btn_display_amount">' + amount.toFixed(2) + '</span> with Razorpay';
            }
          },
          modal: {
            ondismiss: function () {
              payBtn.disabled = false;
              payBtn.innerHTML = 'Pay ₹<span id="btn_display_amount">' + amount.toFixed(2) + '</span> with Razorpay';
            }
          }
        };

        const rzp = new Razorpay(options);
        rzp.open();
      } catch (err) {
        alert('Network connection error: ' + err.message);
        payBtn.disabled = false;
        payBtn.innerHTML = 'Pay ₹<span id="btn_display_amount">' + (parseFloat(amountInput.value) || 500).toFixed(2) + '</span> with Razorpay';
      }
    });
  }
});
</script>

<?php require __DIR__ . '/footer.php'; ?>

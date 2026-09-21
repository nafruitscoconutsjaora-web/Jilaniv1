<?php
$pageTitle = 'Add Funds';
$currentPage = 'add_funds';
require_once __DIR__ . '/../../../../includes/razorpay.php';
require __DIR__ . '/header.php';

$userId = (int)$user['id'];

// Query only payment gateways enabled by the admin
$enabledGateways = DB::fetchAll(
    "SELECT * FROM payment_gateways WHERE is_enabled = 1 ORDER BY sort_order ASC, id ASC"
);

// Fetch Razorpay gateway instance for min/max
$razorpay = new RazorpayGateway();
$isRazorpayEnabled = false;
$razorpayGateway = null;

foreach ($enabledGateways as $gw) {
    if ($gw['code'] === 'razorpay') {
        $isRazorpayEnabled = true;
        $razorpayGateway = $gw;
        break;
    }
}

// User Transaction Ledger (Cards, no tables)
$transactions = DB::fetchAll(
    "SELECT * FROM transactions WHERE user_id = ? ORDER BY id DESC LIMIT 15",
    [$userId]
);
?>

<div class="grid grid-cols-3" style="align-items: start; gap: 1.5rem;">
  <!-- Deposit Funds Card (2 cols) -->
  <div class="card" style="grid-column: span 2;" id="deposit_funds_card">
    <div class="card-header" style="border-bottom: 1px solid var(--rose-100); padding-bottom: 1rem; margin-bottom: 1.25rem;">
      <div>
        <h3 class="card-title" style="font-size: 1.25rem;">Add Funds to Wallet</h3>
        <p class="card-subtitle">Instant and secure payment in Indian Rupees (INR)</p>
      </div>
    </div>

    <?php if (empty($enabledGateways)): ?>
      <div class="alert alert-warning" id="no_gateways_notice">
        <div>
          <strong>Payment Gateways Unavailable:</strong><br>
          There are currently no active payment gateways enabled. Please contact support.
        </div>
      </div>
    <?php else: ?>
      <form id="payment_initiate_form">
        <?= csrf_field() ?>

        <!-- Payment Method Selection -->
        <div class="form-group">
          <label class="form-label">Select Payment Method</label>
          <div style="display: flex; flex-direction: column; gap: 0.75rem;" id="gateway_options">
            <?php foreach ($enabledGateways as $index => $gw): ?>
              <label class="gateway-select-card <?= $index === 0 ? 'selected' : '' ?>" style="display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem; border: 1.5px solid <?= $index === 0 ? 'var(--primary-rose)' : 'var(--border-light)' ?>; border-radius: var(--radius-md); background: <?= $index === 0 ? 'var(--rose-50)' : '#ffffff' ?>; cursor: pointer; transition: all var(--transition-fast);">
                <div style="display: flex; align-items: center; gap: 0.875rem;">
                  <input type="radio" name="gateway_code" value="<?= e($gw['code']) ?>" <?= $index === 0 ? 'checked' : '' ?> style="accent-color: var(--primary-rose); width: 18px; height: 18px;">
                  <div>
                    <div style="font-weight: 700; color: var(--rose-950); font-size: 0.9375rem;">
                      <?= e($gw['name']) ?>
                    </div>
                    <?php if (!empty($gw['description'])): ?>
                      <div style="font-size: 0.8125rem; color: var(--text-muted); margin-top: 0.125rem;">
                        <?= e($gw['description']) ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
                <span class="badge" style="background: #ffffff; border: 1px solid var(--rose-200); color: var(--primary-rose); font-weight: 700;">
                  Currency: <?= e($gw['currency']) ?>
                </span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <?php if ($isRazorpayEnabled): 
          $minInr = (float)($razorpayGateway['min_amount'] ?? 10.0);
          $maxInr = (float)($razorpayGateway['max_amount'] ?? 50000.0);
          $instructions = $razorpayGateway['instructions'] ?? '';
        ?>
          <div id="razorpay_section">
            <?php if (!empty($instructions)): ?>
              <div style="margin-bottom: 1.25rem; padding: 0.75rem 1rem; background: var(--bg-subtle); border-radius: var(--radius-sm); border-left: 3px solid var(--primary-rose); font-size: 0.875rem; color: var(--rose-900);">
                <?= nl2br(e($instructions)) ?>
              </div>
            <?php endif; ?>

            <!-- Amount Input strictly in INR -->
            <div class="form-group">
              <label for="deposit_amount_inr" class="form-label">
                Deposit Amount (INR)
              </label>
              <div style="position: relative;">
                <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); font-weight: 700; color: var(--primary-rose); font-size: 1.15rem;">₹</span>
                <input type="number" id="deposit_amount_inr" name="amount" class="form-control" style="padding-left: 2rem; font-size: 1.125rem; font-weight: 700;" step="1" min="<?= (int)$minInr ?>" max="<?= (int)$maxInr ?>" value="500" required placeholder="500">
              </div>
              <div style="display: flex; justify-content: space-between; font-size: 0.8125rem; color: var(--text-muted); margin-top: 0.375rem;">
                <span>Minimum: ₹<?= number_format($minInr, 2) ?> INR</span>
                <span>Maximum: ₹<?= number_format($maxInr, 2) ?> INR</span>
              </div>
            </div>

            <!-- Quick Preset INR Buttons -->
            <div class="form-group" style="margin-bottom: 1.5rem;">
              <label class="form-label" style="font-size: 0.8125rem; color: var(--text-muted);">Quick Presets</label>
              <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;" id="preset_buttons">
                <?php foreach ([100, 250, 500, 1000, 2500, 5000] as $preset): ?>
                  <button type="button" class="btn btn-secondary btn-sm preset-btn" data-val="<?= $preset ?>" style="padding: 0.35rem 0.75rem; font-weight: 600;">
                    + ₹<?= number_format($preset) ?>
                  </button>
                <?php endforeach; ?>
              </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" id="pay_now_btn" style="padding: 0.875rem 1.5rem; font-size: 1rem; font-weight: 700; border-radius: var(--radius-pill); box-shadow: var(--shadow-rose);">
              Proceed to Pay <span id="pay_btn_amount_label">₹500.00</span> INR
            </button>
          </div>
        <?php endif; ?>
      </form>
    <?php endif; ?>
  </div>

  <!-- Wallet Summary Card (1 col) -->
  <div class="card" id="wallet_summary_card">
    <div class="card-header" style="border-bottom: 1px solid var(--rose-100); padding-bottom: 0.75rem; margin-bottom: 1rem;">
      <h4 class="card-title" style="font-size: 1.125rem;">Wallet Balance</h4>
    </div>
    <div class="card-body">
      <div style="background: linear-gradient(135deg, var(--rose-50) 0%, #fff 100%); padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--rose-200); margin-bottom: 1.25rem;">
        <span style="font-size: 0.8125rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 0.25rem;">Current Available Funds</span>
        <div style="font-size: 1.75rem; font-weight: 800; color: var(--primary-rose);">
          <?= Currency::format((float)$user['balance'], $userCurrency) ?>
        </div>
      </div>

      <div style="display: flex; flex-direction: column; gap: 0.75rem; font-size: 0.875rem;">
        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--rose-100); padding-bottom: 0.5rem;">
          <span style="color: var(--text-muted);">Account</span>
          <span style="font-weight: 600;"><?= e($user['username']) ?></span>
        </div>
        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--rose-100); padding-bottom: 0.5rem;">
          <span style="color: var(--text-muted);">Status</span>
          <span class="badge badge-completed">Verified</span>
        </div>
        <div style="display: flex; justify-content: space-between;">
          <span style="color: var(--text-muted);">Settlement</span>
          <span style="font-weight: 600; color: var(--rose-700);">Instant Credit</span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Transaction History Section (Cards, No Tables) -->
<div class="card" style="margin-top: 2rem;" id="transactions_card">
  <div class="card-header">
    <div>
      <h3 class="card-title" style="font-size: 1.125rem;">Transaction Ledger</h3>
      <p class="card-subtitle">Recent deposit credits and wallet balance movements</p>
    </div>
  </div>

  <?php if (empty($transactions)): ?>
    <div class="empty-state">
      <div class="empty-title">No Transactions Yet</div>
      <p class="empty-desc">Your balance additions and deductions will appear here automatically.</p>
    </div>
  <?php else: ?>
    <div class="card-list" id="transaction_items_list">
      <?php foreach ($transactions as $t): ?>
        <div class="list-item-card" id="tx_item_<?= $t['id'] ?>">
          <div class="item-card-row">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
              <span class="badge badge-<?= $t['type'] === 'deposit' || $t['type'] === 'bonus' ? 'completed' : 'pending' ?>">
                <?= ucfirst($t['type']) ?>
              </span>
              <span style="font-weight: 700; color: var(--rose-900); font-size: 0.9375rem;">
                <?= e($t['description'] ?: 'Wallet Transaction') ?>
              </span>
            </div>
            <div style="font-weight: 800; font-size: 1.125rem; color: <?= $t['amount'] >= 0 ? '#10b981' : 'var(--primary-rose)' ?>;">
              <?= $t['amount'] >= 0 ? '+' : '' ?><?= Currency::format((float)$t['amount'], $userCurrency) ?>
            </div>
          </div>

          <div class="item-meta-grid" style="margin-top: 0.5rem;">
            <div class="meta-box">
              <span class="meta-label">Reference ID</span>
              <span class="meta-val"><code><?= e($t['reference_id'] ?: 'TX-' . $t['id']) ?></code></span>
            </div>
            <div class="meta-box">
              <span class="meta-label">Balance After</span>
              <span class="meta-val"><?= Currency::format((float)$t['balance_after'], $userCurrency) ?></span>
            </div>
            <div class="meta-box">
              <span class="meta-label">Date & Time</span>
              <span class="meta-val"><?= date('M d, Y h:i A', strtotime($t['created_at'])) ?></span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- Razorpay Checkout Integration Script -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const amountInput = document.getElementById('deposit_amount_inr');
  const payBtnAmountLabel = document.getElementById('pay_btn_amount_label');
  const form = document.getElementById('payment_initiate_form');
  const payBtn = document.getElementById('pay_now_btn');
  const presetButtons = document.querySelectorAll('.preset-btn');

  function updateButtonLabel() {
    if (!amountInput || !payBtnAmountLabel) return;
    const val = parseFloat(amountInput.value) || 0;
    payBtnAmountLabel.textContent = '₹' + val.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  if (amountInput) {
    amountInput.addEventListener('input', updateButtonLabel);
    updateButtonLabel();
  }

  presetButtons.forEach(btn => {
    btn.addEventListener('click', function () {
      const val = parseFloat(this.getAttribute('data-val'));
      if (amountInput && val > 0) {
        amountInput.value = val;
        updateButtonLabel();
        amountInput.focus();
      }
    });
  });

  if (form) {
    form.addEventListener('submit', async function (e) {
      e.preventDefault();
      const amountInr = parseFloat(amountInput.value);
      if (!amountInr || amountInr <= 0) {
        alert('Please enter a valid INR deposit amount.');
        return;
      }

      payBtn.disabled = true;
      payBtn.textContent = 'Initializing Payment with Razorpay...';

      try {
        const formData = new FormData();
        formData.append('amount', amountInr);
        formData.append('csrf_token', "<?= csrf_token() ?>");

        const res = await fetch('/payment/razorpay/initiate', {
          method: 'POST',
          body: formData
        });

        const data = await res.json();
        if (!data.success) {
          alert(data.message || 'Payment initiation failed.');
          payBtn.disabled = false;
          updateButtonLabel();
          return;
        }

        // Open Real Razorpay Checkout modal strictly in INR
        const options = {
          key: data.key_id,
          amount: data.amount, // In paise
          currency: 'INR',
          name: "<?= e(get_setting('site_name', 'Rose SMM Panel')) ?>",
          description: "Wallet Balance Deposit (₹" + amountInr.toFixed(2) + " INR)",
          order_id: data.order_id,
          prefill: {
            name: "<?= e($user['username']) ?>",
            email: "<?= e($user['email']) ?>"
          },
          theme: {
            color: "#e11d48"
          },
          handler: async function (response) {
            payBtn.textContent = 'Verifying payment signature...';

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
              updateButtonLabel();
            }
          },
          modal: {
            ondismiss: function () {
              payBtn.disabled = false;
              updateButtonLabel();
            }
          }
        };

        const rzp = new Razorpay(options);
        rzp.open();
      } catch (err) {
        alert('Network connection error: ' + err.message);
        payBtn.disabled = false;
        updateButtonLabel();
      }
    });
  }
});
</script>

<?php require __DIR__ . '/footer.php'; ?>

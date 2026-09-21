<?php
$pageTitle = 'Add Funds';
$currentPage = 'add_funds';
require_once __DIR__ . '/../../../../includes/razorpay.php';
require __DIR__ . '/header.php';

$userId = (int)$user['id'];
$razorpay = new RazorpayGateway();
$isGatewayReady = $razorpay->isConfigured();

// User Transaction Ledger (Cards, no tables)
$transactions = DB::fetchAll(
    "SELECT * FROM transactions WHERE user_id = ? ORDER BY id DESC LIMIT 10",
    [$userId]
);
?>

<div class="grid grid-cols-3" style="align-items: start;">
  <!-- Deposit Funds Card (2 cols) -->
  <div class="card" style="grid-column: span 2;" id="deposit_funds_card">
    <div class="card-header">
      <div>
        <h3 class="card-title">Deposit Funds into Wallet</h3>
        <p class="card-subtitle">Automated instant payment processing</p>
      </div>
    </div>

    <?php if (!$isGatewayReady): ?>
      <div class="alert alert-warning" id="gateway_notice">
        <div>
          <strong>Payment Gateway Notice:</strong><br>
          Online automated payments via Razorpay are currently awaiting API credentials configuration in the Admin Settings.
          Please contact support or configure your Razorpay Key ID and Secret in Admin &rarr; Settings.
        </div>
      </div>
    <?php endif; ?>

    <form id="payment_initiate_form">
      <?= csrf_field() ?>

      <div class="form-group">
        <label class="form-label">Selected Payment Gateway</label>
        <div style="background: var(--bg-subtle); border: 1px solid var(--rose-200); border-radius: var(--radius-sm); padding: 0.875rem; display: flex; align-items: center; gap: 0.75rem;">
          <input type="radio" checked id="gw_razorpay" name="gateway" value="razorpay">
          <label for="gw_razorpay" style="font-weight: 600; cursor: pointer; margin-bottom: 0;">
            Razorpay (UPI, Credit/Debit Cards, NetBanking, Wallets)
          </label>
        </div>
      </div>

      <div class="form-group">
        <label for="deposit_amount" class="form-label">
          Deposit Amount (USD) - Current rate: 1 USD = <?= number_format($userCurrency['rate'], 4) ?> <?= e($userCurrency['code']) ?>
        </label>
        <div style="position: relative;">
          <input type="number" id="deposit_amount" name="amount" class="form-control" step="0.50" min="1" max="10000" required placeholder="10.00">
        </div>
        <div id="converted_amount_preview" style="font-size: 0.875rem; font-weight: 600; color: var(--primary-rose); margin-top: 0.375rem;">
          You will pay approximately: <?= e($userCurrency['symbol']) ?><span id="calc_target_val">0.00</span> <?= e($userCurrency['code']) ?>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-block btn-lg" id="pay_now_btn" <?= !$isGatewayReady ? 'disabled' : '' ?>>
        Pay with Razorpay
      </button>
    </form>
  </div>

  <!-- Wallet Summary Card (1 col) -->
  <div class="card" id="wallet_summary_card">
    <div class="card-header">
      <h4 class="card-title" style="font-size: 1.125rem;">Wallet Summary</h4>
    </div>
    <div class="card-body">
      <div class="item-card-row" style="margin-bottom: 0.75rem;">
        <span style="color: var(--text-muted);">Current Balance:</span>
        <span style="font-size: 1.25rem; font-weight: 800; color: var(--primary-rose);">
          <?= Currency::format((float)$user['balance'], $userCurrency) ?>
        </span>
      </div>
      <div class="item-card-row" style="margin-bottom: 0.75rem;">
        <span style="color: var(--text-muted);">Base Currency:</span>
        <span style="font-weight: 600;">$<?= number_format($user['balance'], 4) ?> USD</span>
      </div>
      <div class="item-card-row" style="margin-bottom: 0.75rem;">
        <span style="color: var(--text-muted);">Active Rate:</span>
        <span style="font-weight: 600;"><?= number_format($userCurrency['rate'], 4) ?></span>
      </div>
      <div style="font-size: 0.8125rem; color: var(--text-muted); border-top: 1px solid var(--rose-100); padding-top: 0.75rem;">
        All ledger balances are securely tracked with millicent precision and encrypted transaction hashes.
      </div>
    </div>
  </div>
</div>

<!-- Transaction History Section (Cards, No Tables) -->
<div class="card" style="margin-top: 2rem;" id="transactions_card">
  <div class="card-header">
    <div>
      <h3 class="card-title">Transaction Ledger</h3>
      <p class="card-subtitle">Recent wallet additions, deductions and adjustments</p>
    </div>
  </div>

  <?php if (empty($transactions)): ?>
    <div class="empty-state" id="empty_trans_state">
      <div class="empty-title">No Transactions Recorded</div>
      <p class="empty-desc">Your transaction history will appear here once you make your first deposit or order.</p>
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
              <span style="font-weight: 700;">#<?= $tx['id'] ?></span>
              <span style="font-size: 0.8125rem; color: var(--text-muted);"><?= format_date($tx['created_at']) ?></span>
            </div>
            <div style="font-weight: 800; font-size: 1.0625rem; color: <?= $tx['type'] === 'deposit' ? '#166534' : 'var(--primary-rose)' ?>;">
              <?= $tx['type'] === 'deposit' ? '+' : '-' ?><?= Currency::format((float)$tx['amount'], $userCurrency) ?>
            </div>
          </div>

          <div style="font-size: 0.875rem; color: var(--text-muted);">
            <?= e($tx['description']) ?>
            <?php if (!empty($tx['reference_id'])): ?>
              <span style="font-size: 0.8125rem; color: var(--text-light);">(Ref: <?= e($tx['reference_id']) ?>)</span>
            <?php endif; ?>
          </div>

          <div class="item-meta-grid">
            <div class="meta-box">
              <span class="meta-label">Balance Before</span>
              <span class="meta-val"><?= Currency::format((float)$tx['balance_before'], $userCurrency) ?></span>
            </div>
            <div class="meta-box">
              <span class="meta-label">Balance After</span>
              <span class="meta-val"><?= Currency::format((float)$tx['balance_after'], $userCurrency) ?></span>
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
  const amountInput = document.getElementById('deposit_amount');
  const calcTargetVal = document.getElementById('calc_target_val');
  const rate = parseFloat(window.userCurrencyRate || 1.0);
  const form = document.getElementById('payment_initiate_form');
  const payBtn = document.getElementById('pay_now_btn');

  if (amountInput && calcTargetVal) {
    amountInput.addEventListener('input', function () {
      const val = parseFloat(this.value) || 0;
      calcTargetVal.textContent = (val * rate).toFixed(2);
    });
  }

  if (form) {
    form.addEventListener('submit', async function (e) {
      e.preventDefault();
      const amount = parseFloat(amountInput.value);
      if (!amount || amount < 1) {
        alert('Please enter a valid deposit amount ($1 minimum).');
        return;
      }

      payBtn.disabled = true;
      payBtn.textContent = 'Contacting Razorpay Gateway...';

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
          payBtn.textContent = 'Pay with Razorpay';
          return;
        }

        // Open Real Razorpay Modal
        const options = {
          key: data.key_id,
          amount: data.amount,
          currency: data.currency,
          name: "<?= e(get_setting('site_name', 'Rose SMM Panel')) ?>",
          description: "Wallet Balance Topup",
          order_id: data.order_id,
          prefill: {
            name: "<?= e($user['username']) ?>",
            email: "<?= e($user['email']) ?>"
          },
          theme: {
            color: "#e11d48"
          },
          handler: async function (response) {
            payBtn.textContent = 'Verifying signature...';

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
              payBtn.textContent = 'Pay with Razorpay';
            }
          },
          modal: {
            ondismiss: function () {
              payBtn.disabled = false;
              payBtn.textContent = 'Pay with Razorpay';
            }
          }
        };

        const rzp = new Razorpay(options);
        rzp.open();
      } catch (err) {
        alert('Network connection error: ' + err.message);
        payBtn.disabled = false;
        payBtn.textContent = 'Pay with Razorpay';
      }
    });
  }
});
</script>

<?php require __DIR__ . '/footer.php'; ?>

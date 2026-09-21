<?php
$pageTitle = 'Account Profile';
$currentPage = 'profile';
require __DIR__ . '/header.php';
?>

<div class="grid grid-cols-2" style="align-items: start;">
  <!-- Profile & Currency Settings Card -->
  <div class="card" id="profile_settings_card">
    <div class="card-header">
      <h3 class="card-title">Currency & Preferences</h3>
    </div>

    <form method="POST" action="/user/currency" style="margin-bottom: 2rem;">
      <?= csrf_field() ?>
      <div class="form-group">
        <label for="pref_currency" class="form-label">Preferred Display Currency</label>
        <select id="pref_currency" name="currency" class="form-control">
          <?php foreach ($activeCurrencies as $c): ?>
            <option value="<?= e($c['code']) ?>" <?= $user['currency_code'] === $c['code'] ? 'selected' : '' ?>>
              <?= e($c['code']) ?> - <?= e($c['name']) ?> (<?= e($c['symbol']) ?>) [Rate: <?= number_format($c['rate'], 4) ?>]
            </option>
          <?php endforeach; ?>
        </select>
        <span class="form-text">
          Saves to your account in MySQL. Automatically applies numerical conversions across your wallet, services, and new orders.
        </span>
      </div>

      <button type="submit" class="btn btn-primary btn-sm">
        Save Currency Preference
      </button>
    </form>

    <div class="card-header" style="border-top: 1px solid var(--rose-100); padding-top: 1.25rem;">
      <h3 class="card-title">Account Information</h3>
    </div>

    <div class="item-meta-grid" style="margin-bottom: 1.25rem;">
      <div class="meta-box">
        <span class="meta-label">Username</span>
        <span class="meta-val"><?= e($user['username']) ?></span>
      </div>
      <div class="meta-box">
        <span class="meta-label">Email</span>
        <span class="meta-val"><?= e($user['email']) ?></span>
      </div>
      <div class="meta-box">
        <span class="meta-label">Member Since</span>
        <span class="meta-val"><?= format_date($user['created_at'], 'M d, Y') ?></span>
      </div>
      <div class="meta-box">
        <span class="meta-label">Role</span>
        <span class="meta-val" style="text-transform: capitalize;"><?= e($user['role']) ?></span>
      </div>
    </div>
  </div>

  <!-- Security & API Key Card -->
  <div style="display: flex; flex-direction: column; gap: 1.25rem;">
    <!-- Change Password Form -->
    <div class="card" id="change_password_card">
      <div class="card-header">
        <h4 class="card-title" style="font-size: 1.125rem;">Change Password</h4>
      </div>

      <form method="POST" action="/profile/password" id="change_password_form">
        <?= csrf_field() ?>

        <div class="form-group">
          <label for="current_password" class="form-label">Current Password</label>
          <input type="password" id="current_password" name="current_password" class="form-control" required>
        </div>

        <div class="form-group">
          <label for="new_password" class="form-label">New Password</label>
          <input type="password" id="new_password" name="new_password" class="form-control" required minlength="6">
        </div>

        <button type="submit" class="btn btn-secondary btn-sm">
          Update Password
        </button>
      </form>
    </div>

    <!-- API Key Card -->
    <div class="card" id="api_key_card">
      <div class="card-header">
        <h4 class="card-title" style="font-size: 1.125rem;">Developer API Key</h4>
      </div>

      <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1rem;">
        Use your personal API key to place and query orders programmatically via the Standard SMM API v2.
      </p>

      <div class="form-group">
        <input type="text" readonly value="<?= e($user['api_key'] ?? 'No API key generated') ?>" class="form-control" style="font-family: monospace; font-size: 0.8125rem; background: var(--bg-subtle);">
      </div>

      <form method="POST" action="/profile/api-key">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-outline-rose btn-sm" onclick="return confirm('Regenerate your API key? Any existing scripts using the old key will stop working.')">
          Regenerate API Key
        </button>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>

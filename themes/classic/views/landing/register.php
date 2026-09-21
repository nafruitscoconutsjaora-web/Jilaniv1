<?php
$pageTitle = 'Create Account';
$currentPage = 'register';
require __DIR__ . '/header.php';

$activeCurrencies = DB::fetchAll("SELECT * FROM currencies WHERE status = 'active' ORDER BY is_default DESC, code ASC");
?>

<div class="container" style="padding: 3.5rem 1.25rem; max-width: 480px;">
  <div class="card" id="register_card">
    <div class="card-header" style="text-align: center; display: block; border-bottom: none; margin-bottom: 0.5rem;">
      <h2 style="font-size: 1.5rem; margin-bottom: 0.25rem;">Create an Account</h2>
      <p class="card-subtitle">Get instant access to social marketing infrastructure</p>
    </div>

    <form method="POST" action="/register" id="register_form">
      <?= csrf_field() ?>

      <div class="form-group">
        <label for="reg_username" class="form-label">Username</label>
        <input type="text" id="reg_username" name="username" class="form-control" required placeholder="Choose a unique username" pattern="[a-zA-Z0-9_]{3,30}">
        <span class="form-text">3-30 characters, alphanumeric and underscore</span>
      </div>

      <div class="form-group">
        <label for="reg_email" class="form-label">Email Address</label>
        <input type="email" id="reg_email" name="email" class="form-control" required placeholder="name@example.com">
      </div>

      <div class="form-group">
        <label for="reg_currency" class="form-label">Account Preferred Currency</label>
        <select id="reg_currency" name="currency" class="form-control">
          <?php foreach ($activeCurrencies as $c): ?>
            <option value="<?= e($c['code']) ?>" <?= $c['is_default'] ? 'selected' : '' ?>>
              <?= e($c['code']) ?> - <?= e($c['name']) ?> (<?= e($c['symbol']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
        <span class="form-text">You can also update this anytime in your profile settings.</span>
      </div>

      <div class="form-group">
        <label for="reg_password" class="form-label">Password</label>
        <input type="password" id="reg_password" name="password" class="form-control" required placeholder="At least 6 characters" minlength="6">
      </div>

      <button type="submit" class="btn btn-primary btn-block" id="register_submit_btn" style="margin-top: 1.25rem;">
        Complete Registration
      </button>
    </form>

    <div class="card-footer" style="justify-content: center; font-size: 0.875rem;">
      <span>Already have an account? <a href="/login">Sign In</a></span>
    </div>
  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>

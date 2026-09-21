<?php
$pageTitle = 'Reset Password';
$currentPage = 'forgot_password';
require __DIR__ . '/header.php';
?>

<div class="container" style="padding: 3.5rem 1.25rem; max-width: 460px;">
  <div class="card" id="forgot_card">
    <div class="card-header" style="text-align: center; display: block; border-bottom: none; margin-bottom: 0.5rem;">
      <h2 style="font-size: 1.5rem; margin-bottom: 0.25rem;">Reset Password</h2>
      <p class="card-subtitle">Enter your registered email address</p>
    </div>

    <form method="POST" action="/forgot-password" id="forgot_form">
      <?= csrf_field() ?>

      <div class="form-group">
        <label for="reset_email" class="form-label">Account Email</label>
        <input type="email" id="reset_email" name="email" class="form-control" required placeholder="name@example.com">
        <span class="form-text">Instructions to securely reset your password will be sent.</span>
      </div>

      <button type="submit" class="btn btn-primary btn-block" id="reset_submit_btn" style="margin-top: 1.25rem;">
        Send Reset Link
      </button>
    </form>

    <div class="card-footer" style="justify-content: center; font-size: 0.875rem;">
      <a href="/login">&larr; Return to Sign In</a>
    </div>
  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>

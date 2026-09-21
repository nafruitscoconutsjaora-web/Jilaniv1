<?php
$pageTitle = 'Sign In';
$currentPage = 'login';
require __DIR__ . '/header.php';
?>

<div class="container" style="padding: 3.5rem 1.25rem; max-width: 460px;">
  <div class="card" id="login_card">
    <div class="card-header" style="text-align: center; display: block; border-bottom: none; margin-bottom: 0.5rem;">
      <h2 style="font-size: 1.5rem; margin-bottom: 0.25rem;">Welcome Back</h2>
      <p class="card-subtitle">Sign in to your SMM Panel account</p>
    </div>

    <form method="POST" action="/login" id="login_form">
      <?= csrf_field() ?>

      <div class="form-group">
        <label for="username" class="form-label">Username or Email</label>
        <input type="text" id="username" name="username" class="form-control" required autofocus placeholder="Enter your username or email">
      </div>

      <div class="form-group">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.375rem;">
          <label for="password" class="form-label" style="margin-bottom: 0;">Password</label>
          <a href="/forgot-password" style="font-size: 0.8125rem;">Forgot?</a>
        </div>
        <input type="password" id="password" name="password" class="form-control" required placeholder="Enter your password">
      </div>

      <button type="submit" class="btn btn-primary btn-block" id="login_submit_btn" style="margin-top: 1.25rem;">
        Sign In
      </button>
    </form>

    <div class="card-footer" style="justify-content: center; font-size: 0.875rem;">
      <span>Don't have an account yet? <a href="/register">Create Account</a></span>
    </div>
  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>

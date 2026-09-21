<?php
/**
 * Public Landing Page Footer
 * Separate Public Footer - No Admin Links / No User Links / No Currency Selection
 */
$siteName = e(get_setting('site_name', 'Rose SMM Panel'));
?>
</main>

<footer class="landing-footer" id="public_footer">
  <div class="container">
    <div class="item-card-row" style="margin-bottom: 1.5rem; justify-content: center; gap: 2rem;">
      <a href="/" style="color: var(--text-muted); font-size: 0.875rem;">Home</a>
      <a href="/services" style="color: var(--text-muted); font-size: 0.875rem;">Services</a>
      <a href="/login" style="color: var(--text-muted); font-size: 0.875rem;">Login</a>
      <a href="/register" style="color: var(--text-muted); font-size: 0.875rem;">Register</a>
    </div>
    <p>&copy; <?= date('Y') ?> <?= $siteName ?>. All rights reserved.</p>
  </div>
</footer>

<script src="/themes/classic/assets/js/app.js"></script>
</body>
</html>

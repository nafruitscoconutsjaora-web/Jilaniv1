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
    <div class="footer-card" id="footer_main_card">
      <div class="footer-grid">
        <!-- Brand & Mission Column -->
        <div class="footer-col footer-col-brand">
          <a href="/" class="brand-logo footer-brand-logo" id="footer_brand_logo">
            <span class="brand-badge">SMM</span>
            <span class="brand-text"><?= $siteName ?></span>
          </a>
          <p class="footer-bio">
            Premium social media marketing infrastructure designed for automated, high-speed fulfillment. Connect directly with verified providers, monitor deliveries, and scale your social growth.
          </p>
          <div class="footer-status-pill">
            <span class="status-pulse-dot"></span>
            <span class="status-text">All API Systems Operational</span>
          </div>
        </div>

        <!-- Platform Column -->
        <div class="footer-col">
          <h4 class="footer-col-title">Platform</h4>
          <ul class="footer-links">
            <li><a href="/">Home Overview</a></li>
            <li><a href="/services">Service Catalog</a></li>
            <li><a href="/services">Instant Delivery Rates</a></li>
            <li><a href="/services">High-Speed Inventory</a></li>
          </ul>
        </div>

        <!-- Client Portal Column -->
        <div class="footer-col">
          <h4 class="footer-col-title">Client Access</h4>
          <ul class="footer-links">
            <li><a href="/login">Client Portal</a></li>
            <li><a href="/register">Create Account</a></li>
            <li><a href="/login">Track Order History</a></li>
            <li><a href="/login">Submit Support Ticket</a></li>
          </ul>
        </div>

        <!-- Legal & Security Column -->
        <div class="footer-col">
          <h4 class="footer-col-title">Legal & Trust</h4>
          <ul class="footer-links">
            <li><a href="/services#terms">Terms of Service</a></li>
            <li><a href="/services#privacy">Privacy Policy</a></li>
            <li><a href="/services#refund">Refund Policy</a></li>
            <li><a href="/services#sla">Service Level Guarantee</a></li>
          </ul>
        </div>
      </div>

      <!-- Trust Badges Row -->
      <div class="footer-trust-row">
        <div class="trust-item">
          <span class="trust-icon">&#128274;</span>
          <span class="trust-text">256-Bit SSL Encrypted Transactions</span>
        </div>
        <div class="trust-item">
          <span class="trust-icon">&#9889;</span>
          <span class="trust-text">Automated Order Dispatching</span>
        </div>
        <div class="trust-item">
          <span class="trust-icon">&#10004;</span>
          <span class="trust-text">Verified Razorpay Gateway</span>
        </div>
      </div>
    </div>

    <!-- Bottom Copyright & Details -->
    <div class="footer-bottom">
      <p class="footer-copyright">
        &copy; <?= date('Y') ?> <?= $siteName ?>. All rights reserved.
      </p>
      <div class="footer-badges">
        <span>Instant Delivery</span>
        <span class="sep">&bull;</span>
        <span>24/7 Monitoring</span>
        <span class="sep">&bull;</span>
        <span>Enterprise Reliability</span>
      </div>
    </div>
  </div>
</footer>

<script src="/themes/classic/assets/js/app.js"></script>
</body>
</html>

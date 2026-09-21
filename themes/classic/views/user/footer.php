<?php
/**
 * User Panel Footer
 * Separate User Footer
 */
$siteName = e(get_setting('site_name', 'Rose SMM Panel'));
?>
    </div> <!-- .panel-content -->

    <footer class="panel-footer" id="user_panel_footer">
      <span>&copy; <?= date('Y') ?> <?= $siteName ?> - User Portal</span>
      <span>Current Preferred Currency: <strong><?= e($userCurrency['code']) ?> (<?= e($userCurrency['symbol']) ?>)</strong></span>
    </footer>
  </div> <!-- .panel-main -->
</div> <!-- .panel-wrapper -->

<script src="/themes/classic/assets/js/app.js"></script>
</body>
</html>

<?php
/**
 * Admin Panel Footer
 * Separate Admin Footer
 */
$siteName = e(get_setting('site_name', 'Rose SMM Panel'));
?>
    </div> <!-- .panel-content -->

    <footer class="panel-footer" id="admin_panel_footer">
      <span>&copy; <?= date('Y') ?> <?= $siteName ?> - Super Administrator Console</span>
      <span>Engine: Core PHP 8.2 + MariaDB</span>
    </footer>
  </div> <!-- .panel-main -->
</div> <!-- .panel-wrapper -->

<script src="/themes/classic/assets/js/admin.js"></script>
</body>
</html>

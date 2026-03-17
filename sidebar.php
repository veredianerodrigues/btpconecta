<?php
if (!defined('ABSPATH')) {
    exit;
}
/**
 * BTP Conecta — sidebar.php
 * Sidebar direita (opcional). O menu lateral principal fica no header.php (navigation-wrapper).
 */
if (!is_active_sidebar('sidebar-1')) {
    return;
}
?>
<aside id="sidebar" class="widget-area" role="complementary">
    <?php dynamic_sidebar('sidebar-1'); ?>
</aside>

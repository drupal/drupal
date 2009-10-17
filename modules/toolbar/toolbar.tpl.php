<?php
// $Id$

/**
 * @file
 * Default template for admin toolbar.
 *
 * Available variables:
 * - $toolbar['toolbar_user']: User account / logout links.
 * - $toolbar['toolbar_menu']: Top level management menu links.
 * - $toolbar['toolbar_drawer']: A place for extended toolbar content.
 *
 * @see template_preprocess()
 * @see template_preprocess_admin_toolbar()
 */
?>
<div id="toolbar" class="clearfix">
  <div class="toolbar-menu clearfix">
    <?php if ($toolbar['toolbar_drawer']):?>
      <span class="toggle toggle-active"><?php print t('Open'); ?></span>
    <?php endif; ?>
    <?php print render($toolbar['toolbar_menu']); ?>
    <?php print render($toolbar['toolbar_user']); ?>
  </div>

  <div class="toolbar-drawer clearfix">
    <?php print render($toolbar['toolbar_drawer']); ?>
  </div>

  <div class="shadow"></div>
</div>

<?php

/**
 * @file
 * Two column template for the node add/edit form.
 *
 * Available variables:
 * - $form: The actual form to print.
 */

hide($form['advanced']);
hide($form['actions']);

?>
<div class="layout-node-form clearfix">
  <div class="layout-region layout-region-node-main">
    <?php print drupal_render_children($form); ?>
  </div>

  <div class="layout-region layout-region-node-secondary">
    <?php print render($form['advanced']); ?>
  </div>

  <div class="layout-region layout-region-node-footer">
    <?php print render($form['actions']); ?>
  </div>
</div>

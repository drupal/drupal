<?php

/**
 * @file
 * Default theme implementation to configure blocks.
 *
 * Available variables:
 * - $left: Any form array elements that should appear in the left hand column.
 * - $right: Any form array elements that should appear in the right hand column.
 * - $form_submit: Form submit button.
 *
 * @see template_preprocess_block_library_form()
 * @see theme_block_library_form()
 *
 * @ingroup themeable
 */
?>
<div id="block-library" class="container">
  <div class="left-col">
    <div class="inside">
      <?php print $left; ?>
    </div>
  </div>
  <div class="right-col">
    <div class="inside">
      <?php print $right; ?>
    </div>
  </div>
  <?php if ($form_submit) { ?>
  <div class="bottom-bar"><?php print $form_submit; ?></div>
  <?php } ?>
</div>

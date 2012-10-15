<?php
/**
 * @file
 * Template for a 2 column layout.
 *
 * This template provides a two column display layout, with each column equal in
 * width.
 *
 * Variables:
 * - $content: An array of content, each item in the array is keyed to one
 *   region of the layout. This layout supports the following sections:
 *   - $content['left']: Content in the left column.
 *   - $content['right']: Content in the right column.
 */
?>
<div class="layout-display layout-two-col clearfix">
  <div class="layout-region layout-col-left">
    <div class="inside"><?php print $content['left']; ?></div>
  </div>

  <div class="layout-region layout-col-right">
    <div class="inside"><?php print $content['right']; ?></div>
  </div>
</div>

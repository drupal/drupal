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
 *   - $content['first']: Content in the first column.
 *   - $content['second']: Content in the second column.
 */
?>
<div class="layout-display layout-two-col clearfix <?php print $attributes['class']; ?>"<?php print $attributes; ?>>
  <div class="layout-region layout-col-first">
    <?php print $content['first']; ?>
  </div>

  <div class="layout-region layout-col-second">
    <?php print $content['second']; ?>
  </div>
</div>

<?php
/**
 * @file
 * Template for a 1 column layout.
 *
 * This template provides a simple one column display layout.
 *
 * Variables:
 * - $content: An array of content, each item in the array is keyed to one
 *   region of the layout. This layout supports only one section:
 *   - $content['content']: Content in the content column.
 */
?>
<div class="layout-display layout-one-col <?php print $attributes['class']; ?>"<?php print $attributes; ?>>
  <div class="layout-region">
    <?php print $content['content']; ?>
  </div>
</div>

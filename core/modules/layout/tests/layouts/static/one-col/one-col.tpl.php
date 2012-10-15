<?php
/**
 * @file
 * Template for a one column layout.
 *
 * This template provides a very simple "one column" display layout.
 *
 * Variables:
 * - $content: An array of content, each item in the array is keyed to one
 *   region of the layout. This layout supports the following sections:
 *   $content['middle']: The only region in the layout.
 */
?>
<div class="layout-display layout-one-col clearfix">
  <div class="layout-region layout-col">
    <div class="inside"><?php print $content['middle']; ?></div>
  </div>
</div>

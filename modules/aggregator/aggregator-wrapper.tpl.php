<?php
// $Id: aggregator-wrapper.tpl.php,v 1.1 2007/09/13 08:02:38 goba Exp $

/**
 * @file comment-wrapper.tpl.php
 * Default theme implementation to wrap aggregator content.
 *
 * Available variables:
 * - $content: All aggregator content.
 * - $page: Pager links rendered through theme_pager().
 *
 * @see template_preprocess()
 * @see template_preprocess_comment_wrapper()
 */
?>
<div id="aggregator">
  <?php print $content; ?>
  <?php print $pager; ?>
</div>

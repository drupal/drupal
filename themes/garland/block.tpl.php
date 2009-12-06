<?php
// $Id$
?>
<div id="block-<?php print $block->module . '-' . $block->delta; ?>" class="<?php print $classes; ?> clearfix"<?php print $attributes; ?>>

<?php if (!empty($contextual_links)): ?>
  <?php print render($contextual_links); ?>
<?php endif; ?>

<?php if (!empty($block->subject)): ?>
  <h2 class="title"<?php print $title_attributes; ?>><?php print $block->subject ?></h2>
<?php endif;?>

  <div class="content"<?php print $content_attributes; ?>><?php print $content ?></div>
</div>

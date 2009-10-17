<?php
// $Id: block.tpl.php,v 1.10 2009/10/17 05:50:29 webchick Exp $
?>
<div id="block-<?php print $block->module . '-' . $block->delta; ?>" class="<?php print $classes; ?> clearfix"<?php print $attributes; ?>>

<?php if ($contextual_links): ?>
  <?php print render($contextual_links); ?>
<?php endif; ?>

<?php if (!empty($block->subject)): ?>
  <h2 class="title"<?php print $title_attributes; ?>><?php print $block->subject ?></h2>
<?php endif;?>

  <div class="content"><?php print $content ?></div>
</div>

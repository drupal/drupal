<?php
// $Id: block.tpl.php,v 1.8 2009/09/05 08:53:29 dries Exp $
?>
<div id="block-<?php print $block->module . '-' . $block->delta; ?>" class="<?php print $classes; ?> clearfix">

<?php if (!empty($block->subject)): ?>
  <h2 class="title"><?php print $block->subject ?></h2>
<?php endif;?>

  <div class="content"><?php print $content ?></div>
</div>

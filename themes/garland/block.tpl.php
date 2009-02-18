<?php
// $Id: block.tpl.php,v 1.5 2009/02/18 14:28:25 webchick Exp $
?>
<div id="block-<?php print $block->module . '-' . $block->delta; ?>" class="clearfix block block-<?php print $block->module ?>">

<?php if (!empty($block->subject)): ?>
  <h2><?php print $block->subject ?></h2>
<?php endif;?>

  <div class="content"><?php print $block->content ?></div>
</div>

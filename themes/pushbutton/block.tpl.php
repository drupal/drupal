<?php
// $Id: block.tpl.php,v 1.2 2007/08/07 08:39:36 goba Exp $
?>
<div class="<?php print "block block-$block->module" ?>" id="<?php print "block-$block->module-$block->delta"; ?>">
  <div class="title"><h3><?php print $block->subject ?></h3></div>
  <div class="content"><?php print $block->content ?></div>
</div>
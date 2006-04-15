<div class="node<?php if ($sticky) { print " sticky"; } ?><?php if (!$status) { print " node-unpublished"; } ?>">
  <?php if ($page == 0): ?>
    <h2><a href="<?php print $node_url ?>" title="<?php print $title ?>"><?php print $title ?></a></h2>
  <?php endif; ?>
  <?php print $picture ?>

  <div class="info"><?php print $submitted ?><span class="terms"><?php print $terms ?></span></div>
  <div class="content">
    <?php print $content ?>
  </div>
<?php if ($links): ?>

    <?php if ($picture): ?>
      <br class='clear' />
    <?php endif; ?>
    <div class="links"><?php print $links ?></div>
<?php endif; ?>
</div>

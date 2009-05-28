<?php
// $Id$
?>
<div id="node-<?php print $node->nid; ?>" class="<?php print $classes ?>">

<?php print $picture ?>

<?php if ($page == 0): ?>
  <h2><a href="<?php print $node_url ?>" title="<?php print $title ?>"><?php print $title ?></a></h2>
<?php endif; ?>

  <?php if ($submitted): ?>
    <span class="submitted"><?php print $submitted; ?></span>
  <?php endif; ?>

  <div class="content clearfix">
    <?php print $content ?>
  </div>

  <div class="clearfix">
    <div class="meta">
    <?php if ($terms): ?>
      <div class="terms"><?php print $terms ?></div>
    <?php endif;?>
    </div>

    <?php if ($links): ?>
      <div class="links"><?php print $links; ?></div>
    <?php endif; ?>

    <?php print $comments; ?>

  </div>

</div>

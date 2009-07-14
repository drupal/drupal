<?php
// $Id$
?>
<div id="node-<?php print $node->nid; ?>" class="<?php print $classes ?>">

<?php print $picture ?>
<?php if ($page == 0): ?>
  <h2><a href="<?php print $node_url ?>"><?php print $title ?></a></h2>
<?php endif; ?>

  <?php if ($submitted): ?>
    <span class="submitted"><?php print $submitted; ?></span>
  <?php endif; ?>

  <div class="content clearfix">
    <?php hide($content['links']); hide($content['comments']); print render($content); ?>
  </div>

  <div class="clearfix">
    <div class="meta">
    <?php if (!empty($content['links']['terms'])): ?>
      <div class="terms"><?php print render($content['links']['terms']) ?></div>
    <?php endif;?>
    </div>

    <?php if (!empty($content['links'])): ?>
      <div class="links"><?php print render($content['links']) ?></div>
    <?php endif; ?>

    <?php print render($content['comments']); ?>

  </div>

</div>

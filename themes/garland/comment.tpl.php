<?php
// $Id: comment.tpl.php,v 1.15 2009/08/29 04:16:15 webchick Exp $
?>
<div class="<?php print $classes . ' ' . $zebra; ?>">

  <div class="clearfix">

    <span class="submitted"><?php print $date; ?> — <?php print $author; ?></span>

  <?php if ($new) : ?>
    <span class="new"><?php print drupal_ucfirst($new) ?></span>
  <?php endif; ?>

  <?php print $picture ?>

    <h3><?php print $title ?></h3>

    <div class="content">
      <?php hide($content['links']); print render($content); ?>
      <?php if ($signature): ?>
      <div class="clearfix">
        <div>—</div>
        <?php print $signature ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <?php print render($content['links']) ?>
</div>

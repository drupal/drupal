<?php
// $Id: comment.tpl.php,v 1.18 2009/10/17 05:50:29 webchick Exp $
?>
<div class="<?php print $classes . ' ' . $zebra; ?>"<?php print $attributes; ?>>

  <div class="clearfix">

  <?php if ($contextual_links): ?>
    <?php print render($contextual_links); ?>
  <?php endif; ?>

    <span class="submitted"><?php print $created; ?> — <?php print $author; ?></span>

  <?php if ($new) : ?>
    <span class="new"><?php print drupal_ucfirst($new) ?></span>
  <?php endif; ?>

  <?php print $picture ?>

    <h3<?php print $title_attributes; ?>><?php print $title ?></h3>

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

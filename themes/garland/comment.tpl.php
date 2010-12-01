<?php
// $Id: comment.tpl.php,v 1.21 2010/12/01 00:18:15 webchick Exp $
?>
<div class="<?php print $classes . ' ' . $zebra; ?>"<?php print $attributes; ?>>

  <div class="clearfix">

    <span class="submitted"><?php print $submitted ?></span>

  <?php if ($new) : ?>
    <span class="new"><?php print drupal_ucfirst($new) ?></span>
  <?php endif; ?>

  <?php print $picture ?>

    <?php print render($title_prefix); ?>
    <h3<?php print $title_attributes; ?>><?php print $title ?></h3>
    <?php print render($title_suffix); ?>

    <div class="content"<?php print $content_attributes; ?>>
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

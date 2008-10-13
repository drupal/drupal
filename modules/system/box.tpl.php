<?php
// $Id: box.tpl.php,v 1.4 2008/10/13 12:31:43 dries Exp $

/**
 * @file
 * Default theme implementation to display a box.
 *
 * Available variables:
 * - $title: Box title.
 * - $content: Box content.
 *
 * @see template_preprocess()
 */
?>
<div class="box">

<?php if ($title): ?>
  <h2><?php print $title ?></h2>
<?php endif; ?>

  <div class="content"><?php print $content ?></div>
</div>

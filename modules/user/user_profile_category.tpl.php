<?php
// $Id: user_profile_category.tpl.php,v 1.2 2007/08/07 08:39:36 goba Exp $
?>
<?php if ($element['#title']): ?>
  <h3><?php print $element['#title'] ?></h3>
<?php endif; ?>

<dl<?php (isset($element['#attributes']) ? print drupal_attributes($element['#attributes']) : '') ?>>
<?php print $element['#children'] ?>
</dl>

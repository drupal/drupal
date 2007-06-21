<?php if ($element['#title']): ?>
  <h3><?php print $element['#title'] ?></h3>
<?php endif; ?>

<dl<?php (isset($element['#attributes']) ? print drupal_attributes($element['#attributes']) : '') ?>>
<?php print $element['#children'] ?>
</dl>

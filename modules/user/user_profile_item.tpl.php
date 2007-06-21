<?php
  $attributes = isset($element['#attributes']) ? ' '. drupal_attributes($element['#attributes']) : '';
?>
<dt<?php print $attributes ?>><?php print $element['#title'] ?></dt>
<dd<?php print $attributes ?>><?php print $element['#value'] ?></dd>

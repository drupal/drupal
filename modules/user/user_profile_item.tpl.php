<?php
// $Id: user_profile_item.tpl.php,v 1.2 2007/08/07 08:39:36 goba Exp $

  $attributes = isset($element['#attributes']) ? ' '. drupal_attributes($element['#attributes']) : '';
?>
<dt<?php print $attributes ?>><?php print $element['#title'] ?></dt>
<dd<?php print $attributes ?>><?php print $element['#value'] ?></dd>

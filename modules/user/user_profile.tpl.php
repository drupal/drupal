<?php
// $Id: user_profile.tpl.php,v 1.2 2007/08/07 08:39:36 goba Exp $

/*
 * In order to customize user profiles, replace the HTML below with your own
 * wrapper and then sprinkle drupal_render($account->content[foo]) calls
 * where you need each piece of data to appear. Replace the 'foo' in the
 * example above with the element that is desired such as
 * drupal_render($account->content['summary']['member_for']).
 */

// Uncomment the line below to see what data is available in this template.
# print '<pre>'. check_plain(print_r($account->content, 1)) .'</pre>';

 ?>

<div class="profile">

<?php

// The following line should always be the last PHP in this file. Do not remove.
print drupal_render($account->content);

?>

</div>
<?php

/**
 * @file user-picture.tpl.php
 * Default theme implementation to present an picture configured for the
 * user's account.
 *
 * Available variables:
 * - $picture: Image set by the user or the site's default. Will be linked
 *   depending on the viewer's permission to view the users profile page.
 * - $account: Array of account information. Potentially unsafe. Be sure to
 *   check_plain() before use.
 *
 * @see template_preprocess_user_picture()
 */
?>
<div class="picture">
  <?php print $picture; ?>
</div>

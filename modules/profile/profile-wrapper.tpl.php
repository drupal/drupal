<?php

/**
 * @file
 * Default theme implementation for wrapping member listings and their
 * profiles.
 *
 * This template is used when viewing a list of users. It can be a general
 * list for viewing all users with the URL of "example.com/profile" or when
 * viewing a set of users who share a specific value for a profile such
 * as "example.com/profile/country/belgium".
 *
 * Available variables:
 * - $content: User account profiles iterated through profile-listing.tpl.php.
 * - $current_field: The named field being browsed. Provided here for context.
 *   The above example would result in "last_name". An alternate template name
 *   is also based on this, e.g., "profile-wrapper-last_name.tpl.php".
 *
 * @see template_preprocess_profile_wrapper()
 */
?>
<div id="profile">
  <?php print $content; ?>
</div>

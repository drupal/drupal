<?php
// $Id: user-profile-item.tpl.php,v 1.2 2007/08/07 08:39:36 goba Exp $

/**
 * @file user-profile-item.tpl.php
 * Default theme implementation to present profile items (values from user
 * account profile fields or modules).
 *
 * This template is used to loop through and render each field configured
 * for the user's account. It can also be the data from modules. The output is
 * grouped by categories.
 *
 * @see user-profile-category.tpl.php
 *      for the parent markup. Implemented as a definition list by default.
 * @see user-profile.tpl.php
 *      where all items and categories are collected and printed out.
 *
 * Available variables:
 * - $title: Field title for the profile item.
 * - $value: User defined value for the profile item or data from a module.
 * - $attributes: HTML attributes. Usually renders classes.
 *
 * @see template_preprocess_user_profile_item()
 */
?>
<dt<?php print $attributes; ?>><?php print $title; ?></dt>
<dd<?php print $attributes; ?>><?php print $value; ?></dd>

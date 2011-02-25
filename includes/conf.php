<?php

#
# Database settings:
#
#   Note that the $db_url variable gets parsed using PHP's built-in
#   URL parser (i.e. using the "parse_url()" function) so make sure
#   not to confuse the parser.  In practice, you should avoid using
#   special characters that are not used in "normal" URLs either.
#   That is, the use of ':', '/', '@', '?', '=' and '#', ''', '"',
#   and so on is likely to confuse the parser; use alpha-numerical
#   characters instead.

# $db_url = "pgsql://user:password@hostname/database";
# $db_url = "mysql://user:password@hostname/database";

$db_url = "mysql://drupal:drupal@localhost/drupal";

#
# PHP settings:
#
#   To see what PHP settings are known to work well, take a look at
#   the .htacesss file in Drupal's root directory.  If you get
#   unexecpted warnings or errors, double-check your PHP settings.

# If required, update PHP's include path to include your PEAR directory:
// ini_set("include_path", ".:/path/to/pear");

#
# Languages / translation / internationalization:
#
#   The first language listed in this associative array will
#   automatically become the default language.  You can add a language
#   but make sure your SQL table, called locales is updated
#   appropriately.
$languages = array("en" => "English");

#
# Custom navigation links:
#
# Custom navigation links override the standard page links offerred
# by most Drupal modules. Administrators may add/remove/reorder all
# links here.  These links are typically displayed in a row near the
# top of every page.
// $custom_links = array(
//  "<a href=\"index.php\">home</a>",
//  "<a href=\"module.php?mod=user\">school</a>",
//  "<a href=\"module.php?mod=blog\">work</a>");


?>

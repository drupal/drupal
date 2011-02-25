<?php

#
# Database settings:
#

# $db_url = "pgsql://user:password@hostname/database";
# $db_url = "mysql://user:password@hostname/database";

$db_url = "mysql://drupal:drupal@localhost/drupal";

#
# PHP settings:
#

# Avoid "page has expired" problems when browsing from your cache or history
# after having filled out a form:
// ini_set("session.cache_limiter", "");

# If required, update PHP's include path to include your PEAR directory:
// ini_set("include_path", ".:/path/to/pear");

#
# Languages / translation / internationalization:
#   The first language listed in this associative array will
#   automatically become the default language.  You can add a language
#   but make sure your SQL table, called locales is updated
#   appropriately.
$languages = array("en" => "English");

# Custom Navigation Links override the standard page links
# offerred by most Drupal modules. Administrators may
# add/remove/reorder nav links here. These links are typically
# displayed in a row near the top of every page.
# $custom_links = array(
#  "<a href=\"\index.php\">home</a>",
#  "<a href=\"\module.php?mod=user\">school</a>",
#  "<a href=\"\module.php?mod=blog\">work</a>");

?>
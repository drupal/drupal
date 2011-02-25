<?php

/**
 * @file
 * Drupal site-specific configuration file.
 */

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
#
#   To specify multiple connections to be used in your site (i.e. for
#   complex custom modules) you can also specify an associative array
#   of $db_url variables with the 'default' element used until otherwise
#   requested.

# $db_url = "mysql://user:password@hostname/database";
# $db_url = "pgsql://user:password@hostname/database";
$db_url = "mysql://drupal:drupal@localhost/drupal";

#   If $db_prefix is specified all database table names will be
#   prepended with this string.  Be sure to use valid database
#   characters only, usually alphanumeric and underscore.  If no
#   prefixes are desired, set to empty string "".
$db_prefix = "";

#
# Base URL:
#
#   The URL of your website's main page.  It is not allowed to have
#   a trailing slash; Drupal will add it for you.
#
$base_url = "http://localhost";

#
# PHP settings:
#
#   To see what PHP settings are known to work well, take a look at
#   the .htaccesss file in Drupal's root directory.  If you get
#   unexpected warnings or errors, double-check your PHP settings.

# If required, update PHP's include path to include your PEAR directory:
// ini_set("include_path", ".:/path/to/pear");

?>

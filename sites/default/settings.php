<?php
// $Id$

/**
 * @file
 * Drupal site-specific configuration file.
 *
 * The configuration file which will be loaded is based upon the rules
 * below.
 *
 * The configuration directory will be discovered by stripping the
 * website's hostname from left to right and pathname from right to
 * left.  The first configuration file found will be used and any
 * others will be ignored.  If no other configuration file is found
 * then the default configuration file at 'sites/default' will be used.
 *
 * Example for a ficticious site installed at
 * http://www.drupal.org/mysite/test/ the 'settings.php' is
 * searched in the following directories:
 *
 *  1. sites/www.drupal.org.mysite.test
 *  2. sites/drupal.org.mysite.test
 *  3. sites/org.mysite.test
 *
 *  4. sites/www.drupal.org.mysite
 *  5. sites/drupal.org.mysite
 *  6. sites/org.mysite
 *
 *  7. sites/www.drupal.org
 *  8. sites/drupal.org
 *  9. sites/org
 *
 * 10. sites/default
 */

/**
 * Database settings:
 *
 *   Note that the $db_url variable gets parsed using PHP's built-in
 *   URL parser (i.e. using the "parse_url()" function) so make sure
 *   not to confuse the parser.  In practice, you should avoid using
 *   special characters that are not used in "normal" URLs either.
 *   That is, the use of ':', '/', '@', '?', '=' and '#', ''', '"',
 *   and so on is likely to confuse the parser; use alpha-numerical
 *   characters instead.
 *
 *   To specify multiple connections to be used in your site (i.e. for
 *   complex custom modules) you can also specify an associative array
 *   of $db_url variables with the 'default' element used until otherwise
 *   requested.
 *
 *   If an optional $db_prefix is specified all database table names
 *   will be prepended with this string.  Be sure to use valid database
 *   characters only, usually alphanumeric and underscore.  If no
 *   prefixes are desired, set to empty string "".
 *
 *   Database URL format:
 *   $db_url = 'mysql://user:password@hostname/database';
 *   $db_url = 'pgsql://user:password@hostname/database';
 */
$db_url = 'mysql://drupal:drupal@localhost/drupal';
$db_prefix = '';

/**
 * Base URL:
 *
 *   The URL of your website's main page.  It is not allowed to have
 *   a trailing slash; Drupal will add it for you.
 */
$base_url = 'http://localhost';

/**
 * PHP settings:
 *
 *   To see what PHP settings are known to work well, read the PHP
 *   documentation at http://www.php.net/manual/en/ini.php#ini.list
 *   and take a look at the .htaccess file to see which settings are
 *   used there. Settings defined here should not be duplicated there
 *   to avoid conflict issues.
 */
ini_set('session.cache_expire',     200000);
ini_set('session.cache_limiter',    'none');
ini_set('session.gc_maxlifetime',   200000);
ini_set('session.cookie_lifetime',  2000000);
ini_set('session.save_handler',     'user');
ini_set('session.use_only_cookies', 1);
ini_set('session.use_trans_sid',    0);

/**
 * Variable overrides:
 *
 *   To override specific entries in the 'variable' table for this site,
 *   set them here.  You usually don't need to use this feature.  This is
 *   useful when used in a configuration file for a vhost or directory,
 *   rather than the default settings.php. Any configuration setting from
 *   the variable table can be given a new value.
 */
// $conf = array(
//   'site_name' => 'My Drupal site',
//   'theme_default' => 'pushbutton',
//   'anonymous' => 'Visitor'
// );

?>

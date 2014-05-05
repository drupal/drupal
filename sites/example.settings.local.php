<?php

/**
 * @file
 * Local development override configuration feature.
 *
 * To activate this feature, copy and rename it such that its path plus
 * filename is 'sites/yoursite.com/settings.local.php'. Then, go to the bottom
 * of 'sites/yoursite.com/settings.php' and uncomment the commented lines that
 * mention 'settings.local.php'.
 */

// Show all error messages, with backtrace information.
$config['system.logging']['error_level'] = 'verbose';

// Disable CSS and JS aggregation.
$config['system.performance']['css']['preprocess'] = FALSE;
$config['system.performance']['js']['preprocess'] = FALSE;

// Disable the render cache, by using the Null cache back-end.
$settings['cache']['bins']['render'] = 'cache.backend.null';

/**
 * Enable access to rebuild.php.
 *
 * This setting can be enabled to allow Drupal's php and database cached
 * storage to be cleared via the rebuild.php page. Access to this page can also
 * be gained by generating a query string from rebuild_token_calculator.sh and
 * using these parameters in a request to rebuild.php.
 */
$settings['rebuild_access'] = TRUE;

/**
 * Twig debugging:
 *
 * When debugging is enabled:
 * - The markup of each Twig template is surrounded by HTML comments that
 *   contain theming information, such as template file name suggestions.
 * - Note that this debugging markup will cause automated tests that directly
 *   check rendered HTML to fail. When running automated tests, 'twig_debug'
 *   should be set to FALSE.
 * - The dump() function can be used in Twig templates to output information
 *   about template variables.
 * - Twig templates are automatically recompiled whenever the source code
 *   changes (see twig_auto_reload below).
 *
 * Note: changes to this setting will only take effect once the cache is
 * cleared.
 *
 * For more information about debugging Twig templates, see
 * http://drupal.org/node/1906392.
 *
 * Not recommended in production environments (Default: FALSE).
 */
# $settings['twig_debug'] = TRUE;

/**
 * Twig auto-reload:
 *
 * Automatically recompile Twig templates whenever the source code changes. If
 * you don't provide a value for twig_auto_reload, it will be determined based
 * on the value of twig_debug.
 *
 * Note: changes to this setting will only take effect once the cache is
 * cleared.
 *
 * Not recommended in production environments (Default: NULL).
 */
# $settings['twig_auto_reload'] = TRUE;

/**
 * Twig cache:
 *
 * By default, Twig templates will be compiled and stored in the filesystem to
 * increase performance. Disabling the Twig cache will recompile the templates
 * from source each time they are used. In most cases the twig_auto_reload
 * setting above should be enabled rather than disabling the Twig cache.
 *
 * Note: changes to this setting will only take effect once the cache is
 * cleared.
 *
 * Not recommended in production environments (Default: TRUE).
 */
# $settings['twig_cache'] = FALSE;

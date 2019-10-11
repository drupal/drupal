<?php

/**
 * @file
 * These are the global variables that Drupal uses.
 */

use Drupal\Component\Utility\DeprecatedArray;

/**
 * The insecure base URL of the Drupal installation.
 *
 * @see \Drupal\Core\DrupalKernel::initializeRequestGlobals()
 */
global $base_insecure_url;

/**
 * The base path of the Drupal installation.
 *
 * This will at least default to '/'.
 *
 * @see \Drupal\Core\DrupalKernel::initializeRequestGlobals()
 */
global $base_path;

/**
 * The root URL of the host, excluding the path.
 *
 * @see \Drupal\Core\DrupalKernel::initializeRequestGlobals()
 */
global $base_root;

/**
 * The secure base URL of the Drupal installation.
 *
 * @see \Drupal\Core\DrupalKernel::initializeRequestGlobals()
 */
global $base_secure_url;

/**
 * The base URL of the Drupal installation.
 *
 * @see \Drupal\Core\DrupalKernel::initializeRequestGlobals()
 */
global $base_url;

/**
 * Allows defining of site-specific service providers for the Drupal kernel.
 *
 * To define a site-specific service provider class, use code like this:
 * @code
 * $GLOBALS['conf']['container_service_providers']['MyClassName'] = 'Drupal\My\Namespace\MyClassName';
 * @endcode
 *
 * @see \Drupal\Core\DrupalKernel::$serviceProviderClasses
 */
global $conf;

/**
 * Array of configuration overrides from the settings.php file.
 */
global $config;

/**
 * The location of file system directories used for site configuration data.
 *
 * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use
 *   \Drupal\Core\Site\Settings::get('config_sync_directory') instead.
 *
 * @see https://www.drupal.org/node/3018145
 */
global $config_directories;

/**
 * Store settings and profile information during installation process.
 *
 * @see install_drupal()
 */
global $install_state;

/**
 * Array of the number of items per page for each pager.
 *
 * The array index is the pager element index (0 by default).
 *
 * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Do not
 *   directly set or get values from this array. Use the pager.manager service
 *   instead.
 *
 * @see https://www.drupal.org/node/2779457
 * @see \Drupal\Core\Pager\PagerManagerInterface
 */
$GLOBALS['pager_limits'] = new DeprecatedArray([], 'Global variable $pager_limits is deprecated in drupal:8.8.0 and is removed in drupal:9.0.0. Use \Drupal\Core\Pager\PagerManagerInterface instead. See https://www.drupal.org/node/2779457');

/**
 * Array of current page numbers for each pager.
 *
 * The array index is the pager element index (0 by default).
 *
 * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Do not
 *   directly set or get values from this array. Use the pager.manager service
 *   instead.
 *
 * @see https://www.drupal.org/node/2779457
 * @see \Drupal\Core\Pager\PagerManagerInterface
 */
$GLOBALS['pager_page_array'] = new DeprecatedArray([], 'Global variable $pager_page_array is deprecated in drupal:8.8.0 and is removed in drupal:9.0.0. Use \Drupal\Core\Pager\PagerManagerInterface instead. See https://www.drupal.org/node/2779457');

/**
 * Array of the total number of pages for each pager.
 *
 * The array index is the pager element index (0 by default).
 *
 * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Do not
 *   directly set or get values from this array. Use the pager.manager service
 *   instead.
 *
 * @see https://www.drupal.org/node/2779457
 * @see \Drupal\Core\Pager\PagerManagerInterface
 */
$GLOBALS['pager_total'] = new DeprecatedArray([], 'Global variable $pager_total is deprecated in drupal:8.8.0 and is removed in drupal:9.0.0. Use \Drupal\Core\Pager\PagerManagerInterface instead. See https://www.drupal.org/node/2779457');

/**
 * Array of the total number of items for each pager.
 *
 * The array index is the pager element index (0 by default).
 *
 * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Do not
 *   directly set or get values from this array. Use the pager.manager service
 *   instead.
 *
 * @see https://www.drupal.org/node/2779457
 * @see \Drupal\Core\Pager\PagerManagerInterface
 */
$GLOBALS['pager_total_items'] = new DeprecatedArray([], 'Global variable $pager_total_items is deprecated in drupal:8.8.0 and is removed in drupal:9.0.0. Use \Drupal\Core\Pager\PagerManagerInterface instead. See https://www.drupal.org/node/2779457');

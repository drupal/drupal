<?php

/**
 * @file
 * These are the global variables that Drupal uses.
 */

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
 * @see drupal_install_config_directories()
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
 * @see pager_default_initialize()
 */
global $pager_limits;

/**
 * Array of current page numbers for each pager.
 *
 * The array index is the pager element index (0 by default).
 *
 * @see pager_default_initialize()
 */
global $pager_page_array;

/**
 * Array of the total number of pages for each pager.
 *
 * The array index is the pager element index (0 by default).
 *
 * @see pager_default_initialize()
 */
global $pager_total;

/**
 * Array of the total number of items for each pager.
 *
 * The array index is the pager element index (0 by default).
 *
 * @see pager_default_initialize()
 */
global $pager_total_items;

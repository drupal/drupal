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
 * Store settings and profile information during installation process.
 *
 * @see install_drupal()
 */
global $install_state;

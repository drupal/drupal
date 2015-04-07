<?php

/**
 * @file
 * Autoloader for Drupal PHPUnit testing.
 *
 * @see phpunit.xml.dist
 */

/**
 * Finds all valid extension directories recursively within a given directory.
 *
 * @param string $scan_directory
 *   The directory that should be recursively scanned.
 * @return array
 *   An associative array of extension directories found within the scanned
 *   directory, keyed by extension name.
 */
function drupal_phpunit_find_extension_directories($scan_directory) {
  $extensions = array();
  $dirs = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($scan_directory, \RecursiveDirectoryIterator::FOLLOW_SYMLINKS));
  foreach ($dirs as $dir) {
    if (strpos($dir->getPathname(), 'info.yml') !== FALSE) {
      // Cut off ".info.yml" from the filename for use as the extension name.
      $extensions[substr($dir->getFilename(), 0, -9)] = $dir->getPathInfo()->getRealPath();
    }
  }
  return $extensions;
}

/**
 * Returns directories under which contributed extensions may exist.
 *
 * @return array
 *   An array of directories under which contributed extensions may exist.
 */
function drupal_phpunit_contrib_extension_directory_roots() {
  $sites_path = __DIR__ . '/../../sites';
  $paths = array();
  // Note this also checks sites/../modules and sites/../profiles.
  foreach (scandir($sites_path) as $site) {
    $path = "$sites_path/$site";
    $paths[] = is_dir("$path/modules") ? realpath("$path/modules") : NULL;
    $paths[] = is_dir("$path/profiles") ? realpath("$path/profiles") : NULL;
  }
  return array_filter($paths);
}

/**
 * Registers the namespace for each extension directory with the autoloader.
 *
 * @param Composer\Autoload\ClassLoader $loader
 *   The supplied autoloader.
 * @param array $dirs
 *   An associative array of extension directories, keyed by extension name.
 */
function drupal_phpunit_register_extension_dirs(Composer\Autoload\ClassLoader $loader, $dirs) {
  foreach ($dirs as $extension => $dir) {
    if (is_dir($dir . '/src')) {
      // Register the PSR-4 directory for module-provided classes.
      $loader->addPsr4('Drupal\\' . $extension . '\\', $dir . '/src');
    }
    if (is_dir($dir . '/tests/src')) {
      // Register the PSR-4 directory for PHPUnit test classes.
      $loader->addPsr4('Drupal\\Tests\\' . $extension . '\\', $dir . '/tests/src');
    }
  }
}

// Start with classes in known locations.
$loader = require __DIR__ . '/../../autoload.php';
$loader->add('Drupal\\Tests', __DIR__);

// Scan for arbitrary extension namespaces from core and contrib.
$extension_roots = array_merge(array(
  __DIR__ . '/../modules',
  __DIR__ . '/../profiles',
), drupal_phpunit_contrib_extension_directory_roots());

$dirs = array_map('drupal_phpunit_find_extension_directories', $extension_roots);
$dirs = array_reduce($dirs, 'array_merge', array());
drupal_phpunit_register_extension_dirs($loader, $dirs);

// Set sane locale settings, to ensure consistent string, dates, times and
// numbers handling.
// @see \Drupal\Core\DrupalKernel::bootEnvironment()
setlocale(LC_ALL, 'C');

// Set the default timezone. While this doesn't cause any tests to fail, PHP
// complains if 'date.timezone' is not set in php.ini.
date_default_timezone_set('UTC');

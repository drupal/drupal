<?php

declare(strict_types=1);

namespace Drupal\Core\Runtime;

use Composer\Autoload\ClassLoader;

/**
 * Provides deprecation errors when the classloader is accessed.
 *
 * Allows making the classloader available in the `$GLOBAL['autoloader']` but
 * will trigger errors if any of its methods are accessed in this way.
 *
 * Since the path to our autoloader depends on where we're installed
 * (root/core vs root/vendor/drupal/core) we lazily traverse the filesystem for
 * the autoloader only if it's actually requested.
 */
class DeprecatedAutoloadAccess extends ClassLoader {

  /**
   * The lazy loaded classloader from autoload.php.
   */
  private static ?ClassLoader $actual = NULL;

  /**
   * {@inheritdoc}
   */
  public function getPrefixes() {
    @trigger_error('Accessing the \'autoloader\' through Drupal\'s provided global is deprecated in drupal:11.4.0 and will be unsupported in drupal:12.0.0. Load the vendor/autoload.php file or provide the global yourself instead. See https://www.drupal.org/node/3576336', E_USER_DEPRECATED);
    return self::getActualClassLoader()->getPrefixes();
  }

  /**
   * {@inheritdoc}
   */
  public function getPrefixesPsr4() {
    @trigger_error('Accessing the \'autoloader\' through Drupal\'s provided global is deprecated in drupal:11.4.0 and will be unsupported in drupal:12.0.0. Load the vendor/autoload.php file or provide the global yourself instead. See https://www.drupal.org/node/3576336', E_USER_DEPRECATED);
    return self::getActualClassLoader()->getPrefixesPsr4();
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackDirs() {
    @trigger_error('Accessing the \'autoloader\' through Drupal\'s provided global is deprecated in drupal:11.4.0 and will be unsupported in drupal:12.0.0. Load the vendor/autoload.php file or provide the global yourself instead. See https://www.drupal.org/node/3576336', E_USER_DEPRECATED);
    return self::getActualClassLoader()->getFallbackDirs();
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackDirsPsr4() {
    @trigger_error('Accessing the \'autoloader\' through Drupal\'s provided global is deprecated in drupal:11.4.0 and will be unsupported in drupal:12.0.0. Load the vendor/autoload.php file or provide the global yourself instead. See https://www.drupal.org/node/3576336', E_USER_DEPRECATED);
    return self::getActualClassLoader()->getFallbackDirsPsr4();
  }

  /**
   * {@inheritdoc}
   */
  public function getClassMap() {
    @trigger_error('Accessing the \'autoloader\' through Drupal\'s provided global is deprecated in drupal:11.4.0 and will be unsupported in drupal:12.0.0. Load the vendor/autoload.php file or provide the global yourself instead. See https://www.drupal.org/node/3576336', E_USER_DEPRECATED);
    return self::getActualClassLoader()->getClassMap();
  }

  /**
   * {@inheritdoc}
   */
  public function addClassMap(array $classMap) {
    @trigger_error('Accessing the \'autoloader\' through Drupal\'s provided global is deprecated in drupal:11.4.0 and will be unsupported in drupal:12.0.0. Load the vendor/autoload.php file or provide the global yourself instead. See https://www.drupal.org/node/3576336', E_USER_DEPRECATED);
    self::getActualClassLoader()->addClassMap($classMap);
  }

  /**
   * {@inheritdoc}
   */
  public function add($prefix, $paths, $prepend = FALSE) {
    @trigger_error('Accessing the \'autoloader\' through Drupal\'s provided global is deprecated in drupal:11.4.0 and will be unsupported in drupal:12.0.0. Load the vendor/autoload.php file or provide the global yourself instead. See https://www.drupal.org/node/3576336', E_USER_DEPRECATED);
    self::getActualClassLoader()->add($prefix, $paths, $prepend);
  }

  /**
   * {@inheritdoc}
   */
  public function addPsr4($prefix, $paths, $prepend = FALSE) {
    @trigger_error('Accessing the \'autoloader\' through Drupal\'s provided global is deprecated in drupal:11.4.0 and will be unsupported in drupal:12.0.0. Load the vendor/autoload.php file or provide the global yourself instead. See https://www.drupal.org/node/3576336', E_USER_DEPRECATED);
    self::getActualClassLoader()->addPsr4($prefix, $paths, $prepend);
  }

  /**
   * {@inheritdoc}
   */
  public function set($prefix, $paths) {
    @trigger_error('Accessing the \'autoloader\' through Drupal\'s provided global is deprecated in drupal:11.4.0 and will be unsupported in drupal:12.0.0. Load the vendor/autoload.php file or provide the global yourself instead. See https://www.drupal.org/node/3576336', E_USER_DEPRECATED);
    self::getActualClassLoader()->set($prefix, $paths);
  }

  /**
   * {@inheritdoc}
   */
  public function setPsr4($prefix, $paths) {
    @trigger_error('Accessing the \'autoloader\' through Drupal\'s provided global is deprecated in drupal:11.4.0 and will be unsupported in drupal:12.0.0. Load the vendor/autoload.php file or provide the global yourself instead. See https://www.drupal.org/node/3576336', E_USER_DEPRECATED);
    self::getActualClassLoader()->setPsr4($prefix, $paths);
  }

  /**
   * {@inheritdoc}
   */
  public function setUseIncludePath($useIncludePath) {
    @trigger_error('Accessing the \'autoloader\' through Drupal\'s provided global is deprecated in drupal:11.4.0 and will be unsupported in drupal:12.0.0. Load the vendor/autoload.php file or provide the global yourself instead. See https://www.drupal.org/node/3576336', E_USER_DEPRECATED);
    self::getActualClassLoader()->setUseIncludePath($useIncludePath);
  }

  /**
   * {@inheritdoc}
   */
  public function getUseIncludePath() {
    @trigger_error('Accessing the \'autoloader\' through Drupal\'s provided global is deprecated in drupal:11.4.0 and will be unsupported in drupal:12.0.0. Load the vendor/autoload.php file or provide the global yourself instead. See https://www.drupal.org/node/3576336', E_USER_DEPRECATED);
    return self::getActualClassLoader()->getUseIncludePath();
  }

  /**
   * {@inheritdoc}
   */
  public function setClassMapAuthoritative($classMapAuthoritative) {
    @trigger_error('Accessing the \'autoloader\' through Drupal\'s provided global is deprecated in drupal:11.4.0 and will be unsupported in drupal:12.0.0. Load the vendor/autoload.php file or provide the global yourself instead. See https://www.drupal.org/node/3576336', E_USER_DEPRECATED);
    self::getActualClassLoader()->setClassMapAuthoritative($classMapAuthoritative);
  }

  /**
   * {@inheritdoc}
   */
  public function isClassMapAuthoritative() {
    @trigger_error('Accessing the \'autoloader\' through Drupal\'s provided global is deprecated in drupal:11.4.0 and will be unsupported in drupal:12.0.0. Load the vendor/autoload.php file or provide the global yourself instead. See https://www.drupal.org/node/3576336', E_USER_DEPRECATED);
    return self::getActualClassLoader()->isClassMapAuthoritative();
  }

  /**
   * {@inheritdoc}
   */
  public function setApcuPrefix($apcuPrefix) {
    @trigger_error('Accessing the \'autoloader\' through Drupal\'s provided global is deprecated in drupal:11.4.0 and will be unsupported in drupal:12.0.0. Load the vendor/autoload.php file or provide the global yourself instead. See https://www.drupal.org/node/3576336', E_USER_DEPRECATED);
    self::getActualClassLoader()->setApcuPrefix($apcuPrefix);
  }

  /**
   * {@inheritdoc}
   */
  public function getApcuPrefix() {
    @trigger_error('Accessing the \'autoloader\' through Drupal\'s provided global is deprecated in drupal:11.4.0 and will be unsupported in drupal:12.0.0. Load the vendor/autoload.php file or provide the global yourself instead. See https://www.drupal.org/node/3576336', E_USER_DEPRECATED);
    return self::getActualClassLoader()->getApcuPrefix();
  }

  /**
   * {@inheritdoc}
   */
  public function register($prepend = FALSE) {
    @trigger_error('Accessing the \'autoloader\' through Drupal\'s provided global is deprecated in drupal:11.4.0 and will be unsupported in drupal:12.0.0. Load the vendor/autoload.php file or provide the global yourself instead. See https://www.drupal.org/node/3576336', E_USER_DEPRECATED);
    self::getActualClassLoader()->register($prepend);
  }

  /**
   * {@inheritdoc}
   */
  public function unregister() {
    @trigger_error('Accessing the \'autoloader\' through Drupal\'s provided global is deprecated in drupal:11.4.0 and will be unsupported in drupal:12.0.0. Load the vendor/autoload.php file or provide the global yourself instead. See https://www.drupal.org/node/3576336', E_USER_DEPRECATED);
    self::getActualClassLoader()->unregister();
  }

  /**
   * {@inheritdoc}
   */
  public function loadClass($class) {
    @trigger_error('Accessing the \'autoloader\' through Drupal\'s provided global is deprecated in drupal:11.4.0 and will be unsupported in drupal:12.0.0. Load the vendor/autoload.php file or provide the global yourself instead. See https://www.drupal.org/node/3576336', E_USER_DEPRECATED);
    return self::getActualClassLoader()->loadClass($class);
  }

  /**
   * {@inheritdoc}
   */
  public function findFile($class) {
    @trigger_error('Accessing the \'autoloader\' through Drupal\'s provided global is deprecated in drupal:11.4.0 and will be unsupported in drupal:12.0.0. Load the vendor/autoload.php file or provide the global yourself instead. See https://www.drupal.org/node/3576336', E_USER_DEPRECATED);
    return self::getActualClassLoader()->findFile($class);
  }

  /**
   * {@inheritdoc}
   */
  public static function getRegisteredLoaders() {
    @trigger_error('Accessing the \'autoloader\' through Drupal\'s provided global is deprecated in drupal:11.4.0 and will be unsupported in drupal:12.0.0. Load the vendor/autoload.php file or provide the global yourself instead. See https://www.drupal.org/node/3576336', E_USER_DEPRECATED);
    return static::getActualClassLoader()::getRegisteredLoaders();
  }

  /**
   * Lazy load the actual classloader once.
   *
   * @return \Composer\Autoload\ClassLoader
   *   The classloader for this project.
   */
  private static function getActualClassLoader() : ClassLoader {
    // Find the autoload.php.
    if (static::$actual === NULL) {
      // No matter whether we're in $root/core or in vendor/drupal/core, we can
      // always traverse up to find an autoload.php file.
      $dir = __DIR__;
      while (($parent = dirname($dir)) !== $dir) {
        if (file_exists("$dir/autoload.php")) {
          static::$actual = require "$dir/autoload.php";
          break;
        }
        // Move up one directory.
        $dir = $parent;
      }
    }
    // If we haven't found it by now then we're outside of a Drupal
    // installation.
    if (static::$actual === NULL) {
      throw new \RuntimeException("Unable to find autoload.php file.");
    }
    return static::$actual;
  }

}

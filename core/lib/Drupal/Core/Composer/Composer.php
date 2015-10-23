<?php

/**
 * @file
 * Contains \Drupal\Core\Composer\Composer.
 */

namespace Drupal\Core\Composer;

use Drupal\Component\PhpStorage\FileStorage;
use Composer\Script\Event;
use Composer\Installer\PackageEvent;

/**
 * Provides static functions for composer script events.
 *
 * @see https://getcomposer.org/doc/articles/scripts.md
 */
class Composer {

  protected static $packageToCleanup = [
    'behat/mink' => ['tests', 'driver-testsuite'],
    'behat/mink-browserkit-driver' => ['tests'],
    'behat/mink-goutte-driver' => ['tests'],
    'doctrine/cache' => ['tests'],
    'doctrine/collections' => ['tests'],
    'doctrine/common' => ['tests'],
    'doctrine/inflector' => ['tests'],
    'doctrine/instantiator' => ['tests'],
    'egulias/email-validator' => ['documentation', 'tests'],
    'fabpot/goutte' => ['Goutte/Tests'],
    'guzzlehttp/promises' => ['tests'],
    'guzzlehttp/psr7' => ['tests'],
    'jcalderonzumba/gastonjs' => ['docs'],
    'jcalderonzumba/gastonjs' => ['examples'],
    'jcalderonzumba/gastonjs' => ['tests'],
    'masterminds/html5' => ['test'],
    'mikey179/vfsStream' => ['src/test'],
    'phpdocumentor/reflection-docblock' => ['tests'],
    'phpunit/php-code-coverage' => ['tests'],
    'phpunit/php-timer' => ['tests'],
    'phpunit/php-token-stream' => ['tests'],
    'phpunit/phpunit' => ['tests'],
    'phpunit/php-mock-objects' => ['tests'],
    'sebastian/comparator' => ['tests'],
    'sebastian/diff' => ['tests'],
    'sebastian/environment' => ['tests'],
    'sebastian/exporter' => ['tests'],
    'sebastian/global-state' => ['tests'],
    'sebastian/recursion-context' => ['tests'],
    'stack/builder' => ['tests'],
    'symfony/browser-kit' => ['Tests'],
    'symfony/class-loader' => ['Tests'],
    'symfony/console' => ['Tests'],
    'symfony/css-selector' => ['Tests'],
    'symfony/debug' => ['Tests'],
    'symfony/dependency-injection' => ['Tests'],
    'symfony/dom-crawler' => ['Tests'],
    // @see \Drupal\Tests\Component\EventDispatcher\ContainerAwareEventDispatcherTest
    // 'symfony/event-dispatcher' => ['Tests'],
    'symfony/http-foundation' => ['Tests'],
    'symfony/http-kernel' => ['Tests'],
    'symfony/process' => ['Tests'],
    'symfony/psr-http-message-bridge' => ['Tests'],
    'symfony/routing' => ['Tests'],
    'symfony/serializer' => ['Tests'],
    'symfony/translation' => ['Tests'],
    'symfony/validator' => ['Tests'],
    'symfony/yaml' => ['Tests'],
    'symfony-cmf/routing' => ['Test', 'Tests'],
    'twig/twig' => ['doc', 'ext', 'test'],
  ];

  /**
   * Add vendor classes to composers static classmap.
   */
  public static function preAutoloadDump(Event $event) {
    $composer = $event->getComposer();
    $package = $composer->getPackage();
    $autoload = $package->getAutoload();
    $autoload['classmap'] = array_merge($autoload['classmap'], array(
      'vendor/symfony/http-foundation/Request.php',
      'vendor/symfony/http-foundation/ParameterBag.php',
      'vendor/symfony/http-foundation/FileBag.php',
      'vendor/symfony/http-foundation/ServerBag.php',
      'vendor/symfony/http-foundation/HeaderBag.php',
      'vendor/symfony/http-kernel/HttpKernel.php',
      'vendor/symfony/http-kernel/HttpKernelInterface.php',
      'vendor/symfony/http-kernel/TerminableInterface.php',
    ));
    $package->setAutoload($autoload);
  }

  /**
   * Ensures that .htaccess and web.config files are present in Composer root.
   *
   * @param \Composer\Script\Event $event
   */
  public static function ensureHtaccess(Event $event) {

    // The current working directory for composer scripts is where you run
    // composer from.
    $vendor_dir = $event->getComposer()->getConfig()->get('vendor-dir');

    // Prevent access to vendor directory on Apache servers.
    $htaccess_file = $vendor_dir . '/.htaccess';
    if (!file_exists($htaccess_file)) {
      file_put_contents($htaccess_file, FileStorage::htaccessLines(TRUE) . "\n");
    }

    // Prevent access to vendor directory on IIS servers.
    $webconfig_file = $vendor_dir . '/web.config';
    if (!file_exists($webconfig_file)) {
      $lines = <<<EOT
<configuration>
  <system.webServer>
    <authorization>
      <deny users="*">
    </authorization>
  </system.webServer>
</configuration>
EOT;
      file_put_contents($webconfig_file, $lines . "\n");
    }
  }

  /**
   * Remove possibly problematic test files from vendored projects.
   *
   * @param \Composer\Script\Event $event
   */
  public static function vendorTestCodeCleanup(PackageEvent $event) {
    $vendor_dir = $event->getComposer()->getConfig()->get('vendor-dir');
    $op = $event->getOperation();
    if ($op->getJobType() == 'update') {
      $package = $op->getTargetPackage();
    }
    else {
      $package = $op->getPackage();
    }
    $package_key = static::findPackageKey($package->getName());
    if ($package_key) {
      foreach (static::$packageToCleanup[$package_key] as $path) {
        $dir_to_remove = $vendor_dir . '/' . $package_key . '/' . $path;
        if (is_dir($dir_to_remove)) {
          if (!static::deleteRecursive($dir_to_remove)) {
            throw new \RuntimeException(sprintf("Failure removing directory '%s' in package '%s'.", $path, $package->getPrettyName()));
          }
        }
        else {
          throw new \RuntimeException(sprintf("The directory '%s' in package '%s' does not exist.", $path, $package->getPrettyName()));
        }
      }
    }
  }

  /**
   * Find the array key for a given package name with a case-insensitive search.
   *
   * @param string $package_name
   *   The package name from composer. This is always already lower case.
   *
   * @return NULL|string
   *   The string key, or NULL if none was found.
   */
  protected static function findPackageKey($package_name) {
    $package_key = NULL;
    // In most cases the package name is already used as the array key.
    if (isset(static::$packageToCleanup[$package_name])) {
      $package_key = $package_name;
    }
    else {
      // Handle any mismatch in case between the package name and array key.
      // For example, the array key 'mikey179/vfsStream' needs to be found
      // when composer returns a package name of 'mikey179/vfsstream'.
      foreach (static::$packageToCleanup as $key => $dirs) {
        if (strtolower($key) === $package_name) {
          $package_key = $key;
          break;
        }
      }
    }
    return $package_key;
  }

  /**
   * Helper method to remove directories and the files they contain.
   *
   * @param string $path
   *   The directory or file to remove. It must exist.
   *
   * @return bool
   *   TRUE on success or FALSE on failure.
   */
  protected static function deleteRecursive($path) {
    if (is_file($path) || is_link($path)) {
      return unlink($path);
    }
    $success = TRUE;
    $dir = dir($path);
    while (($entry = $dir->read()) !== FALSE) {
      if ($entry == '.' || $entry == '..') {
        continue;
      }
      $entry_path = $path . '/' . $entry;
      $success = static::deleteRecursive($entry_path) && $success;
    }
    $dir->close();

    return rmdir($path) && $success;
  }

}

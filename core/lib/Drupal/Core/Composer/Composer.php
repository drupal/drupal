<?php

namespace Drupal\Core\Composer;

use Drupal\Component\PhpStorage\FileStorage;
use Composer\Script\Event;
use Composer\Installer\PackageEvent;
use Composer\Semver\Constraint\Constraint;

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
    'drupal/coder' => ['coder_sniffer/Drupal/Test', 'coder_sniffer/DrupalPractice/Test'],
    'doctrine/cache' => ['tests'],
    'doctrine/collections' => ['tests'],
    'doctrine/common' => ['tests'],
    'doctrine/inflector' => ['tests'],
    'doctrine/instantiator' => ['tests'],
    'egulias/email-validator' => ['documentation', 'tests'],
    'fabpot/goutte' => ['Goutte/Tests'],
    'guzzlehttp/promises' => ['tests'],
    'guzzlehttp/psr7' => ['tests'],
    'jcalderonzumba/gastonjs' => ['docs', 'examples', 'tests'],
    'jcalderonzumba/mink-phantomjs-driver' => ['tests'],
    'masterminds/html5' => ['test'],
    'mikey179/vfsStream' => ['src/test'],
    'paragonie/random_compat' => ['tests'],
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
    'symfony/validator' => ['Tests', 'Resources'],
    'symfony/yaml' => ['Tests'],
    'symfony-cmf/routing' => ['Test', 'Tests'],
    'twig/twig' => ['doc', 'ext', 'test'],
  ];

  /**
   * Add vendor classes to Composer's static classmap.
   */
  public static function preAutoloadDump(Event $event) {
    // We need the root package so we can add our classmaps to its loader.
    $package = $event->getComposer()->getPackage();
    // We need the local repository so that we can query and see if it's likely
    // that our files are present there.
    $repository = $event->getComposer()->getRepositoryManager()->getLocalRepository();
    // This is, essentially, a null constraint. We only care whether the package
    // is present in vendor/ yet, but findPackage() requires it.
    $constraint = new Constraint('>', '');
    // Check for our packages, and then optimize them if they're present.
    if ($repository->findPackage('symfony/http-foundation', $constraint)) {
      $autoload = $package->getAutoload();
      $autoload['classmap'] = array_merge($autoload['classmap'], [
        'vendor/symfony/http-foundation/Request.php',
        'vendor/symfony/http-foundation/ParameterBag.php',
        'vendor/symfony/http-foundation/FileBag.php',
        'vendor/symfony/http-foundation/ServerBag.php',
        'vendor/symfony/http-foundation/HeaderBag.php',
      ]);
      $package->setAutoload($autoload);
    }
    if ($repository->findPackage('symfony/http-kernel', $constraint)) {
      $autoload = $package->getAutoload();
      $autoload['classmap'] = array_merge($autoload['classmap'], [
        'vendor/symfony/http-kernel/HttpKernel.php',
        'vendor/symfony/http-kernel/HttpKernelInterface.php',
        'vendor/symfony/http-kernel/TerminableInterface.php',
      ]);
      $package->setAutoload($autoload);
    }
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
   * @param \Composer\Installer\PackageEvent $event
   *   A PackageEvent object to get the configured composer vendor directories
   *   from.
   */
  public static function vendorTestCodeCleanup(PackageEvent $event) {
    $vendor_dir = $event->getComposer()->getConfig()->get('vendor-dir');
    $io = $event->getIO();
    $op = $event->getOperation();
    if ($op->getJobType() == 'update') {
      $package = $op->getTargetPackage();
    }
    else {
      $package = $op->getPackage();
    }
    $package_key = static::findPackageKey($package->getName());
    $message = sprintf("    Processing <comment>%s</comment>", $package->getPrettyName());
    if ($io->isVeryVerbose()) {
      $io->write($message);
    }
    if ($package_key) {
      foreach (static::$packageToCleanup[$package_key] as $path) {
        $dir_to_remove = $vendor_dir . '/' . $package_key . '/' . $path;
        $print_message = $io->isVeryVerbose();
        if (is_dir($dir_to_remove)) {
          if (static::deleteRecursive($dir_to_remove)) {
            $message = sprintf("      <info>Removing directory '%s'</info>", $path);
          }
          else {
            // Always display a message if this fails as it means something has
            // gone wrong. Therefore the message has to include the package name
            // as the first informational message might not exist.
            $print_message = TRUE;
            $message = sprintf("      <error>Failure removing directory '%s'</error> in package <comment>%s</comment>.", $path, $package->getPrettyName());
          }
        }
        else {
          // If the package has changed or the --prefer-dist version does not
          // include the directory this is not an error.
          $message = sprintf("      Directory '%s' does not exist", $path);
        }
        if ($print_message) {
          $io->write($message);
        }
      }

      if ($io->isVeryVerbose()) {
        // Add a new line to separate this output from the next package.
        $io->write("");
      }
    }
  }

  /**
   * Find the array key for a given package name with a case-insensitive search.
   *
   * @param string $package_name
   *   The package name from composer. This is always already lower case.
   *
   * @return string|null
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

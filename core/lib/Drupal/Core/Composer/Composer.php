<?php

namespace Drupal\Core\Composer;

use Composer\Script\Event;
use Composer\Semver\Constraint\Constraint;

/**
 * Provides static functions for composer script events.
 *
 * @see https://getcomposer.org/doc/articles/scripts.md
 */
class Composer {

  /**
   * Add vendor classes to Composer's static classmap.
   *
   * @param \Composer\Script\Event $event
   *   The event.
   */
  public static function preAutoloadDump(Event $event) {
    // Get the configured vendor directory.
    $vendor_dir = $event->getComposer()->getConfig()->get('vendor-dir');

    // We need the root package so we can add our classmaps to its loader.
    $package = $event->getComposer()->getPackage();
    // We need the local repository so that we can query and see if it's likely
    // that our files are present there.
    $repository = $event->getComposer()->getRepositoryManager()->getLocalRepository();
    // This is, essentially, a null constraint. We only care whether the package
    // is present in the vendor directory yet, but findPackage() requires it.
    $constraint = new Constraint('>', '');
    // It's possible that there is no classmap specified in a custom project
    // composer.json file. We need one so we can optimize lookup for some of our
    // dependencies.
    $autoload = $package->getAutoload();
    if (!isset($autoload['classmap'])) {
      $autoload['classmap'] = [];
    }
    // Check for packages used prior to the default classloader being able to
    // use APCu and optimize them if they're present.
    // @see \Drupal\Core\DrupalKernel::boot()
    if ($repository->findPackage('symfony/http-foundation', $constraint)) {
      $autoload['classmap'] = array_merge($autoload['classmap'], [
        $vendor_dir . '/symfony/http-foundation/Request.php',
        $vendor_dir . '/symfony/http-foundation/RequestStack.php',
        $vendor_dir . '/symfony/http-foundation/ParameterBag.php',
        $vendor_dir . '/symfony/http-foundation/FileBag.php',
        $vendor_dir . '/symfony/http-foundation/ServerBag.php',
        $vendor_dir . '/symfony/http-foundation/HeaderBag.php',
        $vendor_dir . '/symfony/http-foundation/HeaderUtils.php',
      ]);
    }
    if ($repository->findPackage('symfony/http-kernel', $constraint)) {
      $autoload['classmap'] = array_merge($autoload['classmap'], [
        $vendor_dir . '/symfony/http-kernel/HttpKernel.php',
        $vendor_dir . '/symfony/http-kernel/HttpKernelInterface.php',
        $vendor_dir . '/symfony/http-kernel/TerminableInterface.php',
      ]);
    }
    if ($repository->findPackage('symfony/dependency-injection', $constraint)) {
      $autoload['classmap'] = array_merge($autoload['classmap'], [
        $vendor_dir . '/symfony/dependency-injection/ContainerInterface.php',
      ]);
    }
    if ($repository->findPackage('psr/container', $constraint)) {
      $autoload['classmap'] = array_merge($autoload['classmap'], [
        $vendor_dir . '/psr/container/src/ContainerInterface.php',
      ]);
    }
    $package->setAutoload($autoload);
  }

  /**
   * Fires the drupal-phpunit-upgrade script event if necessary.
   *
   * @param \Composer\Script\Event $event
   *   The event.
   *
   * @internal
   */
  public static function upgradePHPUnit(Event $event) {
    $repository = $event->getComposer()->getRepositoryManager()->getLocalRepository();
    // This is, essentially, a null constraint. We only care whether the package
    // is present in the vendor directory yet, but findPackage() requires it.
    $constraint = new Constraint('>', '');
    $phpunit_package = $repository->findPackage('phpunit/phpunit', $constraint);
    if (!$phpunit_package) {
      // There is nothing to do. The user is probably installing using the
      // --no-dev flag.
      return;
    }

    // If the PHP version is 8.4 or above and PHPUnit is less than version 11
    // call the drupal-phpunit-upgrade script to upgrade PHPUnit.
    if (!static::upgradePHPUnitCheck($phpunit_package->getVersion())) {
      $event->getComposer()
        ->getEventDispatcher()
        ->dispatchScript('drupal-phpunit-upgrade');
    }
  }

  /**
   * Determines if PHPUnit needs to be upgraded.
   *
   * This method is located in this file because it is possible that it is
   * called before the autoloader is available.
   *
   * @param string $phpunit_version
   *   The PHPUnit version string.
   *
   * @return bool
   *   TRUE if the PHPUnit needs to be upgraded, FALSE if not.
   *
   * @internal
   */
  public static function upgradePHPUnitCheck($phpunit_version) {
    return !(version_compare(PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION, '8.4') >= 0 && version_compare($phpunit_version, '11.0') < 0);
  }

}

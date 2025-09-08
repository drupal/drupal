<?php

namespace Drupal\Core\Composer;

use Composer\Script\Event;
use Composer\Semver\Constraint\Constraint;
use Drupal\Composer\Plugin\Scaffold\Plugin;

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
   *
   * @deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Use
   *   \Drupal\Composer\Plugin\Scaffold\Plugin::preAutoloadDump() instead.
   *
   * @see https://www.drupal.org/node/3531162
   */
  public static function preAutoloadDump(Event $event) {
    @trigger_error('\Drupal\Core\Composer\Composer::preAutoloadDump() is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Use \Drupal\Composer\Plugin\Scaffold\Plugin::preAutoloadDump() instead. See https://www.drupal.org/node/3531162', E_USER_DEPRECATED);
    Plugin::preAutoloadDump($event);
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

<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\Database;

/**
 * Legacy database tests.
 *
 * @group Database
 * @group legacy
 */
class DatabaseLegacyTest extends DatabaseTestBase {

  /**
   * Tests deprecation of install.inc database driver functions.
   */
  public function testDeprecatedInstallFunctions(): void {
    include_once $this->root . '/core/includes/install.inc';
    $this->expectDeprecation('drupal_detect_database_types() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use DatabaseDriverList::getList() instead. See https://www.drupal.org/node/3258175');
    $this->expectDeprecation('drupal_get_database_types() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use DatabaseDriverList::getList() instead. See https://www.drupal.org/node/3258175');
    $installableDriverNames = [];
    foreach (Database::getDriverList()->getInstallableList() as $driver => $driverExtension) {
      $installableDriverNames[$driverExtension->getDriverName()] = $driverExtension->getInstallTasks()->name();
    }
    $this->assertEquals($installableDriverNames, drupal_detect_database_types());
  }

}

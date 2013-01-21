<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Cache\DatabaseBackendUnitTest.
 */

namespace Drupal\system\Tests\Cache;

use Drupal\Core\Cache\DatabaseBackend;

/**
 * Tests DatabaseBackend using GenericCacheBackendUnitTestBase.
 */
class DatabaseBackendUnitTest extends GenericCacheBackendUnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Database backend',
      'description' => 'Unit test of the database backend using the generic cache unit test base.',
      'group' => 'Cache',
    );
  }

  /**
   * Creates a new instance of DatabaseBackend.
   *
   * @return
   *   A new DatabaseBackend object.
   */
  protected function createCacheBackend($bin) {
    return new DatabaseBackend($bin);
  }

  /**
   * Installs system schema.
   */
  public function setUpCacheBackend() {
    // Calling drupal_install_schema() entails a call to module_invoke, for which
    // we need a ModuleHandler. Register one to the container.
    // @todo Use DrupalUnitTestBase.
    $this->container->register('module_handler', 'Drupal\Core\Extension\ModuleHandler');

    drupal_install_schema('system');
  }

  /**
   * Uninstalls system schema.
   */
  public function tearDownCacheBackend() {
    drupal_uninstall_schema('system');
  }
}

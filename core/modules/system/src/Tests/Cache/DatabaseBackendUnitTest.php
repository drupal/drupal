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

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system');

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
    return new DatabaseBackend($this->container->get('database'), $bin);
  }

}

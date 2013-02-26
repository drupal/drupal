<?php

/**
 * Definition of Drupal\system\Tests\System\SystemInitTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\Core\Database\Database;
use \Drupal\simpletest\UnitTestBase;
use \Symfony\Component\HttpFoundation\Request;

/**
 * Tests system_init().
 */
class SystemInitTest extends UnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'System Init',
      'description' => 'Tests the system_init function in system.module.',
      'group' => 'System',
    );
  }

  /**
   * Tests that system_init properly ignores slaves when requested.
   */
  function testSystemInitIgnoresSlaves() {
    // Clone the master credentials to a slave connection.
    // Note this will result in two independent connection objects that happen
    // to point to the same place.
    $connection_info = Database::getConnectionInfo('default');
    Database::addConnectionInfo('default', 'slave', $connection_info['default']);

    db_ignore_slave();
    system_init();

    $db1 = Database::getConnection('default', 'default');
    $db2 = Database::getConnection('slave', 'default');

    $this->assertIdentical($db1, $db2, 'System Init ignores slaves when requested.');
  }
}

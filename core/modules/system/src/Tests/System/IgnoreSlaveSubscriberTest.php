<?php

/**
 * Contains \Drupal\system\Tests\System\IgnoreSlaveSubscriberTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\Core\Database\Database;
use Drupal\Core\EventSubscriber\SlaveDatabaseIgnoreSubscriber;
use Drupal\Core\DrupalKernel;
use Drupal\simpletest\UnitTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Tests the event subscriber that disables the slave database.
 */
class IgnoreSlaveSubscriberTest extends UnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Slave database ignoring event listener',
      'description' => 'Tests that SlaveDatabaseIgnoreSubscriber functions correctly.',
      'group' => 'System',
    );
  }

  /**
   * Tests \Drupal\Core\EventSubscriber\SlaveDatabaseIgnoreSubscriber::checkSlaveServer().
   */
  function testSystemInitIgnoresSlaves() {
    // Clone the master credentials to a slave connection.
    // Note this will result in two independent connection objects that happen
    // to point to the same place.
    $connection_info = Database::getConnectionInfo('default');
    Database::addConnectionInfo('default', 'slave', $connection_info['default']);

    db_ignore_slave();
    $kernel = new DrupalKernel('testing', drupal_classloader(), FALSE);
    $event = new GetResponseEvent($kernel, Request::create('http://example.com'), HttpKernelInterface::MASTER_REQUEST);
    $subscriber = new SlaveDatabaseIgnoreSubscriber();
    $subscriber->checkSlaveServer($event);

    $db1 = Database::getConnection('default', 'default');
    $db2 = Database::getConnection('slave', 'default');

    $this->assertIdentical($db1, $db2, 'System Init ignores slaves when requested.');
  }
}

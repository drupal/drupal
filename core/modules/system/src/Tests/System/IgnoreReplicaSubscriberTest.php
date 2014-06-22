<?php

/**
 * Contains \Drupal\system\Tests\System\IgnoreReplicaSubscriberTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\Core\Database\Database;
use Drupal\Core\EventSubscriber\ReplicaDatabaseIgnoreSubscriber;
use Drupal\Core\DrupalKernel;
use Drupal\simpletest\UnitTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Tests the event subscriber that disables the replica database.
 */
class IgnoreReplicaSubscriberTest extends UnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Replica database ignoring event listener',
      'description' => 'Tests that ReplicaDatabaseIgnoreSubscriber functions correctly.',
      'group' => 'System',
    );
  }

  /**
   * Tests \Drupal\Core\EventSubscriber\ReplicaDatabaseIgnoreSubscriber::checkReplicaServer().
   */
  function testSystemInitIgnoresSecondaries() {
    // Clone the master credentials to a replica connection.
    // Note this will result in two independent connection objects that happen
    // to point to the same place.
    $connection_info = Database::getConnectionInfo('default');
    Database::addConnectionInfo('default', 'replica', $connection_info['default']);

    db_ignore_replica();
    $kernel = new DrupalKernel('testing', drupal_classloader(), FALSE);
    $event = new GetResponseEvent($kernel, Request::create('http://example.com'), HttpKernelInterface::MASTER_REQUEST);
    $subscriber = new ReplicaDatabaseIgnoreSubscriber();
    $subscriber->checkReplicaServer($event);

    $db1 = Database::getConnection('default', 'default');
    $db2 = Database::getConnection('replica', 'default');

    $this->assertIdentical($db1, $db2, 'System Init ignores secondaries when requested.');
  }
}

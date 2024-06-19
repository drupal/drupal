<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\Database;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Tests that ReplicaKillSwitch functions correctly.
 *
 * @group system
 */
class ReplicaKillSwitchTest extends KernelTestBase {

  /**
   * Tests database.replica_kill_switch service.
   */
  public function testSystemInitIgnoresSecondaries(): void {
    // Clone the master credentials to a replica connection.
    // Note this will result in two independent connection objects that happen
    // to point to the same place.
    $connection_info = Database::getConnectionInfo('default');
    Database::addConnectionInfo('default', 'replica', $connection_info['default']);

    /** @var \Drupal\Core\Database\ReplicaKillSwitch $service */
    $service = \Drupal::service('database.replica_kill_switch');
    $service->trigger();
    $class_loader = require $this->root . '/autoload.php';
    $kernel = new DrupalKernel('testing', $class_loader, FALSE);
    $event = new RequestEvent($kernel, Request::create('http://example.com'), HttpKernelInterface::MAIN_REQUEST);
    $service->checkReplicaServer($event);

    $db1 = Database::getConnection('default', 'default');
    $db2 = Database::getConnection('replica', 'default');

    $this->assertSame($db1, $db2, 'System Init ignores secondaries when requested.');

    // Makes sure that session value set right.
    $session = \Drupal::service('session');
    $this->assertTrue($session->has('ignore_replica_server'));
    $expected = \Drupal::time()->getRequestTime() + Settings::get('maximum_replication_lag', 300);
    $this->assertEquals($expected, $session->get('ignore_replica_server'));
  }

}

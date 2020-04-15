<?php

namespace Drupal\Tests\Core\Database;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Log;
use Drupal\Tests\Core\Database\Stub\StubConnection;
use Drupal\Tests\Core\Database\Stub\StubPDO;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the Log class.
 *
 * @group Database
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 * @coversDefaultClass \Drupal\Core\Database\Log
 */
class LogTest extends UnitTestCase {

  /**
   * Tests that a log called by a custom database driver returns proper caller.
   *
   * @covers ::findCaller
   */
  public function testContribDriverLog() {
    Database::addConnectionInfo('default', 'default', [
      'driver' => 'test',
      'namespace' => 'Drupal\Tests\Core\Database\Stub',
    ]);

    $pdo = $this->prophesize(StubPDO::class)->reveal();
    $result = (new StubConnection($pdo, []))->testLogCaller();
    $this->assertSame([
      'file' => __FILE__,
      'line' => 33,
      'function' => 'testContribDriverLog',
      'class' => 'Drupal\Tests\Core\Database\LogTest',
      'type' => '->',
      'args' => [],
    ], $result);

    // Test calling the database log from outside of database code.
    $result = (new Log())->findCaller();
    $this->assertSame([
      'file' => __FILE__,
      'line' => 44,
      'function' => 'testContribDriverLog',
      'class' => 'Drupal\Tests\Core\Database\LogTest',
      'type' => '->',
      'args' => [],
    ], $result);
  }

}

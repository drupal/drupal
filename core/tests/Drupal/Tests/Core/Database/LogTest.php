<?php

declare(strict_types=1);

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
 * @group legacy
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
  public function testContribDriverLog(): void {
    Database::addConnectionInfo('default', 'default', [
      'driver' => 'test',
      'namespace' => 'Drupal\Tests\Core\Database\Stub',
    ]);

    $this->expectDeprecation('Drupal\Core\Database\Log::findCaller() is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use Connection::findCallerFromDebugBacktrace(). See https://www.drupal.org/node/3328053');
    $this->expectDeprecation('Drupal\Core\Database\Log::getDebugBacktrace() is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3328053');
    $this->expectDeprecation('Drupal\Core\Database\Log::removeDatabaseEntries() is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use Connection::removeDatabaseEntriesFromDebugBacktrace(). See https://www.drupal.org/node/3328053');
    $pdo = $this->prophesize(StubPDO::class)->reveal();
    $result = (new StubConnection($pdo, []))->testLogCaller();
    $this->assertSame([
      'file' => __FILE__,
      'line' => 39,
      'function' => 'testContribDriverLog',
      'class' => 'Drupal\Tests\Core\Database\LogTest',
      'type' => '->',
      'args' => [],
    ], $result);

    // Test calling the database log from outside of database code.
    $this->expectDeprecation('Drupal\Core\Database\Log::findCaller() is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use Connection::findCallerFromDebugBacktrace(). See https://www.drupal.org/node/3328053');
    $this->expectDeprecation('Drupal\Core\Database\Log::getDebugBacktrace() is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3328053');
    $this->expectDeprecation('Drupal\Core\Database\Log::removeDatabaseEntries() is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use Connection::removeDatabaseEntriesFromDebugBacktrace(). See https://www.drupal.org/node/3328053');
    $result = (new Log())->findCaller();
    $this->assertSame([
      'file' => __FILE__,
      'line' => 53,
      'function' => 'testContribDriverLog',
      'class' => 'Drupal\Tests\Core\Database\LogTest',
      'type' => '->',
      'args' => [],
    ], $result);
  }

}

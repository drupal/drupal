<?php

declare(strict_types=1);

namespace Drupal\Tests;

use Drupal\TestTools\Extension\Dump\DebugDump;

/**
 * Tests for the UnitTestCase class.
 *
 * @group Tests
 */
class UnitTestCaseTest extends UnitTestCase {

  /**
   * Tests the dump() function in a test run in the same process.
   */
  public function testVarDumpSameProcess(): void {
    // Dump some variables.
    $object = (object) [
      'Aldebaran' => 'Betelgeuse',
    ];
    dump($object);
    dump('Alpheratz');

    $dumpString = json_encode(DebugDump::getDumps());

    $this->assertStringContainsString('Aldebaran', $dumpString);
    $this->assertStringContainsString('Betelgeuse', $dumpString);
    $this->assertStringContainsString('Alpheratz', $dumpString);
  }

  /**
   * Tests the dump() function in a test run in a separate process.
   *
   * @runInSeparateProcess
   */
  public function testVarDumpSeparateProcess(): void {
    // Dump some variables.
    $object = (object) [
      'Denebola' => 'Aspidiske',
    ];
    dump($object);
    dump('Schedar');

    $dumpString = json_encode(DebugDump::getDumps());

    $this->assertStringContainsString('Denebola', $dumpString);
    $this->assertStringContainsString('Aspidiske', $dumpString);
    $this->assertStringContainsString('Schedar', $dumpString);

    // We should also find the dump of the previous test.
    $this->assertStringContainsString('Aldebaran', $dumpString);
    $this->assertStringContainsString('Betelgeuse', $dumpString);
    $this->assertStringContainsString('Alpheratz', $dumpString);
  }

}

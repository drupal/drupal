<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\Query\Select;
use Drupal\Core\Database\Query\SelectExtender;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests SelectInterface::getRange().
 */
#[RunTestsInSeparateProcesses]
#[Group('Database')]
#[CoversClass(Select::class)]
#[CoversClass(SelectExtender::class)]
final class SelectGetRangeTest extends DatabaseTestBase {

  /**
   * Ensures getRange() returns NULL by default.
   */
  public function testGetRangeReferenceOnSelect(): void {
    $raw = $this->connection
      ->select('test', 't')
      ->fields('t', ['name'])
      ->orderBy('name')
      ->execute()
      ->fetchCol();

    $query = $this->connection
      ->select('test', 't')
      ->fields('t', ['name'])
      ->orderBy('name');

    $range = &$query->getRange();
    $this->assertNull($range);

    $query->range(1, 3);
    $this->assertSame(['start' => 1, 'length' => 3], $query->getRange());

    $range2 = &$query->getRange();
    $range2['start'] = 2;
    $this->assertEquals(2, $range['start']);

    $names = $query->execute()->fetchCol();
    $expected = array_slice($raw, 2, 3);
    $this->assertSame($expected, $names);
  }

  /**
   * Ensures SelectExtender::getRange() delegates and returns a live reference.
   */
  public function testGetRangeReferenceOnSelectExtender(): void {
    $raw = $this->connection
      ->select('test', 't')
      ->fields('t', ['name'])
      ->orderBy('name')
      ->execute()
      ->fetchCol();

    $base_query = $this->connection
      ->select('test', 't')
      ->fields('t', ['name'])
      ->orderBy('name');

    /** @var \Drupal\Core\Database\Query\SelectExtender $extender */
    $extender = $base_query->extend(SelectExtender::class);

    $range = &$extender->getRange();
    $this->assertNull($range);

    $base_query->range(2, 2);
    $this->assertSame(['start' => 2, 'length' => 2], $extender->getRange());

    $range2 = &$extender->getRange();
    $range2['length'] = 1;
    $this->assertEquals(1, $range['length']);

    $names = $extender->execute()->fetchCol();
    $expected = array_slice($raw, 2, 1);
    $this->assertSame($expected, $names);
  }

}

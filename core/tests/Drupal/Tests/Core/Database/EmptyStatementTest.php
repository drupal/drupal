<?php

namespace Drupal\Tests\Core\Database;

use Drupal\Core\Database\StatementEmpty;
use Drupal\Core\Database\StatementInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the empty pseudo-statement class.
 *
 * @group Database
 */
class EmptyStatementTest extends UnitTestCase {

  /**
   * Tests that the empty result set behaves as empty.
   */
  public function testEmpty() {
    $result = new StatementEmpty();

    $this->assertInstanceOf(StatementInterface::class, $result);
    $this->assertNull($result->fetchObject(), 'Null result returned.');
  }

  /**
   * Tests that the empty result set iterates safely.
   */
  public function testEmptyIteration() {
    $result = new StatementEmpty();
    $this->assertSame(0, iterator_count($result), 'Empty result set should not iterate.');
  }

  /**
   * Tests that the empty result set mass-fetches in an expected way.
   */
  public function testEmptyFetchAll() {
    $result = new StatementEmpty();

    $this->assertEquals($result->fetchAll(), [], 'Empty array returned from empty result set.');
  }

  /**
   * Tests accessing deprecated properties.
   *
   * @group legacy
   */
  public function testGetDeprecatedProperties(): void {
    $statement = new StatementEmpty();
    $this->expectDeprecation('%s$allowRowCount should not be accessed in drupal:9.2.0 and will error in drupal:10.0.0.%s');
    $this->assertFalse($statement->allowRowCount);
  }

  /**
   * Tests writing deprecated properties.
   *
   * @group legacy
   */
  public function testSetDeprecatedProperties(): void {
    $statement = new StatementEmpty();
    $this->expectDeprecation('%s$allowRowCount should not be written in drupal:9.2.0 and will error in drupal:10.0.0.%s');
    $statement->allowRowCount = TRUE;
  }

}

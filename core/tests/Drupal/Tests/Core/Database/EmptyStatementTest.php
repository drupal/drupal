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
   *
   * @group legacy
   */
  public function testEmpty() {
    $this->expectDeprecation('\Drupal\Core\Database\StatementEmpty is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. There is no replacement. Use mocked StatementInterface classes in tests if needed. See https://www.drupal.org/node/3201283');
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

}

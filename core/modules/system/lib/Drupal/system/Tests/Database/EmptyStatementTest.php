<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Database\EmptyStatementTest.
 */

namespace Drupal\system\Tests\Database;

use Drupal\Core\Database\StatementEmpty;
use Drupal\Core\Database\StatementInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the empty pseudo-statement class.
 */
class EmptyStatementTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => t('Empty statement'),
      'description' => t('Test the empty pseudo-statement class.'),
      'group' => t('Database'),
    );
  }

  /**
   * Test that the empty result set behaves as empty.
   */
  function testEmpty() {
    $result = new StatementEmpty();

    $this->assertTrue($result instanceof StatementInterface, t('Class implements expected interface'));
    $this->assertNull($result->fetchObject(), t('Null result returned.'));
  }

  /**
   * Test that the empty result set iterates safely.
   */
  function testEmptyIteration() {
    $result = new StatementEmpty();

    foreach ($result as $record) {
      $this->fail(t('Iterating empty result set should not iterate.'));
      return;
    }

    $this->pass(t('Iterating empty result set skipped iteration.'));
  }

  /**
   * Test that the empty result set mass-fetches in an expected way.
   */
  function testEmptyFetchAll() {
    $result = new StatementEmpty();

    $this->assertEqual($result->fetchAll(), array(), t('Empty array returned from empty result set.'));
  }
}

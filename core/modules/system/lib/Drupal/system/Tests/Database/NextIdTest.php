<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Database\NextIdTest.
 */

namespace Drupal\system\Tests\Database;

use Drupal\simpletest\WebTestBase;

/**
 * Checks the sequences API.
 */
class NextIdTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Sequences API',
      'description' => 'Test the secondary sequences API.',
      'group' => 'Database',
    );
  }

  /**
   * Tests that the sequences API works.
   */
  function testDbNextId() {
    $first = db_next_id();
    $second = db_next_id();
    // We can test for exact increase in here because we know there is no
    // other process operating on these tables -- normally we could only
    // expect $second > $first.
    $this->assertEqual($first + 1, $second, 'The second call from a sequence provides a number increased by one.');
    $result = db_next_id(1000);
    $this->assertEqual($result, 1001, 'Sequence provides a larger number than the existing ID.');
  }
}

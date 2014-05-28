<?php

/**
 * @file
 * Definition of Drupal\system\Tests\System\IndexPhpTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\simpletest\WebTestBase;

/**
 * Test the handling of requests containing 'index.php'.
 */
class IndexPhpTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Index.php handling',
      'description' => "Test the handling of requests containing 'index.php'.",
      'group' => 'System',
    );
  }

  function setUp() {
    parent::setUp();
  }

  /**
   * Test index.php handling.
   */
  function testIndexPhpHandling() {
    $index_php = $GLOBALS['base_url'] . '/index.php';

    $this->drupalGet($index_php, array('external' => TRUE));
    $this->assertResponse(200, 'Make sure index.php returns a valid page.');

    $this->drupalGet($index_php .'/user', array('external' => TRUE));
    $this->assertResponse(200, 'Make sure index.php/user returns a valid page.');
  }
}

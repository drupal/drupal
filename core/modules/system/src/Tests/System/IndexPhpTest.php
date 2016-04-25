<?php

namespace Drupal\system\Tests\System;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the handling of requests containing 'index.php'.
 *
 * @group system
 */
class IndexPhpTest extends WebTestBase {
  protected function setUp() {
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

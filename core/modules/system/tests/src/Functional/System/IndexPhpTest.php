<?php

namespace Drupal\Tests\system\Functional\System;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the handling of requests containing 'index.php'.
 *
 * @group system
 */
class IndexPhpTest extends BrowserTestBase {
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Test index.php handling.
   */
  public function testIndexPhpHandling() {
    $index_php = $GLOBALS['base_url'] . '/index.php';

    $this->drupalGet($index_php, ['external' => TRUE]);
    $this->assertResponse(200, 'Make sure index.php returns a valid page.');

    $this->drupalGet($index_php . '/user', ['external' => TRUE]);
    $this->assertResponse(200, 'Make sure index.php/user returns a valid page.');
  }

}

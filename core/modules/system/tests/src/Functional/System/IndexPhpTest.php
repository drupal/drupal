<?php

namespace Drupal\Tests\system\Functional\System;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the handling of requests containing 'index.php'.
 *
 * @group system
 */
class IndexPhpTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
  }

  /**
   * Tests index.php handling.
   */
  public function testIndexPhpHandling() {
    $index_php = $GLOBALS['base_url'] . '/index.php';

    $this->drupalGet($index_php, ['external' => TRUE]);
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet($index_php . '/user', ['external' => TRUE]);
    $this->assertSession()->statusCodeEquals(200);
  }

}

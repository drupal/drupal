<?php

namespace Drupal\Tests\classy\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the classy theme.
 *
 * @group classy
 * @group legacy
 */
class ClassyTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * Tests that the Classy theme always adds its message CSS.
   *
   * @see classy.info.yml
   */
  public function testRegressionMissingMessagesCss() {
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('classy/css/components/messages.css');
  }

}

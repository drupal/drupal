<?php

namespace Drupal\Tests\bartik\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Bartik theme.
 *
 * @group bartik
 * @group legacy
 */
class BartikTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'bartik';

  /**
   * Tests that the Bartik theme always adds its message CSS files.
   *
   * @see bartik.libraries.yml
   * @see classy.info.yml
   */
  public function testRegressionMissingMessagesCss() {
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('bartik/css/components/messages.css');
    $this->assertSession()->responseContains('bartik/css/classy/components/messages.css');
  }

}

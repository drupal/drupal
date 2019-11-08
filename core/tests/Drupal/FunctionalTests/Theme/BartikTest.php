<?php

namespace Drupal\FunctionalTests\Theme;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Bartik theme.
 *
 * @group bartik
 */
class BartikTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'bartik';

  /**
   * Tests that the Bartik theme always adds its message CSS and Classy's.
   *
   * @see bartik.libraries.yml
   * @see classy.info.yml
   */
  public function testRegressionMissingMessagesCss() {
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('bartik/css/components/messages.css');
    $this->assertSession()->responseContains('classy/css/components/messages.css');
  }

}

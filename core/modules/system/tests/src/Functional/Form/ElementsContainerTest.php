<?php

namespace Drupal\Tests\system\Functional\Form;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the container form element for expected behavior.
 *
 * @group Form
 */
class ElementsContainerTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['form_test'];

  /**
   * Tests the #optional container property.
   */
  public function testOptionalContainerElements() {
    $this->drupalGet('form-test/optional-container');
    $assertSession = $this->assertSession();
    $assertSession->elementNotExists('css', 'div.empty_optional');
    $assertSession->elementExists('css', 'div.empty_nonoptional');
    $assertSession->elementExists('css', 'div.nonempty_optional');
    $assertSession->elementExists('css', 'div.nonempty_nonoptional');
  }

}

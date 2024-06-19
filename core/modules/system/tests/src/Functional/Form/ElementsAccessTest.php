<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Form;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests access control for form elements.
 *
 * @group Form
 */
class ElementsAccessTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['form_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Ensures that child values are still processed when #access = FALSE.
   */
  public function testAccessFalse(): void {
    $this->drupalGet('form_test/vertical-tabs-access');
    $this->submitForm([], 'Submit');
    $this->assertSession()->pageTextNotContains('This checkbox inside a vertical tab does not have its default value.');
    $this->assertSession()->pageTextNotContains('This textfield inside a vertical tab does not have its default value.');
    $this->assertSession()->pageTextNotContains('This checkbox inside a fieldset does not have its default value.');
    $this->assertSession()->pageTextNotContains('This checkbox inside a container does not have its default value.');
    $this->assertSession()->pageTextNotContains('This checkbox inside a nested container does not have its default value.');
    $this->assertSession()->pageTextNotContains('This checkbox inside a vertical tab whose fieldset access is allowed does not have its default value.');
    $this->assertSession()->pageTextContains('The form submitted correctly.');
  }

}

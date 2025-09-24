<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Form;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests access control for form elements.
 */
#[Group('Form')]
#[RunTestsInSeparateProcesses]
class ElementsAccessTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
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

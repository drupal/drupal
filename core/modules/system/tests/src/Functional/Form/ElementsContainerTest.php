<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Form;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the container form element for expected behavior.
 */
#[Group('Form')]
#[RunTestsInSeparateProcesses]
class ElementsContainerTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['form_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the #optional container property.
   */
  public function testOptionalContainerElements(): void {
    $this->drupalGet('form-test/optional-container');
    $assertSession = $this->assertSession();
    $assertSession->elementNotExists('css', 'div.empty_optional');
    $assertSession->elementExists('css', 'div.empty_non_optional');
    $assertSession->elementExists('css', 'div.nonempty_optional');
    $assertSession->elementExists('css', 'div.nonempty_non_optional');
  }

}

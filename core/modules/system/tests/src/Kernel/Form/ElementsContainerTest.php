<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Form;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the container form element for expected behavior.
 */
#[Group('Form')]
#[RunTestsInSeparateProcesses]
class ElementsContainerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'form_test'];

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

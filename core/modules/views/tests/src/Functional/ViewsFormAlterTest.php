<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Functional;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests hook_form_BASE_FORM_ID_alter for a ViewsForm.
 */
#[Group('views')]
#[RunTestsInSeparateProcesses]
class ViewsFormAlterTest extends ViewTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['views_form_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests hook_form_BASE_FORM_ID_alter for a ViewsForm.
   */
  public function testViewsFormAlter(): void {
    $this->drupalLogin($this->createUser(['access media overview']));
    $this->drupalGet('admin/content/media');
    $count = $this->container->get('state')->get('hook_form_BASE_FORM_ID_alter_count');
    $this->assertEquals(1, $count, 'hook_form_BASE_FORM_ID_alter was invoked only once');
  }

}

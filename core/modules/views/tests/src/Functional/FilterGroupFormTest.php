<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Functional;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the filter group form.
 */
#[Group('views')]
#[RunTestsInSeparateProcesses]
class FilterGroupFormTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static array $testViews = ['test_empty_group_form'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node'];

  /**
   * Tests a view with a grouped filter with empty options.
   */
  public function testFilterGroupFormEmpty(): void {
    $this->drupalGet('/test-empty-groups');
    $this->assertSession()->statusCodeEquals(200);
  }

}

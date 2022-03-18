<?php

namespace Drupal\Tests\user\Functional\Views;

use Drupal\Tests\views\Functional\ViewTestBase;

/**
 * Tests the changed field.
 *
 * @group user
 */
class UserChangedTest extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['views_ui', 'user_test_views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_user_changed'];

  protected function setUp($import_test_views = TRUE, $modules = ['user_test_views']): void {
    parent::setUp($import_test_views, $modules);

    $this->enableViewsTestModule();
  }

  /**
   * Tests changed field.
   */
  public function testChangedField() {
    $path = 'test_user_changed';

    $options = [];

    $this->drupalGet($path, $options);

    $this->assertSession()->pageTextContains('Updated date: ' . date('Y-m-d', REQUEST_TIME));
  }

}

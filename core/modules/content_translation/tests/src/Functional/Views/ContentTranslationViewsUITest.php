<?php

namespace Drupal\Tests\content_translation\Functional\Views;

use Drupal\Tests\views_ui\Functional\UITestBase;

/**
 * Tests the views UI when content_translation is enabled.
 *
 * @group content_translation
 */
class ContentTranslationViewsUITest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['content_translation'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the views UI.
   */
  public function testViewsUI() {
    $this->drupalGet('admin/structure/views/view/test_view/edit');
    $this->assertTitle('Test view (Views test data) | Drupal');
  }

}

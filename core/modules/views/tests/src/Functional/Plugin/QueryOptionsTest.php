<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Functional\Plugin;

use Drupal\Tests\views\Functional\ViewTestBase;

/**
 * Tests setting the query options.
 *
 * @group views
 */
class QueryOptionsTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'views_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test that query overrides are stored.
   */
  public function testStoreQuerySettingsOverride(): void {
    // Show the default display so the override selection is shown.
    \Drupal::configFactory()->getEditable('views.settings')->set('ui.show.default_display', TRUE)->save();

    $admin_user = $this->drupalCreateUser([
      'administer views',
      'administer site configuration',
    ]);
    $this->drupalLogin($admin_user);

    $edit = [];
    $this->drupalGet('admin/structure/views/view/test_view/edit');
    $this->submitForm($edit, 'Add Page');

    $this->drupalGet('admin/structure/views/nojs/display/test_view/page_1/query');
    $this->assertSession()->checkboxNotChecked('query[options][distinct]');
    $edit = [
      'override[dropdown]' => 'page_1',
      'query[options][distinct]' => 1,
    ];
    $this->submitForm($edit, 'Apply');
    $this->drupalGet('admin/structure/views/nojs/display/test_view/page_1/query');
    $this->assertSession()->checkboxChecked('query[options][distinct]');
    $edit = [
      'query[options][query_comment]' => 'comment',
      'query[options][query_tags]' => 'query_tag, another_tag',
    ];
    $this->submitForm($edit, 'Apply');
    $this->drupalGet('admin/structure/views/nojs/display/test_view/page_1/query');
    $this->assertSession()->checkboxChecked('query[options][distinct]');
    $this->assertSession()->fieldValueEquals('query[options][query_comment]', 'comment');
    $this->assertSession()->fieldValueEquals('query[options][query_tags]', 'query_tag, another_tag');
  }

}

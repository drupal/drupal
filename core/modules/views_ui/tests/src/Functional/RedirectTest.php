<?php

declare(strict_types=1);

namespace Drupal\Tests\views_ui\Functional;

/**
 * Tests the redirecting after saving a views.
 *
 * @group views_ui
 */
class RedirectTest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view', 'test_redirect_view'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the redirecting.
   */
  public function testRedirect(): void {
    $view_name = 'test_view';

    $random_destination = $this->randomMachineName();
    $edit_path = "admin/structure/views/view/$view_name/edit";

    // Verify that the user gets redirected to the expected page defined in the
    // destination.
    $this->drupalGet($edit_path, ['query' => ['destination' => $random_destination]]);
    $this->submitForm([], 'Save');
    $this->assertSession()->addressEquals($random_destination);

    // Setup a view with a certain page display path. If you change the path
    // but have the old URL in the destination the user should be redirected to
    // the new path.
    $view_name = 'test_redirect_view';
    $new_path = $this->randomMachineName();

    $edit_path = "admin/structure/views/view/$view_name/edit";
    $path_edit_path = "admin/structure/views/nojs/display/$view_name/page_1/path";

    $this->drupalGet($path_edit_path);
    $this->submitForm(['path' => $new_path], 'Apply');
    $this->drupalGet($edit_path, ['query' => ['destination' => 'test-redirect-view']]);
    $this->submitForm([], 'Save');
    // Verify that the user gets redirected to the expected page after changing
    // the URL of a page display.
    $this->assertSession()->addressEquals($new_path);
  }

}

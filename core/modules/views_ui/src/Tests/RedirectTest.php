<?php

namespace Drupal\views_ui\Tests;

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
   * Tests the redirecting.
   */
  public function testRedirect() {
    $view_name = 'test_view';

    $random_destination = $this->randomMachineName();
    $edit_path = "admin/structure/views/view/$view_name/edit";

    $this->drupalPostForm($edit_path, [], t('Save'), ['query' => ['destination' => $random_destination]]);
    $this->assertUrl($random_destination, [], 'Make sure the user got redirected to the expected page defined in the destination.');

    // Setup a view with a certain page display path. If you change the path
    // but have the old url in the destination the user should be redirected to
    // the new path.
    $view_name = 'test_redirect_view';
    $new_path = $this->randomMachineName();

    $edit_path = "admin/structure/views/view/$view_name/edit";
    $path_edit_path = "admin/structure/views/nojs/display/$view_name/page_1/path";

    $this->drupalPostForm($path_edit_path, ['path' => $new_path], t('Apply'));
    $this->drupalPostForm($edit_path, [], t('Save'), ['query' => ['destination' => 'test-redirect-view']]);
    $this->assertUrl($new_path, [], 'Make sure the user got redirected to the expected page after changing the URL of a page display.');
  }

}

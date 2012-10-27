<?php

/**
 * @file
 * Definition of Drupal\views\tests\UI\RedirectTest.
 */

namespace Drupal\views\Tests\UI;

/**
 * Tests the redirecting after saving a views.
 */
class RedirectTest extends UITestBase {

  public static function getInfo() {
    return array(
      'name' => 'Redirect',
      'description' => 'Tests the redirecting after saving a views',
      'group' => 'Views UI',
    );
  }

  /**
   * Tests the redirecting.
   */
  public function testRedirect() {
    $view = $this->getBasicView();

    $random_destination = $this->randomName();
    $edit_path = "admin/structure/views/view/{$view->storage->get('name')}/edit";

    $this->drupalPost($edit_path, array(), t('Save') , array('query' => array('destination' => $random_destination)));
    $this->assertUrl($random_destination, array(), 'Make sure the user got redirected to the expected page defined in the destination.');

    // Setup a view with a certain page display path. If you change the path
    // but have the old url in the destination the user should be redirected to
    // the new path.
    $view = views_get_view('test_redirect_view');
    $random_destination = $this->randomName();
    $new_path = $this->randomName();

    $edit_path = "admin/structure/views/view/{$view->storage->get('name')}/edit";
    $path_edit_path = "admin/structure/views/nojs/display/{$view->storage->get('name')}/page_1/path";

    $this->drupalPost($path_edit_path, array('path' => $new_path), t('Apply'));
    $this->drupalPost($edit_path, array(), t('Save'), array('query' => array('destination' => 'test-redirect-view')));
    $this->assertUrl($new_path, array(), 'Make sure the user got redirected to the expected page after changing the url of a page display.');
  }

}

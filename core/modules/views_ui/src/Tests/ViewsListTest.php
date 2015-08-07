<?php

/**
 * @file
 * Contains \Drupal\views_ui\Tests\ViewsListTest.
 */

namespace Drupal\views_ui\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\views\Entity\View;
use Drupal\views\Views;

/**
 * Tests the views list.
 *
 * @group views_ui
 */
class ViewsListTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views_ui');

  /**
   * Tests that the views list does not use a pager.
   */
  public function testViewsListLimit() {
    // Login.
    $user = $this->createUser(['administer views']);
    $this->drupalLogin($user);

    // Check if we can access the main views admin page.
    $this->drupalGet('admin/structure/views');
    $this->assertText(t('Add new view'));

    // Count default views to be subtracted from the limit.
    $views = count(Views::getEnabledViews());

    // Create multiples views.
    $limit = 51;
    $values = $this->config('views.view.test_view_storage')->get();
    for ($i = 1; $i <= $limit - $views; $i++) {
      $values['id'] = 'test_view_storage_new' . $i;
      unset($values['uuid']);
      $created = View::create($values);
      $created->save();
    }
    $this->drupalGet('admin/structure/views');

    // Check that all the rows are listed.
    $this->assertEqual(count($this->xpath('//tbody/tr[contains(@class,"views-ui-list-enabled")]')), $limit);
  }

}

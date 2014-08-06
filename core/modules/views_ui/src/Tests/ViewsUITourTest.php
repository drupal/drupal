<?php

/**
 * @file
 * Contains \Drupal\views_ui\Tests\ViewsUITourTest.
 */

namespace Drupal\views_ui\Tests;

use Drupal\tour\Tests\TourTestBase;

/**
 * Tests the Views UI tour.
 *
 * @group views_ui
 */
class ViewsUITourTest extends TourTestBase {

  /**
   * An admin user with administrative permissions for views.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views_ui', 'tour');

  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser(array('administer views', 'access tour'));
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests views_ui tour tip availability.
   */
  public function testViewsUiTourTips() {
    // Create a basic view that shows all content, with a page and a block
    // display.
    $view['label'] = $this->randomMachineName(16);
    $view['id'] = strtolower($this->randomMachineName(16));
    $view['page[create]'] = 1;
    $view['page[path]'] = $this->randomMachineName(16);
    $this->drupalPostForm('admin/structure/views/add', $view, t('Save and edit'));
    $this->assertTourTips();
  }

}

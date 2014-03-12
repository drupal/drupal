<?php

/**
 * @file
 * Contains \Drupal\views_ui\Tests\ViewsUITourTest.
 */

namespace Drupal\views_ui\Tests;

use Drupal\tour\Tests\TourTestBase;

/**
 * Tests tour functionality.
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

  public static function getInfo() {
    return array(
      'name' => 'Views UI tour tests',
      'description' => 'Tests the Views UI tour.',
      'group' => 'Tour',
    );
  }

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
    $view['label'] = $this->randomName(16);
    $view['id'] = strtolower($this->randomName(16));
    $view['page[create]'] = 1;
    $view['page[path]'] = $this->randomName(16);
    $this->drupalPostForm('admin/structure/views/add', $view, t('Save and edit'));
    $this->assertTourTips();
  }

}

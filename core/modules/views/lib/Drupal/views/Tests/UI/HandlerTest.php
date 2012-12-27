<?php

/**
 * @file
 * Contains \Drupal\views\Tests\UI\HandlerTest.
 */

namespace Drupal\views\Tests\UI;

use Drupal\views\ViewExecutable;

/**
 * Tests some generic handler UI functionality.
 *
 * @see \Drupal\views\Plugin\views\HandlerBase
 */
class HandlerTest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  public static function getInfo() {
    return array(
      'name' => 'Handler test',
      'description' => 'Tests handler UI for views.',
      'group' => 'Views UI'
    );
  }

  /**
   * Tests UI CRUD.
   */
  public function testUICRUD() {
    $handler_types = ViewExecutable::viewsHandlerTypes();
    foreach ($handler_types as $type => $type_info) {
      // Test adding handlers.
      $add_handler_url = "admin/structure/views/nojs/add-item/test_view/default/$type";

      // Area handler types need to use a different handler.
      if (in_array($type, array('header', 'footer', 'empty'))) {
        $this->drupalPost($add_handler_url, array('name[views.area]' => TRUE), t('Add and configure @handler', array('@handler' => $type_info['ltitle'])));
        $edit_handler_url = "admin/structure/views/nojs/config-item/test_view/default/$type/area";
      }
      elseif ($type == 'relationship') {
        // @todo Add a views_test_data relationship handler to test.
        continue;
      }
      else {
        $this->drupalPost($add_handler_url, array('name[views_test_data.job]' => TRUE), t('Add and configure @handler', array('@handler' => $type_info['ltitle'])));
        $edit_handler_url = "admin/structure/views/nojs/config-item/test_view/default/$type/job";
      }

      $this->assertUrl($edit_handler_url, array(), 'The user got redirected to the handler edit form.');
      $this->drupalPost(NULL, array(), t('Apply'));

      $this->assertUrl('admin/structure/views/view/test_view/edit', array(), 'The user got redirected to the views edit form.');

      $this->assertLinkByHref($edit_handler_url, 0, 'The handler edit link appears in the UI.');

      $this->drupalPost($edit_handler_url, array(), t('Remove'));
      $this->assertNoLinkByHref($edit_handler_url, 0, 'The handler edit link does not appears in the UI after removing.');
    }
  }

}

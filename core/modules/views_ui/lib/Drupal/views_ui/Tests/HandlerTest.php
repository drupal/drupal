<?php

/**
 * @file
 * Contains \Drupal\views_ui\Tests\HandlerTest.
 */

namespace Drupal\views_ui\Tests;

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
  public static $testViews = array('test_view_empty');

  public static function getInfo() {
    return array(
      'name' => 'Handler test',
      'description' => 'Tests handler UI for views.',
      'group' => 'Views UI'
    );
  }

  /**
   * Overrides \Drupal\views\Tests\ViewTestBase::schemaDefinition().
   *
   * Adds a uid column to test the relationships.
   */
  protected function schemaDefinition() {
    $schema = parent::schemaDefinition();

    $schema['views_test_data']['fields']['uid'] = array(
      'description' => "The {users}.uid of the author of the beatle entry.",
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'default' => 0
    );

    return $schema;
  }

  /**
   * Overrides \Drupal\views\Tests\ViewTestBase::viewsData().
   *
   * Adds a relationship for the uid column.
   */
  protected function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['uid'] = array(
      'title' => t('UID'),
      'help' => t('The test data UID'),
      'relationship' => array(
        'id' => 'standard',
        'base' => 'users',
        'base field' => 'uid'
      )
    );

    return $data;
  }

  /**
   * Tests UI CRUD.
   */
  public function testUICRUD() {
    $handler_types = ViewExecutable::viewsHandlerTypes();
    foreach ($handler_types as $type => $type_info) {
      // Test adding handlers.
      $add_handler_url = "admin/structure/views/nojs/add-item/test_view_empty/default/$type";

      // Area handler types need to use a different handler.
      if (in_array($type, array('header', 'footer', 'empty'))) {
        $this->drupalPost($add_handler_url, array('name[views.area]' => TRUE), t('Add and configure @handler', array('@handler' => $type_info['ltitle'])));
        $id = 'area';
        $edit_handler_url = "admin/structure/views/nojs/config-item/test_view_empty/default/$type/$id";
      }
      elseif ($type == 'relationship') {
        $this->drupalPost($add_handler_url, array('name[views_test_data.uid]' => TRUE), t('Add and configure @handler', array('@handler' => $type_info['ltitle'])));
        $id = 'uid';
        $edit_handler_url = "admin/structure/views/nojs/config-item/test_view_empty/default/$type/$id";
      }
      else {
        $this->drupalPost($add_handler_url, array('name[views_test_data.job]' => TRUE), t('Add and configure @handler', array('@handler' => $type_info['ltitle'])));
        $id = 'job';
        $edit_handler_url = "admin/structure/views/nojs/config-item/test_view_empty/default/$type/$id";
      }

      $this->assertUrl($edit_handler_url, array(), 'The user got redirected to the handler edit form.');
      $random_label = $this->randomName();
      $this->drupalPost(NULL, array('options[admin_label]' => $random_label), t('Apply'));

      $this->assertUrl('admin/structure/views/view/test_view_empty/edit/default', array(), 'The user got redirected to the views edit form.');

      $this->assertLinkByHref($edit_handler_url, 0, 'The handler edit link appears in the UI.');
      $links = $this->xpath('//a[starts-with(normalize-space(text()), :label)]', array(':label' => $random_label));
      $this->assertTrue(isset($links[0]), 'The handler edit link has the right label');

      // Save the view and have a look whether the handler was added as expected.
      $this->drupalPost(NULL, array(), t('Save'));
      $view = $this->container->get('entity.manager')->getStorageController('view')->load('test_view_empty');
      $display = $view->getDisplay('default');
      $this->assertTrue(isset($display['display_options'][$type_info['plural']][$id]), 'Ensure the field was added to the view itself.');

      // Remove the item and check that it's removed
      $this->drupalPost($edit_handler_url, array(), t('Remove'));
      $this->assertNoLinkByHref($edit_handler_url, 0, 'The handler edit link does not appears in the UI after removing.');

      $this->drupalPost(NULL, array(), t('Save'));
      $view = $this->container->get('entity.manager')->getStorageController('view')->load('test_view_empty');
      $display = $view->getDisplay('default');
      $this->assertFalse(isset($display['display_options'][$type_info['plural']][$id]), 'Ensure the field was removed from the view itself.');
    }

    // Test adding a field of the user table using the uid relationship.
    $type_info = $handler_types['relationship'];
    $add_handler_url = "admin/structure/views/nojs/add-item/test_view_empty/default/relationship";
    $this->drupalPost($add_handler_url, array('name[views_test_data.uid]' => TRUE), t('Add and configure @handler', array('@handler' => $type_info['ltitle'])));

    $add_handler_url = "admin/structure/views/nojs/add-item/test_view_empty/default/field";
    $type_info = $handler_types['field'];
    $this->drupalPost($add_handler_url, array('name[users.signature]' => TRUE), t('Add and configure @handler', array('@handler' => $type_info['ltitle'])));
    $id = 'signature';
    $edit_handler_url = "admin/structure/views/nojs/config-item/test_view_empty/default/field/$id";

    $this->assertUrl($edit_handler_url, array(), 'The user got redirected to the handler edit form.');
    $this->assertFieldByName('options[relationship]', 'uid', 'Ensure the relationship select is filled with the UID relationship.');
    $this->drupalPost(NULL, array(), t('Apply'));

    $this->drupalPost(NULL, array(), t('Save'));
    $view = $this->container->get('entity.manager')->getStorageController('view')->load('test_view_empty');
    $display = $view->getDisplay('default');
    $this->assertTrue(isset($display['display_options'][$type_info['plural']][$id]), 'Ensure the field was added to the view itself.');
  }

}

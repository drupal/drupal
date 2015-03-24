<?php

/**
 * @file
 * Contains \Drupal\views_ui\Tests\HandlerTest.
 */

namespace Drupal\views_ui\Tests;

use Drupal\Component\Utility\String;
use Drupal\views\ViewExecutable;

/**
 * Tests handler UI for views.
 *
 * @group views_ui
 * @see \Drupal\views\Plugin\views\HandlerBase
 */
class HandlerTest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view_empty', 'test_view_broken');

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
        'base' => 'users_field_data',
        'base field' => 'uid'
      )
    );

    return $data;
  }

  /**
   * Tests UI CRUD.
   */
  public function testUICRUD() {
    $handler_types = ViewExecutable::getHandlerTypes();
    foreach ($handler_types as $type => $type_info) {
      // Test adding handlers.
      $add_handler_url = "admin/structure/views/nojs/add-handler/test_view_empty/default/$type";

      // Area handler types need to use a different handler.
      if (in_array($type, array('header', 'footer', 'empty'))) {
        $this->drupalPostForm($add_handler_url, array('name[views.area]' => TRUE), t('Add and configure @handler', array('@handler' => $type_info['ltitle'])));
        $id = 'area';
        $edit_handler_url = "admin/structure/views/nojs/handler/test_view_empty/default/$type/$id";
      }
      elseif ($type == 'relationship') {
        $this->drupalPostForm($add_handler_url, array('name[views_test_data.uid]' => TRUE), t('Add and configure @handler', array('@handler' => $type_info['ltitle'])));
        $id = 'uid';
        $edit_handler_url = "admin/structure/views/nojs/handler/test_view_empty/default/$type/$id";
      }
      else {
        $this->drupalPostForm($add_handler_url, array('name[views_test_data.job]' => TRUE), t('Add and configure @handler', array('@handler' => $type_info['ltitle'])));
        $id = 'job';
        $edit_handler_url = "admin/structure/views/nojs/handler/test_view_empty/default/$type/$id";
      }

      $this->assertUrl($edit_handler_url, array(), 'The user got redirected to the handler edit form.');
      $random_label = $this->randomMachineName();
      $this->drupalPostForm(NULL, array('options[admin_label]' => $random_label), t('Apply'));

      $this->assertUrl('admin/structure/views/view/test_view_empty/edit/default', array(), 'The user got redirected to the views edit form.');

      $this->assertLinkByHref($edit_handler_url, 0, 'The handler edit link appears in the UI.');
      $links = $this->xpath('//a[starts-with(normalize-space(text()), :label)]', array(':label' => $random_label));
      $this->assertTrue(isset($links[0]), 'The handler edit link has the right label');

      // Save the view and have a look whether the handler was added as expected.
      $this->drupalPostForm(NULL, array(), t('Save'));
      $view = $this->container->get('entity.manager')->getStorage('view')->load('test_view_empty');
      $display = $view->getDisplay('default');
      $this->assertTrue(isset($display['display_options'][$type_info['plural']][$id]), 'Ensure the field was added to the view itself.');

      // Remove the item and check that it's removed
      $this->drupalPostForm($edit_handler_url, array(), t('Remove'));
      $this->assertNoLinkByHref($edit_handler_url, 0, 'The handler edit link does not appears in the UI after removing.');

      $this->drupalPostForm(NULL, array(), t('Save'));
      $view = $this->container->get('entity.manager')->getStorage('view')->load('test_view_empty');
      $display = $view->getDisplay('default');
      $this->assertFalse(isset($display['display_options'][$type_info['plural']][$id]), 'Ensure the field was removed from the view itself.');
    }

    // Test adding a field of the user table using the uid relationship.
    $type_info = $handler_types['relationship'];
    $add_handler_url = "admin/structure/views/nojs/add-handler/test_view_empty/default/relationship";
    $this->drupalPostForm($add_handler_url, array('name[views_test_data.uid]' => TRUE), t('Add and configure @handler', array('@handler' => $type_info['ltitle'])));

    $add_handler_url = "admin/structure/views/nojs/add-handler/test_view_empty/default/field";
    $type_info = $handler_types['field'];
    $this->drupalPostForm($add_handler_url, array('name[users_field_data.name]' => TRUE), t('Add and configure @handler', array('@handler' => $type_info['ltitle'])));
    $id = 'name';
    $edit_handler_url = "admin/structure/views/nojs/handler/test_view_empty/default/field/$id";

    $this->assertUrl($edit_handler_url, array(), 'The user got redirected to the handler edit form.');
    $this->assertFieldByName('options[relationship]', 'uid', 'Ensure the relationship select is filled with the UID relationship.');
    $this->drupalPostForm(NULL, array(), t('Apply'));

    $this->drupalPostForm(NULL, array(), t('Save'));
    $view = $this->container->get('entity.manager')->getStorage('view')->load('test_view_empty');
    $display = $view->getDisplay('default');
    $this->assertTrue(isset($display['display_options'][$type_info['plural']][$id]), 'Ensure the field was added to the view itself.');
  }

  /**
   * Tests broken handlers.
   */
  public function testBrokenHandlers() {
    $handler_types = ViewExecutable::getHandlerTypes();
    foreach ($handler_types as $type => $type_info) {
      $this->drupalGet('admin/structure/views/view/test_view_broken/edit');

      $href = "admin/structure/views/nojs/handler/test_view_broken/default/$type/id_broken";

      $result = $this->xpath('//a[contains(@href, :href)]', array(':href' => $href));
      $this->assertEqual(count($result), 1, String::format('Handler (%type) edit link found.', array('%type' => $type)));

      $text = t('Broken/missing handler');

      $this->assertIdentical((string) $result[0], $text, 'Ensure the broken handler text was found.');

      $this->drupalGet($href);
      $result = $this->xpath('//h1');
      $this->assertTrue(strpos((string) $result[0], $text) !== FALSE, 'Ensure the broken handler text was found.');

      $original_configuration = [
        'field' => 'id_broken',
        'id' => 'id_broken',
        'relationship' => 'none',
        'table' => 'views_test_data',
        'plugin_id' => 'numeric',
      ];

      foreach ($original_configuration as $key => $value) {
        $this->assertText(String::format('@key: @value', array('@key' => $key, '@value' => $value)));
      }
    }
  }

}

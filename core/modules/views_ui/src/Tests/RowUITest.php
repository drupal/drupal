<?php

/**
 * @file
 * Contains \Drupal\views_ui\Tests\RowUITest.
 */

namespace Drupal\views_ui\Tests;

use Drupal\views\Views;

/**
 * Tests the UI of row plugins.
 *
 * @group views_ui
 * @see \Drupal\views_test_data\Plugin\views\row\RowTest.
 */
class RowUITest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  /**
   * Tests changing the row plugin and changing some options of a row.
   */
  public function testRowUI() {
    $view_name = 'test_view';
    $view_edit_url = "admin/structure/views/view/$view_name/edit";

    $row_plugin_url = "admin/structure/views/nojs/display/$view_name/default/row";
    $row_options_url = "admin/structure/views/nojs/display/$view_name/default/row_options";

    $this->drupalGet($row_plugin_url);
    $this->assertFieldByName('row[type]', 'fields', 'The default row plugin selected in the UI should be fields.');

    $edit = array(
      'row[type]' => 'test_row'
    );
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    $this->assertFieldByName('row_options[test_option]', NULL, 'Make sure the custom settings form from the test plugin appears.');
    $random_name = $this->randomMachineName();
    $edit = array(
      'row_options[test_option]' => $random_name
    );
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    $this->drupalGet($row_options_url);
    $this->assertFieldByName('row_options[test_option]', $random_name, 'Make sure the custom settings form field has the expected value stored.');

    $this->drupalPostForm($view_edit_url, array(), t('Save'));
    $this->assertLink(t('Test row plugin'), 0, 'Make sure the test row plugin is shown in the UI');

    $view = Views::getView($view_name);
    $view->initDisplay();
    $row = $view->display_handler->getOption('row');
    $this->assertEqual($row['type'], 'test_row', 'Make sure that the test_row got saved as used row plugin.');
    $this->assertEqual($row['options']['test_option'], $random_name, 'Make sure that the custom settings field got saved as expected.');

    // Change the row plugin to fields using ajax.
    $this->drupalPostAjaxForm($row_plugin_url, array('row[type]' => 'fields'), array('op' => 'Apply'), str_replace('/nojs/', '/ajax/', $row_plugin_url));
    $this->drupalPostAjaxForm(NULL, array(), array('op' => 'Apply'));
    $this->assertResponse(200);
    $this->assertFieldByName('row[type]', 'fields', 'Make sure that the fields got saved as used row plugin.');
  }

}

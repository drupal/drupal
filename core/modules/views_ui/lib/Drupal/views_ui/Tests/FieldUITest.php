<?php

/**
 * @file
 * Contains \Drupal\views\Tests\UI\FieldUITest.
 */

namespace Drupal\views_ui\Tests;

/**
 * Tests the UI of field handlers.
 *
 * @see \Drupal\views\Plugin\views\field\FieldPluginBase
 */
class FieldUITest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  public static function getInfo() {
    return array(
      'name' => 'Field: UI',
      'description' => 'Tests the UI of field handlers.',
      'group' => 'Views UI',
    );
  }

  /**
   * Tests the UI of field handlers.
   */
  public function testFieldUI() {
    // Ensure the field is not marked as hidden on the first run.
    $this->drupalGet('admin/structure/views/view/test_view/edit');
    $this->assertText('Views test: Name (Name)');
    $this->assertNoText('Views test: Name (Name) [' . t('hidden') . ']');

    // Hides the field and check whether the hidden label is appended.
    $edit_handler_url = 'admin/structure/views/nojs/config-item/test_view/default/field/name';
    $this->drupalPost($edit_handler_url, array('options[exclude]' => TRUE), t('Apply'));

    $this->assertText('Views test: Name (Name) [' . t('hidden') . ']');
  }

}

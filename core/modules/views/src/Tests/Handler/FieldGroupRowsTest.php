<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Handler\FieldGroupRowsTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\views\Views;

/**
 * Tests the "Display all values in the same row" setting.
 *
 * @see \Drupal\views\Plugin\views\field\Field
 *
 * @group views
 */
class FieldGroupRowsTest extends HandlerTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_group_rows', 'test_ungroup_rows');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'field_test');

  /**
   * Field that will be created to test the group/ungroup rows functionality
   *
   * @var string
   */
  private $fieldName = 'field_group_rows';

  protected function setUp() {
    parent::setUp();

    // Create content type with unlimited text field.
    $node_type = $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));

    // Create the unlimited text field.
    $field_storage = entity_create('field_storage_config', array(
        'field_name' => $this->fieldName,
        'entity_type' => 'node',
        'type' => 'text',
        'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      ));
    $field_storage->save();

    // Create an instance of the text field on the content type.
    $field = array(
      'field_storage' => $field_storage,
      'bundle' => $node_type->id(),
    );
    entity_create('field_config', $field)->save();
  }

  /**
   * Testing the "Grouped rows" functionality.
   */
  public function testGroupRows() {
    $edit = array(
      'title' => $this->randomMachineName(),
      $this->fieldName => array('a', 'b', 'c'),
    );
    $this->drupalCreateNode($edit);

    $view = Views::getView('test_group_rows');

    // Test grouped rows.
    $this->executeView($view);
    $this->assertEqual($view->field[$this->fieldName]->advancedRender($view->result[0]), 'a, b, c');

    // Change the group_rows checkbox to false.
    $view = Views::getView('test_group_rows');
    $view->setHandlerOption('default', 'field', $this->fieldName, 'group_rows', FALSE);

    // Test ungrouped rows.
    $this->executeView($view);
    $view->render();

    $view->row_index = 0;
    $this->assertEqual($view->field[$this->fieldName]->advancedRender($view->result[0]), 'a');
    $view->row_index = 1;
    $this->assertEqual($view->field[$this->fieldName]->advancedRender($view->result[1]), 'b');
    $view->row_index = 2;
    $this->assertEqual($view->field[$this->fieldName]->advancedRender($view->result[2]), 'c');
  }

}

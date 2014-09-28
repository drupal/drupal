<?php

/**
 * @file
 * Contains \Drupal\field\Tests\Views\HandlerFieldFieldTest.
 */

namespace Drupal\field\Tests\Views;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Tests the field itself of the Field integration.
 *
 * @group field
 * @TODO
 *   Check a entity-type with bundles
 *   Check a entity-type without bundles
 *   Check locale:disabled, locale:enabled and locale:enabled with another language
 *   Check revisions
 */
class HandlerFieldFieldTest extends FieldTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view_fieldapi');

  public $nodes;

  /**
   * @todo.
   */
  protected function setUp() {
    parent::setUp();

    // Setup basic fields.
    $this->setUpFieldStorages(3);

    // Setup a field with cardinality > 1.
    $this->fieldStorages[3] = entity_create('field_storage_config', array(
      'field_name' => 'field_name_3',
      'entity_type' => 'node',
      'type' => 'string',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ));
    $this->fieldStorages[3]->save();
    // Setup a field that will have no value.
    $this->fieldStorages[4] = entity_create('field_storage_config', array(
      'field_name' => 'field_name_4',
      'entity_type' => 'node',
      'type' => 'string',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ));
    $this->fieldStorages[4]->save();

    // Setup a text field.
    $this->fieldStorages[5] = entity_create('field_storage_config', array(
      'field_name' => 'field_name_5',
      'entity_type' => 'node',
      'type' => 'text',
    ));
    $this->fieldStorages[5]->save();

    $this->setUpFields();

    // Create some nodes.
    $this->nodes = array();
    for ($i = 0; $i < 3; $i++) {
      $edit = array('type' => 'page');

      foreach (array(0, 1, 2, 5) as $key) {
        $field_storage = $this->fieldStorages[$key];
        $edit[$field_storage->getName()][0]['value'] = $this->randomMachineName(8);
      }
      for ($j = 0; $j < 5; $j++) {
        $edit[$this->fieldStorages[3]->getName()][$j]['value'] = $this->randomMachineName(8);
      }
      // Set this field to be empty.
      $edit[$this->fieldStorages[4]->getName()] = array(array('value' => NULL));

      $this->nodes[$i] = $this->drupalCreateNode($edit);
    }

    $this->container->get('views.views_data')->clear();
  }

  /**
   * Sets up the testing view with random field data.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view to add field data to.
   */
  protected function prepareView(ViewExecutable $view) {
    $view->initDisplay();
    foreach ($this->fieldStorages as $field_storage) {
      $field_name = $field_storage->getName();
      $view->display_handler->options['fields'][$field_name]['id'] = $field_name;
      $view->display_handler->options['fields'][$field_name]['table'] = 'node__' . $field_name;
      $view->display_handler->options['fields'][$field_name]['field'] = $field_name;
    }
  }

  public function testFieldRender() {
    $this->_testSimpleFieldRender();
    $this->_testFormatterSimpleFieldRender();
    $this->_testMultipleFieldRender();
  }

  public function _testSimpleFieldRender() {
    $view = Views::getView('test_view_fieldapi');
    $this->prepareView($view);
    $this->executeView($view);

    // Tests that the rendered fields match the actual value of the fields.
    for ($i = 0; $i < 3; $i++) {
      for ($key = 0; $key < 2; $key++) {
        $field_name = $this->fieldStorages[$key]->getName();
        $rendered_field = $view->style_plugin->getField($i, $field_name);
        $expected_field = $this->nodes[$i]->$field_name->value;
        $this->assertEqual($rendered_field, $expected_field);
      }
    }
  }

  /**
   * Tests that fields with formatters runs as expected.
   */
  public function _testFormatterSimpleFieldRender() {
    $view = Views::getView('test_view_fieldapi');
    $this->prepareView($view);
    $view->displayHandlers->get('default')->options['fields'][$this->fieldStorages[5]->getName()]['type'] = 'text_trimmed';
    $view->displayHandlers->get('default')->options['fields'][$this->fieldStorages[5]->getName()]['settings'] = array(
      'trim_length' => 3,
    );
    $this->executeView($view);

    // Make sure that the formatter works as expected.
    // @TODO: actually there should be a specific formatter.
    for ($i = 0; $i < 2; $i++) {
      $rendered_field = $view->style_plugin->getField($i, $this->fieldStorages[5]->getName());
      $this->assertEqual(strlen(html_entity_decode($rendered_field)), 3);
    }
  }

  public function _testMultipleFieldRender() {
    $view = Views::getView('test_view_fieldapi');
    $field_name = $this->fieldStorages[3]->getName();

    // Test delta limit.
    $this->prepareView($view);
    $view->displayHandlers->get('default')->options['fields'][$field_name]['group_rows'] = TRUE;
    $view->displayHandlers->get('default')->options['fields'][$field_name]['delta_limit'] = 3;
    $this->executeView($view);

    for ($i = 0; $i < 3; $i++) {
      $rendered_field = $view->style_plugin->getField($i, $field_name);
      $items = array();
      $pure_items = $this->nodes[$i]->{$field_name}->getValue();
      $pure_items = array_splice($pure_items, 0, 3);
      foreach ($pure_items as $j => $item) {
        $items[] = $pure_items[$j]['value'];
      }
      $this->assertEqual($rendered_field, implode(', ', $items), 'Make sure that the amount of items is limited.');
    }

    // Test that an empty field is rendered without error.
    $view->style_plugin->getField(4, $this->fieldStorages[4]->getName());
    $view->destroy();

    // Test delta limit + offset
    $this->prepareView($view);
    $view->displayHandlers->get('default')->options['fields'][$field_name]['group_rows'] = TRUE;
    $view->displayHandlers->get('default')->options['fields'][$field_name]['delta_limit'] = 3;
    $view->displayHandlers->get('default')->options['fields'][$field_name]['delta_offset'] = 1;
    $this->executeView($view);

    for ($i = 0; $i < 3; $i++) {
      $rendered_field = $view->style_plugin->getField($i, $field_name);
      $items = array();
      $pure_items = $this->nodes[$i]->{$field_name}->getValue();
      $pure_items = array_splice($pure_items, 1, 3);
      foreach ($pure_items as $j => $item) {
        $items[] = $pure_items[$j]['value'];
      }
      $this->assertEqual($rendered_field, implode(', ', $items), 'Make sure that the amount of items is limited.');
    }
    $view->destroy();

    // Test delta limit + reverse.
    $this->prepareView($view);
    $view->displayHandlers->get('default')->options['fields'][$field_name]['delta_offset'] = 0;
    $view->displayHandlers->get('default')->options['fields'][$field_name]['group_rows'] = TRUE;
    $view->displayHandlers->get('default')->options['fields'][$field_name]['delta_limit'] = 3;
    $view->displayHandlers->get('default')->options['fields'][$field_name]['delta_reversed'] = TRUE;
    $this->executeView($view);

    for ($i = 0; $i < 3; $i++) {
      $rendered_field = $view->style_plugin->getField($i, $field_name);
      $items = array();
      $pure_items = $this->nodes[$i]->{$field_name}->getValue();
      array_splice($pure_items, 0, -3);
      $pure_items = array_reverse($pure_items);
      foreach ($pure_items as $j => $item) {
        $items[] = $pure_items[$j]['value'];
      }
      $this->assertEqual($rendered_field, implode(', ', $items), 'Make sure that the amount of items is limited.');
    }
    $view->destroy();

    // Test delta first last.
    $this->prepareView($view);
    $view->displayHandlers->get('default')->options['fields'][$field_name]['group_rows'] = TRUE;
    $view->displayHandlers->get('default')->options['fields'][$field_name]['delta_limit'] = 0;
    $view->displayHandlers->get('default')->options['fields'][$field_name]['delta_first_last'] = TRUE;
    $view->displayHandlers->get('default')->options['fields'][$field_name]['delta_reversed'] = FALSE;
    $this->executeView($view);

    for ($i = 0; $i < 3; $i++) {
      $rendered_field = $view->style_plugin->getField($i, $field_name);
      $items = array();
      $pure_items = $this->nodes[$i]->{$field_name}->getValue();
      $items[] = $pure_items[0]['value'];
      $items[] = $pure_items[4]['value'];
      $this->assertEqual($rendered_field, implode(', ', $items), 'Make sure that the amount of items is limited.');
    }
    $view->destroy();

    // Test delta limit + custom seperator.
    $this->prepareView($view);
    $view->displayHandlers->get('default')->options['fields'][$field_name]['delta_first_last'] = FALSE;
    $view->displayHandlers->get('default')->options['fields'][$field_name]['delta_limit'] = 3;
    $view->displayHandlers->get('default')->options['fields'][$field_name]['group_rows'] = TRUE;
    $view->displayHandlers->get('default')->options['fields'][$field_name]['separator'] = ':';
    $this->executeView($view);

    for ($i = 0; $i < 3; $i++) {
      $rendered_field = $view->style_plugin->getField($i, $field_name);
      $items = array();
      $pure_items = $this->nodes[$i]->{$field_name}->getValue();
      $pure_items = array_splice($pure_items, 0, 3);
      foreach ($pure_items as $j => $item) {
        $items[] = $pure_items[$j]['value'];
      }
      $this->assertEqual($rendered_field, implode(':', $items), 'Make sure that the amount of items is limited.');
    }
  }

}

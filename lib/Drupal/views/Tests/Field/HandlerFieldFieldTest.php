<?php

/**
 * @file
 * Definition of Drupal\views\Test\Field\HandlerFieldFieldTest.
 */

namespace Drupal\views\Tests\Field;

use Drupal\views\View;

/**
 * Tests the field_field handler.
 * @TODO
 *   Check a entity-type with bundles
 *   Check a entity-type without bundles
 *   Check locale:disabled, locale:enabled and locale:enabled with another language
 *   Check revisions
 */
class HandlerFieldFieldTest extends FieldTestBase {

  public $nodes;

  public static function getInfo() {
    return array(
      'name' => 'Field: Field handler',
      'description' => 'Tests the field itself of the Field integration.',
      'group' => 'Views Modules'
    );
  }

  protected function setUp() {
    parent::setUp();

    // Setup basic fields.
    $this->setUpFields(3);

    // Setup a field with cardinality > 1.
    $this->fields[3] = $field = field_create_field(array('field_name' => 'field_name_3', 'type' => 'text', 'cardinality' => FIELD_CARDINALITY_UNLIMITED));
    // Setup a field that will have no value.
    $this->fields[4] = $field = field_create_field(array('field_name' => 'field_name_4', 'type' => 'text', 'cardinality' => FIELD_CARDINALITY_UNLIMITED));

    $this->setUpInstances();

    $this->clearViewsCaches();

    // Create some nodes.
    $this->nodes = array();
    for ($i = 0; $i < 3; $i++) {
      $edit = array('type' => 'page');

      for ($key = 0; $key < 3; $key++) {
        $field = $this->fields[$key];
        $edit[$field['field_name']][LANGUAGE_NOT_SPECIFIED][0]['value'] = $this->randomName(8);
      }
      for ($j = 0; $j < 5; $j++) {
        $edit[$this->fields[3]['field_name']][LANGUAGE_NOT_SPECIFIED][$j]['value'] = $this->randomName(8);
      }
      // Set this field to be empty.
      $edit[$this->fields[4]['field_name']] = array();

      $this->nodes[$i] = $this->drupalCreateNode($edit);
    }
  }

  public function testFieldRender() {
    $this->_testSimpleFieldRender();
    $this->_testFormatterSimpleFieldRender();
    $this->_testMultipleFieldRender();
  }

  public function _testSimpleFieldRender() {
    $view = $this->getFieldView();
    $this->executeView($view);

    // Tests that the rendered fields match the actual value of the fields.
    for ($i = 0; $i < 3; $i++) {
      for ($key = 0; $key < 2; $key++) {
        $field = $this->fields[$key];
        $rendered_field = $view->style_plugin->get_field($i, $field['field_name']);
        $expected_field = $this->nodes[$i]->{$field['field_name']}[LANGUAGE_NOT_SPECIFIED][0]['value'];
        $this->assertEqual($rendered_field, $expected_field);
      }
    }
  }

  /**
   * Tests that fields with formatters runs as expected.
   */
  public function _testFormatterSimpleFieldRender() {
    $view = $this->getFieldView();
    $view->display['default']->display_options['fields'][$this->fields[0]['field_name']]['type'] = 'text_trimmed';
    $view->display['default']->display_options['fields'][$this->fields[0]['field_name']]['settings'] = array(
      'trim_length' => 3,
    );
    $this->executeView($view);

    // Take sure that the formatter works as expected.
    // @TODO: actually there should be a specific formatter.
    for ($i = 0; $i < 2; $i++) {
      $rendered_field = $view->style_plugin->get_field($i, $this->fields[0]['field_name']);
      $this->assertEqual(strlen($rendered_field), 3);
    }
  }

  public function _testMultipleFieldRender() {
    $view = $this->getFieldView();

    // Test delta limit.
    $view->display['default']->display_options['fields'][$this->fields[3]['field_name']]['group_rows'] = TRUE;
    $view->display['default']->display_options['fields'][$this->fields[3]['field_name']]['delta_limit'] = 3;
    $this->executeView($view);

    for ($i = 0; $i < 3; $i++) {
      $rendered_field = $view->style_plugin->get_field($i, $this->fields[3]['field_name']);
      $items = array();
      $pure_items = $this->nodes[$i]->{$this->fields[3]['field_name']}[LANGUAGE_NOT_SPECIFIED];
      $pure_items = array_splice($pure_items, 0, 3);
      foreach ($pure_items as $j => $item) {
        $items[] = $pure_items[$j]['value'];
      }
      $this->assertEqual($rendered_field, implode(', ', $items), 'Take sure that the amount of items are limited.');
    }

    // Test that an empty field is rendered without error.
    $rendered_field = $view->style_plugin->get_field(4, $this->fields[4]['field_name']);

    $view->destroy();

    // Test delta limit + offset
    $view->display['default']->display_options['fields'][$this->fields[3]['field_name']]['group_rows'] = TRUE;
    $view->display['default']->display_options['fields'][$this->fields[3]['field_name']]['delta_limit'] = 3;
    $view->display['default']->display_options['fields'][$this->fields[3]['field_name']]['delta_offset'] = 1;
    $this->executeView($view);

    for ($i = 0; $i < 3; $i++) {
      $rendered_field = $view->style_plugin->get_field($i, $this->fields[3]['field_name']);
      $items = array();
      $pure_items = $this->nodes[$i]->{$this->fields[3]['field_name']}[LANGUAGE_NOT_SPECIFIED];
      $pure_items = array_splice($pure_items, 1, 3);
      foreach ($pure_items as $j => $item) {
        $items[] = $pure_items[$j]['value'];
      }
      $this->assertEqual($rendered_field, implode(', ', $items), 'Take sure that the amount of items are limited.');
    }
    $view->destroy();

    // Test delta limit + reverse.
    $view->display['default']->display_options['fields'][$this->fields[3]['field_name']]['delta_offset'] = 0;
    $view->display['default']->display_options['fields'][$this->fields[3]['field_name']]['group_rows'] = TRUE;
    $view->display['default']->display_options['fields'][$this->fields[3]['field_name']]['delta_limit'] = 3;
    $view->display['default']->display_options['fields'][$this->fields[3]['field_name']]['delta_reversed'] = TRUE;
    $this->executeView($view);

    for ($i = 0; $i < 3; $i++) {
      $rendered_field = $view->style_plugin->get_field($i, $this->fields[3]['field_name']);
      $items = array();
      $pure_items = $this->nodes[$i]->{$this->fields[3]['field_name']}[LANGUAGE_NOT_SPECIFIED];
      array_splice($pure_items, 0, -3);
      $pure_items = array_reverse($pure_items);
      foreach ($pure_items as $j => $item) {
        $items[] = $pure_items[$j]['value'];
      }
      $this->assertEqual($rendered_field, implode(', ', $items), 'Take sure that the amount of items are limited.');
    }
    $view->destroy();

    // Test delta first last.
    $view->display['default']->display_options['fields'][$this->fields[3]['field_name']]['group_rows'] = TRUE;
    $view->display['default']->display_options['fields'][$this->fields[3]['field_name']]['delta_limit'] = 0;
    $view->display['default']->display_options['fields'][$this->fields[3]['field_name']]['delta_first_last'] = TRUE;
    $view->display['default']->display_options['fields'][$this->fields[3]['field_name']]['delta_reversed'] = FALSE;
    $this->executeView($view);

    for ($i = 0; $i < 3; $i++) {
      $rendered_field = $view->style_plugin->get_field($i, $this->fields[3]['field_name']);
      $items = array();
      $pure_items = $this->nodes[$i]->{$this->fields[3]['field_name']}[LANGUAGE_NOT_SPECIFIED];
      $items[] = $pure_items[0]['value'];
      $items[] = $pure_items[4]['value'];
      $this->assertEqual($rendered_field, implode(', ', $items), 'Take sure that the amount of items are limited.');
    }
    $view->destroy();

    // Test delta limit + custom seperator.
    $view->display['default']->display_options['fields'][$this->fields[3]['field_name']]['delta_first_last'] = FALSE;
    $view->display['default']->display_options['fields'][$this->fields[3]['field_name']]['delta_limit'] = 3;
    $view->display['default']->display_options['fields'][$this->fields[3]['field_name']]['group_rows'] = TRUE;
    $view->display['default']->display_options['fields'][$this->fields[3]['field_name']]['separator'] = ':';
    $this->executeView($view);

    for ($i = 0; $i < 3; $i++) {
      $rendered_field = $view->style_plugin->get_field($i, $this->fields[3]['field_name']);
      $items = array();
      $pure_items = $this->nodes[$i]->{$this->fields[3]['field_name']}[LANGUAGE_NOT_SPECIFIED];
      $pure_items = array_splice($pure_items, 0, 3);
      foreach ($pure_items as $j => $item) {
        $items[] = $pure_items[$j]['value'];
      }
      $this->assertEqual($rendered_field, implode(':', $items), 'Take sure that the amount of items are limited.');
    }
  }

  protected function getFieldView() {
    $view = new View(array(), 'view');
    $view->name = 'view_fieldapi';
    $view->description = '';
    $view->tag = 'default';
    $view->base_table = 'node';
    $view->human_name = 'view_fieldapi';
    $view->core = 8;
    $view->api_version = '3.0';
    $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

    /* Display: Master */
    $handler = $view->new_display('default', 'Master', 'default');
    $handler->display->display_options['access']['type'] = 'perm';
    $handler->display->display_options['cache']['type'] = 'none';
    $handler->display->display_options['query']['type'] = 'views_query';
    $handler->display->display_options['exposed_form']['type'] = 'basic';
    $handler->display->display_options['pager']['type'] = 'full';
    $handler->display->display_options['style_plugin'] = 'default';
    $handler->display->display_options['row_plugin'] = 'fields';

    $handler->display->display_options['fields']['nid']['id'] = 'nid';
    $handler->display->display_options['fields']['nid']['table'] = 'node';
    $handler->display->display_options['fields']['nid']['field'] = 'nid';
    foreach ($this->fields as $key => $field) {
      $handler->display->display_options['fields'][$field['field_name']]['id'] = $field['field_name'];
      $handler->display->display_options['fields'][$field['field_name']]['table'] = 'field_data_' . $field['field_name'];
      $handler->display->display_options['fields'][$field['field_name']]['field'] = $field['field_name'];
    }
    return $view;
  }

}

<?php

/**
 * @file
 * Definition of Drupal\edit\Tests\EditorSelectionTest.
 */

namespace Drupal\edit\Tests;

use Drupal\simpletest\DrupalUnitTestBase;
use Drupal\edit\Plugin\ProcessedTextEditorManager;
use Drupal\edit\EditorSelector;

/**
 * Test in-place field editor selection.
 */
class EditorSelectionTest extends DrupalUnitTestBase {
  var $default_storage = 'field_sql_storage';

  /**
   * The editor selector object to be tested.
   *
   * @var \Drupal\edit\EditorSelectorInterface
   */
  protected $editorSelector;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'field_test', 'field', 'number', 'text', 'edit', 'edit_test');

  public static function getInfo() {
    return array(
      'name' => 'In-place field editor selection',
      'description' => 'Tests in-place field editor selection.',
      'group' => 'Edit',
    );
  }

  /**
   * Sets the default field storage backend for fields created during tests.
   */
  function setUp() {
    parent::setUp();

    $this->installSchema('system', 'variable');
    $this->enableModules(array('field', 'field_sql_storage', 'field_test'));

    // Set default storage backend.
    variable_set('field_storage_default', $this->default_storage);

    // @todo Rather than using the real ProcessedTextEditorManager, which can
    //   find all text editor plugins in the codebase, create a mock one for
    //   testing that is populated with only the ones we want to test.
    $text_editor_manager = new ProcessedTextEditorManager();

    $this->editorSelector = new EditorSelector($text_editor_manager);
  }

  /**
   * Creates a field and an instance of it.
   *
   * @param string $field_name
   *   The field name.
   * @param string $type
   *   The field type.
   * @param int $cardinality
   *   The field's cardinality.
   * @param string $label
   *   The field's label (used everywhere: widget label, formatter label).
   * @param array $instance_settings
   * @param string $widget_type
   *   The widget type.
   * @param array $widget_settings
   *   The widget settings.
   * @param string $formatter_type
   *   The formatter type.
   * @param array $formatter_settings
   *   The formatter settings.
   */
  function createFieldWithInstance($field_name, $type, $cardinality, $label, $instance_settings, $widget_type, $widget_settings, $formatter_type, $formatter_settings) {
    $field = $field_name . '_field';
    $this->$field = array(
      'field_name' => $field_name,
      'type' => $type,
      'cardinality' => $cardinality,
    );
    $this->$field_name = field_create_field($this->$field);

    $instance = $field_name . '_instance';
    $this->$instance = array(
      'field_name' => $field_name,
      'entity_type' => 'test_entity',
      'bundle' => 'test_bundle',
      'label' => $label,
      'description' => $label,
      'weight' => mt_rand(0, 127),
      'settings' => $instance_settings,
      'widget' => array(
        'type' => $widget_type,
        'label' => $label,
        'settings' => $widget_settings,
      ),
      'display' => array(
        'default' => array(
          'label' => 'above',
          'type' => $formatter_type,
          'settings' => $formatter_settings
        ),
      ),
    );
    field_create_instance($this->$instance);
  }

  /**
   * Retrieves the FieldInstance object for the given field and returns the
   * editor that Edit selects.
   */
  function getSelectedEditor($items, $field_name, $display = 'default') {
    $field_instance = field_info_instance('test_entity', $field_name, 'test_bundle');
    return $this->editorSelector->getEditor($field_instance['display'][$display]['type'], $field_instance, $items);
  }

  /**
   * Tests a textual field, without/with text processing, with cardinality 1 and
   * >1, always without a WYSIWYG editor present.
   */
  function testText() {
    $field_name = 'field_text';
    $this->createFieldWithInstance(
      $field_name, 'text', 1, 'Simple text field',
      // Instance settings.
      array('text_processing' => 0),
      // Widget type & settings.
      'text_textfield',
      array('size' => 42),
      // 'default' formatter type & settings.
      'text_default',
      array()
    );

    // Pretend there is an entity with these items for the field.
    $items = array(array('value' => 'Hello, world!', 'format' => 'full_html'));

    // Editor selection without text processing, with cardinality 1.
    $this->assertEqual('direct', $this->getSelectedEditor($items, $field_name), "Without text processing, cardinality 1, the 'direct' editor is selected.");

    // Editor selection with text processing, cardinality 1.
    $this->field_text_instance['settings']['text_processing'] = 1;
    field_update_instance($this->field_text_instance);
    $this->assertEqual('form', $this->getSelectedEditor($items, $field_name), "With text processing, cardinality 1, the 'form' editor is selected.");

    // Editor selection without text processing, cardinality 1 (again).
    $this->field_text_instance['settings']['text_processing'] = 0;
    field_update_instance($this->field_text_instance);
    $this->assertEqual('direct', $this->getSelectedEditor($items, $field_name), "Without text processing again, cardinality 1, the 'direct' editor is selected.");

    // Editor selection without text processing, cardinality >1
    $this->field_text_field['cardinality'] = 2;
    field_update_field($this->field_text_field);
    $items[] = array('value' => 'Hallo, wereld!', 'format' => 'full_html');
    $this->assertEqual('form', $this->getSelectedEditor($items, $field_name), "Without text processing, cardinality >1, the 'form' editor is selected.");

    // Editor selection with text processing, cardinality >1
    $this->field_text_instance['settings']['text_processing'] = 1;
    field_update_instance($this->field_text_instance);
    $this->assertEqual('form', $this->getSelectedEditor($items, $field_name), "With text processing, cardinality >1, the 'form' editor is selected.");
  }

  /**
   * Tests a textual field, with text processing, with cardinality 1 and >1,
   * always with a ProcessedTextEditor plug-in present, but with varying text
   * format compatibility.
   */
  function testTextWysiwyg() {
    $field_name = 'field_textarea';
    $this->createFieldWithInstance(
      $field_name, 'text', 1, 'Long text field',
      // Instance settings.
      array('text_processing' => 1),
      // Widget type & settings.
      'text_textarea',
      array('size' => 42),
      // 'default' formatter type & settings.
      'text_default',
      array()
    );

    // ProcessedTextEditor plug-in compatible with the full_html text format.
    state()->set('edit_test.compatible_format', 'full_html');

    // Pretend there is an entity with these items for the field.
    $items = array(array('value' => 'Hello, world!', 'format' => 'filtered_html'));

    // Editor selection with cardinality 1, without compatible text format.
    $this->assertEqual('form', $this->getSelectedEditor($items, $field_name), "Without cardinality 1, and the filtered_html text format, the 'form' editor is selected.");

    // Editor selection with cardinality 1, with compatible text format.
    $items[0]['format'] = 'full_html';
    $this->assertEqual('direct-with-wysiwyg', $this->getSelectedEditor($items, $field_name), "With cardinality 1, and the full_html text format, the 'direct-with-wysiwyg' editor is selected.");

    // Editor selection with text processing, cardinality >1
    $this->field_textarea_field['cardinality'] = 2;
    field_update_field($this->field_textarea_field);
    $items[] = array('value' => 'Hallo, wereld!', 'format' => 'full_html');
    $this->assertEqual('form', $this->getSelectedEditor($items, $field_name), "With cardinality >1, and both items using the full_html text format, the 'form' editor is selected.");
  }

  /**
   * Tests a number field, with cardinality 1 and >1.
   */
  function testNumber() {
    $field_name = 'field_nr';
    $this->createFieldWithInstance(
      $field_name, 'number_integer', 1, 'Simple number field',
      // Instance settings.
      array(),
      // Widget type & settings.
      'number',
      array(),
      // 'default' formatter type & settings.
      'number_integer',
      array()
    );

    // Pretend there is an entity with these items for the field.
    $items = array(42, 43);

    // Editor selection with cardinality 1.
    $this->assertEqual('form', $this->getSelectedEditor($items, $field_name), "With cardinality 1, the 'form' editor is selected.");

    // Editor selection with cardinality >1.
    $this->field_nr_field['cardinality'] = 2;
    field_update_field($this->field_nr_field);
    $this->assertEqual('form', $this->getSelectedEditor($items, $field_name), "With cardinality >1, the 'form' editor is selected.");
  }

}

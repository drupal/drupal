<?php

/**
 * @file
 * Contains \Drupal\edit\Tests\EditorSelectionTest.
 */

namespace Drupal\edit\Tests;

use Drupal\edit\Plugin\InPlaceEditorManager;
use Drupal\edit\EditorSelector;

/**
 * Test in-place field editor selection.
 */
class EditorSelectionTest extends EditTestBase {

  /**
   * The manager for editor plugins.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $editorManager;

  /**
   * The editor selector object to be tested.
   *
   * @var \Drupal\edit\EditorSelectorInterface
   */
  protected $editorSelector;

  public static function getInfo() {
    return array(
      'name' => 'In-place field editor selection',
      'description' => 'Tests in-place field editor selection.',
      'group' => 'Edit',
    );
  }

  function setUp() {
    parent::setUp();

    $this->editorManager = $this->container->get('plugin.manager.edit.editor');
    $this->editorSelector = new EditorSelector($this->editorManager);
  }

  /**
   * Retrieves the FieldInstance object for the given field and returns the
   * editor that Edit selects.
   */
  protected function getSelectedEditor($items, $field_name, $view_mode = 'default') {
    $options = entity_get_display('entity_test', 'entity_test', $view_mode)->getComponent($field_name);
    $field_instance = field_info_instance('entity_test', $field_name, 'entity_test');
    return $this->editorSelector->getEditor($options['type'], $field_instance, $items);
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
   * always with an Editor plugin present that supports textual fields with text
   * processing, but with varying text format compatibility.
   */
  function testTextWysiwyg() {
    // Enable edit_test module so that the 'wysiwyg' editor becomes available.
    $this->enableModules(array('edit_test'));

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

    // Pretend there is an entity with these items for the field.
    $items = array(array('value' => 'Hello, world!', 'format' => 'filtered_html'));

    // Editor selection w/ cardinality 1, text format w/o associated text editor.
    $this->assertEqual('form', $this->getSelectedEditor($items, $field_name), "With cardinality 1, and the filtered_html text format, the 'form' editor is selected.");

    // Editor selection w/ cardinality 1, text format w/ associated text editor.
    $items[0]['format'] = 'full_html';
    $this->assertEqual('wysiwyg', $this->getSelectedEditor($items, $field_name), "With cardinality 1, and the full_html text format, the 'wysiwyg' editor is selected.");

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

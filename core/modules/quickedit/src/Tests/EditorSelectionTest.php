<?php

/**
 * @file
 * Contains \Drupal\quickedit\Tests\EditorSelectionTest.
 */

namespace Drupal\quickedit\Tests;

use Drupal\Core\Language\LanguageInterface;
use Drupal\quickedit\Plugin\InPlaceEditorManager;
use Drupal\quickedit\EditorSelector;

/**
 * Test in-place field editor selection.
 */
class EditorSelectionTest extends QuickEditTestBase {

  /**
   * The manager for editor plugins.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $editorManager;

  /**
   * The editor selector object to be tested.
   *
   * @var \Drupal\quickedit\EditorSelectorInterface
   */
  protected $editorSelector;

  public static function getInfo() {
    return array(
      'name' => 'In-place field editor selection',
      'description' => 'Tests in-place field editor selection.',
      'group' => 'Quick Edit',
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->editorManager = $this->container->get('plugin.manager.quickedit.editor');
    $this->editorSelector = new EditorSelector($this->editorManager, $this->container->get('plugin.manager.field.formatter'));
  }

  /**
   * Returns the in-place editor that Quick Edit selects.
   */
  protected function getSelectedEditor($entity_id, $field_name, $view_mode = 'default') {
    $entity = entity_load('entity_test', $entity_id, TRUE);
    $items = $entity->getTranslation(LanguageInterface::LANGCODE_NOT_SPECIFIED)->get($field_name);
    $options = entity_get_display('entity_test', 'entity_test', $view_mode)->getComponent($field_name);
    return $this->editorSelector->getEditor($options['type'], $items);
  }

  /**
   * Tests a textual field, without/with text processing, with cardinality 1 and
   * >1, always without a WYSIWYG editor present.
   */
  public function testText() {
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

    // Create an entity with values for this text field.
    $this->entity = entity_create('entity_test');
    $this->entity->{$field_name}->value = 'Hello, world!';
    $this->entity->{$field_name}->format = 'full_html';
    $this->entity->save();

    // Editor selection without text processing, with cardinality 1.
    $this->assertEqual('plain_text', $this->getSelectedEditor($this->entity->id(), $field_name), "Without text processing, cardinality 1, the 'plain_text' editor is selected.");

    // Editor selection with text processing, cardinality 1.
    $this->field_text_instance->settings['text_processing'] = 1;
    $this->field_text_instance->save();
    $this->assertEqual('form', $this->getSelectedEditor($this->entity->id(), $field_name), "With text processing, cardinality 1, the 'form' editor is selected.");

    // Editor selection without text processing, cardinality 1 (again).
    $this->field_text_instance->settings['text_processing'] = 0;
    $this->field_text_instance->save();
    $this->assertEqual('plain_text', $this->getSelectedEditor($this->entity->id(), $field_name), "Without text processing again, cardinality 1, the 'plain_text' editor is selected.");

    // Editor selection without text processing, cardinality >1
    $this->field_text_field->cardinality = 2;
    $this->field_text_field->save();
    $this->assertEqual('form', $this->getSelectedEditor($this->entity->id(), $field_name), "Without text processing, cardinality >1, the 'form' editor is selected.");

    // Editor selection with text processing, cardinality >1
    $this->field_text_instance->settings['text_processing'] = 1;
    $this->field_text_instance->save();
    $this->assertEqual('form', $this->getSelectedEditor($this->entity->id(), $field_name), "With text processing, cardinality >1, the 'form' editor is selected.");
  }

  /**
   * Tests a textual field, with text processing, with cardinality 1 and >1,
   * always with an Editor plugin present that supports textual fields with text
   * processing, but with varying text format compatibility.
   */
  public function testTextWysiwyg() {
    // Enable edit_test module so that the 'wysiwyg' editor becomes available.
    $this->enableModules(array('quickedit_test'));
    $this->editorManager = $this->container->get('plugin.manager.quickedit.editor');
    $this->editorSelector = new EditorSelector($this->editorManager, $this->container->get('plugin.manager.field.formatter'));

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

    // Create an entity with values for this text field.
    $this->entity = entity_create('entity_test');
    $this->entity->{$field_name}->value = 'Hello, world!';
    $this->entity->{$field_name}->format = 'filtered_html';
    $this->entity->save();

    // Editor selection w/ cardinality 1, text format w/o associated text editor.
    $this->assertEqual('form', $this->getSelectedEditor($this->entity->id(), $field_name), "With cardinality 1, and the filtered_html text format, the 'form' editor is selected.");

    // Editor selection w/ cardinality 1, text format w/ associated text editor.
    $this->entity->{$field_name}->format = 'full_html';
    $this->entity->save();
    $this->assertEqual('wysiwyg', $this->getSelectedEditor($this->entity->id(), $field_name), "With cardinality 1, and the full_html text format, the 'wysiwyg' editor is selected.");

    // Editor selection with text processing, cardinality >1
    $this->field_textarea_field->cardinality = 2;
    $this->field_textarea_field->save();
    $this->assertEqual('form', $this->getSelectedEditor($this->entity->id(), $field_name), "With cardinality >1, and both items using the full_html text format, the 'form' editor is selected.");
  }

  /**
   * Tests a number field, with cardinality 1 and >1.
   */
  public function testNumber() {
    $field_name = 'field_nr';
    $this->createFieldWithInstance(
      $field_name, 'integer', 1, 'Simple number field',
      // Instance settings.
      array(),
      // Widget type & settings.
      'number',
      array(),
      // 'default' formatter type & settings.
      'number_integer',
      array()
    );

    // Create an entity with values for this text field.
    $this->entity = entity_create('entity_test');
    $this->entity->{$field_name}->value = 42;
    $this->entity->save();

    // Editor selection with cardinality 1.
    $this->assertEqual('form', $this->getSelectedEditor($this->entity->id(), $field_name), "With cardinality 1, the 'form' editor is selected.");

    // Editor selection with cardinality >1.
    $this->field_nr_field->cardinality = 2;
    $this->field_nr_field->save();
    $this->assertEqual('form', $this->getSelectedEditor($this->entity->id(), $field_name), "With cardinality >1, the 'form' editor is selected.");
  }

}

<?php

namespace Drupal\Tests\quickedit\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\quickedit\EditorSelector;

/**
 * Tests in-place field editor selection.
 *
 * @group quickedit
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

  protected function setUp(): void {
    parent::setUp();

    $this->editorManager = $this->container->get('plugin.manager.quickedit.editor');
    $this->editorSelector = new EditorSelector($this->editorManager, $this->container->get('plugin.manager.field.formatter'));
  }

  /**
   * Returns the in-place editor that Quick Edit selects.
   */
  protected function getSelectedEditor($entity_id, $field_name, $view_mode = 'default') {
    $storage = $this->container->get('entity_type.manager')->getStorage('entity_test');
    $storage->resetCache([$entity_id]);
    $entity = $storage->load($entity_id);
    $items = $entity->get($field_name);
    $options = \Drupal::service('entity_display.repository')
      ->getViewDisplay('entity_test', 'entity_test', $view_mode)
      ->getComponent($field_name);
    return $this->editorSelector->getEditor($options['type'], $items);
  }

  /**
   * Tests a string (plain text) field, with cardinality 1 and >1.
   */
  public function testText() {
    $field_name = 'field_text';
    $this->createFieldWithStorage(
      $field_name, 'string', 1, 'Simple text field',
      // Instance settings.
      [],
      // Widget type & settings.
      'string_textfield',
      ['size' => 42],
      // 'default' formatter type & settings.
      'string',
      []
    );

    // Create an entity with values for this text field.
    $entity = EntityTest::create();
    $entity->{$field_name}->value = 'Hello, world!';
    $entity->save();

    // With cardinality 1.
    $this->assertEquals('plain_text', $this->getSelectedEditor($entity->id(), $field_name), "With cardinality 1, the 'plain_text' editor is selected.");

    // With cardinality >1
    $this->fields->field_text_field_storage->setCardinality(2);
    $this->fields->field_text_field_storage->save();
    $this->assertEquals('form', $this->getSelectedEditor($entity->id(), $field_name), "With cardinality >1, the 'form' editor is selected.");

  }

  /**
   * Tests a textual field, with text filtering, with cardinality 1 and >1,
   * always with an Editor plugin present that supports textual fields with text
   * filtering, but with varying text format compatibility.
   */
  public function testTextWysiwyg() {
    // Enable edit_test module so that the 'wysiwyg' editor becomes available.
    $this->enableModules(['quickedit_test']);
    $this->editorManager = $this->container->get('plugin.manager.quickedit.editor');
    $this->editorSelector = new EditorSelector($this->editorManager, $this->container->get('plugin.manager.field.formatter'));

    $field_name = 'field_textarea';
    $this->createFieldWithStorage(
      $field_name, 'text', 1, 'Long text field',
      // Instance settings.
      [],
      // Widget type & settings.
      'text_textarea',
      ['size' => 42],
      // 'default' formatter type & settings.
      'text_default',
      []
    );

    // Create an entity with values for this text field.
    $entity = EntityTest::create();
    $entity->{$field_name}->value = 'Hello, world!';
    $entity->{$field_name}->format = 'filtered_html';
    $entity->save();

    // Editor selection w/ cardinality 1, text format w/o associated text editor.
    $this->assertEquals('form', $this->getSelectedEditor($entity->id(), $field_name), "With cardinality 1, and the filtered_html text format, the 'form' editor is selected.");

    // Editor selection w/ cardinality 1, text format w/ associated text editor.
    $entity->{$field_name}->format = 'full_html';
    $entity->save();
    $this->assertEquals('wysiwyg', $this->getSelectedEditor($entity->id(), $field_name), "With cardinality 1, and the full_html text format, the 'wysiwyg' editor is selected.");

    // Editor selection with text field, cardinality >1.
    $this->fields->field_textarea_field_storage->setCardinality(2);
    $this->fields->field_textarea_field_storage->save();
    $this->assertEquals('form', $this->getSelectedEditor($entity->id(), $field_name), "With cardinality >1, and both items using the full_html text format, the 'form' editor is selected.");
  }

  /**
   * Tests a number field, with cardinality 1 and >1.
   */
  public function testNumber() {
    $field_name = 'field_nr';
    $this->createFieldWithStorage(
      $field_name, 'integer', 1, 'Simple number field',
      // Instance settings.
      [],
      // Widget type & settings.
      'number',
      [],
      // 'default' formatter type & settings.
      'number_integer',
      []
    );

    // Create an entity with values for this text field.
    $entity = EntityTest::create();
    $entity->{$field_name}->value = 42;
    $entity->save();

    // Editor selection with cardinality 1.
    $this->assertEquals('form', $this->getSelectedEditor($entity->id(), $field_name), "With cardinality 1, the 'form' editor is selected.");

    // Editor selection with cardinality >1.
    $this->fields->field_nr_field_storage->setCardinality(2);
    $this->fields->field_nr_field_storage->save();
    $this->assertEquals('form', $this->getSelectedEditor($entity->id(), $field_name), "With cardinality >1, the 'form' editor is selected.");
  }

}

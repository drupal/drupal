<?php

/**
 * @file
 * Contains \Drupal\edit\Tests\MetadataGeneratorTest.
 */

namespace Drupal\quickedit\Tests;

use Drupal\Core\Language\Language;
use Drupal\quickedit\EditorSelector;
use Drupal\quickedit\MetadataGenerator;
use Drupal\quickedit\Plugin\InPlaceEditorManager;
use Drupal\quickedit_test\MockEditEntityFieldAccessCheck;

/**
 * Test in-place field editing metadata.
 */
class MetadataGeneratorTest extends QuickEditTestBase {

  /**
   * The manager for editor plugins.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $editorManager;

  /**
   * The metadata generator object to be tested.
   *
   * @var \Drupal\quickedit\MetadataGeneratorInterface.php
   */
  protected $metadataGenerator;

  /**
   * The editor selector object to be used by the metadata generator object.
   *
   * @var \Drupal\quickedit\EditorSelectorInterface
   */
  protected $editorSelector;

  /**
   * The access checker object to be used by the metadata generator object.
   *
   * @var \Drupal\quickedit\Access\EditEntityFieldAccessCheckInterface
   */
  protected $accessChecker;

  public static function getInfo() {
    return array(
      'name' => 'In-place field editing metadata',
      'description' => 'Tests in-place field editing metadata generation.',
      'group' => 'Quick Edit',
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->editorManager = $this->container->get('plugin.manager.quickedit.editor');
    $this->accessChecker = new MockEditEntityFieldAccessCheck();
    $this->editorSelector = new EditorSelector($this->editorManager, $this->container->get('plugin.manager.field.formatter'));
    $this->metadataGenerator = new MetadataGenerator($this->accessChecker, $this->editorSelector, $this->editorManager);
  }

  /**
   * Tests a simple entity type, with two different simple fields.
   */
  public function testSimpleEntityType() {
    $field_1_name = 'field_text';
    $field_1_label = 'Simple text field';
    $this->createFieldWithInstance(
      $field_1_name, 'text', 1, $field_1_label,
      // Instance settings.
      array('text_processing' => 0),
      // Widget type & settings.
      'text_textfield',
      array('size' => 42),
      // 'default' formatter type & settings.
      'text_default',
      array()
    );
    $field_2_name = 'field_nr';
    $field_2_label = 'Simple number field';
    $this->createFieldWithInstance(
      $field_2_name, 'integer', 1, $field_2_label,
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
    $this->entity->{$field_1_name}->value = 'Test';
    $this->entity->{$field_2_name}->value = 42;
    $this->entity->save();
    $entity = entity_load('entity_test', $this->entity->id());

    // Verify metadata for field 1.
    $items_1 = $entity->getTranslation(Language::LANGCODE_NOT_SPECIFIED)->get($field_1_name);
    $metadata_1 = $this->metadataGenerator->generateFieldMetadata($items_1, 'default');
    $expected_1 = array(
      'access' => TRUE,
      'label' => 'Simple text field',
      'editor' => 'plain_text',
      'aria' => 'Entity entity_test 1, field Simple text field',
    );
    $this->assertEqual($expected_1, $metadata_1, 'The correct metadata is generated for the first field.');

    // Verify metadata for field 2.
    $items_2 = $entity->getTranslation(Language::LANGCODE_NOT_SPECIFIED)->get($field_2_name);
    $metadata_2 = $this->metadataGenerator->generateFieldMetadata($items_2, 'default');
    $expected_2 = array(
      'access' => TRUE,
      'label' => 'Simple number field',
      'editor' => 'form',
      'aria' => 'Entity entity_test 1, field Simple number field',
    );
    $this->assertEqual($expected_2, $metadata_2, 'The correct metadata is generated for the second field.');
  }

  /**
   * Tests a field whose associated in-place editor generates custom metadata.
   */
  public function testEditorWithCustomMetadata() {
    $this->installSchema('system', 'url_alias');
    $this->enableModules(array('user', 'filter'));

    // Enable edit_test module so that the WYSIWYG editor becomes available.
    $this->enableModules(array('quickedit_test'));
    $this->editorManager = $this->container->get('plugin.manager.quickedit.editor');
    $this->editorSelector = new EditorSelector($this->editorManager, $this->container->get('plugin.manager.field.formatter'));
    $this->metadataGenerator = new MetadataGenerator($this->accessChecker, $this->editorSelector, $this->editorManager);

    $this->editorManager = $this->container->get('plugin.manager.quickedit.editor');
    $this->editorSelector = new EditorSelector($this->editorManager, $this->container->get('plugin.manager.field.formatter'));
    $this->metadataGenerator = new MetadataGenerator($this->accessChecker, $this->editorSelector, $this->editorManager);

    // Create a rich text field.
    $field_name = 'field_rich';
    $field_label = 'Rich text field';
    $this->createFieldWithInstance(
      $field_name, 'text', 1, $field_label,
      // Instance settings.
      array('text_processing' => 1),
      // Widget type & settings.
      'text_textfield',
      array('size' => 42),
      // 'default' formatter type & settings.
      'text_default',
      array()
    );

    // Create a text format.
    $full_html_format = entity_create('filter_format', array(
      'format' => 'full_html',
      'name' => 'Full HTML',
      'weight' => 1,
      'filters' => array(
        'filter_htmlcorrector' => array('status' => 1),
      ),
    ));
    $full_html_format->save();

    // Create an entity with values for this rich text field.
    $this->entity = entity_create('entity_test');
    $this->entity->{$field_name}->value = 'Test';
    $this->entity->{$field_name}->format = 'full_html';
    $this->entity->save();
    $entity = entity_load('entity_test', $this->entity->id());

    // Verify metadata.
    $items = $entity->getTranslation(Language::LANGCODE_NOT_SPECIFIED)->get($field_name);
    $metadata = $this->metadataGenerator->generateFieldMetadata($items, 'default');
    $expected = array(
      'access' => TRUE,
      'label' => 'Rich text field',
      'editor' => 'wysiwyg',
      'aria' => 'Entity entity_test 1, field Rich text field',
      'custom' => array(
        'format' => 'full_html'
      ),
    );
    $this->assertEqual($expected, $metadata); //, 'The correct metadata (including custom metadata) is generated.');
  }

}

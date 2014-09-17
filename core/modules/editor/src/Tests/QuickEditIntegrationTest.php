<?php

/**
 * @file
 * Contains \Drupal\editor\Tests\QuickEditIntegrationTest.
 */

namespace Drupal\editor\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Language\LanguageInterface;
use Drupal\quickedit\EditorSelector;
use Drupal\quickedit\MetadataGenerator;
use Drupal\quickedit\Plugin\InPlaceEditorManager;
use Drupal\quickedit\Tests\QuickEditTestBase;
use Drupal\quickedit_test\MockEditEntityFieldAccessCheck;
use Drupal\editor\EditorController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests Edit module integration (Editor module's inline editing support).
 *
 * @group editor
 */
class QuickEditIntegrationTest extends QuickEditTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('editor', 'editor_test');

  /**
   * The manager for editor plug-ins.
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

  /**
   * The name of the field ued for tests.
   *
   * @var string
   */
  protected $field_name;

  protected function setUp() {
    parent::setUp();

    // Install the Filter module.
    $this->installSchema('system', 'url_alias');

    // Create a field.
    $this->field_name = 'field_textarea';
    $this->createFieldWithInstance(
      $this->field_name, 'text', 1, 'Long text field',
      // Instance settings.
      array(),
      // Widget type & settings.
      'text_textarea',
      array('size' => 42),
      // 'default' formatter type & settings.
      'text_default',
      array()
    );

    // Create text format.
    $full_html_format = entity_create('filter_format', array(
      'format' => 'full_html',
      'name' => 'Full HTML',
      'weight' => 1,
      'filters' => array(),
    ));
    $full_html_format->save();

    // Associate text editor with text format.
    $editor = entity_create('editor', array(
      'format' => $full_html_format->format,
      'editor' => 'unicorn',
    ));
    $editor->save();

    // Also create a text format without an associated text editor.
    entity_create('filter_format', array(
      'format' => 'no_editor',
      'name' => 'No Text Editor',
      'weight' => 2,
      'filters' => array(),
    ))->save();
  }

  /**
   * Returns the in-place editor that Edit selects.
   */
  protected function getSelectedEditor($entity_id, $field_name, $view_mode = 'default') {
    $entity = entity_load('entity_test', $entity_id, TRUE);
    $items = $entity->getTranslation(LanguageInterface::LANGCODE_NOT_SPECIFIED)->get($field_name);
    $options = entity_get_display('entity_test', 'entity_test', $view_mode)->getComponent($field_name);
    return $this->editorSelector->getEditor($options['type'], $items);
  }

  /**
   * Tests editor selection when the Editor module is present.
   *
   * Tests a textual field, with text filtering, with cardinality 1 and >1,
   * always with a ProcessedTextEditor plug-in present, but with varying text
   * format compatibility.
   */
  public function testEditorSelection() {
    $this->editorManager = $this->container->get('plugin.manager.quickedit.editor');
    $this->editorSelector = $this->container->get('quickedit.editor.selector');

    // Create an entity with values for this text field.
    $entity = entity_create('entity_test');
    $entity->{$this->field_name}->value = 'Hello, world!';
    $entity->{$this->field_name}->format = 'filtered_html';
    $entity->save();

    // Editor selection w/ cardinality 1, text format w/o associated text editor.
    $this->assertEqual('form', $this->getSelectedEditor($entity->id(), $this->field_name), "With cardinality 1, and the filtered_html text format, the 'form' editor is selected.");

    // Editor selection w/ cardinality 1, text format w/ associated text editor.
    $entity->{$this->field_name}->format = 'full_html';
    $entity->save();
    $this->assertEqual('editor', $this->getSelectedEditor($entity->id(), $this->field_name), "With cardinality 1, and the full_html text format, the 'editor' editor is selected.");

    // Editor selection with text processing, cardinality >1
    $this->fields->field_textarea_field_storage->cardinality = 2;
    $this->fields->field_textarea_field_storage->save();
    $this->assertEqual('form', $this->getSelectedEditor($entity->id(), $this->field_name), "With cardinality >1, and both items using the full_html text format, the 'form' editor is selected.");
  }

  /**
   * Tests (custom) metadata when the formatted text editor is used.
   */
  public function testMetadata() {
    $this->editorManager = $this->container->get('plugin.manager.quickedit.editor');
    $this->accessChecker = new MockEditEntityFieldAccessCheck();
    $this->editorSelector = $this->container->get('quickedit.editor.selector');
    $this->metadataGenerator = new MetadataGenerator($this->accessChecker, $this->editorSelector, $this->editorManager);

    // Create an entity with values for the field.
    $entity = entity_create('entity_test');
    $entity->{$this->field_name}->value = 'Test';
    $entity->{$this->field_name}->format = 'full_html';
    $entity->save();
    $entity = entity_load('entity_test', $entity->id());

    // Verify metadata.
    $items = $entity->getTranslation(LanguageInterface::LANGCODE_NOT_SPECIFIED)->get($this->field_name);
    $metadata = $this->metadataGenerator->generateFieldMetadata($items, 'default');
    $expected = array(
      'access' => TRUE,
      'label' => 'Long text field',
      'editor' => 'editor',
      'aria' => 'Entity entity_test 1, field Long text field',
      'custom' => array(
        'format' => 'full_html',
        'formatHasTransformations' => FALSE,
      ),
    );
    $this->assertEqual($expected, $metadata, 'The correct metadata (including custom metadata) is generated.');
  }

  /**
   * Tests in-place editor attachments when the Editor module is present.
   */
  public function testAttachments() {
    $this->editorSelector = $this->container->get('quickedit.editor.selector');

    $editors = array('editor');
    $attachments = $this->editorSelector->getEditorAttachments($editors);
    $this->assertIdentical($attachments, array('library' => array('editor/quickedit.inPlaceEditor.formattedText')), "Expected attachments for Editor module's in-place editor found.");
  }

  /**
   * Tests GetUntransformedTextCommand AJAX command.
   */
  public function testGetUntransformedTextCommand() {
    // Create an entity with values for the field.
    $entity = entity_create('entity_test');
    $entity->{$this->field_name}->value = 'Test';
    $entity->{$this->field_name}->format = 'full_html';
    $entity->save();
    $entity = entity_load('entity_test', $entity->id());

    // Verify AJAX response.
    $controller = new EditorController();
    $request = new Request();
    $response = $controller->getUntransformedText($entity, $this->field_name, LanguageInterface::LANGCODE_NOT_SPECIFIED, 'default');
    $expected = array(
      array(
        'command' => 'editorGetUntransformedText',
        'data' => 'Test',
      )
    );
    $this->assertEqual(Json::encode($expected), $response->prepare($request)->getContent(), 'The GetUntransformedTextCommand AJAX command works correctly.');
  }

}

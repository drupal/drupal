<?php

namespace Drupal\Tests\editor\Kernel;

use Drupal\Component\Serialization\Json;
use Drupal\Core\EventSubscriber\AjaxResponseSubscriber;
use Drupal\Core\Language\LanguageInterface;
use Drupal\editor\Entity\Editor;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\quickedit\MetadataGenerator;
use Drupal\Tests\quickedit\Kernel\QuickEditTestBase;
use Drupal\quickedit_test\MockQuickEditEntityFieldAccessCheck;
use Drupal\editor\EditorController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Drupal\filter\Entity\FilterFormat;

/**
 * Tests Edit module integration (Editor module's inline editing support).
 *
 * @group editor
 */
class QuickEditIntegrationTest extends QuickEditTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['editor', 'editor_test'];

  /**
   * The manager for editor plug-ins.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $editorManager;

  /**
   * The metadata generator object to be tested.
   *
   * @var \Drupal\quickedit\MetadataGeneratorInterface
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
   * @var \Drupal\quickedit\Access\QuickEditEntityFieldAccessCheckInterface
   */
  protected $accessChecker;

  /**
   * The name of the field ued for tests.
   *
   * @var string
   */
  protected $fieldName;

  protected function setUp(): void {
    parent::setUp();

    // Install the Filter module.

    // Create a field.
    $this->fieldName = 'field_textarea';
    $this->createFieldWithStorage(
      $this->fieldName, 'text', 1, 'Long text field',
      // Instance settings.
      [],
      // Widget type & settings.
      'text_textarea',
      ['size' => 42],
      // 'default' formatter type & settings.
      'text_default',
      []
    );

    // Create text format.
    $full_html_format = FilterFormat::create([
      'format' => 'full_html',
      'name' => 'Full HTML',
      'weight' => 1,
      'filters' => [],
    ]);
    $full_html_format->save();

    // Associate text editor with text format.
    $editor = Editor::create([
      'format' => $full_html_format->id(),
      'editor' => 'unicorn',
    ]);
    $editor->save();

    // Also create a text format without an associated text editor.
    FilterFormat::create([
      'format' => 'no_editor',
      'name' => 'No Text Editor',
      'weight' => 2,
      'filters' => [],
    ])->save();
  }

  /**
   * Returns the in-place editor that quickedit selects.
   *
   * @param int $entity_id
   *   An entity ID.
   * @param string $field_name
   *   A field name.
   * @param string $view_mode
   *   A view mode.
   *
   * @return string
   *   Returns the selected in-place editor.
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
    $entity = EntityTest::create();
    $entity->{$this->fieldName}->value = 'Hello, world!';
    $entity->{$this->fieldName}->format = 'filtered_html';
    $entity->save();

    // Editor selection w/ cardinality 1, text format w/o associated text editor.
    $this->assertEqual('form', $this->getSelectedEditor($entity->id(), $this->fieldName), "With cardinality 1, and the filtered_html text format, the 'form' editor is selected.");

    // Editor selection w/ cardinality 1, text format w/ associated text editor.
    $entity->{$this->fieldName}->format = 'full_html';
    $entity->save();
    $this->assertEqual('editor', $this->getSelectedEditor($entity->id(), $this->fieldName), "With cardinality 1, and the full_html text format, the 'editor' editor is selected.");

    // Editor selection with text processing, cardinality >1
    $this->fields->field_textarea_field_storage->setCardinality(2);
    $this->fields->field_textarea_field_storage->save();
    $this->assertEqual('form', $this->getSelectedEditor($entity->id(), $this->fieldName), "With cardinality >1, and both items using the full_html text format, the 'form' editor is selected.");
  }

  /**
   * Tests (custom) metadata when the formatted text editor is used.
   */
  public function testMetadata() {
    $this->editorManager = $this->container->get('plugin.manager.quickedit.editor');
    $this->accessChecker = new MockQuickEditEntityFieldAccessCheck();
    $this->editorSelector = $this->container->get('quickedit.editor.selector');
    $this->metadataGenerator = new MetadataGenerator($this->accessChecker, $this->editorSelector, $this->editorManager);

    // Create an entity with values for the field.
    $entity = EntityTest::create();
    $entity->{$this->fieldName}->value = 'Test';
    $entity->{$this->fieldName}->format = 'full_html';
    $entity->save();
    $entity = EntityTest::load($entity->id());

    // Verify metadata.
    $items = $entity->get($this->fieldName);
    $metadata = $this->metadataGenerator->generateFieldMetadata($items, 'default');
    $expected = [
      'access' => TRUE,
      'label' => 'Long text field',
      'editor' => 'editor',
      'custom' => [
        'format' => 'full_html',
        'formatHasTransformations' => FALSE,
      ],
    ];
    $this->assertEqual($expected, $metadata, 'The correct metadata (including custom metadata) is generated.');
  }

  /**
   * Tests in-place editor attachments when the Editor module is present.
   */
  public function testAttachments() {
    $this->editorSelector = $this->container->get('quickedit.editor.selector');

    $editors = ['editor'];
    $attachments = $this->editorSelector->getEditorAttachments($editors);
    $this->assertSame(['library' => ['editor/quickedit.inPlaceEditor.formattedText']], $attachments, "Expected attachments for Editor module's in-place editor found.");
  }

  /**
   * Tests GetUntransformedTextCommand AJAX command.
   */
  public function testGetUntransformedTextCommand() {
    // Create an entity with values for the field.
    $entity = EntityTest::create();
    $entity->{$this->fieldName}->value = 'Test';
    $entity->{$this->fieldName}->format = 'full_html';
    $entity->save();
    $entity = EntityTest::load($entity->id());

    // Verify AJAX response.
    $controller = new EditorController();
    $request = new Request();
    $response = $controller->getUntransformedText($entity, $this->fieldName, LanguageInterface::LANGCODE_DEFAULT, 'default');
    $expected = [
      [
        'command' => 'editorGetUntransformedText',
        'data' => 'Test',
      ],
    ];

    $ajax_response_attachments_processor = \Drupal::service('ajax_response.attachments_processor');
    $subscriber = new AjaxResponseSubscriber($ajax_response_attachments_processor);
    $event = new ResponseEvent(
      \Drupal::service('http_kernel'),
      $request,
      HttpKernelInterface::MASTER_REQUEST,
      $response
    );
    $subscriber->onResponse($event);

    $this->assertEqual(Json::encode($expected), $response->getContent(), 'The GetUntransformedTextCommand AJAX command works correctly.');
  }

}

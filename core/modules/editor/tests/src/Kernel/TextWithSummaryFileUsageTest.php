<?php

declare(strict_types=1);

namespace Drupal\Tests\editor\Kernel;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\editor\Entity\Editor;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\filter\Entity\FilterFormat;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests tracking of file usage by the Text Editor module.
 */
#[Group('editor')]
#[RunTestsInSeparateProcesses]
class TextWithSummaryFileUsageTest extends EntityKernelTestBase {

  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['editor', 'editor_test', 'node', 'file'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('file');
    $this->installSchema('node', ['node_access']);
    $this->installSchema('file', ['file_usage']);
    $this->installConfig(['node']);

    // Add text formats.
    $filtered_html_format = FilterFormat::create([
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'weight' => 0,
      'filters' => [],
    ]);
    $filtered_html_format->save();

    // Set up text editor.
    $editor = Editor::create([
      'format' => 'filtered_html',
      'editor' => 'unicorn',
      'image_upload' => [
        'status' => FALSE,
      ],
    ]);
    $editor->save();

    // Create a node type for testing.
    $this->createContentType(['type' => 'page', 'name' => 'page'], FALSE);
    FieldStorageConfig::create([
      'field_name' => 'body',
      'type' => 'text_with_summary',
      'entity_type' => 'node',
      'cardinality' => 1,
    ])->save();

    $fieldStorage = FieldStorageConfig::loadByName('node', 'body');
    FieldConfig::create([
      'field_storage' => $fieldStorage,
      'bundle' => 'page',
      'label' => 'Body',
      'settings' => [
        'display_summary' => TRUE,
        'allowed_formats' => [],
      ],
    ])->save();

    // Set cardinality for body field.
    FieldStorageConfig::loadByName('node', 'body')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->save();
  }

  /**
   * Tests the configurable text editor manager.
   */
  public function testEditorEntityHooks(): void {
    $image_paths = [
      0 => 'core/misc/druplicon.png',
      1 => 'core/misc/tree.png',
      2 => 'core/misc/help.png',
    ];

    $image_entities = [];
    foreach ($image_paths as $key => $image_path) {
      $image = File::create();
      $image->setFileUri($image_path);
      $image->setFilename(basename($image->getFileUri()));
      $image->save();

      $file_usage = $this->container->get('file.usage');
      $this->assertSame([], $file_usage->listUsage($image), 'The image ' . $image_paths[$key] . ' has zero usages.');

      $image_entities[] = $image;
    }

    $body = [];
    foreach ($image_entities as $key => $image_entity) {
      // Don't be rude, say hello.
      $body_value = '<p>Hello, world!</p>';
      // Test handling of a valid image entry.
      $body_value .= '<img src="awesome-llama-' . $key . '.jpg" data-entity-type="file" data-entity-uuid="' . $image_entity->uuid() . '" />';
      // Test handling of an invalid data-entity-uuid attribute.
      $body_value .= '<img src="awesome-llama-' . $key . '.jpg" data-entity-type="file" data-entity-uuid="invalid-entity-uuid-value" />';
      // Test handling of an invalid data-entity-type attribute.
      $body_value .= '<img src="awesome-llama-' . $key . '.jpg" data-entity-type="invalid-entity-type-value" data-entity-uuid="' . $image_entity->uuid() . '" />';
      // Test handling of a non-existing UUID.
      $body_value .= '<img src="awesome-llama-' . $key . '.jpg" data-entity-type="file" data-entity-uuid="30aac704-ba2c-40fc-b609-9ed121aa90f4" />';

      $body[] = [
        'value' => $body_value,
        'format' => 'filtered_html',
      ];
    }

    // Test editor_entity_insert(): increment.
    $this->createUser();
    $node = Node::create([
      'type' => 'page',
      'title' => 'test',
      'body' => $body,
      'uid' => 1,
    ]);
    $node->save();

    $original_values = [];
    for ($i = 0; $i < count($image_entities); $i++) {
      $original_value = $node->body[$i]->value;
      $original_values[$i] = $original_value;
    }
    $node->save();

    // Populate both the body and summary. Because this will be the same
    // revision of the same node, it will record only one usage.
    foreach ($original_values as $key => $original_value) {
      $node->body[$key]->value = $original_value;
      $node->body[$key]->summary = $original_value;
    }
    $node->save();
    foreach ($image_entities as $key => $image_entity) {
      $this->assertSame(['editor' => ['node' => [1 => '1']]], $file_usage->listUsage($image_entity), 'The image ' . $image_paths[$key] . ' has 1 usage.');
    }

    // Empty out the body value, but keep the summary. The number of usages
    // should not change.
    foreach ($original_values as $key => $original_value) {
      $node->body[$key]->value = '';
      $node->body[$key]->summary = $original_value;
    }
    $node->save();
    foreach ($image_entities as $key => $image_entity) {
      $this->assertSame(['editor' => ['node' => [1 => '1']]], $file_usage->listUsage($image_entity), 'The image ' . $image_paths[$key] . ' has 1 usage.');
    }

    // Empty out the body and summary. The number of usages should decrease by
    // one.
    foreach ($original_values as $key => $original_value) {
      $node->body[$key]->value = '';
      $node->body[$key]->summary = '';
    }
    $node->save();
    foreach ($image_entities as $key => $image_entity) {
      $this->assertSame([], $file_usage->listUsage($image_entity), 'The image ' . $image_paths[$key] . ' has 0 usages.');
    }

  }

}

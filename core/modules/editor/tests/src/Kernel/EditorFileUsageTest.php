<?php

declare(strict_types=1);

namespace Drupal\Tests\editor\Kernel;

use Drupal\editor\Entity\Editor;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\file\Entity\File;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\filter\Entity\FilterFormat;

/**
 * Tests tracking of file usage by the Text Editor module.
 *
 * @group editor
 */
class EditorFileUsageTest extends EntityKernelTestBase {

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

    // Set cardinality for body field.
    FieldStorageConfig::loadByName('node', 'body')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->save();

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
    $type = NodeType::create(['type' => 'page', 'name' => 'page']);
    $type->save();
    node_add_body_field($type);
    FieldStorageConfig::create([
      'field_name' => 'description',
      'entity_type' => 'node',
      'type' => 'editor_test_text_long',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();
    FieldConfig::create([
      'field_name' => 'description',
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => 'Description',
    ])->save();
  }

  /**
   * Tests file save operations when node with referenced files is saved.
   */
  public function testFileSaveOperations(): void {
    $permanent_image = File::create([
      'uri' => 'core/misc/druplicon.png',
      'status' => 1,
    ]);
    $permanent_image->save();
    $temporary_image = File::create([
      'uri' => 'core/misc/tree.png',
      'status' => 0,
    ]);
    $temporary_image->save();
    $body_value = '<img data-entity-type="file" data-entity-uuid="' . $permanent_image->uuid() . '" />';
    $body_value .= '<img data-entity-type="file" data-entity-uuid="' . $temporary_image->uuid() . '" />';
    $body[] = [
      'value' => $body_value,
      'format' => 'filtered_html',
    ];
    $node = Node::create([
      'type' => 'page',
      'title' => 'test',
      'body' => $body,
      'uid' => 1,
    ]);
    $node->save();

    $file_save_count = \Drupal::state()->get('editor_test.file_save_count', []);
    $this->assertEquals(1, $file_save_count[$permanent_image->getFilename()]);
    $this->assertEquals(2, $file_save_count[$temporary_image->getFilename()]);

    // Assert both images are now permanent.
    $permanent_image = File::load($permanent_image->id());
    $temporary_image = File::load($temporary_image->id());
    $this->assertTrue($permanent_image->isPermanent(), 'Permanent image was saved as permanent.');
    $this->assertTrue($temporary_image->isPermanent(), 'Temporary image was saved as permanent.');
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
      $image->setFilename(\Drupal::service('file_system')->basename($image->getFileUri()));
      $image->save();

      $file_usage = $this->container->get('file.usage');
      $this->assertSame([], $file_usage->listUsage($image), 'The image ' . $image_paths[$key] . ' has zero usages.');

      $image_entities[] = $image;
    }

    $body = [];
    $description = [];
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
      $description[] = [
        'value' => 'something',
        'format' => 'filtered_html',
      ];
    }

    // Test editor_entity_insert(): increment.
    $this->createUser();
    $node = $node = Node::create([
      'type' => 'page',
      'title' => 'test',
      'body' => $body,
      'description' => $description,
      'uid' => 1,
    ]);
    $node->save();
    foreach ($image_entities as $key => $image_entity) {
      $this->assertSame(['editor' => ['node' => [1 => '1']]], $file_usage->listUsage($image_entity), 'The image ' . $image_paths[$key] . ' has 1 usage.');
    }

    // Test editor_entity_update(): increment, twice, by creating new revisions.
    $node->setNewRevision(TRUE);
    $node->save();
    $second_revision_id = $node->getRevisionId();
    $node->setNewRevision(TRUE);
    $node->save();
    foreach ($image_entities as $key => $image_entity) {
      $this->assertSame(['editor' => ['node' => [1 => '3']]], $file_usage->listUsage($image_entity), 'The image ' . $image_paths[$key] . ' has 3 usages.');
    }

    // Test hook_entity_update(): decrement, by modifying the last revision:
    // remove the data-entity-type attribute from the body field.
    $original_values = [];
    for ($i = 0; $i < count($image_entities); $i++) {
      $original_value = $node->body[$i]->value;
      $new_value = str_replace('data-entity-type', 'data-entity-type-modified', $original_value);
      $node->body[$i]->value = $new_value;
      $original_values[$i] = $original_value;
    }
    $node->save();
    foreach ($image_entities as $key => $image_entity) {
      $this->assertSame(['editor' => ['node' => [1 => '2']]], $file_usage->listUsage($image_entity), 'The image ' . $image_paths[$key] . ' has 2 usages.');
    }

    // Test editor_entity_update(): increment again by creating a new revision:
    // read the data- attributes to the body field.
    $node->setNewRevision(TRUE);
    foreach ($original_values as $key => $original_value) {
      $node->body[$key]->value = $original_value;
    }
    $node->save();
    foreach ($image_entities as $key => $image_entity) {
      $this->assertSame(['editor' => ['node' => [1 => '3']]], $file_usage->listUsage($image_entity), 'The image ' . $image_paths[$key] . ' has 3 usages.');
    }

    // Test hook_entity_update(): decrement, by modifying the last revision:
    // remove the data-entity-uuid attribute from the body field.
    foreach ($original_values as $key => $original_value) {
      $original_value = $node->body[$key]->value;
      $new_value = str_replace('data-entity-type', 'data-entity-type-modified', $original_value);
      $node->body[$key]->value = $new_value;
    }
    $node->save();
    foreach ($image_entities as $key => $image_entity) {
      $this->assertSame(['editor' => ['node' => [1 => '2']]], $file_usage->listUsage($image_entity), 'The image ' . $image_paths[$key] . ' has 2 usages.');
    }

    // Test hook_entity_update(): increment, by modifying the last revision:
    // read the data- attributes to the body field.
    foreach ($original_values as $key => $original_value) {
      $node->body[$key]->value = $original_value;
    }
    $node->save();
    foreach ($image_entities as $key => $image_entity) {
      $this->assertSame(['editor' => ['node' => [1 => '3']]], $file_usage->listUsage($image_entity), 'The image ' . $image_paths[$key] . ' has 3 usages.');
    }

    // Test editor_entity_revision_delete(): decrement, by deleting a revision.
    $this->container->get('entity_type.manager')->getStorage('node')->deleteRevision($second_revision_id);
    foreach ($image_entities as $key => $image_entity) {
      $this->assertSame(['editor' => ['node' => [1 => '2']]], $file_usage->listUsage($image_entity), 'The image ' . $image_paths[$key] . ' has 2 usages.');
    }

    // Populate both the body and summary. Because this will be the same
    // revision of the same node, it will record only one usage.
    foreach ($original_values as $key => $original_value) {
      $node->body[$key]->value = $original_value;
      $node->body[$key]->summary = $original_value;
    }
    $node->save();
    foreach ($image_entities as $key => $image_entity) {
      $this->assertSame(['editor' => ['node' => [1 => '2']]], $file_usage->listUsage($image_entity), 'The image ' . $image_paths[$key] . ' has 2 usages.');
    }

    // Empty out the body value, but keep the summary. The number of usages
    // should not change.
    foreach ($original_values as $key => $original_value) {
      $node->body[$key]->value = '';
      $node->body[$key]->summary = $original_value;
    }
    $node->save();
    foreach ($image_entities as $key => $image_entity) {
      $this->assertSame(['editor' => ['node' => [1 => '2']]], $file_usage->listUsage($image_entity), 'The image ' . $image_paths[$key] . ' has 2 usages.');
    }

    // Empty out the body and summary. The number of usages should decrease by
    // one.
    foreach ($original_values as $key => $original_value) {
      $node->body[$key]->value = '';
      $node->body[$key]->summary = '';
    }
    $node->save();
    foreach ($image_entities as $key => $image_entity) {
      $this->assertSame(['editor' => ['node' => [1 => '1']]], $file_usage->listUsage($image_entity), 'The image ' . $image_paths[$key] . ' has 1 usage.');
    }

    // Set the field of a custom field type that is a subclass of
    // Drupal\text\Plugin\Field\FieldType\TextItemBase. The number of usages
    // should increase by one.
    foreach ($original_values as $key => $original_value) {
      $node->description[$key]->value = $original_value;
    }
    $node->save();
    foreach ($image_entities as $key => $image_entity) {
      $this->assertSame(['editor' => ['node' => [1 => '2']]], $file_usage->listUsage($image_entity), 'The image ' . $image_paths[$key] . ' has 2 usages.');
    }

    // Test editor_entity_delete().
    $node->delete();
    foreach ($image_entities as $key => $image_entity) {
      $this->assertSame([], $file_usage->listUsage($image_entity), 'The image ' . $image_paths[$key] . ' has zero usages again.');
    }
  }

}

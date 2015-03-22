<?php

/**
 * @file
 * Contains \Drupal\editor\Tests\EditorFileUsageTest.
 */

namespace Drupal\editor\Tests;

use Drupal\system\Tests\Entity\EntityUnitTestBase;

/**
 * Tests tracking of file usage by the Text Editor module.
 *
 * @group editor
 */
class EditorFileUsageTest extends EntityUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('editor', 'editor_test', 'node', 'file');

  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('file');
    $this->installSchema('node', array('node_access'));
    $this->installSchema('file', array('file_usage'));
    $this->installConfig(['node']);

    // Add text formats.
    $filtered_html_format = entity_create('filter_format', array(
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'weight' => 0,
      'filters' => array(),
    ));
    $filtered_html_format->save();

    // Set up text editor.
    $editor = entity_create('editor', array(
      'format' => 'filtered_html',
      'editor' => 'unicorn',
    ));
    $editor->save();

    // Create a node type for testing.
    $type = entity_create('node_type', array('type' => 'page', 'name' => 'page'));
    $type->save();
    node_add_body_field($type);
  }

  /**
   * Tests the configurable text editor manager.
   */
  public function testEditorEntityHooks() {
    $image = entity_create('file');
    $image->setFileUri('core/misc/druplicon.png');
    $image->setFilename(drupal_basename($image->getFileUri()));
    $image->save();
    $file_usage = $this->container->get('file.usage');
    $this->assertIdentical(array(), $file_usage->listUsage($image), 'The image has zero usages.');

    $body_value = '<p>Hello, world!</p><img src="awesome-llama.jpg" data-entity-type="file" data-entity-uuid="' . $image->uuid() . '" />';
    // Test handling of an invalid data-entity-uuid attribute.
    $body_value .= '<img src="awesome-llama.jpg" data-entity-type="file" data-entity-uuid="invalid-entity-uuid-value" />';
    // Test handling of an invalid data-entity-type attribute.
    $body_value .= '<img src="awesome-llama.jpg" data-entity-type="invalid-entity-type-value" data-entity-uuid="' . $image->uuid() . '" />';
    // Test handling of a non-existing UUID.
    $body_value .= '<img src="awesome-llama.jpg" data-entity-type="file" data-entity-uuid="30aac704-ba2c-40fc-b609-9ed121aa90f4" />';
    // Test editor_entity_insert(): increment.
    $this->createUser();
    $node = entity_create('node', array(
      'type' => 'page',
      'title' => 'test',
      'body' => array(
        'value' => $body_value,
        'format' => 'filtered_html',
      ),
      'uid' => 1,
    ));
    $node->save();
    $this->assertIdentical(array('editor' => array('node' => array(1 => '1'))), $file_usage->listUsage($image), 'The image has 1 usage.');

    // Test editor_entity_update(): increment, twice, by creating new revisions.
    $node->setNewRevision(TRUE);
    $node->save();
    $second_revision_id = $node->getRevisionId();
    $node->setNewRevision(TRUE);
    $node->save();
    $this->assertIdentical(array('editor' => array('node' => array(1 => '3'))), $file_usage->listUsage($image), 'The image has 3 usages.');

    // Test hook_entity_update(): decrement, by modifying the last revision:
    // remove the data-entity-type attribute from the body field.
    $body = $node->get('body')->first()->get('value');
    $original_value = $body->getValue();
    $new_value = str_replace('data-entity-type', 'data-entity-type-modified', $original_value);
    $body->setValue($new_value);
    $node->save();
    $this->assertIdentical(array('editor' => array('node' => array(1 => '2'))), $file_usage->listUsage($image), 'The image has 2 usages.');

    // Test editor_entity_update(): increment again by creating a new revision:
    // read the data- attributes to the body field.
    $node->setNewRevision(TRUE);
    $node->get('body')->first()->get('value')->setValue($original_value);
    $node->save();
    $this->assertIdentical(array('editor' => array('node' => array(1 => '3'))), $file_usage->listUsage($image), 'The image has 3 usages.');

    // Test hook_entity_update(): decrement, by modifying the last revision:
    // remove the data-entity-uuid attribute from the body field.
    $body = $node->get('body')->first()->get('value');
    $new_value = str_replace('data-entity-uuid', 'data-entity-uuid-modified', $original_value);
    $body->setValue($new_value);
    $node->save();
    $this->assertIdentical(array('editor' => array('node' => array(1 => '2'))), $file_usage->listUsage($image), 'The image has 2 usages.');

    // Test hook_entity_update(): increment, by modifying the last revision:
    // read the data- attributes to the body field.
    $node->get('body')->first()->get('value')->setValue($original_value);
    $node->save();
    $this->assertIdentical(array('editor' => array('node' => array(1 => '3'))), $file_usage->listUsage($image), 'The image has 3 usages.');

    // Test editor_entity_revision_delete(): decrement, by deleting a revision.
    entity_revision_delete('node', $second_revision_id);
    $this->assertIdentical(array('editor' => array('node' => array(1 => '2'))), $file_usage->listUsage($image), 'The image has 2 usages.');

    // Test editor_entity_delete().
    $node->delete();
    $this->assertIdentical(array(), $file_usage->listUsage($image), 'The image has zero usages again.');
  }

}

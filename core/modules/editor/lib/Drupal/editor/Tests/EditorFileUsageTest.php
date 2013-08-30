<?php

/**
 * @file
 * Contains \Drupal\editor\Tests\EditorFileUsageTest.
 */

namespace Drupal\editor\Tests;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Unit tests for editor.module's entity hooks to track file usage.
 */
class EditorFileUsageTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'editor', 'editor_test', 'filter', 'node', 'entity', 'field', 'text', 'field_sql_storage', 'file');

  public static function getInfo() {
    return array(
      'name' => 'Text Editor file usage',
      'description' => 'Tests tracking of file usage by the Text Editor module.',
      'group' => 'Text Editor',
    );
  }

  function setUp() {
    parent::setUp();
    $this->installSchema('system', 'url_alias');
    $this->installSchema('node', 'node');
    $this->installSchema('node', 'node_access');
    $this->installSchema('node', 'node_field_data');
    $this->installSchema('node', 'node_field_revision');
    $this->installSchema('file', 'file_managed');
    $this->installSchema('file', 'file_usage');

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
  }

  /**
   * Tests the configurable text editor manager.
   */
  function testEditorEntityHooks() {
    $image = entity_create('file', array());
    $image->setFileUri('core/misc/druplicon.png');
    $image->setFilename(drupal_basename($image->getFileUri()));
    $image->save();
    $this->assertIdentical(array(), file_usage()->listUsage($image), 'The image has zero usages.');

    // Test editor_entity_insert(): increment.
    $node = entity_create('node', array(
      'type' => 'page',
      'title' => 'test',
      'body' => array(
        'value' => '<p>Hello, world!</p><img src="awesome-llama.jpg" data-editor-file-uuid="' . $image->uuid() . '" />',
        'format' => 'filtered_html',
      )
    ));
    $node->save();
    $this->assertIdentical(array('editor' => array('node' => array(1 => '1'))), file_usage()->listUsage($image), 'The image has 1 usage.');

    // Test editor_entity_update(): increment, twice, by creating new revisions.
    $node->setNewRevision(TRUE);
    $node->save();
    $second_revision_id = $node->getRevisionId();
    $node->setNewRevision(TRUE);
    $node->save();
    $this->assertIdentical(array('editor' => array('node' => array(1 => '3'))), file_usage()->listUsage($image), 'The image has 3 usages.');

    // Test hook_entity_update(): decrement, by modifying the last revision:
    // remove the data- attribute from the body field.
    $body = $node->get('body')->offsetGet(0)->get('value');
    $original_value = $body->getValue();
    $new_value = str_replace('data-editor-file-uuid', 'data-editor-file-uuid-modified', $original_value);
    $body->setValue($new_value);
    $node->save();
    $this->assertIdentical(array('editor' => array('node' => array(1 => '2'))), file_usage()->listUsage($image), 'The image has 2 usages.');

    // Test hook_entity_update(): increment, by modifying the last revision:
    // readd the data- attribute to the body field.
    $node->get('body')->offsetGet(0)->get('value')->setValue($original_value);
    $node->save();
    $this->assertIdentical(array('editor' => array('node' => array(1 => '3'))), file_usage()->listUsage($image), 'The image has 3 usages.');

    // Test editor_entity_revision_delete(): decrement, by deleting a revision.
    entity_revision_delete('node', $second_revision_id);
    $this->assertIdentical(array('editor' => array('node' => array(1 => '2'))), file_usage()->listUsage($image), 'The image has 2 usages.');

    // Test editor_entity_delete().
    $node->delete();
    $this->assertIdentical(array(), file_usage()->listUsage($image), 'The image has zero usages again.');
  }

}

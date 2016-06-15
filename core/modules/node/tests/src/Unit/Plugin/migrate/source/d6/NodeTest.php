<?php

namespace Drupal\Tests\node\Unit\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D6 node source plugin.
 *
 * @group node
 */
class NodeTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\node\Plugin\migrate\source\d6\Node';

  protected $migrationConfiguration = array(
    'id' => 'test',
    'source' => array(
      'plugin' => 'd6_node',
    ),
  );

  protected $expectedResults = array(
    array(
      // Node fields.
      'nid' => 1,
      'vid' => 1,
      'type' => 'page',
      'language' => 'en',
      'title' => 'node title 1',
      'uid' => 1,
      'status' => 1,
      'created' => 1279051598,
      'changed' => 1279051598,
      'comment' => 2,
      'promote' => 1,
      'moderate' => 0,
      'sticky' => 0,
      'tnid' => 0,
      'translate' => 0,
      // Node revision fields.
      'body' => 'body for node 1',
      'teaser' => 'teaser for node 1',
      'log' => '',
      'timestamp' => 1279051598,
      'format' => 1,
    ),
    array(
      // Node fields.
      'nid' => 2,
      'vid' => 2,
      'type' => 'page',
      'language' => 'en',
      'title' => 'node title 2',
      'uid' => 1,
      'status' => 1,
      'created' => 1279290908,
      'changed' => 1279308993,
      'comment' => 0,
      'promote' => 1,
      'moderate' => 0,
      'sticky' => 0,
      'tnid' => 0,
      'translate' => 0,
      // Node revision fields.
      'body' => 'body for node 2',
      'teaser' => 'teaser for node 2',
      'log' => '',
      'timestamp' => 1279308993,
      'format' => 1,
    ),
    array(
      'nid' => 5,
      'vid' => 5,
      'type' => 'story',
      'language' => 'en',
      'title' => 'node title 5',
      'uid' => 1,
      'status' => 1,
      'created' => 1279290908,
      'changed' => 1279308993,
      'comment' => 0,
      'promote' => 1,
      'moderate' => 0,
      'sticky' => 0,
      'tnid' => 0,
      'translate' => 0,
      // Node revision fields.
      'body' => 'body for node 5',
      'teaser' => 'body for node 5',
      'log' => '',
      'timestamp' => 1279308993,
      'format' => 1,
      'field_test_four' => array(
        array(
          'value' => '3.14159',
          'delta' => 0,
        ),
      ),
    ),
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->databaseContents['content_node_field'] = array(
      array(
        'field_name' => 'field_test_four',
        'type' => 'number_float',
        'global_settings' => 'a:0:{}',
        'required' => '0',
        'multiple' => '0',
        'db_storage' => '1',
        'module' => 'number',
        'db_columns' => 'a:1:{s:5:"value";a:3:{s:4:"type";s:5:"float";s:8:"not null";b:0;s:8:"sortable";b:1;}}',
        'active' => '1',
        'locked' => '0',
      ),
    );
    $this->databaseContents['content_node_field_instance'] = array(
      array(
        'field_name' => 'field_test_four',
        'type_name' => 'story',
        'weight' => '3',
        'label' => 'Float Field',
        'widget_type' => 'number',
        'widget_settings' => 'a:0:{}',
        'display_settings' => 'a:0:{}',
        'description' => 'An example float field.',
        'widget_module' => 'number',
        'widget_active' => '1',
      ),
    );
    $this->databaseContents['content_type_story'] = array(
      array(
        'nid' => 5,
        'vid' => 5,
        'uid' => 5,
        'field_test_four_value' => '3.14159',
      ),
    );
    $this->databaseContents['system'] = array(
      array(
        'type' => 'module',
        'name' => 'content',
        'schema_version' => 6001,
        'status' => TRUE,
      ),
    );
    foreach ($this->expectedResults as $k => $row) {
      foreach (array('nid', 'vid', 'title', 'uid', 'body', 'teaser', 'format', 'timestamp', 'log') as $field) {
        $this->databaseContents['node_revisions'][$k][$field] = $row[$field];
        switch ($field) {
          case 'nid': case 'vid':
            break;
          case 'uid':
            $this->databaseContents['node_revisions'][$k]['uid']++;
            break;
          default:
            unset($row[$field]);
            break;
        }
      }
      $this->databaseContents['node'][$k] = $row;
    }
    array_walk($this->expectedResults, function (&$row) {
      $row['node_uid'] = $row['uid'];
      $row['revision_uid'] = $row['uid'] + 1;
      unset($row['uid']);
    });

    parent::setUp();
  }

}

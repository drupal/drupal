<?php

namespace Drupal\Tests\node\Unit\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D7 node source plugin.
 *
 * @group node
 */
class NodeTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\node\Plugin\migrate\source\d7\Node';

  protected $migrationConfiguration = array(
    'id' => 'test',
    'source' => array(
      'plugin' => 'd7_node',
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
      'sticky' => 0,
      'tnid' => 0,
      'translate' => 0,
      'log' => '',
      'timestamp' => 1279051598,
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
      'sticky' => 0,
      'tnid' => 0,
      'translate' => 0,
      'log' => '',
      'timestamp' => 1279308993,
    ),
    array(
      // Node fields.
      'nid' => 5,
      'vid' => 5,
      'type' => 'article',
      'language' => 'en',
      'title' => 'node title 5',
      'uid' => 1,
      'status' => 1,
      'created' => 1279290908,
      'changed' => 1279308993,
      'comment' => 0,
      'promote' => 1,
      'sticky' => 0,
      'tnid' => 0,
      'translate' => 0,
      'log' => '',
      'timestamp' => 1279308993,
    ),
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    foreach ($this->expectedResults as $k => $row) {
      foreach (array('nid', 'vid', 'title', 'uid', 'timestamp', 'log') as $field) {
        $this->databaseContents['node_revision'][$k][$field] = $row[$field];
        switch ($field) {
          case 'nid': case 'vid':
            break;
          case 'uid':
            $this->databaseContents['node_revision'][$k]['uid']++;
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

    $this->databaseContents['field_config_instance'] = array(
      array(
        'id' => '2',
        'field_id' => '2',
        'field_name' => 'body',
        'entity_type' => 'node',
        'bundle' => 'page',
        'data' => 'a:0:{}',
        'deleted' => '0',
      ),
      array(
        'id' => '2',
        'field_id' => '2',
        'field_name' => 'body',
        'entity_type' => 'node',
        'bundle' => 'article',
        'data' => 'a:0:{}',
        'deleted' => '0',
      ),
    );
    $this->databaseContents['field_revision_body'] = array(
      array(
        'entity_type' => 'node',
        'bundle' => 'page',
        'deleted' => '0',
        'entity_id' => '1',
        'revision_id' => '1',
        'language' => 'en',
        'delta' => '0',
        'body_value' => 'Foobaz',
        'body_summary' => '',
        'body_format' => 'filtered_html',
      ),
    );

    parent::setUp();
  }

}

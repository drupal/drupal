<?php

namespace Drupal\Tests\node\Unit\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Base for D6 node migration tests.
 */
abstract class NodeTestBase extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\node\Plugin\migrate\source\d6\Node';

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
    $this->databaseContents['node'] = [
      [
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
        'translate' => 0,
        'tnid' => 0,
      ],
      [
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
        'translate' => 0,
        'tnid' => 0,
      ],
      [
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
        'translate' => 0,
        'tnid' => 0,
      ],
      [
        'nid' => 6,
        'vid' => 6,
        'type' => 'story',
        'language' => 'en',
        'title' => 'node title 6',
        'uid' => 1,
        'status' => 1,
        'created' => 1279290909,
        'changed' => 1279308994,
        'comment' => 0,
        'promote' => 1,
        'moderate' => 0,
        'sticky' => 0,
        'translate' => 0,
        'tnid' => 6,
      ],
      [
        'nid' => 7,
        'vid' => 7,
        'type' => 'story',
        'language' => 'fr',
        'title' => 'node title 7',
        'uid' => 1,
        'status' => 1,
        'created' => 1279290910,
        'changed' => 1279308995,
        'comment' => 0,
        'promote' => 1,
        'moderate' => 0,
        'sticky' => 0,
        'translate' => 0,
        'tnid' => 6,
      ],
    ];

    foreach ($this->databaseContents['node'] as $k => $row) {
      // Find the equivalent row from expected results.
      $result_row = NULL;
      foreach ($this->expectedResults as $result) {
        if (in_array($result['nid'], [$row['nid'], $row['tnid']]) && $result['language'] == $row['language']) {
          $result_row = $result;
          break;
        }
      }

      // Populate node_revisions.
      foreach (array('nid', 'vid', 'title', 'uid', 'body', 'teaser', 'format', 'timestamp', 'log') as $field) {
        $value = isset($row[$field]) ? $row[$field] : $result_row[$field];
        $this->databaseContents['node_revisions'][$k][$field] = $value;
        if ($field == 'uid') {
          $this->databaseContents['node_revisions'][$k]['uid']++;
        }
      }
    }

    array_walk($this->expectedResults, function (&$row) {
      $row['node_uid'] = $row['uid'];
      $row['revision_uid'] = $row['uid'] + 1;
      unset($row['uid']);
    });

    parent::setUp();
  }

}

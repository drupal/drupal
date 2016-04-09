<?php

namespace Drupal\Tests\node\Unit\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D6 node source plugin with 'node_type' configuration.
 *
 * @group node
 */
class NodeByNodeTypeTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\node\Plugin\migrate\source\d6\Node';

  // The fake Migration configuration entity.
  protected $migrationConfiguration = array(
    'id' => 'test',
    // The fake configuration for the source.
    'source' => array(
      'plugin' => 'd6_node',
      'node_type' => 'page',
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
      'timestamp' => 1279051598,
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
      'format' => 1,
      'log' => 'log message 1',
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
      'timestamp' => 1279290908,
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
      'format' => 1,
      'log' => 'log message 2',
    ),
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $database_contents = $this->expectedResults;
    array_walk($this->expectedResults, function (&$row) {
      $row['node_uid'] = $row['uid'];
      $row['revision_uid'] = $row['uid'] + 1;
      unset($row['uid']);
    });

    $database_contents[] = array(
      // Node fields.
      'nid' => 5,
      'vid' => 5,
      'type' => 'article',
      'language' => 'en',
      'title' => 'node title 5',
      'uid' => 1,
      'status' => 1,
      'timestamp' => 1279290908,
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
      'format' => 1,
      'log' => 'log message 3',
    );

    // Add another row with an article node and make sure it is not migrated.

    foreach ($database_contents as $k => $row) {
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

    parent::setUp();
  }

}

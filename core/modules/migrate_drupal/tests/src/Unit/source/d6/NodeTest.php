<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate_drupal\Unit\source\d6\NodeTest.
 */

namespace Drupal\Tests\migrate_drupal\Unit\source\d6;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D6 node source plugin.
 *
 * @group migrate_drupal
 */
class NodeTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\migrate_drupal\Plugin\migrate\source\d6\Node';

  // The fake Migration configuration entity.
  protected $migrationConfiguration = array(
    'id' => 'test',
    // Leave it empty for now.
    'idlist' => array(),
    // The fake configuration for the source.
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
    ),
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
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

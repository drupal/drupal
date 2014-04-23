<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\source\d6\NodeTest.
 */

namespace Drupal\migrate_drupal\Tests\source\d6;

use Drupal\migrate\Tests\MigrateSqlSourceTestCase;

/**
 * Tests the Drupal 6 node source
 *
 * @group migrate_drupal
 * @group Drupal
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
      'bundle' => 'page',
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
      'format' => 1,
    ),
  );

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'D6 node source functionality',
      'description' => 'Tests D6 node source plugin.',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    foreach ($this->expectedResults as $k => $row) {
      foreach (array('nid', 'vid', 'title', 'uid', 'body', 'teaser', 'format') as $i => $field) {
        $this->databaseContents['node_revisions'][$k][$field] = $row[$field];
        // Keep nid and vid.
        if ($i > 1) {
          unset($row[$field]);
        }
      }
      $this->databaseContents['node'][$k] = $row;
    }

    parent::setUp();
  }

}

namespace Drupal\migrate_drupal\Tests\source\d6;

use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\migrate_drupal\Plugin\migrate\source\d6\Node;

class TestNode extends Node {
  protected $cckSchemaCorrect = true;
  public function setDatabase(Connection $database) {
    $this->database = $database;
  }
  public function setModuleHandler(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }
}

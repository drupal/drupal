<?php

namespace Drupal\Tests\node\Unit\Plugin\migrate\source\d6;

/**
 * Tests D6 node source plugin.
 *
 * @group node
 */
class NodeTest extends NodeTestBase {

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
      'tnid' => 1,
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
      'tnid' => 2,
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
      'tnid' => 5,
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
    array(
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
      'tnid' => 6,
      'translate' => 0,
      // Node revision fields.
      'body' => 'body for node 6',
      'teaser' => 'body for node 6',
      'log' => '',
      'timestamp' => 1279308994,
      'format' => 1,
    ),
  );

}

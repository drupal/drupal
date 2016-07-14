<?php

namespace Drupal\Tests\node\Unit\Plugin\migrate\source\d6;

/**
 * Tests D6 node translation source plugin.
 *
 * @group node
 */
class NodeTranslationTest extends NodeTestBase {

  protected $migrationConfiguration = array(
    'id' => 'test',
    'source' => array(
      'plugin' => 'd6_node',
      'translations' => TRUE,
    ),
  );

  protected $expectedResults = array(
    array(
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
      'tnid' => 6,
      'translate' => 0,
      // Node revision fields.
      'body' => 'body for node 7',
      'teaser' => 'body for node 7',
      'log' => '',
      'timestamp' => 1279308995,
      'format' => 1,
    ),
  );

}

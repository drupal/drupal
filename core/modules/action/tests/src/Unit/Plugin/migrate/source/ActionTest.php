<?php

/**
 * @file
 * Contains \Drupal\Tests\action\Unit\Plugin\migrate\source\ActionTest.
 */

namespace Drupal\Tests\action\Unit\Plugin\migrate\source;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests actions source plugin.
 *
 * @group action
 */
class ActionTest extends MigrateSqlSourceTestCase {

  // The plugin system is not working during unit testing so the source plugin
  // class needs to be manually specified.
  const PLUGIN_CLASS = 'Drupal\action\Plugin\migrate\source\Action';

  // The fake Migration configuration entity.
  protected $migrationConfiguration = array(
    // The ID of the entity, can be any string.
    'id' => 'test',
    'source' => array(
      'plugin' => 'action',
    ),
  );

  // We need to set up the database contents; it's easier to do that below.

  protected $expectedResults = array(
    array(
      'aid' => 'Redirect to node list page',
      'type' => 'system',
      'callback' => 'system_goto_action',
      'parameters' => 'a:1:{s:3:"url";s:4:"node";}',
      'description' => 'Redirect to node list page',
    ),
    array(
      'aid' => 'Test notice email',
      'type' => 'system',
      'callback' => 'system_send_email_action',
      'parameters' => 'a:3:{s:9:"recipient";s:7:"%author";s:7:"subject";s:4:"Test";s:7:"message";s:4:"Test',
      'description' => 'Test notice email',
    ),
    array(
      'aid' => 'comment_publish_action',
      'type' => 'comment',
      'callback' => 'comment_publish_action',
      'parameters' => NULL,
      'description' => NULL,
    ),
    array(
      'aid' => 'node_publish_action',
      'type' => 'comment',
      'callback' => 'node_publish_action',
      'parameters' => NULL,
      'description' => NULL,
    ),
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->databaseContents['actions'] = $this->expectedResults;
    parent::setUp();
  }

}

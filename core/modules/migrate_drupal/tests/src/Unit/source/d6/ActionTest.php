<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate_drupal\Unit\source\d6\ActionTest.
 */

namespace Drupal\Tests\migrate_drupal\Unit\source\d6;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D6 actions source plugin.
 *
 * @group migrate_drupal
 */
class ActionTest extends MigrateSqlSourceTestCase {

  // The plugin system is not working during unit testing so the source plugin
  // class needs to be manually specified.
  const PLUGIN_CLASS = 'Drupal\migrate_drupal\Plugin\migrate\source\d6\Action';

  // The fake Migration configuration entity.
  protected $migrationConfiguration = array(
    // The ID of the entity, can be any string.
    'id' => 'test',
    // Leave it empty for now.
    'idlist' => array(),
    'source' => array(
      'plugin' => 'd6_action',
    ),
  );

  // We need to set up the database contents; it's easier to do that below.

  protected $expectedResults = array(
    array(
      'aid' => '1',
      'type' => 'system',
      'callback' => 'system_goto_action',
      'parameters' => 'a:1:{s:3:"url";s:4:"node";}',
      'description' => 'Redirect to node list page',
    ),
    array(
      'aid' => '2',
      'type' => 'system',
      'callback' => 'system_send_email_action',
      'parameters' => 'a:3:{s:9:"recipient";s:7:"%author";s:7:"subject";s:4:"Test";s:7:"message";s:4:"Test',
      'description' => 'Test notice email',
    ),
    array(
      'aid' => 'comment_publish_action',
      'type' => 'comment',
      'callback' => 'comment_publish_action',
      'parameters' => null,
      'description' => null,
    ),
    array(
      'aid' => 'node_publish_action',
      'type' => 'comment',
      'callback' => 'node_publish_action',
      'parameters' => null,
      'description' => null,
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

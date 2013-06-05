<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Upgrade\StateSystemUpgradePathTest.
 */

namespace Drupal\system\Tests\Upgrade;

/**
 * Tests upgrade of system variables.
 */
class StateSystemUpgradePathTest extends UpgradePathTestBase {
  public static function getInfo() {
    return array(
      'name' => 'State system upgrade test',
      'description' => 'Tests upgrade of system variables to the state system.',
      'group' => 'Upgrade path',
    );
  }

  public function setUp() {
    $this->databaseDumpFiles = array(
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.bare.standard_all.database.php.gz',
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.state.system.database.php',
    );
    parent::setUp();
  }

  /**
   * Tests upgrade of system variables to state system.
   */
  public function testSystemVariableUpgrade() {
    $this->assertTrue($this->performUpgrade(), 'The upgrade was completed successfully.');

    $expected_state = array();

    $expected_state['node.node_access_needs_rebuild'] = array(
      'value' => TRUE,
      'variable_name' => 'node_access_needs_rebuild',
    );
    $expected_state['node.cron_last'] = array(
      'value' => 1304208001,
      'variable_name' => 'node_cron_last',
    );
    $expected_state['statistics.day_timestamp'] = array(
      'value' => 1352070595,
      'variable_name' => 'statistics_day_timestamp',
    );
    $expected_state['statistics.node_counter_scale'] = array(
      'value' => 1.0 / 2000,
      'variable_name' => 'node_cron_views_scale',
    );
    $expected_state['system.cron_last'] = array(
      'value' => 1304208002,
      'variable_name' => 'cron_last',
    );
    $expected_state['system.cron_key'] = array(
      'value' => 'kdm95qppDDlyZrcUOx453YwQqDA4DNmxi4VQcxzFU9M',
      'variable_name' => 'cron_key',
    );
    $expected_state['system.private_key'] = array(
      'value' => 'G38mKqASKus8VGMkMzVuXImYbzspCQ1iRT2iEZpMYmQ',
      'variable_name' => 'drupal_private_key',
    );
    $expected_state['tracker.index_nid'] = array(
      'value' => 0,
      'variable_name' => 'tracker_index_nid',
    );
    $expected_state['update.last_check'] = array(
      'value' => 1304208000,
      'variable_name' => 'update_last_check',
    );
    $expected_state['update.last_email_notification'] = array(
      'value' => 1304208000,
      'variable_name' => 'update_last_email_notification',
    );
    $expected_state['comment.node_comment_statistics_scale'] = array(
      'value' => 1.0 / 1000,
      'variable_name' => 'node_cron_comments_scale',
    );

    foreach ($expected_state as $name => $data) {
      $this->assertIdentical(\Drupal::state()->get($name), $data['value']);
      $deleted = !db_query('SELECT value FROM {variable} WHERE name = :name', array(':name' => $data['variable_name']))->fetchField();
      $this->assertTrue($deleted, format_string('Variable !name has been deleted.', array('!name' => $data['variable_name'])));
    }
  }
}

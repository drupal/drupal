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

    $expected_state['system.install_time'] = array(
      'value' => 1304208000,
      'variable_name' => 'install_time',
    );
    $expected_state['system.install_task'] = array(
      'value' => 'done',
      'variable_name' => 'install_task',
    );
    $expected_state['system.path_alias_whitelist'] = array(
      'value' => array(

      ),
      'variable_name' => 'path_alias_whitelist',
    );

    foreach ($expected_state as $name => $data) {
      $this->assertIdentical(state()->get($name), $data['value']);
      $deleted = !db_query('SELECT value FROM {variable} WHERE name = :name', array(':name' => $data['variable_name']))->fetchField();
      $this->assertTrue($deleted, format_string('Variable !name has been deleted.', array('!name' => $data['variable_name'])));
    }
  }
}

<?php

/**
 * @file
 * Definition of Drupal\config\Tests\ConfigUpgradeTest.
 */

namespace Drupal\config\Tests;

use Drupal\Core\Config\ConfigException;
use Drupal\simpletest\WebTestBase;

/**
 * Tests migration of variables into configuration objects.
 */
class ConfigUpgradeTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('config_upgrade');

  protected $testContent = 'Olá, Sao Paulo!';

  public static function getInfo() {
    return array(
      'name' => 'Variable migration',
      'description' => 'Tests migration of variables into configuration objects.',
      'group' => 'Configuration',
    );
  }

  function setUp() {
    parent::setUp();
    require_once DRUPAL_ROOT . '/core/includes/update.inc';
  }

  /**
   * Tests update_variables_to_config().
   */
  function testConfigurationUpdate() {
    // Ensure that the variable table has the object. The variable table will
    // remain in place for Drupal 8 to provide an upgrade path for overridden
    // variables.
    db_insert('variable')
      ->fields(array('name', 'value'))
      ->values(array('config_upgrade_foo', serialize($this->testContent)))
      ->values(array('config_upgrade_bar', serialize($this->testContent)))
      ->execute();

    // Perform migration.
    update_variables_to_config('config_upgrade.test', array(
      'config_upgrade_bar' => 'parent.bar',
      'config_upgrade_foo' => 'foo',
      // A default configuration value for which no variable exists.
      'config_upgrade_baz' => 'parent.baz',
    ));

    // Verify that variables have been converted and default values exist.
    $config = config('config_upgrade.test');
    $this->assertIdentical($config->get('foo'), $this->testContent);
    $this->assertIdentical($config->get('parent.bar'), $this->testContent);
    $this->assertIdentical($config->get('parent.baz'), 'Baz');

    // Verify that variables have been deleted.
    $variables = db_query('SELECT name FROM {variable} WHERE name IN (:names)', array(':names' => array('config_upgrade_bar', 'config_upgrade_foo')))->fetchCol();
    $this->assertFalse($variables);

    // Add another variable to migrate into the same config object.
    db_insert('variable')
      ->fields(array('name', 'value'))
      ->values(array('config_upgrade_additional', serialize($this->testContent)))
      ->execute();

    // Perform migration into the exsting config object.
    update_variables_to_config('config_upgrade.test', array(
      'config_upgrade_additional' => 'parent.additional',
    ));

    // Verify that new variables have been converted and existing still exist.
    $config = config('config_upgrade.test');
    $this->assertIdentical($config->get('foo'), $this->testContent);
    $this->assertIdentical($config->get('parent.bar'), $this->testContent);
    $this->assertIdentical($config->get('parent.baz'), 'Baz');
    $this->assertIdentical($config->get('parent.additional'), $this->testContent);

    // Verify that variables have been deleted.
    $variables = db_query('SELECT name FROM {variable} WHERE name IN (:names)', array(':names' => array('config_upgrade_additional')))->fetchCol();
    $this->assertFalse($variables);

    // Verify that a default module configuration file is required to exist.
    try {
      update_variables_to_config('config_upgrade.missing.default.config', array());
      $this->fail('Exception was not thrown on missing default module configuration file.');
    }
    catch (ConfigException $e) {
      $this->pass('Exception was thrown on missing default module configuration file.');
    }

    // For this test it is essential that update_variables_to_config has already
    // run on the config object.
    config('config_upgrade.test')
      ->set('numeric_keys.403', '')
      ->set('numeric_keys.404', '')
      ->save();

    db_insert('variable')
      ->fields(array('name', 'value'))
      ->values(array('config_upgrade_403', serialize('custom403')))
      ->values(array('config_upgrade_404', serialize('custom404')))
      ->execute();

    // Perform migration.
    update_variables_to_config('config_upgrade.test', array(
      'config_upgrade_403' => 'numeric_keys.403',
      'config_upgrade_404' => 'numeric_keys.404',
    ));

    $this->assertIdentical(config('config_upgrade.test')->get('numeric_keys'), array(403 => 'custom403', 404 => 'custom404'));
  }
}

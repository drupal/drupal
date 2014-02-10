<?php

/**
 * @file
 * Contains \Drupal\config\Tests\ConfigInstallTest.
 */

namespace Drupal\config\Tests;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests installation of configuration objects in installation functionality.
 */
class ConfigInstallTest extends DrupalUnitTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Installation functionality unit tests',
      'description' => 'Tests installation of configuration objects in installation functionality.',
      'group' => 'Configuration',
    );
  }

  function setUp() {
    parent::setUp();

    // Ensure the global variable being asserted by this test does not exist;
    // a previous test executed in this request/process might have set it.
    unset($GLOBALS['hook_config_test']);
  }

  /**
   * Tests module installation.
   */
  function testModuleInstallation() {
    $default_config = 'config_test.system';
    $default_configuration_entity = 'config_test.dynamic.dotted.default';

    // Verify that default module config does not exist before installation yet.
    $config = \Drupal::config($default_config);
    $this->assertIdentical($config->isNew(), TRUE);
    $config = \Drupal::config($default_configuration_entity);
    $this->assertIdentical($config->isNew(), TRUE);

    // Ensure that schema provided by modules that are not installed is not
    // available.
    $this->assertFalse(\Drupal::service('config.typed')->hasConfigSchema('config_test.schema_in_install'), 'Configuration schema for config_test.schema_in_install does not exist.');

    // Install the test module.
    $this->enableModules(array('config_test'));
    $this->installConfig(array('config_test'));

    // After module installation the new schema should exist.
    $this->assertTrue(\Drupal::service('config.typed')->hasConfigSchema('config_test.schema_in_install'), 'Configuration schema for config_test.schema_in_install exists.');

    // Verify that default module config exists.
    \Drupal::configFactory()->reset($default_config);
    \Drupal::configFactory()->reset($default_configuration_entity);
    $config = \Drupal::config($default_config);
    $this->assertIdentical($config->isNew(), FALSE);
    $config = \Drupal::config($default_configuration_entity);
    $this->assertIdentical($config->isNew(), FALSE);

    // Verify that config_test API hooks were invoked for the dynamic default
    // configuration entity.
    $this->assertFalse(isset($GLOBALS['hook_config_test']['load']));
    $this->assertTrue(isset($GLOBALS['hook_config_test']['presave']));
    $this->assertTrue(isset($GLOBALS['hook_config_test']['insert']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['update']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['predelete']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['delete']));

    // Ensure that data type casting is applied during config installation.
    $config = \Drupal::config('config_test.schema_in_install');
    $this->assertIdentical($config->get('integer'), 1);

    // Test that uninstalling configuration removes configuration schema.
    \Drupal::config('system.module')->set('enabled', array())->save();
    \Drupal::service('config.manager')->uninstall('module', 'config_test');
    $this->assertFalse(\Drupal::service('config.typed')->hasConfigSchema('config_test.schema_in_install'), 'Configuration schema for config_test.schema_in_install does not exist.');

  }
}

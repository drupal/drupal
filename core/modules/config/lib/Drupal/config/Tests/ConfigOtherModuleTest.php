<?php

/**
 * @file
 * Contains Drupal\config\Tests\ConfigOtherModuleTest.
 */

namespace Drupal\config\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests default configuration provided by a module that does not own it.
 */
class ConfigOtherModuleTest extends WebTestBase {

  /**
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  public static function getInfo() {
    return array(
      'name' => 'Default configuration owner',
      'description' => 'Tests default configuration provided by a module that does not own it.',
      'group' => 'Configuration',
    );
  }

  /**
   * Sets up the module handler for enabling and disabling modules.
   */
  public function setUp() {
    parent::setUp();
    $this->moduleHandler = $this->container->get('module_handler');
  }

  /**
   * Tests enabling the provider of the default configuration first.
   */
  public function testInstallOtherModuleFirst() {
    $this->moduleHandler->install(array('config_other_module_config'));

    // Check that the config entity doesn't exist before the config_test module
    // is enabled. We cannot use the entity system because the config_test
    // entity type does not exist.
    $config = $this->container->get('config.factory')->get('config_test.dynamic.other_module');
    $this->assertTrue($config->isNew(), 'Default configuration for other modules is not installed if that module is not enabled.');

    // Install the module that provides the entity type. This installs the
    // default configuration.
    $this->moduleHandler->install(array('config_test'));
    $this->assertTrue(entity_load('config_test', 'other_module', TRUE), 'Default configuration has been installed.');

    // Uninstall the module that provides the entity type. This will remove the
    // default configuration.
    $this->moduleHandler->uninstall(array('config_test'));
    $config = $this->container->get('config.factory')->get('config_test.dynamic.other_module');
    $this->assertTrue($config->isNew(), 'Default configuration for other modules is removed when the config entity provider is disabled.');

    // Install the module that provides the entity type again. This installs the
    // default configuration.
    $this->moduleHandler->install(array('config_test'));
    $other_module_config_entity = entity_load('config_test', 'other_module', TRUE);
    $this->assertTrue($other_module_config_entity, "Default configuration has been recreated.");

    // Update the default configuration to test that the changes are preserved
    // if the module that provides the default configuration is uninstalled.
    $other_module_config_entity->set('style', "The piano ain't got no wrong notes.");
    $other_module_config_entity->save();

    // Uninstall the module that provides the default configuration.
    $this->moduleHandler->uninstall(array('config_other_module_config'));
    $this->assertTrue(entity_load('config_test', 'other_module', TRUE), 'Default configuration for other modules is not removed when the module that provides it is uninstalled.');

    // Default configuration provided by config_test should still exist.
    $this->assertTrue(entity_load('config_test', 'dotted.default', TRUE), 'The configuration is not deleted.');

    // Re-enable module to test that default config is unchanged.
    $this->moduleHandler->install(array('config_other_module_config'));
    $config_entity = entity_load('config_test', 'other_module', TRUE);
    $this->assertEqual($config_entity->get('style'), "The piano ain't got no wrong notes.", 'Re-enabling the module does not install default config over the existing config entity.');
  }

  /**
   * Tests enabling the provider of the config entity type first.
   */
  public function testInstallConfigEnityModuleFirst() {
    $this->moduleHandler->install(array('config_test'));
    $this->assertFalse(entity_load('config_test', 'other_module', TRUE), 'Default configuration provided by config_other_module_config does not exist.');

    $this->moduleHandler->install(array('config_other_module_config'));
    $this->assertTrue(entity_load('config_test', 'other_module', TRUE), 'Default configuration provided by config_other_module_config has been installed.');
  }

  /**
   * Tests uninstalling Node module removes views which are dependent on it.
   */
  public function testUninstall() {
    $this->moduleHandler->install(array('views'));
    $this->assertTrue(entity_load('view', 'frontpage', TRUE) === NULL, 'After installing Views, frontpage view which is dependant on the Node and Views modules does not exist.');
    $this->moduleHandler->install(array('node'));
    $this->assertTrue(entity_load('view', 'frontpage', TRUE) !== NULL, 'After installing Node, frontpage view which is dependant on the Node and Views modules exists.');
    $this->moduleHandler->uninstall(array('node'));
    $this->assertTrue(entity_load('view', 'frontpage', TRUE) === NULL, 'After uninstalling Node, frontpage view which is dependant on the Node and Views modules does not exist.');
  }

}

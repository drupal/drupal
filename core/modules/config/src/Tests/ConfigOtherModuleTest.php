<?php

/**
 * @file
 * Contains Drupal\config\Tests\ConfigOtherModuleTest.
 */

namespace Drupal\config\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests default configuration provided by a module that does not own it.
 *
 * @group config
 */
class ConfigOtherModuleTest extends WebTestBase {

  /**
   * Tests enabling the provider of the default configuration first.
   */
  public function testInstallOtherModuleFirst() {
    $this->installModule('config_other_module_config_test');

    // Check that the config entity doesn't exist before the config_test module
    // is enabled. We cannot use the entity system because the config_test
    // entity type does not exist.
    $config = $this->config('config_test.dynamic.other_module_test');
    $this->assertTrue($config->isNew(), 'Default configuration for other modules is not installed if that module is not enabled.');

    // Install the module that provides the entity type. This installs the
    // default configuration.
    $this->installModule('config_test');
    $this->assertTrue(entity_load('config_test', 'other_module_test', TRUE), 'Default configuration has been installed.');

    // Uninstall the module that provides the entity type. This will remove the
    // default configuration.
    $this->uninstallModule('config_test');
    $config = $this->config('config_test.dynamic.other_module_test');
    $this->assertTrue($config->isNew(), 'Default configuration for other modules is removed when the config entity provider is disabled.');

    // Install the module that provides the entity type again. This installs the
    // default configuration.
    $this->installModule('config_test');
    $other_module_config_entity = entity_load('config_test', 'other_module_test', TRUE);
    $this->assertTrue($other_module_config_entity, "Default configuration has been recreated.");

    // Update the default configuration to test that the changes are preserved
    // if the module that provides the default configuration is uninstalled.
    $other_module_config_entity->set('style', "The piano ain't got no wrong notes.");
    $other_module_config_entity->save();

    // Uninstall the module that provides the default configuration.
    $this->uninstallModule('config_other_module_config_test');
    $this->assertTrue(entity_load('config_test', 'other_module_test', TRUE), 'Default configuration for other modules is not removed when the module that provides it is uninstalled.');

    // Default configuration provided by config_test should still exist.
    $this->assertTrue(entity_load('config_test', 'dotted.default', TRUE), 'The configuration is not deleted.');

    // Re-enable module to test that pre-existing optional configuration does
    // not throw an error.
    $this->installModule('config_other_module_config_test');
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('config_other_module_config_test'), 'The config_other_module_config_test module is installed.');

    // Ensure that optional configuration with unmet dependencies is only
    // installed once all the dependencies are met.
    $this->assertNull(entity_load('config_test', 'other_module_test_unmet', TRUE), 'The optional configuration whose dependencies are met is not created.');
    $this->installModule('config_install_dependency_test');
    $this->assertTrue(entity_load('config_test', 'other_module_test_unmet', TRUE), 'The optional configuration whose dependencies are met is now created.');
  }

  /**
   * Tests enabling the provider of the config entity type first.
   */
  public function testInstallConfigEntityModuleFirst() {
    $this->installModule('config_test');
    $this->assertFalse(entity_load('config_test', 'other_module_test', TRUE), 'Default configuration provided by config_other_module_config_test does not exist.');

    $this->installModule('config_other_module_config_test');
    $this->assertTrue(entity_load('config_test', 'other_module_test', TRUE), 'Default configuration provided by config_other_module_config_test has been installed.');
  }

  /**
   * Tests uninstalling Node module removes views which are dependent on it.
   */
  public function testUninstall() {
    $this->installModule('views');
    $this->assertTrue(entity_load('view', 'frontpage', TRUE) === NULL, 'After installing Views, frontpage view which is dependant on the Node and Views modules does not exist.');
    $this->installModule('node');
    $this->assertTrue(entity_load('view', 'frontpage', TRUE) !== NULL, 'After installing Node, frontpage view which is dependant on the Node and Views modules exists.');
    $this->uninstallModule('node');
    $this->assertTrue(entity_load('view', 'frontpage', TRUE) === NULL, 'After uninstalling Node, frontpage view which is dependant on the Node and Views modules does not exist.');
  }

  /**
   * Installs a module.
   *
   * @param string $module
   *   The module name.
   */
  protected function installModule($module) {
    $this->container->get('module_installer')->install(array($module));
    $this->container = \Drupal::getContainer();
  }

  /**
   * Uninstalls a module.
   *
   * @param string $module
   *   The module name.
   */
  protected function uninstallModule($module) {
    $this->container->get('module_installer')->uninstall(array($module));
    $this->container = \Drupal::getContainer();
  }

}

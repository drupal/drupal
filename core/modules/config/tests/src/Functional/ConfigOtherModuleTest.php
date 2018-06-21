<?php

namespace Drupal\Tests\config\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests default configuration provided by a module that does not own it.
 *
 * @group config
 */
class ConfigOtherModuleTest extends BrowserTestBase {

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
    $this->assertNull(entity_load('config_test', 'other_module_test_unmet', TRUE), 'The optional configuration config_test.dynamic.other_module_test_unmet whose dependencies are not met is not created.');
    $this->assertNull(entity_load('config_test', 'other_module_test_optional_entity_unmet', TRUE), 'The optional configuration config_test.dynamic.other_module_test_optional_entity_unmet whose dependencies are not met is not created.');
    $this->installModule('config_test_language');
    $this->assertNull(entity_load('config_test', 'other_module_test_optional_entity_unmet2', TRUE), 'The optional configuration config_test.dynamic.other_module_test_optional_entity_unmet2 whose dependencies are not met is not created.');
    $this->installModule('config_install_dependency_test');
    $this->assertTrue(entity_load('config_test', 'other_module_test_unmet', TRUE), 'The optional configuration config_test.dynamic.other_module_test_unmet whose dependencies are met is now created.');
    // The following configuration entity's dependencies are now met. It is
    // indirectly dependent on the config_install_dependency_test module because
    // it has a dependency on the config_test.dynamic.dependency_for_unmet2
    // configuration provided by that module and, therefore, should be created.
    $this->assertTrue(entity_load('config_test', 'other_module_test_optional_entity_unmet2', TRUE), 'The optional configuration config_test.dynamic.other_module_test_optional_entity_unmet2 whose dependencies are met is now created.');

    // The following configuration entity's dependencies are now met even though
    // it has no direct dependency on the module. It is indirectly dependent on
    // the config_install_dependency_test module because it has a dependency on
    // the config_test.dynamic.other_module_test_unmet configuration that is
    // dependent on the config_install_dependency_test module and, therefore,
    // should be created.
    $entity = entity_load('config_test', 'other_module_test_optional_entity_unmet', TRUE);
    $this->assertTrue($entity, 'The optional configuration config_test.dynamic.other_module_test_optional_entity_unmet whose dependencies are met is created.');
    $entity->delete();

    // Install another module to ensure the configuration just deleted is not
    // recreated.
    $this->installModule('config');
    $this->assertFalse(entity_load('config_test', 'other_module_test_optional_entity_unmet', TRUE), 'The optional configuration config_test.dynamic.other_module_test_optional_entity_unmet whose dependencies are met is not installed when an unrelated module is installed.');
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
    $storage = $this->container->get('entity_type.manager')->getStorage('view');
    $storage->resetCache(['frontpage']);
    $this->assertTrue($storage->load('frontpage') === NULL, 'After installing Views, frontpage view which is dependant on the Node and Views modules does not exist.');
    $this->installModule('node');
    $storage->resetCache(['frontpage']);
    $this->assertTrue($storage->load('frontpage') !== NULL, 'After installing Node, frontpage view which is dependant on the Node and Views modules exists.');
    $this->uninstallModule('node');
    $storage = $this->container->get('entity_type.manager')->getStorage('view');
    $storage->resetCache(['frontpage']);
    $this->assertTrue($storage->load('frontpage') === NULL, 'After uninstalling Node, frontpage view which is dependant on the Node and Views modules does not exist.');
  }

  /**
   * Installs a module.
   *
   * @param string $module
   *   The module name.
   */
  protected function installModule($module) {
    $this->container->get('module_installer')->install([$module]);
    $this->container = \Drupal::getContainer();
  }

  /**
   * Uninstalls a module.
   *
   * @param string $module
   *   The module name.
   */
  protected function uninstallModule($module) {
    $this->container->get('module_installer')->uninstall([$module]);
    $this->container = \Drupal::getContainer();
  }

}

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
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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
    $this->assertNotEmpty($this->getStorage()->load('other_module_test'), 'Default configuration has been installed.');

    // Uninstall the module that provides the entity type. This will remove the
    // default configuration.
    $this->uninstallModule('config_test');
    $config = $this->config('config_test.dynamic.other_module_test');
    $this->assertTrue($config->isNew(), 'Default configuration for other modules is removed when the config entity provider is disabled.');

    // Install the module that provides the entity type again. This installs the
    // default configuration.
    $this->installModule('config_test');
    $other_module_config_entity = $this->getStorage()->load('other_module_test');
    $this->assertNotEmpty($other_module_config_entity, "Default configuration has been recreated.");

    // Update the default configuration to test that the changes are preserved
    // if the module that provides the default configuration is uninstalled.
    $other_module_config_entity->set('style', "The piano ain't got no wrong notes.");
    $other_module_config_entity->save();

    // Uninstall the module that provides the default configuration.
    $this->uninstallModule('config_other_module_config_test');
    $this->assertNotEmpty($this->getStorage()->load('other_module_test'), 'Default configuration for other modules is not removed when the module that provides it is uninstalled.');

    // Default configuration provided by config_test should still exist.
    $this->assertNotEmpty($this->getStorage()->load('dotted.default'), 'The configuration is not deleted.');

    // Re-enable module to test that pre-existing optional configuration does
    // not throw an error.
    $this->installModule('config_other_module_config_test');
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('config_other_module_config_test'), 'The config_other_module_config_test module is installed.');

    // Ensure that optional configuration with unmet dependencies is only
    // installed once all the dependencies are met.
    $this->assertNull($this->getStorage()->load('other_module_test_unmet'), 'The optional configuration config_test.dynamic.other_module_test_unmet whose dependencies are not met is not created.');
    $this->assertNull($this->getStorage()->load('other_module_test_optional_entity_unmet'), 'The optional configuration config_test.dynamic.other_module_test_optional_entity_unmet whose dependencies are not met is not created.');
    $this->installModule('config_test_language');
    $this->assertNull($this->getStorage()->load('other_module_test_optional_entity_unmet'), 'The optional configuration config_test.dynamic.other_module_test_optional_entity_unmet whose dependencies are met is not created.');
    $this->installModule('config_install_dependency_test');
    $this->assertNotEmpty($this->getStorage()->load('other_module_test_unmet'), 'The optional configuration config_test.dynamic.other_module_test_unmet whose dependencies are met is now created.');
    // The following configuration entity's dependencies are now met. It is
    // indirectly dependent on the config_install_dependency_test module because
    // it has a dependency on the config_test.dynamic.dependency_for_unmet2
    // configuration provided by that module and, therefore, should be created.
    $this->assertNotEmpty($this->getStorage()->load('other_module_test_optional_entity_unmet2'), 'The optional configuration config_test.dynamic.other_module_test_optional_entity_unmet2 whose dependencies are met is now created.');

    // The following configuration entity's dependencies are now met even though
    // it has no direct dependency on the module. It is indirectly dependent on
    // the config_install_dependency_test module because it has a dependency on
    // the config_test.dynamic.other_module_test_unmet configuration that is
    // dependent on the config_install_dependency_test module and, therefore,
    // should be created.
    $entity = $this->getStorage()->load('other_module_test_optional_entity_unmet');
    $this->assertNotEmpty($entity, 'The optional configuration config_test.dynamic.other_module_test_optional_entity_unmet whose dependencies are met is created.');
    $entity->delete();

    // Install another module to ensure the configuration just deleted is not
    // recreated.
    $this->installModule('config');
    $this->assertNull($this->getStorage()->load('other_module_test_optional_entity_unmet'), 'The optional configuration config_test.dynamic.other_module_test_optional_entity_unmet whose dependencies are met is not installed when an unrelated module is installed.');

    // Ensure that enforced dependencies do not overwrite base ones.
    $this->installModule('config_install_dependency_enforced_combo_test');
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('config_install_dependency_enforced_combo_test'), 'The config_install_dependency_enforced_combo_test module is installed.');
    $this->assertNull($this->getStorage()->load('config_test.dynamic.enforced_and_base_dependencies'), 'The optional configuration config_test.dynamic.enforced_and_base_dependencies whose enforced dependencies are met but base module dependencies are not met is not created.');
  }

  /**
   * Tests enabling the provider of the config entity type first.
   */
  public function testInstallConfigEntityModuleFirst() {
    $this->installModule('config_test');
    $this->assertNull($this->getStorage()->load('other_module_test'), 'Default configuration provided by config_other_module_config_test does not exist.');

    $this->installModule('config_other_module_config_test');
    $this->assertNotEmpty($this->getStorage()->load('other_module_test'), 'Default configuration provided by config_other_module_config_test has been installed.');
  }

  /**
   * Tests uninstalling Node module removes views which are dependent on it.
   */
  public function testUninstall() {
    $this->installModule('views');
    $this->assertNull($this->getStorage('view')->load('frontpage'), 'After installing Views, frontpage view which is dependant on the Node and Views modules does not exist.');
    $this->installModule('node');
    $this->assertNotNull($this->getStorage('view')->load('frontpage'), 'After installing Node, frontpage view which is dependant on the Node and Views modules exists.');
    $this->uninstallModule('node');
    $this->assertNull($this->getStorage('view')->load('frontpage'), 'After uninstalling Node, frontpage view which is dependant on the Node and Views modules does not exist.');
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

  /**
   * Gets the provided entity type's storage.
   *
   * @param string $entity_type_id
   *   (optional) The entity type ID to get a storage for. Defaults to
   *   'config_test'.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The entity type's storage.
   */
  protected function getStorage($entity_type_id = 'config_test') {
    return \Drupal::entityTypeManager()->getStorage($entity_type_id);
  }

}

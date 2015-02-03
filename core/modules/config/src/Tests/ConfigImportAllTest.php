<?php

/**
 * @file
 * Contains \Drupal\config\Tests\ConfigImportAllTest.
 */

namespace Drupal\config\Tests;

use Drupal\Core\Config\StorageComparer;
use Drupal\system\Tests\Module\ModuleTestBase;
use Drupal\shortcut\Entity\Shortcut;

/**
 * Tests the largest configuration import possible with all available modules.
 *
 * @group config
 */
class ConfigImportAllTest extends ModuleTestBase {

  use SchemaCheckTestTrait;

  /**
   * A user with the 'synchronize configuration' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * The profile to install as a basis for testing.
   *
   * Using the standard profile as this has a lot of additional configuration.
   *
   * @var string
   */
  protected $profile = 'standard';

  protected function setUp() {
    parent::setUp();

    $this->webUser = $this->drupalCreateUser(array('synchronize configuration'));
    $this->drupalLogin($this->webUser);
  }

  /**
   * Tests that a fixed set of modules can be installed and uninstalled.
   */
  public function testInstallUninstall() {

    // Get a list of modules to enable.
    $all_modules = system_rebuild_module_data();
    $all_modules = array_filter($all_modules, function ($module) {
      // Filter contrib, hidden, already enabled modules and modules in the
      // Testing package.
      if ($module->origin !== 'core' || !empty($module->info['hidden']) || $module->status == TRUE || $module->info['package'] == 'Testing') {
        return FALSE;
      }
      return TRUE;
    });

    // Install every module possible.
    \Drupal::service('module_installer')->install(array_keys($all_modules));

    $this->assertModules(array_keys($all_modules), TRUE);
    foreach($all_modules as $module => $info) {
      $this->assertModuleConfig($module);
      $this->assertModuleTablesExist($module);
    }

    // Export active config to staging
    $this->copyConfig($this->container->get('config.storage'), $this->container->get('config.storage.staging'));

    system_list_reset();
    $this->resetAll();

    // Delete every field on the site so all modules can be uninstalled. For
    // example, if a comment field exists then module becomes required and can
    // not be uninstalled.

    $field_storages = \Drupal::entityManager()->getStorage('field_storage_config')->loadMultiple();
    \Drupal::entityManager()->getStorage('field_storage_config')->delete($field_storages);
    // Purge the data.
    field_purge_batch(1000);

    // Delete any forum terms so it can be uninstalled.
    $vid = $this->config('forum.settings')->get('vocabulary');
    $terms = entity_load_multiple_by_properties('taxonomy_term', ['vid' => $vid]);
    foreach ($terms as $term) {
      $term->delete();
    }

    // Delete any shortcuts so the shortcut module can be uninstalled.
    $shortcuts = Shortcut::loadMultiple();
    entity_delete_multiple('shortcut', array_keys($shortcuts));

    system_list_reset();
    $all_modules = system_rebuild_module_data();

    $modules_to_uninstall = array_filter($all_modules, function ($module) {
      // Filter required and not enabled modules.
      if (!empty($module->info['required']) || $module->status == FALSE) {
        return FALSE;
      }
      return TRUE;
    });

    // Can not uninstall config and use admin/config/development/configuration!
    unset($modules_to_uninstall['config']);

    $this->assertTrue(isset($modules_to_uninstall['comment']), 'The comment module will be disabled');

    // Uninstall all modules that can be uninstalled.
    \Drupal::service('module_installer')->uninstall(array_keys($modules_to_uninstall));

    $this->assertModules(array_keys($modules_to_uninstall), FALSE);
    foreach($modules_to_uninstall as $module => $info) {
      $this->assertNoModuleConfig($module);
      $this->assertModuleTablesDoNotExist($module);
    }

    // Import the configuration thereby re-installing all the modules.
    $this->drupalPostForm('admin/config/development/configuration', array(), t('Import all'));
    // Modules have been installed that have services.
    $this->rebuildContainer();

    // Check that there are no errors.
    $this->assertIdentical($this->configImporter()->getErrors(), array());

    // Check that all modules that were uninstalled are now reinstalled.
    $this->assertModules(array_keys($modules_to_uninstall), TRUE);
    foreach($modules_to_uninstall as $module => $info) {
      $this->assertModuleConfig($module);
      $this->assertModuleTablesExist($module);
    }

    // Ensure that we have no configuration changes to import.
    $storage_comparer = new StorageComparer(
      $this->container->get('config.storage.staging'),
      $this->container->get('config.storage'),
      $this->container->get('config.manager')
    );
    $this->assertIdentical($storage_comparer->createChangelist()->getChangelist(), $storage_comparer->getEmptyChangelist());

    // Now we have all configuration imported, test all of them for schema
    // conformance. Ensures all imported default configuration is valid when
    // all modules are enabled.
    $names = $this->container->get('config.storage')->listAll();
    /** @var \Drupal\Core\Config\TypedConfigManagerInterface $typed_config */
    $typed_config = $this->container->get('config.typed');
    foreach ($names as $name) {
      $config = $this->config($name);
      $this->assertConfigSchema($typed_config, $name, $config->get());
    }
  }
}

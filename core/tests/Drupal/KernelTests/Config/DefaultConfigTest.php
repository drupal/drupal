<?php

namespace Drupal\KernelTests\Config;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\KernelTests\AssertConfigTrait;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that the installed config matches the default config.
 *
 * @group Config
 */
class DefaultConfigTest extends KernelTestBase {

  use AssertConfigTrait;

  /**
   * {@inheritdoc}
   */
  protected static $timeLimit = 500;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // @todo ModuleInstaller calls system_rebuild_module_data which is part of
    //   system.module, see https://www.drupal.org/node/2208429.
    include_once $this->root . '/core/modules/system/system.module';

    // Set up the state values so we know where to find the files when running
    // drupal_get_filename().
    // @todo Remove as part of https://www.drupal.org/node/2186491
    system_rebuild_module_data();
  }

  /**
   * Tests if installed config is equal to the exported config.
   *
   * @dataProvider providerTestModuleConfig
   */
  public function testModuleConfig($module) {
    /** @var \Drupal\Core\Extension\ModuleInstallerInterface $module_installer */
    $module_installer = $this->container->get('module_installer');
    /** @var \Drupal\Core\Config\StorageInterface $active_config_storage */
    $active_config_storage = $this->container->get('config.storage');
    /** @var \Drupal\Core\Config\ConfigManagerInterface $config_manager */
    $config_manager = $this->container->get('config.manager');

    // @todo https://www.drupal.org/node/2308745 Rest has an implicit dependency
    //   on the Node module remove once solved.
    if (in_array($module, ['rest', 'hal'])) {
      $module_installer->install(['node']);
    }
    $module_installer->install([$module]);

    // System and user are required in order to be able to install some of the
    // other modules. Therefore they are put into static::$modules, which though
    // doesn't install config files, so import those config files explicitly.
    switch ($module) {
      case 'system':
      case 'user':
        $this->installConfig([$module]);
        break;
    }

    $default_install_path = drupal_get_path('module', $module) . '/' . InstallStorage::CONFIG_INSTALL_DIRECTORY;
    $module_config_storage = new FileStorage($default_install_path, StorageInterface::DEFAULT_COLLECTION);

    // The following config entries are changed on module install, so compare
    // them doesn't make sense.
    $skipped_config = [];
    $skipped_config['locale.settings'][] = 'path: ';
    $skipped_config['syslog.settings'][] = 'facility: ';
    // @todo Figure out why simpletest.settings is not installed.
    $skipped_config['simpletest.settings'] = TRUE;

    // Compare the installed config with the one in the module directory.
    foreach ($module_config_storage->listAll() as $config_name) {
      $result = $config_manager->diff($module_config_storage, $active_config_storage, $config_name);
      $this->assertConfigDiff($result, $config_name, $skipped_config);
    }
  }

  /**
   * Test data provider for ::testModuleConfig().
   *
   * @return array
   *   An array of module names to test.
   */
  public function providerTestModuleConfig() {
    $module_dirs = array_keys(iterator_to_array(new \FilesystemIterator(__DIR__ . '/../../../../modules/')));
    $module_names = array_map(function($path) {
      return str_replace(__DIR__ . '/../../../../modules/', '', $path);
    }, $module_dirs);
    $modules_keyed = array_combine($module_names, $module_names);

    $data = array_map(function ($module) {
      return [$module];
    }, $modules_keyed);

    return $data;
  }

}

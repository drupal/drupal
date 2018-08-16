<?php

namespace Drupal\KernelTests\Config;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\KernelTests\AssertConfigTrait;
use Drupal\KernelTests\FileSystemModuleDiscoveryDataProviderTrait;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that the installed config matches the default config.
 *
 * @group Config
 */
class DefaultConfigTest extends KernelTestBase {

  use AssertConfigTrait;
  use FileSystemModuleDiscoveryDataProviderTrait;

  /**
   * {@inheritdoc}
   */
  protected static $timeLimit = 500;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'user'];

  /**
   * The following config entries are changed on module install.
   *
   * Comparing them does not make sense.
   *
   * @todo Figure out why simpletest.settings is not installed.
   *
   * @var array
   */
  public static $skippedConfig = [
    'locale.settings' => ['path: '],
    'syslog.settings' => ['facility: '],
    'simpletest.settings' => TRUE,
  ];

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
   * @dataProvider coreModuleListDataProvider
   */
  public function testModuleConfig($module) {
    // System and user are required in order to be able to install some of the
    // other modules. Therefore they are put into static::$modules, which though
    // doesn't install config files, so import those config files explicitly.
    switch ($module) {
      case 'system':
      case 'user':
        $this->installConfig([$module]);
        break;
    }

    $module_path = drupal_get_path('module', $module) . '/';

    /** @var \Drupal\Core\Extension\ModuleInstallerInterface $module_installer */
    $module_installer = $this->container->get('module_installer');

    // Work out any additional modules and themes that need installing to create
    // an optional config.
    $optional_config_storage = new FileStorage($module_path . InstallStorage::CONFIG_OPTIONAL_DIRECTORY, StorageInterface::DEFAULT_COLLECTION);
    $modules_to_install = [$module];
    $themes_to_install = [];
    foreach ($optional_config_storage->listAll() as $config_name) {
      $data = $optional_config_storage->read($config_name);
      if (isset($data['dependencies']['module'])) {
        $modules_to_install = array_merge($modules_to_install, $data['dependencies']['module']);
      }
      if (isset($data['dependencies']['theme'])) {
        $themes_to_install = array_merge($themes_to_install, $data['dependencies']['theme']);
      }
    }
    $module_installer->install(array_unique($modules_to_install));
    $this->container->get('theme_installer')->install($themes_to_install);

    // Test configuration in the module's config/install directory.
    $module_config_storage = new FileStorage($module_path . InstallStorage::CONFIG_INSTALL_DIRECTORY, StorageInterface::DEFAULT_COLLECTION);
    $this->doTestsOnConfigStorage($module_config_storage);

    // Test configuration in the module's config/optional directory.
    $this->doTestsOnConfigStorage($optional_config_storage);
  }

  /**
   * Tests that default config matches the installed config.
   *
   * @param \Drupal\Core\Config\StorageInterface $default_config_storage
   *   The default config storage to test.
   */
  protected function doTestsOnConfigStorage(StorageInterface $default_config_storage) {
    /** @var \Drupal\Core\Config\ConfigManagerInterface $config_manager */
    $config_manager = $this->container->get('config.manager');

    // Just connect directly to the config table so we don't need to worry about
    // the cache layer.
    $active_config_storage = $this->container->get('config.storage');

    foreach ($default_config_storage->listAll() as $config_name) {
      if ($active_config_storage->exists($config_name)) {
        // If it is a config entity re-save it. This ensures that any
        // recalculation of dependencies does not cause config change.
        if ($entity_type = $config_manager->getEntityTypeIdByName($config_name)) {
          $entity_storage = $config_manager
            ->getEntityManager()
            ->getStorage($entity_type);
          $id = $entity_storage->getIDFromConfigName($config_name, $entity_storage->getEntityType()
            ->getConfigPrefix());
          $entity_storage->load($id)->calculateDependencies()->save();
        }
        $result = $config_manager->diff($default_config_storage, $active_config_storage, $config_name);
        $this->assertConfigDiff($result, $config_name, static::$skippedConfig);
      }
    }
  }

}

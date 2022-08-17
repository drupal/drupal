<?php

namespace Drupal\KernelTests\Config;

use Drupal\Core\Config\Entity\ConfigEntityDependency;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\ExtensionLifecycle;
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
  protected static $modules = ['system', 'user', 'path_alias'];

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
   * Tests if installed config is equal to the exported config.
   *
   * @dataProvider moduleListDataProvider
   */
  public function testModuleConfig($module) {
    $this->assertExtensionConfig($module, 'module');
  }

  /**
   * Tests if installed config is equal to the exported config.
   *
   * @dataProvider themeListDataProvider
   */
  public function testThemeConfig($theme) {
    $this->assertExtensionConfig($theme, 'theme');
  }

  /**
   * Tests that the config provided by the extension is correct.
   *
   * @param string $name
   *   Extension name.
   * @param string $type
   *   Extension type, either 'module' or 'theme'.
   *
   * @internal
   */
  protected function assertExtensionConfig(string $name, string $type): void {
    // Parse .info.yml file for module/theme $name. Since it's not installed at
    // this point we can't retrieve it from the 'module_handler' service.
    switch ($name) {
      case 'test_deprecated_theme':
        $file_name = DRUPAL_ROOT . '/core/modules/system/tests/themes/' . $name . '/' . $name . '.info.yml';
        break;

      case 'deprecated_module':
        $file_name = DRUPAL_ROOT . '/core/modules/system/tests/modules/' . $name . '/' . $name . '.info.yml';
        break;

      default;
        $file_name = DRUPAL_ROOT . '/core/' . $type . 's/' . $name . '/' . $name . '.info.yml';
    }

    $info = \Drupal::service('info_parser')->parse($file_name);
    // Test we have a parsed info.yml file.
    $this->assertNotEmpty($info);

    // Skip deprecated extensions.
    if (isset($info[ExtensionLifecycle::LIFECYCLE_IDENTIFIER])
      && $info[ExtensionLifecycle::LIFECYCLE_IDENTIFIER] === ExtensionLifecycle::DEPRECATED) {
      $this->markTestSkipped("The $type '$name' is deprecated.");
    }

    // System and user are required in order to be able to install some of the
    // other modules. Therefore they are put into static::$modules, which though
    // doesn't install config files, so import those config files explicitly. Do
    // this for all tests in case optional configuration depends on it.
    $this->installConfig(['system', 'user']);

    $extension_path = \Drupal::service('extension.path.resolver')->getPath($type, $name) . '/';
    $extension_config_storage = new FileStorage($extension_path . InstallStorage::CONFIG_INSTALL_DIRECTORY, StorageInterface::DEFAULT_COLLECTION);
    $optional_config_storage = new FileStorage($extension_path . InstallStorage::CONFIG_OPTIONAL_DIRECTORY, StorageInterface::DEFAULT_COLLECTION);

    if (empty($optional_config_storage->listAll()) && empty($extension_config_storage->listAll())) {
      $this->markTestSkipped("$name has no configuration to test");
    }

    // Work out any additional modules and themes that need installing to create
    // an optional config.
    $modules_to_install = $type !== 'theme' ? [$name] : [];
    $themes_to_install = $type === 'theme' ? [$name] : [];
    foreach ($optional_config_storage->listAll() as $config_name) {
      $data = $optional_config_storage->read($config_name);
      $dependency = new ConfigEntityDependency($config_name, $data);
      $modules_to_install = array_merge($modules_to_install, $dependency->getDependencies('module'));
      $themes_to_install = array_merge($themes_to_install, $dependency->getDependencies('theme'));
    }
    // Remove core and standard because they cannot be installed.
    $modules_to_install = array_diff(array_unique($modules_to_install), ['core', 'standard']);
    $this->container->get('module_installer')->install($modules_to_install);
    $this->container->get('theme_installer')->install(array_unique($themes_to_install));

    // Test configuration in the extension's config/install directory.
    $this->doTestsOnConfigStorage($extension_config_storage, $name, $type);

    // Test configuration in the extension's config/optional directory.
    $this->doTestsOnConfigStorage($optional_config_storage, $name, $type);
  }

  /**
   * A data provider that lists every theme in core.
   *
   * Also adds a deprecated theme with config.
   *
   * @return string[][]
   *   An array of theme names to test, with both key and value being the name
   *   of the theme.
   */
  public function themeListDataProvider() {
    $prefix = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'themes';
    $theme_dirs = array_keys(iterator_to_array(new \FilesystemIterator($prefix)));
    $theme_names = array_map(function ($path) use ($prefix) {
      return str_replace($prefix . DIRECTORY_SEPARATOR, '', $path);
    }, $theme_dirs);
    $themes_keyed = array_combine($theme_names, $theme_names);

    // Engines is not a theme.
    unset($themes_keyed['engines']);

    // Add a deprecated theme with config.
    $themes_keyed['test_deprecated_theme'] = 'test_deprecated_theme';

    return array_map(function ($theme) {
      return [$theme];
    }, $themes_keyed);
  }

  /**
   * A data provider that lists every module in core.
   *
   * Also adds a deprecated module with config.
   *
   * @return string[][]
   *   An array of module names to test, with both key and value being the name
   *   of the module.
   */
  public function moduleListDataProvider() {
    $modules_keyed = $this->coreModuleListDataProvider();

    // Add a deprecated module with config.
    $modules_keyed['deprecated_module'] = ['deprecated_module'];

    return $modules_keyed;
  }

  /**
   * Tests that default config matches the installed config.
   *
   * @param \Drupal\Core\Config\StorageInterface $default_config_storage
   *   The default config storage to test.
   * @param string $extension
   *   The extension that is being tested.
   * @param string $type
   *   The extension type to test.
   */
  protected function doTestsOnConfigStorage(StorageInterface $default_config_storage, $extension, string $type = 'module') {
    /** @var \Drupal\Core\Config\ConfigManagerInterface $config_manager */
    $config_manager = $this->container->get('config.manager');

    // Just connect directly to the config table so we don't need to worry about
    // the cache layer.
    $active_config_storage = $this->container->get('config.storage');

    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->container->get('config.factory');

    foreach ($default_config_storage->listAll() as $config_name) {
      if ($active_config_storage->exists($config_name)) {
        // If it is a config entity re-save it. This ensures that any
        // recalculation of dependencies does not cause config change.
        if ($entity_type = $config_manager->getEntityTypeIdByName($config_name)) {
          $entity_storage = $config_manager
            ->getEntityTypeManager()
            ->getStorage($entity_type);
          $id = $entity_storage->getIDFromConfigName($config_name, $entity_storage->getEntityType()
            ->getConfigPrefix());
          $entity_storage->load($id)->calculateDependencies()->save();
        }
        else {
          // Ensure simple configuration is re-saved so any schema sorting is
          // applied.
          $config_factory->getEditable($config_name)->save();
        }
        $result = $config_manager->diff($default_config_storage, $active_config_storage, $config_name);
        // ::assertConfigDiff will throw an exception if the configuration is
        // different.
        $this->assertNull($this->assertConfigDiff($result, $config_name, static::$skippedConfig));
      }
      else {
        $data = $default_config_storage->read($config_name);
        $dependency = new ConfigEntityDependency($config_name, $data);
        if ($dependency->hasDependency('module', 'standard')) {
          // Skip configuration with a dependency on the standard profile. Such
          // configuration has probably been removed from the standard profile
          // and needs its own test.
          continue;
        }
        $info = $this->container->get("extension.list.$type")->getExtensionInfo($extension);
        if (!isset($info[ExtensionLifecycle::LIFECYCLE_IDENTIFIER]) || $info[ExtensionLifecycle::LIFECYCLE_IDENTIFIER] !== ExtensionLifecycle::EXPERIMENTAL) {
          $this->fail("$config_name provided by $extension does not exist after installing all dependencies");
        }
      }
    }
  }

}

<?php

/**
 * @file
 * Contains Drupal\Core\Config\ConfigInstaller.
 */

namespace Drupal\Core\Config;

use Drupal\Component\Utility\Unicode;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ConfigInstaller implements ConfigInstallerInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The active configuration storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $activeStorage;

  /**
   * The typed configuration manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfig;

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs the configuration installer.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Config\StorageInterface $active_storage
   *   The active configuration storage.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   *   The typed configuration manager.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The configuration manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(ConfigFactoryInterface $config_factory, StorageInterface $active_storage, TypedConfigManagerInterface $typed_config, ConfigManagerInterface $config_manager, EventDispatcherInterface $event_dispatcher) {
    $this->configFactory = $config_factory;
    $this->activeStorage = $active_storage;
    $this->typedConfig = $typed_config;
    $this->configManager = $config_manager;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function installDefaultConfig($type, $name) {
    // Get all default configuration owned by this extension.
    $source_storage = new ExtensionInstallStorage($this->activeStorage);
    $config_to_install = $source_storage->listAll($name . '.');

    // Work out if this extension provides default configuration for any other
    // enabled extensions.
    $config_dir = drupal_get_path($type, $name) . '/config';
    if (is_dir($config_dir)) {
      if (is_dir($config_dir . '/schema')) {
        // Refresh the schema cache if installing default configuration and the
        // extension has a configuration schema directory.
        $this->typedConfig->clearCachedDefinitions();
      }
      $default_storage = new FileStorage($config_dir);
      $other_module_config = array_filter($default_storage->listAll(), function ($value) use ($name) {
        return !preg_match('/^' . $name . '\./', $value);
      });

      // Read enabled extensions directly from configuration to avoid circular
      // dependencies with ModuleHandler and ThemeHandler.
      $enabled_extensions = array_keys((array) $this->configFactory->get('system.module')->get('enabled'));
      $enabled_extensions += array_keys((array) $this->configFactory->get('system.theme')->get('enabled'));

      $other_module_config = array_filter($other_module_config, function ($config_name) use ($enabled_extensions) {
        $provider = Unicode::substr($config_name, 0, strpos($config_name, '.'));
        return in_array($provider, $enabled_extensions);
      });

      $config_to_install = array_merge($config_to_install, $other_module_config);
    }

    if (!empty($config_to_install)) {
      $old_state = $this->configFactory->getOverrideState();
      $this->configFactory->setOverrideState(FALSE);
      foreach ($config_to_install as $name) {
        // Only import new config.
        if ($this->activeStorage->exists($name)) {
          continue;
        }

        $new_config = new Config($name, $this->activeStorage, $this->eventDispatcher, $this->typedConfig);
        $data = $source_storage->read($name);
        if ($data !== FALSE) {
          $new_config->setData($data);
        }
        if ($entity_type = $this->configManager->getEntityTypeIdByName($name)) {
          $this->configManager
            ->getEntityManager()
            ->getStorageController($entity_type)
            ->create($new_config->get())
            ->save();
        }
        else {
          $new_config->save();
        }
      }
      $this->configFactory->setOverrideState($old_state);
    }
  }

}

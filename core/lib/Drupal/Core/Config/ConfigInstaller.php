<?php

/**
 * @file
 * Contains Drupal\Core\Config\ConfigInstaller.
 */

namespace Drupal\Core\Config;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\Entity\ConfigDependencyManager;
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
      // Order the configuration to install in the order of dependencies.
      $data = $source_storage->readMultiple($config_to_install);
      $dependency_manager = new ConfigDependencyManager();
      $sorted_config = $dependency_manager
        ->setData($data)
        ->sortAll();

      $old_state = $this->configFactory->getOverrideState();
      $this->configFactory->setOverrideState(FALSE);

      // Remove configuration that already exists in the active storage.
      $sorted_config = array_diff($sorted_config, $this->activeStorage->listAll());

      foreach ($sorted_config as $name) {
        $new_config = new Config($name, $this->activeStorage, $this->eventDispatcher, $this->typedConfig);
        if ($data[$name] !== FALSE) {
          $new_config->setData($data[$name]);
        }
        if ($entity_type = $this->configManager->getEntityTypeIdByName($name)) {
          $entity_storage = $this->configManager
            ->getEntityManager()
            ->getStorageController($entity_type);
          // It is possible that secondary writes can occur during configuration
          // creation. Updates of such configuration are allowed.
          if ($this->activeStorage->exists($name)) {
            $id = $entity_storage->getIDFromConfigName($name, $entity_storage->getEntityType()->getConfigPrefix());
            $entity = $entity_storage->load($id);
            foreach ($new_config->get() as $property => $value) {
              $entity->set($property, $value);
            }
            $entity->save();
          }
          else {
            $entity_storage
              ->create($new_config->get())
              ->save();
          }
        }
        else {
          $new_config->save();
        }
      }
      $this->configFactory->setOverrideState($old_state);
    }
    // Reset all the static caches and list caches.
    $this->configFactory->reset();
  }

}

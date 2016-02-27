<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\MigrationPluginManager.
 */

namespace Drupal\migrate\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDirectoryDiscovery;
use Drupal\Core\Plugin\Factory\ContainerFactory;

/**
 * Plugin manager for migration plugins.
 */
class MigrationPluginManager extends DefaultPluginManager implements MigrationPluginManagerInterface {

  /**
   * Provides default values for migrations.
   *
   * @var array
   */
  protected $defaults = array(
    'class' => '\Drupal\migrate\Plugin\Migration',
  );

  /**
   * The interface the plugins should implement.
   *
   * @var string
   */
  protected $pluginInterface = 'Drupal\migrate\Entity\MigrationInterface';

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Construct a migration plugin manager.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend for the definitions.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_backend, LanguageManagerInterface $language_manager) {
    $this->factory = new ContainerFactory($this, $this->pluginInterface);
    $this->alterInfo('migration_plugins');
    $this->setCacheBackend($cache_backend, 'migration_plugins', array('migration_plugins'));
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $directories = array_map(function($directory) {
        return [$directory . '/migration_templates', $directory . '/migrations'];
      }, $this->moduleHandler->getModuleDirectories());

      $yaml_discovery = new YamlDirectoryDiscovery($directories, 'migrate');
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($yaml_discovery);
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstances($id, array $configuration = array()) {
    $factory = $this->getFactory();
    $instances = [];
    $plugin_ids = preg_grep('/^' . preg_quote($id, '/') . ':/', array_keys($this->getDefinitions()));
    if ($this->hasDefinition($id)) {
      $plugin_ids[] = $id;
    }
    foreach ($plugin_ids as $plugin_id) {
      $instances[$plugin_id] = $factory->createInstance($plugin_id, isset($configuration[$plugin_id]) ? $configuration[$plugin_id] : []);
    }
    return $instances;
  }

}

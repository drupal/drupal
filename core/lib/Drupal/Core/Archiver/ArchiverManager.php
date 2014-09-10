<?php

/**
 * @file
 * Contains \Drupal\Core\Archiver\ArchiverManager.
 */

namespace Drupal\Core\Archiver;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides an Archiver plugin manager.
 *
 * @see \Drupal\Core\Archiver\Annotation\Archiver
 * @see \Drupal\Core\Archiver\ArchiverInterface
 * @see plugin_api
 */
class ArchiverManager extends DefaultPluginManager {

  /**
   * Constructs a ArchiverManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Archiver', $namespaces, $module_handler, 'Drupal\Core\Archiver\ArchiverInterface', 'Drupal\Core\Archiver\Annotation\Archiver');
    $this->alterInfo('archiver_info');
    $this->setCacheBackend($cache_backend, 'archiver_info_plugins');
  }

  /**
   * Overrides \Drupal\Component\Plugin\PluginManagerBase::createInstance().
   */
  public function createInstance($plugin_id, array $configuration = array()) {
    $plugin_definition = $this->getDefinition($plugin_id);
    $plugin_class = DefaultFactory::getPluginClass($plugin_id, $plugin_definition, 'Drupal\Core\Archiver\ArchiverInterface');
    return new $plugin_class($configuration['filepath']);
  }

  /**
   * Implements \Drupal\Core\PluginManagerInterface::getInstance().
   */
  public function getInstance(array $options) {
    $filepath = $options['filepath'];
    foreach ($this->getDefinitions() as $plugin_id => $definition) {
      foreach ($definition['extensions'] as $extension) {
        // Because extensions may be multi-part, such as .tar.gz,
        // we cannot use simpler approaches like substr() or pathinfo().
        // This method isn't quite as clean but gets the job done.
        // Also note that the file may not yet exist, so we cannot rely
        // on fileinfo() or other disk-level utilities.
        if (strrpos($filepath, '.' . $extension) === strlen($filepath) - strlen('.' . $extension)) {
          return $this->createInstance($plugin_id, $options);
        }
      }
    }
  }

}

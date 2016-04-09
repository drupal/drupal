<?php

namespace Drupal\Core\Queue;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Defines the queue worker manager.
 *
 * @see \Drupal\Core\Queue\QueueWorkerInterface
 * @see \Drupal\Core\Queue\QueueWorkerBase
 * @see \Drupal\Core\Annotation\QueueWorker
 * @see plugin_api
 */
class QueueWorkerManager extends DefaultPluginManager implements QueueWorkerManagerInterface {

  /**
   * Constructs an QueueWorkerManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/QueueWorker', $namespaces, $module_handler, 'Drupal\Core\Queue\QueueWorkerInterface', 'Drupal\Core\Annotation\QueueWorker');

    $this->setCacheBackend($cache_backend, 'queue_plugins');
    $this->alterInfo('queue_info');
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    // Assign a default time if a cron is specified.
    if (isset($definition['cron'])) {
      $definition['cron'] += [
        'time' => 15,
      ];
    }
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Queue\QueueWorkerInterface
   */
  public function createInstance($plugin_id, array $configuration = []) {
    return parent::createInstance($plugin_id, $configuration);
  }

}

<?php

namespace Drupal\workflows;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\workflows\Annotation\WorkflowType;

/**
 * Provides a Workflow type plugin manager.
 *
 * @see \Drupal\workflows\Annotation\WorkflowType
 * @see \Drupal\workflows\WorkflowTypeInterface
 * @see plugin_api
 */
class WorkflowTypeManager extends DefaultPluginManager {

  /**
   * Constructs a new class instance.
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
    parent::__construct('Plugin/WorkflowType', $namespaces, $module_handler, WorkflowTypeInterface::class, WorkflowType::class);
    $this->alterInfo('workflow_type_info');
    $this->setCacheBackend($cache_backend, 'workflow_type_info');
  }

}

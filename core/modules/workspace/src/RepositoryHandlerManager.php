<?php

namespace Drupal\workspace;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Core\Plugin\CategorizingPluginManagerTrait;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides a plugin manager for Repository Handlers.
 *
 * @see \Drupal\workspace\Annotation\RepositoryHandler
 * @see \Drupal\workspace\RepositoryHandlerInterface
 * @see plugin_api
 */
class RepositoryHandlerManager extends DefaultPluginManager implements RepositoryHandlerManagerInterface, FallbackPluginManagerInterface {

  use CategorizingPluginManagerTrait;

  /**
   * Constructs a new RepositoryHandlerManager.
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
    parent::__construct('Plugin/RepositoryHandler', $namespaces, $module_handler, 'Drupal\workspace\RepositoryHandlerInterface', 'Drupal\workspace\Annotation\RepositoryHandler');
    $this->alterInfo('workspace_repository_handler_info');
    $this->setCacheBackend($cache_backend, 'workspace_repository_handler');
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);
    $this->processDefinitionCategory($definition);
  }

  /**
   * {@inheritdoc}
   */
  public function createFromWorkspace(WorkspaceInterface $workspace) {
    $target = $workspace->target->value;
    $configuration = [
      'source' => $workspace->id(),
      'target' => $target,
    ];
    return $this->createInstance($target, $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) {
    return 'null';
  }

}

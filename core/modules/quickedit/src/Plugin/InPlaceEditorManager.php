<?php

/**
 * @file
 * Contains \Drupal\quickedit\Plugin\InPlaceEditorManager.
 */

namespace Drupal\quickedit\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides an in-place editor manager.
 *
 * The 'form' in-place editor must always be available.
 *
 * @see \Drupal\quickedit\Annotation\InPlaceEditor
 * @see \Drupal\quickedit\Plugin\InPlaceEditorBase
 * @see \Drupal\quickedit\Plugin\InPlaceEditorInterface
 * @see plugin_api
 */
class InPlaceEditorManager extends DefaultPluginManager {

  /**
   * Constructs a InPlaceEditorManager object.
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
    parent::__construct('Plugin/InPlaceEditor', $namespaces, $module_handler, 'Drupal\quickedit\Plugin\InPlaceEditorInterface', 'Drupal\quickedit\Annotation\InPlaceEditor');
    $this->alterInfo('quickedit_editor');
    $this->setCacheBackend($cache_backend, 'quickedit:editor');
  }

}

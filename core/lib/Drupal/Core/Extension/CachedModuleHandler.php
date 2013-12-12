<?php

/**
 * @file
 * Contains Drupal\Core\Extension\CachedModuleHandler.
 */

namespace Drupal\Core\Extension;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\KeyValueStore\StateInterface;

/**
 * Class that manages enabled modules in a Drupal installation.
 */
class CachedModuleHandler extends ModuleHandler implements CachedModuleHandlerInterface {

  /**
   * State key/value store.
   *
   * @var \Drupal\Core\KeyValueStore\StateInterface
   */
  protected $state;

  /**
   * Cache backend for storing enabled modules.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $bootstrapCache;

  /**
   * Whether the cache needs to be written.
   *
   * @var boolean
   */
  protected $cacheNeedsWriting = FALSE;

  /**
   * Constructs a new CachedModuleHandler object.
   */
  public function __construct(array $module_list = array(), StateInterface $state, CacheBackendInterface $bootstrap_cache) {
    parent::__construct($module_list);
    $this->state = $state;
    $this->bootstrapCache = $bootstrap_cache;
  }

  /**
   * Overrides \Drupal\Core\Extension\ModuleHandler::getHookInfo().
   */
  public function getHookInfo() {
    // When this function is indirectly invoked from bootstrap_invoke_all() prior
    // to all modules being loaded, we do not want to cache an incomplete
    // hook_hookInfo() result, so instead return an empty array. This requires
    // bootstrap hook implementations to reside in the .module file, which is
    // optimal for performance anyway.
    if (!$this->loaded) {
      return array();
    }
    if (!isset($this->hookInfo)) {
      if ($cache = $this->bootstrapCache->get('hook_info')) {
        $this->hookInfo = $cache->data;
      }
      else {
        $this->hookInfo = parent::getHookInfo();
        $this->bootstrapCache->set('hook_info', $this->hookInfo);
      }
    }
    return $this->hookInfo;
  }

  /**
   * Implements \Drupal\Core\Extension\ModuleHandlerInterface::resetImplementations().
   */
  public function resetImplementations() {
    // We maintain a persistent cache of hook implementations in addition to the
    // static cache to avoid looping through every module and every hook on each
    // request. Benchmarks show that the benefit of this caching outweighs the
    // additional database hit even when using the default database caching
    // backend and only a small number of modules are enabled. The cost of the
    // $this->bootstrapCache->get() is more or less constant and reduced further when
    // non-database caching backends are used, so there will be more significant
    // gains when a large number of modules are installed or hooks invoked, since
    // this can quickly lead to \Drupal::moduleHandler()->implementsHook() being
    // called several thousand times per request.
    parent::resetImplementations();
    $this->bootstrapCache->set('module_implements', array());
    $this->bootstrapCache->delete('hook_info');
  }

  /**
   * Implements \Drupal\Core\Extension\CachedModuleHandlerInterface::writeCache().
   */
  public function writeCache() {
    if ($this->cacheNeedsWriting) {
      $this->bootstrapCache->set('module_implements', $this->implementations);
      $this->cacheNeedsWriting = FALSE;
    }
  }

  /**
   * Overrides \Drupal\Core\Extension\ModuleHandler::getImplementationInfo().
   */
  protected function getImplementationInfo($hook) {
    if (!isset($this->implementations)) {
      $this->implementations = $this->getCachedImplementationInfo();
    }
    if (!isset($this->implementations[$hook])) {
      // The hook is not cached, so ensure that whether or not it has
      // implementations, the cache is updated at the end of the request.
      $this->cacheNeedsWriting = TRUE;
      $this->implementations[$hook] = parent::getImplementationInfo($hook);
    }
    else {
      foreach ($this->implementations[$hook] as $module => $group) {
        // If this hook implementation is stored in a lazy-loaded file, include
        // that file first.
        if ($group) {
          $this->loadInclude($module, 'inc', "$module.$group");
        }
        // It is possible that a module removed a hook implementation without the
        // implementations cache being rebuilt yet, so we check whether the
        // function exists on each request to avoid undefined function errors.
        // Since \Drupal::moduleHandler()->implementsHook() may needlessly try to
        // load the include file again, function_exists() is used directly here.
        if (!function_exists($module . '_' . $hook)) {
          // Clear out the stale implementation from the cache and force a cache
          // refresh to forget about no longer existing hook implementations.
          unset($this->implementations[$hook][$module]);
          $this->cacheNeedsWriting = TRUE;
        }
      }
    }
    return $this->implementations[$hook];
  }

  /**
   * Retrieves hook implementation info from the cache.
   */
  protected function getCachedImplementationInfo() {
    if ($cache = $this->bootstrapCache->get('module_implements')) {
      return $cache->data;
    }
    else {
      return array();
    }
  }

}

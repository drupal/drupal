<?php

/**
 * @file
 * Contains \Drupal\Core\Path\AliasWhitelist.
 */

namespace Drupal\Core\Path;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheCollector;
use Drupal\Core\Database\Connection;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Lock\LockBackendInterface;

/**
 * Extends CacheCollector to build the path alias whitelist over time.
 */
class AliasWhitelist extends CacheCollector implements AliasWhitelistInterface {

  /**
   * The Key/Value Store to use for state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The Path CRUD service.
   *
   * @var \Drupal\Core\Path\AliasStorageInterface
   */
  protected $aliasStorage;

  /**
   * Constructs an AliasWhitelist object.
   *
   * @param string $cid
   *   The cache id to use.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state keyvalue store.
   * @param \Drupal\Core\Path\AliasStorageInterface $alias_storage
   *   The alias storage service.
   */
  public function __construct($cid, CacheBackendInterface $cache, LockBackendInterface $lock, StateInterface $state, AliasStorageInterface $alias_storage) {
    parent::__construct($cid, $cache, $lock);
    $this->state = $state;
    $this->aliasStorage = $alias_storage;
  }

  /**
   * {@inheritdoc}
   */
  protected function lazyLoadCache() {
    parent::lazyLoadCache();

    // On a cold start $this->storage will be empty and the whitelist will
    // need to be rebuilt from scratch. The whitelist is initialized from the
    // list of all valid path roots stored in the 'router.path_roots' state,
    // with values initialized to NULL. During the request, each path requested
    // that matches one of these keys will be looked up and the array value set
    // to either TRUE or FALSE. This ensures that paths which do not exist in
    // the router are not looked up, and that paths that do exist in the router
    // are only looked up once.
    if (empty($this->storage)) {
      $this->loadMenuPathRoots();
    }
  }

  /**
   * Loads menu path roots to prepopulate cache.
   */
  protected function loadMenuPathRoots() {
    if ($roots = $this->state->get('router.path_roots')) {
      foreach ($roots as $root) {
        $this->storage[$root] = NULL;
        $this->persist($root);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function get($offset) {
    $this->lazyLoadCache();
    // this may be called with paths that are not represented by menu router
    // items such as paths that will be rewritten by hook_url_outbound_alter().
    // Therefore internally TRUE is used to indicate whitelisted paths. FALSE is
    // used to indicate paths that have already been checked but are not
    // whitelisted, and NULL indicates paths that have not been checked yet.
    if (isset($this->storage[$offset])) {
      if ($this->storage[$offset]) {
        return TRUE;
      }
    }
    elseif (array_key_exists($offset, $this->storage)) {
      return $this->resolveCacheMiss($offset);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function resolveCacheMiss($root) {
    $exists = $this->aliasStorage->pathHasMatchingAlias('/' . $root);
    $this->storage[$root] = $exists;
    $this->persist($root);
    if ($exists) {
      return TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function clear() {
    parent::clear();
    $this->loadMenuPathRoots();
  }

}

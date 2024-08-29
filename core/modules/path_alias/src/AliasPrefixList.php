<?php

namespace Drupal\path_alias;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheCollector;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\State\StateInterface;

/**
 * Cache a list of valid alias prefixes.
 */
class AliasPrefixList extends CacheCollector implements AliasPrefixListInterface {

  /**
   * The Key/Value Store to use for state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The path alias repository.
   *
   * @var \Drupal\path_alias\AliasRepositoryInterface
   */
  protected $pathAliasRepository;

  /**
   * Constructs an AliasPrefixList object.
   *
   * @param string $cid
   *   The cache id to use.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state keyvalue store.
   * @param \Drupal\path_alias\AliasRepositoryInterface $alias_repository
   *   The path alias repository.
   */
  public function __construct($cid, CacheBackendInterface $cache, LockBackendInterface $lock, StateInterface $state, AliasRepositoryInterface $alias_repository) {
    parent::__construct($cid, $cache, $lock);
    $this->state = $state;
    $this->pathAliasRepository = $alias_repository;
  }

  /**
   * {@inheritdoc}
   */
  protected function lazyLoadCache() {
    parent::lazyLoadCache();

    // On a cold start $this->storage will be empty and the prefix list will
    // need to be rebuilt from scratch. The prefix list is initialized from the
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
    // This may be called with paths that are not represented by menu router
    // items such as paths that will be rewritten by hook_url_outbound_alter().
    // Therefore internally TRUE is used to indicate valid paths. FALSE is
    // used to indicate paths that have already been checked but are not
    // valid, and NULL indicates paths that have not been checked yet.
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
    $exists = $this->pathAliasRepository->pathHasMatchingAlias('/' . $root);
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

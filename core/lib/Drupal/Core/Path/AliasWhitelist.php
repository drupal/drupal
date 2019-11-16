<?php

namespace Drupal\Core\Path;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheCollector;
use Drupal\Core\DependencyInjection\DeprecatedServicePropertyTrait;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Lock\LockBackendInterface;

/**
 * Extends CacheCollector to build the path alias whitelist over time.
 *
 * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use
 *   \Drupal\path_alias\AliasWhitelist instead.
 *
 * @see https://www.drupal.org/node/3092086
 */
class AliasWhitelist extends CacheCollector implements AliasWhitelistInterface {

  use DeprecatedServicePropertyTrait;

  /**
   * {@inheritdoc}
   */
  protected $deprecatedProperties = ['aliasStorage' => 'path.alias_storage'];

  /**
   * The Key/Value Store to use for state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The path alias repository.
   *
   * @var \Drupal\Core\Path\AliasRepositoryInterface
   */
  protected $pathAliasRepository;

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
   * @param \Drupal\Core\Path\AliasRepositoryInterface $alias_repository
   *   The path alias repository.
   */
  public function __construct($cid, CacheBackendInterface $cache, LockBackendInterface $lock, StateInterface $state, $alias_repository) {
    parent::__construct($cid, $cache, $lock);
    $this->state = $state;

    if (!$alias_repository instanceof AliasRepositoryInterface) {
      @trigger_error('Passing the path.alias_storage service to AliasWhitelist::__construct() is deprecated in drupal:8.8.0 and will be removed before drupal:9.0.0. Pass the new dependencies instead. See https://www.drupal.org/node/3013865.', E_USER_DEPRECATED);
      $alias_repository = \Drupal::service('path_alias.repository');
    }
    $this->pathAliasRepository = $alias_repository;

    // This is used as base class by the new class, so we do not trigger
    // deprecation notices when that or any child class is instantiated.
    $new_class = 'Drupal\path_alias\AliasWhitelist';
    if (!is_a($this, $new_class) && class_exists($new_class)) {
      @trigger_error('The \\' . __CLASS__ . ' class is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Instead, use \\' . $new_class . '. See https://drupal.org/node/3092086', E_USER_DEPRECATED);
    }
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

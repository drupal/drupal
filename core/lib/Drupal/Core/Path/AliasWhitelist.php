<?php

/**
 * @file
 * Contains \Drupal\Core\Path\AliasWhitelist.
 */

namespace Drupal\Core\Path;

use Drupal\Core\Database\Connection;
use Drupal\Core\DestructableInterface;
use Drupal\Core\KeyValueStore\KeyValueFactory;
use Drupal\Core\Utility\CacheArray;

/**
 * Extends CacheArray to build the path alias whitelist over time.
 */
class AliasWhitelist extends CacheArray implements DestructableInterface {

  /**
   * The Key/Value Store to use for state.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $state;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs an AliasWhitelist object.
   *
   * @param string $cid
   *   The cache id to use.
   * @param string $bin
   *   The cache bin that should be used.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactory $keyvalue
   *   The keyvalue factory to get the state cache from.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct($cid, $bin, KeyValueFactory $keyvalue, Connection $connection) {
    parent::__construct($cid, $bin);
    $this->state = $keyvalue->get('state');
    $this->connection = $connection;

    // On a cold start $this->storage will be empty and the whitelist will
    // need to be rebuilt from scratch. The whitelist is initialized from the
    // list of all valid path roots stored in the 'menu_path_roots' state,
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
    if ($roots = $this->state->get('menu_path_roots')) {
      foreach ($roots as $root) {
        $this->storage[$root] = NULL;
        $this->persist($root);
      }
    }
  }

  /**
   * Overrides \ArrayAccess::offsetGet().
   */
  public function offsetGet($offset) {
    // url() may be called with paths that are not represented by menu router
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
   * Overrides \Drupal\Core\Utility\CacheArray::resolveCacheMiss().
   */
  public function resolveCacheMiss($root) {
    $query = $this->connection->select('url_alias', 'u');
    $query->addExpression(1);
    $exists = (bool) $query
      ->condition('u.source', $this->connection->escapeLike($root) . '%', 'LIKE')
      ->range(0, 1)
      ->execute()
      ->fetchField();
    $this->storage[$root] = $exists;
    $this->persist($root);
    if ($exists) {
      return TRUE;
    }
  }

  /**
   * Overrides \Drupal\Core\Utility\CacheArray::set().
   */
  public function set($data, $lock = TRUE) {
    $lock_name = $this->cid . ':' . $this->bin;
    if (!$lock || lock()->acquire($lock_name)) {
      if ($cached = cache($this->bin)->get($this->cid)) {
        // Use array merge instead of union so that filled in values in $data
        // overwrite empty values in the current cache.
        $data = array_merge($cached->data, $data);
      }
      cache($this->bin)->set($this->cid, $data);
      if ($lock) {
        lock()->release($lock_name);
      }
    }
  }

  /**
   * Overrides \Drupal\Core\Utility\CacheArray::clear().
   */
  public function clear() {
    parent::clear();
    $this->loadMenuPathRoots();
  }

  /**
   * Implements Drupal\Core\DestructableInterface::destruct().
   */
  public function destruct() {
    parent::__destruct();
  }

  /**
   * Overrides \Drupal\Core\Utility\CacheArray::clear().
   */
  public function __destruct() {
    // Do nothing to avoid segmentation faults. This can go away after the
    // cache collector from http://drupal.org/node/1786490 is used.
  }
}

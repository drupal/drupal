<?php
/**
 * @file
 * Contains \Drupal\Core\Access\AccessResult.
 */

namespace Drupal\Core\Access;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Value object for passing an access result with cacheability metadata.
 *
 * When using ::orIf() and ::andIf(), cacheability metadata will be merged
 * accordingly as well.
 */
class AccessResult implements AccessResultInterface, CacheableInterface {

  /**
   * The value that explicitly allows access.
   */
  const ALLOW = 'ALLOW';

  /**
   * The value that neither explicitly allows nor explicitly forbids access.
   */
  const DENY = 'DENY';

  /**
   * The value that explicitly forbids access.
   */
  const KILL = 'KILL';

  /**
   * The access result value.
   *
   * A \Drupal\Core\Access\AccessResultInterface constant value.
   *
   * @var string
   */
  protected $value;

  /**
   * Whether the access result is cacheable.
   *
   * @var bool
   */
  protected $isCacheable;

  /**
   * The cache context IDs (to vary a cache item ID based on active contexts).
   *
   * @see \Drupal\Core\Cache\CacheContextInterface
   * @see \Drupal\Core\Cache\CacheContexts::convertTokensToKeys()
   *
   * @var string[]
   */
  protected $contexts;

  /**
   * The cache tags.
   *
   * @var array
   */
  protected $tags;

  /**
   * The maximum caching time in seconds.
   *
   * @var int
   */
  protected $maxAge;

  /**
   * Constructs a new AccessResult object.
   */
  public function __construct() {
    $this->resetAccess();
    $this->setCacheable(TRUE)
      ->resetCacheContexts()
      ->resetCacheTags()
      // Typically, cache items are invalidated via associated cache tags, not
      // via a maximum age.
      ->setCacheMaxAge(Cache::PERMANENT);
  }

  /**
   * Instantiates a new AccessResult object.
   *
   * This factory method exists to improve DX; it allows developers to fluently
   * create access results.
   *
   * Defaults to a cacheable access result that neither explicitly allows nor
   * explicitly forbids access.
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public static function create() {
    return new static();
  }

  /**
   * Convenience method, creates an AccessResult object and calls allow().
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public static function allowed() {
    return static::create()->allow();
  }

  /**
   * Convenience method, creates an AccessResult object and calls forbid().
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public static function forbidden() {
    return static::create()->forbid();
  }

  /**
   * Convenience method, creates an AccessResult object and calls allowIf().
   *
   * @param bool $condition
   *   The condition to evaluate. If TRUE, ::allow() will be called.
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public static function allowedIf($condition) {
    return static::create()->allowIf($condition);
  }

  /**
   * Convenience method, creates an AccessResult object and calls forbiddenIf().
   *
   * @param bool $condition
   *   The condition to evaluate. If TRUE, ::forbid() will be called.
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public static function forbiddenIf($condition) {
    return static::create()->forbidIf($condition);
  }

  /**
   * Convenience method, creates an AccessResult object and calls allowIfHasPermission().
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account for which to check a permission.
   * @param string $permission
   *   The permission to check for.
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public static function allowedIfHasPermission(AccountInterface $account, $permission) {
    return static::create()->allowIfHasPermission($account, $permission);
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowed() {
    return $this->value === static::ALLOW;
  }

  /**
   * {@inheritdoc}
   */
  public function isForbidden() {
    return $this->value === static::KILL;
  }

  /**
   * Explicitly allows access.
   *
   * @return $this
   */
  public function allow() {
    $this->value = static::ALLOW;
    return $this;
  }

  /**
   * Explicitly forbids access.
   *
   * @return $this
   */
  public function forbid() {
    $this->value = static::KILL;
    return $this;
  }

  /**
   * Neither explicitly allows nor explicitly forbids access.
   *
   * @return $this
   */
  public function resetAccess() {
    $this->value = static::DENY;
    return $this;
  }

  /**
   * Conditionally calls ::allow().
   *
   * @param bool $condition
   *   The condition to evaluate. If TRUE, ::allow() will be called.
   *
   * @return $this
   */
  public function allowIf($condition) {
    if ($condition) {
      $this->allow();
    }
    return $this;
  }

  /**
   * Conditionally calls ::forbid().
   *
   * @param bool $condition
   *   The condition to evaluate. If TRUE, ::forbid() will be called.
   *
   * @return $this
   */
  public function forbidIf($condition) {
    if ($condition) {
      $this->forbid();
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * AccessResult objects solely return cache context tokens, no static strings.
   */
  public function getCacheKeys() {
    sort($this->contexts);
    return $this->contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return $this->tags;
  }

  /**
   * {@inheritdoc}
   *
   * It's not very useful to cache individual access results, but the interface
   * forces us to implement this method, so just use the default cache bin.
   */
  public function getCacheBin() {
    return 'default';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return $this->maxAge;
  }

  /**
   * {@inheritdoc}
   */
  public function isCacheable() {
    return $this->isCacheable;
  }

  /**
   * Sets whether this access result is cacheable. It is cacheable by default.
   *
   * @param bool $is_cacheable
   *   Whether this access result is cacheable.
   *
   * @return $this
   */
  public function setCacheable($is_cacheable) {
    $this->isCacheable = $is_cacheable;
    return $this;
  }

  /**
   * Adds cache contexts associated with the access result.
   *
   * @param string[] $contexts
   *   An array of cache context IDs, used to generate a cache ID.
   *
   * @return $this
   */
  public function addCacheContexts(array $contexts) {
    $this->contexts = array_unique(array_merge($this->contexts, $contexts));
    return $this;
  }

  /**
   * Resets cache contexts (to the empty array).
   *
   * @return $this
   */
  public function resetCacheContexts() {
    $this->contexts = array();
    return $this;
  }

  /**
   * Adds cache tags associated with the access result.
   *
   * @param array $tags
   *   An array of cache tags.
   *
   * @return $this
   */
  public function addCacheTags(array $tags) {
    foreach ($tags as $namespace => $values) {
      if (is_array($values)) {
        foreach ($values as $value) {
          $this->tags[$namespace][$value] = $value;
        }
        ksort($this->tags[$namespace]);
      }
      else {
        if (!isset($this->tags[$namespace])) {
          $this->tags[$namespace] = $values;
        }
      }
    }
    ksort($this->tags);
    return $this;
  }

  /**
   * Resets cache tags (to the empty array).
   *
   * @return $this
   */
  public function resetCacheTags() {
    $this->tags = array();
    return $this;
  }

  /**
   * Sets the maximum age for which this access result may be cached.
   *
   * @param int $max_age
   *   The maximum time in seconds that this access result may be cached.
   *
   * @return $this
   */
  public function setCacheMaxAge($max_age) {
    $this->maxAge = $max_age;
    return $this;
  }

  /**
   * Convenience method, adds the "cache_context.user.roles" cache context.
   *
   * @return $this
   */
  public function cachePerRole() {
    $this->addCacheContexts(array('cache_context.user.roles'));
    return $this;
  }

  /**
   * Convenience method, adds the "cache_context.user" cache context.
   *
   * @return $this
   */
  public function cachePerUser() {
    $this->addCacheContexts(array('cache_context.user'));
    return $this;
  }

  /**
   * Convenience method, adds the entity's cache tag.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity whose cache tag to set on the access result.
   *
   * @return $this
   */
  public function cacheUntilEntityChanges(EntityInterface $entity) {
    $this->addCacheTags($entity->getCacheTag());
    return $this;
  }

  /**
   * Convenience method, checks permission and calls ::cachePerRole().
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account for which to check a permission.
   * @param string $permission
   *   The permission to check for.
   *
   * @return $this
   */
  public function allowIfHasPermission(AccountInterface $account, $permission) {
    $this->allowIf($account->hasPermission($permission))->cachePerRole();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function orIf(AccessResultInterface $other) {
    // If this AccessResult already is forbidden, then that already is the
    // conclusion. We can completely disregard $other.
    if ($this->isForbidden()) {
      return $this;
    }
    // Otherwise, we make this AccessResult forbidden if the other is, or
    // allowed if the other is, and we merge in the cacheability metadata if the
    // other access result also has cacheability metadata.
    else {
      if ($other->isForbidden()) {
        $this->forbid();
      }
      else if ($other->isAllowed()) {
        $this->allow();
      }
      $this->mergeCacheabilityMetadata($other);
      return $this;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function andIf(AccessResultInterface $other) {
    // If this AccessResult already is forbidden or is merely not explicitly
    // allowed, then that already is the conclusion. We can completely disregard
    // $other.
    if ($this->isForbidden() || !$this->isAllowed()) {
      return $this;
    }
    // Otherwise, we make this AccessResult forbidden if the other is, or not
    // explicitly allowed if the other isn't, and we merge in the cacheability
    // metadata if the other access result also has cacheability metadata.
    else {
      if ($other->isForbidden()) {
        $this->forbid();
      }
      else if (!$other->isAllowed()) {
        $this->resetAccess();
      }
      $this->mergeCacheabilityMetadata($other);
      return $this;
    }
  }

  /**
   * Merges the cacheability metadata of the other access result, if any.
   *
   * @param \Drupal\Core\Access\AccessResultInterface $other
   *   The other access result, whose cacheability data (if any) to merge.
   */
  protected function mergeCacheabilityMetadata(AccessResultInterface $other) {
    if ($other instanceof CacheableInterface) {
      $this->setCacheable($other->isCacheable());
      $this->addCacheContexts($other->getCacheKeys());
      $this->addCacheTags($other->getCacheTags());
      // Use the lowest max-age.
      if ($this->getCacheMaxAge() === Cache::PERMANENT) {
        // The other max-age is either lower or equal.
        $this->setCacheMaxAge($other->getCacheMaxAge());
      }
      else {
        $this->setCacheMaxAge(min($this->getCacheMaxAge(), $other->getCacheMaxAge()));
      }
    }
    // If any of the access results don't provide cacheability metadata, then
    // we cannot cache the combined access result, for we may not make
    // assumptions.
    else {
      $this->setCacheable(FALSE);
    }
  }

}

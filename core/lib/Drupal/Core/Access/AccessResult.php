<?php
/**
 * @file
 * Contains \Drupal\Core\Access\AccessResult.
 */

namespace Drupal\Core\Access;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableInterface;
use Drupal\Core\Config\ConfigBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Value object for passing an access result with cacheability metadata.
 *
 * The access result itself — excluding the cacheability metadata — is
 * immutable. There are subclasses for each of the three possible access results
 * themselves:
 *
 * @see \Drupal\Core\Access\AccessResultAllowed
 * @see \Drupal\Core\Access\AccessResultForbidden
 * @see \Drupal\Core\Access\AccessResultNeutral
 *
 * When using ::orIf() and ::andIf(), cacheability metadata will be merged
 * accordingly as well.
 */
abstract class AccessResult implements AccessResultInterface, CacheableInterface {

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
    $this->setCacheable(TRUE)
      ->resetCacheContexts()
      ->resetCacheTags()
      // Typically, cache items are invalidated via associated cache tags, not
      // via a maximum age.
      ->setCacheMaxAge(Cache::PERMANENT);
  }

  /**
   * Creates an AccessResultInterface object with isNeutral() === TRUE.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   isNeutral() will be TRUE.
   */
  public static function neutral() {
    return new AccessResultNeutral();
  }

  /**
   * Creates an AccessResultInterface object with isAllowed() === TRUE.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   isAllowed() will be TRUE.
   */
  public static function allowed() {
    return new AccessResultAllowed();
  }

  /**
   * Creates an AccessResultInterface object with isForbidden() === TRUE.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   isForbidden() will be TRUE.
   */
  public static function forbidden() {
    return new AccessResultForbidden();
  }

  /**
   * Creates an allowed or neutral access result.
   *
   * @param bool $condition
   *   The condition to evaluate.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   If $condition is TRUE, isAllowed() will be TRUE, otherwise isNeutral()
   *   will be TRUE.
   */
  public static function allowedIf($condition) {
    return $condition ? static::allowed() : static::neutral();
  }

  /**
   * Creates a forbidden or neutral access result.
   *
   * @param bool $condition
   *   The condition to evaluate.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   If $condition is TRUE, isForbidden() will be TRUE, otherwise isNeutral()
   *   will be TRUE.
   */
  public static function forbiddenIf($condition) {
    return $condition ? static::forbidden(): static::neutral();
  }

  /**
   * Creates an allowed access result if the permission is present, neutral otherwise.
   *
   * Convenience method, checks the permission and calls ::cachePerRole().
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account for which to check a permission.
   * @param string $permission
   *   The permission to check for.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   If the account has the permission, isAllowed() will be TRUE, otherwise
   *   isNeutral() will be TRUE.
   */
  public static function allowedIfHasPermission(AccountInterface $account, $permission) {
    return static::allowedIf($account->hasPermission($permission))->cachePerRole();
  }

  /**
   * Creates an allowed access result if the permissions are present, neutral otherwise.
   *
   * Convenience method, checks the permissions and calls ::cachePerRole().
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account for which to check permissions.
   * @param array $permissions
   *   The permissions to check.
   * @param string $conjunction
   *   (optional) 'AND' if all permissions are required, 'OR' in case just one.
   *   Defaults to 'AND'
   *
   * @return \Drupal\Core\Access\AccessResult
   *   If the account has the permissions, isAllowed() will be TRUE, otherwise
   *   isNeutral() will be TRUE.
   */
  public static function allowedIfHasPermissions(AccountInterface $account, array $permissions, $conjunction = 'AND') {
    $access = FALSE;

    if ($conjunction == 'AND' && !empty($permissions)) {
      $access = TRUE;
      foreach ($permissions as $permission) {
        if (!$permission_access = $account->hasPermission($permission)) {
          $access = FALSE;
          break;
        }
      }
    }
    else {
      foreach ($permissions as $permission) {
        if ($permission_access = $account->hasPermission($permission)) {
          $access = TRUE;
          break;
        }
      }
    }

    return static::allowedIf($access)->cachePerRole();
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\Core\Access\AccessResultAllowed
   */
  public function isAllowed() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\Core\Access\AccessResultForbidden
   */
  public function isForbidden() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\Core\Access\AccessResultNeutral
   */
  public function isNeutral() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheKeys() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
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
    $this->tags = Cache::mergeTags($this->tags, $tags);
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
   * Convenience method, adds the "user.roles" cache context.
   *
   * @return $this
   */
  public function cachePerRole() {
    $this->addCacheContexts(array('user.roles'));
    return $this;
  }

  /**
   * Convenience method, adds the "user" cache context.
   *
   * @return $this
   */
  public function cachePerUser() {
    $this->addCacheContexts(array('user'));
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
    $this->addCacheTags($entity->getCacheTags());
    return $this;
  }

  /**
   * Convenience method, adds the configuration object's cache tag.
   *
   * @param \Drupal\Core\Config\ConfigBase $configuration
   *   The configuration object whose cache tag to set on the access result.
   *
   * @return $this
   */
  public function cacheUntilConfigurationChanges(ConfigBase $configuration) {
    $this->addCacheTags($configuration->getCacheTags());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function orIf(AccessResultInterface $other) {
    // $other's cacheability metadata is merged if $merge_other gets set to TRUE
    // and this happens in two cases:
    // 1. $other's access result is the one that determines the combined access
    //    result.
    // 2. This access result is not cacheable and $other's access result is the
    //    same. i.e. attempt to return a cacheable access result.
    $merge_other = FALSE;
    if ($this->isForbidden() || $other->isForbidden()) {
      $result = static::forbidden();
      if (!$this->isForbidden() || (!$this->isCacheable() && $other->isForbidden())) {
        $merge_other = TRUE;
      }
    }
    elseif ($this->isAllowed() || $other->isAllowed()) {
      $result = static::allowed();
      if (!$this->isAllowed() || (!$this->isCacheable() && $other->isAllowed())) {
        $merge_other = TRUE;
      }
    }
    else {
      $result = static::neutral();
      if (!$this->isNeutral() || (!$this->isCacheable() && $other->isNeutral())) {
        $merge_other = TRUE;
      }
    }
    $result->inheritCacheability($this);
    if ($merge_other) {
      $result->inheritCacheability($other);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function andIf(AccessResultInterface $other) {
    // The other access result's cacheability metadata is merged if $merge_other
    // gets set to TRUE. It gets set to TRUE in one case: if the other access
    // result is used.
    $merge_other = FALSE;
    if ($this->isForbidden() || $other->isForbidden()) {
      $result = static::forbidden();
      if (!$this->isForbidden()) {
        $merge_other = TRUE;
      }
    }
    elseif ($this->isAllowed() && $other->isAllowed()) {
      $result = static::allowed();
      $merge_other = TRUE;
    }
    else {
      $result = static::neutral();
      if (!$this->isNeutral()) {
        $merge_other = TRUE;
      }
    }
    $result->inheritCacheability($this);
    if ($merge_other) {
      $result->inheritCacheability($other);
      // If this access result is not cacheable, then an AND with another access
      // result must also not be cacheable, except if the other access result
      // has isForbidden() === TRUE. isForbidden() access results are contagious
      // in that they propagate regardless of the other value.
      if (!$this->isCacheable() && !$result->isForbidden()) {
        $result->setCacheable(FALSE);
      }
    }
    return $result;
  }

  /**
   * Inherits the cacheability of the other access result, if any.
   *
   * @param \Drupal\Core\Access\AccessResultInterface $other
   *   The other access result, whose cacheability (if any) to inherit.
   *
   * @return $this
   */
  public function inheritCacheability(AccessResultInterface $other) {
    if ($other instanceof CacheableInterface) {
      $this->setCacheable($other->isCacheable());
      $this->addCacheContexts($other->getCacheContexts());
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
    return $this;
  }

}

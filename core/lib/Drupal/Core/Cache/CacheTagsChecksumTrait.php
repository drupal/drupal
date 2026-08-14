<?php

declare(strict_types=1);

namespace Drupal\Core\Cache;

/**
 * A trait for cache tag checksum implementations.
 *
 * Handles delayed cache tag invalidations.
 */
trait CacheTagsChecksumTrait {

  /**
   * A list of tags that have already been invalidated in this request.
   *
   * Used to prevent the invalidation of the same cache tag multiple times.
   *
   * @var bool[]
   */
  protected $invalidatedTags = [];

  /**
   * The set of cache tags whose invalidation is delayed.
   *
   * @var string[]
   */
  protected $delayedTags = [];

  /**
   * Contains already loaded tag invalidation counts from the storage.
   *
   * @var int[]
   */
  protected $tagCache = [];

  /**
   * Registered cache tags to preload.
   */
  protected array $preloadTags = [];

  /**
   * Callback to be invoked just after a database transaction gets committed.
   *
   * Executes all delayed tag invalidations.
   *
   * @param bool $success
   *   Whether or not the transaction was successful.
   */
  public function rootTransactionEndCallback($success): void {
    if ($success) {
      $this->doInvalidateTags($this->delayedTags);
    }
    $this->delayedTags = [];
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateTags(array $tags): void {
    foreach ($tags as $key => $tag) {
      if (isset($this->invalidatedTags[$tag])) {
        unset($tags[$key]);
      }
      else {
        $this->invalidatedTags[$tag] = TRUE;
        unset($this->tagCache[$tag]);
      }
    }
    if (!$tags) {
      return;
    }

    $in_transaction = $this->getDatabaseConnection()->inTransaction();
    if ($in_transaction) {
      if (empty($this->delayedTags)) {
        $this->getDatabaseConnection()
          ->transactionManager()
          ->addPostTransactionCallback([$this, 'rootTransactionEndCallback']);
      }
      $this->delayedTags = Cache::mergeTags($this->delayedTags, $tags);
    }
    else {
      $this->doInvalidateTags($tags);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentChecksum(array $tags): string {
    // Any cache writes in this request containing cache tags whose invalidation
    // has been delayed due to an in-progress transaction must not be read by
    // any other request, so use a nonsensical checksum which will cause any
    // written cache items to be ignored.
    if ($this->delayedTags && array_intersect($tags, $this->delayedTags)) {
      return (string) CacheTagsChecksumInterface::INVALID_CHECKSUM_WHILE_IN_TRANSACTION;
    }

    // Remove tags that were already invalidated during this request from the
    // static caches so that another invalidation can occur later in the same
    // request. Without that, written cache items would not be invalidated
    // correctly.
    foreach ($tags as $tag) {
      unset($this->invalidatedTags[$tag]);
    }
    return (string) $this->calculateChecksum($tags);
  }

  /**
   * Implements \Drupal\Core\Cache\CacheTagsChecksumInterface::isValid()
   */
  public function isValid($checksum, array $tags): bool {
    // If there are no cache tags, then there is no cache tag to validate,
    // hence it's always valid.
    if (empty($tags)) {
      return TRUE;
    }
    // Any cache reads in this request involving cache tags whose invalidation
    // has been delayed due to an in-progress transaction are not allowed to use
    // data stored in cache; it must be assumed to be stale. This forces those
    // results to be computed instead. Together with the logic in
    // ::getCurrentChecksum(), it also prevents that computed data from being
    // written to the cache.
    if ($this->delayedTags && array_intersect($tags, $this->delayedTags)) {
      return FALSE;
    }

    return (string) $checksum === (string) $this->calculateChecksum($tags);
  }

  /**
   * Calculates the current checksum for a given set of tags.
   *
   * @param string[] $tags
   *   The array of tags to calculate the checksum for.
   *
   * @return int
   *   The calculated checksum.
   */
  protected function calculateChecksum(array $tags): int {
    $checksum = 0;

    // If there are no cache tags, then there is no cache tag to checksum,
    // so return early.
    if (empty($tags)) {
      return $checksum;
    }

    // This code is optimized for larger sets of cache tags in $this->tagCache
    // and avoids running expensive array functions on it.
    $query_tags = [];
    foreach ($tags as $tag) {
      if (!isset($this->tagCache[$tag])) {
        $query_tags[$tag] = $tag;
      }
    }
    // Preload tags only go into $query_tags, so that the checksum below still
    // covers the requested tags only.
    if ($this->preloadTags) {
      foreach ($this->preloadTags as $tag) {
        if (!isset($this->tagCache[$tag])) {
          $query_tags[$tag] = $tag;
        }
      }
      $this->preloadTags = [];
    }

    if ($query_tags) {
      $tag_invalidations = $this->getTagInvalidationCounts(array_values($query_tags));
      foreach ($query_tags as $tag) {
        $this->tagCache[$tag] = $tag_invalidations[$tag] ?? 0;
      }
    }

    foreach ($tags as $tag) {
      $checksum += $this->tagCache[$tag];
    }

    return $checksum;
  }

  /**
   * Implements \Drupal\Core\Cache\CacheTagsChecksumInterface::reset()
   */
  public function reset(): void {
    $this->tagCache = [];
    $this->invalidatedTags = [];
  }

  /**
   * Implements \Drupal\Core\Cache\CacheTagsChecksumPreloadInterface::registerCacheTagsForPreload()
   */
  public function registerCacheTagsForPreload(array $cache_tags): void {
    if (empty($cache_tags)) {
      return;
    }
    // Don't preload delayed tags that are awaiting invalidation.
    $preloadable_tags = array_diff($cache_tags, $this->delayedTags);
    if ($preloadable_tags) {
      $this->preloadTags = array_merge($this->preloadTags, $preloadable_tags);
    }
  }

  /**
   * Fetches invalidation counts for cache tags.
   *
   * @param string[] $tags
   *   The list of tags to fetch invalidations for.
   *
   * @return int[]
   *   List of invalidation counts keyed by the respective cache tag.
   *
   * @throws \Exception
   *   Thrown if the table could not be created or the database connection
   *   failed.
   */
  abstract protected function getTagInvalidationCounts(array $tags);

  /**
   * Returns the database connection.
   *
   * @return \Drupal\Core\Database\Connection
   *   The database connection.
   */
  abstract protected function getDatabaseConnection();

  /**
   * Marks cache items with any of the specified tags as invalid.
   *
   * @param string[] $tags
   *   The set of tags for which to invalidate cache items.
   *
   * @throws \Exception
   *   Thrown if the table could not be created or the database connection
   *   failed.
   */
  abstract protected function doInvalidateTags(array $tags);

}

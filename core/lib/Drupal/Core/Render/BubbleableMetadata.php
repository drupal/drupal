<?php

/**
 * @file
 * Contains \Drupal\Core\Render\BubbleableMetadata.
 */

namespace Drupal\Core\Render;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableMetadata;

/**
 * Value object used for bubbleable rendering metadata.
 *
 * @see \Drupal\Core\Render\RendererInterface::render()
 */
class BubbleableMetadata extends CacheableMetadata {

  /**
   * Cache contexts.
   *
   * @var string[]
   */
  protected $contexts = [];

  /**
   * Cache tags.
   *
   * @var string[]
   */
  protected $tags = [];

  /**
   * Cache max-age.
   *
   * @var int
   */
  protected $maxAge = Cache::PERMANENT;

  /**
   * Attached assets.
   *
   * @var string[][]
   */
  protected $attached = [];

  /**
   * #post_render_cache metadata.
   *
   * @var array[]
   */
  protected $postRenderCache = [];

  /**
   * Merges the values of another bubbleable metadata object with this one.
   *
   * @param \Drupal\Core\Render\BubbleableMetadata $other
   *   The other bubbleable metadata object.
   * @return static
   *   A new bubbleable metadata object, with the merged data.
   *
   * @todo Add unit test for this in
   *       \Drupal\Tests\Core\Render\BubbleableMetadataTest when
   *       drupal_merge_attached() no longer is a procedural function and remove
   *       the '@codeCoverageIgnore' annotation.
   */
  public function merge(BubbleableMetadata $other) {
    $result = new BubbleableMetadata();
    $result->contexts = Cache::mergeContexts($this->contexts, $other->contexts);
    $result->tags = Cache::mergeTags($this->tags, $other->tags);
    $result->maxAge = Cache::mergeMaxAges($this->maxAge, $other->maxAge);
    $result->attached = \Drupal::service('renderer')->mergeAttachments($this->attached, $other->attached);
    $result->postRenderCache = NestedArray::mergeDeep($this->postRenderCache, $other->postRenderCache);
    return $result;
  }

  /**
   * Applies the values of this bubbleable metadata object to a render array.
   *
   * @param array &$build
   *   A render array.
   */
  public function applyTo(array &$build) {
    $build['#cache']['contexts'] = $this->contexts;
    $build['#cache']['tags'] = $this->tags;
    $build['#cache']['max-age'] = $this->maxAge;
    $build['#attached'] = $this->attached;
    $build['#post_render_cache'] = $this->postRenderCache;
  }

  /**
   * Creates a bubbleable metadata object with values taken from a render array.
   *
   * @param array $build
   *   A render array.
   *
   * @return static
   */
  public static function createFromRenderArray(array $build) {
    $meta = new static();
    $meta->contexts = (isset($build['#cache']['contexts'])) ? $build['#cache']['contexts'] : [];
    $meta->tags = (isset($build['#cache']['tags'])) ? $build['#cache']['tags'] : [];
    $meta->maxAge = (isset($build['#cache']['max-age'])) ? $build['#cache']['max-age'] : Cache::PERMANENT;
    $meta->attached = (isset($build['#attached'])) ? $build['#attached'] : [];
    $meta->postRenderCache = (isset($build['#post_render_cache'])) ? $build['#post_render_cache'] : [];
    return $meta;
  }

  /**
   * Creates a bubbleable metadata object from a depended object.
   *
   * @param \Drupal\Core\Cache\CacheableDependencyInterface|mixed $object
   *   The object whose cacheability metadata to retrieve.
   *
   * @return static
   */
  public static function createFromObject($object) {
    if ($object instanceof CacheableDependencyInterface) {
      $meta = new static();
      $meta->contexts = $object->getCacheContexts();
      $meta->tags = $object->getCacheTags();
      $meta->maxAge = $object->getCacheMaxAge();
      return $meta;
    }

    // Objects that don't implement CacheableDependencyInterface must be assumed
    // to be uncacheable, so set max-age 0.
    $meta = new static();
    $meta->maxAge = 0;
    return $meta;
  }

  /**
   * Gets assets.
   *
   * @return array
   */
  public function getAssets() {
    return $this->attached;
  }

  /**
   * Adds assets.
   *
   * @param array $assets
   *   The associated assets to be attached.
   *
   * @return $this
   */
  public function addAssets(array $assets) {
    $this->attached = NestedArray::mergeDeep($this->attached, $assets);
    return $this;
  }

  /**
   * Sets assets.
   *
   * @param array $assets
   *   The associated assets to be attached.
   *
   * @return $this
   */
  public function setAssets(array $assets) {
    $this->attached = $assets;
    return $this;
  }

  /**
   * Gets #post_render_cache callbacks.
   *
   * @return array
   */
  public function getPostRenderCacheCallbacks() {
    return $this->postRenderCache;
  }

  /**
   * Adds #post_render_cache callbacks.
   *
   * @param string $callback
   *   The #post_render_cache callback that will replace the placeholder with
   *   its eventual markup.
   * @param array $context
   *   An array providing context for the #post_render_cache callback.
   *
   * @see \Drupal\Core\Render\RendererInterface::generateCachePlaceholder()
   *
   * @return $this
   */
  public function addPostRenderCacheCallback($callback, array $context) {
    $this->postRenderCache[$callback][] = $context;
    return $this;
  }

  /**
   * Sets #post_render_cache callbacks.
   *
   * @param array $post_render_cache_callbacks
   *   The associated #post_render_cache callbacks to be executed.
   *
   * @return $this
   */
  public function setPostRenderCacheCallbacks(array $post_render_cache_callbacks) {
    $this->postRenderCache = $post_render_cache_callbacks;
    return $this;
  }

}

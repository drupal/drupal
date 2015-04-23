<?php

/**
 * @file
 * Contains \Drupal\Core\Render\BubbleableMetadata.
 */

namespace Drupal\Core\Render;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheableMetadata;

/**
 * Value object used for bubbleable rendering metadata.
 *
 * @see \Drupal\Core\Render\RendererInterface::render()
 */
class BubbleableMetadata extends CacheableMetadata {

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
   * @param \Drupal\Core\Cache\CacheableMetadata $other
   *   The other bubbleable metadata object.
   *
   * @return static
   *   A new bubbleable metadata object, with the merged data.
   */
  public function merge(CacheableMetadata $other) {
    $result = parent::merge($other);
    if ($other instanceof BubbleableMetadata) {
      $result->attached = \Drupal::service('renderer')->mergeAttachments($this->attached, $other->attached);
      $result->postRenderCache = NestedArray::mergeDeep($this->postRenderCache, $other->postRenderCache);
    }
    return $result;
  }

  /**
   * Applies the values of this bubbleable metadata object to a render array.
   *
   * @param array &$build
   *   A render array.
   */
  public function applyTo(array &$build) {
    parent::applyTo($build);
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
    $meta = parent::createFromRenderArray($build);
    $meta->attached = (isset($build['#attached'])) ? $build['#attached'] : [];
    $meta->postRenderCache = (isset($build['#post_render_cache'])) ? $build['#post_render_cache'] : [];
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

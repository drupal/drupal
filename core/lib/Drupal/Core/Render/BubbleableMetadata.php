<?php

/**
 * @file
 * Contains \Drupal\Core\Render\BubbleableMetadata.
 */

namespace Drupal\Core\Render;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;

/**
 * Value object used for bubbleable rendering metadata.
 *
 * @see \Drupal\Core\Render\RendererInterface::render()
 */
class BubbleableMetadata {

  /**
   * Cache tags.
   *
   * @var string[]
   */
  protected $tags;

  /**
   * Attached assets.
   *
   * @var string[][]
   */
  protected $attached;

  /**
   * #post_render_cache metadata.
   *
   * @var array[]
   */
  protected $postRenderCache;

  /**
   * Constructs a BubbleableMetadata value object.
   *
   * @param string[] $tags
   *   An array of cache tags.
   * @param array $attached
   *   An array of attached assets.
   * @param array $post_render_cache
   *   An array of #post_render_cache metadata.
   */
  public function __construct(array $tags = [], array $attached = [], array $post_render_cache = []) {
    $this->tags = $tags;
    $this->attached = $attached;
    $this->postRenderCache = $post_render_cache;
  }

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
    $result->tags = Cache::mergeTags($this->tags, $other->tags);
    $result->attached = Renderer::mergeAttachments($this->attached, $other->attached);
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
    $build['#cache']['tags'] = $this->tags;
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
    $meta->tags = (isset($build['#cache']['tags'])) ? $build['#cache']['tags'] : [];
    $meta->attached = (isset($build['#attached'])) ? $build['#attached'] : [];
    $meta->postRenderCache = (isset($build['#post_render_cache'])) ? $build['#post_render_cache'] : [];
    return $meta;
  }

}

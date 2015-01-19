<?php

/**
 * @file
 * Contains \Drupal\filter\FilterProcessResult.
 */

namespace Drupal\filter;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Used to return values from a text filter plugin's processing method.
 *
 * The typical use case for a text filter plugin's processing method is to just
 * apply some filtering to the given text, but for more advanced use cases,
 * it may be necessary to also:
 * 1. declare asset libraries to be loaded;
 * 2. declare cache tags that the filtered text depends upon, so when either of
 *   those cache tags is invalidated, the filtered text should also be
 *   invalidated;
 * 3. apply uncacheable filtering, for example because it differs per user.
 *
 * In case a filter needs one or more of these advanced use cases, it can use
 * the additional methods available.
 *
 * The typical use case:
 * @code
 * public function process($text, $langcode) {
 *   // Modify $text.
 *
 *   return new FilterProcess($text);
 * }
 * @endcode
 *
 * The advanced use cases:
 * @code
 * public function process($text, $langcode) {
 *   // Modify $text.
 *
 *   $result = new FilterProcess($text);
 *
 *   // Associate assets to be attached.
 *   $result->setAssets(array(
 *     'library' => array(
 *        'filter/caption',
 *     ),
 *   ));
 *
 *   // Associate cache tags to be invalidated by.
 *   $result->setCacheTags($node->getCacheTags());
 *
 *   return $result;
 * }
 * @endcode
 */
class FilterProcessResult {

  /**
   * The processed text.
   *
   * @see \Drupal\filter\Plugin\FilterInterface::process()
   *
   * @var string
   */
  protected $processedText;

  /**
   * An array of associated assets to be attached.
   *
   * @see drupal_process_attached()
   *
   * @var array
   */
  protected $assets;

  /**
   * The attached cache tags.
   *
   * @see drupal_render_collect_cache_tags()
   *
   * @var array
   */
  protected $cacheTags;

  /**
   * The associated #post_render_cache callbacks.
   *
   * @see _drupal_render_process_post_render_cache()
   *
   * @var array
   */
  protected $postRenderCacheCallbacks;

  /**
   * Constructs a FilterProcessResult object.
   *
   * @param string $processed_text
   *   The text as processed by a text filter.
   */
  public function __construct($processed_text) {
    $this->processedText = $processed_text;

    $this->assets = array();
    $this->cacheTags = array();
    $this->postRenderCacheCallbacks = array();
  }

  /**
   * Gets the processed text.
   *
   * @return string
   */
  public function getProcessedText() {
    return $this->processedText;
  }

  /**
   * Gets the processed text.
   *
   * @return string
   */
  public function __toString() {
    return $this->getProcessedText();
  }

  /**
   * Sets the processed text.
   *
   * @param string $processed_text
   *   The text as processed by a text filter.
   *
   * @return $this
   */
  public function setProcessedText($processed_text) {
    $this->processedText = $processed_text;
    return $this;
  }

  /**
   * Gets cache tags associated with the processed text.
   *
   * @return array
   */
  public function getCacheTags() {
    return $this->cacheTags;
  }

  /**
   * Adds cache tags associated with the processed text.
   *
   * @param array $cache_tags
   *   The cache tags to be added.
   *
   * @return $this
   */
  public function addCacheTags(array $cache_tags) {
    $this->cacheTags = Cache::mergeTags($this->cacheTags, $cache_tags);
    return $this;
  }

  /**
   * Sets cache tags associated with the processed text.
   *
   * @param array $cache_tags
   *   The cache tags to be associated.
   *
   * @return $this
   */
  public function setCacheTags(array $cache_tags) {
    $this->cacheTags = $cache_tags;
    return $this;
  }

  /**
   * Gets assets associated with the processed text.
   *
   * @return array
   */
  public function getAssets() {
    return $this->assets;
  }

  /**
   * Adds assets associated with the processed text.
   *
   * @param array $assets
   *   The associated assets to be attached.
   *
   * @return $this
   */
  public function addAssets(array $assets) {
    $this->assets = NestedArray::mergeDeep($this->assets, $assets);
    return $this;
  }

  /**
   * Sets assets associated with the processed text.
   *
   * @param array $assets
   *   The associated assets to be attached.
   *
   * @return $this
   */
  public function setAssets(array $assets) {
    $this->assets = $assets;
    return $this;
  }

  /**
   * Gets #post_render_cache callbacks associated with the processed text.
   *
   * @return array
   */
  public function getPostRenderCacheCallbacks() {
    return $this->postRenderCacheCallbacks;
  }

  /**
   * Adds #post_render_cache callbacks associated with the processed text.
   *
   * @param string $callback
   *   The #post_render_cache callback that will replace the placeholder with
   *   its eventual markup.
   * @param array $context
   *   An array providing context for the #post_render_cache callback.
   *
   * @see drupal_render_cache_generate_placeholder()
   *
   * @return $this
   */
  public function addPostRenderCacheCallback($callback, array $context) {
    $this->postRenderCacheCallbacks[$callback][] = $context;
    return $this;
  }

  /**
   * Sets #post_render_cache callbacks associated with the processed text.
   *
   * @param array $post_render_cache_callbacks
   *   The associated #post_render_cache callbacks to be executed.
   *
   * @return $this
   */
  public function setPostRenderCacheCallbacks(array $post_render_cache_callbacks) {
    $this->postRenderCacheCallbacks = $post_render_cache_callbacks;
    return $this;
  }

  /**
   * Returns the attached asset libraries, etc. as a bubbleable metadata object.
   *
   * @return \Drupal\Core\Render\BubbleableMetadata
   */
  public function getBubbleableMetadata() {
    return new BubbleableMetadata(
      $this->getCacheTags(),
      $this->getAssets(),
      $this->getPostRenderCacheCallbacks()
    );
  }

}

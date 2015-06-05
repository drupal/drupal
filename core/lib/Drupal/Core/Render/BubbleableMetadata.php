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
    return $meta;
  }

  /**
   * Gets attachments.
   *
   * @return array
   *   The attachments
   */
  public function getAttachments() {
    return $this->attached;
  }

  /**
   * Adds attachments.
   *
   * @param array $attachments
   *   The attachments to add.
   *
   * @return $this
   */
  public function addAttachments(array $attachments) {
    $this->attached = \Drupal::service('renderer')->mergeAttachments($this->attached, $attachments);
    return $this;
  }

  /**
   * Sets attachments.
   *
   * @param array $attachments
   *   The attachments to set.
   *
   * @return $this
   */
  public function setAttachments(array $attachments) {
    $this->attached = $attachments;
    return $this;
  }

  /**
   * Gets assets.
   *
   * @return array
   *
   * @deprecated Use ::getAttachments() instead. To be removed before Drupal 8.0.0.
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
   *
   * @deprecated Use ::addAttachments() instead. To be removed before Drupal 8.0.0.
   */
  public function addAssets(array $assets) {
    return $this->addAttachments($assets);
  }

  /**
   * Sets assets.
   *
   * @param array $assets
   *   The associated assets to be attached.
   *
   * @return $this
   *
   * @deprecated Use ::setAttachments() instead. To be removed before Drupal 8.0.0.
   */
  public function setAssets(array $assets) {
    $this->attached = $assets;
    return $this;
  }

}

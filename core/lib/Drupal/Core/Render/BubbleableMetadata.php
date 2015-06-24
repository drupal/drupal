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
class BubbleableMetadata extends CacheableMetadata implements AttachmentsInterface {

  use AttachmentsTrait;

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

    // This is called many times per request, so avoid merging unless absolutely
    // necessary.
    if ($other instanceof BubbleableMetadata) {
      if (empty($this->attachments)) {
        $result->attachments = $other->attachments;
      }
      elseif (empty($other->attachments)) {
        $result->attachments = $this->attachments;
      }
      else {
        $result->attachments = static::mergeAttachments($this->attachments, $other->attachments);
      }
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
    $build['#attached'] = $this->attachments;
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
    $meta->attachments = (isset($build['#attached'])) ? $build['#attached'] : [];
    return $meta;
  }

  /**
   * Merges two attachments arrays (which live under the '#attached' key).
   *
   * The values under the 'drupalSettings' key are merged in a special way, to
   * match the behavior of:
   *
   * @code
   *   jQuery.extend(true, {}, $settings_items[0], $settings_items[1], ...)
   * @endcode
   *
   * This means integer indices are preserved just like string indices are,
   * rather than re-indexed as is common in PHP array merging.
   *
   * Example:
   * @code
   * function module1_page_attachments(&$page) {
   *   $page['a']['#attached']['drupalSettings']['foo'] = ['a', 'b', 'c'];
   * }
   * function module2_page_attachments(&$page) {
   *   $page['#attached']['drupalSettings']['foo'] = ['d'];
   * }
   * // When the page is rendered after the above code, and the browser runs the
   * // resulting <SCRIPT> tags, the value of drupalSettings.foo is
   * // ['d', 'b', 'c'], not ['a', 'b', 'c', 'd'].
   * @endcode
   *
   * By following jQuery.extend() merge logic rather than common PHP array merge
   * logic, the following are ensured:
   * - Attaching JavaScript settings is idempotent: attaching the same settings
   *   twice does not change the output sent to the browser.
   * - If pieces of the page are rendered in separate PHP requests and the
   *   returned settings are merged by JavaScript, the resulting settings are
   *   the same as if rendered in one PHP request and merged by PHP.
   *
   * @param array $a
   *   An attachments array.
   * @param array $b
   *   Another attachments array.
   *
   * @return array
   *   The merged attachments array.
   */
  public static function mergeAttachments(array $a, array $b) {
    // If both #attached arrays contain drupalSettings, then merge them
    // correctly; adding the same settings multiple times needs to behave
    // idempotently.
    if (!empty($a['drupalSettings']) && !empty($b['drupalSettings'])) {
      $drupalSettings = NestedArray::mergeDeepArray(array($a['drupalSettings'], $b['drupalSettings']), TRUE);
      // No need for re-merging them.
      unset($a['drupalSettings']);
      unset($b['drupalSettings']);
    }
    // Optimize merging of placeholders: no need for deep merging.
    if (!empty($a['placeholders']) && !empty($b['placeholders'])) {
      $placeholders = $a['placeholders'] + $b['placeholders'];
      // No need for re-merging them.
      unset($a['placeholders']);
      unset($b['placeholders']);
    }
    // Apply the normal merge.
    $a = array_merge_recursive($a, $b);
    if (isset($drupalSettings)) {
      // Save the custom merge for the drupalSettings.
      $a['drupalSettings'] = $drupalSettings;
    }
    if (isset($placeholders)) {
      // Save the custom merge for the placeholders.
      $a['placeholders'] = $placeholders;
    }
    return $a;
  }

}

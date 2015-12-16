<?php

/**
 * @file
 * Contains \Drupal\Core\Render\PlaceholderGeneratorInterface.
 */

namespace Drupal\Core\Render;

/**
 * Defines an interface for turning a render array into a placeholder.
 *
 * This encapsulates logic related to generating placeholders.
 *
 * Makes it possible to determine whether a render array can be placeholdered
 * (it can be reconstructed independently of the request context), whether a
 * render array should be placeholdered (its cacheability meets the conditions),
 * and to create a placeholder.
 *
 * @see \Drupal\Core\Render\RendererInterface
 */
interface PlaceholderGeneratorInterface {

  /**
   * Analyzes whether the given render array can be placeholdered.
   *
   * @param array $element
   *   A render array. Its #lazy_builder and #create_placeholder properties are
   *   analyzed.
   *
   * @return bool
   */
  public function canCreatePlaceholder(array $element);

  /**
   * Whether the given render array should be automatically placeholdered.
   *
   * The render array should be placeholdered if its cacheability either has a
   * cache context with too high cardinality, a cache tag with a too high
   * invalidation rate, or a max-age that is too low. Either of these would make
   * caching ineffective, and thus we choose to placeholder instead.
   *
   * @param array $element
   *   The render array whose cacheability to analyze.
   *
   * @return bool
   *   Whether the given render array's cacheability meets the placeholdering
   *   conditions.
   */
  public function shouldAutomaticallyPlaceholder(array $element);

  /**
   * Turns the given element into a placeholder.
   *
   * Placeholdering allows us to avoid "poor cacheability contamination": this
   * maps the current render array to one that only has #markup and #attached,
   * and #attached contains a placeholder with this element's prior cacheability
   * metadata. In other words: this placeholder is perfectly cacheable, the
   * placeholder replacement logic effectively cordons off poor cacheability.
   *
   * @param array $element
   *   The render array to create a placeholder for.
   *
   * @return array
   *   Render array with placeholder markup and the attached placeholder
   *   replacement metadata.
   */
  public function createPlaceholder(array $element);

}

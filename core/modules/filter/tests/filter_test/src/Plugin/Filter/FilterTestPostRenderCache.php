<?php

/**
 * @file
 * Contains \Drupal\filter_test\Plugin\Filter\FilterTestPostRenderCache.
 */

namespace Drupal\filter_test\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a test filter to associate #post_render_cache callbacks.
 *
 * @Filter(
 *   id = "filter_test_post_render_cache",
 *   title = @Translation("Testing filter"),
 *   description = @Translation("Appends a placeholder to the content; associates #post_render_cache callbacks."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class FilterTestPostRenderCache extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $callback = '\Drupal\filter_test\Plugin\Filter\FilterTestPostRenderCache::renderDynamicThing';
    $context = array(
      'thing' => 'llama',
    );
    $placeholder = drupal_render_cache_generate_placeholder($callback, $context);
    $result = new FilterProcessResult($text . '<p>' . $placeholder . '</p>');
    $result->addPostRenderCacheCallback($callback, $context);
    return $result;
  }

  /**
   * #post_render_cache callback; replaces placeholder with a dynamic thing.
   *
   * @param array $element
   *   The renderable array that contains the to be replaced placeholder.
   * @param array $context
   *   An array with the following keys:
   *   - thing: a "thing" string
   *
   * @return array
   *   A renderable array containing the comment form.
   */
  public static function renderDynamicThing(array $element, array $context) {
    $callback = '\Drupal\filter_test\Plugin\Filter\FilterTestPostRenderCache::renderDynamicThing';
    $placeholder = drupal_render_cache_generate_placeholder($callback, $context);
    $markup = format_string('This is a dynamic @thing.', array('@thing' => $context['thing']));
    $element['#markup'] = str_replace($placeholder, $markup, $element['#markup']);
    return $element;
  }

}

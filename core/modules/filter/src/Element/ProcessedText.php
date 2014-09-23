<?php

/**
 * @file
 * Contains \Drupal\filter\Element\ProcessedText.
 */

namespace Drupal\filter\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\filter\Entity\FilterFormat;
use Drupal\filter\Plugin\FilterInterface;

/**
 * Provides a processed text render element.
 *
 * @todo Annotate once https://www.drupal.org/node/2326409 is in.
 *   RenderElement("processed_text")
 */
class ProcessedText extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#text' => '',
      '#format' => NULL,
      '#filter_types_to_skip' => array(),
      '#langcode' => '',
      '#pre_render' => array(
        array($class, 'preRenderText'),
      ),
    );
  }

  /**
   * Pre-render callback: Renders a processed text element into #markup.
   *
   * Runs all the enabled filters on a piece of text.
   *
   * Note: Because filters can inject JavaScript or execute PHP code, security
   * is vital here. When a user supplies a text format, you should validate it
   * using $format->access() before accepting/using it. This is normally done in
   * the validation stage of the Form API. You should for example never make a
   * preview of content in a disallowed format.
   *
   * @param array $element
   *   A structured array with the following key-value pairs:
   *   - #text: containing the text to be filtered
   *   - #format: containing the machine name of the filter format to be used to
   *     filter the text. Defaults to the fallback format.
   *   - #langcode: the language code of the text to be filtered, e.g. 'en' for
   *     English. This allows filters to be language-aware so language-specific
   *     text replacement can be implemented. Defaults to an empty string.
   *   - #filter_types_to_skip: an array of filter types to skip, or an empty
   *     array (default) to skip no filter types. All of the format's filters
   *     will be applied, except for filters of the types that are marked to be
   *     skipped. FilterInterface::TYPE_HTML_RESTRICTOR is the only type that
   *     cannot be skipped.
   *
   * @return array
   *   The passed-in element with the filtered text in '#markup'.
   *
   * @ingroup sanitization
   */
  public static function preRenderText($element) {
    $format_id = $element['#format'];
    $filter_types_to_skip = $element['#filter_types_to_skip'];
    $text = $element['#text'];
    $langcode = $element['#langcode'];

    if (!isset($format_id)) {
      $format_id = static::configFactory()->get('filter.settings')->get('fallback_format');
    }
    // If the requested text format does not exist, the text cannot be filtered.
    /** @var \Drupal\filter\Entity\FilterFormat $format **/
    if (!$format = FilterFormat::load($format_id)) {
      static::logger('filter')->alert('Missing text format: %format.', array('%format' => $format_id));
      $element['#markup'] = '';
      return $element;
    }

    $filter_must_be_applied = function(FilterInterface $filter) use ($filter_types_to_skip) {
      $enabled = $filter->status === TRUE;
      $type = $filter->getType();
      // Prevent FilterInterface::TYPE_HTML_RESTRICTOR from being skipped.
      $filter_type_must_be_applied = $type == FilterInterface::TYPE_HTML_RESTRICTOR || !in_array($type, $filter_types_to_skip);
      return $enabled && $filter_type_must_be_applied;
    };

    // Convert all Windows and Mac newlines to a single newline, so filters only
    // need to deal with one possibility.
    $text = str_replace(array("\r\n", "\r"), "\n", $text);

    // Get a complete list of filters, ordered properly.
    /** @var \Drupal\filter\Plugin\FilterInterface[] $filters **/
    $filters = $format->filters();

    // Give filters a chance to escape HTML-like data such as code or formulas.
    foreach ($filters as $filter) {
      if ($filter_must_be_applied($filter)) {
        $text = $filter->prepare($text, $langcode);
      }
    }

    // Perform filtering.
    $all_cache_tags = array();
    $all_assets = array();
    $all_post_render_cache_callbacks = array();
    foreach ($filters as $filter) {
      if ($filter_must_be_applied($filter)) {
        $result = $filter->process($text, $langcode);
        $all_assets[] = $result->getAssets();
        $all_cache_tags[] = $result->getCacheTags();
        $all_post_render_cache_callbacks[] = $result->getPostRenderCacheCallbacks();
        $text = $result->getProcessedText();
      }
    }

    // Filtering done, store in #markup.
    $element['#markup'] = $text;

    // Collect all cache tags.
    if (isset($element['#cache']) && isset($element['#cache']['tags'])) {
      // Prepend the original cache tags array.
      array_unshift($all_cache_tags, $element['#cache']['tags']);
    }
    // Prepend the text format's cache tags array.
    array_unshift($all_cache_tags, $format->getCacheTag());
    $element['#cache']['tags'] = NestedArray::mergeDeepArray($all_cache_tags);

    // Collect all attached assets.
    if (isset($element['#attached'])) {
      // Prepend the original attached assets array.
      array_unshift($all_assets, $element['#attached']);
    }
    $element['#attached'] = NestedArray::mergeDeepArray($all_assets);

    // Collect all #post_render_cache callbacks.
    if (isset($element['#post_render_cache'])) {
      // Prepend the original attached #post_render_cache array.
      array_unshift($all_assets, $element['#post_render_cache']);
    }
    $element['#post_render_cache'] = NestedArray::mergeDeepArray($all_post_render_cache_callbacks);

    return $element;
  }

  /**
   * Wraps a logger channel.
   *
   * @return \Psr\Log\LoggerInterface
   */
  protected static function logger($channel) {
    return \Drupal::logger($channel);
  }

  /**
   * Wraps the config factory.
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected static function configFactory() {
    return \Drupal::configFactory();
  }

}

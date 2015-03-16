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
 * 3. declare cache context to vary by, e.g. 'language' to do language-specific
 *    filtering.
 * 4. declare a maximum age for the filtered text
 * 5. apply uncacheable filtering, for example because it differs per user.
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
 *   // Associate cache contexts to vary by.
 *   $result->setCacheContexts(['language']);
 *
 *   // Associate cache tags to be invalidated by.
 *   $result->setCacheTags($node->getCacheTags());
 *
 *   // Associate a maximum age.
 *   $result->setCacheMaxAge(300); // 5 minutes.
 *
 *   return $result;
 * }
 * @endcode
 */
class FilterProcessResult extends BubbleableMetadata {

  /**
   * The processed text.
   *
   * @see \Drupal\filter\Plugin\FilterInterface::process()
   *
   * @var string
   */
  protected $processedText;

  /**
   * Constructs a FilterProcessResult object.
   *
   * @param string $processed_text
   *   The text as processed by a text filter.
   */
  public function __construct($processed_text) {
    $this->processedText = $processed_text;
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
}

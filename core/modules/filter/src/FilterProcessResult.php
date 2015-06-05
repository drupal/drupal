<?php

/**
 * @file
 * Contains \Drupal\filter\FilterProcessResult.
 */

namespace Drupal\filter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Template\Attribute;

/**
 * Used to return values from a text filter plugin's processing method.
 *
 * The typical use case for a text filter plugin's processing method is to just
 * apply some filtering to the given text, but for more advanced use cases,
 * it may be necessary to also:
 * - Declare asset libraries to be loaded.
 * - Declare cache tags that the filtered text depends upon, so when either of
 *   those cache tags is invalidated, the filtered text should also be
 *   invalidated.
 * - Declare cache context to vary by, e.g. 'language' to do language-specific
 *   filtering.
 * - Declare a maximum age for the filtered text.
 * - Apply uncacheable filtering, for example because it differs per user.
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
 *   $result->setAttachments(array(
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

  /**
   * Creates a placeholder.
   *
   * This generates its own placeholder markup for one major reason: to not have
   * FilterProcessResult depend on the Renderer service, because this is a value
   * object. As a side-effect and added benefit, this makes it easier to
   * distinguish placeholders for filtered text versus generic render system
   * placeholders.
   *
   * @param string $callback
   *   The #lazy_builder callback that will replace the placeholder with its
   *   eventual markup.
   * @param array $args
   *   The arguments for the #lazy_builder callback.
   *
   * @return string
   *   The placeholder markup.
   */
  public function createPlaceholder($callback, array $args) {
    // Generate placeholder markup.
    // @see \Drupal\Core\Render\Renderer::createPlaceholder()
    $attributes = new Attribute();
    $attributes['callback'] = $callback;
    $attributes['arguments'] = UrlHelper::buildQuery($args);
    $attributes['token'] = hash('sha1', serialize([$callback, $args]));
    $placeholder_markup = Html::normalize('<drupal-filter-placeholder' . $attributes . '></drupal-filter-placeholder>');

    // Add the placeholder attachment.
    $this->addAttachments([
      'placeholders' => [
        $placeholder_markup => [
          '#lazy_builder' => [$callback, $args],
        ]
      ],
    ]);

    // Return the placeholder markup, so that the filter wanting to use a
    // placeholder can actually insert the placeholder markup where it needs the
    // placeholder to be replaced.
    return $placeholder_markup;
  }

}

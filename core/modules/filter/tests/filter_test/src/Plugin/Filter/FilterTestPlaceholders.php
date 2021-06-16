<?php

namespace Drupal\filter_test\Plugin\Filter;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a test filter to use placeholders.
 *
 * @Filter(
 *   id = "filter_test_placeholders",
 *   title = @Translation("Testing filter"),
 *   description = @Translation("Appends a placeholder to the content; associates #lazy_builder callback."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class FilterTestPlaceholders extends FilterBase implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);
    $placeholder = $result->createPlaceholder('\Drupal\filter_test\Plugin\Filter\FilterTestPlaceholders::renderDynamicThing', ['llama']);
    $result->setProcessedText($text . '<p>' . $placeholder . '</p>');
    return $result;
  }

  /**
   * #lazy_builder callback; builds a render array containing the dynamic thing.
   *
   * @param string $thing
   *   A "thing" string.
   *
   * @return array
   *   A renderable array.
   */
  public static function renderDynamicThing($thing) {
    return [
      '#markup' => new FormattableMarkup('This is a dynamic @thing.', ['@thing' => $thing]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['renderDynamicThing'];
  }

}

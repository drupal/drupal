<?php

declare(strict_types=1);

namespace Drupal\filter_test\Plugin\Filter;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\filter\Attribute\Filter;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Plugin\FilterInterface;

/**
 * Provides a test filter to use placeholders.
 */
#[Filter(
  id: "filter_test_placeholders",
  title: new TranslatableMarkup("Testing filter"),
  description: new TranslatableMarkup("Appends placeholders to the content; associates #lazy_builder callbacks."),
  type: FilterInterface::TYPE_TRANSFORM_REVERSIBLE
)]
class FilterTestPlaceholders extends FilterBase implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);
    $placeholder_with_argument = $result->createPlaceholder('\Drupal\filter_test\Plugin\Filter\FilterTestPlaceholders::renderDynamicThing', ['llama']);
    $placeholder_without_arguments = $result->createPlaceholder('\Drupal\filter_test\Plugin\Filter\FilterTestPlaceholders::renderStaticThing', []);
    $result->setProcessedText($text . '<p>' . $placeholder_with_argument . '</p>' . '<p>' . $placeholder_without_arguments . '</p>');
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
   * #lazy_builder callback; builds a render array.
   *
   * @return array
   *   A renderable array.
   */
  public static function renderStaticThing(): array {
    return [
      '#markup' => 'This is a static llama.',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return [
      'renderDynamicThing',
      'renderStaticThing',
    ];
  }

}

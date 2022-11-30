<?php

declare(strict_types = 1);

namespace Drupal\filter\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to lazy load tracked images.
 *
 * @Filter(
 *   id = "filter_image_lazy_load",
 *   title = @Translation("Lazy load images"),
 *   description = @Translation("Instruct browsers to lazy load images if dimensions are specified. Use in conjunction with and place after the 'Track images uploaded via a Text Editor' filter that adds image dimensions required for lazy loading. Results can be overridden by <code>&lt;img loading=&quot;eager&quot;&gt;</code>."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 *   weight = 15
 * )
 */
final class FilterImageLazyLoad extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode): FilterProcessResult {
    $result = new FilterProcessResult($text);

    // If there are no images, return early.
    if (stripos($text, '<img ') === FALSE && stripos($text, 'data-entity-type="file"') === FALSE) {
      return $result;
    }

    return $result->setProcessedText($this->transformImages($text));
  }

  /**
   * Transform markup of images to include loading="lazy".
   *
   * @param string $text
   *   The markup to transform.
   *
   * @return string
   *   The transformed text with loading attribute added.
   */
  private function transformImages(string $text): string {
    $dom = Html::load($text);
    $xpath = new \DOMXPath($dom);
    // Only set loading="lazy" if no existing loading attribute is specified and
    // dimensions are specified.
    foreach ($xpath->query('//img[not(@loading="eager") and @width and @height]') as $element) {
      assert($element instanceof \DOMElement);
      $element->setAttribute('loading', 'lazy');
    }
    return Html::serialize($dom);
  }

}

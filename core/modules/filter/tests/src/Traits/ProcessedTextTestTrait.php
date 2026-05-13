<?php

declare(strict_types=1);

namespace Drupal\Tests\filter\Traits;

use Drupal\Component\Render\MarkupInterface;

/**
 * Provides a trait for testing processed text.
 */
trait ProcessedTextTestTrait {

  /**
   * Processes a text using the renderer.
   *
   * Important: This technique of rendering processed text is only permitted for
   * testing isolated string cases. This is because flattening the render array
   * leads to loss of cacheability metadata. In production code, use the render
   * array directly.
   *
   * @param string $text
   *   Text to process.
   * @param string|null $format
   *   (optional) The format to use. Defaults to the fallback format.
   * @param string|null $langcode
   *   (optional) The language code to use. Defaults to the current language.
   * @param array $filterTypesToSkip
   *   (optional) An array of filter types to skip.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The processed text.
   */
  protected function processText(string $text, ?string $format = NULL, ?string $langcode = NULL, array $filterTypesToSkip = []): MarkupInterface {
    $build = [
      '#type' => 'processed_text',
      '#text' => $text,
      '#format' => $format,
      '#langcode' => $langcode,
      '#filter_types_to_skip' => $filterTypesToSkip,
    ];
    return \Drupal::service('renderer')->renderInIsolation($build);
  }

}

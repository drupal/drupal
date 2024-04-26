<?php

namespace Drupal\Core\Asset;

use Drupal\Core\File\FileUrlGeneratorInterface;

/**
 * Renders CSS assets.
 */
class CssCollectionRenderer implements AssetCollectionRendererInterface {

  /**
   * Constructs a CssCollectionRenderer.
   *
   * @param \Drupal\Core\Asset\AssetQueryStringInterface $assetQueryString
   *   The asset query string.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator
   *   The file URL generator.
   */
  public function __construct(
    protected AssetQueryStringInterface $assetQueryString,
    protected FileUrlGeneratorInterface $fileUrlGenerator,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function render(array $css_assets) {
    $elements = [];

    // A dummy query-string is added to filenames, to gain control over
    // browser-caching. The string changes on every update or full cache
    // flush, forcing browsers to load a new copy of the files, as the
    // URL changed.
    $query_string = $this->assetQueryString->get();

    // Defaults for LINK and STYLE elements.
    $link_element_defaults = [
      '#type' => 'html_tag',
      '#tag' => 'link',
      '#attributes' => [
        'rel' => 'stylesheet',
      ],
    ];

    foreach ($css_assets as $css_asset) {
      $element = $link_element_defaults;
      $element['#attributes']['media'] = $css_asset['media'];

      switch ($css_asset['type']) {
        // For file items, output a LINK tag for file CSS assets.
        case 'file':
          $element['#attributes']['href'] = $this->fileUrlGenerator->generateString($css_asset['data']);
          // Only add the cache-busting query string if this isn't an aggregate
          // file.
          if (!isset($css_asset['preprocessed'])) {
            $query_string_separator = str_contains($css_asset['data'], '?') ? '&' : '?';
            $element['#attributes']['href'] .= $query_string_separator . $query_string;
          }
          break;

        case 'external':
          $element['#attributes']['href'] = $css_asset['data'];
          break;

        default:
          throw new \Exception('Invalid CSS asset type.');
      }

      // Merge any additional attributes.
      if (!empty($css_asset['attributes'])) {
        $element['#attributes'] += $css_asset['attributes'];
      }

      $elements[] = $element;
    }

    return $elements;
  }

}
